(function () {
    'use strict';

    var sent = false;

    function hasStatisticsConsent() {
        if (typeof window.wp_has_consent !== 'function' || !window.wp_has_consent('statistics')) {
            return false;
        }
        var match = document.cookie.match(/(?:^|;\s*)wpconsent_preferences=([^;]*)/);
        if (!match) {
            return false;
        }
        try {
            var preferences = JSON.parse(decodeURIComponent(match[1]));
            return preferences.statistics === true &&
                !(navigator.globalPrivacyControl === true && preferences.respect_gpc !== false);
        } catch (error) {
            console.error('Browser view tracking: invalid consent preference.', error);
            return false;
        }
    }

    function track() {
        if (sent || !hasStatisticsConsent()) {
            return;
        }
        if (!window.crypto || !window.crypto.getRandomValues) {
            console.error('Browser view tracking: secure random numbers are unavailable.');
            return;
        }
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 15) | 64;
        bytes[8] = (bytes[8] & 63) | 128;
        var hex = Array.from(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
        var id = hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
        var body = new URLSearchParams({ action: 'a1geo_browser_view', event_id: id, path: window.location.pathname });
        sent = true;
        fetch(a1geoBrowserViews.endpoint, { method: 'POST', credentials: 'same-origin', body: body, keepalive: true })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
            })
            .catch(function (error) {
                console.error('Browser view tracking failed:', error);
            });
    }

    document.addEventListener('wp_listen_for_consent_change', track);
    window.addEventListener('wpconsent_consent_saved', track);
    window.addEventListener('wpconsent_consent_updated', track);
    track();
}());
