<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$collection_id = intval($atts['id']);
$tweets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tweets WHERE collection_id = %d ORDER BY id DESC", $collection_id));

if ($tweets) {
    echo '<div class="tweet-collection">';
    foreach ($tweets as $index => $tweet) {
        // Only show the first 3 tweets initially
        $style = $index >= 3 ? 'style="display:none;"' : '';
        $tweet_url = 'https://twitter.com/' . esc_attr($tweet->account_name) . '/status/' . esc_attr($tweet->tweet_id);
        $embed_code = wp_oembed_get($tweet_url);
        if ($embed_code) {
            echo '<div class="tweet" ' . $style . '>';
            echo $embed_code;
            echo '</div>';
        } else {
            echo '<div class="tweet" ' . $style . '>';
            echo '<p>Unable to embed tweet: ' . esc_html($tweet_url) . '</p>';
            echo '</div>';
        }
    }
    // Add a loading zone at the bottom
    echo '<div class="loading-zone" style="text-align: center; margin: 20px 0;"><div class="loading-icon">C</div></div>';
    echo '</div>';
} else {
    echo '<p>No tweets found in this collection.</p>';
}
?>