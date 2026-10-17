<?php
// -------------------------------------------------------------------------
// BILLING PERIOD RUN HISTORY AND COMPLIANCE MONITOR
// -------------------------------------------------------------------------

function ts_render_billing_period_summary_table() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'text-domain' ) );
    }

    global $wpdb;
    $table_name = 'billing_period_summary';

    // 1. Check if table exists to prevent errors
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
        echo '<div class="notice notice-error"><p>' . sprintf( __( 'Database table %s does not exist.', 'text-domain' ), esc_html( $table_name ) ) . '</p></div>';
        return;
    }

    // 2. Fetch all billing periods ordered chronologically descending
    $billing_periods = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY from_date DESC" );
    ?>
    <div class="wrap" style="clear: both; margin-top: 20px;">
        <h1 class="wp-heading-inline"><?php _e( 'Automated Billing Period Run Logs', 'text-domain' ); ?></h1>
        <hr class="wp-header-end" style="clear: both; margin-bottom: 20px;">

        <!-- Billing Period History Log Table Container -->
        <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); box-sizing: border-box; overflow: hidden; max-width: 100%;">
            <div style="padding: 16px 20px; background: #f6f7f7; border-bottom: 1px solid #ccd0d4; margin: 0;">
                <h2 style="font-size: 14px; margin: 0; line-height: 1.4; font-weight: 600; color: #1d2327; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-database-export" style="color: #646970;"></span>
                    <?php _e( 'Historical Statement Execution & Generation Performance Logs', 'text-domain' ); ?>
                </h2>
            </div>

            <table class="wp-list-table widefat fixed striped posts" style="border: none; box-shadow: none;">
                <thead>
                    <tr>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; width: 80px;"><?php _e( 'Run ID', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600;"><?php _e( 'Billing Cycle Window', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; width: 140px; text-align: center;"><?php _e( 'Status', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; width: 140px; text-align: center;"><?php _e( 'Invoices Generated', 'text-domain' ); ?></th>                        
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; width: 180px;"><?php _e( 'Execution Timestamp', 'text-domain' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $billing_periods ) ) : ?>
                        <?php foreach ( $billing_periods as $period ) : 
                            // Convert database date markers to scannable presentation strings
                            $formatted_from = date_i18n( 'M d, Y', strtotime( $period->from_date ) );
                            $formatted_to   = date_i18n( 'M d, Y', strtotime( $period->to_date ) );
                            $formatted_exec = date_i18n( 'd/m/Y H:i:s', strtotime( $period->executed_at ) );
                            
                            ?>
                            <tr>
                                <!-- Run ID -->
                                <td style="padding: 12px 20px; font-size: 13px; color: #646970; vertical-align: middle;">
                                    <code>#<?php echo esc_html( $period->id ); ?></code>
                                </td>

                                <!-- Billing Cycle Window -->
                                <td style="padding: 12px 20px; font-size: 13px; vertical-align: middle;">
                                    <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; color: #8c8f94; margin-right: 4px; vertical-align: text-top;"></span>
                                    <strong><?php echo esc_html( $formatted_from ); ?></strong> 
                                    <span style="color: #8c8f94; margin: 0 4px;">&rarr;</span> 
                                    <strong><?php echo esc_html( $formatted_to ); ?></strong>
                                </td>

                                <!-- Invoice Created Indicator Status Badge -->
                                <td style="padding: 12px 20px; text-align: center; vertical-align: middle;">
                                    <?php if ( intval( $period->invoice_created_ind ) === 1 ) : ?>
                                        <span style="display: inline-block; padding: 4px 10px; background-color: #e5f5fa; color: #007cba; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                            <?php _e( 'Invoiced', 'text-domain' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="display: inline-block; padding: 4px 10px; background-color: #f0f0f1; color: #50575e; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px;">
                                            <?php _e( 'Idle / Open', 'text-domain' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Invoices Count -->
                                <td style="padding: 12px 20px; font-size: 14px; font-weight: 600; text-align: center; vertical-align: middle; color: #1d2327;">
                                    <?php echo number_format_i18n( $period->invoices_count ); ?>
                                </td>

                                <!-- Execution System Timestamp -->
                                <td style="padding: 12px 20px; font-size: 13px; color: #50575e; vertical-align: middle;">
                                    <span class="dashicons dashicons-clock" style="font-size: 15px; width: 15px; height: 15px; color: #8c8f94; margin-right: 4px; vertical-align: text-top;"></span>
                                    <?php echo esc_html( $formatted_exec ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="padding: 32px; text-align: center; font-style: italic; color: #646970;">
                                <span class="dashicons dashicons-info" style="font-size: 20px; width: 20px; height: 20px; margin-bottom: 8px; color: #a7aaad; display: block; margin-left: auto; margin-right: auto;"></span>
                                <?php _e( 'No system execution runs or billing histories have been tracked yet.', 'text-domain' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}