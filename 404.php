<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );

$header_style = 'v11';

if ( function_exists( 'electro_acf_home_v11_get_header_style' ) ) {
	$home_v11_header_option = electro_acf_home_v11_get_header_style();
	if ( $home_v11_header_option ) {
		$header_style = $home_v11_header_option;
	}
}

electro_get_header( $header_style ); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<div class="info-404 text-center" style="padding: 60px 15px;">
			<h1 class="display-3"><?php esc_html_e( '404!', 'electro' ); ?></h1>
			<p class="lead"><?php esc_html_e( 'Sorry, the page you are looking for could not be found.', 'electro' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return to Home Page', 'electro' ); ?></a>
			</p>
		</div>
	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer();
