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

// Set the timezone
date_default_timezone_set($config["timezone"]);

// Setup logging
drLog::outputLevel($config["log_execmod_level"]);
drLog::outputFile($config["log_execmod_file"], TRUE);
drLog::log("Executing modules...", drLog::NORMAL);

// ----
// Main
// ----

// Get the directory of the current file
$cwd = dirname(__FILE__);
$modsearch = $cwd . "/mod_*.php";

// Find modules in the same directory
foreach (glob($modsearch) as $filename) {

    // Execute the modules in alphanumeric (by name) order
    drLog::log(date("Y-m-d H:i:s") . " - Starting: " . $filename . "...", drLog::NORMAL);
    exec($filename);
    drLog::log(date("Y-m-d H:i:s") . " - Done.", drLog::NORMAL);
}

?>
