<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the admin-specific functionalities of the plugin mainly
 * for settings page. Also contain objects to class having
 * functionalities related to banner and draft post segments.
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin
 * @author     Cronycle
 */
class CronycleContentAdmin
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
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    2.0.0
     * @access   private
     * @var      CronycleContentLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    private $loader;

    /**
     * The banner class responsible for functionalities related to banner content of this plugin.
     *
     * @since    2.0.0
     * @access   public
     * @var      CronycleContentAdminBanner    $plugin_banner    Maintains banner content related functionalities.
     */
    public $plugin_banner;

    /**
     * The draft post class responsible for functionalities related to draft post content of this plugin.
     *
     * @since    2.0.0
     * @access   public
     * @var      CronycleContentAdminDraftPost    $plugin_draft_post    Maintains draft post content related functionalities.
     */
    public $plugin_draft_post;

    /**
     * Initialize the class and set its properties.
     *
     * @since   1.0.0
     * @param   string                  $CronycleContent    The name of this plugin.
     * @param   string                  $version    	    The version of this plugin.
     * @param   CronycleContentLoader   $loader    		    The hook loader of this plugin.
     */
    public function __construct($CronycleContent, $version, $loader)
    {
        $this->CronycleContent = $CronycleContent;
        $this->version = $version;
        $this->loader = $loader;
        $this->plugin_banner = new CronycleContentAdminBanner($CronycleContent, $version, $loader);
        $this->plugin_draft_post = new CronycleContentAdminDraftPost($CronycleContent, $version, $loader);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @access   public
     */
    public function enqueue_styles($hook)
    {
        global $cronycle_content_settings_page;
        if ($cronycle_content_settings_page == $hook) {
            wp_enqueue_style(
                $this->CronycleContent,
                plugin_dir_url(__FILE__) . 'css/cronycle-content-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
        wp_enqueue_style(
            $this->CronycleContent . '-font',
            plugin_dir_url(__FILE__) . 'css/cronycle-content-font.css',
            array(),
            $this->version,
            'all'
        );

        $this->plugin_banner->enqueue_styles($hook);
        $this->plugin_draft_post->enqueue_styles($hook);
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @access   public
     */
    public function enqueue_scripts($hook)
    {
        global $cronycle_content_settings_page;
        // if ($cronycle_content_settings_page == $hook)
        {
            wp_enqueue_script(
                $this->CronycleContent,
                plugin_dir_url(__FILE__) . 'js/cronycle-content-admin.js',
                array( 'jquery' ),
                $this->version,
                false
            );
        }

        wp_localize_script($this->CronycleContent, 'WPURLS', array( 'plugin_url' => plugin_dir_url(__FILE__) ));

        $this->plugin_banner->enqueue_scripts($hook);
        $this->plugin_draft_post->enqueue_scripts($hook);
    }

    /**
     * Add settings and other action links in the plugin description on plugins page.
     *
     * @since    1.1.2
     * @access   public
     */
    public function add_action_links($links)
    {
        $plugin_page = plugin_basename(__FILE__);
        $plugin_links = array('<a href="' . admin_url("options-general.php?page=$plugin_page") . '">Settings</a>',);
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Add new menu option and page to setting pages.
     *
     * @since    1.0.0
     * @access   public
     */
    public function plugin_menu()
    {
        global $cronycle_content_settings_page;
        $cronycle_content_settings_page = add_options_page('Cronycle Content', 'Cronycle Content', 'manage_options', __FILE__, array( $this, 'plugin_options_page' ));
    }

    /**
     * Creates the option page from the template.
     *
     * @since    1.0.0
     * @access   public
     */
    public function plugin_options_page()
    {
        $options = get_option('cronycle_content_options');
        if (isset($options['auth_token']) && !empty($options['auth_token'])) {
            $api_client = new CronycleContentAPIClient($options['auth_token']);
            $user_details = $api_client->get_user_details();
        }

        $plugin_page = plugin_basename(__FILE__);
        require_once("partials/template-settings-page.php");
    }

    /**
     * Add admin screen actions to perform when plugin instantiate.
     *
     * @since    1.0.0
     * @access   public
     */
    public function plugin_init()
    {
        register_setting(
            'cronycle_content_options',
            'cronycle_content_options',
            array( $this, 'plugin_options_validate' )
        );
        add_settings_section(
            'cronycle_content_settings_main',
            '',
            array( $this, 'setting_section_text' ),
            $this->CronycleContent
        );
        add_settings_field(
            'cronycle_content_auth_token',
            '',
            array( $this, 'setting_field_auth_token' ),
            $this->CronycleContent,
            'cronycle_content_settings_main'
        );

        $this->add_draft_post_settings();
        $this->add_banner_settings();
        $this->add_account_settings();
    }

    /**
     * Define the settings section text.
     *
     * @since    1.0.0
     * @access   public
     */
    public function setting_section_text()
    {
        // _e('<p>In the <a href="https://app.cronycle.com/account/profile" target="_blank">Cronycle webapp</a>, '
        //  	.'copy your token from Profile &gt; Integrations &gt; Wordpress</p>');
    }

    /**
     * Define the settings field for auth token.
     *
     * @since    1.0.0
     * @access   public
     */
    public function setting_field_auth_token()
    {
        // _e('<input id="cronycle_content_auth_token" name="cronycle_content_options[auth_token]"
        // size="40" type="text" placeholder="Paste token here">', $this->CronycleContent);
    }

    /**
     * Validates the input on plugin setting page.
     *
     * @since    1.0.0
     * @access   public
     * @var      string     $input      Input for auth token entered by the user.
     * @return   string     Sanitized output.
     */
    public function plugin_options_validate($input)
    {
        /* NOTE:
         * Not modifying and returing original $input as it is untrusted data,
         * instead creating $newinput that should be trusted data.
         */

        $auth_token = trim($input['auth_token']);

        $newinput = get_option('cronycle_content_options');

        // handling token reset case during unlinking Cronycle account
        if (empty($input)) {
            return $input;
        }
        if (empty($auth_token)) {
            $newinput['auth_token'] = $auth_token;
            return $newinput;
        }

        if (preg_match('/^[a-z0-9]{16}$/i', $auth_token)) {
            if (CronycleContentAPIClient::verify_token($auth_token)) {
                $newinput['auth_token'] = $auth_token;
                if (isset($input['full_name'])) {
                    $newinput['full_name'] = sanitize_text_field($input['full_name']);
                }
                if (isset($input['avatar'])) {
                    $newinput['avatar'] = esc_url($input['avatar']);
                }
                if (isset($input['user_type'])) {
                    $newinput['user_type'] = sanitize_text_field($input['user_type']);
                }

                add_settings_error($this->CronycleContent, 'token-valid', 'Token saved successfully. 
                    You can now add Cronycle content onto your website.', 'updated');
                CronycleContentLogger::log("Cronycle account linked successfully.");
            } else {
                CronycleContentLogger::log("Authentication token is invalid.");
                add_settings_error($this->CronycleContent, 'token-invalid', 'Invalid Token.', 'error');
            }
        } else {
            CronycleContentLogger::log("Authentication token is not of valid format.");
            add_settings_error($this->CronycleContent, 'token-invalid', 'Invalid Token.', 'error');
        }
        return $newinput;
    }

    /**
     * Add account settings options on the admin screen.
     *
     * @since    1.1.2
     * @access   private
     */
    private function add_account_settings()
    {
        add_settings_section(
            'cronycle_content_account_settings',
            '',
            array( $this, 'setting_account_section_text' ),
            $this->CronycleContent . '-account'
        );
        add_settings_field(
            'cronycle_content_account_user',
            'Connected Account',
            array( $this, 'setting_account_field_user' ),
            $this->CronycleContent . '-account',
            'cronycle_content_account_settings'
        );
        add_settings_field(
            'cronycle_content_account_disconnect_button',
            '',
            array( $this, 'setting_account_field_disconnect_button' ),
            $this->CronycleContent . '-account',
            'cronycle_content_account_settings'
        );
    }

    /**
     * Define the account settings section text.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_account_section_text()
    {
        // _e('<p>Account settings section</p>', $this->CronycleContent);
    }

    /**
     * Define the settings field for user avatar display in account settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_account_field_user()
    {
        $options = get_option('cronycle_content_options');
        if (isset($options['auth_token']) && !empty($options['auth_token'])) {
            $api_client = new CronycleContentAPIClient($options['auth_token']);
            $user_details = $api_client->get_user_details();

            if (isset($user_details)) {
                _e('<div class="cronycle-settings-user">
                    <div class="cronycle-settings-user-avatar"><img src=' . $user_details['avatar'] . '></div>
                    <div class="cronycle-settings-user-name"><p>' . $user_details['full_name'] . '</p></div>
                    <div class="break"></div></div>', $this->CronycleContent);
            }
        }
    }

    /**
     * Define the settings field for disconnect button in account settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_account_field_disconnect_button()
    {
        _e('<input id="cronycle_content_account_disconnect_button" class="button button-primary cronycle-settings-disconnect-button" 
            type="button" value="Disconnect Cronycle Account" onclick="jQuery.unlink()" />', $this->CronycleContent);
    }

    /**
     * Add banner settings options on the admin screen.
     *
     * @since    1.1.2
     * @access   private
     */
    private function add_banner_settings()
    {
        register_setting(
            'cronycle_content_banner_options',
            'cronycle_content_banner_options',
            array( $this, 'plugin_banner_options_validate' )
        );
        add_settings_section(
            'cronycle_content_banner_settings',
            '',
            array( $this, 'setting_banner_section_text' ),
            $this->CronycleContent . '-banner'
        );
        if (!empty($this->plugin_banner->get_boards_list(true))) {
            add_settings_field(
                'cronycle_content_banner_board',
                'Board Name',
                array( $this, 'setting_banner_field_board' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
            add_settings_field(
                'cronycle_content_banner_include_image',
                'Include Images',
                array( $this, 'setting_banner_field_include_image' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
            add_settings_field(
                'cronycle_content_banner_width',
                'Width',
                array( $this, 'setting_banner_field_width' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
            add_settings_field(
                'cronycle_content_banner_position',
                'Position',
                array( $this, 'setting_banner_field_position' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
            add_settings_field(
                'cronycle_content_banner_generate_button',
                '',
                array( $this, 'setting_banner_field_generate_button' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
            add_settings_field(
                'cronycle_content_banner_shortcode',
                'Shortcode',
                array( $this, 'setting_banner_field_shortcode' ),
                $this->CronycleContent . '-banner',
                'cronycle_content_banner_settings'
            );
        }
    }

    /**
     * Define the banner settings section text.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_section_text()
    {
        _e('<p>Include a newsfeed banner on your website, with content from one of your boards</p>', $this->CronycleContent);
        _e('<img src="' . plugin_dir_url(__FILE__) . '../images/banner_preview.png" width="800px">', $this->CronycleContent);
        _e('<h2>How to set it up</h2>', $this->CronycleContent);
        _e('<p>Generate shortcode for banner from here and paste it into your page content</p>', $this->CronycleContent);
    }

    /**
     * Define the settings field for board in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_board()
    {
        $board_list = $this->plugin_banner->get_boards_list(true);
        $options = "";
        foreach ($board_list as $board_item) {
            $options .= '<option value="' . $board_item['id'] . '">' . $board_item['name'] . '</option>';
        }
        _e('<select id="cronycle_content_banner_board" name="cronycle_content_banner_options[board]" onchange="jQuery.updateFormControls()">
            <option value="" disabled selected> Select a board </option>' . $options . '</select>', $this->CronycleContent);
    }

    /**
     * Define the settings field for include image in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_include_image()
    {
        _e('<input id="cronycle_content_banner_include_image" name="cronycle_content_banner_options[include_image]" 
            type="checkbox" checked disabled />', $this->CronycleContent);
    }

    /**
     * Define the settings field for banner width in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_width()
    {
        _e('<input id="cronycle_content_banner_width" name="cronycle_content_banner_options[width]" class="small-text"
            type="number" min="0" max="100" step="1" value="100" disabled 
            onchange="jQuery.updateFormControls()" /> <span class="help-text disabled">in percentage (%)</span>', $this->CronycleContent);
    }

    /**
     * Define the settings field for banner position in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_position()
    {
        _e('<label id="cronycle_content_banner_position">
            <input name="cronycle_content_banner_options[position]" 
            type="radio" value="left" disabled /> <span class="help-text disabled">Left</span>
            <input name="cronycle_content_banner_options[position]" 
            type="radio" value="center" disabled /> <span class="help-text disabled">Center</span>
            <input name="cronycle_content_banner_options[position]" 
            type="radio" value="right" disabled /> <span class="help-text disabled">Right</span>
            </label>', $this->CronycleContent);
    }

    /**
     * Define the settings field for banner shortcode generate button in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_generate_button()
    {
        _e('<input id="cronycle_content_banner_generate_button" class="button button-primary" 
            type="button" value="Generate Shortcode" onclick="jQuery.generateShortcode()" />', $this->CronycleContent);
    }

    /**
     * Define the settings field for banner shortcode in banner settings section.
     *
     * @since    1.1.2
     * @access   public
     */
    public function setting_banner_field_shortcode()
    {
        _e('<textarea id="cronycle_content_banner_shortcode" rows="5" cols="50" readonly 
            onclick="jQuery.copyShortcode()"></textarea><br><span class="help-text"></span>', $this->CronycleContent);
    }

    /**
     * Validates the input for banner options on plugin setting page.
     *
     * @since    1.1.2
     * @access   public
     * @var      string     $input      Input for banner options.
     * @return   string     Sanitized output.
     */
    public function plugin_banner_options_validate($input)
    {
        return $input;
    }

    /**
     * Add draft post settings options on the admin screen.
     *
     * @since    2.0.0
     * @access   private
     */
    private function add_draft_post_settings()
    {
        register_setting(
            'cronycle_content_draft_post_options',
            'cronycle_content_draft_post_options',
            array( $this, 'plugin_draft_options_options_validate' )
        );
        add_settings_section(
            'cronycle_content_draft_post_settings',
            'Draft Post Settings',
            array( $this, 'setting_draft_post_section_text' ),
            $this->CronycleContent . '-draft-post'
        );
        add_settings_field(
            'cronycle_content_draft_post_categories',
            'Default Board Category',
            array( $this, 'setting_draft_post_field_categories' ),
            $this->CronycleContent . '-draft-post',
            'cronycle_content_draft_post_settings'
        );
        add_settings_field(
            'cronycle_content_draft_post_include_tag',
            'Include Tags',
            array( $this, 'setting_draft_post_field_include_tag' ),
            $this->CronycleContent . '-draft-post',
            'cronycle_content_draft_post_settings'
        );
    }

    /**
     * Define the draft post settings section text.
     *
     * @since    2.0.0
     * @access   public
     */
    public function setting_draft_post_section_text()
    {
        _e(
            '<p>Allows you to pick board items and add them as blog posts, with editable summaries followed by content in quotes</p>',
            $this->CronycleContent
        );
    }

    /**
     * Define the settings field for default categories in draft post settings section.
     *
     * @since    2.0.0
     * @access   public
     */
    public function setting_draft_post_field_categories()
    {
        $default_categories = array();
        $parent_categories = array();
        $draft_post_options = get_option('cronycle_content_draft_post_options');
        if (isset($draft_post_options) && isset($draft_post_options['default_categories'])) {
            $default_categories = $draft_post_options['default_categories'];
        }

        $args = array("hide_empty" => 0,
        "type"      => "post",
        "orderby"   => "name",
        "order"     => "ASC");
        $categories = get_categories($args);
        
        foreach ($categories as $category) {
            if ($category->category_parent == 0) {
                array_push($parent_categories, $category);
            }
        }

        foreach ($categories as $category) {
            if ($category->category_parent !== 0) {
                foreach ($parent_categories as $parent_category) {
                    if ($parent_category->term_id === $category->category_parent) {
                        $category->category_parent_name = $parent_category->name;
                    }
                }
            }
        }

        $board_list = $this->plugin_banner->get_boards_list(true);
        $options = "";
        foreach ($board_list as $board_item) {
            $options .= '<option value="' . $board_item['id'] . '">' . $board_item['name'] . '</option>';
        }
        _e('<div class="cronycle_content_draft_post_categories_wrapper">');
        _e('<select id="cronycle_content_draft_post_boards" name="cronycle_content_draft_post_options[board]" size="10" 
            onchange="jQuery.changeCategoriesContainers()">' . $options . '</select>', $this->CronycleContent);
        foreach ($board_list as $board_item) {
            _e('<div class="cronycle_content_draft_post_categories_container" style="display:none"
                id="cronycle_content_draft_post_categories_' . $board_item['id'] . '">', $this->CronycleContent);

                foreach($parent_categories as $parent_category) {
                    if (in_array($category->name, $default_categories[$board_item['id']]) ) {
                        array_push($default_categories[$board_item['id']], $parent_category->term_id);
                    }
                    $checked = isset($default_categories[$board_item['id']]) &&
                        in_array($parent_category->term_id, $default_categories[$board_item['id']]) ? "checked" : "";
    
                    _e('<input type="checkbox" id="cronycle_content_draft_post_category_' . $parent_category->name . '" 
                    name="cronycle_content_draft_post_options[default_categories][' . $board_item['id'] . '][]" value="' . $parent_category->term_id . '" ' . $checked . '
                    onchange="jQuery.updateBoardOptionText(this)" />
                    <label for="cronycle_content_draft_post_category_"' . $parent_category->name . ' class="help-text">' . $parent_category->name .'</label><br>', $this->CronycleContent);
                    foreach ($categories as $category) {
                        // can remove it after sometime, fix to mark category checked on basis of id instead of name. 
    
                        if ($category->category_parent === $parent_category->term_id) {
    
                            if (in_array($category->name, $default_categories[$board_item['id']]) ) {
                                array_push($default_categories[$board_item['id']], $category->term_id);
                            }
                            $checked = isset($default_categories[$board_item['id']]) &&
                                in_array($category->term_id, $default_categories[$board_item['id']]) ? "checked" : "";
            
                            _e('<input style="margin-left: 12px" type="checkbox" id="cronycle_content_draft_post_category_' . $category->name . '" 
                                name="cronycle_content_draft_post_options[default_categories][' . $board_item['id'] . '][]" value="' . $category->term_id . '" ' . $checked . '
                                onchange="jQuery.updateBoardOptionText(this)" />
                                <label for="cronycle_content_draft_post_category_"' . $category->name . ' class="help-text">' . $category->name . '</label><br>', $this->CronycleContent);
                        }
                    }
                }
            _e('</div>');
        }
        _e('</div>');
    }

    /**
     * Define the settings field for include tags in draft post settings section.
     *
     * @since    2.0.0
     * @access   public
     */
    public function setting_draft_post_field_include_tag()
    {
        $checked_yes = '';
        $checked_no = 'checked';
        $options = get_option('cronycle_content_draft_post_options');
        if (isset($options) && isset($options['include_tag']) && $options['include_tag'] == 'yes') {
            $checked_yes = 'checked';
            $checked_no = '';
        }

        _e('<label id="cronycle_content_draft_post_include_tag">
            <input name="cronycle_content_draft_post_options[include_tag]" 
            type="radio" value="yes" ' . $checked_yes . ' required /><span class="help-text">Yes</span>
            <input name="cronycle_content_draft_post_options[include_tag]" 
            type="radio" value="no" ' . $checked_no . ' required /><span class="help-text">No</span></label>
            <p class="description">Tags from your board, and those associated with the original content</p>', $this->CronycleContent);
    }

    /**
     * Validates the input for draft post options on plugin setting page.
     *
     * @since    2.0.0
     * @access   public
     * @var      string     $input      Input for draft post options.
     * @return   string     Sanitized output.
     */
    public function plugin_draft_options_options_validate($input)
    {
        $newinput = get_option('cronycle_content_draft_post_options');
        if (!isset($input['include_tag'])) {
            add_settings_error($this->CronycleContent, 'draft-post-option-invalid', 'Failed to update settings.', 'error');
            return $newinput;
        }

        $newinput['include_tag'] = strtolower(sanitize_text_field($input['include_tag']));
        if (isset($input['default_categories'])) {
            $newinput['default_categories'] = $input['default_categories'];
        } else {
            unset($newinput['default_categories']);
        }

        if (isset($input['last_fetch_timestamp'])) {
            $newinput['last_fetch_timestamp'] = $input['last_fetch_timestamp'];
        }
        return $newinput;
    }

    /**
     * Remove the saved token from settings options.
     *
     * @since    1.1.0
     * @access   public
     */
    public function unlink_cronycle_account()
    {
        $options = get_option('cronycle_content_options');
        if (isset($options['auth_token']) && !empty($options['auth_token'])) {
            // $options['auth_token'] = "";
            if (update_option('cronycle_content_options', array())) {
                CronycleContentAdminDraftPost::delete_content($this->CronycleContent);
                CronycleContentLogger::log("Cronycle account unlinked successfully.");
                _e("Account unlinked.", $this->CronycleContent);
            } else {
                CronycleContentLogger::log("Unable to unlink account.");
                _e("Unable to unlink account.", $this->CronycleContent);
            }
        }

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Resets the log.
     *
     * @since    1.1.2
     * @access   public
     */
    public function reset_logs()
    {
        CronycleContentLogger::reset();
        _e("Logs resetted.");

        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
