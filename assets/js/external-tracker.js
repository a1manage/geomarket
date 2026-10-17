jQuery(document).ready(function($) {
    $('a[href^="http"]').on('click', function(e) {
        var $link = $(this);
        var targetUrl = $link.attr('href');
        var currentHost = window.location.hostname;

        // Ensure link is external
        if (targetUrl && !targetUrl.includes(currentHost)) {
            var isNewTab = e.metaKey || e.ctrlKey || $link.attr('target') === '_blank';

            if (!isNewTab) {
                e.preventDefault();
            }

            fetch(a1geo_api_obj.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': a1geo_api_obj.nonce
                },
                body: JSON.stringify({
                    target_url: targetUrl,
                    source_url: window.location.href
                })
            })
            .catch(function(error) {
                console.error('Tracking Error:', error);
            })
            .finally(function() {
                if (!isNewTab) {
                    window.location.href = targetUrl;
                }
            });
        }
    });
});