<?php
// -------------------------------------------------------------------------
// VENDOR GATEWAY AND UNPAID MONTHLY PERFORMANCE REPORT
// -------------------------------------------------------------------------

function ts_render_sliced_gateway_table() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'text-domain' ) );
    }

    global $wpdb;

    // 1. Capture Filter Selection Values
    $selected_month = isset( $_GET['filter_month'] ) ? sanitize_text_field( $_GET['filter_month'] ) : 'all';
    $selected_year  = isset( $_GET['filter_year'] ) ? sanitize_text_field( $_GET['filter_year'] ) : date( 'Y' );

    // 2. Fetch Unique Invoice Years from Database for the Filter Dropdown
    $available_years = $wpdb->get_col( "
        SELECT DISTINCT YEAR(post_date) as post_year 
        FROM {$wpdb->posts} 
        WHERE post_type = 'sliced_invoice' 
          AND post_status IN ('publish', 'any')
        ORDER BY post_year DESC
    " );
    if ( empty( $available_years ) ) {
        $available_years = array( date( 'Y' ) );
    }

    // 3. Build Post Query Args based on Filters matching standard invoicing loops
    $args = array(
        'post_type'      => 'sliced_invoice',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    );

    if ( $selected_month !== 'all' ) {
        $args['date_query'] = array(
            array(
                'year'  => intval( $selected_year ),
                'month' => intval( $selected_month ),
            ),
        );
    } else {
        $args['date_query'] = array(
            array(
                'year' => intval( $selected_year ),
            ),
        );
    }

    $query = new WP_Query( $args );
    $invoices_dataset = array();

    // 4. Build complete structured array matching the exact format from your invoice pages
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();

            $invoice_no = get_post_meta( $id, '_sliced_invoice_number', true );
            $invoice_no = ! empty( $invoice_no ) ? $invoice_no : get_the_title();
            
            $formatted_total = sliced_get_invoice_total( $id );
            $clean_total     = preg_replace( '/[^0-9.-]/', '', $formatted_total );
            $gross_total     = ! empty( $clean_total ) ? floatval( $clean_total ) : 0.00;

            $terms  = wp_get_object_terms( $id, 'invoice_status' );
            $status = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0]->slug : 'unpaid';

            $payments      = get_post_meta( $id, '_sliced_payment', true );
            $gateway_label = '';

            if ( $status === 'paid' && is_array( $payments ) && ! empty( $payments ) ) {
                $last_payment = end( $payments );
                $resolved_gateway = 'not_set';
                
                if ( isset( $last_payment['gateway'] ) ) {
                    $resolved_gateway = $last_payment['gateway'];
                } elseif ( isset( $last_payment['method'] ) ) {
                    $resolved_gateway = $last_payment['method'];
                } elseif ( isset( $last_payment['payment_method'] ) ) {
                    $resolved_gateway = $last_payment['payment_method'];
                }
                $gateway_label = ucfirst( str_replace( '_', ' ', $resolved_gateway ) );
            } else {
                $assigned_gateways = get_post_meta( $id, '_sliced_payment_methods', true );
                
                if ( ! empty( $assigned_gateways ) ) {
                    if ( ! is_array( $assigned_gateways ) ) {
                        $assigned_gateways = array( $assigned_gateways );
                    }
                    
                    $clean_assigned = array_map( function( $gw ) {
                        return ucfirst( str_replace( '_', ' ', $gw ) );
                    }, $assigned_gateways );
                    
                    $gateway_label = implode( ' / ', $clean_assigned );
                } else {
                    $gateway_label = '—';
                }
            }

            $created_ts = get_post_meta( $id, '_sliced_invoice_created', true );
            $due_ts     = get_post_meta( $id, '_sliced_invoice_due', true );
            $date_created = ! empty( $created_ts ) ? date_i18n( 'd/m/Y', intval( $created_ts ) ) : get_the_date( 'd/m/Y' );
            $date_due     = ! empty( $due_ts ) ? date_i18n( 'd/m/Y', intval( $due_ts ) ) : '—';

            $email_sent_ind = get_post_meta( $id, '_sliced_invoice_email_sent', true );

            $client_title = '';
            $user_login   = '';
            $vendor_phone = '';
            $client_link  = get_post_meta( $id, '_sliced_client', true );

            if ( ! empty( $client_link ) ) {
                $wp_user_id = 0;

                if ( get_post_type( $client_link ) === 'sliced_client' ) {
                    $client_title = get_the_title( $client_link );
                    $wp_user_id   = intval( get_post_meta( $client_link, '_wordpress_user_id', true ) );
                } else {
                    $user_data = get_userdata( $client_link );
                    if ( $user_data ) {
                        $client_title = $user_data->display_name;
                        $user_login   = $user_data->user_login;
                        $wp_user_id   = intval( $client_link );
                    }
                }

                if ( $wp_user_id > 0 ) {
                    $user_data = get_userdata( $wp_user_id );
                    if ( $user_data ) {
                        $user_login = $user_data->user_login;
                    }

                    if ( function_exists( 'dokan_get_vendor' ) ) {
                        $vendor = dokan_get_vendor( $wp_user_id );
                        if ( $vendor && method_exists( $vendor, 'get_phone' ) ) {
                            $vendor_phone = $vendor->get_phone();
                        }
                    }

                    if ( empty( $vendor_phone ) ) {
                        $vendor_phone = get_user_meta( $wp_user_id, 'billing_phone', true );
                    }
                }
            }

            $invoices_dataset[] = array(
                'id'             => $id,
                'invoice_number' => $invoice_no,
                'total_amount'   => $gross_total,
                'created_date'   => $date_created,
                'due_date'       => $date_due,
                'status'         => $status,
                'gateway'        => ! empty( $gateway_label ) ? $gateway_label : '—',
                'raw_payments'   => $payments, 
                'client_name'    => $client_title,
                'client_login'   => $user_login,
                'vendor_phone'   => $vendor_phone,
                'email_sent_ind' => ( intval( $email_sent_ind ) > 0 )
            );
        }
        wp_reset_postdata();
    }

    // 5. Run standard metrics calculator function using our full array
    $metrics = array( 'gateways' => array() );
    if ( function_exists( 'a1_calculate_vendor_invoice_metrics' ) ) {
        $metrics = a1_calculate_vendor_invoice_metrics( $invoices_dataset );
    }

    // Map global metrics matching standard names generated via ucfirst formatting rules
    $paypal_total_paid = isset( $metrics['gateways']['Paypal'] ) ? (float) $metrics['gateways']['Paypal'] : 0.0;
    $stripe_total_paid = isset( $metrics['gateways']['Stripe'] ) ? (float) $metrics['gateways']['Stripe'] : 0.0;

    // 6. Loop through the dataset to aggregate unpaid balances AND gateway volumes by month
    $monthly_performance = array();
    foreach ( $invoices_dataset as $inv ) {
        $date_obj    = DateTime::createFromFormat( 'd/m/Y', $inv['created_date'] );
        $month_key   = $date_obj ? $date_obj->format( 'Y-m' ) : date( 'Y-m' );
        $month_label = $date_obj ? date_i18n( 'F Y', $date_obj->getTimestamp() ) : date_i18n( 'F Y' );

        if ( ! isset( $monthly_performance[$month_key] ) ) {
            $monthly_performance[$month_key] = array(
                'label'          => $month_label,
                'unpaid_amount'  => 0.0,
                'paypal_amount'  => 0.0,
                'stripe_amount'  => 0.0,
            );
        }

        // Aggregate unpaid allocations
        if ( $inv['status'] === 'unpaid' && $inv['total_amount'] > 0 ) {
            $monthly_performance[$month_key]['unpaid_amount'] += $inv['total_amount'];
        }

        // Aggregate metric volumes matching how your computation function works per row
        if ( $inv['status'] === 'paid' && is_array( $inv['raw_payments'] ) ) {
            foreach ( $inv['raw_payments'] as $payment ) {
                $amount = isset( $payment['amount'] ) ? (float) $payment['amount'] : 0.0;
                
                $resolved_gw = 'not_set';
                if ( isset( $payment['gateway'] ) ) {
                    $resolved_gw = $payment['gateway'];
                } elseif ( isset( $payment['method'] ) ) {
                    $resolved_gw = $payment['method'];
                } elseif ( isset( $payment['payment_method'] ) ) {
                    $resolved_gw = $payment['payment_method'];
                }

                $clean_gw = ucfirst( str_replace( '_', ' ', $resolved_gw ) );

                if ( $clean_gw === 'Paypal' ) {
                    $monthly_performance[$month_key]['paypal_amount'] += $amount;
                } elseif ( $clean_gw === 'Stripe' ) {
                    $monthly_performance[$month_key]['stripe_amount'] += $amount;
                }
            }
        }
    }

    krsort( $monthly_performance );
    ?>
    <div class="wrap" style="clear: both; margin-top: 20px;">
        <h1 class="wp-heading-inline"><?php _e( 'Invoice Performance Metric Summary', 'text-domain' ); ?></h1>
        <hr class="wp-header-end" style="clear: both; margin-bottom: 20px;">

        <!-- Month & Year Filtering Panel -->
        <div class="a1-filter-panel-container" style="clear: both; width: 100%; display: block; margin: 0 0 30px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); box-sizing: border-box;">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <input type="hidden" name="page" value="sliced-gateway-reports" />
                
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="filter_month" style="font-weight: 600;"><?php _e( 'Month:', 'text-domain' ); ?></label>
                    <select name="filter_month" id="filter_month" style="min-width: 140px;">
                        <option value="all" <?php selected( $selected_month, 'all' ); ?>><?php _e( 'All Months', 'text-domain' ); ?></option>
                        <?php 
                        for ( $m = 1; $m <= 12; $m++ ) {
                            $month_val = str_pad( $m, 2, '0', STR_PAD_LEFT );
                            $month_lbl = date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) );
                            echo '<option value="' . esc_attr( $month_val ) . '" ' . selected( $selected_month, $month_val, false ) . '>' . esc_html( $month_lbl ) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="filter_year" style="font-weight: 600;"><?php _e( 'Year:', 'text-domain' ); ?></label>
                    <select name="filter_year" id="filter_year" style="min-width: 100px;">
                        <?php foreach ( $available_years as $yr ) : ?>
                            <option value="' . esc_attr( $yr ) . '" <?php selected( $selected_year, $yr ); ?>><?php echo esc_html( $yr ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="button button-primary"><?php _e( 'Apply Filter', 'text-domain' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=sliced-gateway-reports' ) ); ?>" class="button button-secondary"><?php _e( 'Reset Filters', 'text-domain' ); ?></a>
                </div>
            </form>
        </div>

        <!-- Summary Metric Flex Tiles Grid Layout -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; width: 100%;">
            
            <!-- PART 1: PAYPAL TOTAL PAID -->
            <div style="background: #fff; padding: 24px; border-left: 4px solid #003087; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); box-sizing: border-box;">
                <div style="font-size: 11px; color: #646970; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-admin-links" style="color: #003087; font-size: 16px; width: 16px; height: 16px;"></span>
                    <?php _e( 'PayPal Gateway Volume', 'text-domain' ); ?>
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #1d2327; margin-top: 12px;"><?php echo wc_price( $paypal_total_paid ); ?></div>
                <div style="font-size: 12px; color: #8c8f94; margin-top: 6px; font-style: italic;"><?php _e( 'Aggregate volume captured through PayPal integrations.', 'text-domain' ); ?></div>
            </div>

            <!-- PART 2: STRIPE TOTAL PAID -->
            <div style="background: #fff; padding: 24px; border-left: 4px solid #635bff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); box-sizing: border-box;">
                <div style="font-size: 11px; color: #646970; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-cart" style="color: #635bff; font-size: 16px; width: 16px; height: 16px;"></span>
                    <?php _e( 'Stripe Gateway Volume', 'text-domain' ); ?>
                </div>
                <div style="font-size: 28px; font-weight: 700; color: #1d2327; margin-top: 12px;"><?php echo wc_price( $stripe_total_paid ); ?></div>
                <div style="font-size: 12px; color: #8c8f94; margin-top: 6px; font-style: italic;"><?php _e( 'Aggregate volume captured through Stripe credit card infrastructure.', 'text-domain' ); ?></div>
            </div>

        </div>

        <!-- PART 3: MONTHLY BREAKDOWN TABLE WITH GATEWAY AMOUNTS -->
        <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); box-sizing: border-box; overflow: hidden; max-width: 100%;">
            <div style="padding: 16px 20px; background: #f6f7f7; border-bottom: 1px solid #ccd0d4; margin: 0;">
                <h2 style="font-size: 14px; margin: 0; line-height: 1.4; font-weight: 600; color: #1d2327; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-calendar-alt" style="color: #646970;"></span>
                    <?php _e( 'Monthly Performance & Outstanding Balances Summary', 'text-domain' ); ?>
                </h2>
            </div>
            
            <table class="wp-list-table widefat fixed striped posts" style="border: none; box-shadow: none;">
                <thead>
                    <tr>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600;"><?php _e( 'Billing Period / Month', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; text-align: right;"><?php _e( 'PayPal Volume', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; text-align: right;"><?php _e( 'Stripe Volume', 'text-domain' ); ?></th>
                        <th scope="col" style="padding: 12px 20px; font-weight: 600; text-align: right; color: #d63638;"><?php _e( 'Total Unpaid Balances', 'text-domain' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $monthly_performance ) ) : ?>
                        <?php foreach ( $monthly_performance as $data ) : 
                            // Skip months that have absolutely zero volume across all parameters to keep table pristine
                            if ( $data['unpaid_amount'] == 0 && $data['paypal_amount'] == 0 && $data['stripe_amount'] == 0 ) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td style="padding: 12px 20px; font-size: 13px; vertical-align: middle;">
                                    <strong><?php echo esc_html( $data['label'] ); ?></strong>
                                </td>
                                <td style="padding: 12px 20px; font-size: 13px; text-align: right; color: #003087; vertical-align: middle;">
                                    <?php echo $data['paypal_amount'] > 0 ? wc_price( $data['paypal_amount'] ) : '—'; ?>
                                </td>
                                <td style="padding: 12px 20px; font-size: 13px; text-align: right; color: #635bff; vertical-align: middle;">
                                    <?php echo $data['stripe_amount'] > 0 ? wc_price( $data['stripe_amount'] ) : '—'; ?>
                                </td>
                                <td style="padding: 12px 20px; font-size: 14px; font-weight: 600; color: #d63638; text-align: right; vertical-align: middle;">
                                    <?php echo $data['unpaid_amount'] > 0 ? wc_price( $data['unpaid_amount'] ) : '—'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; font-style: italic; color: #646970;">
                                <?php _e( 'No volume or outstanding balances found for the selected timeframe.', 'text-domain' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}