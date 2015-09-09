<?php
/**
 *  Since we can't store arrays as constants until PHP 5.6, we'll use this `$calendars` hash
 *  to store the calendar name and ID and serialize it into a constant. When we need it, we 
 *  can just unserialize it. 
 * 
 *  Big-ups to http://stackoverflow.com/a/1290388
 *
 *  ex.
 *      $calendars = array(
 *        'Library Hours' => 'hours_email_address@gmail.com',
 *        'Another Room' => 'asbcaosd2q90fu9awojeq2@groups.google.com'
 *      );
 */

$calendars = array();

/**
 *  Google apps API key (I'm not 100% sure if you have to specifically register for
 *  Google Calendars or if it's a one-stop-shop kind of thing.)
 */

define("GOOGLE_API_KEY", "");

/**
 *  Do we want to display meta info about the event? If true, the feed will include 
 *  a hash with the following keys:
 *    'type' => // type of calendar, as of now will always be 'google'
 *    'id' => // id of the event
 *    'url' => // url to event
 */

define("DISPLAY_CALENDAR_META", false);

/**
 *  formatting related constants to be used w/in PHP's `date()` function
 *
 *      "l, F jS" => "Wednesday, January 7th"
 *      "h:i a"   => 09:00 am
 *
 *  note: using "D." will abbreviate the day (eg. "Wed.")
 *  note: using "M." will abbreviate the month (eg. "Jan.")
 *  see: http://php.net/manual/en/function.date.php
 */

define("DAY_FORMAT", "l, F jS");
define("HOUR_FORMAT", "g:i a");

/**
 *  Where to store the static JSON files. Defaults to the current directory in a folder named `json`.
 *
 *  NOTE: if using this from within cron, you'll want to make sure that the path is absolute,
 *  as cron executes from the root folder.
*/

define('OUTPUT_LOCATION', dirname(__FILE__) . "/json")

/**
 *  And yeah, leave this one be
 */

define("CALENDARS_SERIALIZED", serialize($calendars));
