#!/usr/bin/php
<?php

// Autoload classes
spl_autoload_register(function ($class_name) {
    require_once $class_name . '.class.php';
});


// ----
// Init
// ----

// Import the configuration
$config = include("config.php");

// Setup logging
drLog::outputLevel(drLog::NORMAL);
drLog::log("Listing surveys...", drLog::NORMAL);

// ----
// Main 
// ----

$sm = new drSurveyMonkey($config);

// Fetch the survey details from SurveyMonkey
drLog::log("Fetching list of surveys...", drLog::NORMAL);
$data = $sm->fetchSurveys();
drLog::logAndExit("Failed to fetch survey list.  Aborting.", drLog::ERROR, is_null($data));

print_r($data);

drLog::log("Done listing surveys.", drLog::NORMAL);

?>
