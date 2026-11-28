<?php
/**
 * Template Name: Paper Category Homepage
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

$papers = get_category_by_slug( 'papers' );
$explorer_pages = get_posts( array(
	'post_type' => 'page',
	'post_status' => 'publish',
	'meta_key' => '_wp_page_template',
	'meta_value' => 'template-paper-category-explorer.php',
	'posts_per_page' => 1,
	'orderby' => 'ID',
	'order' => 'ASC',
) );
$explorer_url = $explorer_pages ? get_permalink( $explorer_pages[0]->ID ) : false;
$sections = array(
	array( 'slug' => 'geophysical-methods', 'title' => __( 'Geophysical Methods', 'a1-geo' ), 'image' => 'assets/img/cate_instrument.jpg', 'class' => 'cat-instrument' ),
	array( 'slug' => 'application', 'title' => __( 'Application', 'a1-geo' ), 'image' => 'assets/img/cate_applications.jpg', 'class' => 'cat-application' ),
);
electro_get_header( $header_style );
?>
<div id="primary" class="entry-content">
	<main id="main" class="site-main paper-category-home">
		<?php do_action( 'electro_page_before' ); ?>
		<div class="stretch-full-width slider-block no-lazyload paper-category-banner">
			<div class="container">
				<div class="pb-4 pt-3">
					<div class="row justify-content-end">
						<div class="col-12 col-sm-12 col-md-6 col-lg-6 pt-3">
							<p class="home-p-1"><b><?php esc_html_e( 'Papers related Geophysical methods and theirs applications', 'a1-geo' ); ?></b></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if ( ! $papers || ! $explorer_url ) : ?>
			<p><?php esc_html_e( 'Paper categories are unavailable. Please contact the site administrator.', 'a1-geo' ); ?></p>
		<?php else : ?>
			<div class="row g-2 gx-2 row-cols-1 row-cols-md-2 paper-category-sections">
				<?php foreach ( $sections as $section ) : ?>
					<?php
					$parent = get_category_by_slug( $section['slug'] );
					$children = array();
					$section_error = false;
					if ( ! $parent || (int) $parent->parent !== (int) $papers->term_id ) {
						$section_error = true;
						error_log( 'Paper homepage: missing or incorrectly parented category ' . $section['slug'] );
					} else {
						$children = get_terms( array( 'taxonomy' => 'category', 'parent' => $parent->term_id, 'hide_empty' => false, 'orderby' => 'name' ) );
						if ( is_wp_error( $children ) ) {
							error_log( 'Paper homepage: could not load categories: ' . $children->get_error_message() );
							$section_error = true;
							$children = array();
						}
					}
					?>
					<div class="col d-flex flex-column">
						<div class="custom-brand-with-category <?php echo esc_attr( $section['class'] ); ?>">
							<div class="category-title"><h2 class="h4 me-2 mb-0 fw-bold cat-title"><?php echo esc_html( $section['title'] ); ?></h2></div>
							<div class="category-image"><img class="img-fluid" src="<?php echo esc_url( get_theme_file_uri( $section['image'] ) ); ?>" alt="" loading="lazy"></div>
							<div class="py-4 position-relative col categories">
								<?php if ( $section_error ) : ?>
									<p><?php esc_html_e( 'This category is currently unavailable.', 'a1-geo' ); ?></p>
								<?php else : ?>
									<ul class="mb-0 list-unstyled">
										<?php foreach ( $children as $child ) : ?>
											<li><div class="sub-cat-name"><a class="category-name" href="<?php echo esc_url( a1geo_paper_category_url( $child->slug, $explorer_url ) ); ?>"><?php echo esc_html( $child->name ); ?></a></div></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php do_action( 'electro_page_after' ); ?>
	</main>
</div>
<?php get_footer(); ?>
