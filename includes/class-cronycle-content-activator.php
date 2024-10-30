<?php

/**
 * Fired during plugin activation
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 * @author     Cronycle
 */
class CronycleContentActivator
{

    /**
     * Script to run at activation time
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        // deletes the cronycle_content_options from db
        // at activation if don't have valid value
        $options = get_option('cronycle_content_options');
        if ($options !== false) {
            CronycleContentLogger::log("Cronycle options already exists with value ", $options);

            if (!isset($options['auth_token'])) {
                if (delete_option('cronycle_content_options')) {
                    CronycleContentLogger::log("Deleted existing Cronycle options.");
                } else {
                    CronycleContentLogger::log("Failure in deleting existing Cronycle options.");
                }
            }
        }
    }
}
