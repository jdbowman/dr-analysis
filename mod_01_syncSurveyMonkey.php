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
drLog::outputLevel($config["log_syncsm_level"]);
drLog::outputFile($config["log_syncsm_file"], TRUE);
drLog::log("Starting syncSurveyMonkey...", drLog::NORMAL);

// ----
// Main 
// ----

$sm = new drSurveyMonkey($config);
$survey_id = $config["sm_survey_id"];

$db = new drDatabase($config);
$elements_table = $config["db_elements_table"];
$responses_table = $config["db_responses_table"];

// Fetch the survey details from SurveyMonkey
drLog::log("Fetching survey details for survey " . $survey_id . "...", drLog::NORMAL);
$data = $sm->fetchSurveyDetail($survey_id);
drLog::logAndExit("Failed to fetch survey details.  Aborting.", drLog::ERROR, is_null($data));

// Parse the survey details
$survey = $sm->parseSurveyDetail($data);
$elements = $survey["elements"];
$columns = $survey["columns"];
$column_names = array_column($columns, "name");
$column_datatypes = array_column($columns, "datatype");
drLog::log("Parsed survey details.  Found " . count($elements) . " elements and " . count($columns) ." columns.", drLog::NORMAL);

// Drop the existing survey tables in database
drLog::log("Dropping existing survey tables...", drLog::NORMAL);
drLog::log("Failed to drop elements table (normal if not already exists).", drLog::WARNING, !$db->dropTable($elements_table));
drLog::log("Failed to drop responses table (normal if not already exists).", drLog::WARNING, !$db->dropTable($responses_table));

// Create new survey tables in the database that match the elements in SurveyMonkey
drLog::log("Creating fresh survey tables...", drLog::NORMAL);
drLog::logAndExit("Failed to create elements table.  Aborting.", drLog::ERROR, !$db->createTable($elements_table, array_keys($elements[0])));
drLog::logAndExit("Failed to create responses table.  Aborting.", drLog::ERROR, !$db->createTable($responses_table, $columns));

// Insert into elements tables
drLog::log("Inserting into elements table...", drLog::NORMAL);
drLog::logAndExit("Failed to insert into elements table.  Aborting.", drLog::ERROR, !$db->insertIntoValues($elements_table, $elements));

// Fetch responses and insert into table
drLog::log("Fetching and inserting responses...", drLog::NORMAL);
$url = $sm->getResponsesUrl($survey_id);
while (!is_null($url)) {
    
    // Fetching page of responses
    drLog::log("Fetching responses from: " . $url, drLog::NORMAL);
    $responses = [];
    $data = $sm->fetchSurveyResponses($survey_id, $url);
    drLog::logAndExit("Failed to create elements table.  Aborting.", drLog::ERROR, is_null($data));

    // Parsing the page
    $url = $sm->parseSurveyResponses($data, $column_names, $responses);
    drLog::log("Parsed page of responses.  Found " . count($responses) . " responses.", drLog::NORMAL);

    // Insert into reponses table
    drLog::log("Inserting into responses table...", drLog::NORMAL);
    drLog::logAndExit("Failed to insert into responses table.  Aborting.", drLog::ERROR, !$db->insertIntoValues($responses_table, $responses, $column_datatypes));
}

drLog::log("Done executing syncSurveyMonkey.", drLog::NORMAL);

?>
