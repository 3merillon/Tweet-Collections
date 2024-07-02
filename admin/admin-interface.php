<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files.
require_once TWEET_COLLECTION_PLUGIN_DIR . 'includes/tweet-functions.php';

// Add admin menu.
add_action('admin_menu', 'tweet_collection_admin_menu');
function tweet_collection_admin_menu() {
    add_menu_page(
        'Tweet Collections',
        'Tweet Collections',
        'manage_options',
        'tweet-collection',
        'tweet_collection_admin_page',
        'dashicons-twitter',
        6
    );
}

// Admin page content.
function tweet_collection_admin_page() {
    ?>
    <div class="wrap">
        <h1 style="color: orange;">Tweet Collections</h1>
        <hr style="border-top: 3px solid #000;">
        <h2 style="color: blue; font-weight: bold;">Add New Collection</h2>
        <form id="add-collection-form" method="post" action="" style="display: flex; align-items: center; gap: 10px;">
            <?php wp_nonce_field('add_collection_action', 'add_collection_nonce'); ?>
            <input type="text" name="collection_name" placeholder="Collection Name" required>
            <input type="text" name="collection_account_name" placeholder="Account Name" style="width: 250px;">
            <button type="submit" name="add_collection" style="background-color: #28a745; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Add Collection</button>
        </form>
        <hr style="border-top: 3px solid #000;">
        <h2 style="color: blue; font-weight: bold;">Manage Collections</h2>
        <hr style="border-top: 2px solid #ccc;">
        <div id="collections-list">
            <?php display_tweet_collections(); ?>
        </div>
    </div>
    <?php
}

// Handle form submissions.
if (isset($_POST['add_collection'])) {
    $collection_name = sanitize_text_field($_POST['collection_name']);
    $collection_account_name = sanitize_text_field($_POST['collection_account_name']);
    if (!empty($collection_name)) {
        if (function_exists('add_tweet_collection')) {
            add_tweet_collection($collection_name, $collection_account_name);
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Function add_tweet_collection not found.</p></div>';
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Collection Name cannot be empty.</p></div>';
        });
    }
}

if (isset($_POST['delete_collection'])) {
    $collection_id = intval($_POST['collection_id']);
    if (function_exists('delete_tweet_collection')) {
        delete_tweet_collection($collection_id);
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Function delete_tweet_collection not found.</p></div>';
        });
    }
}

if (isset($_POST['add_tweet'])) {
    $collection_id = intval($_POST['collection_id']);
    $tweet_id = sanitize_text_field($_POST['tweet_id']);
    $account_name = sanitize_text_field($_POST['account_name']);
    if (!empty($tweet_id)) {
        if (empty($account_name)) {
            $account_name = get_collection_account_name($collection_id);
        }
        if (function_exists('add_tweet_to_collection')) {
            if (!add_tweet_to_collection($collection_id, $tweet_id, $account_name)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>Invalid Tweet credentials, please verify the "Tweet ID" and "Account Name".</p></div>';
                });
            }
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Function add_tweet_to_collection not found.</p></div>';
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Tweet ID cannot be empty.</p></div>';
        });
    }
}

if (isset($_POST['add_tweet_between'])) {
    $collection_id = intval($_POST['collection_id']);
    $tweet_id = sanitize_text_field($_POST['tweet_id']);
    $account_name = sanitize_text_field($_POST['account_name']);
    $insert_after_tweet_id = intval($_POST['insert_after_tweet_id']);
    if (!empty($tweet_id)) {
        if (empty($account_name)) {
            $account_name = get_collection_account_name($collection_id);
        }
        if (function_exists('add_tweet_to_collection_between')) {
            if (!add_tweet_to_collection_between($collection_id, $tweet_id, $account_name, $insert_after_tweet_id)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>Invalid Tweet credentials, please verify the "Tweet ID" and "Account Name".</p></div>';
                });
            }
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>Function add_tweet_to_collection_between not found.</p></div>';
            });
        }
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Tweet ID cannot be empty.</p></div>';
        });
    }
}

if (isset($_POST['delete_tweet'])) {
    $tweet_id = intval($_POST['tweet_id']);
    if (function_exists('delete_tweet_from_collection')) {
        delete_tweet_from_collection($tweet_id);
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Function delete_tweet_from_collection not found.</p></div>';
        });
    }
}

// Save initial number of tweets setting for each collection
if (isset($_POST['initial_tweets']) && isset($_POST['collection_id'])) {
    $initial_tweets = intval($_POST['initial_tweets']);
    $collection_id = intval($_POST['collection_id']);
    update_post_meta($collection_id, 'initial_tweets', $initial_tweets);
}

// Display tweet collections.
function display_tweet_collections() {
    global $wpdb;
    $collections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tweet_collections");
    if ($collections) {
        foreach ($collections as $collection) {
            $account_name_display = !empty($collection->account_name) ? ' (' . esc_html($collection->account_name) . ')' : '';
            $shortcode = '[tweet_collection id="' . esc_html($collection->id) . '"]';
            $initial_tweets = get_post_meta($collection->id, 'initial_tweets', true) ?: 3; // Default to 3 if not set
            echo '<div class="collection" data-collection-id="' . esc_attr($collection->id) . '" style="border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 10px;">';
            echo '<div style="display: flex; align-items: center; justify-content: space-between; padding-bottom:10px;">';
            echo '<div style="display: flex; align-items: center;">';
            echo '<h3 style="color: #0073aa; margin-right: 10px;">' . esc_html($collection->name) . $account_name_display . '</h3>';
            echo '<span id="toggle-tweets-' . esc_attr($collection->id) . '" class="toggle-tweets" data-collection-id="' . esc_attr($collection->id) . '" style="color: blue; font-weight: bold; cursor: pointer; margin-left: 10px;">▼</span>';
            echo '<span style="color: #ff7314; margin-left: 10px;">' . esc_html($shortcode) . '</span>';
            echo '<span class="copy-shortcode" data-shortcode="' . esc_attr($shortcode) . '" style="cursor: pointer; margin-left: 10px;">📋</span>';
            // Add input for initial number of tweets
            echo '<form method="post" action="" style="margin-left: 10px; display: flex; align-items: center;">';
            echo '<label for="initial_tweets" style="margin-right: 10px;">Initial number of tweets:</label>';
            echo '<input type="number" name="initial_tweets" value="' . esc_attr($initial_tweets) . '" style="width: 60px; margin-right: 10px;">';
            echo '<input type="hidden" name="collection_id" value="' . esc_attr($collection->id) . '">';
            wp_nonce_field('initial_tweets_action', 'initial_tweets_nonce');
            echo '<button type="submit" style="background-color: #0073aa; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Save</button>';
            echo '</form>';
            echo '</div>';
            echo '<form method="post" action="" onsubmit="return confirm(\'This will delete this entire Tweet Collection. Are you sure you wish to proceed?\');">';
            echo '<input type="hidden" name="collection_id" value="' . esc_attr($collection->id) . '">';
            wp_nonce_field('delete_collection_action', 'delete_collection_nonce');
            echo '<button type="submit" name="delete_collection" style="background-color: #ff0000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Delete Collection</button>';
            echo '</form>';
            echo '</div>';
            echo '<hr class="before-add-tweet" style="border-top: 1px dotted #ccc; margin: 0;">';
            echo '<form id="add-tweet-form-' . esc_attr($collection->id) . '" method="post" action="" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">'; // Always visible form
            echo '<input type="hidden" name="collection_id" value="' . esc_attr($collection->id) . '">';
            echo '<input type="text" name="tweet_id" placeholder="Tweet ID" required>';
            $placeholder = !empty($collection->account_name) ? 'Account Name (default: ' . esc_attr($collection->account_name) . ')' : 'Account Name';
            echo '<input type="text" name="account_name" placeholder="' . esc_attr($placeholder) . '" style="width: 250px;">';
            wp_nonce_field('add_tweet_action', 'add_tweet_nonce');
            echo '<button type="submit" name="add_tweet" class="add-tweet-btn" style="background-color: #90ee90; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Add Tweet</button>';
            echo '</form>';
            echo '<div id="tweets-list-' . esc_attr($collection->id) . '" class="tweets-list" style="display: none; margin-top: 0; padding-top: 0;">';
            display_tweets_in_collection($collection->id, $account_name_display);
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No collections found.</p>';
    }
}

// Display tweets in a collection.
function display_tweets_in_collection($collection_id, $default_account_name) {
    global $wpdb;
    $tweets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tweets WHERE collection_id = %d ORDER BY `order` ASC", $collection_id)); // Order by `order` ASC
    if ($tweets) {
        echo '<ul>';
        foreach ($tweets as $tweet) {
            echo '<li class="tweet-item" style="border-bottom: 1px dotted #ccc; padding-bottom: 10px; margin-bottom: 10px;">';
            echo '<div class="tweet">';
            echo '<p>' . esc_html($tweet->content) . '</p>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="tweet_id" value="' . esc_attr($tweet->id) . '">';
            wp_nonce_field('delete_tweet_action', 'delete_tweet_nonce');
            echo '<button type="submit" name="delete_tweet" style="background-color: #ff9999; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Delete Tweet</button>';
            echo '</form>';
            echo '</div>';
            // Add the green "+" button between tweets
            echo '<div class="add-tweet-between" style="text-align: center; margin: 10px 0;">';
            echo '<span class="toggle-add-tweet-between" data-collection-id="' . esc_attr($collection_id) . '" data-tweet-id="' . esc_attr($tweet->id) . '" style="color: lightgreen; font-weight: bold; cursor: pointer;">➕</span>'; // Light green color
            echo '<form id="add-tweet-between-form-' . esc_attr($collection_id) . '-' . esc_attr($tweet->id) . '" method="post" action="" style="display: none; align-items: center; gap: 10px; margin-top: 10px;">';
            echo '<input type="hidden" name="collection_id" value="' . esc_attr($collection_id) . '">';
            echo '<input type="hidden" name="insert_after_tweet_id" value="' . esc_attr($tweet->id) . '">';
            echo '<input type="text" name="tweet_id" placeholder="Tweet ID" required>';
            $placeholder = !empty($default_account_name) ? 'Account Name (default: ' . esc_attr($default_account_name) . ')' : 'Account Name';
            echo '<input type="text" name="account_name" placeholder="' . esc_attr($placeholder) . '" style="width: 250px;">';
            wp_nonce_field('add_tweet_between_action', 'add_tweet_between_nonce');
            echo '<button type="submit" name="add_tweet_between" class="add-tweet-btn" style="background-color: #90ee90; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 5px;">Add Tweet</button>';
            echo '</form>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No tweets found in this collection.</p>';
    }
}
?>