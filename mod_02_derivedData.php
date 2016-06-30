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

// Setup logging
drLog::outputLevel($config["log_derived_level"]);
//drLog::outputFile($config["log_derived_file"], TRUE);
drLog::log("Starting derivedData...", drLog::NORMAL);

// ----
// Main 
// ----

$db = new drDatabase($config);
$elements_table_name = $config["db_elements_table"];
$responses_table_name = $config["db_responses_table"];
$derived_table_name = $config["db_derived_table"];

// Drop the existing derived table in database
drLog::log("Dropping existing derived data table...", drLog::NORMAL);
drLog::log("Failed to drop derived data table (normal if not already exists).", drLog::WARNING, !$db->dropTable($derived_table_name));

// Specify the columns for the table
// "name", "datatype", "datalength", "attributes"
$derived_table = new drTable();
$derived_table->setName($derived_table_name);
$derived_table->addColumn(new drTableColumn("response_id", NULL, "TEXT", NULL, NULL, TRUE));
$derived_table->addColumn(new drTableColumn("duration", NULL, "INT"));
$derived_table->addColumn(new drTableColumn("num_questions", NULL, "INT"));

$columns = [    ["name"=>"response_id", "datatype"=>"TEXT", "attributes"=>"NOT NULL"],
                ["name"=>"duration", "datatype"=>"INT"],
                ["name"=>"num_questions", "datatype"=>"INT"] ];

// Create new derived data table in the database
drLog::log("Creating fresh derived data table...", drLog::NORMAL);
drLog::logAndExit("Failed to create derived data table.  Aborting.", drLog::ERROR, !$db->query($db->getCreateTableSQL($derived_table)));

// Insert response_id and duration data
drLog::log("Inserting response_id and duration data...", drLog::NORMAL);
$sql = "INSERT INTO " . $derived_table_name . " (response_id, duration) SELECT response_id, TIME_TO_SEC(TIMEDIFF(end_time, start_time))/60 AS temp_duration FROM " . $responses_table_name . " rt WHERE rt.decline = 'NO';";
drLog::logAndExit("Failed to insert response ids and durations into derived data table.  Aborting.", drLog::ERROR, !$db->query($sql));


drLog::log("Done executing derivedData.", drLog::NORMAL);

?>
