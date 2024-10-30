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
if (! isset($board_tile) || ! isset($article)) {
    CronycleContentLogger::log("Plugin breakdown. No board tile or article data.");
    die;
}
?>

<div class="cronycle-carousel-item-summary">
  <div class="cronycle-carousel-item-summary-text">
    <p><?php _e($board_tile['summary']); ?></p>
  </div>
  <div class="cronycle-carousel-item-summary-tiles">
    <?php
        include("template-content-banner-item.php");
    ?>
  </div>
</div>
