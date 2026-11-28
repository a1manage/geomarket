<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function a1geo_browser_views_table() {
	global $wpdb;
	return $wpdb->prefix . 'a1geo_browser_views';
}

function a1geo_install_browser_views_table() {
	if ( '1' === get_option( 'a1geo_browser_views_schema' ) ) {
		return;
	}
	global $wpdb;
	$table = a1geo_browser_views_table();
	$sql = "CREATE TABLE $table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_id char(36) NOT NULL,
		page_url text NOT NULL,
		viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY event_id (event_id),
		KEY viewed_at (viewed_at)
	) {$wpdb->get_charset_collate()};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
		error_log( 'Browser views: failed to create table: ' . $wpdb->last_error );
		return;
	}
	update_option( 'a1geo_browser_views_started_at', current_time( 'mysql' ), false );
	update_option( 'a1geo_browser_views_schema', '1', false );
}
add_action( 'init', 'a1geo_install_browser_views_table' );

function a1geo_browser_views_has_consent() {
	if ( ! function_exists( 'wp_has_consent' ) || ! wp_has_consent( 'statistics' ) || ! isset( $_COOKIE['wpconsent_preferences'] ) || ! is_string( $_COOKIE['wpconsent_preferences'] ) ) {
		return false;
	}
	$preferences = json_decode( wp_unslash( $_COOKIE['wpconsent_preferences'] ), true );
	if ( ! is_array( $preferences ) || ! isset( $preferences['statistics'] ) || true !== $preferences['statistics'] ) {
		return false;
	}
	if ( isset( $_SERVER['HTTP_SEC_GPC'] ) && '1' === $_SERVER['HTTP_SEC_GPC'] && ( ! isset( $preferences['respect_gpc'] ) || false !== $preferences['respect_gpc'] ) ) {
		return false;
	}
	return true;
}

function a1geo_record_browser_view() {
	if ( ! a1geo_browser_views_has_consent() ) {
		wp_send_json_error( array( 'message' => 'Statistics consent is required.' ), 403 );
	}
	$event_id = isset( $_POST['event_id'] ) && is_string( $_POST['event_id'] ) ? wp_unslash( $_POST['event_id'] ) : '';
	$path = isset( $_POST['path'] ) && is_string( $_POST['path'] ) ? wp_unslash( $_POST['path'] ) : '';
	if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $event_id ) ||
		'' === $path || strlen( $path ) > 2048 || '/' !== $path[0] || str_starts_with( $path, '//' ) ||
		preg_match( '/[?#\\\\\x00-\x20\x7f]/', $path ) ) {
		wp_send_json_error( array( 'message' => 'Invalid browser view.' ), 400 );
	}
	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? untrailingslashit( $home_path ) : '';
	if ( '' !== $home_path && $path !== $home_path && ! str_starts_with( $path, $home_path . '/' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid browser view path.' ), 400 );
	}
	$origin = wp_parse_url( home_url( '/' ) );
	if ( ! is_array( $origin ) || empty( $origin['scheme'] ) || empty( $origin['host'] ) ) {
		error_log( 'Browser views: invalid site URL.' );
		wp_send_json_error( array( 'message' => 'Browser view tracking is unavailable.' ), 500 );
	}
	$base = $origin['scheme'] . '://' . $origin['host'] . ( isset( $origin['port'] ) ? ':' . $origin['port'] : '' );
	$page_url = esc_url_raw( $base . ( '/' === $path ? '' : untrailingslashit( $path ) ) );
	global $wpdb;
	if ( '1' !== get_option( 'a1geo_browser_views_schema' ) ) {
		error_log( 'Browser views: tracking table is unavailable.' );
		wp_send_json_error( array( 'message' => 'Browser view tracking is unavailable.' ), 503 );
	}
	$table = a1geo_browser_views_table();
	$result = $wpdb->query( $wpdb->prepare(
		"INSERT INTO `$table` (event_id, page_url, viewed_at) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE event_id = VALUES(event_id)",
		$event_id,
		$page_url,
		current_time( 'mysql' )
	) );
	if ( false === $result ) {
		error_log( 'Browser views: failed to record event: ' . $wpdb->last_error );
		wp_send_json_error( array( 'message' => 'Browser view could not be recorded.' ), 500 );
	}
	wp_send_json_success();
}
add_action( 'wp_ajax_a1geo_browser_view', 'a1geo_record_browser_view' );
add_action( 'wp_ajax_nopriv_a1geo_browser_view', 'a1geo_record_browser_view' );

function a1geo_enqueue_browser_view_tracker() {
	if ( ! function_exists( 'wp_has_consent' ) ) {
		return;
	}
	wp_enqueue_script( 'a1geo-browser-views', get_stylesheet_directory_uri() . '/assets/js/browser-views.js', array( 'wp-consent-api' ), '1.0.0', true );
	wp_localize_script( 'a1geo-browser-views', 'a1geoBrowserViews', array( 'endpoint' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'a1geo_enqueue_browser_view_tracker' );
