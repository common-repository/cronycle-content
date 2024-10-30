<?php 
/**
 * Template for simple item in content banner.
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
if (! isset($board_tile) || ! isset($article)) {
    CronycleContentLogger::log("Plugin breakdown. No board tile or article data.");
    die;
}
?>

<div class="cronycle-carousel-item" data-cronycle-wp-tile-id=<?php _e($board_tile['wp_tile_id']); ?> onclick="window.open('<?php _e($article['url']); ?>', '_blank');">
  <?php if ($include_image) {
    ?>
  <div class="cronycle-carousel-item-image"><img style="background-image: url(<?php _e($article['image_url']); ?>)"></div>
  <?php
} ?>
  <div class="cronycle-carousel-item-heading">
    <div class="cronycle-carousel-item-title">
      <h1> <?php _e($article['title']); ?> </h1>
    </div>
    <div class="cronycle-carousel-item-subtitle">
      <span><?php _e($article['publisher_name']); ?></span> &bull; 
      <span><?php _e($article['published_date']); ?></span>
    </div>
  </div>
  <div class="cronycle-carousel-item-body">
    <p> <?php _e($article['description']); ?> </p>
  </div>
</div>