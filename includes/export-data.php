<?php
/**
 * Plugin Name: Sliced Invoices Custom Schema Daily Export Engine
 * Description: Automatically builds and populates a structured data table and CSV file at 00:05 AM daily matching a precise structural layout. Filters for vendor category code prices > 0.
 * Version: 1.6
 * Author: Developer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// 1. Schedule the Event on Activation
add_action( 'wp', 'schedule_daily_export_product_data' );

function schedule_daily_export_product_data() {
    if ( ! wp_next_scheduled( 'daily_export_product_data' ) ) {
        $timezone = wp_timezone();
        $start_time = new DateTime( 'today 15:05:00', $timezone );
        
        wp_schedule_event( $start_time->getTimestamp(), 'daily', 'daily_export_product_data' );
    }
}

// 2. Hook Execution Function to Scheduled Event
add_action( 'daily_export_product_data', 'execute_daily_export_product_data' );

function execute_daily_export_product_data() {
    global $wpdb;

    if ( ! ini_get( 'safe_mode' ) ) {
        set_time_limit( 0 );
    }

    // Define target date configuration (today's Date)
    $today     = current_time( 'Y_m_d'); 
    $target_table  = "backup_products_" . $today;
    $csv_filename  = "products_" . $today . ".csv";

    $upload_dir    = wp_upload_dir();
    $backup_dir    = $upload_dir['basedir'] . '/product_data_backup/';

    if ( ! file_exists( $backup_dir ) ) {
        wp_mkdir_p( $backup_dir );
    }

    // 3. Create the Database Table with your exact requested Schema layout
    $wpdb->query( "DROP TABLE IF EXISTS `{$target_table}`" );
    
    $create_table_query = "
        CREATE TABLE `{$target_table}` (
            `running_day` DATE NOT NULL,
            `vendor_id` BIGINT(20) NOT NULL,
            `product_id` BIGINT(20) NOT NULL,
            `List_status` TINYINT(1) DEFAULT 0 COMMENT '0=not listed, 1=listed',
            `AD_status` TINYINT(1) DEFAULT 0 COMMENT '0=not for AD, 1=for AD',
            `List_price_code` INT(11) DEFAULT 0,
            `AD_price_code` INT(11) DEFAULT 0,
            `spare1` INT(11) DEFAULT 0,
            `spare2` INT(11) DEFAULT 0,
            `spare3` INT(11) DEFAULT 0,
            `spare4` INT(11) DEFAULT 0,
            PRIMARY KEY (`product_id`),
            KEY `vendor_id` (`vendor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
    $wpdb->query( $create_table_query );

    // 4. Data Gathering & Population Strategy
    // We query all relevant WooCommerce products
    $products_query = "
        SELECT p.ID as product_id, p.post_author as vendor_id, p.post_status
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'product' AND p.post_status IN ('publish', 'draft', 'pending')
    ";
    $products = $wpdb->get_results( $products_query );

    $running_day_value = current_time( 'Y-m-d', -1 ); // Yesterday's runtime value stamp

    if ( ! empty( $products ) ) {
        foreach ( $products as $product ) {
            
            // Gather Category Codes first to run the conditional validation check
            $list_price_code = 0;
            $ad_price_code   = 0;
            $category_codes  = get_vendor_fees_categories( $product->vendor_id );

            if ( $category_codes ) {
                $list_price_code = intval( $category_codes['publishing'] ); // Convert price float value to integer format                       
                $ad_price_code   = intval( $category_codes['featuring'] );
            }

            $list_fee_ = get_vendor_category_fee_cached($category_codes['publishing'],'publishing');
            $ad_fee   = get_vendor_category_fee_cached($category_codes['featuring'],'featuring');

            // CRITICAL REFINEMENT: Only export if at least one vendor category code price is greater than 0
            if ( $list_fee_ <= 0 && $ad_fee <= 0 ) {
                continue; // Skip this product entirely
            }

            // Mapping Logic Variables:
            // List status: 1 if published, 0 if draft/not listed
            $list_status = ( $product->post_status === 'publish' ) ? 1 : 0;
            
            // AD status: Example mapping from post meta
            $product_tags = get_all_tags_of_product( $product->product_id );
            $is_featured = false;
            if ( ! empty( $product_tags ) && ! is_wp_error( $product_tags ) ) {
                foreach ( $product_tags as $tag ) {
                    if ( str_contains( $tag->slug, 'featured' ) ) {
                        $is_featured = true;
                        break;
                    }
                }
            }
            $ad_status = ( $is_featured === true || $is_featured == 1 ) ? 1 : 0;

            // Spare string fields
            $spare1 = 0; 
            $spare2 = 0;
            $spare3 = 0; 
            $spare4 = 0;

            // Insert matching row array element values directly into our target table schema wrapper
            $wpdb->insert(
                $target_table,
                array(
                    'running_day'     => $running_day_value,
                    'vendor_id'       => intval( $product->vendor_id ),
                    'product_id'      => intval( $product->product_id ),
                    'List_status'     => $list_status,
                    'AD_status'       => $ad_status,
                    'List_price_code' => $list_price_code,
                    'AD_price_code'   => $ad_price_code,
                    'spare1'          => $spare1,
                    'spare2'          => $spare2,
                    'spare3'          => $spare3,
                    'spare4'          => $spare4
                ),
                array( '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' )
            );
        }
    }

    // 5. Generate Flat CSV Layout Export from the final table grid
    $csv_file_path = $backup_dir . $csv_filename;
    $file_handle   = fopen( $csv_file_path, 'w' );

    if ( $file_handle !== false ) {
        // Excel UTF-8 representation compatibility flag
        fprintf( $file_handle, chr(0xEF).chr(0xBB).chr(0xBF) );

        // Write specific requested headers
        $csv_headers = array(
            'running day (DATE)',
            'vendor id (LONG)',
            'product id (LONG)',
            'List status (0=not listed, 1=listed)',
            'AD status(0=not for AD, 1=for AD)',
            'List_price_code(integer)',
            'AD_price_code(integer)',
            'spare1(integer)',
            'spare2(integer)',
            'spare3(integer)',
            'spare4(integer)'
        );
        fputcsv( $file_handle, $csv_headers );

        // Pull processed records
        $export_rows = $wpdb->get_results( "SELECT * FROM `{$target_table}`", ARRAY_A );
        
        if ( ! empty( $export_rows ) ) {
            foreach ( $export_rows as $row ) {
                fputcsv( $file_handle, $row );
            }
        }
        
        fclose( $file_handle );
    }

    // 6. DISPATCH AUTOMATED EMAIL ROUTE WITH ATTACHMENT
    ts_email_csv_backup_to_admin( $csv_file_path, $running_day_value );
}

/**
 * Handles the wp_mail processing loop for the CSV attachment.
 */
function ts_email_csv_backup_to_admin( $file_path, $date_string ) {
    $to_email = array( 'admin@a1-geomarket.com', 'manage@a1-geomarket.com' );
    //$to_email = array( 'manage@a1-geomarket.com' );
    
    $subject = sprintf( __( '[Backup Log] Daily Product Export - %s', 'text-domain' ), $date_string );
    
    $body  = "Hello Admin,\n\n";
    $body .= "The automated daily product data extraction has executed successfully.\n";
    $body .= "Filtered items containing vendor category code prices greater than 0 have been written.\n";
    $body .= "Please find the structured CSV matrix attachment for the running tracking day: " . $date_string . ".\n\n";
    $body .= "Best regards,\nAutomated System Manager";

    // Set standard secure plain-text headers
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    // Attach the file path safely
    $attachments = array( $file_path );

    // Fire mailing transmission
    wp_mail( $to_email, $subject, $body, $headers, $attachments );
}