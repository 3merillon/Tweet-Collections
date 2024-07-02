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
            'name' => sanitize_text_field($name),
            'account_name' => sanitize_text_field($account_name)
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
    $result = $wpdb->delete($wpdb->prefix . 'tweet_collections', array('id' => intval($id)), array('%d'));

    if ($result === false) {
        error_log('Failed to delete tweet collection: ' . $wpdb->last_error);
    }
}

// Function to add a tweet to a collection.
function add_tweet_to_collection($collection_id, $tweet_id, $account_name) {
    global $wpdb;
    $tweet_url = "https://twitter.com/" . sanitize_text_field($account_name) . "/status/" . sanitize_text_field($tweet_id);
    $tweet_info = get_tweet_info($tweet_url);
    if ($tweet_info) {
        // Get the lowest order value in the collection
        $min_order = $wpdb->get_var($wpdb->prepare("SELECT MIN(`order`) FROM {$wpdb->prefix}tweets WHERE collection_id = %d", $collection_id));
        $new_order = is_null($min_order) ? 1 : $min_order - 1;

        $result = $wpdb->insert(
            $wpdb->prefix . 'tweets',
            array(
                'collection_id' => intval($collection_id),
                'tweet_id' => sanitize_text_field($tweet_id),
                'account_name' => sanitize_text_field($account_name),
                'content' => sanitize_text_field($tweet_info['content']),
                'order' => $new_order // Set the order value
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            error_log('Failed to add tweet to collection: ' . $wpdb->last_error);
            return false;
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
    $result = $wpdb->delete($wpdb->prefix . 'tweets', array('id' => intval($id)), array('%d'));

    if ($result === false) {
        error_log('Failed to delete tweet from collection: ' . $wpdb->last_error);
    }
}

// Function to extract tweet information using WordPress tweet loader.
function get_tweet_info($tweet_url) {
    $embed_code = wp_oembed_get(esc_url($tweet_url));
    if ($embed_code) {
        // Extract tweet information from the embed code.
        $doc = new DOMDocument();
        @$doc->loadHTML($embed_code);
        $title = $doc->getElementsByTagName('blockquote')->item(0)->nodeValue;
        $content = $doc->getElementsByTagName('p')->item(0)->nodeValue;

        return [
            'title' => sanitize_text_field($title),
            'content' => sanitize_text_field($content),
        ];
    } else {
        return false;
    }
}

// Function to get the account name of a collection.
function get_collection_account_name($collection_id) {
    global $wpdb;
    $collection = $wpdb->get_row($wpdb->prepare("SELECT account_name FROM {$wpdb->prefix}tweet_collections WHERE id = %d", intval($collection_id)));
    return $collection ? sanitize_text_field($collection->account_name) : '';
}

// Function to add a tweet to a collection between existing tweets.
function add_tweet_to_collection_between($collection_id, $tweet_id, $account_name, $insert_after_tweet_id) {
    global $wpdb;

    // Get the tweet information.
    $tweet_url = "https://twitter.com/" . sanitize_text_field($account_name) . "/status/" . sanitize_text_field($tweet_id);
    $tweet_info = get_tweet_info($tweet_url);

    if ($tweet_info) {
        // Get the order of the tweet after which the new tweet will be inserted.
        $insert_after_tweet = $wpdb->get_row($wpdb->prepare("SELECT `order` FROM {$wpdb->prefix}tweets WHERE id = %d", intval($insert_after_tweet_id)));
        $new_order = $insert_after_tweet->order + 1;

        // Update the order of tweets that come after the new tweet.
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}tweets SET `order` = `order` + 1 WHERE collection_id = %d AND `order` > %d", intval($collection_id), $insert_after_tweet->order));

        // Insert the new tweet.
        $result = $wpdb->insert(
            $wpdb->prefix . 'tweets',
            array(
                'collection_id' => intval($collection_id),
                'tweet_id' => sanitize_text_field($tweet_id),
                'account_name' => sanitize_text_field($account_name),
                'content' => sanitize_text_field($tweet_info['content']),
                'order' => $new_order
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            error_log('Failed to add tweet to collection: ' . $wpdb->last_error);
            return false;
        }

        return true;
    } else {
        error_log('Invalid Tweet credentials: ' . $tweet_url);
        return false;
    }
}
?>