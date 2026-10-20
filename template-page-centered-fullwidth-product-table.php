<?php
/**
 * Template Name: Centered Fullwidth Product Table
 *
 * @package electro
 */

// Dynamically output metadata using the page slug as the category slug
add_action( 'wp_head', function() {
    global $post;
    if ( ! $post ) return;

    $slug = $post->post_name; // Page slug matching category
    $page_title = esc_attr( get_the_title() );
    $page_url   = esc_url( get_permalink() );

    // Fetch primary product image for the category (if available)
    $image_url = '';
    $category  = get_term_by( 'slug', $slug, 'product_cat' );
    
    if ( $category ) {
        $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
        if ( $thumbnail_id ) {
            $image_url = wp_get_attachment_url( $thumbnail_id );
        }
    }
    
    // Fallback to post featured image if category image doesn't exist
    if ( ! $image_url && has_post_thumbnail( $post->ID ) ) {
        $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
    }
    
    $image_url = esc_url( $image_url );
    ?>
    <!-- Robots meta for large image preview in Google Search -->
    <meta name="robots" content="max-image-preview:large" />

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo $page_title; ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo $page_url; ?>" />
    <?php if ( $image_url ) : ?>
	<meta property="og:image" content="<?php echo $image_url; ?>" />
	<meta property="og:image:secure_url" content="<?php echo $image_url; ?>" />
    <?php endif; ?>

    <!-- Structured Data Schema -->
    <?php if ( $image_url ) : ?>
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "CollectionPage",
		"name": "<?php echo $page_title; ?>",
		"url": "<?php echo $page_url; ?>",
		"image": "<?php echo $image_url; ?>",
		"primaryImageOfPage": {
			"@type": "ImageObject",
			"url": "<?php echo $image_url; ?>"
		}
	}
	</script>
    <?php endif; ?>
    <?php
}, 1 );

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
			<?php $a1geo_sub_header = get_post_meta( get_the_ID(), '_a1geo_subtitle', true ); ?>
			<?php if ( '' !== $a1geo_sub_header ) : ?>
				<h2 class="custom-page-sub-header"><?php echo esc_html( $a1geo_sub_header ); ?></h2>
			<?php endif; ?>
			<div class="mobile-instruction-text" style="width:100%;float:right;color: red;"><span><b>For a better view, please rotate your phone to landscape mode.</b></span></div>			
			<!--<div style="width:100%;float:right"><span><b>Click a seller’s logo in the upper-left corner of each product list below to visit the seller’s website.</b></span></div>-->
		<?php get_template_part( 'templates/contents/content', 'page' ); ?>
			<?php if ( has_excerpt() ) : ?>
				<div class="custom-page-description">
					<?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?>
				</div>
			<?php endif; ?>
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