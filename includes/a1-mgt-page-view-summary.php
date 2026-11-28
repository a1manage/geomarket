<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class A1_Page_View_Summary_List_Table extends WP_List_Table {

    private $table_data = array();

    public function __construct( $data = array() ) {
        parent::__construct( array(
            'singular' => __( 'Page View Metric', 'a1-geo' ),
            'plural'   => __( 'Page View Metrics', 'a1-geo' ),
            'ajax'     => false,
        ) );
        $this->table_data = $data;
    }

    public function get_columns() {
        return array(
            'page_url'        => __( 'Tracked Destination URL Link Path', 'a1-geo' ),
            'unique_visitors' => __( 'Distinct IPs (Including Bots)', 'a1-geo' ),
            'actual_clicks'   => __( 'Requests Without Known Bot Signatures', 'a1-geo' ),
            'burst_views'     => __( 'Suspicious Burst Requests', 'a1-geo' ),
            'estimated_views' => __( 'Views Excluding Bots and Bursts', 'a1-geo' ),
            'raw_views'       => __( 'Raw Views (Inc. Bots)', 'a1-geo' ),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'page_url'        => array( 'page_url', false ),
            'unique_visitors' => array( 'unique_visitors', false ),
            'actual_clicks'   => array( 'actual_clicks', true ),
            'burst_views'     => array( 'burst_views', false ),
            'estimated_views' => array( 'estimated_views', false ),
            'raw_views'       => array( 'raw_views', false ),
        );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'page_url':
                return sprintf(
                    '<strong><span class="dashicons dashicons-media-text" style="font-size:16px; margin-right:5px; color:#8c8f94; vertical-align:middle;"></span>%s</strong>',
                    esc_html( $item['page_url'] )
                );
            case 'unique_visitors':
                return '<div style="text-align:center; font-weight:500;">' . esc_html( number_format_i18n( $item['unique_visitors'] ) ) . '</div>';
            case 'actual_clicks':
                return '<div style="text-align:center; font-weight:700; color:#135e96; background:#f0f6fc; padding:4px; border-radius:4px;">' . esc_html( number_format_i18n( $item['actual_clicks'] ) ) . '</div>';
            case 'raw_views':
                return '<div style="text-align:center; font-weight:500;">' . esc_html( number_format_i18n( $item['raw_views'] ) ) . '</div>';
            case 'burst_views':
                return '<div style="text-align:center;">' . ( null === $item['burst_views'] ? esc_html__( 'Unavailable', 'a1-geo' ) : esc_html( number_format_i18n( $item['burst_views'] ) ) ) . '</div>';
            case 'estimated_views':
                return '<div style="text-align:center; font-weight:700;">' . ( null === $item['estimated_views'] ? esc_html__( 'Unavailable', 'a1-geo' ) : esc_html( number_format_i18n( $item['estimated_views'] ) ) ) . '</div>';
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Sorting Logic
        $data = $this->table_data;
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'actual_clicks';
        $order   = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'desc';

        usort( $data, function( $a, $b ) use ( $orderby, $order ) {
            $result = 0;
            if ( isset( $a[ $orderby ], $b[ $orderby ] ) ) {
                $result = ( $a[ $orderby ] < $b[ $orderby ] ) ? -1 : ( ( $a[ $orderby ] > $b[ $orderby ] ) ? 1 : 0 );
            }
            return ( 'asc' === $order ) ? $result : -$result;
        } );

        // Pagination Logic
        $per_page     = 10;
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );

        $this->items = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }
}

/**
 * Helper: Find existing monthly log tables matching 'page_access_logs_YYYY_MM'
 * within the given date range and combine them via UNION ALL subquery.
 */
function a1geo_get_monthly_logs_subquery( $start_date_str, $end_date_str ) {
    global $wpdb;

    try {
        $start_dt   = new DateTime( $start_date_str );
        $end_dt     = new DateTime( $end_date_str );
        $start_month = $start_dt->format( 'Y_m' );
        $end_month   = $end_dt->format( 'Y_m' );
    } catch ( Exception $e ) {
        return false;
    }

    $all_tables = $wpdb->get_col( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( 'page_access_logs_' ) . '%' ) );

    if ( empty( $all_tables ) ) {
        return false;
    }

    $valid_tables  = array();
    $regex_pattern = '/^page_access_logs_(\d{4}_\d{2})$/';

    foreach ( $all_tables as $table ) {
        if ( preg_match( $regex_pattern, $table, $matches ) ) {
            $table_ym = $matches[1];
            if ( $table_ym >= $start_month && $table_ym <= $end_month ) {
                $valid_tables[] = '`' . esc_sql( $table ) . '`';
            }
        }
    }

    if ( empty( $valid_tables ) ) {
        return false;
    }

    $subquery_parts = array_map( function( $t ) {
        return "SELECT id, ip_address, user_agent, page_url, accessed_at FROM $t";
    }, $valid_tables );

    return '(' . implode( ' UNION ALL ', $subquery_parts ) . ') AS combined_logs';
}

function a1geo_page_view_bot_signatures() {
    return array(
        'BV' => array( 'title' => 'BlogVault automation signature', 'terms' => array( 'blogvault' ) ),
        'M'  => array( 'title' => 'Meta crawler signature', 'terms' => array( 'facebookexternalhit', 'facebot', 'meta-webindexer' ) ),
        'B'  => array( 'title' => 'Bing crawler signature', 'terms' => array( 'bing' ) ),
        'G'  => array( 'title' => 'Google or Lighthouse signature', 'terms' => array( 'google', 'lighthouse' ) ),
        'AI' => array( 'title' => 'AI crawler signature', 'terms' => array( 'anthropic', 'claude', 'open-ai', 'openai', 'gptbot' ) ),
        'A'  => array(
            'title' => 'Automation or crawler signature',
            'terms' => array( 'crawler', 'spider', 'bot', 'headlesschrome', 'semrush', 'ahrefs', 'yandex', 'builtwith', 'ingeniousbranleadresearch', 'python-urllib', 'python-requests', 'curl/', 'wget/', 'go-http-client' ),
        ),
    );
}

/**
 * SQL predicate for requests without an identified automation signature.
 * User agents and forwarded IP addresses do not prove visitor identity.
 */
function a1geo_actual_click_sql_condition() {
    $conditions = array(
        "BINARY LOWER(TRIM(ip_address)) NOT LIKE '2a03:2880:%'",
    );
    foreach ( a1geo_page_view_bot_signatures() as $signature ) {
        foreach ( $signature['terms'] as $term ) {
            $conditions[] = "BINARY LOWER(user_agent) NOT LIKE '%" . esc_sql( $term ) . "%'";
        }
    }

    return implode( ' AND ', $conditions );
}

function a1geo_classify_page_view( $ip_address, $user_agent ) {
    $ua_lower = strtolower( (string) $user_agent );
    if ( str_starts_with( strtolower( trim( (string) $ip_address ) ), '2a03:2880:' ) ) {
        return array( 'bot' => true, 'label' => 'M', 'title' => 'Meta network range; source not independently verified', 'verified' => false );
    }
    foreach ( a1geo_page_view_bot_signatures() as $label => $signature ) {
        foreach ( $signature['terms'] as $term ) {
            if ( str_contains( $ua_lower, $term ) ) {
                return array( 'bot' => true, 'label' => $label, 'title' => $signature['title'] . ' (not independently verified)', 'verified' => false );
            }
        }
    }

    return array( 'bot' => false, 'label' => 'O', 'title' => 'No known automation signature; visitor not verified as human', 'verified' => false );
}

/**
 * Flag requests that participate in a 60-second window with 20 distinct URLs.
 * The result is a warning, not a human/bot determination.
 */
function a1geo_page_view_burst_counts( $start, $end ) {
    global $wpdb;

    $start_time = strtotime( $start . ' 00:00:00 UTC' );
    $end_time = strtotime( $end . ' 00:00:00 UTC' );
    if ( false === $start_time || false === $end_time || $end_time < $start_time ) {
        return new WP_Error( 'invalid_burst_range', __( 'Suspicious traffic could not be calculated for the selected dates.', 'a1-geo' ) );
    }
    $end_time += DAY_IN_SECONDS;
    $from = a1geo_get_monthly_logs_subquery( gmdate( 'Y-m-d', $start_time - 60 ), gmdate( 'Y-m-d', $end_time + 60 ) );
    if ( ! $from ) {
        if ( $wpdb->last_error ) {
            error_log( 'Page view bursts: could not find access logs: ' . $wpdb->last_error );
            return new WP_Error( 'burst_tables', __( 'Suspicious traffic could not be calculated.', 'a1-geo' ) );
        }
        return array( 'dates' => array(), 'pages' => array(), 'clients' => array(), 'non_bot_dates' => array(), 'non_bot_pages' => array() );
    }

    $where = $wpdb->prepare(
        'WHERE accessed_at >= %s AND accessed_at < %s',
        gmdate( 'Y-m-d H:i:s', $start_time - 60 ),
        gmdate( 'Y-m-d H:i:s', $end_time + 60 )
    );
    $rows = $wpdb->get_results(
        "SELECT ip_address, user_agent, page_url, accessed_at FROM {$from} {$where}
         ORDER BY BINARY ip_address, BINARY user_agent, accessed_at LIMIT 50001",
        ARRAY_A
    );
    if ( $wpdb->last_error || null === $rows ) {
        error_log( 'Page view bursts: could not read access logs: ' . $wpdb->last_error );
        return new WP_Error( 'burst_query', __( 'Suspicious traffic could not be calculated.', 'a1-geo' ) );
    }
    if ( count( $rows ) > 50000 ) {
        return new WP_Error( 'burst_limit', __( 'Suspicious traffic is unavailable for this range; select a shorter date range.', 'a1-geo' ) );
    }

    $counts = array( 'dates' => array(), 'pages' => array(), 'clients' => array(), 'non_bot_dates' => array(), 'non_bot_pages' => array() );
    $group = array();
    $group_key = null;
    $process = static function ( $events ) use ( &$counts, $start_time, $end_time ) {
        $size = count( $events );
        if ( 0 === $size ) {
            return;
        }
        $is_bot = a1geo_classify_page_view( $events[0]['ip_address'], $events[0]['user_agent'] )['bot'];
        $frequency = array();
        $delta = array_fill( 0, $size + 1, 0 );
        $right = 0;
        for ( $left = 0; $left < $size; $left++ ) {
            while ( $right < $size && $events[ $right ]['time'] <= $events[ $left ]['time'] + 60 ) {
                $url = $events[ $right ]['page_url'];
                $frequency[ $url ] = ( $frequency[ $url ] ?? 0 ) + 1;
                $right++;
            }
            if ( count( $frequency ) >= 20 ) {
                $delta[ $left ]++;
                $delta[ $right ]--;
            }
            $url = $events[ $left ]['page_url'];
            if ( 0 === --$frequency[ $url ] ) {
                unset( $frequency[ $url ] );
            }
        }
        $active = 0;
        foreach ( $events as $index => $event ) {
            $active += $delta[ $index ];
            if ( $active <= 0 || $event['time'] < $start_time || $event['time'] >= $end_time ) {
                continue;
            }
            $date = substr( $event['accessed_at'], 0, 10 );
            $page = $event['page_url'];
            $client = hash( 'sha256', serialize( array( $event['ip_address'], $event['user_agent'] ) ) );
            $counts['dates'][ $date ] = ( $counts['dates'][ $date ] ?? 0 ) + 1;
            $counts['pages'][ $page ] = ( $counts['pages'][ $page ] ?? 0 ) + 1;
            $counts['clients'][ $page ][ $client ] = ( $counts['clients'][ $page ][ $client ] ?? 0 ) + 1;
            if ( ! $is_bot ) {
                $counts['non_bot_dates'][ $date ] = ( $counts['non_bot_dates'][ $date ] ?? 0 ) + 1;
                $counts['non_bot_pages'][ $page ] = ( $counts['non_bot_pages'][ $page ] ?? 0 ) + 1;
            }
        }
    };
    foreach ( $rows as $row ) {
        $key = serialize( array( $row['ip_address'], $row['user_agent'] ) );
        if ( null !== $group_key && $key !== $group_key ) {
            $process( $group );
            $group = array();
        }
        $row['time'] = strtotime( $row['accessed_at'] . ' UTC' );
        if ( false === $row['time'] ) {
            error_log( 'Page view bursts: invalid access log timestamp.' );
            return new WP_Error( 'burst_timestamp', __( 'Suspicious traffic could not be calculated.', 'a1-geo' ) );
        }
        $group[] = $row;
        $group_key = $key;
    }
    $process( $group );
    return $counts;
}

function a1geo_render_browser_view_report( $start, $end, $page_url = '' ) {
    global $wpdb;
    $started = get_option( 'a1geo_browser_views_started_at' );
    if ( '1' !== get_option( 'a1geo_browser_views_schema' ) || ! is_string( $started ) || ! $started ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Browser view tracking is not available. Check the browser view table and site logs.', 'a1-geo' ) . '</p></div>';
        return;
    }
    $table = a1geo_browser_views_table();
    $where = $wpdb->prepare( 'viewed_at >= %s AND viewed_at < DATE_ADD(%s, INTERVAL 1 DAY)', $start, $end );
    if ( '' !== $page_url ) {
        $where .= $wpdb->prepare( ' AND page_url = %s', $page_url );
    }
    $rows = $wpdb->get_results( "SELECT page_url, COUNT(*) AS views FROM `$table` WHERE $where GROUP BY page_url ORDER BY views DESC", ARRAY_A );
    if ( null === $rows || $wpdb->last_error ) {
        error_log( 'Browser views: report query failed: ' . $wpdb->last_error );
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Browser views could not be loaded. Check the site logs.', 'a1-geo' ) . '</p></div>';
        return;
    }
    $total = 0;
    foreach ( $rows as $row ) {
        $total += (int) $row['views'];
    }
    ?>
    <div style="background:#fff; padding:20px; border:1px solid #ccd0d4; margin-top:20px;">
        <h3><?php esc_html_e( 'Browser Page Views (Statistics Consent)', 'a1-geo' ); ?></h3>
        <p><?php printf( esc_html__( 'Collection started at %s (database time). Earlier periods, and the part of the start day before collection, are unavailable. Counts require JavaScript and statistics consent; they do not prove a human visit and are not directly equivalent to GA4.', 'a1-geo' ), esc_html( $started ) ); ?></p>
        <?php if ( $end < substr( $started, 0, 10 ) ) : ?>
            <p><?php esc_html_e( 'Browser views are unavailable for this historical period.', 'a1-geo' ); ?></p>
        <?php else : ?>
            <p><strong><?php printf( esc_html__( 'Recorded browser page views: %s', 'a1-geo' ), esc_html( number_format_i18n( $total ) ) ); ?></strong></p>
            <?php if ( $rows ) : ?>
                <table class="widefat striped">
                    <thead><tr><th><?php esc_html_e( 'Page', 'a1-geo' ); ?></th><th><?php esc_html_e( 'Browser views', 'a1-geo' ); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr><td><?php echo esc_html( $row['page_url'] ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $row['views'] ) ); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Displays selection filters on top.
 * - Date Range: Chart aggregates views/clicks daily (dd/MM/yyyy).
 * - Month Range: Chart aggregates views/clicks for the entire month (01/MM/yyyy).
 */
function render_page_view_summary() {
    global $wpdb;

    // 1. Get chosen Filter Mode via URL parameters (defaulting to 'date')
    $filter_mode = isset( $_GET['filter_mode'] ) ? sanitize_key( $_GET['filter_mode'] ) : 'date';

    // 2. Resolve Dynamic Range Bound Enforcements & SQL Aggregation
    if ( 'date' === $filter_mode ) {
        $start_bound = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : current_time( 'Y-m-d' );
        $end_bound   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : current_time( 'Y-m-d' );
        
        $display_start = date( 'Y-m-d', strtotime( $start_bound ) );
        $display_end   = date( 'Y-m-d', strtotime( $end_bound ) );

        $sql_where = $wpdb->prepare( "WHERE DATE(accessed_at) BETWEEN %s AND %s", $display_start, $display_end );
        $sql_select_time = "DATE(accessed_at)";
        $sql_group = "GROUP BY DATE(accessed_at)";
    } else {
        $start_bound = isset( $_GET['start_month'] ) ? sanitize_text_field( $_GET['start_month'] ) : current_time( 'Y-m' );
        $end_bound   = isset( $_GET['end_month'] ) ? sanitize_text_field( $_GET['end_month'] ) : current_time( 'Y-m' );

        $display_start = $start_bound . '-01';
        $display_end   = date( 'Y-m-t', strtotime( $end_bound . '-01' ) );

        $sql_where = $wpdb->prepare( "WHERE DATE_FORMAT(accessed_at, '%%Y-%%m') BETWEEN %s AND %s", $start_bound, $end_bound );
        $sql_select_time = "DATE_FORMAT(accessed_at, '%Y-%m-01')";
        $sql_group = "GROUP BY DATE_FORMAT(accessed_at, '%Y-%m-01')";
    }

    // Determine subquery dynamically based on target monthly tables in date range
    $from_table = a1geo_get_monthly_logs_subquery( $display_start, $display_end );

    $chart_records = array();
    $table_records = array();
    $report_error = false;
    $actual_click_condition = a1geo_actual_click_sql_condition();
    $burst_counts = a1geo_page_view_burst_counts( $display_start, $display_end );

    if ( $from_table ) {
        // 3. Query Aggregated Time-Series Data for Chart.js
        $chart_records_sql =
            "SELECT {$sql_select_time} AS time_point,
                    COUNT(id) AS total_views,
                    SUM(CASE WHEN {$actual_click_condition} THEN 1 ELSE 0 END) AS total_clicks
            FROM {$from_table}
            {$sql_where}
            {$sql_group}
            ORDER BY time_point ASC";

        $chart_records = $wpdb->get_results( $chart_records_sql );
        if ( null === $chart_records || $wpdb->last_error ) {
            error_log( 'Page view summary: chart query failed: ' . $wpdb->last_error );
            $report_error = true;
        }

        // 4. Query Page Breakdown Metrics Data for WP_List_Table
        $table_records_sql =
            "SELECT BINARY page_url AS page_url,
                    COUNT(id) AS raw_views, 
                    COUNT(DISTINCT ip_address) AS unique_visitors,
                    SUM(CASE WHEN {$actual_click_condition} THEN 1 ELSE 0 END) AS actual_clicks
            FROM {$from_table}
            {$sql_where}
            GROUP BY BINARY page_url
            ORDER BY actual_clicks DESC, raw_views DESC";
        $table_records = $wpdb->get_results( $table_records_sql, ARRAY_A );
        if ( null === $table_records || $wpdb->last_error ) {
            error_log( 'Page view summary: page query failed: ' . $wpdb->last_error );
            $report_error = true;
        }
    }

    if ( is_array( $table_records ) ) {
        foreach ( $table_records as &$record ) {
            $record['burst_views'] = is_wp_error( $burst_counts ) ? null : ( $burst_counts['pages'][ $record['page_url'] ] ?? 0 );
            $record['estimated_views'] = is_wp_error( $burst_counts ) ? null : (int) $record['actual_clicks'] - ( $burst_counts['non_bot_pages'][ $record['page_url'] ] ?? 0 );
        }
        unset( $record );
    }

    $chart_labels = array();
    $chart_views  = array();
    $chart_clicks = array();
    $chart_bursts = array();
    $chart_estimated = array();
    if ( ! is_wp_error( $burst_counts ) && 'date' !== $filter_mode ) {
        $monthly_bursts = array();
        $monthly_non_bot_bursts = array();
        foreach ( $burst_counts['dates'] as $date => $count ) {
            $month = substr( $date, 0, 7 ) . '-01';
            $monthly_bursts[ $month ] = ( $monthly_bursts[ $month ] ?? 0 ) + $count;
        }
        foreach ( $burst_counts['non_bot_dates'] as $date => $count ) {
            $month = substr( $date, 0, 7 ) . '-01';
            $monthly_non_bot_bursts[ $month ] = ( $monthly_non_bot_bursts[ $month ] ?? 0 ) + $count;
        }
    }

    if ( ! empty( $chart_records ) ) {
        foreach ( $chart_records as $row ) {
            $chart_labels[] = date( 'd/m/Y', strtotime( $row->time_point ) );
            $chart_views[]  = (int) $row->total_views;
            $chart_clicks[] = (int) $row->total_clicks;
            $chart_bursts[] = is_wp_error( $burst_counts ) ? null : ( 'date' === $filter_mode ? ( $burst_counts['dates'][ $row->time_point ] ?? 0 ) : ( $monthly_bursts[ $row->time_point ] ?? 0 ) );
            $non_bot_bursts = is_wp_error( $burst_counts ) ? null : ( 'date' === $filter_mode ? ( $burst_counts['non_bot_dates'][ $row->time_point ] ?? 0 ) : ( $monthly_non_bot_bursts[ $row->time_point ] ?? 0 ) );
            $chart_estimated[] = null === $non_bot_bursts ? null : (int) $row->total_clicks - $non_bot_bursts;
        }
    }

    // 5. Instantiate and prepare WP_List_Table
    $list_table = new A1_Page_View_Summary_List_Table( $table_records ? $table_records : array() );
    $list_table->prepare_items();

    // 6. Enqueue Chart.js for visualization
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true );
    ?>

    <div class="wrap" style="margin-top: 20px; max-width: 1200px;">
        <h1 style="margin-bottom: 20px; font-weight: 700; color: #23282d;">
            <?php esc_html_e( 'Traffic Analytics', 'a1-geo' ); ?>
        </h1>
        <p><?php esc_html_e( 'Views excluding bots and bursts are estimates, not verified human views. Bursts are 20 distinct URLs within 60 seconds from the same IP and user agent. Burst counts can include known bots; overlapping requests are excluded only once.', 'a1-geo' ); ?></p>
        <?php if ( is_wp_error( $burst_counts ) ) : ?>
            <div class="notice notice-warning"><p><?php echo esc_html( $burst_counts->get_error_message() ); ?></p></div>
        <?php endif; ?>
        <?php if ( $report_error ) : ?>
            <div class="notice notice-error"><p><?php esc_html_e( 'Some access-log data could not be loaded. Check the site logs.', 'a1-geo' ); ?></p></div>
        <?php endif; ?>

        <!-- BLOCK 1: RADIO CONTROL FILTER TOOLBAR (TOP) -->
        <div class="notice notice-info" style="margin: 0 0 30px 0; padding: 20px; background: #fff; border-left-color: #b52025; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
            <form method="GET" action="admin.php" id="a1-management-filter-form">
                <input type="hidden" name="page" value="page-view-summary">
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    
                    <!-- Filter Type Select Options -->
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <span style="font-weight: 700; font-size: 14px; color: #444;"><?php esc_html_e( 'Filter By:', 'a1-geo' ); ?></span>
                        
                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="date" <?php checked( $filter_mode, 'date' ); ?> onchange="document.getElementById('a1-management-filter-form').submit();"> 
                            <?php esc_html_e( 'Date Range', 'a1-geo' ); ?>
                        </label>

                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="month" <?php checked( $filter_mode, 'month' ); ?> onchange="document.getElementById('a1-management-filter-form').submit();"> 
                            <?php esc_html_e( 'Month Range', 'a1-geo' ); ?>
                        </label>
                    </div>

                    <!-- Dynamic Bound Scope Selection Form Segments -->
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #e2e4e7;">
                        <?php if ( 'date' === $filter_mode ) : ?>
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'From Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_start_date" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( $display_start ) ) ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
                                    <input type="date" id="native_start_date" name="start_date" value="<?php echo esc_attr( $start_bound ); ?>" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; z-index: 1; cursor: pointer;">
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'To Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_end_date" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( $display_end ) ) ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
                                    <input type="date" id="native_end_date" name="end_date" value="<?php echo esc_attr( $end_bound ); ?>" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; z-index: 1; cursor: pointer;">
                                </div>
                            </div>
                        <?php else : ?>
                            <div>
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'From Month:', 'a1-geo' ); ?></label>
                                <input type="month" class="regular-text" style="width: auto; height: 30px;" name="start_month" value="<?php echo esc_attr( $start_bound ); ?>">
                            </div>
                            <div>
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'To Month:', 'a1-geo' ); ?></label>
                                <input type="month" class="regular-text" style="width: auto; height: 30px;" name="end_month" value="<?php echo esc_attr( $end_bound ); ?>">
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: auto;">
                            <button type="submit" class="button button-primary" style="height: 30px; font-weight: 600; background: #b52025; border-color: #931a1e; box-shadow: none; text-shadow: none;">
                                <?php esc_html_e( 'Apply Filter', 'a1-geo' ); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        <?php if ( 'date' === $filter_mode ) : ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    function setupFormatWrapper(uiId, nativeId) {
                        var uiInput = document.getElementById(uiId);
                        var nativeInput = document.getElementById(nativeId);
                        if (!uiInput || !nativeInput) return;

                        uiInput.addEventListener('click', function() {
                            if (typeof nativeInput.showPicker === 'function') {
                                nativeInput.showPicker();
                            } else {
                                nativeInput.click();
                            }
                        });

                        nativeInput.addEventListener('change', function() {
                            var dateVal = this.value;
                            if (dateVal) {
                                var parts = dateVal.split('-');
                                if (parts.length === 3) {
                                    uiInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                                }
                            }
                        });
                    }

                    setupFormatWrapper('ui_start_date', 'native_start_date');
                    setupFormatWrapper('ui_end_date', 'native_end_date');
                });
            </script>
        <?php endif; ?>

        <!-- BLOCK 2: GRID FOR LINE CHART & NATIVE WP_LIST_TABLE -->
        <div class="a1-dashboard-grid" style="display: grid; grid-template-columns: 1fr; gap: 30px; align-items: start;">
            
            <!-- TOTAL VIEWS LINE CHART BLOCK -->
            <div class="a1-dashboard-block chart-block" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #23282d;">
                    <span class="dashicons dashicons-chart-line" style="margin-right: 8px; color: #b52025; vertical-align: text-bottom;"></span>
                    <?php 
                    if ( 'date' === $filter_mode ) {
                        esc_html_e( 'Total Daily Views Across All Pages', 'a1-geo' );
                    } else {
                        esc_html_e( 'Total Monthly Views Across All Pages', 'a1-geo' );
                    }
                    ?>
                </h3>
                <div class="chart-container" style="position: relative; height: 320px; width: 100%;">
                    <canvas id="a1TrafficChart"></canvas>
                </div>
            </div>

            <!-- NATIVE WP_LIST_TABLE METRICS BLOCK -->
            <div class="a1-dashboard-block table-block" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #23282d;">
                    <span class="dashicons dashicons-database" style="margin-right: 8px; color: #0073aa; vertical-align: text-bottom;"></span>
                    <?php esc_html_e( 'Detailed Page Performance Metrics', 'a1-geo' ); ?>
                </h3>
                <form method="get">
                    <input type="hidden" name="page" value="page-view-summary" />
                    <input type="hidden" name="filter_mode" value="<?php echo esc_attr( $filter_mode ); ?>" />
                    <?php if ( 'date' === $filter_mode ) : ?>
                        <input type="hidden" name="start_date" value="<?php echo esc_attr( $start_bound ); ?>" />
                        <input type="hidden" name="end_date" value="<?php echo esc_attr( $end_bound ); ?>" />
                    <?php else : ?>
                        <input type="hidden" name="start_month" value="<?php echo esc_attr( $start_bound ); ?>" />
                        <input type="hidden" name="end_month" value="<?php echo esc_attr( $end_bound ); ?>" />
                    <?php endif; ?>

                    <?php 
                    $list_table->display(); 
                    ?>
                </form>
            </div>

            <?php a1geo_render_browser_view_report( $display_start, $display_end ); ?>
        </div>
    </div>

    <!-- Chart.js Render Script -->
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('a1TrafficChart').getContext('2d');
            var trafficChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode( $chart_labels ); ?>,
                    datasets: [
                        {
                            label: '<?php echo esc_js( 'date' === $filter_mode ? __( 'Daily Requests Without Known Bot Signatures', 'a1-geo' ) : __( 'Monthly Requests Without Known Bot Signatures', 'a1-geo' ) ); ?>',
                            data: <?php echo json_encode( $chart_clicks ); ?>,
                            borderColor: '#b52025',
                            backgroundColor: 'rgba(181, 32, 37, 0.08)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: '<?php echo esc_js( __( 'Views Excluding Bots and Bursts', 'a1-geo' ) ); ?>',
                            data: <?php echo wp_json_encode( $chart_estimated ); ?>,
                            borderColor: '#008a20',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4
                        },
                        {
                            label: '<?php echo esc_js( __( 'Suspicious Burst Requests', 'a1-geo' ) ); ?>',
                            data: <?php echo wp_json_encode( $chart_bursts ); ?>,
                            borderColor: '#dba617',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4
                        },
                        {
                            label: '<?php echo esc_js( 'date' === $filter_mode ? __( 'Total Daily Views (Inc. Bots)', 'a1-geo' ) : __( 'Total Monthly Views (Inc. Bots)', 'a1-geo' ) ); ?>',
                            data: <?php echo json_encode( $chart_views ); ?>,
                            borderColor: '#8c8f94',
                            backgroundColor: 'rgba(140, 143, 148, 0.08)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
            });
        });
    </script>
    <?php
}

function render_daily_view_drilldown() {
    global $wpdb;

    // 1. Resolve Target Active Day Date Window Parameter
    $target_date  = isset( $_GET['target_date'] ) ? sanitize_text_field( $_GET['target_date'] ) : current_time( 'Y-m-d' );
    $display_date = date( 'd-m-Y', strtotime( $target_date ) );
    $view_url     = isset( $_GET['view_url'] ) ? wp_unslash( $_GET['view_url'] ) : '';

    // Target monthly partitioned table directly without WP prefix
    $target_table = 'page_access_logs_' . date( 'Y_m', strtotime( $target_date ) );
    
    // Check if partitioned table exists
    $table_exists = ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $target_table ) ) === $target_table );
    $burst_counts = a1geo_page_view_burst_counts( $target_date, $target_date );

    echo '<div class="wrap" style="margin-top: 20px; max-width: 1200px;">';
    echo '<h1 style="font-weight: 700; color: #23282d; margin-bottom: 20px;">' . esc_html__( 'A1 Management — Daily Audit Tracking', 'a1-geo' ) . '</h1>';
    echo '<p>' . esc_html__( 'Views excluding bots and bursts are estimates, not verified human views. Bursts are 20 distinct URLs within 60 seconds from the same IP and user agent. Burst counts can include known bots; overlapping requests are excluded only once.', 'a1-geo' ) . '</p>';
    if ( is_wp_error( $burst_counts ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html( $burst_counts->get_error_message() ) . '</p></div>';
    }

    ?>
    <div class="notice notice-info" style="margin: 0 0 25px 0; padding: 15px; background: #fff; border-left-color: #0073aa; box-shadow: 0 1px 1px rgba(0,0,0,.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <form method="GET" action="admin.php" id="daily-audit-date-form" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <input type="hidden" name="page" value="daily-view-drilldown">
            <label style="font-weight: 600; font-size: 13px; color: #333;"><?php esc_html_e( 'Select Target Day Audit:', 'a1-geo' ); ?></label>
            
            <div style="position: relative; display: inline-block;">
                <input type="text" 
                       id="ui_date_picker" 
                       value="<?php echo esc_attr( $display_date ); ?>" 
                       placeholder="dd-mm-yyyy" 
                       readonly
                       style="height: 30px; padding: 0 8px; width: 130px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;"
                >
                <input type="date" 
                       id="native_date_picker" 
                       name="target_date" 
                       value="<?php echo esc_attr( $target_date ); ?>" 
                       style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; z-index: 1; cursor: pointer;"
                >
            </div>
            
            <button type="submit" class="button button-secondary" style="height: 30px; line-height: 28px;"><?php esc_html_e( 'Change Date', 'a1-geo' ); ?></button>
        </form>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var uiInput = document.getElementById('ui_date_picker');
                var nativeInput = document.getElementById('native_date_picker');

                uiInput.addEventListener('click', function() {
                    if (typeof nativeInput.showPicker === 'function') {
                        nativeInput.showPicker();
                    } else {
                        nativeInput.click();
                    }
                });

                nativeInput.addEventListener('change', function() {
                    var dateVal = this.value;
                    if (dateVal) {
                        var parts = dateVal.split('-');
                        if (parts.length === 3) {
                            uiInput.value = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                });
            });
        </script>

        <?php if ( ! empty( $view_url ) ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=daily-view-drilldown&target_date=' . $target_date ) ); ?>" class="button button-primary" style="background: #23282d; border-color: #23282d; font-weight: 600; box-shadow: none; text-shadow: none;">
                ← <?php esc_html_e( 'Back to Daily Summary List', 'a1-geo' ); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php

    /* ==========================================================================
       VIEW CONTEXT B: SINGLE URL VISITOR IP ROSTER LIST VIEW
       ========================================================================== */
    if ( ! empty( $view_url ) ) {
        $ip_results = array();

        if ( $table_exists ) {
            $ip_results = $wpdb->get_results( $wpdb->prepare(
                "SELECT BINARY ip_address AS ip_address, BINARY user_agent AS user_agent, COUNT(id) AS hit_count, MAX(accessed_at) AS last_seen
                 FROM `{$target_table}`
                 WHERE DATE(accessed_at) = %s AND BINARY page_url = BINARY %s
                 GROUP BY BINARY ip_address, BINARY user_agent
                 ORDER BY hit_count DESC",
                $target_date,
                $view_url
            ) );
            if ( null === $ip_results || $wpdb->last_error ) {
                error_log( 'Page view drill-down: roster query failed: ' . $wpdb->last_error );
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Access-log entries could not be loaded. Check the site logs.', 'a1-geo' ) . '</p></div>';
            }
        }
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin-top:0; color: #23282d; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <span class="dashicons dashicons-networking" style="color:#b52025; margin-right:6px; vertical-align: text-bottom;"></span>
                <?php esc_html_e( 'IP Address Logs Routing Analysis for URL:', 'a1-geo' ); ?> 
                <code style="background: #f0f0f1; padding: 3px 6px; border-radius: 3px; font-size: 13px; margin-left: 5px; word-break: break-all; color: #b52025;"><?php echo esc_html( $view_url ); ?></code>
                <span style="float: right; font-size: 13px; color: #666; font-weight: normal;"><?php echo esc_html( $display_date ); ?></span>
            </h3>

            <table class="wp-list-table widefat fixed striped pages" style="margin-top: 15px; border: none;">
                <thead>
                    <tr>
                        <th style="font-weight: 700; width: 22%;"><?php esc_html_e( 'Visitor IP Address', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 53%;"><?php esc_html_e( 'User Agent Client Signature', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 10%; text-align: center;"><?php esc_html_e( 'Total Hits', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 10%; text-align: center;"><?php esc_html_e( 'Burst Hits', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 10%; text-align: center;"><?php esc_html_e( 'Views Excluding Bots and Bursts', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: right;"><?php esc_html_e( 'Last Access Time', 'a1-geo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $ip_results ) ) : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No log entries traced for this target item.', 'a1-geo' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $ip_results as $row ) : 
                            $classification = a1geo_classify_page_view( $row->ip_address, $row->user_agent );
                            $is_bot = $classification['bot'];
                            $client_key = hash( 'sha256', serialize( array( $row->ip_address, $row->user_agent ) ) );
                            $burst_hits = is_wp_error( $burst_counts ) ? null : ( $burst_counts['clients'][ $view_url ][ $client_key ] ?? 0 );
                            ?>
                            <tr style="<?php echo $is_bot ? 'opacity: 0.75;' : ''; ?>">
                                <td>
                                    <strong>
                                        <span class="dashicons dashicons-location-alt" style="font-size:16px; color:#666; vertical-align:middle; margin-right:4px;"></span>
                                        <?php echo esc_html( $row->ip_address ); ?>
                                    </strong>

                                    <?php
                                    $badge_color = $is_bot ? '#72777c' : '#46b450';
                                    if ( 'G' === $classification['label'] ) {
                                        $badge_color = '#dc3232';
                                    } elseif ( 'B' === $classification['label'] ) {
                                        $badge_color = '#ffb900';
                                    } elseif ( 'M' === $classification['label'] ) {
                                        $badge_color = '#1877f2';
                                    } elseif ( 'BV' === $classification['label'] ) {
                                        $badge_color = '#8c4b00';
                                    }
                                    ?>
                                    <span style="font-weight: 900; color: <?php echo esc_attr( $badge_color ); ?>; margin-left: 6px; font-size: 14px;"
                                          title="<?php echo esc_attr( $classification['title'] ); ?>">
                                        <?php echo esc_html( $classification['label'] ); ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color:#555; font-family: monospace;"><?php echo esc_html( $row->user_agent ); ?></td>
                                <td style="text-align: center;"><span class="update-plugins count-filter" style="background:#f6f7f7; color:#3c434a; font-weight:600; padding:2px 8px; border-radius:10px; border:1px solid #dcdcde;"><?php echo (int) $row->hit_count; ?></span></td>
                                <td style="text-align: center;"><?php echo null === $burst_hits ? esc_html__( 'Unavailable', 'a1-geo' ) : (int) $burst_hits; ?></td>
                                <td style="text-align: center;"><?php echo null === $burst_hits ? esc_html__( 'Unavailable', 'a1-geo' ) : ( $is_bot ? 0 : (int) $row->hit_count - $burst_hits ); ?></td>
                                <td style="text-align: right; color:#666; font-size:12px;"><?php echo esc_html( date( 'H:i:s', strtotime( $row->last_seen ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    } 
    /* ==========================================================================
       VIEW CONTEXT A: MAIN SUMMARY LIST VIEW FOR THE TARGET DAY
       ========================================================================== */
    else {
        $summary_results = array();

        if ( $table_exists ) {
            $actual_click_condition = a1geo_actual_click_sql_condition();
            $target_date_sql = esc_sql( $target_date );
            $summary_results = $wpdb->get_results(
                "SELECT BINARY page_url AS page_url,
                        COUNT(id) AS view_count, 
                        COUNT(DISTINCT ip_address) AS unique_visitors,
                        SUM(CASE WHEN {$actual_click_condition} THEN 1 ELSE 0 END) AS actual_clicks
                 FROM `{$target_table}`
                 WHERE DATE(accessed_at) = '{$target_date_sql}'
                 GROUP BY BINARY page_url
                 ORDER BY actual_clicks DESC, view_count DESC"
            );
            if ( null === $summary_results || $wpdb->last_error ) {
                error_log( 'Page view drill-down: summary query failed: ' . $wpdb->last_error );
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Access-log summary could not be loaded. Check the site logs.', 'a1-geo' ) . '</p></div>';
            }
        }
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin-top:0; color: #23282d; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <span class="dashicons dashicons-visibility" style="color:#0073aa; margin-right:6px; vertical-align: text-bottom;"></span>
                <?php printf( esc_html__( 'Aggregate Traffic View Logs Summary: %s', 'a1-geo' ), '<strong>' . esc_html( $display_date ) . '</strong>' ); ?>
            </h3>

            <table class="wp-list-table widefat fixed striped pages" style="margin-top: 15px; border: none;">
                <thead>
                    <tr>
                        <th style="font-weight: 700; width: 50%;"><?php esc_html_e( 'Tracked Destination URL Link Path', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: center;"><?php esc_html_e( 'Distinct IPs (Including Bots)', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: center; color: #2271b1;"><?php esc_html_e( 'Requests Without Known Bot Signatures', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: center;"><?php esc_html_e( 'Suspicious Burst Requests', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: center;"><?php esc_html_e( 'Views Excluding Bots and Bursts', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 20%; text-align: center;"><?php esc_html_e( 'Raw Views (Inc. Bots)', 'a1-geo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $summary_results ) ) : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No system data logging records identified within this specific calendar frame context.', 'a1-geo' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $summary_results as $row ) : 
                            $drilldown_href = admin_url( 'admin.php?page=daily-view-drilldown&target_date=' . $target_date . '&view_url=' . urlencode( $row->page_url ) );
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( $drilldown_href ); ?>" style="font-weight: 600; color: #2271b1; text-decoration: none;" title="<?php esc_attr_e( 'Click to Audit IP Roster Logs', 'a1-geo' ); ?>">
                                        <span class="dashicons dashicons-media-text" style="font-size:17px; margin-right:5px; vertical-align:middle; color:#8c8f94;"></span>
                                        <?php echo esc_html( $row->page_url ); ?>
                                    </a>
                                </td>
                                <td style="text-align: center; font-weight: 500; color: #444;"><?php echo (int) $row->unique_visitors; ?></td>
                                <td style="text-align: center; font-weight: 700; color: #135e96; background: #f0f6fc;">
                                    <?php echo (int) $row->actual_clicks; ?>
                                </td>
                                <td style="text-align: center;"><?php echo is_wp_error( $burst_counts ) ? esc_html__( 'Unavailable', 'a1-geo' ) : (int) ( $burst_counts['pages'][ $row->page_url ] ?? 0 ); ?></td>
                                <td style="text-align: center; font-weight: 700;"><?php echo is_wp_error( $burst_counts ) ? esc_html__( 'Unavailable', 'a1-geo' ) : (int) $row->actual_clicks - ( $burst_counts['non_bot_pages'][ $row->page_url ] ?? 0 ); ?></td>
                                <td style="text-align: center;">
                                    <a href="<?php echo esc_url( $drilldown_href ); ?>" class="button button-small" style="font-weight: 500; background: #fff; color: #3c434a; border-color: #dcdcde; border-radius: 4px;">
                                        <?php echo (int) $row->view_count; ?> <?php esc_html_e( 'views', 'a1-geo' ); ?> »
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    a1geo_render_browser_view_report( $target_date, $target_date, is_string( $view_url ) ? $view_url : '' );
    echo '</div>';
}