<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$collection_id = intval($atts['id']);
$initial_tweets = get_post_meta($collection_id, 'initial_tweets', true) ?: 3; // Default to 3 if not set

// Fetch the theme directly from the database to avoid any defaulting issues
$collection = $wpdb->get_row($wpdb->prepare("SELECT theme FROM {$wpdb->prefix}tweet_collections WHERE id = %d", $collection_id));
$theme = $collection ? $collection->theme : 'dark'; // Default to dark if not set

$tweets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tweets WHERE collection_id = %d ORDER BY `order` ASC", $collection_id));

if ($tweets) {
    $total_tweets = count($tweets);
    echo '<div class="tweet-collection" data-initial-tweets="' . esc_attr($initial_tweets) . '" data-theme="' . esc_attr($theme) . '">';
    foreach ($tweets as $index => $tweet) {
        // Only show the first `initial_tweets` initially
        $style = $index >= $initial_tweets ? 'style="display:none;"' : '';
        $tweet_url = 'https://twitter.com/' . esc_attr($tweet->account_name) . '/status/' . esc_attr($tweet->tweet_id);
        echo '<div class="tweet" ' . $style . ' data-tweet-url="' . esc_attr($tweet_url) . '" data-theme="' . esc_attr($theme) . '">';
        echo '<div class="tweet-placeholder">Loading tweet...</div>';
        echo '</div>';
    }
    // Add a loading zone at the bottom if initial tweets is less than total tweets
    if ($initial_tweets < $total_tweets) {
        echo '<div class="loading-zone" style="text-align: center; margin: 20px 0;"><div class="loading-icon">C</div></div>';
    }
    echo '</div>';
} else {
    echo '<p>No tweets found in this collection.</p>';
}
?>