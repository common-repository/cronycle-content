<?php

/**
 * The admin-specific functionality of the plugin related to banner content.
 *
 * @link       http://cronycle.com
 * @since      2.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 */

/**
 * The admin-specific functionality of the plugin related to banner content.
 *
 * Defines the admin-specific functionalities of the plugin related to
 * banner content on post editor pages.
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 * @author     Cronycle
 */
class CronycleContentAdminBanner
{

    /**
     * The ID of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $CronycleContent    The ID of this plugin.
     */
    private $CronycleContent;

    /**
     * The version of this plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since   2.0.0
     * @param   string                  $CronycleContent    The name of this plugin.
     * @param   string                  $version    	    The version of this plugin.
     * @param   CronycleContentLoader   $loader    		    The hook loader of this plugin.
     */
    public function __construct($CronycleContent, $version, $loader)
    {
        $this->CronycleContent = $CronycleContent;
        $this->version = $version;
        $this->loader = $loader;
    }

    /**
     * Register all of the hooks related to the banner content functionality
     * in admin area of the plugin.
     *
     * @since    2.0.0
     * @access   public
     */
    public function define_admin_hooks()
    {
        // TinyMCE editor things
        $this->loader->add_filter('mce_buttons', $this, 'register_tinymce_buttons');
        $this->loader->add_filter('mce_external_plugins', $this, 'register_tinymce_javascript');

        // AJAX things
        $this->loader->add_action('wp_ajax_getBoardsList', $this, 'get_boards_list');
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since   2.0.0
     * @access  public
     * @param   string      $hook    Current hook of the page.
     */
    public function enqueue_styles($hook)
    {
        if ($hook != 'post.php' && $hook != 'post-new.php') {
            return;
        }
        wp_enqueue_style(
            $this->CronycleContent . '-banner',
            plugin_dir_url(__FILE__) . 'css/cronycle-content-banner.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            $this->CronycleContent . '-tinymce',
            plugin_dir_url(__FILE__) . 'css/tinymce-plugin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since   2.0.0
     * @access  public
     * @param   string      $hook    Current hook of the page.
     */
    public function enqueue_scripts($hook)
    {
        if ($hook != 'post.php' && $hook != 'post-new.php') {
            return;
        }
        wp_enqueue_script(
            $this->CronycleContent . '-banner',
            plugin_dir_url(__FILE__) . 'js/cronycle-content-banner.js',
            array( 'jquery' ),
            $this->version,
            false
        );
    }

    /**
     * Registers button in TinyMCE editor.
     *
     * @since    1.0.0
     * @access   public
     * @var      array      $buttons    Array of buttons to which new button is to appended.
     * @return   array      Array of buttons with new button data.
     */
    public function register_tinymce_buttons($buttons)
    {
        array_push($buttons, 'separator', 'cronycle_content_button');
        return $buttons;
    }

    /**
     * Registers JS for TinyMCE editor plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      array      $plugin_array   Array of plugins to which new plugin to be added.
     * @return   array      Array of plugins with new plugin url inserted.
     */
    public function register_tinymce_javascript($plugin_array)
    {
        $plugin_array['cronycle_content_button'] = plugins_url('/js/tinymce-plugin.js', __FILE__);
        return $plugin_array;
    }

    /**
     * Get the list of boards.
     *
     * @since   1.1.0
     * @access  public
     * @param   bool    $return    Whether to return list or print.
     */
    public function get_boards_list($return = false)
    {
        $options = get_option('cronycle_content_options');
        if (isset($options['auth_token']) && !empty($options['auth_token'])) {
            $api_client = new CronycleContentAPIClient($options['auth_token']);
            $board_list = $api_client->get_boards_list();

            if ($return === true) {
                return $board_list;
            }

            if (!empty($board_list)) {
                _e(json_encode($board_list));
            } else {
                _e(json_encode(array('error'=>array('message'=>"NO_BOARD_EXIST"))));
            }
        } else {
            if ($return === true) {
                return array();
            }
            _e(json_encode(array('error'=>array('message'=>"NO_TOKEN_EXIST"))));
        }

        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
