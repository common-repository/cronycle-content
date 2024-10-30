<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 * @author     Cronycle
 */
class CronycleContent
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      CronycleContentLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $CronycleContent    The string used to uniquely identify this plugin.
     */
    protected $CronycleContent;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('CRONYCLE_CONTENT_VERSION')) {
            $this->version = CRONYCLE_CONTENT_VERSION;
        } else {
            $this->version = '2.0.0';
        }
        $this->CronycleContent = 'cronycle_content';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - CronycleContentLoader. Orchestrates the hooks of the plugin.
     * - CronycleContenti18n. Defines internationalization functionality.
     * - CronycleContentAdmin. Defines all hooks for the admin area.
     * - CronycleContentPublic. Defines all hooks for the public side of the site.
     * - CronycleContentAPIClient. Defines functions for interacting with the Cronycle API.
     * - CronycleContentLogger. Defines logging related functionalities.
     * - CronycleContentUtility. Defines utility functions of the plugin.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for defining the required logging functions.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cronycle-content-logger.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cronycle-content-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cronycle-content-i18n.php';

        /**
         * The class responsible for defining all the required utility functions.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cronycle-content-utility.php';

        /**
         * The class responsible for defining all actions for interacting with Cronycle APIs.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cronycle-content-api-client.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-cronycle-content-admin.php';

        /**
         * The class responsible for defining all functionalities related to plugin banner functionalities.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-cronycle-content-admin-banner.php';

        /**
         * The class responsible for defining all functionalities related to plugin draft post functionalities.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-cronycle-content-admin-draft-post.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-cronycle-content-public.php';

        $this->loader = new CronycleContentLoader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the CronycleContenti18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new CronycleContenti18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new CronycleContentAdmin($this->get_CronycleContent(), $this->get_version(), $this->loader);

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Setting page things
        $this->loader->add_action('admin_menu', $plugin_admin, 'plugin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'plugin_init');
        $plugin_slug = plugin_basename(plugin_dir_path(dirname(__FILE__)) . 'cronycle-content.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_slug, $plugin_admin, 'add_action_links');

        // AJAX things
        $this->loader->add_action('wp_ajax_unlinkCronycleAccount', $plugin_admin, 'unlink_cronycle_account');
        $this->loader->add_action('wp_ajax_resetLogs', $plugin_admin, 'reset_logs');

        $plugin_admin->plugin_banner->define_admin_hooks();
        $plugin_admin->plugin_draft_post->define_admin_hooks();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        $plugin_public = new CronycleContentPublic($this->get_CronycleContent(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Public content generation things
        $this->loader->add_action('init', $plugin_public, 'plugin_init');

        // AJAX things
        $this->loader->add_action('wp_ajax_getMoreBoardTiles', $plugin_public, 'get_more_board_tiles');
        $this->loader->add_action('wp_ajax_nopriv_getMoreBoardTiles', $plugin_public, 'get_more_board_tiles');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_CronycleContent()
    {
        return $this->CronycleContent;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    CronycleContentLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
