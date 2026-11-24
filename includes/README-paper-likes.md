# Paper likes configuration

Paper likes require Cloudflare Turnstile. Create a widget for the site's public
hostname and define its keys outside the theme, for example in `wp-config.php`
before WordPress loads:

```php
define( 'A1GEO_TURNSTILE_SITE_KEY', getenv( 'A1GEO_TURNSTILE_SITE_KEY' ) );
define( 'A1GEO_TURNSTILE_SECRET_KEY', getenv( 'A1GEO_TURNSTILE_SECRET_KEY' ) );
```

Provide the corresponding environment variables securely. Without both keys,
the form reports that likes are unavailable. Turnstile verifies the hostname
against the WordPress home URL and requires the `paper_like` action. JavaScript
and access to Cloudflare's Turnstile script are required. If a Content Security
Policy is used, allow the Turnstile script and frame endpoints as required by
Cloudflare. Use HTTPS for the page and WordPress endpoints.

The server stores normalized emails only as HMAC-SHA-256 hashes, using the
`a1geo_paper_likes_secret` option. Preserve the secret and likes table when
migrating; existing hashes cannot be recovered or re-keyed without the emails.
The browser stores the normalized email in local storage after a successful
submission, sends it to the site for status lookup, and offers a button to
forget it. Do not send email addresses to Turnstile. Email ownership is not
verified; anyone who knows an address can check or change its likes. Include
this storage and Cloudflare's verification in the site's privacy notice.

The site counts up to 10 like/removal attempts per IP per one-hour window and
five per email per one-hour window. Status requests have a separate limit of 60
per IP per hour. Limits are database-backed, shared across web workers, and
expire after an hour; a daily cleanup removes old counters. If the limit table
is unavailable, requests fail closed.

Only `REMOTE_ADDR` is used for IP limiting. When behind Cloudflare or another
proxy, configure the web server to restore the client address **only for
trusted proxy networks** before PHP runs. Do not blindly trust incoming
forwarded-IP headers. Otherwise visitors may share a proxy's limit, or an
attacker may spoof addresses if the server is misconfigured.
