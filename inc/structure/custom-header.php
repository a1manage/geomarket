<?php

if ( ! function_exists( 'additional_electro_enqueue_scripts' ) ) {
	/**
	 * Enqueues all scripts used by the theme
	 */
	function additional_electro_enqueue_scripts() {

		global $electro_version;		
		
		// ZZZZZZZ - Added a1geo-acf js - Huy Nguyen 18/04/2025
		wp_enqueue_script(
			'a1geo-acf',
			get_stylesheet_directory_uri() . '/assets/js/a1geo-acf.js',
			array( 'jquery', 'wp-hooks' ),
			filemtime( get_stylesheet_directory() . '/assets/js/a1geo-acf.js' ),
			true
		);
		
		$electro_options = apply_filters(
			'electro_localize_script_data',
			array(
				'rtl'                     => is_rtl() ? '1' : '0',				
				'ajax_loader_url'         => get_template_directory_uri() . '/assets/images/ajax-loader.gif',
				'enable_sticky_header'    => apply_filters( 'electro_enable_sticky_header', true ),
				'enable_hh_sticky_header' => apply_filters( 'electro_enable_hh_sticky_header', false ),
				'enable_live_search'      => apply_filters( 'electro_enable_live_search', false ),
				'live_search_limit'       => apply_filters( 'electro_live_search_limit', 10 ),
				'live_search_template'    => apply_filters( 'electro_live_search_template', '<a href="{{url}}" class="media live-search-media"><img src="{{image}}" class="media-left media-object flip float-start" height="60" width="60"><div class="media-body"><p>{{{value}}}</p></div></a>' ),
				'live_search_empty_msg'   => apply_filters( 'electro_live_search_empty_msg', esc_html__( 'Unable to find any products that match the current query', 'electro' ) ),
				'deal_countdown_text'     => apply_filters(
					'electro_deal_countdown_timer_clock_text',
					array(
						'days_text'  => esc_html__( 'Days', 'electro' ),
						'hours_text' => esc_html__( 'Hours', 'electro' ),
						'mins_text'  => esc_html__( 'Mins', 'electro' ),
						'secs_text'  => esc_html__( 'Secs', 'electro' ),
					)
				),
				'typeahead_options'       => array(
					'hint'      => false,
					'highlight' => true,
				),
				'offcanvas_mcs_options'   => array(
					'axis'               => 'y',
					'theme'              => 'minimal-dark',
					'contentTouchScroll' => 100,
					'scrollInertia'      => 1500,
				),
			)
		);
		wp_localize_script( 'electro-js', 'electro_options', $electro_options );
	}
}
?>
<?php
// ZZZZZZZZZZZZ - Add new function to add javascript
if ( ! function_exists( 'additional_vendor_registration_scripts' ) ) {
	/**
	 * Enqueues script to select radio button
	 */
	function additional_vendor_registration_scripts() {
		global $electro_version;
		// ZZZZZZZ - Added a1geo-vendor-registration js - Huy Nguyen 10/05/2025
		wp_enqueue_script( 'a1geo-vendor-registration', get_stylesheet_directory_uri() . '/assets/js/a1geo-vendor-registration.js', array( 'jquery' ), $electro_version, true );
	}
}
?>