<?php

class drLog {

    // drLog selectively displays messages based on whether they meet the 
    // the specified threshold given to self::set.
    //
    // Output levels are defined as:
    //
    // 0 = debug: detailed messages, should only displayed for debugging
    // 1 = normal: general status messages
    // 2 = critical: critical messages, always displayed
    
    const DEBUG = 0;
    const NORMAL = 1;
    const WARNING = 2;
    const ERROR = 3;

    protected static $output_level = self::NORMAL;
    protected static $output_filename = NULL;


    // -----------
    // outputLevel
    // -----------
    // Set the output level threshold for displaying messages.  Messages sent to log() with
    // level equal to or greater than $ouput_level will be shown.  Specify $output_level 
    // using the DEBUG, NORMAL, WARNING, or ERROR constants.
    public static function outputLevel($output_level) {
        self::$output_level = $output_level;
    }

    // ------------
    // outputToFile
    // ------------
    // Set to $filename to the full path of file to use for logging.  
    // Set to $filename to NULL to log to standard out.
    // Set $write_over to TRUE to erase
    public static function outputFile($filename, $write_over=FALSE) {
        self::$output_filename = $filename;
        if ($write_over==TRUE) {
            unlink($filename);
        }
    }

    // ---
    // log
    // ---
    // Add a message to the log.  The $message will be shown if it has $level equal to or higher
    // than the set outputLevel AND if the $condition is TRUE.   Specify $level using the DEBUG, 
    // NORMAL, WARNING, or ERROR constants.  For WARNING and ERROR messages, the prefixes 
    // "WARNING: " and "ERROR: " are prepended to the message.  A newline character is appended 
    // to the end of all messages.  If an output file has been specified using outputFile(), then 
    // the message will be appended to the output file.  Otherwise, it will be written to standard 
    // out.
    public static function log($message, $level, $condition=TRUE) {

        if (($condition == TRUE) and ($level >= self::$output_level)) {

            if ($level == self::WARNING) {
                $message = "WARNING: " . $message;
            } else if ($level == self::ERROR) {
                $message = "ERROR: " . $message;
            }

            if (!is_null(self::$output_filename)) {
                file_put_contents(self::$output_filename, $message . "\n", FILE_APPEND);
            } else {
                echo($message . "\n");
            }
        }

        return $condition;
    }

    // ----------
    // logAndExit
    // ----------
    // Same as log(), but if $condition is TRUE, will call system exit() after adding log message. 
    // The exit status message is set to $message.
    public static function logAndExit($message, $level, $condition=TRUE) {
        if (self::log($message, $level, $condition)) {
            exit($message);
        }
    }

} // end class

?>


