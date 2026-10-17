<?php
/**
 * Template Name: Centered Fullwidth Product Table
 *
 * @package electro
 */

remove_action( 'electro_content_top', 'electro_breadcrumb', 10 );

do_action( 'electro_before_homepage_v11' );

$home_v11_header_option	= electro_acf_home_v11_get_header_style();
$header_style	= 'v11';

if ( $home_v11_header_option ) {
	$header_style = $home_v11_header_option;
}

electro_get_header( $header_style ); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php 
			do_action( 'electro_page_before' );
			
		?>
			<h1 class="custom-page-title"><?php the_title() ?></h1>
			<div class="mobile-instruction-text" style="width:100%;float:right;color: red;"><span><b>For a better view, please rotate your phone to landscape mode.</b></span></div>			
			<!--<div style="width:100%;float:right"><span><b>Click a seller’s logo in the upper-left corner of each product list below to visit the seller’s website.</b></span></div>-->
		<?php get_template_part( 'templates/contents/content', 'page' );			
		?>			
		<?php	
			/**
			 * @hooked electro_display_comments - 10
			 */
			do_action( 'electro_page_after' );
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

	<?php 
get_footer(); ?>
