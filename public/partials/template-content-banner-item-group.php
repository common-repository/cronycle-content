<?php 
/**
 * Template for grouped items in content banner.
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
if (! isset($board_tile) || ! isset($articles)) {
    CronycleContentLogger::log("Plugin breakdown. No board tile or article data.");
    die;
}
?>

<div class="cronycle-carousel-item-group" data-cronycle-wp-group-id=<?php _e($board_tile['wp_group_id']); ?> >
  <div class="cronycle-carousel-item-group-text">
    <p><?php _e($board_tile['summary']); ?></p>
  </div>
  <div class="cronycle-carousel-item-group-tiles">
    <?php
      $article = $articles[0];
      if ($article['article_type'] == "link") {
          include("template-content-banner-item.php");
      } elseif ($article['article_type'] == "conversation") {
          include("template-content-banner-item-convo.php");
      }
      if (count($articles) == 2) {
          $article = $articles[1];
          if ($article['article_type'] == "link") {
              include("template-content-banner-item.php");
          } elseif ($article['article_type'] == "conversation") {
              include("template-content-banner-item-convo.php");
          }
      }
    ?>
  </div>
</div>
