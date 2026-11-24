<?php
/**
 * Template Name: Paper Explorer
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );
do_action( 'electro_before_homepage_v11' );

$header_style = 'v11';
if ( function_exists( 'electro_acf_home_v11_get_header_style' ) ) {
	$header_option = electro_acf_home_v11_get_header_style();
	if ( $header_option ) {
		$header_style = $header_option;
	}
}

$papers_category = get_category_by_slug( 'papers' );
$categories      = array();
$category_error  = false;
if ( $papers_category ) {
	$categories = get_terms(
		array(
			'taxonomy'   => 'category',
			'child_of'   => $papers_category->term_id,
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);
	if ( is_wp_error( $categories ) ) {
		error_log( 'Paper Explorer: ' . $categories->get_error_message() );
		$categories     = array();
		$category_error = true;
	}
}

$category_input = isset( $_GET['paper_category'] ) ? wp_unslash( $_GET['paper_category'] ) : array();
$category_input = is_string( $category_input ) ? ( '' === $category_input ? array() : array( $category_input ) ) : $category_input;
$invalid_category = ! is_array( $category_input );
$selected_categories = array();
$categories_by_slug = array();
$categories_by_id = array();
foreach ( $categories as $category ) {
	$categories_by_slug[ $category->slug ] = $category;
	$categories_by_id[ $category->term_id ] = $category;
}
if ( ! $invalid_category ) {
	foreach ( $category_input as $slug ) {
		if ( ! is_string( $slug ) || '' === $slug || ! isset( $categories_by_slug[ sanitize_title( $slug ) ] ) ) {
			$invalid_category = true;
			break;
		}
		$slug = sanitize_title( $slug );
		$selected_categories[ $slug ] = $categories_by_slug[ $slug ];
	}
}
$likes_available  = function_exists( 'a1geo_paper_likes_available' ) && a1geo_paper_likes_available();
$views_available  = function_exists( 'a1geo_paper_view_counts' );
$invalid_search   = isset( $_GET['paper_search'] ) && ! is_string( $_GET['paper_search'] );
$search           = isset( $_GET['paper_search'] ) && ! $invalid_search ? trim( sanitize_text_field( wp_unslash( $_GET['paper_search'] ) ) ) : '';
$sort             = isset( $_GET['paper_sort'] ) ? ( is_string( $_GET['paper_sort'] ) ? sanitize_key( wp_unslash( $_GET['paper_sort'] ) ) : '' ) : 'date';
$invalid_sort     = ! in_array( $sort, array( 'date', 'views', 'likes' ), true ) || ( 'likes' === $sort && ! $likes_available ) || ( 'views' === $sort && ! $views_available );
$invalid_page     = isset( $_GET['paper_page'] ) && ( ! is_string( $_GET['paper_page'] ) || ! ctype_digit( $_GET['paper_page'] ) || 0 === (int) $_GET['paper_page'] );
$page_number      = isset( $_GET['paper_page'] ) && ! $invalid_page ? max( 1, absint( $_GET['paper_page'] ) ) : 1;
$per_page         = 10;
$page_url         = get_permalink();
$page_url_query   = wp_parse_url( $page_url, PHP_URL_QUERY );
$page_url_args    = array();
if ( is_string( $page_url_query ) ) {
	parse_str( $page_url_query, $page_url_args );
}
$rows             = array();
$total            = 0;

if ( $papers_category && ! $category_error && ! $invalid_category && ! $invalid_sort && ! $invalid_page && ! $invalid_search ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			's'                      => $search,
			'tax_query'              => array(
				array(
					'taxonomy'         => 'category',
					'field'            => 'term_id',
					'terms'            => $selected_categories ? wp_list_pluck( $selected_categories, 'term_id' ) : array( $papers_category->term_id ),
					'include_children' => true,
					'operator'         => 'IN',
				),
			),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'orderby'                => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		)
	);

	$view_counts = $views_available ? a1geo_paper_view_counts( $query->posts ) : array();
	foreach ( $query->posts as $paper_id ) {
		$views = $view_counts[ $paper_id ] ?? null;
		if ( null === $views ) {
			$views_available = false;
		}
		$like_count = $likes_available ? a1geo_paper_like_count( $paper_id ) : null;
		if ( $likes_available && null === $like_count ) {
			$likes_available = false;
		}
		$rows[] = array(
			'id'    => $paper_id,
			'views' => $views,
			'likes' => $like_count,
		);
	}

	if ( ! $likes_available ) {
		foreach ( $rows as &$row ) {
			$row['likes'] = null;
		}
		unset( $row );
		if ( 'likes' === $sort ) {
			$invalid_sort = true;
			$rows = array();
		}
	}
	if ( ! $views_available && 'views' === $sort ) {
		$invalid_sort = true;
		$rows = array();
	}
	if ( 'date' !== $sort && ! $invalid_sort ) {
		usort(
			$rows,
			static function ( $a, $b ) use ( $sort ) {
				$comparison = $b[ $sort ] <=> $a[ $sort ];
				if ( 0 !== $comparison ) {
					return $comparison;
				}
				$date_comparison = strcmp( get_post_field( 'post_date', $b['id'] ), get_post_field( 'post_date', $a['id'] ) );
				return 0 !== $date_comparison ? $date_comparison : $b['id'] <=> $a['id'];
			}
		);
	}
	$total = count( $rows );
	$rows  = array_slice( $rows, ( $page_number - 1 ) * $per_page, $per_page );
}

electro_get_header( $header_style );
?>
<style>
	.paper-explorer { padding: 24px 0 56px; }
	.paper-explorer-search { display: flex; align-items: stretch; margin: 0 0 24px; padding: 24px; border: 1px solid #d9d4d4; background: #f6f4f4; }
	.paper-explorer-search input[type="search"] { flex: 1 1 auto; min-width: 0; height: 44px; margin: 0; padding: 8px 12px; border: 1px solid #282624; border-radius: 0; background: #fff; color: #282624; }
	.paper-explorer-search button { flex: 0 0 auto; height: 44px; margin: 0; padding: 8px 18px; border: 1px solid #282624; border-left: 0; border-radius: 0; background: transparent; color: #282624; }
	.paper-explorer-search button:hover { background: #e8e4e4; }
	.paper-explorer-search input:focus-visible, .paper-explorer-search button:focus-visible { outline: 2px solid #1d5089; outline-offset: 2px; }
	.paper-explorer-layout { display: grid; grid-template-columns: minmax(200px, 260px) minmax(0, 1fr); gap: 28px; }
	.paper-explorer-sidebar { border: 1px solid #d9dde2; background: #f7f9fa; padding: 18px; align-self: start; }
	.paper-explorer-sidebar h2 { font-size: 1.15rem; margin: 0 0 12px; }
	.paper-explorer-sidebar input[type="search"] { width: 100%; margin-bottom: 12px; }
	.paper-explorer-categories { list-style: none; padding: 0; margin: 0; }
	.paper-explorer-categories li { padding: 6px 0; }
	.paper-explorer-categories label { display: flex; align-items: baseline; gap: 8px; margin: 0; }
	.paper-explorer-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; border-bottom: 1px solid #d9dde2; padding-bottom: 14px; }
	.paper-explorer-toolbar form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin: 0; }
	.paper-explorer-card { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 24px; padding: 22px; margin-top: 18px; background: #fff; border: 1px solid #e3e5e8; border-radius: 6px; box-shadow: 0 2px 6px rgba(0, 0, 0, .1); }
	.paper-explorer-card h2 { font-size: 1.25rem; margin: 0 0 8px; }
	.paper-explorer-card p { margin: 0 0 10px; }
	.paper-explorer-content { display: flex; align-items: flex-start; gap: 16px; min-width: 0; }
	.paper-explorer-content-text { flex: 1 1 auto; min-width: 0; }
	.paper-explorer-meta { min-width: 0; padding-left: 20px; border-left: 1px solid #d9dde2; color: #555; font-size: .9rem; overflow-wrap: anywhere; }
	.paper-explorer-meta-list { display: grid; grid-template-columns: max-content minmax(0, 1fr); column-gap: 8px; row-gap: 8px; margin: 0 0 8px; }
	.paper-explorer-meta-list dt, .paper-explorer-meta-list dd { margin: 0; font-weight: normal; min-width: 0; }
	.paper-explorer-meta-list dd { font-weight: bold; }
	.paper-explorer-views { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px; }
	.paper-explorer-views-icon { width: 20px; height: 20px; vertical-align: middle; fill: none; stroke: currentColor; stroke-width: 1.8; }
	.paper-explorer-meta-item { display: block; margin-bottom: 8px; }
	.paper-explorer-image { display: block; flex: 0 0 135px; max-width: 30%; }
	.paper-explorer-image img { width: 100%; height: auto; object-fit: contain; }
	.paper-explorer-pagination { display: flex; gap: 16px; margin-top: 24px; }
	.paper-explorer-notice { padding: 14px; background: #f7f9fa; border: 1px solid #d9dde2; margin-top: 16px; }
	.a1geo-paper-like-form { display: grid; gap: 8px; max-width: 420px; margin-top: 10px; }
	.a1geo-paper-like-form p { font-size: .9rem; }
	@media (min-width: 768px) and (max-width: 1100px) {
		.paper-explorer-card { grid-template-columns: 1fr; gap: 16px; }
		.paper-explorer-meta { padding: 16px 0 0; border-left: 0; border-top: 1px solid #d9dde2; }
	}
	@media (max-width: 767px) {
		.paper-explorer-layout { grid-template-columns: 1fr; }
		.paper-explorer-card { grid-template-columns: 1fr; gap: 16px; }
		.paper-explorer-meta { padding: 16px 0 0; border-left: 0; border-top: 1px solid #d9dde2; }
	}
</style>

<div id="primary" class="content-area paper-explorer">
	<main id="main" class="site-main">
		<?php do_action( 'electro_page_before' ); ?>
		<h1 class="custom-page-title"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php if ( ! $papers_category ) : ?>
			<p class="paper-explorer-notice"><?php esc_html_e( 'The Papers category is not available. Please contact the site administrator.', 'a1-geo' ); ?></p>
		<?php elseif ( $category_error ) : ?>
			<p class="paper-explorer-notice"><?php esc_html_e( 'Paper categories could not be loaded. Please try again later.', 'a1-geo' ); ?></p>
		<?php else : ?>
			<form class="paper-explorer-search" role="search" method="get" action="<?php echo esc_url( $page_url ); ?>">
				<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
				<?php foreach ( $selected_categories as $category ) : ?><input type="hidden" name="paper_category[]" value="<?php echo esc_attr( $category->slug ); ?>"><?php endforeach; ?>
				<?php if ( ! $invalid_sort ) : ?><input type="hidden" name="paper_sort" value="<?php echo esc_attr( $sort ); ?>"><?php endif; ?>
				<input type="search" id="paper-search" name="paper_search" aria-label="<?php esc_attr_e( 'Search papers', 'a1-geo' ); ?>" value="<?php echo esc_attr( $search ); ?>">
				<button type="submit"><?php esc_html_e( 'Search', 'a1-geo' ); ?></button>
			</form>
			<div class="paper-explorer-layout">
				<aside class="paper-explorer-sidebar" aria-labelledby="paper-explorer-category-title">
					<h2 id="paper-explorer-category-title"><?php esc_html_e( 'Categories', 'a1-geo' ); ?></h2>
					<label for="paper-category-filter"><?php esc_html_e( 'Search categories', 'a1-geo' ); ?></label>
					<input type="search" id="paper-category-filter" autocomplete="off">
					<form id="paper-category-form" method="get" action="<?php echo esc_url( $page_url ); ?>">
						<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
						<?php if ( '' !== $search ) : ?><input type="hidden" name="paper_search" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
						<?php if ( ! $invalid_sort ) : ?><input type="hidden" name="paper_sort" value="<?php echo esc_attr( $sort ); ?>"><?php endif; ?>
						<ul class="paper-explorer-categories">
							<?php foreach ( $categories as $category ) : ?>
								<li class="paper-explorer-category">
									<label><input type="checkbox" name="paper_category[]" value="<?php echo esc_attr( $category->slug ); ?>" <?php checked( isset( $selected_categories[ $category->slug ] ) ); ?>> <span><?php echo esc_html( $category->name ); ?></span></label>
								</li>
							<?php endforeach; ?>
						</ul>
						<p id="paper-category-no-match" hidden><?php esc_html_e( 'No matching categories.', 'a1-geo' ); ?></p>
						<noscript><button type="submit"><?php esc_html_e( 'Filter', 'a1-geo' ); ?></button></noscript>
					</form>
				</aside>
				<section aria-label="<?php esc_attr_e( 'Paper results', 'a1-geo' ); ?>">
					<?php if ( $invalid_category ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The selected paper category could not be found.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_sort ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The selected sort option is unavailable. Please choose another.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_page ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The requested page number is invalid.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_search ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The search term is invalid. Please try another search.', 'a1-geo' ); ?></p>
					<?php endif; ?>
					<div class="paper-explorer-toolbar">
						<span><?php echo esc_html( sprintf( _n( '%s paper', '%s papers', $total, 'a1-geo' ), number_format_i18n( $total ) ) ); ?></span>
						<form method="get" action="<?php echo esc_url( $page_url ); ?>">
							<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
							<?php foreach ( $selected_categories as $category ) : ?><input type="hidden" name="paper_category[]" value="<?php echo esc_attr( $category->slug ); ?>"><?php endforeach; ?>
							<?php if ( '' !== $search ) : ?><input type="hidden" name="paper_search" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
							<label for="paper-sort"><?php esc_html_e( 'Sort by', 'a1-geo' ); ?></label>
							<select id="paper-sort" name="paper_sort">
								<option value="date" <?php selected( $sort, 'date' ); ?>><?php esc_html_e( 'Published date', 'a1-geo' ); ?></option>
								<?php if ( $views_available ) : ?><option value="views" <?php selected( $sort, 'views' ); ?>><?php esc_html_e( 'Views', 'a1-geo' ); ?></option><?php endif; ?>
								<?php if ( $likes_available ) : ?><option value="likes" <?php selected( $sort, 'likes' ); ?>><?php esc_html_e( 'Likes', 'a1-geo' ); ?></option><?php endif; ?>
							</select>
							<noscript><button type="submit"><?php esc_html_e( 'Sort', 'a1-geo' ); ?></button></noscript>
						</form>
					</div>
					<?php if ( ! $likes_available ) : ?><p class="paper-explorer-notice"><?php esc_html_e( 'Like counts and sorting by likes are unavailable.', 'a1-geo' ); ?></p><?php endif; ?>
					<?php if ( ! $views_available ) : ?><p class="paper-explorer-notice"><?php esc_html_e( 'Some view counts and sorting by views are unavailable.', 'a1-geo' ); ?></p><?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$paper_id = $row['id'];
						$author   = function_exists( 'get_field' ) ? get_field( 'author', $paper_id ) : get_post_meta( $paper_id, 'author', true );
						if ( $author instanceof WP_User ) {
							$author = $author->display_name;
						} elseif ( is_array( $author ) ) {
							$author = $author['display_name'] ?? $author['name'] ?? '';
						}
						$author = is_scalar( $author ) ? (string) $author : '';
						if ( '' === $author ) {
							$author = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $paper_id ) );
						}
						$document = function_exists( 'get_field' ) ? get_field( 'attach_document', $paper_id ) : get_post_meta( $paper_id, 'attach_document', true );
						if ( is_array( $document ) ) {
							$document = $document['url'] ?? '';
						} elseif ( is_numeric( $document ) ) {
							$document = wp_get_attachment_url( absint( $document ) );
						}
						$document = is_string( $document ) ? $document : '';
						$paper_categories = wp_get_post_terms( $paper_id, 'category', array( 'fields' => 'ids' ) );
						$paper_category_names = array();
						if ( is_wp_error( $paper_categories ) ) {
							error_log( 'Paper Explorer: could not retrieve paper categories: ' . $paper_categories->get_error_message() );
							$paper_category_names[] = __( 'Unavailable', 'a1-geo' );
						} else {
							foreach ( $paper_categories as $term_id ) {
								if ( isset( $categories_by_id[ $term_id ] ) ) {
									$paper_category_names[] = $categories_by_id[ $term_id ]->name;
								}
							}
							if ( ! $paper_category_names && in_array( (int) $papers_category->term_id, array_map( 'intval', $paper_categories ), true ) ) {
								$paper_category_names[] = $papers_category->name;
							}
						}
						?>
						<article class="paper-explorer-card">
							<div class="paper-explorer-content">
								<?php if ( has_post_thumbnail( $paper_id ) ) : ?><a class="paper-explorer-image" href="<?php echo esc_url( get_permalink( $paper_id ) ); ?>"><?php echo get_the_post_thumbnail( $paper_id, 'medium' ); ?></a><?php endif; ?>
								<div class="paper-explorer-content-text">
									<h2><a href="<?php echo esc_url( get_permalink( $paper_id ) ); ?>"><?php echo esc_html( get_the_title( $paper_id ) ); ?></a></h2>
									<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $paper_id ) ), 55, '…' ) ); ?></p>
									<?php if ( $document ) : ?><a href="<?php echo esc_url( $document ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View PDF', 'a1-geo' ); ?></a><?php endif; ?>
								</div>
							</div>
							<div class="paper-explorer-meta">
								<dl class="paper-explorer-meta-list">
									<dt><?php esc_html_e( 'Category:', 'a1-geo' ); ?></dt><dd><?php echo esc_html( implode( ', ', $paper_category_names ) ); ?></dd>
									<dt><?php esc_html_e( 'Author:', 'a1-geo' ); ?></dt><dd><?php echo esc_html( $author ); ?></dd>
									<dt><?php esc_html_e( 'Published:', 'a1-geo' ); ?></dt><dd><?php echo esc_html( get_the_date( '', $paper_id ) ); ?></dd>
								</dl>
								<div class="paper-explorer-views"><span class="screen-reader-text"><?php esc_html_e( 'Views:', 'a1-geo' ); ?></span><svg class="paper-explorer-views-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg><span><?php echo esc_html( null === $row['views'] ? __( 'Unavailable', 'a1-geo' ) : number_format_i18n( $row['views'] ) ); ?></span></div>
								<?php if ( function_exists( 'a1geo_render_paper_like_control' ) ) : ?>
									<?php a1geo_render_paper_like_control( $paper_id, $row['likes'] ); ?>
								<?php else : ?>
									<span class="paper-explorer-meta-item"><?php echo esc_html( sprintf( __( 'Likes: %s', 'a1-geo' ), null === $row['likes'] ? __( 'Unavailable', 'a1-geo' ) : number_format_i18n( $row['likes'] ) ) ); ?></span>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
					<?php if ( ! $rows && ! $invalid_category && ! $invalid_sort && ! $invalid_page && ! $invalid_search ) : ?><p class="paper-explorer-notice"><?php esc_html_e( 'No papers found on this page.', 'a1-geo' ); ?></p><?php endif; ?>
					<?php
					$pages = (int) ceil( $total / $per_page );
					$args  = array( 'paper_sort' => $sort );
					if ( $selected_categories ) {
						$args['paper_category'] = array_keys( $selected_categories );
					}
					if ( '' !== $search ) {
						$args['paper_search'] = urlencode( $search );
					}
					?>
					<?php if ( $pages > 1 || $page_number > 1 ) : ?>
						<nav class="paper-explorer-pagination" aria-label="<?php esc_attr_e( 'Paper pagination', 'a1-geo' ); ?>">
							<?php if ( $page_number > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => $page_number - 1 ) ), $page_url ) ); ?>"><?php esc_html_e( 'Previous', 'a1-geo' ); ?></a><?php endif; ?>
							<span><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'a1-geo' ), $page_number, $pages ) ); ?></span>
							<?php if ( $page_number < $pages ) : ?><a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => $page_number + 1 ) ), $page_url ) ); ?>"><?php esc_html_e( 'Next', 'a1-geo' ); ?></a><?php endif; ?>
						</nav>
					<?php endif; ?>
				</section>
			</div>
		<?php endif; ?>
		<?php do_action( 'electro_page_after' ); ?>
	</main>
</div>
<script>
	(function () {
		const input = document.getElementById('paper-category-filter');
		if (!input) return;
		const items = document.querySelectorAll('.paper-explorer-category');
		const noMatch = document.getElementById('paper-category-no-match');
		const categoryForm = document.getElementById('paper-category-form');
		const sort = document.getElementById('paper-sort');
		sort.addEventListener('change', function () {
			sort.form.requestSubmit();
		});
		categoryForm.addEventListener('change', function (event) {
			if (event.target.type === 'checkbox') categoryForm.requestSubmit();
		});
		input.addEventListener('input', function () {
			const query = input.value.trim().toLocaleLowerCase();
			let visible = 0;
			items.forEach(function (item) {
				const match = item.textContent.toLocaleLowerCase().includes(query);
				item.hidden = !match;
				if (match) visible++;
			});
			noMatch.hidden = visible !== 0;
		});
	}());
</script>
<?php get_footer(); ?>
