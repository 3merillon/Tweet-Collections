<?php
/**
 * Plugin Name: Tweet Collection
 * Description: A plugin to create and manage collections of tweets and embed them in WordPress pages with lazy loading.
 * Version: 1.0
 * Author: Cyril Monkewitz
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// MIT License
// 
// Copyright (c) 2024 Cyril Monkewitz
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('TWEET_COLLECTION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TWEET_COLLECTION_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files.
require_once TWEET_COLLECTION_PLUGIN_DIR . 'includes/tweet-functions.php';
require_once TWEET_COLLECTION_PLUGIN_DIR . 'admin/admin-interface.php';

// Hook for plugin activation.
register_activation_hook(__FILE__, 'tweet_collection_activate');
function tweet_collection_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $collections_table = $wpdb->prefix . 'tweet_collections';
    $tweets_table = $wpdb->prefix . 'tweets';

    $sql = "
    CREATE TABLE $collections_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        account_name varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;

    CREATE TABLE $tweets_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        collection_id mediumint(9) NOT NULL,
        tweet_id varchar(255) NOT NULL,
        account_name varchar(255) NOT NULL,
        content longtext NOT NULL,
        `order` mediumint(9) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        FOREIGN KEY (collection_id) REFERENCES $collections_table(id) ON DELETE CASCADE
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Ensure the `order` column is added to the existing table
    add_order_column_if_not_exists($tweets_table);

    // Populate the `order` column if necessary
    populate_order_column($tweets_table);
}

// Function to add `order` column if it doesn't exist
function add_order_column_if_not_exists($table_name) {
    global $wpdb;

    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM $table_name LIKE %s", 'order'
    ));

    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD `order` mediumint(9) NOT NULL DEFAULT 0");
    }
}

// Function to populate the `order` column
function populate_order_column($table_name) {
    global $wpdb;

    $tweets = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");
    $order = 1;
    foreach ($tweets as $tweet) {
        $wpdb->update(
            $table_name,
            array('order' => $order),
            array('id' => $tweet->id),
            array('%d'),
            array('%d')
        );
        $order++;
    }
}

// Hook for plugin deactivation.
register_deactivation_hook(__FILE__, 'tweet_collection_deactivate');
function tweet_collection_deactivate() {
    // Deactivation code here...
}

// Enqueue scripts and styles for the admin page.
add_action('admin_enqueue_scripts', 'tweet_collection_enqueue_admin_scripts');
function tweet_collection_enqueue_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_tweet-collection') {
        return;
    }
    wp_enqueue_style('tweet-collection-style', TWEET_COLLECTION_PLUGIN_URL . 'assets/css/style.css', array(), time()); // Add version number here
    wp_enqueue_script('tweet-collection-admin-script', TWEET_COLLECTION_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), time(), true); // Add version number here
    wp_enqueue_script('tweet-collection-admin-clipboard', TWEET_COLLECTION_PLUGIN_URL . 'assets/js/admin-clipboard.js', array('jquery'), time(), true); // Add version number here
}

// Enqueue scripts and styles for the front end.
add_action('wp_enqueue_scripts', 'tweet_collection_frontend_enqueue_scripts');
function tweet_collection_frontend_enqueue_scripts() {
    wp_enqueue_style('tweet-collection-style', TWEET_COLLECTION_PLUGIN_URL . 'assets/css/style.css', array(), time()); // Add version number here
    wp_enqueue_script('tweet-collection-script', TWEET_COLLECTION_PLUGIN_URL . 'assets/js/script.js', array('jquery'), time(), true); // Add version number here
    wp_localize_script('tweet-collection-script', 'ajaxurl', admin_url('admin-ajax.php'));
}

// Register shortcode for embedding tweet collections.
add_shortcode('tweet_collection', 'tweet_collection_shortcode');
function tweet_collection_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'tweet_collection');

    ob_start();
    include TWEET_COLLECTION_PLUGIN_DIR . 'templates/collection-template.php';
    return ob_get_clean();
}

// Register AJAX handler for fetching tweet embed code.
add_action('wp_ajax_get_tweet_embed', 'get_tweet_embed');
add_action('wp_ajax_nopriv_get_tweet_embed', 'get_tweet_embed');

function get_tweet_embed() {
    if (!isset($_POST['tweet_url'])) {
        wp_send_json_error('No tweet URL provided.');
    }

    $tweet_url = sanitize_text_field($_POST['tweet_url']);
    $embed_code = wp_oembed_get($tweet_url);

    if ($embed_code) {
        wp_send_json_success($embed_code);
    } else {
        wp_send_json_error('Unable to fetch embed code.');
    }
}