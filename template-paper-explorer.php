<?php
/**
 * Template Name: Paper Explorer
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );
do_action( 'electro_before_homepage_v11' );

$category_only = isset( $a1geo_category_only ) && true === $a1geo_category_only;
$category_home_pages = get_posts( array(
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'meta_key'       => '_wp_page_template',
	'meta_value'     => 'template-paper-homepage.php',
	'posts_per_page' => 1,
	'orderby'        => 'ID',
	'order'          => 'ASC',
) );
$category_home_url = $category_home_pages ? get_permalink( $category_home_pages[0]->ID ) : false;
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

$category_route = $category_only && a1geo_is_paper_category_route();
$category_input = $category_route ? get_query_var( 'a1geo_category_slug' ) : ( isset( $_GET['paper_category'] ) ? wp_unslash( $_GET['paper_category'] ) : array() );
$category_input = is_string( $category_input ) ? ( '' === $category_input ? array() : array( $category_input ) ) : $category_input;
$invalid_category = ! is_array( $category_input ) || ( $category_only && ( ! is_array( $category_input ) || count( $category_input ) !== 1 ) );
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
$per_page_options = array( '5', '10', '20', '50', '100' );
$per_page_input   = isset( $_GET['paper_per_page'] ) ? wp_unslash( $_GET['paper_per_page'] ) : '20';
$invalid_per_page = ! is_string( $per_page_input ) || ! in_array( $per_page_input, $per_page_options, true );
$per_page         = $invalid_per_page ? 20 : (int) $per_page_input;
$page_url         = get_permalink();
if ( $category_route && ! $invalid_category && count( $selected_categories ) === 1 ) {
	$page_url = a1geo_paper_category_url( reset( $selected_categories )->slug, $page_url );
}
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
	$page_number = min( $page_number, max( 1, (int) ceil( $total / $per_page ) ) );
	$rows  = array_slice( $rows, ( $page_number - 1 ) * $per_page, $per_page );
}

electro_get_header( $header_style );
?>


<div id="primary" class="content-area paper-explorer<?php echo $category_only ? ' is-category-only' : ''; ?>">
	<main id="main" class="site-main">
		<?php do_action( 'electro_page_before' ); ?>
		<h1 class="custom-page-title"><?php echo esc_html( $category_only && count( $selected_categories ) === 1 ? reset( $selected_categories )->name : get_the_title() ); ?></h1>
		<?php if ( ! $papers_category ) : ?>
			<p class="paper-explorer-notice"><?php esc_html_e( 'The Papers category is not available. Please contact the site administrator.', 'a1-geo' ); ?></p>
		<?php elseif ( $category_error ) : ?>
			<p class="paper-explorer-notice"><?php esc_html_e( 'Paper categories could not be loaded. Please try again later.', 'a1-geo' ); ?></p>
		<?php else : ?>
			<form class="paper-explorer-search" role="search" method="get" action="<?php echo esc_url( $page_url ); ?>">
				<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
				<?php if ( ! $category_route ) : foreach ( $selected_categories as $category ) : ?><input type="hidden" name="paper_category[]" value="<?php echo esc_attr( $category->slug ); ?>"><?php endforeach; endif; ?>
				<?php if ( ! $invalid_sort ) : ?><input type="hidden" name="paper_sort" value="<?php echo esc_attr( $sort ); ?>"><?php endif; ?>
				<input type="hidden" name="paper_per_page" value="<?php echo esc_attr( $per_page ); ?>">
				<div class="paper-explorer-search-controls">
					<input type="search" id="paper-search" name="paper_search" aria-label="<?php esc_attr_e( 'Search papers', 'a1-geo' ); ?>" value="<?php echo esc_attr( $search ); ?>">
					<button type="submit"><?php esc_html_e( 'Search', 'a1-geo' ); ?></button>
				</div>
			</form>
			<div class="paper-explorer-layout<?php echo $category_only ? ' is-category-only' : ''; ?>">
				<?php if ( ! $category_only ) : ?>
				<aside class="paper-explorer-sidebar" aria-labelledby="paper-explorer-category-title">
					<h2 id="paper-explorer-category-title"><?php esc_html_e( 'Categories', 'a1-geo' ); ?></h2>
					<label for="paper-category-filter"><?php esc_html_e( 'Search categories', 'a1-geo' ); ?></label>
					<input type="search" id="paper-category-filter" autocomplete="off">
					<form id="paper-category-form" method="get" action="<?php echo esc_url( $page_url ); ?>">
						<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
						<?php if ( '' !== $search ) : ?><input type="hidden" name="paper_search" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
						<?php if ( ! $invalid_sort ) : ?><input type="hidden" name="paper_sort" value="<?php echo esc_attr( $sort ); ?>"><?php endif; ?>
						<input type="hidden" name="paper_per_page" value="<?php echo esc_attr( $per_page ); ?>">
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
				<?php endif; ?>
				<section aria-label="<?php esc_attr_e( 'Paper results', 'a1-geo' ); ?>">
					<?php if ( $category_home_url ) : ?>
						<p class="paper-explorer-back"><a href="<?php echo esc_url( $category_home_url ); ?>"><?php esc_html_e( 'Back to categories', 'a1-geo' ); ?></a></p>
					<?php else : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'Paper categories are currently unavailable.', 'a1-geo' ); ?></p>
					<?php endif; ?>
					<?php if ( $invalid_category ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The selected paper category could not be found.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_sort ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The selected sort option is unavailable. Please choose another.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_page ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The requested page number is invalid.', 'a1-geo' ); ?></p>
					<?php elseif ( $invalid_search ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The search term is invalid. Please try another search.', 'a1-geo' ); ?></p>
					<?php endif; ?>
					<?php if ( $invalid_per_page ) : ?>
						<p class="paper-explorer-notice"><?php esc_html_e( 'The requested page size is invalid. Showing 20 papers per page.', 'a1-geo' ); ?></p>
					<?php endif; ?>
					<div class="paper-explorer-toolbar">
						<form method="get" action="<?php echo esc_url( $page_url ); ?>">
							<?php foreach ( array( 'page_id', 'p' ) as $page_key ) : if ( isset( $page_url_args[ $page_key ] ) && is_scalar( $page_url_args[ $page_key ] ) ) : ?><input type="hidden" name="<?php echo esc_attr( $page_key ); ?>" value="<?php echo esc_attr( $page_url_args[ $page_key ] ); ?>"><?php endif; endforeach; ?>
							<?php if ( ! $category_route ) : foreach ( $selected_categories as $category ) : ?><input type="hidden" name="paper_category[]" value="<?php echo esc_attr( $category->slug ); ?>"><?php endforeach; endif; ?>
							<?php if ( '' !== $search ) : ?><input type="hidden" name="paper_search" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
							<div class="paper-explorer-toolbar-group">
								<label for="paper-sort"><?php esc_html_e( 'Sort by', 'a1-geo' ); ?></label>
								<select id="paper-sort" name="paper_sort">
									<option value="date" <?php selected( $sort, 'date' ); ?>><?php esc_html_e( 'Published date', 'a1-geo' ); ?></option>
									<?php if ( $views_available ) : ?><option value="views" <?php selected( $sort, 'views' ); ?>><?php esc_html_e( 'Views', 'a1-geo' ); ?></option><?php endif; ?>
									<?php if ( $likes_available ) : ?><option value="likes" <?php selected( $sort, 'likes' ); ?>><?php esc_html_e( 'Likes', 'a1-geo' ); ?></option><?php endif; ?>
								</select>
							</div>
							<div class="paper-explorer-toolbar-group">
								<label for="paper-per-page"><?php esc_html_e( 'Papers per page', 'a1-geo' ); ?></label>
								<select id="paper-per-page" name="paper_per_page">
									<?php foreach ( $per_page_options as $option ) : ?>
										<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $per_page, $option ); ?>><?php echo esc_html( number_format_i18n( $option ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<noscript><button type="submit"><?php esc_html_e( 'Apply', 'a1-geo' ); ?></button></noscript>
						</form>
					</div>
					<?php if ( ! $likes_available ) : ?><p class="paper-explorer-notice"><?php esc_html_e( 'Like counts and sorting by likes are unavailable.', 'a1-geo' ); ?></p><?php endif; ?>
					<?php if ( ! $views_available ) : ?><p class="paper-explorer-notice"><?php esc_html_e( 'Some view counts and sorting by views are unavailable.', 'a1-geo' ); ?></p><?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$paper_id = $row['id'];
						$paper_url = get_permalink( $paper_id );
						if ( $category_only && count( $selected_categories ) === 1 ) {
							$return_args = array(
								'paper_return_category' => reset( $selected_categories )->slug,
								'paper_return_page'     => $page_number,
								'paper_return_per_page' => $per_page,
								'paper_return_sort'     => $sort,
							);
							if ( '' !== $search ) {
								$return_args['paper_return_search'] = rawurlencode( $search );
							}
							$paper_url = add_query_arg( $return_args, $paper_url );
						}
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
								<?php if ( has_post_thumbnail( $paper_id ) ) : ?><a class="paper-explorer-image" href="<?php echo esc_url( $paper_url ); ?>"><?php echo get_the_post_thumbnail( $paper_id, 'medium' ); ?></a><?php endif; ?>
								<div class="paper-explorer-content-text">
									<h2><a href="<?php echo esc_url( $paper_url ); ?>"><?php echo esc_html( get_the_title( $paper_id ) ); ?></a></h2>
									<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $paper_id ) ), 55, '…' ) ); ?></p>
									<?php if ( $document ) : ?><a class="paper-explorer-pdf-link" href="<?php echo esc_url( $document ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View PDF', 'a1-geo' ); ?></a><?php endif; ?>
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
					<p class="paper-explorer-total"><?php echo esc_html( sprintf( _n( '%s paper', '%s papers', $total, 'a1-geo' ), number_format_i18n( $total ) ) ); ?></p>
					<?php
					$pages = (int) ceil( $total / $per_page );
					$args  = array( 'paper_sort' => $sort, 'paper_per_page' => $per_page );
					if ( $selected_categories && ! $category_route ) {
						$args['paper_category'] = array_keys( $selected_categories );
					}
					if ( '' !== $search ) {
						$args['paper_search'] = urlencode( $search );
					}
					?>
					<?php if ( $pages > 1 || $page_number > 1 ) : ?>
						<nav class="paper-explorer-pagination" aria-label="<?php esc_attr_e( 'Paper pagination', 'a1-geo' ); ?>">
							<?php if ( $page_number > 1 ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => 1 ) ), $page_url ) ); ?>"><span aria-hidden="true">&lt;&lt;</span> <?php esc_html_e( 'First', 'a1-geo' ); ?></a>
								<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => $page_number - 1 ) ), $page_url ) ); ?>"><span aria-hidden="true">&lt;</span> <?php esc_html_e( 'Prev', 'a1-geo' ); ?></a>
							<?php else : ?>
								<span aria-disabled="true"><span aria-hidden="true">&lt;&lt;</span> <?php esc_html_e( 'First', 'a1-geo' ); ?></span>
								<span aria-disabled="true"><span aria-hidden="true">&lt;</span> <?php esc_html_e( 'Prev', 'a1-geo' ); ?></span>
							<?php endif; ?>
							<span aria-current="page"><?php echo esc_html( sprintf( __( 'Page %1$s of %2$s', 'a1-geo' ), number_format_i18n( $page_number ), number_format_i18n( $pages ) ) ); ?></span>
							<?php if ( $page_number < $pages ) : ?>
								<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => $page_number + 1 ) ), $page_url ) ); ?>"><?php esc_html_e( 'Next', 'a1-geo' ); ?> <span aria-hidden="true">&gt;</span></a>
								<a href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'paper_page' => $pages ) ), $page_url ) ); ?>"><?php esc_html_e( 'Last', 'a1-geo' ); ?> <span aria-hidden="true">&gt;&gt;</span></a>
							<?php else : ?>
								<span aria-disabled="true"><?php esc_html_e( 'Next', 'a1-geo' ); ?> <span aria-hidden="true">&gt;</span></span>
								<span aria-disabled="true"><?php esc_html_e( 'Last', 'a1-geo' ); ?> <span aria-hidden="true">&gt;&gt;</span></span>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
					<?php if ( $category_home_url ) : ?>
						<p class="paper-explorer-back"><a href="<?php echo esc_url( $category_home_url ); ?>"><?php esc_html_e( 'Back to categories', 'a1-geo' ); ?></a></p>
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
		const sort = document.getElementById('paper-sort');
		if (sort) sort.addEventListener('change', function () {
			sort.form.requestSubmit();
		});
		const perPage = document.getElementById('paper-per-page');
		if (perPage) perPage.addEventListener('change', function () {
			perPage.form.requestSubmit();
		});
		if (!input) return;
		const items = document.querySelectorAll('.paper-explorer-category');
		const noMatch = document.getElementById('paper-category-no-match');
		const categoryForm = document.getElementById('paper-category-form');
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
