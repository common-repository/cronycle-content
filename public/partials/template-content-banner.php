<?php 
/**
 * Template for Cronycle Content banner.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/public/partials
 */


// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Also die, if no data to display
if (! isset($board_id) || ! isset($board_tiles)) {
    CronycleContentLogger::log("Plugin breakdown. No board tiles data.");
    die;
}

if ($board_tiles == "") {
    _e("Sorry! No content to display.");
    return;
}

?>

<div class="cronycle-banner-container" id="cronycle-banner-<?php _e($instance); ?>">
  <div class="cronycle-banner-inner-container">
    <div class="cronycle-banner">
      <div class="cronycle-header">
        <div class="cronycle-board-name">
          <h1><?php _e($board_name); ?></h1>
        </div>
        <div class="cronycle-logo">
        <?php if (!$is_pro_user) {
    ?>
          <p><span>Powered By</span><br><img src= <?php _e(plugin_dir_url(__FILE__) . "../../images/logo-full.png"); ?> ></p>
        <?php
} ?>
        </div>
      </div>
      <div class="cronycle-carousel" <?php _e("data-board-id=" . $board_id . " data-include-image=" . $include_image); ?> >
        <?php 
          foreach ($board_tiles as $board_tile) {
              $articles = $board_tile['articles'];
              // for simple articles
              if ($board_tile['tile_type'] == "link") {
                  $article = $articles[0];
                  if (empty($board_tile['summary'])) {
                      include("template-content-banner-item.php");
                  } else {
                      include("template-content-banner-item-summary.php");
                  }
              }
              // for story arc articles
              elseif ($board_tile['tile_type'] == "note") {
                  include("template-content-banner-item-group.php");
              }
              // for conversation articles
              elseif ($board_tile['tile_type'] == "conversation") {
                  $article = $articles[0];
                  include("template-content-banner-item-convo.php");
              }
          }
        ?>
      </div>
    </div>
  </div>
</div>

<script>
jQuery(function ($) {

  // format published date from epoch
  $('.cronycle-carousel-item-subtitle span:nth-child(2), \
	    .cronycle-carousel-item-convo-subtitle span:nth-child(2)').formatPublishedDate();

  var width = "<?php _e($width); ?>";
  var position = "<?php _e($position); ?>";

  if(position == "left")
    position = "flex-start";
  else if(position == "right")
    position = "flex-end";
  $('#cronycle-banner-<?php _e($instance); ?>.cronycle-banner-container').css( { 'align-items' : position } );
  $('#cronycle-banner-<?php _e($instance); ?> .cronycle-banner-inner-container').css( { 'width' : width } );

  // apply carousel
  $(".cronycle-carousel").cronycleCarousel();
  
});
</script>