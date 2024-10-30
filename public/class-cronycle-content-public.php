<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/public
 * @author     Cronycle
 */
class CronycleContentPublic
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $CronycleContent    The ID of this plugin.
     */
    private $CronycleContent;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $CronycleContent   The name of the plugin.
     * @param      string    $version    		The version of this plugin.
     */
    public function __construct($CronycleContent, $version)
    {
        $this->CronycleContent = $CronycleContent;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->CronycleContent, plugin_dir_url(__FILE__) . 'css/cronycle-content-public.css', array(), $this->version, 'all');
        wp_enqueue_style($this->CronycleContent . '-slick', plugin_dir_url(__FILE__) . 'slick/slick.css', array(), $this->version, 'all');
        wp_enqueue_style($this->CronycleContent . '-slick-theme', plugin_dir_url(__FILE__) . 'slick/slick-theme.css', array(), $this->version, 'all');
        wp_enqueue_style(
            $this->CronycleContent . '-slick-custom',
            plugin_dir_url(__FILE__) . 'css/slick-carousel.css',
            array($this->CronycleContent . '-slick', $this->CronycleContent . '-slick-theme'),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->CronycleContent . 'moment', plugin_dir_url(__FILE__) . 'js/moment.min.js', array(), $this->version, false);
        wp_enqueue_script($this->CronycleContent, plugin_dir_url(__FILE__) . 'js/cronycle-content-public.js', array( 'jquery' ), $this->version, false);
        wp_enqueue_script($this->CronycleContent . '-slick', plugin_dir_url(__FILE__) . 'slick/slick.min.js', array( 'jquery', 'jquery-migrate' ), $this->version, false);
        wp_enqueue_script(
            $this->CronycleContent . '-slick-init',
            plugin_dir_url(__FILE__) . 'js/slick-carousel.js',
            array( 'jquery',  $this->CronycleContent . '-slick'),
            $this->version,
            false
        );
        wp_localize_script($this->CronycleContent, 'WPURLS', array( 'ajax_url' => admin_url('admin-ajax.php') ));
    }

    /**
     * Add actions to perform when plugin instantiate.
     *
     * @since    1.0.0
     * @access   public
     */
    public function plugin_init()
    {
        add_shortcode('cronycle-content', array( $this, 'parse_shortcode'));
    }

    /**
     * Split up the story arc articles into array with max size 2 and adds a group id
     * to each parsed story arc item for identification of story arc tile at frontend side.
     *
     * @since    1.1.0
     * @access   private
     * @param    array      $body            Body of response received from API.
     * @param    int        $wp_group_id        Start of group id to assign to parsed item group.
     * @return   array      Associative array with splited items.
     */
    private function split_note_board_tiles($board_tiles, $wp_group_id = 0)
    {
        foreach ($board_tiles as $board_tile) {
            // for simple articles (tile_type == 'link')
            if ($board_tile['tile_type'] == "link") {
                $splited_board_tiles[] = $board_tile;
            }
            // for story arcs (tile_type == 'note')
            elseif ($board_tile['tile_type'] == "note") {
                for ($i = 0; $i < count($board_tile['articles']); $i += 2) {
                    $articles = array();

                    $articles[] = $board_tile['articles'][$i];

                    if ($i + 1 < count($board_tile['articles'])) {
                        $articles[] = $board_tile['articles'][$i + 1];
                    }

                    $splited_board_tiles[] = array(
                        'wp_tile_id'  => $board_tile['wp_tile_id'],
                        'wp_group_id' => $wp_group_id,
                        'tile_type'   => $board_tile['tile_type'],
                        'summary'     => $board_tile['summary'],
                        'articles'    => $articles );
                }
                $wp_group_id++;
            }
            // for conversations (tile_type == 'conversation')
            elseif ($board_tile['tile_type'] == "conversation") {
                $splited_board_tiles[] = $board_tile;
            }
        }
        return $splited_board_tiles;
    }

    /**
     * Parses the shortcode added for Cronycle content tag in editor.
     *
     * @since    1.0.0
     * @access   public
     */
    public function parse_shortcode($attrs = [], $content = null)
    {
        $options = get_option('cronycle_content_options');

        // return if no auth token or no board id and name or instance exists
        if (!isset($options['auth_token']) || empty($options['auth_token']) ||
            !isset($attrs['id']) || empty($attrs['id']) ||
            !isset($attrs['name']) || empty($attrs['name']) ||
            !isset($attrs['instance']) || empty($attrs['instance'])) {
            return $content;
        }

        $board_id = sanitize_text_field($attrs['id']);
        $board_name = sanitize_text_field($attrs['name']);
        $instance = sanitize_text_field($attrs['instance']);

        // validating include image attribute
        if (isset($attrs['include_image']) && !empty($attrs['include_image'])) {
            $include_image = json_decode(strtolower(sanitize_text_field($attrs['include_image'])));
        } else {
            $include_image = true;
        }

        // validating width attribute
        if (isset($attrs['width']) && !empty($attrs['width'])) {
            $width = intval(sanitize_text_field($attrs['width'])) . "%";
        } else {
            $width = "100%";
        }

        // validating position attribute
        if (isset($attrs['position']) && !empty($attrs['position'])) {
            $position = strtolower(sanitize_text_field($attrs['position']));
            if ($position != "left" && $position != "center" && $position != "right") {
                $position = "left";
            }
        } else {
            $position = "left";
        }

        $api_client = new CronycleContentAPIClient($options['auth_token']);
        $board_tiles = $api_client->get_board_tiles($board_id);

        if (empty($board_tiles)) {
            return $content;
        }

        $board_tiles = $this->split_note_board_tiles($board_tiles);
        $is_pro_user = $api_client->is_pro_user();

        // debugging statements
        // _e("<script> console.log( 'board details = ', " . json_encode( $board_tiles ) . " ); </script>");

        ob_start();
        include("partials/template-content-banner.php");
        $content .= ob_get_clean();

        // always return
        return $content;
    }
    
    /**
     * Fetch more board tiles for displaying content in the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function get_more_board_tiles()
    {
        $cron_content_settings = json_decode(stripslashes($_GET['cronContentSettings']), true);
        $last_tile_id = $_GET['lastTileId'];
        $last_group_id = $_GET['lastGroupId'];

        $options = get_option('cronycle_content_options');
        // return if no auth token or no content settings or no tile info exists
        if (!isset($options['auth_token']) || empty($options['auth_token']) ||
            $cron_content_settings == null || $last_tile_id == null || $last_group_id == null) {
            _e("");
            return;
        }

        $board_id = $cron_content_settings['boardId'];
        $include_image = $cron_content_settings['includeImage'];

        $api_client = new CronycleContentAPIClient($options['auth_token']);
        $board_tiles = $api_client->get_board_tiles($board_id, $last_tile_id + 1);
        $board_tiles = $this->split_note_board_tiles($board_tiles, $last_group_id + 1);

        foreach ($board_tiles as $board_tile) {
            $articles = $board_tile['articles'];
            // for simple articles
            if ($board_tile['tile_type'] == "link") {
                $article = $articles[0];
                include("partials/template-content-banner-item.php");
            }
            // for story arc articles
            elseif ($board_tile['tile_type'] == "note") {
                include("partials/template-content-banner-item-group.php");
            }
            // for conversation articles
            elseif ($board_tile['tile_type'] == "conversation") {
                $article = $articles[0];
                include("partials/template-content-banner-item-convo.php");
            }
        }
    
        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
