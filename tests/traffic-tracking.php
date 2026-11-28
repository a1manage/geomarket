<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );
class WP_List_Table {}
class WP_Error {
    public function __construct( ...$args ) {}
}
function add_action( ...$args ) {}
function __( $text, $domain = null ) { return $text; }
function esc_sql( $text ) { return $text; }
function wp_unslash( $text ) { return stripslashes( $text ); }
function wp_has_consent( $category ) { return $GLOBALS['consent']; }
function current_time( $type ) { return '2026-12-01 00:01:00'; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path ) { return 'https://example.test' . $path; }
function untrailingslashit( $value ) { return rtrim( $value, '/' ); }
function esc_url_raw( $url ) { return $url; }
function get_option( $name ) { return '1'; }
function wp_send_json_success() { throw new RuntimeException( 'success' ); }
function wp_send_json_error( $data, $status ) { throw new RuntimeException( 'Unexpected error: ' . $status ); }

require dirname( __DIR__ ) . '/includes/a1geo-browser-views.php';
require dirname( __DIR__ ) . '/includes/a1-mgt-page-view-summary.php';

function expect( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

$GLOBALS['consent'] = true;
$_COOKIE['wpconsent_preferences'] = '{"statistics":true}';
expect( a1geo_browser_views_has_consent(), 'Explicit statistics consent should be accepted.' );
$_SERVER['HTTP_SEC_GPC'] = '1';
expect( ! a1geo_browser_views_has_consent(), 'GPC should prevent tracking.' );
$_COOKIE['wpconsent_preferences'] = '{"statistics":true,"respect_gpc":false}';
expect( a1geo_browser_views_has_consent(), 'An explicit GPC override should be respected.' );
unset( $_SERVER['HTTP_SEC_GPC'] );
$GLOBALS['consent'] = false;
expect( ! a1geo_browser_views_has_consent(), 'Consent API denial should prevent tracking.' );
unset( $_COOKIE['wpconsent_preferences'] );
$GLOBALS['consent'] = true;
expect( ! a1geo_browser_views_has_consent(), 'Missing consent preference should prevent tracking.' );

expect( a1geo_classify_page_view( '192.0.2.1', 'IngeniousBranLeadResearch/1.0' )['bot'], 'Lead research signature should be flagged.' );
expect( a1geo_classify_page_view( '192.0.2.1', 'Chrome-Lighthouse' )['bot'], 'Lighthouse signature should be flagged.' );
expect( ! a1geo_classify_page_view( '192.0.2.1', 'Mozilla/5.0 Firefox/131.0' )['bot'], 'Browser signature is not proof of a bot.' );

class Traffic_Test_DB {
    public $last_error = '';
    public $rows = array();
    public $prefix = 'wp_';
    public $prepared_args = array();
    public function esc_like( $text ) { return $text; }
    public function prepare( $query, ...$args ) { $this->prepared_args = $args; return $query; }
    public function query( $query ) { return 1; }
    public function get_col( $query ) { return array( 'page_access_logs_2026_11' ); }
    public function get_results( $query, $format ) { return $this->rows; }
}
$wpdb = new Traffic_Test_DB();
function traffic_test_event( $ip, $url, $time ) {
    return array( 'ip_address' => $ip, 'user_agent' => 'browser', 'page_url' => $url, 'accessed_at' => $time );
}
for ( $i = 0; $i < 19; $i++ ) {
    $wpdb->rows[] = traffic_test_event( 'a', '/page-' . $i, '2026-11-27 12:00:00' );
}
$wpdb->rows[] = traffic_test_event( 'a', '/page-19', '2026-11-27 12:01:00' );
for ( $i = 0; $i < 19; $i++ ) {
    $wpdb->rows[] = traffic_test_event( 'b', '/page-' . $i, '2026-11-27 12:00:00' );
}
$wpdb->rows[] = traffic_test_event( 'b', '/page-19', '2026-11-27 12:01:01' );
for ( $i = 0; $i < 20; $i++ ) {
    $wpdb->rows[] = traffic_test_event( 'c', '/same', '2026-11-27 12:00:00' );
}
for ( $i = 0; $i < 19; $i++ ) {
    $wpdb->rows[] = traffic_test_event( 'd', '/cross-' . $i, '2026-11-26 23:59:30' );
}
$wpdb->rows[] = traffic_test_event( 'd', '/cross-19', '2026-11-27 00:00:00' );
for ( $i = 0; $i < 20; $i++ ) {
    $wpdb->rows[] = array( 'ip_address' => 'e', 'user_agent' => 'bingbot', 'page_url' => '/bot-burst-' . $i, 'accessed_at' => '2026-11-27 13:00:00' );
}
$bursts = a1geo_page_view_burst_counts( '2026-11-27', '2026-11-27' );
expect( ! ( $bursts instanceof WP_Error ), 'Burst calculation should succeed.' );
expect( 41 === $bursts['dates']['2026-11-27'], 'All burst requests, including known bots, should be flagged.' );
expect( 21 === $bursts['non_bot_dates']['2026-11-27'], 'Only non-bot burst requests should be subtracted from the non-bot total.' );
expect( 1 === $bursts['pages']['/cross-19'], 'Cross-midnight burst attribution should be correct.' );
expect( 1 === $bursts['non_bot_pages']['/cross-19'], 'Non-bot burst page attribution should be correct.' );
expect( ! isset( $bursts['non_bot_pages']['/bot-burst-0'] ), 'Known-bot bursts must not be subtracted a second time.' );

$_COOKIE['wpconsent_preferences'] = '{"statistics":true}';
$_POST = array( 'event_id' => '12345678-1234-4234-8234-123456789abc', 'path' => '/test/' );
try {
    a1geo_record_browser_view();
    throw new RuntimeException( 'Expected a JSON response.' );
} catch ( RuntimeException $error ) {
    expect( 'success' === $error->getMessage(), $error->getMessage() );
}
expect( array( '12345678-1234-4234-8234-123456789abc', 'https://example.test/test', '2026-12-01 00:01:00' ) === $wpdb->prepared_args, 'Browser views should use the WordPress local timestamp.' );
echo "Traffic tracking tests passed.\n";
