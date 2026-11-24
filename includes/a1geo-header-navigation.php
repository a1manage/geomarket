<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function a1geo_paper_explorer_url() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'template-paper-explorer.php',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	) );
	return $pages ? get_permalink( $pages[0]->ID ) : false;
}

if ( ! function_exists( 'electro_header_user_account' ) ) {
	function electro_header_user_account() {
		if ( ! apply_filters( 'electro_enable_header_user_account', false ) ) {
			return;
		}

		$my_account_page_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		$papers_url = current_user_can( 'manage_options' ) ? a1geo_paper_explorer_url() : false;
		$header_tooltip_placement = apply_filters( 'electro_header_tooltip_placement', 'bottom' );
		?>
		<div class="custom-menu-hide header-icon header-icon__user-account dropdown animate-dropdown" <?php if ( $header_tooltip_placement ) : ?>data-bs-toggle="tooltip" data-bs-placement="<?php echo esc_attr( $header_tooltip_placement ); ?>" data-bs-title="<?php echo esc_attr( esc_html__( 'Menu', 'electro' ) ); ?>"<?php endif; ?>>
			<a class="dropdown-toggle" href="<?php echo esc_url( $my_account_page_url ); ?>" data-bs-toggle="dropdown" aria-label="<?php echo esc_attr( esc_html__( 'Menu', 'electro' ) ); ?>"><i class="ec ec-menu" aria-hidden="true"></i></a>
			<ul class="dropdown-menu dropdown-menu-user-account">
				<li class="menu-item"><a title="Home" href="/" style="color: #333e48 !important;">Home</a></li>
				<?php if ( $papers_url ) : ?>
					<li class="menu-item"><a href="<?php echo esc_url( $papers_url ); ?>" style="color: #333e48 !important;"><?php esc_html_e( 'Papers', 'a1-geo' ); ?></a></li>
				<?php endif; ?>
				<li class="menu-item"><a title="Feedback" href="/contact" style="color: #333333 !important;">Feedback</a></li>
				<?php if ( is_user_logged_in() ) : ?>
					<?php electro_user_account_nav_menu_fallback(); ?>
				<?php else : ?>
					<li class="menu-item"><a style="color: #333333 !important;" href="<?php echo esc_url( $my_account_page_url ); ?>"><?php echo esc_html__( 'Sign in', 'electro' ); ?></a></li>
					<li class="menu-item"><a style="color: #333333 !important;" href="<?php echo esc_url( $my_account_page_url ); ?>?action=register"><?php echo esc_html__( 'Join us', 'electro' ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<div class="custom-nav-hide primary-nav-menu nav nav-inline">
			<div class="custom-menu-item"><a title="Home" href="/" style="color: #333e48 !important;">Home</a></div>
			<?php if ( $papers_url ) : ?>
				<div class="custom-menu-item"><a href="<?php echo esc_url( $papers_url ); ?>" style="color: #333e48 !important;"><?php esc_html_e( 'Papers', 'a1-geo' ); ?></a></div>
			<?php endif; ?>
			<div class="custom-menu-item"><a title="Feedback" href="/contact" style="color: #333333 !important;">Feedback</a></div>
			<?php if ( is_user_logged_in() ) : ?>
				<?php electro_user_account_nav_menu_fallback_custom(); ?>
			<?php else : ?>
				<div class="custom-menu-item"><a style="color: #333333 !important;" href="<?php echo esc_url( $my_account_page_url ); ?>"><?php echo esc_html__( 'Sign in', 'electro' ); ?></a></div>
				<div class="custom-menu-item"><a style="color: #333333 !important;" href="<?php echo esc_url( $my_account_page_url ); ?>?action=register"><?php echo esc_html__( 'Join us', 'electro' ); ?></a></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
