jQuery(document).ready(function($) {
    // Function to toggle tweets display
    function toggleTweets(collectionId, show) {
        var tweetsList = $('#tweets-list-' + collectionId);
        if (show) {
            tweetsList.slideDown();
            $('#toggle-tweets-' + collectionId).text('▲');
        } else {
            tweetsList.slideUp();
            $('#toggle-tweets-' + collectionId).text('▼');
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
});