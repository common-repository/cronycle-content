<?php

/**
 * The plugin bootstrap file
 *
 * Includes all of the dependencies used by the plugin, registers the activation
 * and deactivation functions, and defines a function that starts the plugin.
 *
 * @link              http://cronycle.com
 * @since             1.0.0
 * @package           CronycleContent
 *
 * @wordpress-plugin
 * Plugin Name:       Cronycle Content
 * Plugin URI:        https://www.cronycle.com/wordpress-plugin/
 * Description:       Create news feeds with content you curate in Cronycle.
 * Version:           5.2.2
 * Author:            Cronycle
 * Author URI:        https://cronycle.com
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('CRONYCLE_CONTENT_VERSION', '5.2.2');

/**
 * Plugin's debugging flag.
 */
define('CRONYCLE_CONTENT_DEBUG', false);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cronycle-content-activator.php
 */
function activate_cronycle_content()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-cronycle-content-activator.php';
    CronycleContentActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cronycle-content-deactivator.php
 */
function deactivate_cronycle_content()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-cronycle-content-deactivator.php';
    CronycleContentDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_cronycle_content');
register_deactivation_hook(__FILE__, 'deactivate_cronycle_content');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-cronycle-content.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_cronycle_content()
{
    $plugin = new CronycleContent();
    $plugin->run();
}
run_cronycle_content();
