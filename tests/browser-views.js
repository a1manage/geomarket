'use strict';

const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/browser-views.js'), 'utf8');

function setup(cookie, consent, gpc) {
    const docEvents = {};
    const winEvents = {};
    const calls = [];
    const document = { cookie, addEventListener: (name, fn) => { docEvents[name] = fn; } };
    const window = {
        wp_has_consent: () => consent.value,
        crypto: { getRandomValues: (bytes) => bytes.fill(12) },
        location: { pathname: '/papers' },
        addEventListener: (name, fn) => { winEvents[name] = fn; }
    };
    vm.runInNewContext(source, {
        window, document, navigator: { globalPrivacyControl: gpc }, console,
        Uint8Array, URLSearchParams, a1geoBrowserViews: { endpoint: '/wp-admin/admin-ajax.php' },
        fetch: (url, options) => { calls.push(options); return Promise.resolve({ ok: true }); }
    });
    return { document, docEvents, winEvents, calls };
}

const consent = { value: false };
let test = setup('', consent, false);
assert.strictEqual(test.calls.length, 0);
test.document.cookie = 'wpconsent_preferences=' + encodeURIComponent(JSON.stringify({ statistics: true }));
consent.value = true;
test.docEvents.wp_listen_for_consent_change();
test.winEvents.wpconsent_consent_saved();
assert.strictEqual(test.calls.length, 1);
assert.match(test.calls[0].body.get('event_id'), /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
assert.strictEqual(test.calls[0].body.get('path'), '/papers');
test = setup('wpconsent_preferences=%7B%22statistics%22%3Afalse%7D', { value: true }, false);
assert.strictEqual(test.calls.length, 0);
test = setup('wpconsent_preferences=%7B%22statistics%22%3Atrue%7D', { value: true }, true);
assert.strictEqual(test.calls.length, 0);
test = setup('wpconsent_preferences=%7B%22statistics%22%3Atrue%2C%22respect_gpc%22%3Afalse%7D', { value: true }, true);
assert.strictEqual(test.calls.length, 1);
console.log('Browser view tests passed.');
