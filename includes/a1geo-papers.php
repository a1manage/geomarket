<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function a1geo_paper_legacy_template_hierarchy( $templates ) {
	foreach ( $templates as &$template ) {
		if ( 'single-papers.php' === $template ) {
			$template = 'single-paper.php';
		}
	}
	unset( $template );
	return $templates;
}
add_filter( 'single_template_hierarchy', 'a1geo_paper_legacy_template_hierarchy' );
add_filter( 'page_template_hierarchy', 'a1geo_paper_legacy_template_hierarchy' );

function a1geo_paper_legacy_template_option( $templates ) {
	$templates['single-papers.php'] = __( 'Papers Custom Template (legacy)', 'a1-geo' );
	return $templates;
}
add_filter( 'theme_post_templates', 'a1geo_paper_legacy_template_option' );
add_filter( 'theme_page_templates', 'a1geo_paper_legacy_template_option' );

function a1geo_paper_rewrite_rules() {
	add_rewrite_rule( '^papers/([1-9][0-9]*)/?$', 'index.php?p=$matches[1]&a1geo_paper_route=1', 'top' );
	add_rewrite_rule( '^paper-category/([^/]+)/?$', 'index.php?pagename=paper-category&a1geo_category_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^paper-category-results/?$', 'index.php?pagename=paper-category', 'top' );
}
add_action( 'init', 'a1geo_paper_rewrite_rules' );

function a1geo_paper_refresh_rewrite_rules() {
	if ( '3' === get_option( 'a1geo_paper_permalink_version' ) || ! get_option( 'permalink_structure' ) ) {
		return;
	}
	flush_rewrite_rules( false );
	$rules = get_option( 'rewrite_rules' );
	if ( is_array( $rules ) && isset( $rules['^papers/([1-9][0-9]*)/?$'], $rules['^paper-category/([^/]+)/?$'], $rules['^paper-category-results/?$'] ) ) {
		update_option( 'a1geo_paper_permalink_version', '3', false );
	} else {
		error_log( 'Paper permalinks: could not register the rewrite rule. Check the permalink settings.' );
	}
}
add_action( 'wp_loaded', 'a1geo_paper_refresh_rewrite_rules', 20 );

function a1geo_paper_query_vars( $vars ) {
	$vars[] = 'a1geo_paper_route';
	$vars[] = 'a1geo_category_slug';
	return $vars;
}
add_filter( 'query_vars', 'a1geo_paper_query_vars' );

function a1geo_paper_category_url( $slug, $explorer_url ) {
	if ( ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( 'paper_category', $slug, $explorer_url );
	}
	return home_url( user_trailingslashit( 'paper-category/' . $slug, 'page' ) );
}

function a1geo_is_paper_category_route() {
	global $wp;
	return isset( $wp->matched_rule ) && '^paper-category/([^/]+)/?$' === $wp->matched_rule;
}

function a1geo_paper_category_route() {
	if ( ! a1geo_is_paper_category_route() ) {
		return;
	}
	$slug = get_query_var( 'a1geo_category_slug' );
	$term = is_string( $slug ) ? get_category_by_slug( $slug ) : false;
	$papers = get_category_by_slug( 'papers' );
	if ( ! is_page_template( 'template-paper-category-explorer.php' ) || ! $term || ! $papers
		|| ! in_array( (int) $papers->term_id, get_ancestors( $term->term_id, 'category' ), true ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'a1geo_paper_category_route', 0 );

function a1geo_paper_category_canonical_redirect( $redirect ) {
	return a1geo_is_paper_category_route() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'a1geo_paper_category_canonical_redirect' );

function a1geo_paper_category_canonical_url( $canonical, $post ) {
	if ( a1geo_is_paper_category_route() && ! is_404() ) {
		return a1geo_paper_category_url( get_query_var( 'a1geo_category_slug' ), get_permalink( $post ) );
	}
	return $canonical;
}
add_filter( 'get_canonical_url', 'a1geo_paper_category_canonical_url', 10, 2 );

function a1geo_paper_category_redirect_legacy() {
	global $wp;
	if ( ! isset( $wp->matched_rule, $_SERVER['REQUEST_METHOD'] )
		|| ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ), true )
		|| ! is_page_template( 'template-paper-category-explorer.php' )
		|| a1geo_is_paper_category_route() || ! get_option( 'permalink_structure' ) ) {
		return;
	}
	$legacy_route = '^paper-category-results/?$' === $wp->matched_rule;
	$args = wp_unslash( $_GET );
	$input = isset( $args['paper_category'] ) ? $args['paper_category'] : '';
	$input = is_array( $input ) && count( $input ) === 1 ? reset( $input ) : $input;
	$term = is_string( $input ) ? get_category_by_slug( $input ) : false;
	$papers = get_category_by_slug( 'papers' );
	$valid = $term && $papers && in_array( (int) $papers->term_id, get_ancestors( $term->term_id, 'category' ), true );
	if ( ! $valid && ! $legacy_route ) {
		return;
	}
	$url = get_permalink();
	if ( $valid ) {
		$url = a1geo_paper_category_url( $term->slug, $url );
		unset( $args['paper_category'] );
	}
	if ( $args ) {
		$url = add_query_arg( $args, $url );
	}
	wp_safe_redirect( $url, 301, 'A1 GeoMarket' );
	exit;
}
add_action( 'template_redirect', 'a1geo_paper_category_redirect_legacy', 1 );

function a1geo_paper_permalink( $permalink, $post, $leavename ) {
	if ( $leavename || ! get_option( 'permalink_structure' ) || ! a1geo_is_public_paper( $post->ID ) ) {
		return $permalink;
	}
	return home_url( user_trailingslashit( 'papers/' . $post->ID, 'single' ) );
}
add_filter( 'post_link', 'a1geo_paper_permalink', 20, 3 );

function a1geo_paper_original_permalink( $post_id ) {
	remove_filter( 'post_link', 'a1geo_paper_permalink', 20 );
	try {
		return get_permalink( $post_id );
	} finally {
		add_filter( 'post_link', 'a1geo_paper_permalink', 20, 3 );
	}
}

function a1geo_paper_validate_route() {
	if ( ! get_query_var( 'a1geo_paper_route' ) ) {
		return;
	}
	if ( ! is_single() || ! a1geo_is_public_paper( get_queried_object_id() ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'a1geo_paper_validate_route', 0 );

function a1geo_paper_no_invalid_redirect( $redirect ) {
	return get_query_var( 'a1geo_paper_route' ) && is_404() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'a1geo_paper_no_invalid_redirect' );

function a1geo_paper_redirect_old_permalink() {
	if ( ! is_single() || get_query_var( 'a1geo_paper_route' ) || ! get_option( 'permalink_structure' )
		|| ! isset( $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] )
		|| ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ), true ) || is_preview() ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! a1geo_is_public_paper( $post_id ) ) {
		return;
	}
	$old_url = a1geo_paper_original_permalink( $post_id );
	$old_path = wp_parse_url( $old_url, PHP_URL_PATH );
	$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	if ( ! is_string( $old_path ) || ! is_string( $request_path )
		|| untrailingslashit( $old_path ) !== untrailingslashit( $request_path ) ) {
		return;
	}
	$url = get_permalink( $post_id );
	if ( ! empty( $_GET ) ) {
		$url = add_query_arg( wp_unslash( $_GET ), $url );
	}
	wp_safe_redirect( $url, 301, 'A1 GeoMarket' );
	exit;
}
add_action( 'template_redirect', 'a1geo_paper_redirect_old_permalink', 1 );

function a1geo_paper_likes_table() {
	global $wpdb;
	return $wpdb->prefix . 'a1geo_paper_likes';
}

function a1geo_paper_likes_install() {
	global $wpdb;
	$schema = get_option( 'a1geo_paper_likes_schema' );
	$secret = get_option( 'a1geo_paper_likes_secret' );
	if ( ! is_string( $secret ) || strlen( $secret ) < 32 ) {
		if ( '1' === $schema ) {
			$table = a1geo_paper_likes_table();
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
			if ( null === $count || (int) $count > 0 ) {
				error_log( 'Paper likes: the identity key is missing and cannot be safely regenerated.' );
				return;
			}
		}
		try {
			$secret = bin2hex( random_bytes( 32 ) );
		} catch ( Exception $exception ) {
			error_log( 'Paper likes: could not generate the identity key: ' . $exception->getMessage() );
			return;
		}
		if ( ! add_option( 'a1geo_paper_likes_secret', $secret, '', false ) ) {
			$secret = get_option( 'a1geo_paper_likes_secret' );
			if ( ! is_string( $secret ) || strlen( $secret ) < 32 ) {
				error_log( 'Paper likes: could not save the identity key.' );
				return;
			}
		}
	}
	if ( '1' === $schema ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = a1geo_paper_likes_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE $table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		email_hash char(64) NOT NULL,
		confirmed tinyint(1) NOT NULL DEFAULT 0,
		token_hash char(64) DEFAULT NULL,
		pending_action varchar(6) DEFAULT NULL,
		expires_at bigint(20) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY paper_email (post_id,email_hash),
		KEY paper_confirmed (post_id,confirmed),
		KEY expires_at (expires_at)
	) $charset;" );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		update_option( 'a1geo_paper_likes_schema', '1', false );
	} else {
		error_log( 'Paper likes: could not create the likes table: ' . $wpdb->last_error );
	}
}
add_action( 'init', 'a1geo_paper_likes_install' );

function a1geo_paper_rate_table() {
	global $wpdb;
	return $wpdb->prefix . 'a1geo_paper_rate_limits';
}

function a1geo_paper_rate_install() {
	global $wpdb;
	if ( '1' === get_option( 'a1geo_paper_rate_schema' ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = a1geo_paper_rate_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE $table (
		identity_hash char(64) NOT NULL,
		window_start bigint(20) unsigned NOT NULL,
		requests int(10) unsigned NOT NULL,
		PRIMARY KEY  (identity_hash),
		KEY window_start (window_start)
	) $charset;" );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		update_option( 'a1geo_paper_rate_schema', '1', false );
	} else {
		error_log( 'Paper likes: could not create rate-limit table: ' . $wpdb->last_error );
	}
}
add_action( 'init', 'a1geo_paper_rate_install' );

function a1geo_paper_rate_limit( $scope, $identity, $limit ) {
	global $wpdb;
	if ( '1' !== get_option( 'a1geo_paper_rate_schema' ) || '' === $identity ) {
		return null;
	}
	$table = a1geo_paper_rate_table();
	$key = hash_hmac( 'sha256', $scope . ':' . $identity, get_option( 'a1geo_paper_likes_secret' ) );
	$now = time();
	$result = $wpdb->query( $wpdb->prepare(
		"INSERT INTO $table (identity_hash, window_start, requests) VALUES (%s, %d, 1)
		ON DUPLICATE KEY UPDATE requests = IF(window_start <= %d, 1, requests + 1),
		window_start = IF(window_start <= %d, %d, window_start)",
		$key, $now, $now - HOUR_IN_SECONDS, $now - HOUR_IN_SECONDS, $now
	) );
	if ( false === $result ) {
		error_log( 'Paper likes: could not update rate limit: ' . $wpdb->last_error );
		return null;
	}
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT requests FROM $table WHERE identity_hash = %s", $key ) );
	if ( null === $count ) {
		error_log( 'Paper likes: could not read rate limit: ' . $wpdb->last_error );
		return null;
	}
	return (int) $count <= $limit;
}

function a1geo_paper_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

function a1geo_paper_turnstile_configured() {
	return defined( 'A1GEO_TURNSTILE_SITE_KEY' ) && is_string( A1GEO_TURNSTILE_SITE_KEY ) && '' !== A1GEO_TURNSTILE_SITE_KEY
		&& defined( 'A1GEO_TURNSTILE_SECRET_KEY' ) && is_string( A1GEO_TURNSTILE_SECRET_KEY ) && '' !== A1GEO_TURNSTILE_SECRET_KEY;
}

function a1geo_paper_verify_turnstile( $token ) {
	if ( ! a1geo_paper_turnstile_configured() || '' === $token || strlen( $token ) > 2048 ) {
		return false;
	}
	$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
		'timeout' => 10,
		'body' => array( 'secret' => A1GEO_TURNSTILE_SECRET_KEY, 'response' => $token ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		error_log( 'Paper likes: Turnstile verification service unavailable.' );
		return false;
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$hostname = wp_parse_url( home_url(), PHP_URL_HOST );
	return is_array( $data ) && ! empty( $data['success'] ) && isset( $data['hostname'], $data['action'] ) && is_string( $data['hostname'] )
		&& is_string( $hostname ) && 0 === strcasecmp( $hostname, $data['hostname'] )
		&& 'paper_like' === $data['action'];
}

function a1geo_paper_like_assets() {
	if ( ! is_page_template( array( 'template-paper-explorer.php', 'template-paper-category-explorer.php' ) ) && ! ( is_singular( 'post' ) && a1geo_is_public_paper( get_queried_object_id() ) ) ) {
		return;
	}
	$path = get_stylesheet_directory() . '/assets/js/a1geo-paper-likes.js';
	if ( a1geo_paper_turnstile_configured() ) {
		wp_enqueue_script( 'a1geo-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), null, true );
	}
	wp_enqueue_script( 'a1geo-paper-likes', get_stylesheet_directory_uri() . '/assets/js/a1geo-paper-likes.js', array(), filemtime( $path ), true );
	wp_localize_script( 'a1geo-paper-likes', 'a1geoPaperLikes', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'liked' => __( 'You liked this paper', 'a1-geo' ),
		'notLiked' => __( 'You have not liked this paper', 'a1-geo' ),
		'statusError' => __( 'Could not check your like status.', 'a1-geo' ),
		'submitError' => __( 'Could not update your like. Please try again.', 'a1-geo' ),
		'captchaError' => __( 'Complete the verification before submitting.', 'a1-geo' ),
		'storageError' => __( 'Your email could not be saved in this browser.', 'a1-geo' ),
		'forgotten' => __( 'Saved email removed from this browser.', 'a1-geo' ),
		'countOne' => __( '%s like. Like or remove a like', 'a1-geo' ),
		'countMany' => __( '%s likes. Like or remove a like', 'a1-geo' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'a1geo_paper_like_assets' );

function a1geo_paper_explorer_scrollup( $enabled ) {
	return is_page_template( array( 'template-paper-explorer.php', 'template-paper-category-explorer.php' ) ) ? true : $enabled;
}
add_filter( 'electro_enable_scrollup', 'a1geo_paper_explorer_scrollup', 20 );

function a1geo_paper_explorer_scrollup_assets() {
	if ( ! is_page_template( array( 'template-paper-explorer.php', 'template-paper-category-explorer.php' ) ) ) {
		return;
	}
	$path = get_stylesheet_directory() . '/assets/js/a1geo-paper-scroll-top.js';
	wp_enqueue_script( 'a1geo-paper-scroll-top', get_stylesheet_directory_uri() . '/assets/js/a1geo-paper-scroll-top.js', array(), filemtime( $path ), true );
}
add_action( 'wp_enqueue_scripts', 'a1geo_paper_explorer_scrollup_assets' );

function a1geo_paper_likes_available() {
	$secret = get_option( 'a1geo_paper_likes_secret' );
	return '1' === get_option( 'a1geo_paper_likes_schema' ) && is_string( $secret ) && strlen( $secret ) >= 32;
}

function a1geo_paper_like_count( $post_id ) {
	global $wpdb;
	if ( ! a1geo_paper_likes_available() ) {
		return null;
	}
	$table = a1geo_paper_likes_table();
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE post_id = %d AND confirmed = 1", $post_id ) );
	if ( null === $count ) {
		error_log( 'Paper likes: could not read counts: ' . $wpdb->last_error );
		return null;
	}
	return (int) $count;
}

function a1geo_is_public_paper( $post_id ) {
	$post = get_post( $post_id );
	$papers = get_category_by_slug( 'papers' );
	if ( ! $post || ! $papers || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		return false;
	}
	$categories = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $categories ) ) {
		error_log( 'Paper likes: could not retrieve categories: ' . $categories->get_error_message() );
		return false;
	}
	foreach ( $categories as $category_id ) {
		if ( (int) $category_id === (int) $papers->term_id || in_array( (int) $papers->term_id, get_ancestors( $category_id, 'category' ), true ) ) {
			return true;
		}
	}
	return false;
}

function a1geo_paper_likes_page( $message, $post_id = 0, $status = 200 ) {
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	if ( $post_id && a1geo_is_public_paper( $post_id ) ) {
		$message .= '<p><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( 'Return to the paper', 'a1-geo' ) . '</a></p>';
	}
	wp_die( $message, esc_html__( 'Paper likes', 'a1-geo' ), array( 'response' => $status ) );
}

function a1geo_render_paper_like_form( $post_id ) {
	if ( ! a1geo_paper_likes_available() || ! a1geo_is_public_paper( $post_id ) ) {
		return;
	}
	if ( ! a1geo_paper_turnstile_configured() ) {
		echo '<p>' . esc_html__( 'Paper likes are unavailable until verification is configured.', 'a1-geo' ) . '</p>';
		return;
	}
	?>
	<form class="a1geo-paper-like-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="a1geo_request_paper_like">
		<input type="hidden" name="paper_id" value="<?php echo esc_attr( $post_id ); ?>">
		<?php wp_nonce_field( 'a1geo_request_paper_like_' . $post_id ); ?>
		<label for="a1geo-like-email-<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Email address', 'a1-geo' ); ?></label>
		<input id="a1geo-like-email-<?php echo esc_attr( $post_id ); ?>" type="email" name="email" maxlength="254" required>
		<label for="a1geo-like-action-<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Action', 'a1-geo' ); ?></label>
		<select id="a1geo-like-action-<?php echo esc_attr( $post_id ); ?>" name="like_action">
			<option value="add"><?php esc_html_e( 'Like this paper', 'a1-geo' ); ?></option>
			<option value="remove"><?php esc_html_e( 'Remove my like', 'a1-geo' ); ?></option>
		</select>
		<div class="a1geo-paper-turnstile" data-sitekey="<?php echo esc_attr( A1GEO_TURNSTILE_SITE_KEY ); ?>"></div>
		<button type="submit"><?php esc_html_e( 'Submit', 'a1-geo' ); ?></button>
		<button type="button" class="a1geo-paper-forget-email"><?php esc_html_e( 'Forget saved email', 'a1-geo' ); ?></button>
		<p class="a1geo-paper-like-message" role="status" aria-live="polite"></p>
		<p><?php esc_html_e( 'One like per email address per paper. Changes take effect immediately; email ownership is not verified. Your email is saved in this browser to check your like status.', 'a1-geo' ); ?></p>
		<noscript><p><?php esc_html_e( 'JavaScript is required for verification.', 'a1-geo' ); ?></p></noscript>
	</form>
	<?php
}

function a1geo_render_paper_like_control( $post_id, $likes_count ) {
	if ( null === $likes_count || ! a1geo_is_public_paper( $post_id ) ) {
		echo '<span>' . esc_html( sprintf( __( 'Likes: %s', 'a1-geo' ), null === $likes_count ? __( 'Unavailable', 'a1-geo' ) : number_format_i18n( $likes_count ) ) ) . '</span>';
		return;
	}
	?>
	<details class="a1geo-paper-like" data-paper-id="<?php echo esc_attr( $post_id ); ?>">
		<summary aria-label="<?php echo esc_attr( sprintf( _n( '%s like. Like or remove a like', '%s likes. Like or remove a like', $likes_count, 'a1-geo' ), number_format_i18n( $likes_count ) ) ); ?>" title="<?php esc_attr_e( 'Like or remove a like', 'a1-geo' ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 10v10H4V10h3Zm0 9h10.2a2 2 0 0 0 1.95-1.55l1.2-5A2 2 0 0 0 18.4 10H14l.55-3.3A2.4 2.4 0 0 0 12.2 4L7 10v9Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span aria-hidden="true"><?php echo esc_html( number_format_i18n( $likes_count ) ); ?></span>
		</summary>
		<span class="a1geo-paper-like-status" role="status" aria-live="polite"></span>
		<?php a1geo_render_paper_like_form( $post_id ); ?>
	</details>
	<?php
}

function a1geo_request_paper_like() {
	global $wpdb;
	$post_id = isset( $_POST['paper_id'] ) && is_scalar( $_POST['paper_id'] ) ? absint( $_POST['paper_id'] ) : 0;
	$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
	$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? strtolower( trim( wp_unslash( $_POST['email'] ) ) ) : '';
	$action = isset( $_POST['like_action'] ) && is_string( $_POST['like_action'] ) ? wp_unslash( $_POST['like_action'] ) : '';
	if ( ! a1geo_paper_likes_available() || ! a1geo_paper_turnstile_configured() ) {
		a1geo_paper_like_response( __( 'Paper likes are currently unavailable. Please try again later.', 'a1-geo' ), $post_id, 503 );
	}
	$ip_allowed = a1geo_paper_rate_limit( 'submit-ip', a1geo_paper_client_ip(), 10 );
	if ( null === $ip_allowed ) {
		a1geo_paper_like_response( __( 'Like requests are temporarily unavailable. Please try again later.', 'a1-geo' ), $post_id, 503 );
	}
	if ( ! $ip_allowed ) {
		a1geo_paper_like_response( __( 'Too many requests. Please try again later.', 'a1-geo' ), $post_id, 429 );
	}
	if ( ! wp_verify_nonce( $nonce, 'a1geo_request_paper_like_' . $post_id ) || ! a1geo_is_public_paper( $post_id ) || ! is_email( $email ) || strlen( $email ) > 254 || ! in_array( $action, array( 'add', 'remove' ), true ) ) {
		a1geo_paper_like_response( __( 'Invalid request. Please return to the paper and try again.', 'a1-geo' ), $post_id, 400 );
	}
	$email_hash = hash_hmac( 'sha256', $email, get_option( 'a1geo_paper_likes_secret' ) );
	foreach ( array( array( 'submit-email', $email_hash, 5 ) ) as $limit ) {
		$allowed = a1geo_paper_rate_limit( $limit[0], $limit[1], $limit[2] );
		if ( null === $allowed ) {
			a1geo_paper_like_response( __( 'Like requests are temporarily unavailable. Please try again later.', 'a1-geo' ), $post_id, 503 );
		}
		if ( ! $allowed ) {
			a1geo_paper_like_response( __( 'Too many requests. Please try again later.', 'a1-geo' ), $post_id, 429 );
		}
	}
	$token = isset( $_POST['cf-turnstile-response'] ) && is_string( $_POST['cf-turnstile-response'] ) ? wp_unslash( $_POST['cf-turnstile-response'] ) : '';
	if ( ! a1geo_paper_verify_turnstile( $token ) ) {
		a1geo_paper_like_response( __( 'Verification failed. Please try again.', 'a1-geo' ), $post_id, 400 );
	}

	$table = a1geo_paper_likes_table();
	if ( 'add' === $action ) {
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (post_id, email_hash, confirmed, token_hash, pending_action, expires_at)
				VALUES (%d, %s, 1, NULL, NULL, 0)
				ON DUPLICATE KEY UPDATE confirmed = 1, token_hash = NULL, pending_action = NULL, expires_at = 0",
				$post_id, $email_hash
			)
		);
	} else {
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE post_id = %d AND email_hash = %s", $post_id, $email_hash ) );
	}
	if ( false === $result ) {
		error_log( 'Paper likes: could not update like: ' . $wpdb->last_error );
		a1geo_paper_like_response( __( 'Could not update your like. Please try again later.', 'a1-geo' ), $post_id, 500 );
	}
	$message = 'add' === $action
		? __( 'Your like has been recorded.', 'a1-geo' )
		: ( $result > 0 ? __( 'Your like has been removed.', 'a1-geo' ) : __( 'No like was found for this email address.', 'a1-geo' ) );
	a1geo_paper_like_response( $message, $post_id, 200, 'add' === $action, a1geo_paper_like_count( $post_id ) );
}
add_action( 'admin_post_a1geo_request_paper_like', 'a1geo_request_paper_like' );
add_action( 'admin_post_nopriv_a1geo_request_paper_like', 'a1geo_request_paper_like' );

function a1geo_paper_like_response( $message, $post_id, $status, $liked = false, $count = null ) {
	if ( isset( $_POST['response_format'] ) && 'json' === $_POST['response_format'] ) {
		if ( 200 === $status ) {
			wp_send_json_success( array( 'message' => $message, 'liked' => $liked, 'count' => $count ), $status );
		}
		wp_send_json_error( array( 'message' => $message ), $status );
	}
	a1geo_paper_likes_page( esc_html( $message ), $post_id, $status );
}

function a1geo_paper_like_status() {
	global $wpdb;
	nocache_headers();
	$email = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? strtolower( trim( wp_unslash( $_POST['email'] ) ) ) : '';
	$ids = isset( $_POST['paper_ids'] ) && is_array( $_POST['paper_ids'] ) ? wp_unslash( $_POST['paper_ids'] ) : array();
	if ( ( '' !== $email && ( ! is_email( $email ) || strlen( $email ) > 254 ) ) || ! $ids || count( $ids ) > 100 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid status request.', 'a1-geo' ) ), 400 );
	}
	if ( ! a1geo_paper_likes_available() ) {
		wp_send_json_error( array( 'message' => __( 'Paper likes are currently unavailable.', 'a1-geo' ) ), 503 );
	}
	$allowed = a1geo_paper_rate_limit( 'status-ip', a1geo_paper_client_ip(), 60 );
	if ( null === $allowed ) {
		wp_send_json_error( array( 'message' => __( 'Status checks are temporarily unavailable.', 'a1-geo' ) ), 503 );
	}
	if ( ! $allowed ) {
		wp_send_json_error( array( 'message' => __( 'Too many status checks. Please try again later.', 'a1-geo' ) ), 429 );
	}
	$paper_ids = array();
	$nonces = array();
	foreach ( $ids as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || ! a1geo_is_public_paper( (int) $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status request.', 'a1-geo' ) ), 400 );
		}
		$paper_ids[] = (int) $id;
		$nonces[ (int) $id ] = wp_create_nonce( 'a1geo_request_paper_like_' . (int) $id );
	}
	if ( '' === $email ) {
		wp_send_json_success( array( 'liked' => array(), 'nonces' => $nonces ) );
	}
	$table = a1geo_paper_likes_table();
	$hash = hash_hmac( 'sha256', $email, get_option( 'a1geo_paper_likes_secret' ) );
	$placeholders = implode( ',', array_fill( 0, count( $paper_ids ), '%d' ) );
	$liked = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $table WHERE email_hash = %s AND confirmed = 1 AND post_id IN ($placeholders)", array_merge( array( $hash ), $paper_ids ) ) );
	if ( $wpdb->last_error ) {
		error_log( 'Paper likes: could not check status: ' . $wpdb->last_error );
		wp_send_json_error( array( 'message' => __( 'Could not check your like status.', 'a1-geo' ) ), 500 );
	}
	wp_send_json_success( array( 'liked' => array_map( 'intval', $liked ), 'nonces' => $nonces ) );
}
add_action( 'wp_ajax_a1geo_paper_like_status', 'a1geo_paper_like_status' );
add_action( 'wp_ajax_nopriv_a1geo_paper_like_status', 'a1geo_paper_like_status' );

function a1geo_confirm_paper_like() {
	$input = 'POST' === $_SERVER['REQUEST_METHOD'] ? $_POST : $_GET;
	$post_id = isset( $input['paper_id'] ) && is_scalar( $input['paper_id'] ) ? absint( $input['paper_id'] ) : 0;
	a1geo_paper_likes_page( esc_html__( 'Email confirmation is no longer used. Please submit your like or removal again on the paper.', 'a1-geo' ), $post_id, 400 );
}
add_action( 'admin_post_a1geo_confirm_paper_like', 'a1geo_confirm_paper_like' );
add_action( 'admin_post_nopriv_a1geo_confirm_paper_like', 'a1geo_confirm_paper_like' );

function a1geo_cleanup_paper_like_requests() {
	global $wpdb;
	if ( ! a1geo_paper_likes_available() ) {
		return;
	}
	$table = a1geo_paper_likes_table();
	if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE confirmed = 0 AND expires_at < %d", time() ) ) ) {
		error_log( 'Paper likes: could not clean up expired requests: ' . $wpdb->last_error );
	}
	if ( false === $wpdb->query( $wpdb->prepare( "UPDATE $table SET token_hash = NULL, pending_action = NULL, expires_at = 0 WHERE confirmed = 1 AND expires_at > 0 AND expires_at < %d", time() ) ) ) {
		error_log( 'Paper likes: could not clear expired confirmations: ' . $wpdb->last_error );
	}
	$rate_table = a1geo_paper_rate_table();
	if ( '1' === get_option( 'a1geo_paper_rate_schema' ) && false === $wpdb->query( $wpdb->prepare( "DELETE FROM $rate_table WHERE window_start < %d", time() - DAY_IN_SECONDS ) ) ) {
		error_log( 'Paper likes: could not clean up rate limits: ' . $wpdb->last_error );
	}
}
add_action( 'a1geo_cleanup_paper_like_requests', 'a1geo_cleanup_paper_like_requests' );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'a1geo_cleanup_paper_like_requests' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'a1geo_cleanup_paper_like_requests' );
	}
} );

function a1geo_paper_view_counts( $post_ids ) {
	global $wpdb;
	$counts = array();
	$from_logs = array();
	foreach ( $post_ids as $post_id ) {
		$post_id = absint( $post_id );
		if ( function_exists( 'pvc_get_post_views' ) ) {
			$counts[ $post_id ] = absint( pvc_get_post_views( $post_id, 'total' ) );
			continue;
		}
		$found = false;
		foreach ( array( 'post_views', '_post_views', 'views', 'view_count' ) as $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );
			if ( '' !== $value && null !== $value ) {
				$counts[ $post_id ] = absint( $value );
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$from_logs[] = $post_id;
			$counts[ $post_id ] = 0;
		}
	}

	if ( ! $from_logs ) {
		return $counts;
	}

	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( 'page_access_logs_' ) . '%' ) );
	if ( $wpdb->last_error ) {
		error_log( 'Paper views: could not find access logs: ' . $wpdb->last_error );
		foreach ( $from_logs as $post_id ) {
			$counts[ $post_id ] = null;
		}
		return $counts;
	}
	foreach ( $tables as $table ) {
		if ( ! preg_match( '/^page_access_logs_[0-9]{4}_[0-9]{2}$/D', $table ) ) {
			continue;
		}
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM `$table`" );
		if ( $wpdb->last_error || ( ! in_array( 'page_id', $columns, true ) && ! in_array( 'page_url', $columns, true ) ) ) {
			error_log( 'Paper views: access log schema is unavailable: ' . $table . ' ' . $wpdb->last_error );
			foreach ( $from_logs as $post_id ) {
				$counts[ $post_id ] = null;
			}
			return $counts;
		}
		foreach ( array_chunk( $from_logs, 500 ) as $chunk ) {
			$by_id = in_array( 'page_id', $columns, true );
			$urls = array();
			if ( $by_id ) {
				$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT page_id AS identifier, COUNT(*) AS views FROM `$table` WHERE page_id IN ($placeholders) GROUP BY page_id", $chunk ) );
			} else {
				foreach ( $chunk as $post_id ) {
					$paper = a1geo_is_public_paper( $post_id );
					$paper_urls = array( get_permalink( $post_id ) );
					if ( $paper ) {
						$paper_urls[] = a1geo_paper_original_permalink( $post_id );
					}
					foreach ( array_unique( $paper_urls ) as $url ) {
						if ( ! $url ) {
							continue;
						}
						$url = untrailingslashit( $url );
						$urls[ $url ] = $post_id;
						$urls[ trailingslashit( $url ) ] = $post_id;
					}
				}
				if ( ! $urls ) {
					continue;
				}
				$placeholders = implode( ',', array_fill( 0, count( $urls ), '%s' ) );
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT BINARY page_url AS identifier, COUNT(*) AS views FROM `$table` WHERE BINARY page_url IN ($placeholders) GROUP BY BINARY page_url", array_keys( $urls ) ) );
			}
			if ( $wpdb->last_error ) {
				error_log( 'Paper views: could not read access logs: ' . $wpdb->last_error );
				foreach ( $from_logs as $post_id ) {
					$counts[ $post_id ] = null;
				}
				return $counts;
			}
			foreach ( $results as $result ) {
				$post_id = $by_id ? (int) $result->identifier : $urls[ $result->identifier ];
				$counts[ $post_id ] += (int) $result->views;
			}
		}
	}
	return $counts;
}
