<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Function to add a new tweet collection.
function add_tweet_collection($name, $account_name = '') {
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'tweet_collections',
        array(
            'name' => $name,
            'account_name' => $account_name
        ),
        array('%s', '%s')
    );

    if ($result === false) {
        error_log('Failed to add tweet collection: ' . $wpdb->last_error);
    }
}

// Function to delete a tweet collection.
function delete_tweet_collection($id) {
    global $wpdb;
    $result = $wpdb->delete($wpdb->prefix . 'tweet_collections', array('id' => $id), array('%d'));

    if ($result === false) {
        error_log('Failed to delete tweet collection: ' . $wpdb->last_error);
    }
}

// Function to add a tweet to a collection.
function add_tweet_to_collection($collection_id, $tweet_id, $account_name) {
    global $wpdb;
    $tweet_url = "https://twitter.com/$account_name/status/$tweet_id";
    $tweet_info = get_tweet_info($tweet_url);
    if ($tweet_info) {
        $result = $wpdb->insert(
            $wpdb->prefix . 'tweets',
            array(
                'collection_id' => $collection_id,
                'tweet_id' => $tweet_id,
                'account_name' => $account_name,
                'content' => $tweet_info['content']
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('Failed to add tweet to collection: ' . $wpdb->last_error);
        }
        return true;
    } else {
        error_log('Invalid Tweet credentials: ' . $tweet_url);
        return false;
    }
}

// Function to delete a tweet from a collection.
function delete_tweet_from_collection($id) {
    global $wpdb;
    $result = $wpdb->delete($wpdb->prefix . 'tweets', array('id' => $id), array('%d'));

    if ($result === false) {
        error_log('Failed to delete tweet from collection: ' . $wpdb->last_error);
    }
}

// Function to extract tweet information using WordPress tweet loader.
function get_tweet_info($tweet_url) {
    $embed_code = wp_oembed_get($tweet_url);
    if ($embed_code) {
        // Extract tweet information from the embed code.
        $doc = new DOMDocument();
        @$doc->loadHTML($embed_code);
        $title = $doc->getElementsByTagName('blockquote')->item(0)->nodeValue;
        $content = $doc->getElementsByTagName('p')->item(0)->nodeValue;

        return [
            'title' => $title,
            'content' => $content,
        ];
    } else {
        return false;
    }
}

// Function to get the account name of a collection.
function get_collection_account_name($collection_id) {
    global $wpdb;
    $collection = $wpdb->get_row($wpdb->prepare("SELECT account_name FROM {$wpdb->prefix}tweet_collections WHERE id = %d", $collection_id));
    return $collection ? $collection->account_name : '';
}