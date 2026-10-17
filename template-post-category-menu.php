<?php
/**
 * Template Name: Post Category Menu
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

$papers_parent = get_category_by_slug( 'papers' );

if ( ! $papers_parent ) {
	$papers_parent = get_term_by( 'name', 'Papers', 'category' );
}

$papers_page = get_page_by_path( 'papers' );
$papers_url  = $papers_page ? get_permalink( $papers_page ) : home_url( '/papers/' );

$announcements_category = get_category_by_slug( 'announcements' );

if ( ! $announcements_category ) {
	$announcements_category = get_term_by( 'name', 'Announcements', 'category' );
}

$announcement_posts = $announcements_category
	? new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'cat'                 => $announcements_category->term_id,
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	)
	: null;

$post_categories = $papers_parent
	? get_categories(
		array(
			'parent'     => $papers_parent->term_id,
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	)
	: array();

$category_columns = array_fill( 0, 4, array() );

foreach ( $post_categories as $category_index => $category ) {
	$category_columns[ $category_index % 4 ][] = $category;
}

electro_get_header( $header_style );
?>

<style>
	.post-category-page {
		padding: 0 0 56px;
		margin-top: -35px;
	}

	.post-category-shell {
		padding-top: 32px;
	}

	.post-category-columns {
		margin-top: 5px;
		margin-bottom: 10px;
	}

	.post-category-group {
		width: calc(100% - 2em);
		margin-left: 2em;
		padding-top: 0.1em;
        padding-bottom: 0.1em;
        padding-left: 1em;
        padding-right: 1em;
		border: 1px solid #d9dde2;
		background: #fff;
	}

	.post-category-columns > .post-category-column {
		flex: 0 0 22%;
		max-width: 22%;
	}

	.post-category-columns > [class*="col-"] {
		min-height: 1px;
	}

	.post-paper-submit-link a {
		color: #0b63ce;
		font-style: italic;
	}

	.post-announcements-group {
		margin-top: 15px; /* Reduced since the title moved out */
	}

	/* Updated style to match custom-page-title hierarchy requirements */
	.post-announcements-title {
		margin: 30px 0 15px 0;
		padding: 0;
		background: transparent;
		color: #333e48;
		font-size: 2rem;
		font-weight: 700;
	}

	.post-announcements-list {
		list-style: none;
		margin: 0;
		padding: 10px 16px;
	}

	.post-announcements-list li + li {
		border-top: 1px solid #f0f1f2;
	}

	.post-announcements-list a {
		display: flex;
		justify-content: space-between;
		gap: 16px;
		padding: 10px 0;
		color: #333;
		text-decoration: none;
	}

	.post-announcements-list a:hover {
		color: #0b63ce;
	}

	.post-announcements-date {
		flex: 0 0 auto;
		color: #595959;
		font-size: 0.9rem;
		font-style: italic;
	}

	.post-announcements-empty {
		margin: 0;
		padding: 16px;
		color: #595959;
	}

	@media (max-width: 991px) {
		.post-category-columns > .post-category-column {
			flex-basis: 50%;
			max-width: 50%;
		}
	}

	@media (max-width: 575px) {
		.post-category-group {
			width: 100%;
			margin-left: 0;
		}

		.post-category-columns > .post-category-column {
			flex-basis: 100%;
			max-width: 100%;
		}
	}

</style>

<div id="primary" class="entry-content post-category-page">
	<main id="main" class="site-main">
		<?php do_action( 'electro_page_before' ); ?>

		<div class="container post-category-shell">
			<h1 class="custom-page-title">Geophysical papers</h1>

			<div class="post-category-group">
			<div class="row g-2 post-category-columns" aria-label="Post categories">
				<?php
				$category_styles = array( 'cat-instrument', 'cat-service', 'cat-application', 'cat-other' );
				foreach ( $category_columns as $column_number => $categories ) :
					$category_style = $category_styles[ $column_number ];
				?>
					<div class="col-12 col-md-6 col-lg-3 d-flex flex-column post-category-column">
						<div class="custom-brand-with-category <?php echo esc_attr( $category_style ); ?>">
							<div class="py-4 position-relative col categories">
								<ul class="mb-0 list-unstyled">
								<?php foreach ( $categories as $category ) : ?>
									<li>
										<div class="sub-cat-name">
											<a class="category-name" href="<?php echo esc_url( add_query_arg( 'category', $category->slug, $papers_url ) ); ?>">
												<?php echo esc_html( $category->name ); ?><br>
												<span class="a-list-item item-count"><?php echo esc_html( $category->count ); ?> <?php echo esc_html( _n( 'article', 'articles', $category->count, 'a1-geo' ) ); ?></span>
											</a>
										</div>
									</li>
								<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			</div>

			<?php $submit_paper_page = get_page_by_path( 'submit-paper' ); ?>
			<p class="post-paper-submit-link">
				<a href="<?php echo esc_url( $submit_paper_page ? get_permalink( $submit_paper_page ) : home_url( '/submit-paper/' ) ); ?>">
					<?php esc_html_e( 'Click here if you want to submit your paper', 'a1-geo' ); ?>
				</a>
			</p>

			<!-- Title moved out to match same structural alignment row as Geophysical Papers -->
			<h2 class="post-announcements-title">Conferences and Events</h2>

			<div class="post-category-group post-announcements-group">
				<?php if ( $announcement_posts && $announcement_posts->have_posts() ) : ?>
					<ul class="post-announcements-list">
						<?php while ( $announcement_posts->have_posts() ) : $announcement_posts->the_post(); ?>
							<li>
								<a href="<?php the_permalink(); ?>">
									<span><?php the_title(); ?></span>
									<span class="post-announcements-date"><?php echo esc_html( get_the_date() ); ?></span>
								</a>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php else : ?>
					<p class="post-announcements-empty"><?php esc_html_e( 'No announcements found.', 'a1-geo' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php wp_reset_postdata(); ?>
		<?php do_action( 'electro_page_after' ); ?>
	</main>
</div>

<?php get_footer(); ?>
