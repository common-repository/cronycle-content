<?php

/**
 * All the logging related functionality of the plugin.
 *
 * @link       http://cronycle.com
 * @since      1.1.2
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 */

/**
 * All the logging related functionality of the plugin.
 *
 * Defines the functions for logging the messages and stacktraces
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 * @author     Cronycle
 */
class CronycleContentLogger
{
    private static $log_file_name = 'cronycle_content_debug.log';
    /**
     * Generates a shorten form of debug stack trace.
     *
     * @since   1.1.2
     * @access  private
     * @return  string      Shorten form of stack trace.
     */
    private static function generate_stack_trace()
    {
        $stack_trace = debug_backtrace();
        $stack_trace_str = "";
        for ($i=2; $i < count($stack_trace)-1; $i++) {
            $frame = $stack_trace[$i];
            $stack_trace_str .= "#" . ($i-1) . " " . $frame['file'] . "(" . $frame['line'] . "): ";
            if (isset($frame['class']) && isset($frame['type'])) {
                $stack_trace_str .= $frame['class'] . $frame['type'] . $frame['function'] . "()";
            } else {
                $stack_trace_str .= $frame['function'] . "()";
            }

            if (isset($frame['args']) && !empty($frame['args'])) {
                $stack_trace_str .= "\n[args] => " . CronycleContentUtility::var_dump_str($frame['args'], true);
            }
            if (isset($frame['object']) && !empty($frame['object'])) {
                $stack_trace_str .= "\n[object] => " . CronycleContentUtility::var_dump_str($frame['object'], true);
            }
            $stack_trace_str .= "\n";
        }
        return $stack_trace_str;
    }

    /**
     * Logs the message into log file with stack trace.
     *
     * @since   1.1.2
     * @access  public
     * @var     string      $message     Message to log.
     */
    public static function log($message, $var = null)
    {
        // logs the info only if CRONYCLE_CONTENT_DEBUG global var is enable
        if (CRONYCLE_CONTENT_DEBUG === true) 
        {
            $log_file_path = plugin_dir_path(__FILE__) . "../" . self::$log_file_name;
            $now = date('d-M-Y H:i:s e');

            $msg = "Message: " . $message;
            if ($var != null) {
                $msg .= "\n" . CronycleContentUtility::var_dump_str($var);
            }
            // error_log($msg);
            error_log("[" . $now . "] " . $msg . "\n", 3, $log_file_path);

            // build backtrace
            // error_log("----------------");
            // error_log("\nDebug backtrace: \n" . self::generate_stack_trace());
            // error_log("----------------");
            error_log("[" . $now . "] " . "----------------\n", 3, $log_file_path);
            error_log("[" . $now . "] " . "Debug backtrace: \n" . self::generate_stack_trace(), 3, $log_file_path);
            error_log("[" . $now . "] " . "----------------\n", 3, $log_file_path);
        }
    }

    /**
     * Resets the log file.
     *
     * @since   1.1.2
     * @access  public
     */
    public static function reset()
    {
        // create a file handler by opening the file
        $log_file_handler = @fopen(plugin_dir_path(__FILE__) . "../" . self::$log_file_name, "r+");

        // truncate the file to zero
        // or you could have used the write method and written nothing to it
        @ftruncate($log_file_handler, 0);

        // close file handler
        @fclose($log_file_handler);
    }
}
