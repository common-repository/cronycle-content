<?php
/**
 * Template for Cronycle Content settings page.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/admin/partials
 */

 // If this file is called directly, abort.
if (! defined('WPINC') || ! is_admin()) {
    wp_die('You do not have sufficient permissions to access this page.');
}

if (!current_user_can('manage_options')) {
    CronycleContentLogger::log("Current user can't manage options.");
    wp_die('You do not have sufficient permissions to access this page.');
}

?>

<div id="cronycle_content_settings" class="wrap">
  <h1 class="cronycle-settings-page-title">Cronycle Content
	 <p>By</p><img class="cronycle-settings-logo" src= <?php _e(plugin_dir_url(__FILE__) . "../../images/logo-full.png"); ?> >
  </h1>
  <p class="cronycle-settings-page-description">
    Publish content from your Cronycle boards onto your website, as draft posts and/or in a news banner<br>
    <a href="https://www.cronycle.com/wordpress-plugin/" target="_blank">Read our tutorial</a>
  </p>

  <?php
    $options = get_option('cronycle_content_options');
    if (!isset($options['auth_token']) || empty($options['auth_token']) || !isset($user_details)) {
        settings_errors($this->CronycleContent); ?>

  <h2 style="margin-top:40px;">Connect your Cronycle account</h2>
	<p>Paste here WordPress token from the <a href="https://app.cronycle.com/account/profile" target="_blank">Cronycle webapp</a>.<br> 
    Generate it in board &gt; Publishing Settings. Find it again in Profile &gt; Integrations.</p>
    
  <form action="options.php" method="post" class="cronycle-content-settings-form">
    <?php settings_fields('cronycle_content_options');
        //do_settings_sections($this->CronycleContent);?>
    <table id="cronycle_content_form_table" class="form-table">
      <tbody>
        <tr>
          <td>
            <input id="cronycle_content_auth_token" name="cronycle_content_options[auth_token]" size="30" type="text" placeholder="Paste token here">
          </td>
          <td>
            <?php submit_button('Save Token'); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </form>

  <?php
    } else {
        settings_fields('cronycle_content_options');
        do_settings_sections($this->CronycleContent . '-account');
    
        if (empty($this->plugin_banner->get_boards_list(true))) {
            ?>
      <p>You currently do not have any boards in your Cronycle account setup with Wordpress sharing enabled.</p>
      <p>Please visit your Cronycle account and enable Wordpress on Publishing Settings for the boards you wish to share.</p>
  <?php
        } else {
            $active_tab = isset($_GET[ 'tab' ]) ? $_GET[ 'tab' ] : 'draft_post_options';

            if ($active_tab == 'draft_post_options') {
                settings_errors($this->CronycleContent . '-draft-post');
            } elseif ($active_tab == 'banner_options') {
                settings_errors($this->CronycleContent . '-banner');
            } else {
                settings_errors($this->CronycleContent . '-account');
            } ?>

  <h2 class="nav-tab-wrapper">
    <!--a href=<?php echo "?page=" . $plugin_page . "&tab=account_options"; ?> class="nav-tab <?php echo $active_tab == 'account_options' ? 'nav-tab-active' : ''; ?>">Account</a-->
    <!-- <a href=<?php echo "?page=" . $plugin_page . "&tab=draft_post_options"; ?> class="nav-tab <?php echo $active_tab == 'draft_post_options' ? 'nav-tab-active' : ''; ?>">Draft Posts</a> -->
    <!-- <a href=<?php echo "?page=" . $plugin_page . "&tab=banner_options"; ?> class="nav-tab <?php echo $active_tab == 'banner_options' ? 'nav-tab-active' : ''; ?>">Banner</a> -->
  </h2>

  <form action="options.php" method="post" class="cronycle-content-settings-form">

    <?php
      if ($active_tab == 'draft_post_options') {
          settings_fields('cronycle_content_draft_post_options');
          do_settings_sections($this->CronycleContent . '-draft-post');
          submit_button('Update Settings');
      } elseif ($active_tab == 'banner_options') {
          settings_fields('cronycle_content_banner_options');
          do_settings_sections($this->CronycleContent . '-banner');
      } else {
          settings_fields('cronycle_content_options');
          do_settings_sections($this->CronycleContent . '-account');
      } ?>

  </form>
  
  <?php
        }
    }
  ?>

</div><!-- /.wrap -->

<?php if (CRONYCLE_CONTENT_DEBUG === true) {
      ?>
<div class="cronycle-settings-log">
  <a href="<?php _e(plugin_dir_url(__FILE__) . "../../cronycle_content_debug.log"); ?>" target="_blank">View logs</a>&nbsp;&nbsp;
  <a href="#" onclick="jQuery.resetLogs()">Reset log</a>
</div>
<?php
  } ?>