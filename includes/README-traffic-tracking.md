# Traffic tracking

The admin traffic reports distinguish these measures:

- Raw requests come from the existing monthly access logs and include bots.
- Requests without known automation signatures are a heuristic, not verified
  humans or clicks. Signatures and Meta-range filtering apply retrospectively.
- Suspicious bursts are requests participating in a window of 60 seconds or
  less containing at least 20 distinct URLs for the same logged IP and user
  agent. Burst counts may overlap known bots.
  Shared networks can cause false positives. Ranges containing more than
  50,000 log rows are reported as unavailable; select a shorter range.
- Views excluding bots and bursts subtract only burst requests that are not
  already identified as bots from the non-bot total. This is an estimate,
  not a verified human count. It is unavailable when burst data is unavailable.

The browser page-view tracker starts when the new table is installed. It
records a page once per browser document after explicit WPConsent statistics
consent and WP Consent API approval. It honors Global Privacy Control unless
the saved preference explicitly overrides it. The tracker stores an event ID,
URL path without query or fragment, and timestamp; it does not store
IP addresses or user agents. Page paths can still contain personal information.
The event ID prevents duplicate submissions of the same event, not bots.
Historical browser views cannot be reconstructed, and the installation day is
partial. JavaScript blockers, consent, caching, site configuration, and the
reporting time zone can make these counts differ from Google Analytics.

New access-log and browser-view timestamps use the WordPress time zone set in
Settings > General, regardless of the database or PHP server time zone. Monthly
access-log tables are selected using that same time zone. Existing timestamps
are not converted; older rows may use a different database time zone. Confirm
their original time zone and account for daylight saving and month boundaries
before migrating historical data. Changing the WordPress time zone later also
does not convert existing rows.

The tracker uses the WordPress AJAX endpoint and the WPConsent and WP Consent
API plugins. If the consent API or the statistics preference is unavailable,
no browser events are sent. Review the site's privacy notice, consent settings,
retention, and access controls before deploying this additional analytics data.
