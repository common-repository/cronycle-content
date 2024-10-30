<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * @link       http://cronycle.com
 * @since      1.0.0
 *
 * @package    CronycleContent
 */

// If uninstall not called from WordPress, then exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// delete all the Cronycle content settings options from database
if (get_option('cronycle_content_options') !== false) {
    if (!delete_option('cronycle_content_options')) {
        errorlog("Failure in deleting Cronycle options during Cronycle content plugin uninstall.");
    }
}

if (get_option('cronycle_content_banner_options') !== false) {
    if (!delete_option('cronycle_content_banner_options')) {
        errorlog("Failure in deleting Cronycle banner options during Cronycle content plugin uninstall.");
    }
}

if (get_option('cronycle_content_draft_post_options') !== false) {
    if (!delete_option('cronycle_content_draft_post_options')) {
        errorlog("Failure in deleting Cronycle draft post options during Cronycle content plugin uninstall.");
    }
}

if (get_option('cronycle_content_account_options') !== false) {
    if (!delete_option('cronycle_content_account_options')) {
        errorlog("Failure in deleting Cronycle account options during Cronycle content plugin uninstall.");
    }
}

// deletes all the post with type 'cronycle_content'
$args = array(
    'post_type'     => 'cronycle_content',
    'post_status'   => array('publish', 'draft', 'trash', 'future', 'pending', 'private', 'auto-draft', 'inherit'),
    'numberposts'   => -1
);
$all_deleted = true;
$posts = get_posts($args);
foreach ($posts as $post) {
    $all_deleted &= !empty(wp_delete_post($post->ID, true));
}
if (!$all_deleted) {
    errorlog("Failure in deleting Cronycle custom posts during Cronycle content plugin uninstall.");
}

error_log("Cronycle content plugin uninstalled successfully.");
