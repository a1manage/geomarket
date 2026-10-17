<?php
/**
 * Search Bar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( is_rtl() ) {
	$dir_value = 'rtl';
} else {
	$dir_value = 'ltr';
}

$navbar_search_text          = apply_filters( 'electro_navbar_search_placeholder', esc_html__( 'Search for products', 'electro' ) );
$navbar_search_dropdown_text = apply_filters( 'electro_navbar_search_dropdown_text', esc_html__( 'All Categories', 'electro' ) );
?>
<div class="col col-lg-3 col-xl-4">
<form class="navbar-search " method="get" action="<?php echo esc_url( home_url( '/all-products/?' ) ); ?>" autocomplete="off">
	<label class="sr-only screen-reader-text visually-hidden" for="search"><?php echo esc_html__( 'Search for:', 'electro' ); ?></label>
	<div class="input-group">
		<div class="input-search-field custom-input-search-field">
			<input type="text" id="search" class="form-control search-field custom-search-field" dir="<?php echo esc_attr( $dir_value ); ?>" value="" name="wdt_search" placeholder="<?php echo esc_attr( $navbar_search_text ); ?>" autocomplete="off" />
			<button type="submit" class="btn btn-secondary custom-search-btn"><i class="ec ec-search"></i></button>
		</div>				
	</div>	
</form>
</div>