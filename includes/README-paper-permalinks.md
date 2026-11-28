# Paper permalinks

Published, publicly accessible posts in the Papers category or its descendants
use `/papers/{post-id}/`. Other posts keep their existing permalinks. Previous
paper permalink paths redirect to the new URL, and historical URL-only view
logs are counted alongside the new URLs.

The paper template is `single-paper.php`. Existing posts and pages assigned
the former `single-papers.php` template remain supported; the editor displays
that assignment as a legacy option.

Paper page, explorer, category homepage, submission, and like-control styles
are maintained in `assets/css/a1geo-papers.css`.

WordPress registers and flushes the rewrite rule once when the theme code is
deployed. Pretty permalinks must be enabled; with plain permalinks the existing
query-string URLs are retained. Clear any WordPress, server or CDN page caches
after deployment so cached listings and redirects use the new URLs. If routes
do not resolve, save **Settings > Permalinks** to refresh the rewrite rules.

## Category homepage and explorer

Assign the Paper Category Homepage and Paper Category Explorer templates to
published pages. The homepage selects the first published category-explorer
page by ID. It expects `geophysical-methods` and `application`
as direct children of the `papers` post category, and lists their immediate
children. Links open `/paper-category/{category-slug}/`; the category explorer
includes descendants, preserves the category during search, sorting and
pagination, and does not display category filter controls. Earlier
`/paper-category-results/` query links redirect to the new URL. With plain
permalinks, category links use a `paper_category` query parameter instead.
The pretty route requires the category-explorer page slug `paper-category`.
Both explorers offer 5, 10, 20, 50 or 100 papers per page (20 by default), with first,
previous, next and last page navigation. Changing the search, sort, category
or page size returns to the first page while preserving the other selections.
Both explorers show a scroll-to-top control after scrolling down.
Opening a paper from a category explorer preserves the category, search, sort,
page size and page number for the single-paper back link. Direct paper visits
and links from the general explorer retain the general explorer back link.
