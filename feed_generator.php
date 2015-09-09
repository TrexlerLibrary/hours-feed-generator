<?php
/**
 *  HOURS, REVISITED (2015 edition)
 *  
 *  Here we define the functions used to pull a list of events from a series of
 *  Google Calendars + form it into our own JSON api. Currently, it's being used
 *  exclusively on the Library's website, but this could also be used for the
 *  long-fabled digital signage or things of that ilk.
 *
 *  NOTE: I refer to associative arrays as "hashes" b/c it's less to write and
 *  it's _kinda_ what they are.
 *
 *  NOTE: a "strtotime-ready string" is one that will actually get a result from `strtotime`.
 *  see: http://php.net/manual/en/datetime.formats.php
 */

/**
 *  URL to which our query string is appended
 */

define("GOOGLE_CALENDAR_URL_BASE", "https://www.googleapis.com/calendar/v3/calendars/");

/**
 *  Return fields from our request to the Google Calendar API. I'm having trouble finding
 *  the documentation for this, but it's essentially a comma-delimited list of what fields
 *  should be returned. Sub-fields are encased in parentheses. To have everything returned
 *  from Google, comment-out this definition.
 */

define("CALENDAR_FIELDS", "items(end,start,summary,description,id,htmlLink)");

/**
 *  Where to store the finished JSON files. Defined in `config.php`, but defaults
 *  to the current directory in a folder named `json`
 */

if ( !defined("OUTPUT_LOCATION") ) {
    define("OUTPUT_LOCATION", dirname(__FILE__) . "/json");
}


/**
 *  formatting related constants to be used w/in PHP's `date()` function. defined
 *  within the `config.php` file.
 */

if ( !defined("DAY_FORMAT") ) { define("DAY_FORMAT", "l, F jS"); }
if ( !defined("HOUR_FORMAT") ) { define("HOUR_FORMAT", "g:i a"); }

/* ====================== */

/**
 *  takes a hash of Google Calendars, optional start and end date-strings (which are
 *  passed through `strtotime()`, so make them capable of working w/ that), and returns
 *  a JSON-encoded string of the results. The hash should be set-up as such:
 *
 *      array(
 *          "calendar_name" => "cal_id",
 *          // ... 
 *      )
 *
 *  Fails silently: if a Cal ID is invalid, or, for whatever reason, no connection can be
 *  made, that calendar is set as an empty hash.  
 *
 *  @param  array    hash of calendar names and calendar ids
 *  @param  string   (optional) strtotime-ready string for start of range
 *  @param  string   (optional) strtotime-ready string for end of range
 *  @return string   json-encoded string
 */

function build_feed(array $calendars, $start = "today", $end = "tomorrow") {
    $feed = array();
    foreach( $calendars as $name => $id ) {
        try { $raw_feed = fetch_feed($id, strtotime($start), strtotime($end)); }
        catch( Exception $e ) {
            $feed[$name] = array();
            continue;
        }

        $clean_feed = array();

        foreach( $raw_feed['items'] as $item ) {
            array_push($clean_feed, calendar_scrub($item));
        }

        usort($clean_feed, function($a, $b) {
            return strtotime($a['dateTime']['start']) - strtotime($b['dateTime']['start']);
        });

        $feed[$name] = $clean_feed;
    }

    return json_encode($feed);
}


/**
 *  returns the URL to retrieve the JSON results for the calendar api
 *
 *  NOTE: convert start + end to timestamps before calling this
 *
 *  @param string       google calendar ID
 *  @param timestamp    unix timestamp of start range
 *  @param timestamp    unix timestamp of range end
 */

function build_calendar_url($cal, $start, $end) {
    return GOOGLE_CALENDAR_URL_BASE
        . $cal
        . "/events?"
        . (defined("CALENDAR_FIELDS") ? "fields=" . CALENDAR_FIELDS : "")
        . "&singleEvents=true"

        // timeMin needs to start at 3a (maybe 4a?) instead of 00:00:00 because
        // otherwise it'll grab the sunday prior b/c of it ending at 1a
        . "&timeMin=" . date("Y-m-d\T03:00:00P", $start)
        . "&timeMax=" . date("Y-m-d\T23:59:59P", $end)
        . "&key=" . GOOGLE_API_KEY
        ;
}

/**
 *  Takes an individual-day hash of returned results from Google and formats
 *  them how we'd prefer them. Returns the newly-formatted hash, which looks like:
 *
 *      array(
 *          "title" => string        // pulled from Google's "summary" field
 *          "info" => string         // pulled from Google's "description" field 
 *          "day" => string          // the event's day, using DAY_FORMAT string constant
 *          "all_day" => boolean     // whether the event is all day or not
 *          "dateTime" => array(     // normalized dateTime string
 *              "start" => dateTime
 *              "end" => dateTime
 *          )
 *          "formatted" => array(    // start + end times formatted using TIME_FORMAT string constant
 *              "start" => string
 *              "end" => string
 *          )
 *          "display" => string      // formatted start + end strings
 *          "calendar" => array(     // hash of calendar-related info
 *              "type" => string     // calendar type (note: may want to make this a constant?)
 *              "id" => string       // id of calendar event
 *              "url" => string      // permalink to calendar event
 *          )
 *      )
 *
 *  One big reason for formatting the results is that Google stores the start/end of events
 *  differently for all-day events (under `date` as opposed to `dateTime`). This also allows
 *  us to format the start/end times into strings on the server end as opposed to needing to
 *  do so using javascript on the client side (which was a nightmare, previously). This helps
 *  in formatting for odd-cases - namely times when the Library's open 24 hours.
 *
 *  NOTE: this expects a single event/day's hash, as opposed to the results from Google. Best
 *  to run this w/in a foreach loop.
 *
 *  ANOTHER NOTE: this is the one function rife with customizations. If you'll need to make changes to
 *  the API backend, it'll very likely be here.
 *
 *  YET ANOTHER NOTE: the $event['calendar'] field is optional and determined by the
 *  `DISPLAY_CALENDAR_META` constant defined in `config.php`
 *
 *  @param  array   Event hash from Google
 *  @return array   Formatted hash
 */

function calendar_scrub(array $item) {
    $cal_type = "google";
    $cal_id = isset($item['id']) ? $item['id'] : "n/a";
    $cal_url = isset($item['htmlLink']) ? $item['htmlLink'] : "n/a";

    $g_start = isset($item['start']['dateTime'])
             ? $item['start']['dateTime']
             : (isset($item['start']['date'])
               ? $item['start']['date'] 
                 // . "T00:00:00"
                 // . date("P", strtotime($item['start']['date']))
               : "00-00-00T00:00:00-00:00");

    $g_end = isset($item['end']['dateTime'])
             ? $item['end']['dateTime']
             : (isset($item['end']['date'])
               ? $item['end']['date'] 
                 // . "T00:00:00"
                 // . date("P", strtotime($item['end']['date']))
               : "00-00-00T00:00:00-00:00");

    /**
     *  is this an all day event? (open + close are dates, not dateTimes)
     */

    $all_day = isset($item['start']['date']) && isset($item['end']['date'])
               ? true : false;

    /**
     *  descriptors
     */

    $title = isset($item['summary']) ? $item['summary'] : "";
    $info = isset($item['description']) ? $item['description'] : "";    
    
    /**
     *  formatted date + times
     *  (all-day events are nulled )
     */

    $day = date(DAY_FORMAT, strtotime($g_start));
    $f_start = !$all_day ? date(HOUR_FORMAT, strtotime($g_start)) : null;
    $f_end = !$all_day ? date(HOUR_FORMAT, strtotime($g_end)) : null;

    if ( !$all_day ) {

        /**
         *  some odds 'n ends for finals week
         */

        // we'll never open at midnight, so we'll treat this as the beginning
        // of the end of 24-hours
        if ( preg_match("/00:00:00/", $g_start)) {
            $display_time = "Close at " . $f_end;
        }

        // 11:59 is a little too specific for our closing hour and will begin
        // our finals week
        elseif ( preg_match("/23:59:00/", $g_end) ) {
            $display_time = $f_start . " - " . "Begin 24 Hours";
        } 

        // normally, we'll display time as "9:00 am - 5:00 pm"
        else {
            $display_time = $f_start . " - " . $f_end;
        }
    } 

    /**
     *  if we're dealing with all-day events, null out the formatted start + end
     *  and use the title (eg. "Closed") for the display time
     */

    else {
        $display_time = $title;
        $f_start = $f_end = null;
    }

    $out = array(
        "title" => $title,
        "info" => $info,
        "day" => $day,
        "all_day" => $all_day,
        "dateTime" => array(
            "start" => $g_start,
            "end" => $g_end,
        ),
        "formatted" => array(
            "start" => $f_start,
            "end" => $f_end
        ),
        "display" => $display_time
    );

    // only display calendar meta info if we want to
    if (defined('DISPLAY_CALENDAR_META') && DISPLAY_CALENDAR_META === true) {
        $out['calendar'] = array(
            "type" => $cal_type,
            "id" => $cal_id,
            "url" => $cal_url
        );
    }

    return $out;
}

/**
 *  Gets the feed from Google of the specified calendar w/in the date range.
 *
 *  @param  string      Google Calendar ID
 *  @param  string      (optional) strtotime-ready string for the start of date range
 *  @param  string      (optional) strtotime-ready string for the end of date range
 *  @return array       Google's results, converted to hash
 *  @throws Exception   thrown if `file_get_contents` of generated url fails
 */

function fetch_feed($cal, $start, $end) {
    $url = build_calendar_url($cal, $start, $end);
    $json = @file_get_contents($url);
    if ( !$json ) { throw new Exception("Unable to obtain feed for `". $cal . "`"); }

    return json_decode($json, true);
}
