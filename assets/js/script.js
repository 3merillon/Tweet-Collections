jQuery(document).ready(function($) {
    var tweetContainer = $('.tweet-collection');
    var loadingZone = $('.loading-zone');
    var loadingIndicator = $('.loading-icon');
    var isLoading = false;

    function loadMoreTweets() {
        if (isLoading) return;
        isLoading = true;

        var hiddenTweets = tweetContainer.find('.tweet:hidden');
        if (hiddenTweets.length > 0) {
            var tweet = hiddenTweets.first();
            tweet.slideDown(function() {
                loadTweetEmbed(tweet, function() {
                    waitForHeightStabilization(tweet, function() {
                        isLoading = false;
                        // Check if more tweets need to be loaded
                        if (hiddenTweets.length > 1) {
                            checkLoadingZoneVisibility();
                        } else {
                            loadingIndicator.hide();
                        }
                    });
                });
            });
        } else {
            isLoading = false;
            loadingIndicator.hide();
        }
    }

    function loadTweetEmbed(tweet, callback) {
        var tweetUrl = tweet.data('tweet-url');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_tweet_embed',
                tweet_url: tweetUrl
            },
            success: function(response) {
                if (response.success) {
                    tweet.html(response.data);
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

    function waitForHeightStabilization(element, callback) {
        var lastHeight = element.height();
        var interval = setInterval(function() {
            var newHeight = element.height();
            if (newHeight === lastHeight) {
                clearInterval(interval);
                callback();
            } else {
                lastHeight = newHeight;
            }
        }, 100); // Check every 100ms
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

    // Use MutationObserver to detect changes in the tweet container
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                checkLoadingZoneVisibility();
            }
        });
    });

    // Observe changes in the tweet container
    observer.observe(tweetContainer[0], {
        childList: true,
        subtree: true
    });

    // Initially hide all tweets except the first 3.
    tweetContainer.find('.tweet').hide().slice(0, 3).each(function() {
        var tweet = $(this);
        loadTweetEmbed(tweet, function() {
            tweet.show();
        });
    });

    // Check loading zone visibility after initial tweets are shown
    setTimeout(checkLoadingZoneVisibility, 100);

    $(window).on('scroll', function() {
        checkLoadingZoneVisibility();
    });
});