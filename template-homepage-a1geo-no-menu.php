<?php
/**
 * Template Name: A1Geo empty homepage
 *
 * @package electro
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
	<style>#content.site-content { margin-top: 0; }</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="off-canvas-wrapper w-100 position-relative">
	<div id="page" class="hfeed site">
		<div id="content" class="site-content" tabindex="-1">
			<div class="container">
				<div id="primary" class="entry-content">
					<main id="main" class="site-main">
						<?php
						while ( have_posts() ) :
							the_post();
							the_content();
						endwhile;
						?>
					</main>
				</div>
			</div>
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>