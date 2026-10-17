<?php
/**
 * Enqueue WordPress Core Datepicker Assets
 */
function a1geo_enqueue_datepicker_assets() {
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css' );
}

/**
 * Helper function to convert dd/MM/yyyy to Y-m-d for MySQL queries
 */
function a1geo_parse_ddmmyyyy_to_db( $date_str ) {
    if ( empty( $date_str ) ) {
        return '';
    }
    $d = DateTime::createFromFormat( 'd/m/Y', $date_str );
    return $d ? $d->format( 'Y-m-d' ) : '';
}

/**
 * Render External Click Summary Admin Page
 */
function a1geo_render_external_clicks_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = 'external_click_logs';

    // Check if table exists
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
        echo '<div class="wrap"><h2>External Clicks Summary</h2><div class="notice notice-warning"><p>No external click logs table found yet.</p></div></div>';
        return;
    }

    // Determine view mode: 'details' or 'summary'
    $view_mode       = isset( $_GET['view'] ) && $_GET['view'] === 'details' ? 'details' : 'summary';
    $selected_domain = isset( $_GET['domain'] ) ? sanitize_text_field( wp_unslash( $_GET['domain'] ) ) : '';
    $traffic_type    = isset( $_GET['traffic_type'] ) ? sanitize_text_field( wp_unslash( $_GET['traffic_type'] ) ) : '';

    // 1. Get chosen Filter Mode via URL parameters (defaulting to 'date')
    $filter_mode = isset( $_GET['filter_mode'] ) ? sanitize_key( $_GET['filter_mode'] ) : 'date';

    // 2. Resolve Dynamic Range Bound Enforcements & SQL Date Filters
    if ( 'date' === $filter_mode ) {
        $start_bound = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : current_time( 'Y-m-d' );
        $end_bound   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : current_time( 'Y-m-d' );

        $date_from_db = date( 'Y-m-d', strtotime( $start_bound ) );
        $date_to_db   = date( 'Y-m-d', strtotime( $end_bound ) );

        $where_clauses = array(
            $wpdb->prepare( "DATE(clicked_at) BETWEEN %s AND %s", $date_from_db, $date_to_db )
        );
    } else {
        $start_bound = isset( $_GET['start_month'] ) ? sanitize_text_field( $_GET['start_month'] ) : current_time( 'Y-m' );
        $end_bound   = isset( $_GET['end_month'] ) ? sanitize_text_field( $_GET['end_month'] ) : current_time( 'Y-m' );

        $date_from_db = $start_bound . '-01';
        $date_to_db   = date( 'Y-m-t', strtotime( $end_bound . '-01' ) );

        $where_clauses = array(
            $wpdb->prepare( "DATE_FORMAT(clicked_at, '%%Y-%%m') BETWEEN %s AND %s", $start_bound, $end_bound )
        );
    }

    // Search query & Pagination
    $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    $paged  = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $limit  = 20;
    $offset = ( $paged - 1 ) * $limit;

    if ( ! empty( $search ) ) {
        $where_clauses[] = $wpdb->prepare(
            "(target_url LIKE %s OR source_url LIKE %s)",
            '%' . $wpdb->esc_like( $search ) . '%',
            '%' . $wpdb->esc_like( $search ) . '%'
        );
    }

    // Standardized SQL expression to isolate domain from target_url
    $domain_sql_expr = "LOWER(REGEXP_REPLACE(REGEXP_REPLACE(target_url, '^https?://(www\\.)?', ''), '/.*$', ''))";

    // Standardized SQL expression to detect Google Ads traffic from source_url
    $gads_condition_sql = "(source_url LIKE '%gad_source=%' OR source_url LIKE '%gclid=%' OR source_url LIKE '%gad_campaignid=%' OR source_url LIKE '%gbraid=%' OR source_url LIKE '%wbraid=%')";

    // If viewing details for a specific domain/traffic type, refine scope
    $card_where_clauses = $where_clauses;
    if ( $view_mode === 'details' ) {
        if ( ! empty( $selected_domain ) ) {
            $card_where_clauses[] = $wpdb->prepare( "{$domain_sql_expr} = %s", $selected_domain );
        }
        if ( 'gads' === $traffic_type ) {
            $card_where_clauses[] = $gads_condition_sql;
        } elseif ( 'organic' === $traffic_type ) {
            $card_where_clauses[] = "NOT " . $gads_condition_sql;
        }
    }

    $card_where_sql = implode( ' AND ', $card_where_clauses );

    // CARD METRICS (Calculated dynamically on search query & date range filter)
    $total_clicks       = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE {$card_where_sql}" );
    $gads_clicks        = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE {$card_where_sql} AND {$gads_condition_sql}" );
    $organic_clicks     = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE {$card_where_sql} AND NOT {$gads_condition_sql}" );
    $unique_domains     = $wpdb->get_var( "SELECT COUNT(DISTINCT {$domain_sql_expr}) FROM {$table_name} WHERE {$card_where_sql}" );
    $unique_users_by_ip = $wpdb->get_var( "SELECT COUNT(DISTINCT ip_address) FROM {$table_name} WHERE {$card_where_sql}" );

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php if ( $view_mode === 'details' ) : ?>
                External Clicks Detail Log <?php echo $selected_domain ? 'for <code>' . esc_html( $selected_domain ) . '</code>' : ''; ?>
                <?php 
                if ( 'gads' === $traffic_type ) {
                    echo ' <span style="font-size:14px; background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:10px;">Google Ads Clicks</span>';
                } elseif ( 'organic' === $traffic_type ) {
                    echo ' <span style="font-size:14px; background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px;">Organic / Direct Clicks</span>';
                }
                ?>
            <?php else : ?>
                External Clicks Summary by Domain
            <?php endif; ?>
        </h1>

        <?php if ( $view_mode === 'details' ) : ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'a1geo-external-clicks', 'view' => 'summary' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">&larr; Back to Domain Summary</a>
        <?php endif; ?>

        <hr class="wp-header-end">

        <!-- Dynamic Summary Metric Cards -->
        <div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
            <div style="background: #fff; padding: 12px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; border-radius: 4px; flex: 1; min-width: 160px;">
                <h3 style="margin: 0; color: #646970; font-size: 13px;">Total Clicks</h3>
                <p style="font-size: 22px; font-weight: bold; margin: 5px 0 0;"><?php echo number_format( (int) $total_clicks ); ?></p>
            </div>
            <div style="background: #fff; padding: 12px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #1a73e8; border-radius: 4px; flex: 1; min-width: 160px;">
                <h3 style="margin: 0; color: #1a73e8; font-size: 13px;">Google Ads Clicks</h3>
                <p style="font-size: 22px; font-weight: bold; margin: 5px 0 0; color: #1a73e8;"><?php echo number_format( (int) $gads_clicks ); ?></p>
            </div>
            <div style="background: #fff; padding: 12px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #1e7e34; border-radius: 4px; flex: 1; min-width: 160px;">
                <h3 style="margin: 0; color: #1e7e34; font-size: 13px;">Organic / Direct Clicks</h3>
                <p style="font-size: 22px; font-weight: bold; margin: 5px 0 0; color: #1e7e34;"><?php echo number_format( (int) $organic_clicks ); ?></p>
            </div>
            <div style="background: #fff; padding: 12px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; border-radius: 4px; flex: 1; min-width: 160px;">
                <h3 style="margin: 0; color: #646970; font-size: 13px;">Unique Domains</h3>
                <p style="font-size: 22px; font-weight: bold; margin: 5px 0 0;"><?php echo number_format( (int) $unique_domains ); ?></p>
            </div>
            <div style="background: #fff; padding: 12px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #dba617; border-radius: 4px; flex: 1; min-width: 160px;">
                <h3 style="margin: 0; color: #646970; font-size: 13px;">Unique Users (IPs)</h3>
                <p style="font-size: 22px; font-weight: bold; margin: 5px 0 0;"><?php echo number_format( (int) $unique_users_by_ip ); ?></p>
            </div>
        </div>

        <!-- RADIO CONTROL FILTER TOOLBAR (MATCHING PAGE ACCESS SUMMARY) -->
        <div class="notice notice-info" style="margin: 0 0 20px 0; padding: 15px; background: #fff; border-left-color: #2271b1; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
            <form method="GET" action="admin.php" id="a1geo-external-clicks-filter-form">
                <input type="hidden" name="page" value="a1geo-external-clicks">
                <input type="hidden" name="view" value="<?php echo esc_attr( $view_mode ); ?>" />
                <?php if ( $selected_domain ) : ?>
                    <input type="hidden" name="domain" value="<?php echo esc_attr( $selected_domain ); ?>" />
                <?php endif; ?>
                <?php if ( $traffic_type ) : ?>
                    <input type="hidden" name="traffic_type" value="<?php echo esc_attr( $traffic_type ); ?>" />
                <?php endif; ?>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    
                    <!-- Filter Type Select Options -->
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <span style="font-weight: 700; font-size: 14px; color: #444;"><?php esc_html_e( 'Filter By:', 'a1-geo' ); ?></span>
                        
                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="date" <?php checked( $filter_mode, 'date' ); ?> onchange="document.getElementById('a1geo-external-clicks-filter-form').submit();"> 
                            <?php esc_html_e( 'Date Range', 'a1-geo' ); ?>
                        </label>

                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="month" <?php checked( $filter_mode, 'month' ); ?> onchange="document.getElementById('a1geo-external-clicks-filter-form').submit();"> 
                            <?php esc_html_e( 'Month Range', 'a1-geo' ); ?>
                        </label>
                    </div>

                    <!-- Dynamic Bound Scope Selection Form Segments -->
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #e2e4e7;">
                        <?php if ( 'date' === $filter_mode ) : ?>
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'From Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_start_date" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( $date_from_db ) ) ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
                                    <input type="date" id="native_start_date" name="start_date" value="<?php echo esc_attr( $start_bound ); ?>" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; z-index: 1; cursor: pointer;">
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'To Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_end_date" value="<?php echo esc_attr( date( 'd-m-Y', strtotime( $date_to_db ) ) ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
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

                        <div>
                            <input type="search" id="search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search URL..." style="height: 30px; vertical-align: middle;" />
                        </div>

                        <div>
                            <button type="submit" class="button button-primary" style="height: 30px; font-weight: 600;">
                                <?php esc_html_e( 'Apply Filter', 'a1-geo' ); ?>
                            </button>
                            <a href="<?php echo admin_url( 'admin.php?page=a1geo-external-clicks' ); ?>" class="button button-secondary" style="height: 30px; vertical-align: middle;">Reset</a>
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

        <?php if ( $view_mode === 'summary' ) : ?>
            <?php
            $where_sql = implode( ' AND ', $where_clauses );

            // Aggregate query grouped by Domain with Ads vs Organic Breakdown
            $domain_grouped_sql = "
                SELECT 
                    {$domain_sql_expr} AS domain_name, 
                    COUNT(id) AS total_clicks,
                    SUM(CASE WHEN {$gads_condition_sql} THEN 1 ELSE 0 END) AS gads_clicks,
                    SUM(CASE WHEN NOT {$gads_condition_sql} THEN 1 ELSE 0 END) AS organic_clicks,
                    COUNT(DISTINCT ip_address) AS unique_users,
                    MAX(clicked_at) AS last_clicked
                FROM {$table_name}
                WHERE {$where_sql}
                GROUP BY domain_name
                ORDER BY total_clicks DESC
                LIMIT %d OFFSET %d
            ";

            $results     = $wpdb->get_results( $wpdb->prepare( $domain_grouped_sql, $limit, $offset ) );
            $total_items = (int) $unique_domains;
            $total_pages = ceil( $total_items / $limit );
            ?>

            <!-- DOMAIN SUMMARY TABLE -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary">Target Domain</th>
                        <th scope="col" class="manage-column" style="width: 100px;">Total Clicks</th>
                        <th scope="col" class="manage-column" style="width: 120px; color: #1a73e8;">Google Ads</th>
                        <th scope="col" class="manage-column" style="width: 130px; color: #1e7e34;">Organic / Direct</th>
                        <th scope="col" class="manage-column" style="width: 130px;">Unique Users (IPs)</th>
                        <th scope="col" class="manage-column" style="width: 160px;">Last Clicked</th>
                        <th scope="col" class="manage-column" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $results ) ) : ?>
                        <?php foreach ( $results as $row ) : ?>
                            <?php 
                            $base_detail_args = array(
                                'page'        => 'a1geo-external-clicks',
                                'view'        => 'details',
                                'domain'      => $row->domain_name,
                                'filter_mode' => $filter_mode,
                                's'           => $search,
                            );

                            if ( 'date' === $filter_mode ) {
                                $base_detail_args['start_date'] = $start_bound;
                                $base_detail_args['end_date']   = $end_bound;
                            } else {
                                $base_detail_args['start_month'] = $start_bound;
                                $base_detail_args['end_month']   = $end_bound;
                            }

                            $detail_url = add_query_arg( $base_detail_args, admin_url( 'admin.php' ) );
                            $gads_url   = add_query_arg( array_merge( $base_detail_args, array( 'traffic_type' => 'gads' ) ), admin_url( 'admin.php' ) );
                            $org_url    = add_query_arg( array_merge( $base_detail_args, array( 'traffic_type' => 'organic' ) ), admin_url( 'admin.php' ) );

                            $gads_count = (int) $row->gads_clicks;
                            $org_count  = (int) $row->organic_clicks;
                            ?>
                            <tr>
                                <td class="column-primary" data-colname="Target Domain">
                                    <strong><?php echo esc_html( $row->domain_name ); ?></strong>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text">Show details</span></button>
                                </td>
                                <td data-colname="Total Clicks">
                                    <span class="badge" style="background: #e7f1fe; color: #0c4a6e; padding: 3px 8px; border-radius: 12px; font-weight: bold;">
                                        <?php echo number_format( $row->total_clicks ); ?>
                                    </span>
                                </td>
                                <td data-colname="Google Ads">
                                    <?php if ( $gads_count > 0 ) : ?>
                                        <a href="<?php echo esc_url( $gads_url ); ?>" style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-decoration: none;">
                                            <?php echo number_format( $gads_count ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span style="background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 10px; font-weight: 600; display: inline-block;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td data-colname="Organic / Direct">
                                    <?php if ( $org_count > 0 ) : ?>
                                        <a href="<?php echo esc_url( $org_url ); ?>" style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-weight: 600; text-decoration: none;">
                                            <?php echo number_format( $org_count ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span style="background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 10px; font-weight: 600; display: inline-block;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td data-colname="Unique Users (IPs)"><?php echo number_format( $row->unique_users ); ?></td>
                                <td data-colname="Last Clicked"><?php echo esc_html( date( 'd/m/Y H:i:s', strtotime( $row->last_clicked ) ) ); ?></td>
                                <td data-colname="Actions">
                                    <a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small button-secondary">View All &rarr;</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No domain clicks found matching criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else : ?>
            <?php
            // DETAILED CLICKS TABLE VIEW
            $clicks_sql = "
                SELECT id, user_id, target_url, source_url, ip_address, user_agent, clicked_at
                FROM {$table_name}
                WHERE {$card_where_sql}
                ORDER BY clicked_at DESC
                LIMIT %d OFFSET %d
            ";

            $results     = $wpdb->get_results( $wpdb->prepare( $clicks_sql, $limit, $offset ) );
            $total_items = (int) $total_clicks;
            $total_pages = ceil( $total_items / $limit );
            ?>

            <!-- INDIVIDUAL CLICKS TABLE -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-primary">Target URL</th>
                        <th scope="col" class="manage-column">Source Page & Channel</th>
                        <th scope="col" class="manage-column" style="width: 130px;">User</th>
                        <th scope="col" class="manage-column" style="width: 120px;">IP Address</th>
                        <th scope="col" class="manage-column" style="width: 160px;">Clicked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $results ) ) : ?>
                        <?php foreach ( $results as $row ) : ?>
                            <?php 
                            $user_info    = $row->user_id ? get_userdata( $row->user_id ) : false;
                            $user_display = $user_info ? esc_html( $user_info->display_name ) : '<em>Guest</em>';
                            
                            $is_gads = (
                                str_contains( $row->source_url, 'gad_source=' ) ||
                                str_contains( $row->source_url, 'gclid=' ) ||
                                str_contains( $row->source_url, 'gad_campaignid=' ) ||
                                str_contains( $row->source_url, 'gbraid=' ) ||
                                str_contains( $row->source_url, 'wbraid=' )
                            );
                            ?>
                            <tr>
                                <td class="column-primary" data-colname="Target URL">
                                    <a href="<?php echo esc_url( $row->target_url ); ?>" target="_blank" rel="noopener noreferrer">
                                        <strong><?php echo esc_html( $row->target_url ); ?></strong>
                                    </a>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text">Show details</span></button>
                                </td>
                                <td data-colname="Source Page & Channel">
                                    <div style="margin-bottom: 4px;">
                                        <a href="<?php echo esc_url( $row->source_url ); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo esc_html( $row->source_url ); ?>
                                        </a>
                                    </div>
                                    <?php if ( $is_gads ) : ?>
                                        <span style="background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;">Google Ads</span>
                                    <?php else : ?>
                                        <span style="background: #f3f4f6; color: #374151; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 500;">Organic / Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td data-colname="User"><?php echo $user_display; ?></td>
                                <td data-colname="IP Address"><code><?php echo esc_html( $row->ip_address ); ?></code></td>
                                <td data-colname="Clicked At"><?php echo esc_html( date( 'd/m/Y H:i:s', strtotime( $row->clicked_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5">No detailed click records found matching criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format( $total_items ); ?> items</span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ) );
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render External Clicks Chart Summary Page
 */
function a1geo_render_external_clicks_chart_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;
    $table_name = 'external_click_logs';

    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
        echo '<div class="wrap"><h2>External Clicks Summary</h2><div class="notice notice-warning"><p>No external click logs table found yet.</p></div></div>';
        return;
    }

    $filter_mode = isset( $_GET['filter_mode'] ) ? sanitize_key( $_GET['filter_mode'] ) : 'date';

    if ( 'date' === $filter_mode ) {
        $start_bound = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : current_time( 'Y-m-d' );
        $end_bound   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : current_time( 'Y-m-d' );

        $date_from_db = date( 'Y-m-d', strtotime( $start_bound ) );
        $date_to_db   = date( 'Y-m-d', strtotime( $end_bound ) );

        $where_sql       = $wpdb->prepare( "DATE(clicked_at) BETWEEN %s AND %s", $date_from_db, $date_to_db );
        $chart_group_sql = "DATE_FORMAT(clicked_at, '%d.%m.%Y')";
    } else {
        $start_bound = isset( $_GET['start_month'] ) ? sanitize_text_field( $_GET['start_month'] ) : current_time( 'Y-m' );
        $end_bound   = isset( $_GET['end_month'] ) ? sanitize_text_field( $_GET['end_month'] ) : current_time( 'Y-m' );

        $date_from_db = $start_bound . '-01';
        $date_to_db   = date( 'Y-m-t', strtotime( $end_bound . '-01' ) );

        $where_sql       = $wpdb->prepare( "DATE_FORMAT(clicked_at, '%%Y-%%m') BETWEEN %s AND %s", $start_bound, $end_bound );
        $chart_group_sql = "DATE_FORMAT(clicked_at, '%m.%Y')";
    }

    $gads_condition_sql = "(source_url LIKE '%gad_source=%' OR source_url LIKE '%gclid=%' OR source_url LIKE '%gad_campaignid=%' OR source_url LIKE '%gbraid=%' OR source_url LIKE '%wbraid=%')";

    $chart_sql = "
        SELECT 
            {$chart_group_sql} AS period, 
            COUNT(id) AS total_clicks,
            SUM(CASE WHEN {$gads_condition_sql} THEN 1 ELSE 0 END) AS gads_clicks,
            SUM(CASE WHEN NOT {$gads_condition_sql} THEN 1 ELSE 0 END) AS organic_clicks
        FROM {$table_name}
        WHERE {$where_sql}
        GROUP BY period
        ORDER BY MIN(clicked_at) ASC
    ";

    $chart_results = $wpdb->get_results( $chart_sql );

    $labels         = array();
    $total_series   = array();
    $gads_series    = array();
    $organic_series = array();

    if ( ! empty( $chart_results ) ) {
        foreach ( $chart_results as $row ) {
            $labels[]         = $row->period;
            $total_series[]   = (int) $row->total_clicks;
            $gads_series[]    = (int) $row->gads_clicks;
            $organic_series[] = (int) $row->organic_clicks;
        }
    }

    // Enqueue Chart.js and Flatpickr CSS/JS
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', false );
    wp_enqueue_style( 'flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13' );
    wp_enqueue_script( 'flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.13', false );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">External Clicks Summary Chart</h1>
        <hr class="wp-header-end">

        <!-- FILTER FORM -->
        <div class="notice notice-info" style="margin: 20px 0; padding: 15px; background: #fff; border-left-color: #2271b1; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
            <form method="GET" action="admin.php" id="a1geo-chart-filter-form">
                <input type="hidden" name="page" value="external-clicks-summary">

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <span style="font-weight: 700; font-size: 14px; color: #444;">Filter By:</span>
                        
                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="date" <?php checked( $filter_mode, 'date' ); ?> onchange="document.getElementById('a1geo-chart-filter-form').submit();"> 
                            Date Range
                        </label>

                        <label style="font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px; margin: 0;">
                            <input type="radio" name="filter_mode" value="month" <?php checked( $filter_mode, 'month' ); ?> onchange="document.getElementById('a1geo-chart-filter-form').submit();"> 
                            Month Range
                        </label>
                    </div>

                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #e2e4e7;">
                        <?php if ( 'date' === $filter_mode ) : ?>
                            <div>
                                <label style="font-weight: 600; font-size: 13px;">From Date:</label>
                                <input type="text" class="a1geo-flatpickr" name="start_date" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( $start_bound ) ) ); ?>" style="height: 30px; background: #fff;">
                            </div>
                            <div>
                                <label style="font-weight: 600; font-size: 13px;">To Date:</label>
                                <input type="text" class="a1geo-flatpickr" name="end_date" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( $end_bound ) ) ); ?>" style="height: 30px; background: #fff;">
                            </div>
                        <?php else : ?>
                            <div>
                                <label style="font-weight: 600; font-size: 13px;">From Month:</label>
                                <input type="month" name="start_month" value="<?php echo esc_attr( $start_bound ); ?>" style="height: 30px;">
                            </div>
                            <div>
                                <label style="font-weight: 600; font-size: 13px;">To Month:</label>
                                <input type="month" name="end_month" value="<?php echo esc_attr( $end_bound ); ?>" style="height: 30px;">
                            </div>
                        <?php endif; ?>

                        <div>
                            <button type="submit" class="button button-primary" style="height: 30px; font-weight: 600;">Apply Filter</button>
                            <a href="<?php echo admin_url( 'admin.php?page=external-clicks-summary' ); ?>" class="button button-secondary" style="height: 30px; vertical-align: middle;">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- CHART DISPLAY -->
        <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
            <div style="max-height: 400px; position: relative;">
                <canvas id="a1geoClicksSummaryChart"></canvas>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr date pickers with dd.mm.yyyy display
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.a1geo-flatpickr', {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y'
                });
            }

            // Initialize Chart.js
            var ctx = document.getElementById('a1geoClicksSummaryChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode( $labels ); ?>,
                    datasets: [
                        {
                            label: 'Total Clicks',
                            data: <?php echo json_encode( $total_series ); ?>,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.05)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Google Ads',
                            data: <?php echo json_encode( $gads_series ); ?>,
                            borderColor: '#1a73e8',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
                        },
                        {
                            label: 'Organic / Direct',
                            data: <?php echo json_encode( $organic_series ); ?>,
                            borderColor: '#1e7e34',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3
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
                    }
                }
            });
        });
    </script>
    <?php
}