jQuery(document).ready(function($) {
    var tweetContainer = $('.tweet-collection');
    var loadingZone = $('.loading-zone');
    var isLoading = false;
    var initialTweets = parseInt(tweetContainer.data('initial-tweets')) || 3; // Get initial tweets from data attribute or default to 3
    var theme = tweetContainer.data('theme') || 'dark'; // Get theme from data attribute or default to dark
    var totalTweets = tweetContainer.find('.tweet').length;
    var loadedTweets = []; // Keep track of loaded tweets to avoid duplicate requests

    function loadMoreTweets() {
        if (isLoading) return;
        isLoading = true;

        var hiddenTweets = tweetContainer.find('.tweet:hidden').not('.loading'); // Get hidden tweets that are not currently loading
        if (hiddenTweets.length > 0) {
            var tweet = $(hiddenTweets[0]);
            tweet.addClass('loading'); // Mark the tweet as loading
            loadTweetEmbed(tweet, function() {
                tweet.removeClass('loading').slideDown(function() {
                    isLoading = false;
                    // Check if more tweets need to be loaded
                    if (hiddenTweets.length > 1) {
                        checkLoadingZoneVisibility();
                    } else {
                        loadingZone.remove(); // Remove the loading zone upon starting to load the last tweet
                    }
                });
            });
        } else {
            isLoading = false;
        }
    }

    function loadTweetEmbed(tweet, callback) {
        var tweetUrl = tweet.data('tweet-url');
        if (loadedTweets.includes(tweetUrl)) {
            callback();
            return;
        }
        loadedTweets.push(tweetUrl);

        var theme = tweet.data('theme') || 'dark'; // Get theme from data attribute or default to dark

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_tweet_embed',
                tweet_url: tweetUrl,
                data_theme: theme // Pass the theme to the AJAX handler
            },
            success: function(response) {
                if (response.success) {
                    var embedCode = $(response.data);
                    if (theme === 'dark') {
                        embedCode.find('.twitter-tweet').attr('data-theme', 'dark'); // Ensure the theme is applied
                    } else {
                        embedCode.find('.twitter-tweet').removeAttr('data-theme'); // Ensure the theme is removed for light
                    }
                    tweet.html(embedCode);
                    tweet.append('<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>'); // Dynamically add the script
                    if (typeof twttr !== "undefined" && twttr.widgets) {
                        twttr.widgets.load(tweet[0]);
                    }
                } else {
                    tweet.html('<p>Unable to embed tweet: ' + tweetUrl + '</p>');
                }
                callback();
            },
            error: function() {
                tweet.html('<p>Unable to embed tweet: ' + tweetUrl + '</p>');
                callback();
            }
        });
    }

    function isElementInViewport(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    function checkLoadingZoneVisibility() {
        if (isElementInViewport(loadingZone[0])) {
            loadMoreTweets();
        }
    }

    // Initially hide all tweets except the first `initialTweets`.
    var tweets = tweetContainer.find('.tweet');
    tweets.hide().slice(0, initialTweets).each(function(index) {
        var tweet = $(this);
        loadTweetEmbed(tweet, function() {
            tweet.show();
        });
    });

    // Check loading zone visibility after initial tweets are shown
    if (initialTweets < totalTweets) {
        setTimeout(checkLoadingZoneVisibility, 100);
    }

    $(window).on('scroll', function() {
        checkLoadingZoneVisibility();
    });
});