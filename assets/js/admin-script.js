jQuery(document).ready(function($) {
    // Function to toggle tweets display
    function toggleTweets(collectionId, show) {
        var tweetsList = $('#tweets-list-' + collectionId);
        var toggleArrow = $('#toggle-tweets-' + collectionId);
        if (show) {
            tweetsList.slideDown();
            toggleArrow.text('▲');
        } else {
            tweetsList.slideUp();
            toggleArrow.text('▼');
        }
    }

    // Function to toggle add tweet form display
    function toggleAddTweetForm(collectionId, show) {
        var addTweetForm = $('#add-tweet-form-' + collectionId);
        var toggleAddArrow = $('#toggle-add-tweet-' + collectionId);
        if (show) {
            addTweetForm.slideDown();
            toggleAddArrow.text('➖');
        } else {
            addTweetForm.slideUp();
            toggleAddArrow.text('➕');
        }
    }

    // Function to toggle add tweet form between tweets
    function toggleAddTweetBetweenForm(collectionId, tweetId, show) {
        var addTweetBetweenForm = $('#add-tweet-between-form-' + collectionId + '-' + tweetId);
        var toggleAddBetweenArrow = $('.toggle-add-tweet-between[data-collection-id="' + collectionId + '"][data-tweet-id="' + tweetId + '"]');
        if (show) {
            addTweetBetweenForm.slideDown();
            toggleAddBetweenArrow.text('➖');
        } else {
            addTweetBetweenForm.slideUp();
            toggleAddBetweenArrow.text('➕');
        }
    }

    // Restore the state of expanded collections from local storage
    $('.collection').each(function() {
        var collectionId = $(this).data('collection-id');
        if (localStorage.getItem('collection-' + collectionId) === 'expanded') {
            $('#tweets-list-' + collectionId).show();
            $('#toggle-tweets-' + collectionId).text('▲');
        }
    });

    // Show more/Show less functionality for tweets
    $('.toggle-tweets').on('click', function() {
        var collectionId = $(this).data('collection-id');
        var tweetsList = $('#tweets-list-' + collectionId);
        if (tweetsList.is(':visible')) {
            toggleTweets(collectionId, false);
            localStorage.setItem('collection-' + collectionId, 'collapsed');
        } else {
            toggleTweets(collectionId, true);
            localStorage.setItem('collection-' + collectionId, 'expanded');
        }
    });

    // Show/hide add tweet form functionality
    $('.toggle-add-tweet').on('click', function() {
        var collectionId = $(this).data('collection-id');
        var addTweetForm = $('#add-tweet-form-' + collectionId);
        if (addTweetForm.is(':visible')) {
            toggleAddTweetForm(collectionId, false);
        } else {
            toggleAddTweetForm(collectionId, true);
        }
    });

    // Show/hide add tweet form between tweets functionality
    $('.toggle-add-tweet-between').on('click', function() {
        var collectionId = $(this).data('collection-id');
        var tweetId = $(this).data('tweet-id');
        var addTweetBetweenForm = $('#add-tweet-between-form-' + collectionId + '-' + tweetId);
        if (addTweetBetweenForm.is(':visible')) {
            toggleAddTweetBetweenForm(collectionId, tweetId, false);
        } else {
            toggleAddTweetBetweenForm(collectionId, tweetId, true);
        }
    });

    // Store the collection ID in local storage upon form submission
    $('form').on('submit', function(event) {
        if ($(this).find('button[name="add_tweet"]').length > 0) {
            var collectionId = $(this).closest('.collection').data('collection-id');
            localStorage.setItem('expand-collection-id', collectionId);
        }
    });

    // Check local storage after the page reloads to expand the collection
    $(document).ready(function() {
        var collectionId = localStorage.getItem('expand-collection-id');
        if (collectionId) {
            toggleTweets(collectionId, true);
            localStorage.setItem('collection-' + collectionId, 'expanded');
            localStorage.removeItem('expand-collection-id');
        }
    });
});