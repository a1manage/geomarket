<?php
// -------------------------------------------------------------------------
// VENDOR Billable Summary SUBMENU CONTROLLER USING THE CORE WP_LIST_TABLE API
// -------------------------------------------------------------------------

// Ensure the core WordPress standard WP_List_Table dependency class is loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Plugin Name: A1 Management - Billable Vendor Summary Class Table Enhanced
 * Description: Displays a grouped vendor performance dashboard extending WP_List_Table with automated multi-line invoice generation math.
 * Version: 2.9
 * Author: Developer
 */

// -------------------------------------------------------------------------
// AUTOMATIC FORCED DATABASE INSTALLATION ROUTINE (RUNS ONCE ON ADMIN LOAD)
// -------------------------------------------------------------------------
add_action( 'admin_init', 'ts_force_install_billing_run_table' );
function ts_force_install_billing_run_table() {
    global $wpdb;
    
    // Incrementing to version v2 to force a re-run with the updated column name
    if ( get_option( 'ts_billing_table_created_v2' ) ) {
        return;
    }

    $table_name = 'billing_period_summary'; 
    $charset_collate = $wpdb->get_charset_collate();

    // Drop the old schema structure if it was partially built during previous attempts
    $wpdb->query( "DROP TABLE IF EXISTS `$table_name`;" );

    // Direct raw SQL structure execution with invoice_created_ind and no global WP prefix
    $sql = "CREATE TABLE `$table_name` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `from_date` date NOT NULL,
        `to_date` date NOT NULL,
        `invoice_created_ind` tinyint(1) DEFAULT 0 NOT NULL,
        `invoices_count` int(11) DEFAULT 0 NOT NULL,
        `executed_at` datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";

    // Execute directly via $wpdb to force it through immediately
    $wpdb->query( $sql );

    // Mark version 2 as complete so it doesn't process again
    update_option( 'ts_billing_table_created_v2', true );
}

// -------------------------------------------------------------------------
// 1. REGISTER EXTENDED WP_LIST_TABLE CORE CLASS
// -------------------------------------------------------------------------
class TS_Billable_Vendor_Table extends WP_List_Table {

    private $raw_data = array();

    public function __construct( $data = array() ) {
        parent::__construct( array(
            'singular' => 'vendor_record',
            'plural'   => 'vendor_records',
            'ajax'     => false
        ) );
        $this->raw_data = $data;
    }

    public function get_columns() {
        return array(
            'vendor_id'       => __( 'Vendor ID', 'text-domain' ),
            'vendor_name'     => __( 'Vendor Name', 'text-domain' ),
            'vendor_account'  => __( 'Vendor Account', 'text-domain' ),            
            'running_day'     => __( 'Running Date', 'text-domain' ),
            'product_id'      => __( 'Product ID', 'text-domain' ),
            'List_status'     => __( 'List Status', 'text-domain' ),
            'AD_status'       => __( 'AD Status', 'text-domain' ),
            'List_price_code' => __( 'List Price Code', 'text-domain' ),
            'AD_price_code'   => __( 'AD Price Code', 'text-domain' ),
            'spare1'          => __( 'Spare 1', 'text-domain' ),
            'spare2'          => __( 'Spare 2', 'text-domain' ),
            'spare3'          => __( 'Spare 3', 'text-domain' ),
            'spare4'          => __( 'Spare 4', 'text-domain' )
        );
    }

    protected function get_sortable_columns() {
        return array(
            'vendor_id'   => array( 'vendor_id', true ),
            'running_day' => array( 'running_day', false )
        );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'vendor_id':
            case 'vendor_name':
            case 'vendor_account':
            case 'product_id':
                return esc_html( $item[$column_name] );
            case 'List_price_code':
            case 'AD_price_code':
                return '<code>' . esc_html( $item[$column_name] ) . '</code>';
            case 'spare1':
                return esc_html( $item['spare1'] );
            case 'spare2':
                return esc_html( $item['spare2'] );
            case 'spare3':
                return esc_html( $item['spare3'] );
            case 'spare4':
                return esc_html( $item['spare4'] );
            default:
                return print_r( $item, true );
        }
    }

    public function column_running_day( $item ) {
        if ( ! empty( $item['running_day'] ) ) {
            return '<strong>' . esc_html( date( 'd/m/Y', strtotime( $item['running_day'] ) ) ) . '</strong>';
        }
        return '—';
    }

    public function column_List_status( $item ) {
        return '<span style="color: #46b450; font-weight: 600;">' . esc_html( $item['List_status'] ) . '</span>';
    }

    public function column_AD_status( $item ) {
        return '<span style="color: #135e96; font-weight: 600;">' . esc_html( $item['AD_status'] ) . '</span>';
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $total_items  = count( $this->raw_data );

        $this->items = array_slice( $this->raw_data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
    }
}

// -------------------------------------------------------------------------
// 2. RETRIEVAL AND ADVANCED GROUPING ENGINE
// -------------------------------------------------------------------------
function ts_get_billable_vendor_data( $start_date, $end_date ) {
    global $wpdb;

    // Convert from dd/mm/yyyy format to standard DateTime if slashes are present
    if ( strpos( $start_date, '/' ) !== false ) {
        $begin = DateTime::createFromFormat( 'd/m/Y', $start_date );
    } else {
        $begin = new DateTime( $start_date );
    }

    if ( strpos( $end_date, '/' ) !== false ) {
        $end = DateTime::createFromFormat( 'd/m/Y', $end_date );
    } else {
        $end = new DateTime( $end_date );
    }

    // Fallback error boundaries
    if ( ! $begin ) { $begin = new DateTime( '-7 days' ); }
    if ( ! $end ) { $end = new DateTime(); }

    $end->modify( '+1 day' ); 
    $interval  = new DateInterval( 'P1D' );
    $daterange = new DatePeriod( $begin, $interval, $end );

    $raw_results = array();

    foreach ( $daterange as $date ) {
        $formatted_date = $date->format( 'Y_m_d' );
        $table_name     = 'backup_products_' . $formatted_date;

        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

        if ( $table_exists ) {
            $query = "
                SELECT running_day, vendor_id, product_id, List_status, AD_status, List_price_code, AD_price_code, spare1, spare2, spare3, spare4
                FROM `{$table_name}`
                WHERE (`List_status` != 0 OR `AD_status` != 0)
                  AND (`List_price_code` NOT IN (0, 11, 22) AND `AD_price_code` NOT IN (0, 11, 22))
            ";

            $day_results = $wpdb->get_results( $query, ARRAY_A );

            if ( ! empty( $day_results ) ) {
                $raw_results = array_merge( $raw_results, $day_results );
            }
        }
    }

    if ( empty( $raw_results ) ) {
        return array();
    }

    $grouped_data = array();
    
    foreach ( $raw_results as $row ) {
        $v_id = $row['vendor_id'];
        
        if ( ! isset( $grouped_data[$v_id] ) ) {
            $grouped_data[$v_id] = array();
        }
        
        $vendor_user = get_userdata( $v_id );
        $row['vendor_name']    = $vendor_user ? $vendor_user->display_name : __( 'Unknown Vendor', 'text-domain' );
        $row['vendor_account'] = $vendor_user ? $vendor_user->user_login : __( 'N/A', 'text-domain' );
        
        $grouped_data[$v_id][] = $row;
    }

    $final_sorted_list = array();
    ksort( $grouped_data, SORT_NUMERIC );

    foreach ( $grouped_data as $vendor_id => $records ) {
        usort( $records, function( $a, $b ) {
            return strtotime( $a['running_day'] ) <=> strtotime( $b['running_day'] );
        });
        
        $final_sorted_list = array_merge( $final_sorted_list, $records );
    }

    return $final_sorted_list;
}

// -------------------------------------------------------------------------
// 3. SCREEN CONTROLLER, DATA PERSISTENCE, AND EMAIL DISPATCH LAYER
// -------------------------------------------------------------------------
function ts_render_billable_vendor_summary_view() {
    global $wpdb;

    // Explicit target table with clean environmental layout setup
    $summary_table = 'billing_period_summary';

    // Enqueue WordPress core jQuery UI Datepicker assets
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css' );

    $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'd/m/Y', strtotime( '-7 days' ) );
    $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'd/m/Y' );

    // Normalize values if they arrive from standard ISO defaults
    if ( strpos( $start_date, '-' ) !== false ) { $start_date = date( 'd/m/Y', strtotime( $start_date ) ); }
    if ( strpos( $end_date, '-' ) !== false ) { $end_date = date( 'd/m/Y', strtotime( $end_date ) ); }

    // --- SETUP CODES FOR DIRECTORY SAVE & EMAIL DISPATCH ---
    $upload_dir = wp_upload_dir();
    $target_folder_path = $upload_dir['basedir'] . '/export_invoice_data';
    $safe_start = str_replace( '/', '_', $start_date );
    $safe_end   = str_replace( '/', '_', $end_date );

    $email_recipients = array(        
        'manage@a1-geomarket.com',
        'admin@a1-geomarket.com'
    );
    $email_headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    // HEADERS MATCHING THE WP_LIST_TABLE SCHEMATIC EXACTLY
    $table_csv_headers = array(
        'Vendor ID', 'Vendor Name', 'Vendor Account', 'Running Date', 'Product ID', 
        'List Status', 'AD Status', 'List Price Code', 'AD Price Code', 
        'Spare 1', 'Spare 2', 'Spare 3', 'Spare 4'
    );

    // =========================================================================
    // CATCH CSV DOWNLOAD TRIGGER (RAW DASHBOARD SUMMARY EXACT DATA)
    // =========================================================================
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to run exports.', 'text-domain' ) );
        }

        $export_data = ts_get_billable_vendor_data( $start_date, $end_date );

        if ( ! file_exists( $target_folder_path ) ) {
            wp_mkdir_p( $target_folder_path );
            file_put_contents( $target_folder_path . '/index.php', '<?php // Silence is golden' );
            file_put_contents( $target_folder_path . '/.htaccess', "Options -Indexes\nDeny from all" );
        }

        $raw_file_name = sprintf( 'raw_vendor_summary_report_%s_to_%s.csv', $safe_start, $safe_end );
        $raw_absolute_path = $target_folder_path . '/' . $raw_file_name;

        // 1. Build and save exact table rows to folder
        $write_fp = fopen( $raw_absolute_path, 'w' );
        if ( $write_fp ) {
            fputcsv( $write_fp, $table_csv_headers );

            if ( ! empty( $export_data ) ) {
                foreach ( $export_data as $row ) {
                    fputcsv( $write_fp, array(
                        $row['vendor_id'], $row['vendor_name'], $row['vendor_account'],
                        date( 'd/m/Y', strtotime( $row['running_day'] ) ), $row['product_id'],
                        $row['List_status'], $row['AD_status'], trim( $row['List_price_code'] ), trim( $row['AD_price_code'] ),
                        $row['spare1'], $row['spare2'], $row['spare3'], $row['spare4']
                    ) );
                }
            }
            fclose( $write_fp );

            // 2. Email exact table data file to distribution list
            $email_subject = sprintf( '[Raw Export] Billable Vendor Table Data: %s to %s', $start_date, $end_date );
            $email_body  = "Hello,\n\nAn export of the exact table data has been executed.\n\n";
            $email_body .= "Total Table Rows Processed: " . count( $export_data ) . "\n";
            $email_body .= "Saved on Server: /wp-content/uploads/export_invoice_data/" . $raw_file_name . "\n\n";
            $email_body .= "The exact table report has been attached.\n\nBest Regards,\nMarketplace Engine Core";

            wp_mail( $email_recipients, $email_subject, $email_body, $email_headers, array( $raw_absolute_path ) );
        }

        // 3. Output direct streaming download to browser
        if ( ob_get_length() ) { ob_end_clean(); }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $raw_file_name );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out_stream = fopen( 'php://output', 'w' );
        fputcsv( $out_stream, $table_csv_headers );
        if ( ! empty( $export_data ) ) {
            foreach ( $export_data as $row ) {
                fputcsv( $out_stream, array(
                    $row['vendor_id'], $row['vendor_name'], $row['vendor_account'],
                    date( 'd/m/Y', strtotime( $row['running_day'] ) ), $row['product_id'],
                    $row['List_status'], $row['AD_status'], trim( $row['List_price_code'] ), trim( $row['AD_price_code'] ),
                    $row['spare1'], $row['spare2'], $row['spare3'], $row['spare4']
                ) );
            }
        }
        fclose( $out_stream );
        exit;
    }

    // =========================================================================
    // CATCH GENERATE INVOICE TRIGGER (PROCESS ENGINE AND DISPATCH MATRIX)
    // =========================================================================
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'generate_invoice' ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( __( 'You do not have sufficient permissions to generate invoices.', 'text-domain' ) );
        }

        $start_timestamp = strtotime( str_replace( '/', '-', $start_date ) );
        $end_timestamp   = strtotime( str_replace( '/', '-', $end_date ) );
        
        $db_start_date = date( 'Y-m-d', $start_timestamp );
        $db_end_date   = date( 'Y-m-d', $end_timestamp );

        // --- CHECK FOR TIME PERIOD INTERFERENCE WRITES ---
        $clashing_periods = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$summary_table}` 
             WHERE (from_date <= %s AND to_date >= %s)", 
            $db_end_date, $db_start_date
        ) );

        $is_exact_match = false;
        $existing_period_id = null;

        if ( ! empty( $clashing_periods ) ) {
            foreach ( $clashing_periods as $period ) {
                if ( $period->from_date === $db_start_date && $period->to_date === $db_end_date ) {
                    $is_exact_match = true;
                    $existing_period_id = $period->id;
                    break;
                }
            }

            if ( ! $is_exact_match ) {
                wp_die( sprintf(
                    __( 'ERROR: The targeted date range (%s to %s) interferes with an a previous recorded date range. Please check all previous date range and try again.', 'text-domain' ),
                    esc_html( $start_date ),
                    esc_html( $end_date )
                ) );
            }
        }

        $all_records = ts_get_billable_vendor_data( $start_date, $end_date );
        
        $invoice_map = array();
        foreach ( $all_records as $row ) {
            $v_id = $row['vendor_id'];
            $p_id = $row['product_id'];
            
            if ( ! isset( $invoice_map[$v_id] ) ) {
                $invoice_map[$v_id] = array(
                    'vendor_name'    => $row['vendor_name'],
                    'vendor_account' => $row['vendor_account'],
                    'products'       => array()
                );
            }
            
            if ( ! isset( $invoice_map[$v_id]['products'][$p_id] ) ) {
                $invoice_map[$v_id]['products'][$p_id] = array(
                    'list_days'        => 0,
                    'ad_days'          => 0,
                    'list_price_codes' => array(),
                    'ad_price_codes'   => array()
                );
            }
            
            if ( intval( $row['List_status'] ) !== 0 ) {
                $invoice_map[$v_id]['products'][$p_id]['list_days']++;
                if ( ! empty( $row['List_price_code'] ) ) {
                    $invoice_map[$v_id]['products'][$p_id]['list_price_codes'][] = str_pad( trim( $row['List_price_code'] ), 2, '0', STR_PAD_LEFT );
                }
            }
            if ( intval( $row['AD_status'] ) !== 0 ) {
                $invoice_map[$v_id]['products'][$p_id]['ad_days']++;
                if ( ! empty( $row['AD_price_code'] ) ) {
                    $invoice_map[$v_id]['products'][$p_id]['ad_price_codes'][] = str_pad( trim( $row['AD_price_code'] ), 2, '0', STR_PAD_LEFT );
                }
            }
        }
        
        $date_yyyymmdd_start = date( 'Ymd', $start_timestamp );
        $date_yyyymmdd_end   = date( 'Ymd', $end_timestamp );
        $date_yyyy_mm_dd_start = date( 'Y/m/d', $start_timestamp );
        $date_yyyy_mm_dd_end   = date( 'Y/m/d', $end_timestamp );
        $date_dd_mm_yyyy_start = date( 'd/m/Y', $start_timestamp );
        $date_dd_mm_yyyy_end   = date( 'd/m/Y', $end_timestamp );

        $created_count = 0;
        $exist_count   = 0;
        $skipped_balance_count = 0;
        
        // Dynamic Buffer to hold calculated itemized invoice lines
        $invoice_csv_buffer = array();

        foreach ( $invoice_map as $vendor_id => $v_data ) {
            $total_list_amount = 0;
            $total_ad_amount   = 0;
            $list_products_count = 0;
            $ad_products_count   = 0;

            foreach ( $v_data['products'] as $product_id => $p_data ) {
                if ( $p_data['list_days'] >= 1 ) {
                    $list_products_count++;
                    $unique_list_codes = array_unique( $p_data['list_price_codes'] );
                    $calculated_price = 0.00;

                    if ( $p_data['list_days'] > 1 && ! empty( $unique_list_codes ) ) {
                        $lowest_price = null;
                        foreach ( $unique_list_codes as $code ) {
                            if ( function_exists( 'get_vendor_category_fee_cached' ) ) {
                                $current_price = get_vendor_category_fee_cached( $code, 'Publishing' );
                            } else {
                                $current_price = 0.00;
                            }
                            if ( $lowest_price === null || $current_price < $lowest_price ) { $lowest_price = $current_price; }
                        }
                        $calculated_price = $lowest_price;
                    } elseif ( $p_data['list_days'] == 1 && ! empty( $unique_list_codes ) ) {
                        $calculated_price = function_exists( 'get_vendor_category_fee_cached' ) ? get_vendor_category_fee_cached( reset( $unique_list_codes ), 'Publishing' ) : 0.00;
                    }
                    $total_list_amount += $calculated_price;
                }

                if ( $p_data['ad_days'] >= 1 ) {
                    $ad_products_count++;
                    $unique_ad_codes = array_unique( $p_data['ad_price_codes'] );
                    $calculated_price = 0.00;

                    if ( $p_data['ad_days'] > 1 && ! empty( $unique_ad_codes ) ) {
                        $lowest_price = null;
                        foreach ( $unique_ad_codes as $code ) {
                            if ( function_exists( 'get_vendor_category_fee_cached' ) ) {
                                $current_price = get_vendor_category_fee_cached( $code, 'Featuring' );
                            } else {
                                $current_price = 0.00;
                            }
                            if ( $lowest_price === null || $current_price < $lowest_price ) { $lowest_price = $current_price; }
                        }
                        $calculated_price = $lowest_price;
                    } elseif ( $p_data['ad_days'] == 1 && ! empty( $unique_ad_codes ) ) {
                        $calculated_price = function_exists( 'get_vendor_category_fee_cached' ) ? get_vendor_category_fee_cached( reset( $unique_ad_codes ), 'Featuring' ) : 0.00;
                    }
                    $total_ad_amount += $calculated_price;
                }
            }

            $custom_invoice_no = sprintf( '%d-%s-%s', $vendor_id, $date_yyyymmdd_start, $date_yyyymmdd_end );
            $listing_line_title     = sprintf( 'Listing products fee (%d products) %s - %s', $list_products_count, $date_dd_mm_yyyy_start, $date_dd_mm_yyyy_end );
            $advertising_line_title = sprintf( 'Advertising products fee (%d products) %s - %s', $ad_products_count, $date_dd_mm_yyyy_start, $date_dd_mm_yyyy_end );
            
            $sliced_invoice_status = 'Skipped (0.00 Balance Due)';

            if ( $total_list_amount > 0 || $total_ad_amount > 0 ) {
                $invoice_title_name = sprintf( 'INV-%d-%s-%s', $vendor_id, $date_yyyy_mm_dd_start, $date_yyyy_mm_dd_end );

                $line_items = array();
                if ( $total_list_amount > 0 ) {
                    $line_items[] = array( 'qty' => 1, 'title' => $listing_line_title, 'amount' => $total_list_amount, 'taxable' => 0 );
                }
                if ( $total_ad_amount > 0 ) {
                    $line_items[] = array( 'qty' => 1, 'title' => $advertising_line_title, 'amount' => $total_ad_amount, 'taxable' => 0 );
                }

                $exists = new WP_Query( array(
                    'post_type' => 'sliced_invoice', 'post_status' => 'any', 'posts_per_page' => 1,
                    'meta_query' => array( array( 'key' => '_sliced_invoice_number', 'value' => $custom_invoice_no ) )
                ) );
                
                if ( ! $exists->have_posts() ) {                    

                    $client_profile_id = 0;
                    $client_lookup_query = new WP_Query( array(
                        'post_type' => 'sliced_client', 'posts_per_page' => 1, 'post_status' => 'any',
                        'meta_query' => array( array( 'key' => '_wordpress_user_id', 'value' => $vendor_id, 'compare' => '=' ) )
                    ) );

                    if ( $client_lookup_query->have_posts() ) {
                        $client_lookup_query->the_post();
                        $client_profile_id = get_the_ID();
                    }
                    wp_reset_postdata();

                    $assigned_client_target = ( $client_profile_id > 0 ) ? $client_profile_id : $vendor_id;

                    $invoice_id = wp_insert_post( array(
                        'post_type' => 'sliced_invoice', 'post_status' => 'publish', 'post_title' => $invoice_title_name, 'post_author' => get_current_user_id()
                    ) );

                    if ( $invoice_id ) {
                        $created_timestamp  = get_post_timestamp( $invoice_id );
                        $due_date_timestamp = strtotime( '+30 days', $created_timestamp );
                        update_post_meta( $invoice_id, '_sliced_client', $assigned_client_target );
                        update_post_meta( $invoice_id, '_sliced_invoice_number', $custom_invoice_no ); 
                        update_post_meta( $invoice_id, '_sliced_invoice_due', $due_date_timestamp );
                        update_post_meta( $invoice_id, '_sliced_items', $line_items );
                        update_post_meta( $invoice_id, '_sliced_invoice_created', $created_timestamp );
                        update_post_meta( $invoice_id, '_sliced_payment_methods', array( 'stripe', 'paypal' ) );

                        $grand_total = $total_list_amount + $total_ad_amount;
                        update_post_meta( $invoice_id, '_total', $grand_total );
                        update_post_meta( $invoice_id, '_sub_total', $grand_total );
                        wp_set_object_terms( $invoice_id, 'unpaid', 'invoice_status', true );

                        $created_count++;
                        $sliced_invoice_status = 'Created (ID: ' . $invoice_id . ')';
                    } else {
                        $sliced_invoice_status = 'Failed (CPT Write Error)';
                    }
                } else {
                    $exist_count++;
                    $sliced_invoice_status = 'Skipped (Invoice configuration already exists)';
                }
            } else {
                $skipped_balance_count++;
            }

            // Append entries into generated invoice data matrix
            if ( $total_list_amount > 0 ) {
                $invoice_csv_buffer[] = array( $vendor_id, $v_data['vendor_name'], $v_data['vendor_account'], $custom_invoice_no, $listing_line_title, $total_list_amount, $sliced_invoice_status );
            }
            if ( $total_ad_amount > 0 ) {
                $invoice_csv_buffer[] = array( $vendor_id, $v_data['vendor_name'], $v_data['vendor_account'], $custom_invoice_no, $advertising_line_title, $total_ad_amount, $sliced_invoice_status );
            }
            if ( $total_list_amount == 0 && $total_ad_amount == 0 ) {
                $invoice_csv_buffer[] = array( $vendor_id, $v_data['vendor_name'], $v_data['vendor_account'], $custom_invoice_no, 'No Active Billable Assets Logged', '0.00', $sliced_invoice_status );
            }
        }

        // --- UPDATE OR INSERT RECORD INTO SUMMARY LOGS (WITH NEW invoice_created_ind COLUMN) ---
        if ( $is_exact_match && ! empty( $existing_period_id ) ) {
            $wpdb->update(
                $summary_table,
                array(
                    'invoice_created_ind' => ( $created_count > 0 ) ? 1 : 0,
                    'invoices_count'      => $created_count,
                    'executed_at'         => current_time( 'mysql' )
                ),
                array( 'id' => $existing_period_id ),
                array( '%d', '%d', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $summary_table,
                array(
                    'from_date'           => $db_start_date,
                    'to_date'             => $db_end_date,
                    'invoice_created_ind' => ( $created_count > 0 ) ? 1 : 0,
                    'invoices_count'      => $created_count,
                    'executed_at'         => current_time( 'mysql' )
                ),
                array( '%s', '%s', '%d', '%d', '%s' )
            );
        }

        // Verify destination folder structure is mapped safely
        if ( ! file_exists( $target_folder_path ) ) {
            wp_mkdir_p( $target_folder_path );
            file_put_contents( $target_folder_path . '/index.php', '<?php // Silence is golden' );
            file_put_contents( $target_folder_path . '/.htaccess', "Options -Indexes\nDeny from all" );
        }

        // 1. FILE SYSTEM STORAGE: Save the Raw Table Data spreadsheet silently to folder
        $raw_snap_name = sprintf( 'raw_vendor_summary_report_%s_to_%s.csv', $safe_start, $safe_end );
        $raw_snap_path = $target_folder_path . '/' . $raw_snap_name;

        $fp_raw = fopen( $raw_snap_path, 'w' );
        if ( $fp_raw ) {
            fputcsv( $fp_raw, $table_csv_headers );
            foreach ( $all_records as $row ) {
                fputcsv( $fp_raw, array(
                    $row['vendor_id'], $row['vendor_name'], $row['vendor_account'],
                    date( 'd/m/Y', strtotime( $row['running_day'] ) ), $row['product_id'],
                    $row['List_status'], $row['AD_status'], trim( $row['List_price_code'] ), trim( $row['AD_price_code'] ),
                    $row['spare1'], $row['spare2'], $row['spare3'], $row['spare4']
                ) );
            }
            fclose( $fp_raw );
        }

        // 2. FILE SYSTEM STORAGE + EMAIL DELIVERY: Save and email the calculated invoice logs
        $inv_data_name = sprintf( 'calculated_invoice_log_report_%s_to_%s.csv', $safe_start, $safe_end );
        $inv_data_path = $target_folder_path . '/' . $inv_data_name;

        $fp_inv = fopen( $inv_data_path, 'w' );
        if ( $fp_inv ) {
            fputcsv( $fp_inv, array( 'Vendor ID', 'Vendor Name', 'Vendor Account', 'Invoice Number References', 'Line Item Title Description', 'Calculated Amount Total', 'Engine Processing Status' ) );
            foreach ( $invoice_csv_buffer as $line_entry ) {
                fputcsv( $fp_inv, $line_entry );
            }
            fclose( $fp_inv );
        }

        $email_subject = sprintf( '[Billing Execution Log] Generated Invoice Data: %s to %s', $start_date, $end_date );
        $email_body  = "Hello,\n\nThe backend invoice generation cycle has completed.\n\n";
        $email_body .= "--- Process Execution Telemetry ---\n";
        $email_body .= "Invoices Configured: " . intval( $created_count ) . "\n";
        $email_body .= "Skipped (Existing Matches): " . intval( $exist_count ) . "\n";
        $email_body .= "Skipped (Zero Financial Balance): " . intval( $skipped_balance_count ) . "\n\n";
        $email_body .= "The generated invoice ledger dataset file has been saved locally and attached below.\n";
        $email_body .= "File Output Reference: " . $inv_data_name . "\n\nBest Regards,\nMarketplace Engine Core";

        // Send out the calculated invoice ledger sheet via email
        wp_mail( $email_recipients, $email_subject, $email_body, $email_headers, array( $inv_data_path ) );

        // 3. WEBSITE DOWNLOAD: Save buffer into transient space for browser downloading access
        set_transient( 'ts_last_invoice_run_csv', $invoice_csv_buffer, 30 );

        $redirect_url = add_query_arg( array(
            'page' => 'billable-vendor-summary', 'start_date' => $start_date, 'end_date' => $end_date,
            'created_count' => $created_count, 'exist_count' => $exist_count, 'skipped_balance' => $skipped_balance_count, 'invoice_status' => 'complete'
        ), admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    // DISPATCH TRANSIENT RUN DOWNLOAD STREAM FOR THE CALCULATED INVOICES DATA
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'download_last_run' ) {
        $csv_buffer = get_transient( 'ts_last_invoice_run_csv' );
        if ( ! $csv_buffer ) {
            wp_die( __( 'Invoice download session expired. Please rerun generator to fetch new files.', 'text-domain' ) );
        }
        if ( ob_get_length() ) { ob_end_clean(); }

        $filename = sprintf( 'calculated_invoice_log_report_%s_to_%s.csv', $safe_start, $safe_end );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        
        $stream = fopen( 'php://output', 'w' );
        fputcsv( $stream, array( 'Vendor ID', 'Vendor Name', 'Vendor Account', 'Invoice Number References', 'Line Item Title Description', 'Calculated Amount Total', 'Engine Processing Status' ) );
        foreach ( $csv_buffer as $line ) { fputcsv( $stream, $line ); }
        fclose( $stream );
        exit;
    }

    // Default WP_List_Table processing
    $all_records = ts_get_billable_vendor_data( $start_date, $end_date );
    $vendor_table = new TS_Billable_Vendor_Table( $all_records );
    $vendor_table->prepare_items();

    $base_url    = admin_url( 'admin.php?page=billable-vendor-summary&start_date=' . $start_date . '&end_date=' . $end_date );
    $export_url  = add_query_arg( array( 'action' => 'export_csv' ), $base_url );
    $invoice_url = add_query_arg( array( 'action' => 'generate_invoice' ), $base_url );
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'Billable Vendor Summary', 'text-domain' ); ?></h1>
        <hr class="wp-header-end">

        <?php if ( isset( $_GET['invoice_status'] ) && $_GET['invoice_status'] === 'complete' ) : ?>
            <div class="notice notice-success is-dismissible" style="margin-top: 15px;">
                <p><strong><?php _e( 'Invoice Generation Sequence Completed!', 'text-domain' ); ?></strong></p>
                <p><?php _e( 'The exact raw data table rows was successfully stored inside your server folder. The generated invoice dataset has been saved, sent to the email distribution list, and is now available for download below.', 'text-domain' ); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px; margin-top: 5px;">
                    <li><strong><?php echo intval( $_GET['created_count'] ); ?></strong> <?php _e( 'Invoices configured successfully.', 'text-domain' ); ?></li>
                    <li><strong><?php echo intval( $_GET['exist_count'] ); ?></strong> <?php _e( 'Skipped as matches already existed.', 'text-domain' ); ?></li>
                    <li><strong><?php echo intval( $_GET['skipped_balance'] ); ?></strong> <?php _e( 'Skipped due to empty transactional balances.', 'text-domain' ); ?></li>
                </ul>
                <p style="margin-top: 12px; margin-bottom: 8px;">
                    <a href="<?php echo esc_url( add_query_arg( 'action', 'download_last_run', $base_url ) ); ?>" class="button button-small button-primary">
                        <span class="dashicons dashicons-download" style="vertical-align: middle; font-size: 14px; width: 14px; height: 14px; margin-top:-2px;"></span> 
                        <?php _e( 'Download Generated Invoice Data CSV', 'text-domain' ); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <form id="vendor-summary-table-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
            <input type="hidden" name="page" value="billable-vendor-summary" />
            
            <div class="tablenav top" style="margin: 20px 0 5px 0; padding: 12px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div class="alignleft actions">
                    <label for="start_date" style="font-weight: 600; margin-right: 5px;"><?php _e( 'Start Date:', 'text-domain' ); ?></label>
                    <input type="text" id="start_date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" style="margin-right: 15px; width: 110px;" autocomplete="off" />

                    <label for="end_date" style="font-weight: 600; margin-right: 5px;"><?php _e( 'End Date:', 'text-domain' ); ?></label>
                    <input type="text" id="end_date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" style="margin-right: 15px; width: 110px;" autocomplete="off" />

                    <?php submit_button( __( 'Generate Summary', 'text-domain' ), 'primary', 'filter_action', false ); ?>
                    
                    <a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary" style="margin-left: 10px; vertical-align: middle; display: inline-flex; align-items: center;">
                        <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span> 
                        <?php _e( 'Export to CSV', 'text-domain' ); ?>
                    </a>

                    <a href="<?php echo esc_url( $invoice_url ); ?>" class="button button-secondary" style="margin-left: 10px; vertical-align: middle; display: inline-flex; align-items: center; border-color: #135e96; color: #135e96;">
                        <span class="dashicons dashicons-media-document" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span> 
                        <?php _e( 'Generate Invoice', 'text-domain' ); ?>
                    </a>
                </div>
                <div class="clear"></div>
            </div>

            <?php $vendor_table->display(); ?>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#start_date, #end_date').datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
    <?php
}