<?php
/**
 * Displays selection filters on top and routes shortcode configurations below
 */
function render_page_view_summary() {
    // 1. Get chosen Filter Mode via URL parameters (defaulting to 'month')
    $filter_mode = isset( $_GET['filter_mode'] ) ? sanitize_key( $_GET['filter_mode'] ) : 'month';

    // 2. Resolve Dynamic Range Bound Enforcements
    if ( 'date' === $filter_mode ) {
        $start_bound = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : current_time( 'Y-m-d' );
        $end_bound   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : current_time( 'Y-m-d' );
        
        // Convert to d-m-Y display formats for the custom text inputs
        $display_start = date( 'd-m-Y', strtotime( $start_bound ) );
        $display_end   = date( 'd-m-Y', strtotime( $end_bound ) );
    } else {
        $start_bound = isset( $_GET['start_month'] ) ? sanitize_text_field( $_GET['start_month'] ) : current_time( 'Y-m' );
        $end_bound   = isset( $_GET['end_month'] ) ? sanitize_text_field( $_GET['end_month'] ) : current_time( 'Y-m' );
    }

    // Set variable strings to pipe directly inside your wpDataTable context bindings
    $wdt_var1 = $filter_mode;  // 'date' or 'month'
    $wdt_var2 = $start_bound;   // e.g., '2026-07-01' or '2026-01'
    $wdt_var3 = $end_bound;     // e.g., '2026-07-09' or '2026-07'
    ?>

    <div class="wrap" style="margin-top: 20px; max-width: 1200px;">
        <h1 style="margin-bottom: 20px; font-weight: 700; color: #23282d;">
            <?php esc_html_e( 'A1 Management — Traffic Analytics', 'a1-geo' ); ?>
        </h1>

        <!-- BLOCK 1: RADIO CONTROL FILTER TOOLBAR (TOP) -->
        <div class="notice notice-info" style="margin: 0 0 30px 0; padding: 20px; background: #fff; border-left-color: #b52025; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
            <form method="GET" action="admin.php" id="a1-management-filter-form">
                <!-- Explicitly maintain admin page state so the form targets admin.php?page=page-view-summary -->
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
                            <!-- From Date Selection Combo -->
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'From Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_start_date" value="<?php echo esc_attr( $display_start ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
                                    <input type="date" id="native_start_date" name="start_date" value="<?php echo esc_attr( $start_bound ); ?>" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; z-index: 1; cursor: pointer;">
                                </div>
                            </div>
                            
                            <!-- To Date Selection Combo -->
                            <div style="display: flex; align-items: center;">
                                <label style="font-weight: 600; margin-right: 5px; font-size: 13px; color: #333;"><?php esc_html_e( 'To Date:', 'a1-geo' ); ?></label>
                                <div style="position: relative; display: inline-block;">
                                    <input type="text" id="ui_end_date" value="<?php echo esc_attr( $display_end ); ?>" placeholder="dd-mm-yyyy" readonly style="height: 30px; padding: 0 8px; width: 120px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;">
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

                        // Click user field -> open native default browser pop-up
                        uiInput.addEventListener('click', function() {
                            if (typeof nativeInput.showPicker === 'function') {
                                nativeInput.showPicker();
                            } else {
                                nativeInput.click();
                            }
                        });

                        // Re-format immediately on selection event change
                        nativeInput.addEventListener('change', function() {
                            var dateVal = this.value; // format: yyyy-mm-dd
                            if (dateVal) {
                                var parts = dateVal.split('-');
                                if (parts.length === 3) {
                                    // Convert yyyy-mm-dd -> dd-mm-yyyy
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

        <!-- BLOCK 2: GRID FOR DYNAMIC CHART & METRIC TABLE VISUALIZATION -->
        <div class="a1-dashboard-grid" style="display: grid; grid-template-columns: 1fr; gap: 30px; align-items: start;">
            
            <!-- VISUAL ANALYTICS WPCHART BLOCK -->
            <div class="a1-dashboard-block chart-block" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #23282d;">
                    <span class="dashicons dashicons-chart-bar" style="margin-right: 8px; color: #b52025; vertical-align: text-bottom;"></span>
                    <?php 
                    if ( 'date' === $filter_mode ) {
                        esc_html_e( 'Traffic Analysis (By Selected Date Range)', 'a1-geo' );
                    } else {
                        esc_html_e( 'Traffic Analysis (By Selected Month Range)', 'a1-geo' );
                    }
                    ?>
                </h3>
                <div class="chart-container">
                    <?php 
                    if ( 'date' === $filter_mode ) {
                        echo do_shortcode( sprintf( '[wpdatachart id="1" var1="%s" var2="%s" ]', esc_attr($wdt_var2), esc_attr($wdt_var3) ) ); 
                        echo do_shortcode( sprintf( '[wpdatatable id="61" var1="%s" var2="%s" ]', esc_attr($wdt_var2), esc_attr($wdt_var3) ) ); 
                    } else {
                        echo do_shortcode( sprintf( '[wpdatachart id="2" var1="%s" var2="%s" ]', esc_attr($wdt_var2), esc_attr($wdt_var3) ) ); 
                    }
                    ?>
                </div>
            </div>

            <!-- METRICS DATA DETAILS WPDATABLE BLOCK -->
            <div class="a1-dashboard-block table-block" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #23282d;">
                    <span class="dashicons dashicons-database" style="margin-right: 8px; color: #0073aa; vertical-align: text-bottom;"></span>
                    <?php esc_html_e( 'Detailed Page Performance Metrics', 'a1-geo' ); ?>
                </h3>
                <div class="wp-responsive-table">
                    <?php 
                    echo do_shortcode( sprintf( '[wpdatatable id="60" var1="%s" var2="%s"]', esc_attr($wdt_var2), esc_attr($wdt_var3) ) ); 
                    ?>
                </div>
            </div>

        </div>
    </div>
    <?php
}


function render_daily_view_drilldown() {
    global $wpdb;
    $table_name = 'page_access_logs';

    // 1. Resolve Target Active Day Date Window Parameter (Keep SQL standard Y-m-d internally)
    $target_date = isset( $_GET['target_date'] ) ? sanitize_text_field( $_GET['target_date'] ) : current_time( 'Y-m-d' );
    
    // Convert to d-m-Y format for the user-facing text field
    $display_date = date( 'd-m-Y', strtotime( $target_date ) );
    
    // Check if the administrator is examining a specific deep nested URL entry view
    $view_url = isset( $_GET['view_url'] ) ? wp_unslash( $_GET['view_url'] ) : '';

    echo '<div class="wrap" style="margin-top: 20px; max-width: 1200px;">';
    echo '<h1 style="font-weight: 700; color: #23282d; margin-bottom: 20px;">' . esc_html__( 'A1 Management — Daily Audit Tracking', 'a1-geo' ) . '</h1>';

    // --- RENDER INTERFACE BACK NAVIGATION LINK & TARGET FILTER BAR ---
    ?>
    <div class="notice notice-info" style="margin: 0 0 25px 0; padding: 15px; background: #fff; border-left-color: #0073aa; box-shadow: 0 1px 1px rgba(0,0,0,.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <form method="GET" action="admin.php" id="daily-audit-date-form" style="display: flex; align-items: center; gap: 10px; margin: 0;">
            <input type="hidden" name="page" value="daily-view-drilldown">
            <label style="font-weight: 600; font-size: 13px; color: #333;"><?php esc_html_e( 'Select Target Day Audit:', 'a1-geo' ); ?></label>
            
            <div style="position: relative; display: inline-block;">
                <!-- User Facing Input: Beautifully formatted as dd-mm-yyyy -->
                <input type="text" 
                       id="ui_date_picker" 
                       value="<?php echo esc_attr( $display_date ); ?>" 
                       placeholder="dd-mm-yyyy" 
                       readonly
                       style="height: 30px; padding: 0 8px; width: 130px; text-align: center; background: #fff; cursor: pointer; z-index: 2; position: relative;"
                >
                
                <!-- Native Hidden Picker: Invisible but triggers the default system popup calendar when clicked -->
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

                // When user clicks the text input, programmatically trigger the default browser popup
                uiInput.addEventListener('click', function() {
                    if (typeof nativeInput.showPicker === 'function') {
                        nativeInput.showPicker();
                    } else {
                        nativeInput.click();
                    }
                });

                // Update the text box format immediately after picking a date from the default popup
                nativeInput.addEventListener('change', function() {
                    var dateVal = this.value; // format: yyyy-mm-dd
                    if (dateVal) {
                        var parts = dateVal.split('-');
                        if (parts.length === 3) {
                            // Convert yyyy-mm-dd -> dd-mm-yyyy
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
        // Query unique visitor IPs hitting this explicitly targeted path link on the target date string
        $ip_results = $wpdb->get_results( $wpdb->prepare(
            "SELECT ip_address, user_agent, COUNT(id) AS hit_count, MAX(accessed_at) AS last_seen
             FROM $table_name
             WHERE DATE(accessed_at) = %s AND page_url = %s
             GROUP BY ip_address, user_agent
             ORDER BY hit_count DESC",
            $target_date,
            $view_url
        ) );
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
                        <th style="font-weight: 700; width: 15%; text-align: right;"><?php esc_html_e( 'Last Access Time', 'a1-geo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $ip_results ) ) : ?>
                        <tr><td colspan="4"><?php esc_html_e( 'No log entries traced for this target item.', 'a1-geo' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $ip_results as $row ) : 
                            $ua_lower = strtolower( $row->user_agent );
                            
                            $is_google  = str_contains( $ua_lower, 'google' );
                            $is_bing    = str_contains( $ua_lower, 'bing' );
                            $is_claude  = str_contains( $ua_lower, 'claude-searchbot' ) || str_contains( $ua_lower, 'anthropic.com' );
                            $is_openai  = str_contains( $ua_lower, 'open-ai.com' ) || str_contains( $ua_lower, 'openai' );
                            
                            $is_bot = ($is_google || $is_bing || $is_claude || $is_openai);
                            ?>
                            <tr style="<?php echo $is_bot ? 'opacity: 0.75;' : ''; ?>">
                                <td>
                                    <strong>
                                        <span class="dashicons dashicons-location-alt" style="font-size:16px; color:#666; vertical-align:middle; margin-right:4px;"></span>
                                        <?php echo esc_html( $row->ip_address ); ?>
                                    </strong>
                                    
                                    <?php if ( $is_google ) : ?>
                                        <span style="font-weight: 900; color: #dc3232; margin-left: 6px; font-size: 14px;" title="Google Agent / Crawler">G</span>
                                    <?php elseif ( $is_bing ) : ?>
                                        <span style="font-weight: 900; color: #ffb900; text-shadow: 0px 1px 1px rgba(0,0,0,0.2); margin-left: 6px; font-size: 14px;" title="Bing Agent / Crawler">B</span>
                                    <?php elseif ( $is_claude || $is_openai ) : ?>
                                        <span style="font-weight: 900; color: #72777c; margin-left: 6px; font-size: 14px;" title="<?php echo $is_claude ? 'Anthropic Claude Bot' : 'OpenAI Bot'; ?>">AI</span>
                                    <?php else : ?>
                                        <span style="font-weight: 900; color: #46b450; margin-left: 6px; font-size: 14px;" title="Other / Human Visitor">O</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; color:#555; font-family: monospace;"><?php echo esc_html( $row->user_agent ); ?></td>
                                <td style="text-align: center;"><span class="update-plugins count-filter" style="background:#f6f7f7; color:#3c434a; font-weight:600; padding:2px 8px; border-radius:10px; border:1px solid #dcdcde;"><?php echo (int) $row->hit_count; ?></span></td>
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
        // Query daily metrics tracking actual clicks where Agent string filters out search engine crawlers and known AI engines
        $summary_results = $wpdb->get_results( $wpdb->prepare(
            "SELECT page_url, 
                    COUNT(id) AS view_count, 
                    COUNT(DISTINCT ip_address) AS unique_visitors,
                    SUM(CASE WHEN 
                        user_agent NOT LIKE '%%google%%' AND 
                        user_agent NOT LIKE '%%bing%%' AND 
                        user_agent NOT LIKE '%%claude-searchbot%%' AND 
                        user_agent NOT LIKE '%%anthropic%%' AND 
                        user_agent NOT LIKE '%%open-ai%%' AND
                        user_agent NOT LIKE '%%openai%%'
                    THEN 1 ELSE 0 END) AS actual_clicks
             FROM $table_name
             WHERE DATE(accessed_at) = %s
             GROUP BY page_url
             ORDER BY actual_clicks DESC, view_count DESC",
            $target_date
        ) );
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
                        <th style="font-weight: 700; width: 15%; text-align: center;"><?php esc_html_e( 'Unique Visitors (IPs)', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 15%; text-align: center; color: #2271b1;"><?php esc_html_e( 'Actual Clicks (Filtered)', 'a1-geo' ); ?></th>
                        <th style="font-weight: 700; width: 20%; text-align: center;"><?php esc_html_e( 'Raw Views (Inc. Bots)', 'a1-geo' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $summary_results ) ) : ?>
                        <tr><td colspan="4"><?php esc_html_e( 'No system data logging records identified within this specific calendar frame context.', 'a1-geo' ); ?></td></tr>
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

    echo '</div>'; // Close wrap
}
