#!/usr/bin/php
<?php
include dirname(__FILE__) . "/config.php";
include dirname(__FILE__) . "/feed_generator.php";
date_default_timezone_set("America/New_York");

// name of script calling
$name = array_shift($argv);

$command = array_shift($argv);

if ($command == 'update') {
    $feed = array_shift($argv);
    update($feed);
    exit();
} else {
    printOptions();
    exit();
}

function printOptions() {
    echo "usage: hours update [<feed name>]\n";
}

function update($which = "") {
    $feeds = array(
        "today" => OUTPUT_LOCATION . "/today.json",
        "week"  => OUTPUT_LOCATION . "/week.json",
        "year"  => OUTPUT_LOCATION . "/year.json"
    );

    // if $which is empty, update all of the feeds
    
    if (!$which) {
        foreach($feeds as $name => $path) { updateFeed($name, $path); }
    } else {
        if (!array_key_exists($which, $feeds)) {
            echo "I don't know the feed: '" . $which . "'" . PHP_EOL;
            return false;
        } else {
            updateFeed($which, $feeds[$which]);
        }
    }
}

function updateFeed($name, $path) {
    $calendars = unserialize(CALENDARS_SERIALIZED)

    echo   cliColor("Updating", "light blue") 
         . cliColor(" '{$name}' ", "yellow") 
         . cliColor("feed ... ", "light blue");       

    if ($name === "week") {
        $start = date('N') == 1 ? "today" : "last monday";
        $end = date('N', strtotime("today")) == 7 ? "today" : "next monday";
    } elseif ($name === "year") {
        $start = date('N') == 1 ? "today" : "last monday";
        $end = $start . " + 52 weeks";

    } else {
        $start = $end = "today";
    }    

   echo file_put_contents(
          $path, 
          build_feed($calendars, $start, $end)
        )
        ? cliColor("success! ^_^", "light green") . "\n"
        : cliColor("failure! ;_;", "light red") . "\n"
        ;
}

function cliColor($input, $color) {
    $fgcolors = array(
       "dark grey" => "1;30",
       "blue" => "0;34",
       "light blue" => "1;34",
       "green" => "0;32",
       "light green" => "1;32",
       "cyan" => "0;36",
       "light cyan" => "1;36",
       "red" => "0;31",
       "light red" => "1;31",
       "purple" => "0;35",
       "light purple" => "1;35",
       "brown" => "0;33",
       "yellow" => "1;33",
       "light gray" => "0;37",
       "white" => "1;37" 
    );

    $escape = "\033[0m";

    return "\033[" . $fgcolors[$color] . "m" . $input . $escape;
}

?>
