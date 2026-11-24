# Paper permalinks

Published, publicly accessible posts in the Papers category or its descendants
use `/papers/{post-id}/`. Other posts keep their existing permalinks. Previous
paper permalink paths redirect to the new URL, and historical URL-only view
logs are counted alongside the new URLs.

WordPress registers and flushes the rewrite rule once when the theme code is
deployed. Pretty permalinks must be enabled; with plain permalinks the existing
query-string URLs are retained. Clear any WordPress, server or CDN page caches
after deployment so cached listings and redirects use the new URLs. If routes
do not resolve, save **Settings > Permalinks** to refresh the rewrite rules.
