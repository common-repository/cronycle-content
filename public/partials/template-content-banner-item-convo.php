<?php 
/**
 * Template for conversation item in content banner.
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 * @subpackage CronycleContent/public/partials
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die();
}

// Also die, if no data to display
if (! isset($board_tile) || ! isset($article)) {
    CronycleContentLogger::log("Plugin breakdown. No board tile or article data.");
    die;
}
?>

<div class="cronycle-carousel-item-convo" data-cronycle-wp-tile-id=<?php _e($board_tile['wp_tile_id']); ?> >
  <div class="cronycle-carousel-item-convo-heading" onclick="window.open('<?php _e($article['tweet_url']); ?>', '_blank');">
    <div class="cronycle-carousel-item-convo-avatar"><img src=<?php _e($article['avatar_url']); ?> ></div>
    <div class="cronycle-carousel-item-convo-title">
      <h2>
        <div class="cronycle-carousel-item-convo-bubble">
          <svg width="40px" height="40px" viewBox="0 0 40 40" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <path d="M5.5,17.0707692 C5.5,10.5892586 10.5830689,5.5 17.0621538,5.5 L22.9421538,5.52153846 C29.4176363,5.52153846 34.4910094,10.596285 34.4999991,17.0932817 C34.4932461,20.5598092 32.8153839,24.0362958 30.0089299,26.412367 C28.9828886,27.2810756 26.6663623,28.8989863 23.5108835,30.9945917 C22.3906961,31.7385266 21.2350525,32.4956832 20.1187191,33.2197313 C19.384146,33.6961717 18.670205,34.1553757 18.4790729,34.2761289 C18.2293554,34.4283956 17.9453248,34.5 17.648,34.5 C17.12586,34.5 16.6298675,34.2412213 16.3280008,33.7911533 C16.1781005,33.5513128 16.084,33.2050939 16.084,32.9446154 L16.084,27.9892362 C9.97495328,27.5325358 5.5,22.9847295 5.5,17.0707692 Z M17.061248,6.50000004 C11.1351539,6.50047296 6.5,11.1415732 6.5,17.0707692 C6.5,22.5887669 10.7747055,26.789697 16.6035094,27.0173038 L17.084,27.0360663 L17.084,32.9446154 C17.084,33.0222501 17.1281053,33.1845242 17.1671106,33.2474368 C17.2724746,33.4043619 17.455781,33.5 17.648,33.5 C17.7699572,33.5 17.8741826,33.4737247 17.95159,33.4266018 C18.1318559,33.3126406 18.8429758,32.855251 19.5745597,32.3807494 C20.6880446,31.6585488 21.840731,30.9033297 22.9576549,30.1615622 C26.076201,28.0904844 28.3784802,26.4825242 29.3627657,25.6491686 C31.9492517,23.4593322 33.4938302,20.2590061 33.5000005,17.0929997 C33.4917735,11.149043 28.8655214,6.52153846 22.9403224,6.52153511 L17.061248,6.50000004 Z"
              id="path-1"></path>
          </svg>
          <span><?php _e($article['replies_count']); ?></span>
        </div>
        <span><?php _e($article['tweet_title_prefix']); ?></span>
      </h2>
      <h1><?php _e($article['tweeter_name']); ?></h1>
      <div class="cronycle-carousel-item-convo-subtitle">
        <span><?php _e($article['tweeter_screen_name']); ?></span> &bull; 
        <span><?php _e($article['tweet_time']); ?></span></div>
    </div>
  </div>
  <?php if ($include_image) {
    ?>
  <div class="cronycle-carousel-item-convo-empty"></div>
  <?php
} ?>
  <div class="cronycle-carousel-item-convo-body">
    <p><?php _e($article['tweet_text']); ?></p>
  </div>
</div>
