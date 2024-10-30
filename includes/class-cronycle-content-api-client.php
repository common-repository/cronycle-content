<?php

/**
 * The Cronycle's API calling related functionality of the plugin.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 */

/**
 * The Cronycle's API calling related functionality of the plugin.
 *
 * Defines the required vars and functions for interacting with the Cronycle API.
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/includes
 * @author     Cronycle
 */
class CronycleContentAPIClient
{
    /**
     * Authentication token required to make calls to Cronycle's API from
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $auth_token    Unique code generated from Cronycle platform.
     */
    private $auth_token;

    /**
     * Base URL for all the API calls.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    CRON_BASE_API_URL    Base URL for all the API calls.
     */
    const CRON_BASE_API_URL = 'https://api.cronycle.com/';
    
    /**
     * API endpoint to fetch list of all the boards of any user.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    CRON_BOARDS_LIST_ENDPOINT    API endpoint for fetching boards list.
     */
    const CRON_BOARDS_LIST_ENDPOINT = 'wordpress/content/topic_boards';

    /**
     * API endpoint to fetch tiles of the specified board of any user.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    CRON_BOARD_TILES_ENDPOINT    API endpoint for fetching board tiles.
     */
    const CRON_BOARD_TILES_ENDPOINT = 'v10/wordpress/content/tiles';

    /**
     * API endpoint to fetch details of any user.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    CRON_USER_DETAILS_ENDPOINT    API endpoint for fetching user details.
     */
    const CRON_USER_DETAILS_ENDPOINT = 'wordpress/content/user';

    /**
     * API endpoint to fetch details of any influencer.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    CRON_INFLUENCER_LOOKUP_ENDPOINT    API endpoint for fetching influencer details.
     */
    const CRON_INFLUENCER_LOOKUP_ENDPOINT = 'wordpress/content/influencers_lookup';

    /**
     * Count of tiles to fetch from board tiles endpoint per API call.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    CRON_BASE_API_URL    Board tiles to fetch per API call.
     */
    const CRON_BOARD_TILES_PER_CALL = 40;

    /**
     * Twitter base URL.
     *
     * @since    3.0.0
     * @access   private
     * @var      string    TWITTER_URL    Twitter base URL.
     */
    const TWITTER_URL = 'https://twitter.com/';
    
    /**
     * Sets the authentication token.
     *
     * @since    1.0.0
     * @param    string     $auth_token     Authentication token.
     */
    public function __construct($auth_token)
    {
        $this->auth_token = $auth_token;
    }

    /**
     * Verifies the token by calling any API endpoint.
     *
     * @since    2.0.2
     * @access   public
     */
    public static function verify_token($auth_token)
    {
        $args = array(
            'headers' => array(
                'Content-type' => ' application/json',
                'Authorization' => ' Token auth_token=' . $auth_token
            )
        );
        $url = self::CRON_BASE_API_URL . self::CRON_USER_DETAILS_ENDPOINT;

        $response = wp_remote_get($url, $args);

        // Token is not valid for now
        if (wp_remote_retrieve_response_code($response) >= 400 &&
            wp_remote_retrieve_response_code($response) < 500) {
            if (update_option('cronycle_content_options', array()) === false) {
                CronycleContentLogger::log("Unable to update option for user details.");
            }
            return false;
        }
        return true;
    }

    /**
     * Get the list of boards any user has in Cronycle account.
     *
     * @since    1.0.0
     * @access   public
     */
    public function get_boards_list()
    {
        $args = array(
            'headers' => array(
                'Content-type' => ' application/json',
                'Authorization' => ' Token auth_token=' . $this->auth_token
            )
        );
        $url = self::CRON_BASE_API_URL . self::CRON_BOARDS_LIST_ENDPOINT;
        $response = wp_remote_get($url, $args);

        if (wp_remote_retrieve_response_code($response) == 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
        
            $boards_list = array();
            foreach ($body as $board) {
                $boards_list[] = array( 'name' => $board['name'], 'id' => $board['id'] );
            }

            return $boards_list;
        } else {
            CronycleContentLogger::log("Response status not OK for API call: " . $url
                . "\nRequest Headers: " . print_r($args, true) . "\nResponse:", $response);
        }

        return array();
    }

    /**
     * Finds which image from the assests is to use as lead image in banner items.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $assets     Assets received in the board tile data.
     * @return   string    Lead image url.
     */
    private function find_lead_image($assets)
    {
        foreach ($assets as $asset) {
            if ($asset['media_type'] == "image") {
                return $asset['url_original'];
            }
        }
        return "";
    }

    /**
     * Gets the tweeter url on the basis of tweeter screen name.
     *
     * @since    3.1.0
     * @access   private
     * @param    string    $tweeter_screen_name     Tweeter screen name.
     * @return   string    Tweeter url.
     */
    private function get_tweeter_url($tweeter_screen_name)
    {
        return self::TWITTER_URL . $tweeter_screen_name;
    }

    /**
     * Gets the tweet url on the basis of tweeter screen name and tweet ID.
     *
     * @since    3.0.0
     * @access   private
     * @param    string    $tweeter_screen_name     Tweeter screen name.
     * @param    string    $tweet_id                Tweet ID.
     * @return   string    Tweet url.
     */
    private function get_tweet_url($tweeter_screen_name, $tweet_id)
    {
        return self::TWITTER_URL . $tweeter_screen_name . '/status/' . $tweet_id;
    }

    /**
     * Gets the tweeter screen name from tweet url.
     *
     * @since    4.1.0
     * @access   private
     * @param    string    $tweeturl     Tweet url.
     * @return   string    Tweeter screen name.
     */
    private function get_tweeter_screen_name($tweet_url)
    {
        $parts = explode('/', $tweet_url);
        return count($parts) >= 4 ? $parts[3] : "";
    }

    /**
     * Parses the required data from board tiles data returned by API as response
     * into easily iteratable form and adds a tile id to each parsed item.
     *
     * In each parsed item following items are common:
     * - wp_tile_id: unique id assigned to each item for Wordpress,
     * - tile_id: id of board tile
     * - tile_type: type of the tile it is obtained from (link, note or conversation),
     * - user_tags: user tags added by user on board tile
     * - board_id: id of board to item belongs to
     * - board_name: name of board to item belongs to
     * - created_at: board tile creation time
     * - updated_at: last updated time of tile
     * - content_type: type to display to user for item (Article, Conversation, Tweet, Note or Story Arc)
     *
     * Following are fields that vary depending upon tile type
     * For link type tile (simple article),
     * - summary: user summary added for tile
     * - articles: array of article data (with size = 1) which contain fields such as
     *             article_type, title, description, url, published_date, publisher_name, image_url,
     *             user_title, user_primary_image_url
     *
     * For note type tile (story arc article),
     * - summary: note text in the story arc
     * - text_only: whether story arc just have text or have grouped content as well
     * - articles: array of article data which contain fields such as
     *             article_type, title, description, url, published_date, publisher_name, image_url
     *             in case of simple articles and,
     *             article_type, tweet_title_prefix, tweeter_name, tweet_text, tweet_url, tweet_time,
     *             tweeter_screen_name, replies_count, avatar_url
     *             in case of conversations
     * }
     *
     * For conversation type tile,
     * - articles: array of article data (with size = 1) which contain fields such as
     *             article_type, tweet_title_prefix, tweeter_name, tweet_text, tweet_url,
     *             tweet_time, tweeter_screen_name, replies_count, avatar_url
     *
     * For tweet type tile,
     * - summary: user summary added for tile
     * - articles: array of article data (with size = 1) which contain fields such as
     *             article_type, tweet_title_prefix, tweeter_name, tweet_text, tweet_url,
     *             tweet_time, tweeter_screen_name, avatar_url, image_url
     *
     * @since    1.0.0
     * @access   private
     * @param    array      $body            Body of response received from API.
     * @param    int        $wp_tile_id      Start of tile id to assign to each parsed item.
     * @param    bool       $require_avatar_url  Whether to fetch avatar url for conversation items or not
     * @return   array      Associative array with parsed items.
     */
    private function parse_board_tiles($body, $wp_tile_id, $require_avatar_url)
    {

        for ($i=0; $i < count($body['tiles']); $i++, $wp_tile_id++) {
            $board_tile = $body['tiles'][$i];

            $board_tiles[] = array(
                'wp_tile_id' => $wp_tile_id,
                'tile_id'    => $board_tile['id'],
                'tile_type'  => $board_tile['tile_type'],
                'user_tags'  => $board_tile['user_tags'],
                'board_id'   => $board_tile['board_id'],
                'summary'    => $board_tile['summary'],
                'board_name' => $board_tile['board_name'],
                'created_at' => $board_tile['created_at']
            );
            
            // for simple articles (tile_type == 'link')
            if ($board_tile['tile_type'] == "link") {
                $articles = array();
                $link = $board_tile['link']['link'];

                $articles[] = array(
                    'article_type'   => 'link',
                    'title'          => $link['title'],
                    'description'    => CronycleContentUtility::nlToBr($link['description']),
                    'url'            => $link['url'],
                    'url_host'       => isset($link['url_host']) && !empty(trim($link['url_host'])) &&
                        CronycleContentUtility::is_valid_url(trim($link['url_host'])) ? trim($link['url_host']) : '',
                    'published_date' => $link['published_date'],
                    'publisher_name' => trim($link['publisher_name']) ?? '',
                    'primary_author' => trim($link['primary_author']) ?? '',
                    'author_url'     => isset($link['author_url']) && !empty(trim($link['author_url'])) &&
                        CronycleContentUtility::is_valid_url(trim($link['author_url'])) ? trim($link['author_url']) : '',
                    'image_url'      => $this->find_lead_image($link['assets'])
                );

                $board_tiles[$i]['updated_at']   = $board_tile['link']['updated_at'];
                $board_tiles[$i]['summary']      = CronycleContentUtility::nlToBr($board_tile['link']['user_summary']);
                $board_tiles[$i]['user_title']   = $board_tile['link']['user_title'] ?? '';
                $board_tiles[$i]['user_primary_image_url'] = $board_tile['link']['user_primary_image_url'] ?? '';
                $board_tiles[$i]['articles']     = $articles;
                $board_tiles[$i]['content_type'] = "Article";
            }
            // for conversations (tile_type == 'conversation')
            elseif ($board_tile['tile_type'] == "conversation") {
                $articles = array();
                $conversation = $board_tile['conversation']['conversation'];
                
                $articles[] = array(
                    'article_type'        => 'conversation',
                    'tweet_title_prefix'  => "started by",
                    'tweeter_name'        => $conversation['tweeter_name'],
                    'tweeter_url'         => $this->get_tweeter_url($conversation['tweeter_screen_name']),
                    'tweet_text'          => CronycleContentUtility::nlToBr(CronycleContentUtility::tweet_text_to_html($conversation['tweet_text'])),
                    'tweet_url'           => $this->get_tweet_url($conversation['tweeter_screen_name'], $conversation['tweet_id']),
                    'tweet_time'          => $conversation['tweet_time'],
                    'tweeter_screen_name' => CronycleContentUtility::tweet_text_to_html("@".$conversation['tweeter_screen_name']),
                    'replies_count'       => $conversation['replies_count'],
                    'avatar_url'          => $require_avatar_url ? $this->get_influencer_avatar($conversation['tweeter_id']) : ''
                );

                $board_tiles[$i]['updated_at']   = $board_tile['conversation']['updated_at'];
                $board_tiles[$i]['articles']     = $articles;
                $board_tiles[$i]['content_type'] = "Conversation";
            }
            // for tweets (tile_type == 'tweet')
            elseif ($board_tile['tile_type'] == "tweet") {
                $articles = array();
                $tweet = $board_tile['tweet']['tweet'];
                
                $tweeter_screen_name = $this->get_tweeter_screen_name($tweet['tweet_url']);
                $articles[] = array(
                    'article_type'        => 'tweet',
                    'tweet_title_prefix'  => "by",
                    'tweeter_name'        => $tweet['tweeter_handle'],
                    'tweeter_url'         => $this->get_tweeter_url($tweeter_screen_name),
                    'tweet_text'          => CronycleContentUtility::nlToBr(CronycleContentUtility::tweet_text_to_html($tweet['tweet_text'])),
                    'tweet_url'           => $tweet['tweet_url'],
                    'tweet_time'          => $tweet['published_at'],
                    'tweeter_screen_name' => CronycleContentUtility::tweet_text_to_html("@".$tweeter_screen_name),
                    'avatar_url'          => $tweet['tweeter_avatar_url'],
                    'image_url'           => $tweet['asset_url']
                );

                $board_tiles[$i]['updated_at']   = $board_tile['tweet']['updated_at'];
                $board_tiles[$i]['summary']      = CronycleContentUtility::nlToBr($board_tile['tweet']['user_summary']);
                $board_tiles[$i]['articles']     = $articles;
                $board_tiles[$i]['content_type'] = "Tweet";
            }
            // for story arcs (tile_type == 'note')
            elseif ($board_tile['tile_type'] == "note") {
                $articles = array();

                // for simple articles
                if (isset($board_tile['note']['links']) && !empty($board_tile['note']['links'])) {
                    foreach ($board_tile['note']['links'] as $link) {
                        $articles[] = array(
                            'article_type'   => 'link',
                            'title'          =>  $link['user_title']?  $link['user_title']: $link['title'],
                            'description'    => CronycleContentUtility::nlToBr($link['description']),
                            'url'            => $link['url'],
                            'url_host'       => isset($link['url_host']) && !empty(trim($link['url_host'])) &&
                                CronycleContentUtility::is_valid_url(trim($link['url_host'])) ? trim($link['url_host']) : '',
                            'published_date' => $link['published_date'],
                            'publisher_name' => trim($link['publisher_name']) ?? '',
                            'primary_author' => trim($link['primary_author']) ?? '',
                            'author_url'     => isset($link['author_url']) && !empty(trim($link['author_url'])) &&
                                CronycleContentUtility::is_valid_url(trim($link['author_url'])) ? trim($link['author_url']) : '',
                            'image_url'      => isset($link['user_primary_image_url']) && !empty(trim($link['user_primary_image_url'])) ? $link['user_primary_image_url'] : $this->find_lead_image($link['assets']),
                            'position'       => isset($link['position']) ? $link['position'] : 0
                        );
                    }
                }
                // for conversations
                if (isset($board_tile['note']['conversations'])) {
                    foreach ($board_tile['note']['conversations'] as $conversation) {
                        $articles[] = array(
                            'article_type'        => 'conversation',
                            'tweet_title_prefix'  => "started by",
                            'tweeter_name'        => $conversation['tweeter_name'],
                            'tweeter_url'         => $this->get_tweeter_url($conversation['tweeter_screen_name']),
                            'tweet_text'          => CronycleContentUtility::nlToBr(CronycleContentUtility::tweet_text_to_html($conversation['tweet_text'])),
                            'tweet_url'           => $this->get_tweet_url($conversation['tweeter_screen_name'], $conversation['tweet_id']),
                            'tweet_time'          => $conversation['tweet_time'],
                            'tweeter_screen_name' => CronycleContentUtility::tweet_text_to_html("@".$conversation['tweeter_screen_name']),
                            'replies_count'       => $conversation['replies_count'],
                            'avatar_url'          => $require_avatar_url ? $this->get_influencer_avatar($conversation['tweeter_id']) : '',
                            'position'            => isset($conversation['position']) ? $conversation['position'] : 0

                        );
                    }
                }
                // for tweets
                if (isset($board_tile['note']['tweets'])) {
                    foreach ($board_tile['note']['tweets'] as $tweet) {
                        $tweeter_screen_name = $this->get_tweeter_screen_name($tweet['tweet_url']);
                        $articles[] = array(
                            'article_type'        => 'tweet',
                            'tweet_title_prefix'  => "by",
                            'tweeter_name'        => $tweet['tweeter_handle'],
                            'tweeter_url'         => $this->get_tweeter_url($tweeter_screen_name),
                            'tweet_text'          => CronycleContentUtility::nlToBr(CronycleContentUtility::tweet_text_to_html($tweet['tweet_text'])),
                            'tweet_url'           => $tweet['tweet_url'],
                            'tweet_time'          => $tweet['published_at'],
                            'tweeter_screen_name' => CronycleContentUtility::tweet_text_to_html("@".$tweeter_screen_name),
                            'avatar_url'          => $tweet['tweeter_avatar_url'],
                            'image_url'           => $tweet['asset_url'],
                            'position'            => isset($tweet['position']) ? $tweet['position'] : 0

                        );
                    }
                }
                // for notes
                if (isset($board_tile['note']['board_documents'])) {
                    
                    foreach ($board_tile['note']['board_documents'] as $board_document) {
                        $articles[] = array(
                            'article_type'        => $board_document['content_sub_type'],
                            'title'               => $board_document['title'],
                            'tweet_text'          => CronycleContentUtility::nlToBr($board_document['content']),
                            'position'            => isset($board_document['position']) ? $board_document['position'] : 0
                        );
                    }
                }

                usort($articles, function ($a, $b) {
                    return $a['position'] - $b['position'];
                });

                $board_tiles[$i]['updated_at']   = $board_tile['note']['updated_at'];
                $board_tiles[$i]['summary']      = $board_tile['note']['summary'] ? $board_tile['note']['summary'] : CronycleContentUtility::nlToBr($board_tile['note']['note']);

                if ($board_tile['note']['text_only'] === true && $board_tile['note']['rich_text_note'] && $board_tile['note']['rich_text_note'] !== '') {
                    $board_tiles[$i]['summary']  = $board_tile['note']['rich_text_note'];
                }
                if (!isset($board_tiles[$i]['summary'])) {
                }
                $board_tiles[$i]['text_only']    = $board_tile['note']['text_only'];
                $board_tiles[$i]['articles']     = $articles;
                $board_tiles[$i]['content_type'] = "Story Arc";
                if ($board_tile['note']['text_only'] === true) {
                    $board_tiles[$i]['content_type'] = "Note";
                }
                if ($board_tile['note']['title']) {
                    $board_tiles[$i]['title'] = $board_tile['note']['title'];
                }
                // $fp = fopen(plugin_dir_path(__FILE__) . 'cronycle_content_debug_log.txt', 'a');
                // fwrite($fp, print_r($board_tiles, true));
                // fclose($fp);
            }
        }

        return $board_tiles;
    }

    /**
     * Get board tiles data for the specified board.
     *
     * @since    1.0.0
     * @access   public
     * @param    array      $board_id        Board id whose data is to fetched.
     * @param    int        $start           Starting tile id of current fetch.
     * @return   array      A parsed data which can be easily used at banner template side.
     */
    public function get_board_tiles(
        $board_id = -1,
        $start = 0,
        $count = self::CRON_BOARD_TILES_PER_CALL,
        $pagination = true,
        $ignore_paging_check = false,
        $require_avatar_url = true,
        $max_timestamp = null,
        $min_timestamp = null
    ) {
        if ($pagination && ($start % $count != 0 && !$ignore_paging_check)) {
            return "";
        }

        $args = array(
            'headers' => array(
                'Content-type' => ' application/json',
                'Authorization' => ' Token auth_token=' . $this->auth_token
            ),
            'timeout' => 30000
        );
        $params = array();
        if (isset($board_id) && $board_id != -1) {
            $params['board_id'] = $board_id;
        }
        if ($pagination) {
            $params['start'] = $start;
            $params['count'] = $count;
        }
        if (isset($max_timestamp) && !empty($max_timestamp)) {
            $params['max_timestamp'] = $max_timestamp;
        }
        if (isset($min_timestamp) && !empty($min_timestamp)) {
            $params['min_timestamp'] = $min_timestamp;
        }

        $url = self::CRON_BASE_API_URL . self::CRON_BOARD_TILES_ENDPOINT . '?' . http_build_query($params);

        $response = wp_remote_get($url, $args);

        if (wp_remote_retrieve_response_code($response) == 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (count($body) > 0) {
                return $this->parse_board_tiles($body, $start, $require_avatar_url);
            }
        } else {
            CronycleContentLogger::log("Response status not OK for API call: " . $url
                . "\nRequest Headers: " . print_r($args, true) . "\nResponse:", $response);
        }
        return "";
    }

    /**
     * Get the user details and checks whether user has pro account or not.
     *
     * @since    1.0.0
     * @access   public
     * @return   boolean      True if user is pro user else false.
     */
    public function is_pro_user()
    {
        $options = get_option('cronycle_content_options');
        $user_type = '';
        if (isset($options['user_type'])) {
            $user_type = $options['user_type'];
        } else {
            $args = array(
                'headers' => array(
                    'Content-type' => ' application/json',
                    'Authorization' => ' Token auth_token=' . $this->auth_token
                )
            );
            $url = self::CRON_BASE_API_URL . self::CRON_USER_DETAILS_ENDPOINT;

            $response = wp_remote_get($url, $args);

            if (wp_remote_retrieve_response_code($response) == 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);

                $user_type = $body['user_type'];

                $options['user_type'] = $user_type;
                if (update_option('cronycle_content_options', $options) === false) {
                    CronycleContentLogger::log("Unable to update option for user type.");
                }
            } else {
                CronycleContentLogger::log("Response status not OK for API call: " . $url
                    . "\nRequest Headers: " . print_r($args, true) . "\nResponse:", $response);
            }
        }
        return $user_type != "free";
    }

    /**
     * Get and return the required user details such as full name and avatar url.
     *
     * @since    1.0.0
     * @access   public
     * @return   array      Associative array with full name and avatar of user.
     */
    public function get_user_details()
    {
        $options = get_option('cronycle_content_options');
        $user_details = null;
        if (isset($options['full_name']) && isset($options['avatar']) && isset($options['user_type'])) {
            $user_details = array(
                'full_name' => $options['full_name'],
                'avatar' => $options['avatar'],
                'user_type' => $options['user_type'],
            );
        } else {
            $args = array(
                'headers' => array(
                    'Content-type' => ' application/json',
                    'Authorization' => ' Token auth_token=' . $this->auth_token
                )
            );
            $url = self::CRON_BASE_API_URL . self::CRON_USER_DETAILS_ENDPOINT;

            $response = wp_remote_get($url, $args);

            if (wp_remote_retrieve_response_code($response) == 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $user_details = array(
                    'full_name' => $body['full_name'],
                    'avatar' => $body['avatar']['small'],
                    'user_type' => $body['user_type'],
                );

                $options['full_name'] = $body['full_name'];
                $options['avatar'] = $body['avatar']['small'];
                $options['user_type'] = $body['user_type'];
                if (update_option('cronycle_content_options', $options) === false) {
                    CronycleContentLogger::log("Unable to update option for user details.");
                }
            } else {
                CronycleContentLogger::log("Response status not OK for API call: " . $url
                . "\nRequest Headers: " . print_r($args, true) . "\nResponse:", $response);
            }
        }
        
        return $user_details;
    }

    /**
     * Gets the avatar of the specified influencer.
     *
     * @since    1.0.0
     * @access   public
     * @param    int     $influencer_id    Influencer id whose details to be lookup.
     * @return   string  Influencer avatar url.
     */
    private function get_influencer_avatar($influencer_id)
    {
        $args = array(
            'headers' => array(
                'Content-type' => ' application/json',
                'Authorization' => ' Token auth_token=' . $this->auth_token
            )
        );
        $params = array(
            'query' => $influencer_id
        );
        $url = self::CRON_BASE_API_URL . self::CRON_INFLUENCER_LOOKUP_ENDPOINT . '?' . http_build_query($params);

        $response = wp_remote_get($url, $args);

        if (wp_remote_retrieve_response_code($response) == 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (count($body['top_influencers']) > 0 && isset($body['top_influencers'][0]['twitter_profile_image_url'])) {
                return $body['top_influencers'][0]['twitter_profile_image_url'];
            }
        } else {
            CronycleContentLogger::log("Response status not OK for API call: " . $url
                . "\nRequest Headers: " . print_r($args, true) . "\nResponse:", $response);
        }
        return "";
    }

}
