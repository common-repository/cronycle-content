<?php

/**
 * The admin-specific functionality of the plugin related to draft post content.
 *
 * @link       http://cronycle.com
 * @since      2.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 */

/**
 * The admin-specific functionality of the plugin related to draft post content.
 *
 * Defines the admin-specific functionalities of the plugin related to
 * draft post content on custom post list view pages.
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 * @author     Cronycle
 */
class CronycleContentAdminDraftPost
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
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      CronycleContentLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    private $loader;

    /**
     * Time interval (in seconds) after which Cronycle content post data in database will be considered outdated.
     *
     * @since    2.0.0
     * @access   private
     * @var      int    CRON_CONTENT_OUTDATE_INTERVAL    Cronycle content outdate time interval.
     */
    const CRON_CONTENT_OUTDATE_INTERVAL = 30 * 24 * 60 * 60;

    /**
     * Whether to load media asynchronously in WordPress media library or not.
     *
     * @since    5.0.0
     * @access   private
     * @var      bool   CRON_CONTENT_ASYNC_MEDIA_LOAD    Cronycle content async media load flag.
     */
    const CRON_CONTENT_ASYNC_MEDIA_LOAD = true;

    /**
     * Limit (in seconds) to timeout media load script.
     * Defaults to ini_get('max_execution_time') which is 30 secs.
     *
     * @since    5.0.0
     * @access   private
     * @var      int    CRON_CONTENT_MEDIA_LOAD_TIMEOUT    Cronycle content featured image load timeout.
     */
    const CRON_CONTENT_MEDIA_LOAD_TIMEOUT = 30;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     * @param    string                 $CronycleContent    The name of this plugin.
     * @param    string                 $version    	    The version of this plugin.
     * @param    CronycleContentLoader  $loader    		    The hook loader of this plugin.
     */
    public function __construct($CronycleContent, $version, $loader)
    {
        $this->CronycleContent = $CronycleContent;
        $this->version = $version;
        $this->loader = $loader;
    }

    /**
     * Register all of the hooks related to the draft post content functionality
     * in admin area of the plugin.
     *
     * @since    2.0.0
     * @access   public
     */
    public function define_admin_hooks()
    {
        $this->loader->add_action('init', $this, 'register_cronycle_content_post_type');
        $this->loader->add_action('init', $this, 'register_cronycle_content_post_status');
        $this->loader->add_action('load-edit.php', $this, 'load_content');
        $this->loader->add_action('load-post.php', $this, 'open_wp_post_editor');

        $this->loader->add_action('admin_menu', $this, 'modify_admin_menu');
        $this->loader->add_action('all_admin_notices', $this, 'add_help_text');

        $this->loader->add_action('publish_cronycle_content', $this, 'cronycle_content_post_published_notification', 10, 2);
        $this->loader->add_action('publish_post', $this, 'wp_post_published_notification', 10, 2);
        $this->loader->add_filter('post_updated_messages', $this, 'cronycle_content_post_updated_messages');

        $this->loader->add_filter('manage_cronycle_content_posts_columns', $this, 'cronycle_content_post_columns', 20);
        $this->loader->add_action('manage_cronycle_content_posts_custom_column', $this, 'cronycle_content_post_column_value', 10, 2);
        $this->loader->add_filter('manage_edit-cronycle_content_sortable_columns', $this, 'cronycle_content_post_sortable_columns');
        $this->loader->add_action('pre_get_posts', $this, 'cronycle_content_post_orderby');
        $this->loader->add_action('restrict_manage_posts', $this, 'cronycle_content_post_filters');
        $this->loader->add_filter('parse_query', $this, 'cronycle_content_post_filterby');
        $this->loader->add_filter('post_row_actions', $this, 'cronycle_content_post_row_actions', 10, 2);
        $this->loader->add_action('manage_posts_extra_tablenav', $this, 'cronycle_content_post_tablenav_actions');
        $this->loader->add_action('admin_footer-edit.php', $this, 'hide_status_into_bulk_inline_edit');

        $this->loader->add_action('admin_bar_menu', $this, 'edit_admin_bar_menu', 81);
        $this->loader->add_filter('display_post_states', $this, 'hide_post_state', 10, 2);
        $this->loader->add_filter('views_edit-cronycle_content', $this, 'hide_quick_links', 20);
        $this->loader->add_filter('get_edit_post_link', $this, 'remove_post_title_hyperlink', 10, 3);

        if (self::CRON_CONTENT_ASYNC_MEDIA_LOAD) {
            // event to trigger when new cronycle content added
            $this->loader->add_action('cronycle_content_new_tile_added', $this, 'add_lead_image_as_featured_image', 10, 3);
        }
    }
    
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.0.0
     * @access   public
     * @param    string     $hook    Current hook of the page.
     */
    public function enqueue_styles($hook)
    {
        global $typenow;
        if ($hook != 'edit.php' || $this->CronycleContent != $typenow) {
            return;
        }
        wp_enqueue_style(
            $this->CronycleContent . '-draft-post',
            plugin_dir_url(__FILE__) . 'css/cronycle-content-draft-post.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.0.0
     * @access   public
     * @param    string     $hook    Current hook of the page.
     */
    public function enqueue_scripts($hook)
    {
        global $typenow;
        if ($hook != 'edit.php' || $this->CronycleContent != $typenow) {
            return;
        }
        wp_enqueue_script(
            $this->CronycleContent . '-draft-post',
            plugin_dir_url(__FILE__) . 'js/cronycle-content-draft-post.js',
            array( 'jquery' ),
            $this->version,
            false
        );
    }

    /**
     * Register the custom post for the Cronycle board content.
     *
     * @since    2.0.0
     * @access   public
     */
    public function register_cronycle_content_post_type()
    {
        // WARNING: this will break down the preview in draft posts
        // return if not on admin page
        // if (!is_admin()) {
        //     return;
        // }
        // abort if no auth token exists
        $options = get_option('cronycle_content_options');
        if (!isset($options['auth_token']) || empty($options['auth_token'])) {
            return;
            // WARNING: this will increase user api calls
            // } elseif (!CronycleContentAPIClient::verify_token($options['auth_token'])) {
            //     // also delete the post content if token is not more valid
            //     self::delete_content($this->CronycleContent);
            //     return;
        }

        $labels = array(
                'name'                  => __('Cronycle Content', $this->CronycleContent),
                'singular_name'         => __('Cronycle Content', $this->CronycleContent),
                'add_new'               => __('Add New', $this->CronycleContent),
                'all_items'             => __('Cronycle Content', $this->CronycleContent),
                'add_new_item'          => __('Add New Cronycle Content', $this->CronycleContent),
                'edit_item'             => __('Edit Cronycle Content', $this->CronycleContent),
                'new_item'              => __('New Cronycle Content', $this->CronycleContent),
                'view_item'             => __('View Cronycle Content', $this->CronycleContent),
                'search_items'          => __('Search Cronycle Content', $this->CronycleContent),
                'not_found'             => __('No Cronycle Content Found', $this->CronycleContent),
                'not_found_in_trash'    => __('No Cronycle Content Found In Trash', $this->CronycleContent),
                'menu_name'             => __('Cronycle Content', $this->CronycleContent)
            );
    
        register_post_type(
            $this->CronycleContent,
            array(
                'exclude_from_search'   => true,
                'publicly_querable'     => false,
                'show_in_nav_menus'     => false,
                'public'                => true,
                'show_ui'               => true,
                'query_var'             => $this->CronycleContent,
                'show_in_menu'          => true,
                'show_in_admin_bar'     => false,
                'rewrite'               => false,
                'capabilities'          => array(
                    'create_posts' => false,
                ),
                'map_meta_cap'          => true,
                'supports'              => array('title'),
                'taxonomies'            => array('post_tag', 'category'),
                'labels'                => $labels,
                'menu_icon'             => 'dashicons-cronycle-content',
            )
        );
    }

    /**
     * Register the custom post statues for the Cronycle board content.
     *
     * @since    4.0.0
     * @access   public
     */
    public function register_cronycle_content_post_status()
    {
        register_post_status('added_to_drafts', array(
            'label'                     => __('Added to Drafts', $this->CronycleContent),
            'post_type'                 => array( $this->CronycleContent ),
            'public'                    => false,
            'protected'                 => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Added to Drafts <span class="count">(%s)</span>',
                'Added to Drafts <span class="count">(%s)</span>'
            ),
        ));
    }
    
    
    /**
     * Generate the post content for custom post from the Cronycle board tile content.
     *
     * @since    2.0.0
     * @access   private
     * @param    array      $board_tile    Board tile details.
     * @return   string     Custom post content
     */
    private function get_custom_post_content($board_tile)
    {
        $post_content = '';
        switch ($board_tile['tile_type']) {
            case 'link': {
                $article = $board_tile['articles'][0];
                if (!empty($board_tile['summary'])) {
                    $post_content .=  $board_tile['summary'] . '<br>';
                }
                if (isset($article['image_url'])) {
                    $post_content .= '<img class="alignleft" src="' . $article['image_url'] . '" />';
                }
                $post_content .= '<blockquote>' . $article['description'] . '</blockquote><br>';
                $post_content .= 'Read more from the source: <em><a href="' . $article['url'] . '" target="_blank">' .
                    $article['title'] . '</a></em>';
                break;
            }
            case 'conversation': {
                $article = $board_tile['articles'][0];
                $epoch = $article['tweet_time'];
                $dt = new DateTime("@$epoch");
                $post_content .= '<blockquote>' . $article['tweet_text'] . '</blockquote><br>';
                $post_content .= 'Tweeted by: <em><a href="' . $article['tweeter_url'] . '" target="_blank">@' .
                    $article['tweeter_name'] . '</a> on <a href="' . $article['tweet_url'] . '" target="_blank">' .
                    $dt->format('l, F d, Y') . '</a></em>';
                break;
            }
            case 'note': {
                $post_content .= $board_tile['summary'] . '<br>';
                foreach ($board_tile['articles'] as $article) {
                    if ('link' == $article['article_type']) {
                        $post_content .= '<h1>' . $article['title'] . '</h1>';
                        if (isset($article['image_url'])) {
                            $post_content .= '<img class="alignleft" src="' . $article['image_url'] . '" />';
                        }
                        $post_content .= '<blockquote>' . $article['description'] . '</blockquote><br>';
                        $post_content .= 'Read more from the source: <em><a href="' . $article['url'] . '" target="_blank">' .
                            $article['title'] . '</a></em><br>';
                    } elseif ('conversation' == $article['article_type']) {
                        $post_content .= '><h1>Conversation started by ' . $article['tweeter_name'] . '</h1>';
                        $epoch = $article['tweet_time'];
                        $dt = new DateTime("@$epoch");
                        $post_content .= '<blockquote>' . $article['tweet_text'] . '</blockquote><br>';
                        $post_content .= 'Tweeted by: <em><a href="' . $article['tweeter_url'] . '" target="_blank">@' .
                            $article['tweeter_name'] . '</a> on <a href="' . $article['tweet_url'] . '" target="_blank">' .
                            $dt->format('l, F d, Y') . '</em>';
                    }
                    $post_content .= '<br>';
                }
                break;
            }
        }
        return $post_content;
    }

    /**
     * Generate the post content for custom post from the Cronycle board tile content
     * according to Gutenberg editor.
     *
     * @since    3.1.0
     * @access   private
     * @param    array      $board_tile    Board tile details.
     * @return   string     Custom post content with Gutenberg comment tags
     */
    private function get_custom_post_content_for_gutenberg($board_tile)
    {
        $post_content = '';
        switch ($board_tile['tile_type']) {
            case 'link': {
                $article = $board_tile['articles'][0];
                if (!empty($board_tile['summary'])) {
                    $post_content .=  '<!-- wp:paragraph --><p>' . $board_tile['summary'] . '</p><!-- /wp:paragraph -->';
                }

                // if (isset($article['image_url'])) {
                //     $post_content .= '<!-- wp:image --><figure class="wp-block-image"><img src="' . $article['image_url'] .
                //     '" alt="' . $article['title'] . '" /></figure><!-- /wp:image -->';
                // }

                $source_name = '';
                if (isset($article['publisher_name']) && !empty($article['publisher_name'])) {
                    $source_name = ' ' . $article['publisher_name'];
                }

                $author_html = '';
                if (isset($article['primary_author']) && !empty($article['primary_author'])) {
                    $author_html = ', Published by: ';
                    if (isset($article['author_url']) && !empty($article['author_url'])) {
                        $author_html .= '<em><a href="' . $article['author_url'] . '" target="_blank">' .
                            $article['primary_author'] . '</a></em>';
                    } else {
                        $author_html .= '<em>' . $article['primary_author'] . '</em>';
                    }
                }

                $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['description'] .
                    '</p></blockquote><!-- /wp:quote -->';
                $post_content .= '<!-- wp:paragraph --><p>Read more from the source' . $source_name . ': <em><a href="' . $article['url'] .
                    '" target="_blank">' . $article['title'] . '</a></em>' . $author_html . '</p><!-- /wp:paragraph -->';
                break;
            }
            case 'conversation': {
                $article = $board_tile['articles'][0];
                $epoch = $article['tweet_time'];
                $dt = new DateTime("@$epoch");
                $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['tweet_text'] .
                    '</p></blockquote><!-- /wp:quote -->';
                $post_content .= '<!-- wp:paragraph --><p>Tweeted by: <em><a href="' . $article['tweeter_url'] . '" target="_blank">@' .
                    $article['tweeter_name'] . '</a> on <a href="' . $article['tweet_url'] . '" target="_blank">' .
                    $dt->format('l, F d, Y') . '</a></em></p><!-- /wp:paragraph -->';
                break;
            }
            case 'tweet': {
                $article = $board_tile['articles'][0];
                $epoch = $article['tweet_time'];
                $dt = new DateTime("@$epoch");

                if (!empty($board_tile['summary'])) {
                    $post_content .=  '<!-- wp:paragraph --><p>' . $board_tile['summary'] . '</p><!-- /wp:paragraph -->';
                }

                $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['tweet_text'] .
                    '</p></blockquote><!-- /wp:quote -->';

                // if (isset($article['image_url']) && !empty($article['image_url'])) {
                //     $post_content .= '<!-- wp:image --><figure class="wp-block-image"><img src="' . $article['image_url'] .
                //     '" alt="Tweeted by: ' . $article['tweeter_name'] . '" /></figure><!-- /wp:image -->';
                // }
                
                $post_content .= '<!-- wp:paragraph --><p>Check <a href="' . $article['tweet_url'] . '" target="_blank">' .
                    'the tweet published on ' . $dt->format('l, F d, Y') . '</a> tweeted by <em><a href="' . $article['tweeter_url'] .
                    '" target="_blank">@' . $article['tweeter_name'] . '</a></em></p><!-- /wp:paragraph -->';

                break;
            }
            case 'note': {  
                
                $board_tile['summary'] = str_replace('<p>&nbsp;</p>', '<br data-rich-text-line-break="true">', $board_tile['summary']);
                $board_tile['summary'] = str_replace('<blockquote>', '<blockquote class="wp-block-quote">', $board_tile['summary']);
                $board_tile['summary'] = str_replace('</br>', '', $board_tile['summary']);
                $board_tile['summary'] = str_replace('<br/>', '', $board_tile['summary']);

                $post_content .= '<!-- wp:paragraph --><em>' . $board_tile['summary'] . '</em><!-- /wp:paragraph -->';
                $is_first_article = true;
                foreach ($board_tile['articles'] as $article) {
                    if ('link' == $article['article_type']) {

                        $post_content .= '<!-- wp:heading {"level":1} --><h1>' . $article['title'] . '</h1><!-- /wp:heading -->';
                        if (isset($article['image_url']) && !$is_first_article) {
                            $post_content .= '<!-- wp:image --><figure class="wp-block-image"><img src="' .
                                $article['image_url'] . '" alt="' . $article['title'] . '" /></figure><!-- /wp:image -->';
                        }
                        
                        $source_name = '';
                        if (isset($article['publisher_name']) && !empty($article['publisher_name'])) {
                            $source_name = ' ' . $article['publisher_name'];
                        }
        
                        $author_html = '';
                        if (isset($article['primary_author']) && !empty($article['primary_author'])) {
                            $author_html = ', Published by: ';
                            if (isset($article['author_url']) && !empty($article['author_url'])) {
                                $author_html .= '<em><a href="' . $article['author_url'] . '" target="_blank">' .
                                    $article['primary_author'] . '</a></em>';
                            } else {
                                $author_html .= '<em>'. $article['primary_author'] . '</em>';
                            }
                        }
        
                        $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['description'] .
                            '</p></blockquote><!-- /wp:quote -->';
                        $post_content .= '<!-- wp:paragraph --><p>Read more from the source' . $source_name . ': <em><a href="' . $article['url'] .
                            '" target="_blank">' . $article['title'] . '</a></em>' . $author_html . '</p><!-- /wp:paragraph -->';
                    } elseif ('conversation' == $article['article_type']) {
                        $post_content .= '<!-- wp:heading {"level":1} --><h1>Conversation started by ' . $article['tweeter_name'] .
                            '</h1><!-- /wp:heading -->';
                        $epoch = $article['tweet_time'];
                        $dt = new DateTime("@$epoch");
                        $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['tweet_text'] .
                            '</p></blockquote><!-- /wp:quote -->';
                        $post_content .= '<!-- wp:paragraph --><p>Tweeted by: <em><a href="' . $article['tweeter_url'] .
                            '" target="_blank">@' . $article['tweeter_name'] . '</a> on <a href="' . $article['tweet_url'] .
                            '" target="_blank">' . $dt->format('l, F d, Y') . '</a></em></p><!-- /wp:paragraph -->';
                    } elseif ('tweet' == $article['article_type']) {
                        $post_content .= '<!-- wp:heading {"level":1} --><h1>Tweet by ' . $article['tweeter_name'] .
                            '</h1><!-- /wp:heading -->';
                        $epoch = $article['tweet_time'];
                        $dt = new DateTime("@$epoch");
                        $post_content .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $article['tweet_text'] .
                            '</p></blockquote><!-- /wp:quote -->';

                        if (isset($article['image_url']) && !empty($article['image_url'] && !$is_first_article)) {
                            $post_content .= '<!-- wp:image --><figure class="wp-block-image"><img src="' . $article['image_url'] .
                            '" alt="Tweeted by: ' . $article['tweeter_name'] . '" /></figure><!-- /wp:image -->';
                        }

                        $post_content .= '<!-- wp:paragraph --><p>Check <a href="' . $article['tweet_url'] . '" target="_blank">' .
                            'the tweet published on ' . $dt->format('l, F d, Y') . '</a> tweeted by <em><a href="' . $article['tweeter_url'] .
                            '" target="_blank">@' . $article['tweeter_name'] . '</a></em></p><!-- /wp:paragraph -->';
                    } elseif ('converted_note' == $article['article_type']) {
                        if (isset($article['title'])) {
                            $post_content .= '<!-- wp:heading {"level":1} --><h1>' . $article['title'] .
                            '</h1><!-- /wp:heading -->';
                        }
                        
                        $article['tweet_text'] = str_replace('</br>', '', $article['tweet_text']);
                        $article['tweet_text'] = str_replace('<br/>', '', $article['tweet_text']);

                        $post_content .= '<!-- wp:paragraph --><em>' . $article['tweet_text'] . '</em><!-- /wp:paragraph -->';
                    }
                    $is_first_article = false;
                }
                break;
            }
        }
        return $post_content;
    }

    /**
     * Generate the post title for custom post from the Cronycle board tile content.
     *
     * @since    2.0.0
     * @access   private
     * @param    array      $board_tile    Board tile details.
     * @return   string     Custom post title
     */
    private function get_custom_post_title($board_tile)
    {
        $post_title = '';
        switch ($board_tile['tile_type']) {
            case 'link': {
                if (!empty($board_tile['user_title'])) {
                    $post_title = $board_tile['user_title'];
                } else {
                    $post_title = $board_tile['articles'][0]['title'];
                }
                break;
            }
            case 'conversation': {
                $post_title = 'Conversation started by ' . $board_tile['articles'][0]['tweeter_name'];
                break;
            }
            case 'tweet': {
                $post_title = 'Tweet by ' . $board_tile['articles'][0]['tweeter_name'];
                break;
            }
            case 'note': {
                if ($board_tile['title']) {
                    $post_title = $board_tile['title'];
                } elseif ($board_tile['text_only'] === true) {
                    $post_title = substr($board_tile['summary'], 0, 50) . "...";
                } elseif ('link' == $board_tile['articles'][0]['article_type']) {
                    $post_title = $board_tile['articles'][0]['title'];
                } elseif ('conversation' == $board_tile['articles'][0]['article_type']) {
                    $post_title = 'Conversation started by ' . $board_tile['articles'][0]['tweeter_name'];
                } elseif ('tweet' == $board_tile['articles'][0]['article_type']) {
                    $post_title = 'Tweet by ' . $board_tile['articles'][0]['tweeter_name'];
                }
                break;
            }
        }
        return $post_title;
    }

    /**
     * Gets the max created_at date corresponding to the Cronycle content
     * according to board if specified.
     *
     * @since    2.0.0
     * @access   private
     * @param    int        $board_id   Board ID whose max created_at date to fetch
     * @return   string     Max created_at date
     */
    private function get_max_created_at($board_id = null)
    {
        if (empty($board_id)) {
            $cronycle_content_post_ids = get_posts(
                array(
                    'post_type'      => $this->CronycleContent,
                    'post_status'    => array('publish', 'draft',
                        'trash', 'future', 'pending', 'private',
                        'auto-draft', 'inherit', 'added_to_drafts'),
                    'posts_per_page' => -1,
                    'meta_key'       => 'created_at',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'fields'         => 'ids',
                )
            );

            if (!empty($cronycle_content_post_ids)) {
                return get_post_meta($cronycle_content_post_ids[0], 'created_at', true);
            }
        } else {
            $cronycle_content_post_ids = get_posts(
                array(
                    'post_type'      => $this->CronycleContent,
                    'post_status'    => array('publish', 'draft',
                        'trash', 'future', 'pending', 'private',
                        'auto-draft', 'inherit', 'added_to_drafts'),
                    'posts_per_page' => -1,
                    'meta_key'       => 'board_id',
                    'meta_value'     => $board_id,
                    'fields'         => 'ids',
                )
            );

            if (!empty($cronycle_content_post_ids)) {
                $max_created_at = 0;
                foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                    $max_created_at = max($max_created_at, get_post_meta($cronycle_content_post_id, 'created_at', true));
                }
                return $max_created_at;
            }
        }

        return 0;
    }

    /**
     * Gets the min created_at date corresponding to the Cronycle content
     * according to board if specified.
     *
     * @since    2.0.0
     * @access   private
     * @param    int        $board_id   Board ID whose min created_at date to fetch
     * @return   string     Min created_at date
     */
    private function get_min_created_at($board_id = null)
    {
        if (empty($board_id)) {
            $cronycle_content_post_ids = get_posts(
                array(
                    'post_type'      => $this->CronycleContent,
                    'post_status'    => array('publish', 'draft',
                        'trash', 'future', 'pending', 'private',
                        'auto-draft', 'inherit', 'added_to_drafts'),
                    'posts_per_page' => -1,
                    'meta_key'       => 'created_at',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'ASC',
                    'fields'         => 'ids',
                )
            );

            if (!empty($cronycle_content_post_ids)) {
                return get_post_meta($cronycle_content_post_ids[0], 'created_at', true);
            }
        } else {
            $cronycle_content_post_ids = get_posts(
                array(
                    'post_type'      => $this->CronycleContent,
                    'post_status'    => array('publish', 'draft',
                        'trash', 'future', 'pending', 'private',
                        'auto-draft', 'inherit', 'added_to_drafts'),
                    'posts_per_page' => -1,
                    'meta_key'       => 'board_id',
                    'meta_value'     => $board_id,
                    'fields'         => 'ids',
                )
            );

            if (!empty($cronycle_content_post_ids)) {
                $min_created_at = PHP_INT_MAX;
                foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                    $min_created_at = min($min_created_at, get_post_meta($cronycle_content_post_id, 'created_at', true));
                }
                return $min_created_at;
            }
        }
    
        return PHP_INT_MAX;
    }

    /**
     * Get lead image url from the Cronycle board tile content if exists.
     *
     * @since    5.0.0
     * @access   private
     * @param    array      $board_tile    Board tile details.
     * @return   string     Lead image url
     */
    private function get_lead_image_url($board_tile)
    {
        switch ($board_tile['tile_type']) {
            case 'link': {
                if (!empty($board_tile['user_primary_image_url'])) {
                    return $board_tile['user_primary_image_url'];
                }
                return $board_tile['articles'][0]['image_url'];
            }
            case 'tweet': {
                return $board_tile['articles'][0]['image_url'];
            }
            case 'note': {
                if ($board_tile['text_only'] === true) {
                    return "";
                } elseif ('link' == $board_tile['articles'][0]['article_type']) {
                    return $board_tile['articles'][0]['image_url'];
                } elseif ('tweet' == $board_tile['articles'][0]['article_type']) {
                    return $board_tile['articles'][0]['image_url'];
                }
            }
        }
        return "";
    }

    /**
     * Find wordpress post by Cronycle content info.
     *
     * @since    5.0.0
     * @access   private
     * @param    array      $cronycle_content_post_id    ID of the Cronycle content.
     * @return   int        WP post ID if exists else null
     */
    private function find_post_by_board_info($cronycle_content_post_id)
    {
        $cronycle_content_meta = get_post_meta($cronycle_content_post_id);
        $args = array(
            'post_type'   => 'post',
            'post_status' => 'any',
            'meta_query'  => array(
                array(
                    'key'   => 'cronycle_content_meta',
                    'value' => serialize(array(
                        'board_id'      => $cronycle_content_meta['board_id'][0],
                        'board_tile_id' => $cronycle_content_meta['board_tile_id'][0],
                    )),
                    'compare' => 'LIKE'
                )
            ),
            'fields'      => 'ids'
        );
        $query = new WP_Query($args);
        if (!empty($query) && !empty($query->posts)) {
            return $query->posts[0];
        }
        return null;
    }

    /**
     * Find featured image by Cronycle content info.
     *
     * @since    5.0.0
     * @access   private
     * @param    array      $cronycle_content_post_id    ID of the Cronycle content.
     * @return   int        Featured image ID if exists else null
     */
    private function find_attachment_by_board_info($cronycle_content_post_id)
    {
        $cronycle_content_meta = get_post_meta($cronycle_content_post_id);
        $args = array(
            'post_type'   => 'attachment',
            'post_status' => 'any',
            'meta_query'  => array(
                array(
                    'key'   => 'cronycle_content_meta',
                    'value' => serialize(array(
                        'board_id'      => $cronycle_content_meta['board_id'][0],
                        'board_tile_id' => $cronycle_content_meta['board_tile_id'][0],
                        'image_url'     => $cronycle_content_meta['image_url'][0],
                    )),
                    'compare' => 'LIKE'
                )
            ),
            'fields'      => 'ids'
        );
        $query = new WP_Query($args);
        if (!empty($query) && !empty($query->posts)) {
            return $query->posts[0];
        }
        return null;
    }

    /**
     * Add lead image as featured image in Cronycle content.
     *
     * @since    5.0.0
     * @access   public
     * @param    array      $cronycle_content_post_id    ID of the Cronycle content.
     */
    public function add_lead_image_as_featured_image($cronycle_content_post_id)
    {
        $lead_image_url = get_post_meta($cronycle_content_post_id, 'image_url', true);
        if (isset($lead_image_url) && !empty($lead_image_url)) {
            $desc = get_the_title($cronycle_content_post_id);
            
            // find existing thumbnail first and add if not exists
            $attachment_id = $this->find_attachment_by_board_info($cronycle_content_post_id);
            if (!isset($attachment_id)) {
                if (self::CRON_CONTENT_ASYNC_MEDIA_LOAD) {
                    set_time_limit(self::CRON_CONTENT_MEDIA_LOAD_TIMEOUT);

                    // Require some Wordpress core files for processing images
                    // when using media_sideload_image with WP-Cron
                    // ref: https://royduineveld.nl/creating-your-own-wordpress-import/
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');

                    $attachment_id = media_sideload_image($lead_image_url, 0, $desc, 'id');

                    if ($l = ini_get('max_execution_time')) {
                        set_time_limit($l);
                    }
                } else {
                    $attachment_id = media_sideload_image($lead_image_url, 0, $desc, 'id');
                }
            }

            if (isset($attachment_id) && !is_wp_error($attachment_id)) {
                set_post_thumbnail($cronycle_content_post_id, $attachment_id);

                // add some metadata to identify Cronycle source
                $cronycle_content_meta = get_post_meta($cronycle_content_post_id);
                add_post_meta($attachment_id, 'cronycle_content_meta', array(
                    'board_id'      => $cronycle_content_meta['board_id'][0],
                    'board_tile_id' => $cronycle_content_meta['board_tile_id'][0],
                    'image_url'     => $cronycle_content_meta['image_url'][0],
                ), true);
            }
        }
    }

    /**
     * Gets the last fetch time corresponding to the Cronycle content.
     *
     * @since    2.0.0
     * @access   private
     * @return   string     Last fetch timestamp
     */
    private function get_last_fetch_timestamp()
    {
        $options = get_option('cronycle_content_draft_post_options');
        if (!isset($options['last_fetch_timestamp']) || empty($options['last_fetch_timestamp'])) {
            return 0;
        }
        return $options['last_fetch_timestamp'];
    }

    /**
     * Updates the options created for internal use related to the Cronycle content.
     *
     * @since    2.0.0
     * @access   private
     */
    private function update_draft_post_options()
    {
        $options = get_option('cronycle_content_draft_post_options');
        $options['last_fetch_timestamp'] = time();  // current epoch time
        if (!update_option('cronycle_content_draft_post_options', $options)) {
            CronycleContentLogger::log("Unable to update Cronycle draft post options.");
        }
    }

    /**
     * Loads the content from Cronycle boards and insert it as custom post into WordPress database.
     * Also resets data if requested or data get outdated.
     *
     * @since    2.0.0
     * @access   public
     */
    public function load_content()
    {
        global $typenow;
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $typenow) {
            return;
        }

        // return if account not linked
        $options = get_option('cronycle_content_options');
        if (!isset($options['auth_token']) || empty($options['auth_token'])) {
            return;
        }

        // finding number of items set to display on edit.php page
        global $wpdb;
        // $query = "SELECT $wpdb->usermeta.meta_value FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'edit_cronycle_content_per_page'";
        $wpdb->update($wpdb->usermeta, ['meta_value' => 40], ['meta_key' => 'edit_cronycle_content_per_page']);

        // retrieving query parameters from url
        $posts_per_page = 40;
        $page = isset($_GET['paged']) ? $_GET['paged'] - 1 : 0;
        $start = ($page * $posts_per_page);
        $board_id = isset($_GET['board_id']) && $_GET['board_id'] != 'all' ? $_GET['board_id'] : -1;
        $reset_content = isset($_GET['cronycle_content_reset']);

        // fetching one extra article so that page navigation buttons will always be displayed
        $posts_per_page += 1;

        // for all cronycle_content posts deletion, in case
        // - reset is request is made
        // - token in database is not valid now
        if ($reset_content || !CronycleContentAPIClient::verify_token($options['auth_token'])) {
            self::delete_content($this->CronycleContent);
            $cronycle_content_admin_url = admin_url() . "edit.php?post_type=cronycle_content";
            echo "<script>window.top.location='$cronycle_content_admin_url'</script>";
            exit();
        }

        // creating a map of board tile IDs of existing board content in WP database
        $args = array(
            'post_type'     => $this->CronycleContent,
            'post_status'   => array('publish', 'draft',
                'trash', 'future', 'pending', 'private',
                'auto-draft', 'inherit', 'added_to_drafts'),
            'numberposts'   => -1,
            'fields'        => 'ids',
        );
        $board_tile_id_to_post_id = array();
        $board_tile_id_to_updated_at = array();
        $cronycle_content_post_ids = get_posts($args);
        if (!empty($cronycle_content_post_ids)) {
            foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                $board_tile_id = get_post_meta($cronycle_content_post_id, 'board_tile_id', true);
                $board_id_to_post_id[$board_tile_id] = $cronycle_content_post_id;
                $board_tile_id_to_updated_at[$board_tile_id] = get_post_meta($cronycle_content_post_id, 'updated_at', true);
            }
        }

        // fetching the board content
        $api_client = new CronycleContentAPIClient($options['auth_token']);
        $board_tiles = $api_client->get_board_tiles($board_id, $start, $posts_per_page, true, true, false);

        if (empty($board_tiles)) {
            return;
        }

        // debugging statements
        // $fp = fopen(plugin_dir_path(__FILE__) . 'cronycle_content_debug_log.txt', 'w');
        // fwrite($fp, print_r($board_tiles, true));
        // fclose($fp);

        foreach ($board_tiles as $board_tile) {
            // inserting the board content as custom post
            // either if not exist or is not up-to-date
            if (!isset($board_tile_id_to_updated_at[$board_tile['tile_id']]) ||
                (isset($board_tile_id_to_updated_at[$board_tile['tile_id']]) &&
                    $board_tile_id_to_updated_at[$board_tile['tile_id']] != $board_tile['updated_at'])) {
                $postarr = array(
                    'ID' => isset($board_id_to_post_id[$board_tile['tile_id']]) ? $board_id_to_post_id[$board_tile['tile_id']] : 0,
                    'post_title'    => wp_strip_all_tags($this->get_custom_post_title($board_tile)),
                    'post_content'  => $this->get_custom_post_content_for_gutenberg($board_tile),
                    'post_status'   => 'draft',
                    'post_type'     => $this->CronycleContent,
                    'meta_input'    => array(
                        'wp_board_tile_id' => $board_tile['wp_tile_id'],
                        'board_id'         => $board_tile['board_id'],
                        'board_name'       => $board_tile['board_name'],
                        'board_tile_id'    => $board_tile['tile_id'],
                        'board_tile_type'  => $board_tile['tile_type'],
                        'content_type'     => $board_tile['content_type'],
                        'created_at'       => $board_tile['created_at'],
                        'updated_at'       => $board_tile['updated_at'],
                        'tags'             => $board_tile['user_tags'],
                        'image_url'        => $this->get_lead_image_url($board_tile),
                    )
                );
            
                $cronycle_content_post_id = wp_insert_post($postarr);

                // update post status for new/updated content
                $this->update_post_status($cronycle_content_post_id);

                // add lead image as featured image
                if (self::CRON_CONTENT_ASYNC_MEDIA_LOAD) {
                    // remove existing cronycle content cron event for this post if one exists and trigger new one
                    wp_clear_scheduled_hook('cronycle_content_new_tile_added', array($cronycle_content_post_id));
                    wp_schedule_single_event(time(), 'cronycle_content_new_tile_added', array($cronycle_content_post_id));
                } else {
                    $this->add_lead_image_as_featured_image($cronycle_content_post_id);
                }
                // update post date to current date
                $this->update_post_date($board_tile);
            }
        }

        // set the default categories
        $this->set_default_categories();

        // set the tags according to the settings
        $this->set_tags();
    }
    
    /**
     * Delete all the content from WordPress database related to Cronycle
     * content custom post type.
     *
     * @since    2.0.0
     * @access   public
     * @param    string     $cronycle_content_post_type      Type of Cronycle content post
     */
    public static function delete_content($cronycle_content_post_type)
    {
        // deletes all the post with status publish, draft and trash
        $args = array(
                'post_type'     => $cronycle_content_post_type,
                'post_status'   => array('publish', 'draft',
                    'trash', 'future', 'pending', 'private',
                    'auto-draft', 'inherit', 'added_to_drafts'),
                'numberposts'   => -1,
                'fields'        => 'ids'
            );
        $cronycle_content_post_ids = get_posts($args);
        if (!empty($cronycle_content_post_ids)) {
            foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                // delete featured image first if exists and not in use
                if (has_post_thumbnail($cronycle_content_post_id)) {
                    $attachment_id = get_post_thumbnail_id($cronycle_content_post_id);
                    if (!self::is_featured_image_in_use($attachment_id, $cronycle_content_post_type)) {
                        wp_delete_attachment($attachment_id, true);
                    }
                }
                wp_delete_post($cronycle_content_post_id, true);
            }
        }
    }

    /**
     * Check whether featured image is use or not.
     *
     * @since    5.0.0
     * @access   private
     * @param    int        $attachment_id                   ID of featured image to check
     * @param    string     $cronycle_content_post_type      Type of Cronycle content post
     */
    private static function is_featured_image_in_use($attachment_id, $cronycle_content_post_type)
    {
        // query posts by thumbnail other than Cronycle content itself
        $args = array(
                'post_type__not_in'  => $cronycle_content_post_type,
                'meta_query' => array(
                    array(
                        'key'   => '_thumbnail_id',
                        'value' => $attachment_id
                    ),
                ),
                'fields'=> 'ids'
            );
        $query = new WP_Query($args);
        return isset($query->posts) && !empty($query->posts);
    }

    /**
     * Sets the default categories for the Cronycle content post.
     *
     * @since    2.0.0
     * @access   private
     */
    private function set_default_categories()
    {
        $options = get_option('cronycle_content_draft_post_options');
        if (!isset($options)) {
            return;
        }
        
        // creating array of category IDs using category names
        $categories = array();
        if (isset($options['default_categories'])) {
            foreach ($options['default_categories'] as $key => $default_categories) {
                $categories[$key] = array();
                foreach ($default_categories as $default_category) {
                    $categories[$key][] = $default_category;
                }
            }
        }

        // fetching all the post with draft status
        $args = array(
            'post_type'     => $this->CronycleContent,
            'post_status'   => array('draft'),
            'numberposts'   => -1,
            'fields'        => 'ids'
        );
        $cronycle_content_post_ids = get_posts($args);
        if (!empty($cronycle_content_post_ids)) {
            foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                $board_id = get_post_meta($cronycle_content_post_id, 'board_id', true);
                // setting default category if exist in settings else removing
                if (isset($categories[$board_id])) {
                    wp_set_post_categories($cronycle_content_post_id, $categories[$board_id]);
                } else {
                    wp_set_post_categories($cronycle_content_post_id, array());
                }
            }
        }
    }

    /**
     * Sets the tags for the Cronycle content post if inclusion is true in settings.
     *
     * @since    2.0.0
     * @access   private
     */
    private function set_tags()
    {
        $options = get_option('cronycle_content_draft_post_options');
        if (!isset($options) || !isset($options['include_tag'])) {
            return;
        }

        // fetching all the post with draft status
        $args = array(
            'post_type'     => $this->CronycleContent,
            'post_status'   => array('draft'),
            'numberposts'   => -1,
            'fields'        => 'ids'
        );
        $cronycle_content_post_ids = get_posts($args);
        if (!empty($cronycle_content_post_ids)) {
            foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
                // setting tags if publishing for tags is set else removing
                if ($options['include_tag'] == 'yes') {
                    $tags = get_post_meta($cronycle_content_post_id, 'tags', true);
                    wp_set_post_tags($cronycle_content_post_id, $tags);
                } else {
                    wp_set_post_tags($cronycle_content_post_id, array());
                }
            }
        }
    }

    /**
     * Update the post date for the Cronycle content post to current date.
     *
     * @since    2.0.1
     * @access   private
     */
    private function update_post_date($board_tile)
    {

        $time = current_time('mysql');
        wp_update_post(
            array(
                'ID'            => $board_tile['tile_id'],
                'post_date'     => $time,
                'post_date_gmt' => get_gmt_from_date($time)
            )
        );

        // fetching all the post with draft status
        // $args = array(
        //     'post_type'     => $this->CronycleContent,
        //     'post_status'   => array('draft'),
        //     'numberposts'   => -1,
        //     'fields'        => 'ids'
        // );
        // $cronycle_content_post_ids = get_posts($args);
        // if (!empty($cronycle_content_post_ids)) {
        //     foreach ($cronycle_content_post_ids as $cronycle_content_post_id) {
        //         $fp = fopen(plugin_dir_path(__FILE__) . 'cronycle_content_debug_log.txt', 'a');
        //         fwrite($fp, print_r(wp_get_single_post($cronycle_content_post_id), true));
        //         fclose($fp);
        //     }
        // }
    }

    /**
     * Get the expected Cronycle content post status on the basis of corresponding WP post if exists.
     *
     * @since    4.0.0
     * @access   private
     * @param    int        $cronycle_content_post_id      Cronycle content post ID
     * @return   string     Expected post status
     */
    private function get_published_cronycle_content_status($cronycle_content_post_id)
    {
        // if already published as WP post return it
        $post_status = get_post_status($cronycle_content_post_id);
        if ('publish' === $post_status) {
            return $post_status;
        }

        // for added to drafts case we need to check corresponding WP post status
        // so lets find using cronycle content meta info
        $board_id = get_post_meta($cronycle_content_post_id, 'board_id', true);
        $board_tile_id = get_post_meta($cronycle_content_post_id, 'board_tile_id', true);

        // query post by board_tile_id meta
        $args = array(
            'post_type'  => 'post',
            'meta_query' => array(
                array(
                    'key'   => 'cronycle_content_meta',
                    'value' => serialize(array(
                        'board_id'         => $board_id,
                        'board_tile_id'    => $board_tile_id
                    )),
                    'compare' => 'LIKE'
                )
            )
        );
        $query = new WP_Query($args);
        // now check for wordpress post status if exist for current cronycle
        if (!empty($query) && !empty($query->posts)) {
            foreach ($query->posts as $wp_post) {
                if ('publish' === $wp_post->post_status) {
                    return $wp_post->post_status;
                } elseif ('draft' === $wp_post->post_status) {
                    return 'added_to_drafts';
                }
            }
        }

        // for backward compatibility (as earlier post IDs were stored on cronycle content side
        // which were reset on resetting cronycle content)
        if ('draft' === $post_status) {
            $wp_post_ids = get_post_meta($cronycle_content_post_id, 'wp_post_id');
            foreach ($wp_post_ids as $wp_post_id) {
                $wp_post_status = get_post_status($wp_post_id);
                if ('publish' === $wp_post_status) {
                    return $wp_post_status;
                }
            }
        }
        return $post_status;
    }

    /**
     * Update the post status of Cronycle content post in accordance with corresponding WP post if exists.
     *
     * @since    4.0.0
     * @access   private
     * @param    int        $cronycle_content_post_id      Cronycle content post ID
     */
    private function update_post_status($cronycle_content_post_id)
    {
        $post_status = get_post_status($cronycle_content_post_id);
        if ('publish' === $post_status) {
            return;
        }

        $wp_post_status = $this->get_published_cronycle_content_status($cronycle_content_post_id);
        if ($post_status != $wp_post_status) {
            wp_update_post(array(
                'ID' => $cronycle_content_post_id,
                'post_status' => $wp_post_status,
            ));
        }
    }

    /**
     * Find cronycle content by board tile ID.
     *
     * @since    4.0.0
     * @access   private
     * @param    int        $board_tile_id     Board tile ID
     * @return   object     Cronycle content post object
     */
    private function get_cronycle_content_by_board_tile($board_tile_id)
    {
        $args = array(
            'post_type'  => $this->CronycleContent,
            'meta_query' => array(
                array(
                    'key'   => 'board_tile_id',
                    'value' => $board_tile_id,
                )
            )
        );
        $query = new WP_Query($args);
        if (!empty($query) && !empty($query->posts)) {
            return $query->posts[0];
        }
        return null;
    }

    /**
     * Add a Cronycle content post as original WordPress post
     *
     * @since    5.0.0
     * @access   private
     * @param    int        $cronycle_content_post_id    Cronycle content post ID
     * @param    string     $new_post_status             New post status to use, if not same as Cronycle content
     * @return   int        ID of newly added WP post
     */
    private function add_cronycle_content_as_wp_post($cronycle_content_post_id, $new_post_status = '')
    {
        // publishes an original WordPress post corresponding to
        // Cronycle content post whose edit request is received
        $post = get_post($cronycle_content_post_id);
        $post->post_type = 'post';
        if (!empty($new_post_status)) {
            $post->status = $new_post_status;
        }

        // add categories and tags
        $post->post_category = wp_get_post_categories($post->ID);
        $post->tags_input = wp_get_post_tags($post->ID, array('fields' => 'names'));
    
        // reset post ID, otherwise WP post will not be published
        $post->ID = 0;

        // insert WP post
        $post_id = wp_insert_post($post);

        // add featured image
        $attachment_id = get_post_thumbnail_id($cronycle_content_post_id);
        set_post_thumbnail($post_id, $attachment_id);

        // storing original WP post ID as meta info in cronycle content
        add_post_meta($cronycle_content_post_id, 'wp_post_id', $post_id);
    
        // adding board info in published post too
        $cronycle_content_meta = get_post_meta($cronycle_content_post_id);
        add_post_meta($post_id, 'cronycle_content_meta', array(
            'board_id'         => $cronycle_content_meta['board_id'][0],
            'board_tile_id'    => $cronycle_content_meta['board_tile_id'][0]
        ), true);

        return $post_id;
    }

    /**
     * Add Cronycle content posts as real WordPress drafts
     *
     * @since    3.1.0
     * @access   public
     * @param    int        $cronycle_content_post_id    Cronycle content post ID to add as WP draft
     * @return   int        ID of newly added WP draft post
     */
    private function add_to_wp_drafts($cronycle_content_post_id)
    {
        // copy/publish Cronycle content as an original WordPress post
        $post_id = $this->add_cronycle_content_as_wp_post($cronycle_content_post_id, 'draft');

        // storing original WP post ID as meta info in cronycle content
        add_post_meta($cronycle_content_post_id, 'wp_post_id', $post_id);
    
        // adding board info in published post too
        $cronycle_content_meta = get_post_meta($cronycle_content_post_id);
        add_post_meta($post_id, 'cronycle_content_meta', array(
            'board_id'         => $cronycle_content_meta['board_id'][0],
            'board_tile_id'    => $cronycle_content_meta['board_tile_id'][0]
        ), true);

        // update cronycle content status
        wp_update_post(array(
            'ID' => $cronycle_content_post_id,
            'post_status' => 'added_to_drafts',
        ));

        return $post_id;
    }
    
    /**
     * Redirect to editing screen of real WordPress draft
     *
     * @since    3.1.0
     * @access   public
     */
    public function open_wp_post_editor()
    {
        global $typenow;
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $typenow) {
            return;
        }

        // add cronycle content as real wp draft if request corresponds to that
        if (isset($_GET['add_to_wp_drafts']) && $_GET['add_to_wp_drafts'] == true && isset($_GET['post'])) {
            $post_id = $this->add_to_wp_drafts($_GET['post']);

            // redirecting to new wp draft edit screen
            if (wp_safe_redirect(get_edit_post_link($post_id, ''))) {
                exit;
            }
        }
    }

    /**
     * Remove all non-required sub-menus from admin navbar.
     *
     * @since    2.0.0
     * @access   public
     */
    public function modify_admin_menu()
    {
        remove_submenu_page('edit.php?post_type=cronycle_content', 'edit-tags.php?taxonomy=category&amp;post_type=cronycle_content');
        remove_submenu_page('edit.php?post_type=cronycle_content', 'edit-tags.php?taxonomy=post_tag&amp;post_type=cronycle_content');
    }

    /**
     * Add help text below list view title.
     *
     * @since    2.0.2
     * @access   public
     */
    public function add_help_text()
    {
        global $pagenow, $post_type;
        if (is_admin() && $pagenow == 'edit.php' && $post_type == $this->CronycleContent) {
            $help_text = '"<p>Not seeing any board content? Please learn more about plugin in <a href=\"https://www.cronycle.com/wordpress-plugin/\" target=\"_blank\">our tutorial</a>.</p>"';
            echo '<script>jQuery(document).ready(function($) { $(' . $help_text . ').insertBefore(".subsubsub"); });</script>';
        }
    }

    /**
     * Defines the actions after Cronycle content post publishing notification.
     *
     * @since    2.0.0
     * @access   public
     * @param    string     $cronycle_content_post_id      ID of the published Cronycle content
     * @param    WP_Post    $cronycle_content_post         Post object corresponding to published cronycle content
     */
    public function cronycle_content_post_published_notification($cronycle_content_post_id, $cronycle_content_post)
    {
        // if post id is available then post is already published
        $wp_post_id = get_post_meta($cronycle_content_post_id, 'wp_post_id', true);
        if (!empty($wp_post_id)) {
            return;
        }

        // if wordpress post already exist just update metadata and return
        $wp_post_id = $this->find_post_by_board_info($cronycle_content_post_id);
        if (isset($wp_post_id) && !empty($wp_post_id)) {
            add_post_meta($cronycle_content_post_id, 'wp_post_id', $wp_post_id);
            return;
        }
        
        // copy/publish Cronycle content as an original WordPress post
        $post_id = $this->add_cronycle_content_as_wp_post($cronycle_content_post_id);
    }

    /**
     * Defines the actions after WordPress post publishing notification.
     *
     * @since    4.0.0
     * @access   public
     * @param    string     $ID      ID of the published post
     * @param    WP_Post    $post    Post object corresponding to published post
     */
    public function wp_post_published_notification($ID, $post)
    {
        // check if cronycle content meta info exists in current published post
        $cronycle_content_meta = get_post_meta($ID, 'cronycle_content_meta', true);
        if (empty($cronycle_content_meta)) {
            return;
        }

        // find cronycle content from meta info
        $cronycle_content = $this->get_cronycle_content_by_board_tile($cronycle_content_meta['board_tile_id']);
        if (empty($cronycle_content)) {
            return;
        }

        // check status, if already in publish state then we are done else update it
        $post_status = get_post_status($cronycle_content->ID);
        if ('publish' == $post_status) {
            return;
        }
        wp_update_post(array(
            'ID' => $cronycle_content->ID,
            'post_status' => 'publish',
        ));
    }

    /**
     * Customizes the column header array as per Cronycle content post list view.
     *
     * @since    2.0.0
     * @access   public
     * @param    array      $columns      Array of column header names
     * @return   array      Modified array of column header names
     */
    public function cronycle_content_post_columns($columns)
    {
        $columns = array(
            'cb'            => $columns['cb'],
            'title'         => __('Title', $this->CronycleContent),
            'status'        => __('Status', $this->CronycleContent),
            'content_type'  => __('Content Type', $this->CronycleContent),
            'board'         => __('Board', $this->CronycleContent),
            'created_at'    => __('Pinning Date', $this->CronycleContent),
        );
        return $columns;
    }

    /**
     * Finds and prints the value corresponding to input column header and post ID.
     *
     * @since    2.0.0
     * @access   public
     * @param    string     $column                       Name of header column
     * @param    string     $cronycle_content_post_id     Cronycle content post ID
     */
    public function cronycle_content_post_column_value($column, $cronycle_content_post_id)
    {
        // Board column
        if ('board' === $column) {
            echo get_post_meta($cronycle_content_post_id, 'board_name', true);
        }
        // Content Type column
        if ('content_type' === $column) {
            echo get_post_meta($cronycle_content_post_id, 'content_type', true);
        }
        // Pinning Date column
        if ('created_at' === $column) {
            $epoch = get_post_meta($cronycle_content_post_id, 'created_at', true);
            $dt = new DateTime("@$epoch");
            echo $dt->format('Y/m/d') . "<br>" . $dt->format('H:i:s');
        }
        // Post Status column
        if ('status' === $column) {
            $post_status = get_post_status($cronycle_content_post_id);
            // NOTE: Not using this as it can impact the performance of cronycle content's page reload
            // if ('publish' !== $post_status) {
            //     $wp_post_status = $this->get_published_cronycle_content_status($cronycle_content_post_id);
            //     // update cronycle content status as per corresponding WP post status if not up to date
            //     if ('publish' === $wp_post_status ||
            //         ('draft' === $post_status && 'added_to_drafts' === $wp_post_status)) {
            //         wp_update_post(array(
            //             'ID' => $cronycle_content_post_id,
            //             'post_status' => $wp_post_status,
            //         ));
            //         $post_status = $wp_post_status;
            //     }
            // }
            switch ($post_status) {
                case 'draft':           echo 'New';    break;
                case 'added_to_drafts': echo 'Draft';  break;
                case 'trash':           echo 'Hidden'; break;
                default:                echo get_post_status_object($post_status)->label;
            }
        }
    }

    /**
     * Customizes the column header array of sortable columns in
     * Cronycle content post list view.
     *
     * @since    2.0.0
     * @access   public
     * @param    array      $columns      Array of sortable columns
     * @return   array      Modified array of sortable columns
     */
    public function cronycle_content_post_sortable_columns($columns)
    {
        // $columns['board'] = 'board';
        // $columns['content_type'] = 'content_type';
        $columns['created_at'] = 'created_at';
        return $columns;
    }

    /**
     * Sets the required query params depending upon the order by field
     * in the query.
     *
     * @since    2.0.0
     * @access   public
     * @param    WP_Query   $query      Generated WP query
     */
    public function cronycle_content_post_orderby($query)
    {
        global $typenow;
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $typenow) {
            return;
        }

        // return if not an admin page or query is not main query
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        // if no order is set
        if ('' == $query->get('orderby') && empty($query->get('meta_key'))) {
            $query->set('orderby', 'created_at');
            if ('' == $query->get('order')) {
                $query->set('order', 'DESC');
            }
        }

        // for order by board
        if ('board' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'board_name');
        }
        // for order by content type
        if ('content_type' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'content_type');
        }
        // for order by pinning date
        if ('created_at' === $query->get('orderby')) {
            $query->set('orderby', 'meta_value');
            $query->set('meta_key', 'created_at');
            $query->set('meta_type', 'numeric');
        }
    }

    /**
     * Hides the default filters for Cronycle content post list view provided by WordPress.
     *
     * @since    2.0.0
     * @access   private
     */
    private function hide_default_wp_filters()
    {
        ?>
            <script>
                jQuery('select[name="m"]').hide();      // for published date filter
                jQuery('select[name="cat"]').hide();    // for category filter
                // for bulk actions
                jQuery('div.bulkactions select[name="action"] option[value="edit"]').hide();
                jQuery('div.bulkactions select[name="action2"] option[value="edit"]').hide();
                jQuery('div.bulkactions select[name="action"] option[value="trash"]').html("Hide");
                jQuery('div.bulkactions select[name="action2"] option[value="trash"]').html("Hide");
                jQuery('div.bulkactions select[name="action"] option[value="delete"]').hide();
                // filters by yoast plugin
                jQuery('select[name="seo_filter"]').hide();
                jQuery('select[name="readability_filter"]').hide();
            </script>
        <?php
    }

    /**
     * Defines custom filters for filtering Cronycle content post list view.
     *
     * @since    2.0.0
     * @access   public
     */
    public function cronycle_content_post_filters()
    {
        global $typenow;
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $typenow) {
            return;
        }

        // fetching all distinct options for the board filter
        global $wpdb;
        $query = "SELECT T1.board_id, T2.board_name FROM 
            (SELECT MIN($wpdb->postmeta.post_id) AS id, $wpdb->postmeta.meta_value AS board_id FROM $wpdb->postmeta 
                WHERE $wpdb->postmeta.meta_key = 'board_id' GROUP by $wpdb->postmeta.meta_value) T1 INNER JOIN 
            (SELECT MIN($wpdb->postmeta.post_id) AS id, $wpdb->postmeta.meta_value AS board_name FROM $wpdb->postmeta 
                WHERE $wpdb->postmeta.meta_key = 'board_name' GROUP by $wpdb->postmeta.meta_value) T2 ON T1.id=T2.id";
        $res = $wpdb->get_results($query, OBJECT);

        $options = array();
        if (!empty($res)) {
            foreach ($res as $obj) {
                $options[$obj->board_id] = $obj->board_name;
            }
        }
        
        // creating filter dropdown list
        $current_option = '';
        if (isset($_GET['board_id'])) {
            $current_option = $_GET['board_id']; // check if option has been selected
        }
        _e('<select name="board_id" id="board_id">');
        _e('<option value="all" ' . selected('all', $current_option) . '>All Boards</option>', $this->CronycleContent);
        foreach ($options as $key=>$value) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($key, $current_option), esc_attr($value));
        }
        _e('</select>');
    }

    /**
     * Handles the query for custom filters.
     *
     * @since    2.0.0
     * @access   public
     * @param    WP_Query   $query      Generated WP query
     */
    public function cronycle_content_post_filterby($query)
    {
        global $pagenow;
        // get the post type
        $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        if (is_admin() && $pagenow == 'edit.php' && $post_type == $this->CronycleContent &&
            isset($_GET['board_id']) && $_GET['board_id'] != 'all') {
            /**
             * NOTE:
             * This logic is intersecting with the order by query request
             * so we are now using meta_query instead of meta_key and meta_values directly
             */
            // $query->query_vars['meta_key'] = 'board_id';
            // $query->query_vars['meta_value'] = $_GET['board_id'];
            // $query->query_vars['meta_compare'] = '=';
            $query->query_vars['meta_query'] = array(
                array(
                    'key'     => 'board_id',
                    'compare' => '=',
                    'value'   => $_GET['board_id']
                )
            );
        }
    }

    /**
     * Customizes the row actions corresponding to Cronycle content post.
     *
     * @since    2.0.0
     * @access   public
     * @param    array      $actions      Array of row actions
     * @param    WP_Post    $post         WP Post object
     */
    public function cronycle_content_post_row_actions($actions, $post)
    {
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $post->post_type) {
            return $actions;
        }

        if (isset($actions['trash'])) {
            $actions['trash'] = str_replace('Trash', 'Hide', $actions['trash']);
        }

        // removing edit action for all except draft post
        if ('draft' != $post->post_status) {
            unset($actions['edit']);
        }


        if ('trash' == $post->post_status) {
            unset($actions['delete']);
            $actions['untrash'] = str_replace('Restore |', 'Restore', $actions['untrash']);
        }

        // for draft post types
        if ('draft' == $post->post_status) {
            // removing trash action for draft post
            // unset($actions['trash']);
            $actions['edit'] = sprintf('<a href="%s&add_to_wp_drafts=true">%s</a>', get_edit_post_link(), __('Add to my drafts', $this->CronycleContent));
        }
        
        // for published post types
        if ('publish' == $post->post_status || 'added_to_drafts' == $post->post_status) {
            // removing quick edit action for published post
            unset($actions['inline hide-if-no-js']);
            unset($actions['view']);
                
            $wp_post_id = get_post_meta($post->ID, 'wp_post_id', true);
            if (isset($wp_post_id) && !empty($wp_post_id)) {
                // setting view action link to wordpress published post
                $permalink = get_permalink($wp_post_id);
                /* NOTE:
                 * If published post is deleted, then permalink will not be fetched.
                 */
                if (isset($permalink) && !empty($permalink) && get_post_status($wp_post_id) != 'trash') {
                    $actions['view'] = sprintf('<a href="%s">%s</a>', esc_url($permalink), __('View', $this->CronycleContent));
                    if ('added_to_drafts' == $post->post_status) {
                        $actions = array_merge(array('open' => sprintf(
                            '<a href="%s">%s</a>',
                            get_edit_post_link($wp_post_id),
                            __('Open', $this->CronycleContent)
                        )), $actions);
                    }
                }
            }

            // jQuery to remove hyperlinks from the post titles in list view
            // echo '<script>if(typeof jQuery.removeTitleHyperlinks == "function") jQuery.removeTitleHyperlinks();</script>';
        }

        return $actions;
    }

    /**
     * Customizes the messages corresponding to Cronycle content post.
     *
     * @since    2.0.0
     * @access   public
     * @param    array      $messages     Array of update messages
     * @param    array      Customized array of update messages
     */
    public function cronycle_content_post_updated_messages($messages)
    {
        $post             = get_post();
        $post_type        = get_post_type($post);
        $post_type_object = get_post_type_object($post_type);

        $messages[$this->CronycleContent] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => __('Cronycle Content updated.', $this->CronycleContent),
            2  => __('Custom field updated.', $this->CronycleContent),
            3  => __('Custom field deleted.', $this->CronycleContent),
            4  => __('Cronycle Content updated.', $this->CronycleContent),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision']) ? sprintf(__('Cronycle Content restored to revision from %s', $this->CronycleContent), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __('Cronycle Content published.', $this->CronycleContent),
            7  => __('Cronycle Content saved.', $this->CronycleContent),
            8  => __('Cronycle Content submitted.', $this->CronycleContent),
            9  => sprintf(
                __('Cronycle Content scheduled for: <strong>%1$s</strong>.', $this->CronycleContent),
                // translators: Publish box date format, see http://php.net/date
                date_i18n(__('M j, Y @ G:i', $this->CronycleContent), strtotime($post->post_date))
            ),
            10 => __('Cronycle Content draft updated.', $this->CronycleContent)
        );
    
        if ($post_type_object->publicly_queryable && $this->CronycleContent === $post_type) {
            $wp_post_id = get_post_meta($post->ID, 'wp_post_id', true);
            if (isset($wp_post_id) && !empty($wp_post_id)) {
                $permalink = get_permalink($wp_post_id);

                $view_link = sprintf(' <a href="%s">%s</a>', esc_url($permalink), __('View post', $this->CronycleContent));
                $messages[$post_type][1] .= $view_link;
                $messages[$post_type][6] .= $view_link;
                $messages[$post_type][9] .= $view_link;

                $preview_permalink = add_query_arg('preview', 'true', $permalink);
                $preview_link = sprintf(' <a target="_blank" href="%s">%s</a>', esc_url($preview_permalink), __('Preview post', $this->CronycleContent));
                $messages[$post_type][8]  .= $preview_link;
                $messages[$post_type][10] .= $preview_link;
            }
        }
    
        return $messages;
    }

    /**
     * Defines custom actions above Cronycle content post list view table.
     *
     * @since    2.0.0
     * @access   public
     */
    public function cronycle_content_post_tablenav_actions()
    {
        global $typenow;
        // return if page other than Cronycle content page is loaded
        if ($this->CronycleContent != $typenow) {
            return;
        }

        $this->hide_default_wp_filters();

        _e('<!--div class="alignleft actions">');
        _e('<input type="submit" class="button" name="cronycle_content_reset" id="cronycle_content_reset"
            value="Reset Content" onclick="jQuery.confirmResetContent(event);" />', $this->CronycleContent);
        _e('</div-->');
    }

    /**
     * Hides not required post status from status dropdown in bulk edit row.
     *
     * @since    2.0.3
     * @access   public
     */
    public function hide_status_into_bulk_inline_edit()
    {
        ?> 
            <script>
                jQuery( ".bulk-edit-cronycle_content select[name=_status] option[value=private]" ).remove();
                jQuery( ".bulk-edit-cronycle_content select[name=_status] option[value=pending]" ).remove();
                jQuery( ".bulk-edit-cronycle_content select[name=_status] option[value=featured]" ).remove();
            </script>
        <?php
    }

    /**
     * Modify edit link of Cronycle content in admin bar menu.
     *
     * @since    3.1.0
     * @access   public
     */
    public function edit_admin_bar_menu($wp_admin_bar)
    {
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != get_post_type()) {
            return;
        }

        // if 'Edit Cronycle Content' exists in admin menu bar edit its link
        $edit_node = $wp_admin_bar->get_node('edit');
        if ($edit_node != null) {
            // remove and add again
            $wp_admin_bar->remove_node('edit');
            $edit_node->href = get_edit_post_link() . "&add_to_wp_drafts=true";
            $wp_admin_bar->add_node($edit_node);
        }
    }

    /**
     * Hide post state of Cronycle content in list view.
     *
     * @since    3.1.0
     * @access   public
     */
    public function hide_post_state($post_states, $post)
    {
        // return if page other than Cronycle content is loaded
        if ($this->CronycleContent != $post->post_type) {
            return $post_states;
        }

        // return nothing
        return array();
    }

    /**
     * Hide unwanted post state quick links.
     *
     * @since    3.1.0
     * @access   public
     */
    public function hide_quick_links($views)
    {
        return array_intersect_key(array(
            'all'     => isset($views['all']) ? $views['all'] : '',
            'publish' => isset($views['publish']) ? $views['publish'] : '',
            'draft'   => isset($views['draft']) ? str_replace('Drafts', 'New', $views['draft']) : '',
            'added_to_drafts' => isset($views['added_to_drafts']) ?
                str_replace('Added to Drafts', 'Drafts', $views['added_to_drafts']) : '',
            'trash'   => isset($views['trash']) ? str_replace('Trash', 'Hidden', $views['trash']) : ''
        ), $views);
    }

    /**
     * Remove edit hyperlinks from post title in list view.
     *
     * @since    4.0.0
     * @access   public
     */
    public function remove_post_title_hyperlink($url, $post_id, $context)
    {
        // run script only if page is Cronycle content one
        if ($this->CronycleContent == get_post_type($post_id)) {
            echo '<script>if(typeof jQuery.removeTitleHyperlinks == "function") jQuery.removeTitleHyperlinks();</script>';
        }
        return $url;
    }

}
