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
$config = include("config.inc.php");

// Import the SurveyMonkey translation dictionary (to human readable names)
$dictionary = include("dictionary-implementation.inc.php");

// Setup logging
drLog::outputLevel($config["log_syncsm_level"]);
drLog::outputFile($config["log_syncsm_file"], TRUE);
drLog::log("Starting syncSurveyMonkey...", drLog::NORMAL);

// ----
// Main 
// ----

$sm = new drSurveyMonkey($config);
$sm->setTranslateDictionary($dictionary);
$survey_id = $config["sm_survey_id"];

$db = new drDatabase($config);
$elements_table_name = $config["db_elements_table"];
$responses_table_name = $config["db_responses_table"];

// Fetch the survey details from SurveyMonkey
drLog::log("Fetching survey details for survey " . $survey_id . "...", drLog::NORMAL);
$data = $sm->fetchSurveyDetail($survey_id);
drLog::logAndExit("Failed to fetch survey details.  Aborting.", drLog::ERROR, is_null($data));

// Parse the survey details
$survey = $sm->parseSurveyDetail($data);
$elements_table = $survey["elements_table"];
$elements_table->setName($elements_table_name);
$responses_table = $survey["responses_table"];
$responses_table->setName($responses_table_name);

drLog::log($responses_table->display(), drLog::NORMAL);

drLog::log("Parsed survey details - added " . count($elements_table->getColumns()) . " elements columns.", drLog::NORMAL);
drLog::log("Parsed survey details - added " . count($responses_table->getColumns()) ." response columns.", drLog::NORMAL);

// Drop the existing survey tables in database
drLog::log("Dropping existing survey tables...", drLog::NORMAL);
drLog::log("Failed to drop elements table (normal if not already exists).", drLog::WARNING, !$db->dropTable($elements_table_name));
drLog::log("Failed to drop responses table (normal if not already exists).", drLog::WARNING, !$db->dropTable($responses_table_name));

// Create new survey tables in the database that match the elements in SurveyMonkey
drLog::log("Creating fresh survey tables...", drLog::NORMAL);
drLog::logAndExit("Failed to create elements table.  Aborting.", drLog::ERROR, !$db->query($db->getCreateTableSQL($elements_table)));
drLog::logAndExit("Failed to create responses table.  Aborting.", drLog::ERROR, !$db->query($db->getCreateTableSQL($responses_table)));

// Insert into elements tables
drLog::log("Inserting into elements table...", drLog::NORMAL);
drLog::logAndExit("Failed to insert into elements table.  Aborting.", drLog::ERROR, !$db->query($db->getInsertIntoValuesSQL($elements_table)));

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
    $url = $sm->parseSurveyResponses($data, $responses);
    $responses_table->setRows($responses);
    drLog::log("Parsed page of responses.  Found " . count($responses) . " responses.", drLog::NORMAL);

    // Insert into reponses table
    drLog::log("Inserting into responses table...", drLog::NORMAL);
    drLog::logAndExit("Failed to insert into responses table.  Aborting.", drLog::ERROR, !$db->query($db->getInsertIntoValuesSQL($responses_table)));
}

drLog::log("Done executing syncSurveyMonkey.", drLog::NORMAL);

?>
