<?php

// Define theme-wide directory path constants for cleaner inclusion strings
define( 'A1_INC_DIR', get_stylesheet_directory() . '/includes/' );
/**
 * Core Component Loading Array
 */
$a1_management_includes = [    	
    'a1-mgt-vendor-categories.php', // Vendor category management
    'a1-mgt-billable-summary.php', // Billable summary
    'a1-mgt-vendor-invoices.php', // Vendor invoices management
    'a1-mgt-category-fees.php', // Category fee management 
    'a1-mgt-gateway-summary.php', // Gateway summary -- Not finished
    'a1-mgt-manager1-vendor-invoices.php', // Vendor invoices management for Manager 1
    'a1-mgt-manager2-vendor-invoices.php', // Vendor invoices management for Manager 2
    'a1-mgt-other-vendor-invoices.php', // Other Vendor invoices management
	'a1-mgt-billing-period-summary.php', // Billing period summary
	'a1-mgt-page-view-summary.php', // Page view summary
	'a1-external-clicks-summary.php', // External clicks summary
];

// Loop through and cleanly require each file
foreach ( $a1_management_includes as $file ) {
    $file_path = A1_INC_DIR . $file;	
    
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // Trigger a noticeable admin warning if a file goes missing during local development
        error_log( sprintf( 'Theme error: Unable to locate include file target at %s', $file_path ) );
    }
}

/**
 * Extract publishing and featuring category codes from a vendor's phone number.
 * 
 * Phone Structure:
 * - Chars 0-2 (First 3 digits): Ignored
 * - Chars 3-4 (Next 2 digits): Featuring Category
 * - Chars 5-6 (Next 2 digits): Publishing Category
 *
 * @param int|string $vendor_identifier User ID
 * @return array|false Array containing category codes, or false if phone number is invalid/too short.
 */
function get_vendor_fees_categories( $vendor_identifier ) {
    $phone = '';
    // If a numeric User ID is passed, retrieve their phone from user meta
   
	$user_id = intval( $vendor_identifier );        
	
	$dokan_settings = get_user_meta( $user_id, 'dokan_profile_settings', true );
	if ( is_array( $dokan_settings ) && ! empty( $dokan_settings['phone'] ) ) {
		$phone = $dokan_settings['phone'];
	}           

    // Clean the string: Remove spaces, dashes, or plus signs to ensure clean digit indexing
    $phone = preg_replace( '/[^0-9]/', '', $phone );
    // Validation: The phone must have at least 7 digits to extract both 2-digit pairs
    if ( strlen( $phone ) < 7 ) {
        return false; 
    }

    // Extract the categories using string offsets
	$featuring_category  = substr( $phone, 3, 2 ); // Starts at index 3, takes 2 characters
    $publishing_category = substr( $phone, 5, 2 ); // Starts at index 5, takes 2 characters    

    return [
        'publishing' => $publishing_category,
        'featuring'  => $featuring_category
    ];
}

/**
 * Get the fee amount for a given category code and type with multi-layer caching.
 *
 * @param string $category_code The 2-digit code (e.g., '01', '02').
 * @param string $category_type 'Publishing' or 'Featuring'.
 * @return float The fee amount, defaults to 0.00 if not found.
 */
function get_vendor_category_fee_cached( $category_code, $category_type ) {
    // Layer 1: Runtime Memory Cache (persists for the current page request only)
    static $runtime_fee_cache = null;

    if ( null === $runtime_fee_cache ) {
        // Layer 2: Persistent Cache via WordPress Transients (expires in 24 hours)
        $transient_key     = 'a1_vendor_category_fees_map';
        $runtime_fee_cache = get_transient( $transient_key );

        if ( false === $runtime_fee_cache ) {
		
            global $wpdb;
            $table_name = 'vendor_category_fees';
            $runtime_fee_cache = [];

            // Fetch the whole map from the database in one quick query
            $results = $wpdb->get_results( "SELECT category_code, category_type, fee_amount FROM $table_name", ARRAY_A );

            if ( ! empty( $results ) ) {
                foreach ( $results as $row ) {
                    // Create a unique combined string key like "01_publishing"
                    $cache_key = strtolower( $row['category_code'] . '_' . $row['category_type'] );
                    $runtime_fee_cache[ $cache_key ] = floatval( $row['fee_amount'] );
                }
            }

            // Save the structured array back to the transient database for 1 day
            set_transient( $transient_key, $runtime_fee_cache, DAY_IN_SECONDS );
        }
    }

    // Standardize inputs to match our cache keys
    $lookup_key = strtolower( trim( $category_code ) . '_' . trim( $category_type ) );
	// Return cached fee or fallback to 0.00 if it doesn't exist
	$lookup_amount = isset( $runtime_fee_cache[ $lookup_key ] ) ? $runtime_fee_cache[ $lookup_key ] : 0.00;	
    
    return $lookup_amount;
}

/**
 * Utility function to clear the category fee cache when modifications happen.
 */
function delete_vendor_category_fee_cache() {
    delete_transient( 'a1_vendor_category_fees_map' );
}

add_action( 'admin_menu', 'register_a1_management_menu_system' );

function register_a1_management_menu_system() {
    
    // 1. Create the Top-Level Parent Menu: A1 Management
    add_menu_page(
        'A1 Management Dashboard',      // Page title
        'A1 Management',                // Menu title in sidebar
        'manage_woocommerce',               // Capability required
        'a1-management',                // Main Menu Slug
        'render_a1_management_main',    // Callback function for the landing tab
        'dashicons-performance',        // Menu Icon
        25                              // Position in sidebar
    );


    // 2. Add "Vendor Invoices" as a Submenu Item - Deprecated
    /*add_submenu_page(
        'a1-management',                // Parent Slug
        'Vendor Invoices Overview',     // Page title
        'Vendor Invoices',              // Submenu title in sidebar
        'manage_options',               // Capability required
        'vendor-invoices',              // Submenu Slug (Keep this identical to your current slug)
        'render_vendor_invoice_page'   // Your existing invoices callback function
    );*/

    // 3. Add "Category Fees" as a Submenu Item
    add_submenu_page(
        'a1-management',                // Parent Slug
        'Vendor Category Fees',         // Page title
        'Category Fees',                // Submenu title in sidebar
        'manage_woocommerce',               // Capability required
        'vendor-category-fees',         // Submenu Slug (Keep this identical to your current slug)
        'render_vendor_category_fee_page' // Your existing callback function
    );

	// Add this inside your existing register_a1_management_menu_system() function:
	add_submenu_page(
		'a1-management',
		'Vendor Categories Overview',
		'Vendor Categories',
		'manage_woocommerce',
		'vendor-categories',
		'render_vendor_categories_page'
	);

    add_submenu_page(
        'a1-management', // Parent menu slug (attaches to Sliced Invoices menu)
        'Gateway Performance',
        'Gateway Reports',
        'manage_woocommerce',                    // Capability required
        'sliced-gateway-reports',            // Menu slug
        'ts_render_sliced_gateway_table'     // Display callback function
    );

	add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'Billing period summary', 'text-domain' ),  // Page title
        __( 'Billing period summary', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'billing-period-summary',                // Menu unique identifier
        'ts_render_billing_period_summary_table'    // Display view renderer function
    );

    add_submenu_page(
        'a1-management', 
        __( 'Billable Vendor Summary', 'text-domain' ),
        __( 'Billable Vendor Summary', 'text-domain' ),
        'manage_woocommerce',
        'billable-vendor-summary', 
        'ts_render_billable_vendor_summary_view'
    );

    add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'All Vendor Invoices', 'text-domain' ),  // Page title
        __( 'All Vendor Invoices', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'vendor-invoices-report',                // Menu unique identifier
        'a1_render_vendor_invoices_dashboard'    // Display view renderer function
    );

    add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'Manager 1 Vendor Invoices', 'text-domain' ),  // Page title
        __( 'Manager 1 Vendor Invoices', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'manager1-vendor-invoices-report',                // Menu unique identifier
        'manager1_render_vendor_invoices_dashboard'    // Display view renderer function
    );

    add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'Manager 2 Vendor Invoices', 'text-domain' ),  // Page title
        __( 'Manager 2 Vendor Invoices', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'manager2-vendor-invoices-report',                // Menu unique identifier
        'manager2_render_vendor_invoices_dashboard'    // Display view renderer function
    );

    add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'Other Vendor Invoices', 'text-domain' ),  // Page title
        __( 'Other Vendor Invoices', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'other-vendor-invoices-report',                // Menu unique identifier
        'other_render_vendor_invoices_dashboard'    // Display view renderer function
    );

	add_submenu_page(
        'a1-management',                        // Parent system administration slug
        __( 'Page View Summary', 'text-domain' ),  // Page title
        __( 'Page View Summary', 'text-domain' ),  // Menu title
        'manage_woocommerce',                        // Capability requirements
        'page-view-summary',                // Menu unique identifier
        'render_page_view_summary'    // Display view renderer function
    );

	// NEW SUB-MENU VIEW: Daily Drill Down Interface
    add_submenu_page(
        'a1-management',               // Enforces nesting alignment under same root tree
        __( 'Daily View Drilldown', 'a1-geo' ),
        __( 'Daily View Drilldown', 'a1-geo' ),
        'manage_options',
        'daily-view-drilldown',            // Submenu Slug (admin.php?page=daily-view-drilldown)
        'render_daily_view_drilldown'      // New Dynamic Render Callback
    );

	add_submenu_page(
        'a1-management',                    // Parent menu slug
        'External Clicks',                  // Page title
        'External Clicks',                  // Sub-menu title
        'manage_options',                   // Capability required
        'a1geo-external-clicks',            // Menu slug
        'a1geo_render_external_clicks_page' // Callback function
    );

	add_submenu_page(
		'a1-management',                        // Parent Menu Slug
		__( 'External clicks summary', 'a1-geo' ), // Page Title
		__( 'External clicks summary', 'a1-geo' ), // Submenu Title
		'manage_options',                       // Capability
		'external-clicks-summary',              // Menu Slug
		'a1geo_render_external_clicks_chart_page' // New Callback Function
	);
	
    
    // 4. Clean up: Remove the default duplicate submenu link if desired
    // (WordPress automatically duplicates the parent name as the first submenu item)
    remove_submenu_page('a1-management', 'a1-management');
}


/**
 * Optional: Default landing page when a user clicks 'A1 Management' directly.
 * If you don't want a landing page, you can set the parent callback to point 
 * directly to your Category Fees function instead.
 */
function render_a1_management_main() {
    echo '<div class="wrap">';
    echo '<h1>A1 Management System</h1>';
    echo '<p>Welcome to your central administration hub. Please select a utility from the submenu below:</p>';
    echo '<ul>';
    echo '<li><a href="'.admin_url('admin.php?page=vendor-category-fees').'">Manage Category Fees</a></li>';
    echo '<li><a href="'.admin_url('admin.php?page=vendor-invoices').'">Manage Vendor Invoices</a></li>';
    echo '</ul>';
    echo '</div>';
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Vendor_Billing_Table extends WP_List_Table {

    public function get_columns() {
        return [
			'cb'               => '<input type="checkbox" />', // Select All
            'ID'               => 'ID',
			'username'         => 'Username',
			'store_name'       => 'Store Name',
			'email'            => 'Email',
			'status'           => 'Client Status',			
			'pub_count'        => 'V-list',			
			'feat_count'       => 'V-AD',
            'price_unit_list'  => 'V-list-price ',
			'price_unit_ad'    => 'V-AD-price',
			'total_amount'     => 'Total Amount',
			'invoice_generated'  => 'Invoice Generated',
			'email_sent'	   => 'Email Sent',
			'invoice_status'   => 'Invoice Status'
        ];
    }

	public function get_sortable_columns() {
        return [            
            'username' 	   => [ 'username', false ],
            'store_name'   => [ 'store_name', false],            
			'email'   	   => [ 'email', false],
			'status'   	   => [ 'status', false],			
			'pub_count'    => [ 'pub_count', false ], 			
            'feat_count'   => [ 'feat_count', false ], 			
			'total_amount' => [ 'total_amount', false ], 
        ];
    }

	// Define how the checkbox for each row is rendered
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="vendor_ids[]" value="%s" />',
			$item['ID']
		);
	}

	public function column_username( $item ) {
		// Generate the URL to the admin product list filtered by this user (author)
		$admin_products_url = add_query_arg(
			[
				'post_type' => 'product',
				'author'    => $item['ID']
			],
			admin_url( 'edit.php' )
		);

		return sprintf(
			'<strong><a href="%1$s" aria-label="View products for %2$s">%2$s</a></strong>',
			esc_url( $admin_products_url ),
			esc_html( $item['username'] )
		);
	}

    public function prepare_items() {		
		// Get the search string
    	$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
		$status_filter = isset($_POST['billing_status_filter']) ? $_POST['billing_status_filter'] : '';
        $columns = $this->get_columns();
		$sortableColumns = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, [], $sortableColumns ];

        $d_val = isset($_POST['d_filter']) ? $_POST['d_filter'] : date('j');
		$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
		$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');

        $previous_month_year = DateTime::createFromFormat('d-m-Y', "01-$m_val-$y_val");
        $previous_month_year = $previous_month_year->modify( '-1 month' );

        $previous_day = $previous_month_year->format('d');
        $previous_month = $previous_month_year->format('m');
        $previous_year = $previous_month_year->format('Y');

        $formatted_day = str_pad($d_val, 2, '0', STR_PAD_LEFT); 
		$formatted_month = str_pad($m_val, 2, '0', STR_PAD_LEFT); 
		

		$period_string = $formatted_month . "/" . $y_val;

        // 1. Fetch all sellers
		$sellers = dokan_get_sellers( [ 'number' => -1 ] );
		$all_data = [];
		
		$only_billable = ( isset($_POST['filter_billable']) && $_POST['filter_billable'] === '1' );

		foreach ( $sellers['users'] as $seller ) {
			$v_id = $seller->ID;
			$store_info = dokan_get_store_info($v_id);
			$store_name = $store_info['store_name'] ?: '—';
        	$email      = $seller->user_email;
			// --- SEARCH FILTER LOGIC ---
			if ( ! empty( $search ) ) {
				$match_user  = stripos( $seller->user_login, $search ) !== false;
				$match_store = stripos( $store_name, $search ) !== false;
				$match_email = stripos( $email, $search ) !== false;

				// If none of the fields match, skip this row
				if ( ! $match_user && ! $match_store && ! $match_email ) {
					continue;
				}
			}			
			
			$pub_total    = $this->count_vendor_prods($v_id, 'publish');			

			// Counts products with any tag starting with 'featured-'
			$feat_total   = $this->count_vendor_prods($v_id, 'publish', '', true); 

			$price_per_advertising_product = 0;
			$price_per_published_product = 0;
			$category_codes = get_vendor_fees_categories($v_id);

			if ($category_codes) {
				$price_per_advertising_product = get_vendor_category_fee_cached($category_codes['featuring'],'featuring');
				$price_per_published_product = get_vendor_category_fee_cached($category_codes['publishing'],'publishing');
			}

			$total_amount = ($pub_total * $price_per_advertising_product) + ($feat_total * $price_per_published_product);

			//$custom_invoice_no = "{$v_id}-{$formatted_month}{$y_val}";

            // Add day for testing
            //$custom_invoice_no = "{$v_id}-{$y_val}{$formatted_month}{$formatted_day}";
            $custom_invoice_no = "{$v_id}-{$previous_year}{$previous_month}{$previous_day}";
			
			// NEW: Check if an invoice already exists for this vendor and period
			$invoice_check = new WP_Query([
				'post_type'      => 'sliced_invoice',
				'post_status'    => ['publish', 'draft', 'private', 'sent'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					['key' => '_sliced_client', 'value' => $v_id],
					['key' => '_sliced_invoice_number', 'value' => $custom_invoice_no ]					
				]				
			]);

			$invoice_generated = $invoice_check->have_posts();			
			$invoice_status = '-';
			$is_sent = false;
			if ( $invoice_generated  ) {
				$inv_post = $invoice_check->posts[0];
				$invoice_statuses = wp_get_object_terms($inv_post, 'invoice_status', array('fields' => 'names'));				
				
				if (!empty($invoice_statuses)) {
					$invoice_status = $invoice_statuses[0];
				}
				// Sliced Invoices often sets status to 'sent' or stores it in meta
				$status_meta = get_post_meta( $inv_post , '_sliced_invoice_email_sent',true);				

				if (!empty($status_meta)) {					
					if ( intval($status_meta[0]) > 0 ) {
						$is_sent = true;
					}
				}				
			}

			// Apply HTML Filter Logic
			if ($status_filter === 'billable' && $total_amount <= 0) continue;
			if ($status_filter === 'generated' && !$invoice_generated) continue;
			if ($status_filter === 'sent' && !$is_sent) continue;			

			$all_data[] = [
				'ID'         => $v_id,
				'username'   => $seller->user_login,
				'store_name' => $store_info['store_name'] ?: '—',
				'email'      => $seller->user_email,
				'status'     => get_user_meta($v_id, '_sliced_client_business', true) ? 'Active' : 'Missing',
				'pub_count'  => $pub_total,				
				'feat_count' => $feat_total,
                'price_unit_list' => $price_per_published_product,
				'price_unit_ad'   => $price_per_advertising_product,
				'total_amount'   => $total_amount,
				'invoice_generated' => $invoice_generated ? 'Yes' : 'No', // Status result
				'email_sent'     => $is_sent ? 'Yes' : 'No',
				'invoice_status'  => $invoice_status
			];
		}

		// 2. Handle Sorting
		usort( $all_data, function ( $a, $b ) {
			// Set default orderby to 'username' and default order to 'asc'
			$orderby = ( ! empty( $_POST['orderby'] ) ) ? sanitize_text_field( $_POST['orderby'] ) : 'username';
			$order   = ( ! empty( $_POST['order'] ) )   ? sanitize_text_field( $_POST['order'] )   : 'asc';
			
			$result = strnatcasecmp( $a[ $orderby ], $b[ $orderby ] );
			return ( $order === 'asc' ) ? $result : -$result;
		} );

		// 3. Pagination Logic
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count($all_data);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		]);

		// 4. Slice the data for the current page
		$this->items = array_slice($all_data, (($current_page - 1) * $per_page), $per_page);
    }

    private function count_vendor_prods($vendor_id, $status, $brand = '', $is_featured_logic = false) {
		$m = isset($_POST['m_filter']) ? sanitize_text_field($_POST['m_filter']) : date('m');
		$y = isset($_POST['y_filter']) ? sanitize_text_field($_POST['y_filter']) : date('Y');
		$cutoff_date_str = "{$y}-{$m}-01 00:00:00";
		$cutoff_date = DateTime::createFromFormat('Y-m-d H:i:s', $cutoff_date_str);
        $cutoff_date->modify('last day of this month');
		$args = [
			'post_type'      => 'product',
			'post_status'    => $status,
			'author'         => $vendor_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => [
				[ 'before' => $cutoff_date_str ] 
			]
		];

		$tax_query = [];

		// Brand filtering (if applicable)
		if ( ! empty( $brand ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => $brand,
			];
		}

		// NEW: Featured logic based on Tags starting with 'featured-'
		if ( $is_featured_logic ) {
			// We find all tags that start with 'featured-'
			$featured_tags = get_terms([
				'taxonomy'   => 'product_tag',
				'slug__like' => 'featured-', // Standard WP parameter for "contains"
				'fields'     => 'slugs',
			]);

			if ( ! empty( $featured_tags ) ) {
				$tax_query[] = [
					'taxonomy' => 'product_tag',
					'field'    => 'slug',
					'terms'    => $featured_tags,
					'operator' => 'IN',
				];
			} else {
				// If no such tags exist in the system, return 0 immediately
				return 0;
			}
		}
		
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = [ 'relation' => 'AND' ];
			$args['tax_query'][] = $tax_query;
		}

		$query = new WP_Query( $args );

        if ($is_featured_logic ) {
            $product_ids = $query->posts;
            $count_num = 0;
            // Ensure we have results before looping

            if ( ! empty( $product_ids ) ) {                                
                // Use a lightweight foreach loop directly on the primitive ID values                
                foreach ( $product_ids as $product_id ) {              
                    //print_r($product_id.'<br/>');
                    $featured_found = false;                      
                    $product_tags = get_all_tags_of_product($product_id);

                    if (! empty($product_tags)) {                                        
                        foreach($product_tags as $product_tag) {                            
                            $tag_assigned_date = get_product_tag_assigned_time($product_id, $product_tag->term_id);                                                        
                            //print_r($product_id.'>'.$tag_assigned_date.'<br/>');
                            if ($tag_assigned_date) {                                                                
                                try {
                                    $tag_date = DateTime::createFromFormat('Y-m-d H:i:s', $tag_assigned_date);
                                    //print_r($product_id.'>'.$tag_date->format('Y-m-d H:i:s').'<br/>');
                                    //print_r('Cutoff'.'>'.$cutoff_date->format('Y-m-d H:i:s').'<br/>');
                                    if ($tag_date && $tag_date < $cutoff_date) {
                                        $featured_found = true;
                                    }
                                } catch (Exception $e) {
                                    print_r($e);
                                }
                                
                            }
                        }
                    }
                    if ($featured_found) {
                        $count_num = $count_num + 1;
                    }
                }
                return $count_num;
            } else {
                return 0;
            }
        }

		return $query->found_posts;
	}

    public function column_default($item, $col) { return $item[$col]; }
    
    public function column_status($item) {
        return $item['status'] === 'Active' ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
    }
	public function column_invoice_generated($item) {
        return $item['invoice_generated'] === 'Yes' ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
    }
	public function column_email_sent($item) {
        return $item['email_sent'] === 'Yes' ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>';
    }	
	
	public function column_invoice_status($item) {
        return $item['invoice_status'] === 'Paid' ? '<span style="color:green;">Paid</span>' : '<span style="color:red;">'.$item['invoice_status'].'</span>';
    }	
}

/**
 * Handle Menu and Logic
 */
/*add_action('admin_menu', function() {    

	add_menu_page(
	'Vendor Invoices', 
	'Vendor Invoices', 
	'manage_options', 
	'vendor-invoices', 
	'render_vendor_invoice_page', // Parent doesn't need its own page if we use sub-menus
	'dashicons-media-spreadsheet', 
	57);

	// Sub Menu: Vendor Invoices
    add_submenu_page(
        'vendor-invoices',
        'Vendor Invoices',
        'Vendor Invoices',
        'manage_options',
        'vendor-invoices',
        'render_vendor_invoice_page'
    );
	
});*/



function render_vendor_invoice_page() {
	$price_per_advertising_product = 1;
	$price_per_published_product = 1;

    // 1. ACTION: Create Sliced Invoice Clients for all Vendors
    if ( isset($_POST['sync_clients']) ) {
		// SECURITY CHECK: Verify the nonce
        // 'billing_nonce_field' is the name we gave to the wp_nonce_field in the form
        if ( ! isset( $_POST['client_nonce_field'] ) || ! wp_verify_nonce( $_POST['client_nonce_field'], 'bulk_client_create' ) ) {
            wp_die( 'Security check failed. Please refresh the page and try again.' );
        }
        $vendors = dokan_get_sellers( [ 'number' => -1 ] );
        $synced = 0;

        foreach ( $vendors['users'] as $vendor ) {
            // Only create if they don't have a Sliced Client profile already
            if ( ! get_user_meta( $vendor->ID, '_sliced_client_business', true ) ) {
                $store_info = dokan_get_store_info( $vendor->ID );
                $business_name = ! empty( $store_info['store_name'] ) ? $store_info['store_name'] : $vendor->display_name;
                
                // Set the required Sliced Invoices meta
                update_user_meta( $vendor->ID, '_sliced_client_business', $business_name );
                $synced++;
            }
        }
        echo "<div class='updated notice is-dismissible'><p>Successfully created $synced Sliced Invoice clients.</p></div>";
    }

    // 2. ACTION: Generate Invoices for Billable Vendors    
	if ( isset($_POST['gen_invoices']) ) {
		// 2. SECURITY CHECK: Verify the nonce
        // 'billing_nonce_field' is the name we gave to the wp_nonce_field in the form
        if ( ! isset( $_POST['billing_nonce_field'] ) || ! wp_verify_nonce( $_POST['billing_nonce_field'], 'bulk_invoicing' ) ) {
            wp_die( 'Security check failed. Please refresh the page and try again.' );
        }

		// --- DEBUGGING BLOCK ---
		/*if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( isset($_POST['vendor_ids']) ) {
				echo '<div class="notice notice-info is-dismissible"><p><strong>Debug - Selected IDs:</strong> ' . implode( ', ', array_map( 'intval', $_POST['vendor_ids'] ) ) . '</p></div>';
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>Debug:</strong> No vendor_ids found in POST data.</p></div>';
			}
			
			// Uncomment the line below to see everything being sent (Filters, Search, IDs)
			 echo '<pre>' . print_r($_POST, true) . '</pre>';
		}*/
		// --- END DEBUGGING ---

		// Check if any checkboxes were selected
		$selected_ids = isset($_POST['vendor_ids']) ? array_map('intval', $_POST['vendor_ids']) : [];		

		if ( empty($selected_ids) ) {
			echo "<div class='notice notice-error is-dismissible'><p>Please select at least one vendor.</p></div>";
			return;
		}

		$table = new Vendor_Billing_Table();
		$table->prepare_items();
		
		$generated_count = 0;
		$duplicate_count = 0;

		// Get selected filter values (defaulting to current date if not set)
		$d_val = isset($_POST['d_filter']) ? $_POST['d_filter'] : date('j');
		$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
		$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');
		
        $previous_month_year = DateTime::createFromFormat('d-m-Y', "01-$m_val-$y_val");
        $previous_month_year = $previous_month_year->modify( '-1 month' );

        $previous_day = $previous_month_year->format('d');
        $previous_month = $previous_month_year->format('m');
        $previous_year = $previous_month_year->format('Y');

		// Formatting variables
		$formatted_day = str_pad($d_val, 2, '0', STR_PAD_LEFT); // Ensures 2 digits (MM)
		$formatted_month = str_pad($m_val, 2, '0', STR_PAD_LEFT); // Ensures 2 digits (MM)
		$month_name      = date('F', mktime(0, 0, 0, (int)$m_val, 1));
		$selected_payment_methods = array( 'paypal','stripe');

		foreach ( $table->items as $item ) {
			// ONLY process if the vendor is in the selected IDs array
			if ( ! in_array( $item['ID'], $selected_ids ) ) {
				continue;
			}

			// Only process active clients with an amount to bill
			if ( $item['total_amount'] <= 0 || $item['status'] !== 'Active' ) {
				continue;
			}

			$vendor_id = $item['ID'];
			
			// 1. RULE: Invoice Name (Store Name + yyyy + MM)
			// Example: "My Store - 2026 04"
			//$invoice_name = sprintf( 'Invoice for %s - %s/%s', $item['store_name'], $formatted_month, $y_val );

            // Use previous month instead of selected month as this invoice is for previous month
			//$invoice_name = sprintf( 'Invoice for %s - %s/%s/%s', $item['store_name'], $formatted_day, $formatted_month, $y_val );
            $invoice_name = sprintf( 'Invoice for %s - %s/%s/%s', $item['store_name'], $previous_day, $previous_month, $previous_year );

			// 2. RULE: Invoice Number (INV- + ID + yyyy + MM)
			// Example: "INV-42202604"
			//$custom_invoice_no = "{$vendor_id}-{$y_val}{$formatted_month}";

			// For testing, add day
			//$custom_invoice_no = "{$vendor_id}-{$y_val}{$formatted_month}{$formatted_day}";

            $custom_invoice_no = "{$vendor_id}-{$previous_year}{$previous_month}{$previous_day}";

			// 3. UNIQUENESS CHECK: Ensure this specific Invoice Number doesn't exist
			$exists = new WP_Query([
				'post_type'      => 'sliced_invoice',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => [
					[ 'key' => '_sliced_invoice_number', 'value' => $custom_invoice_no ]
				]
			]);

			if ( $exists->have_posts() ) {
				$duplicate_count++;
				continue;
			}

			// 4. CREATE THE INVOICE
			$invoice_id = wp_insert_post([
				'post_type'   => 'sliced_invoice',
				'post_status' => 'publish',
				'post_title'  => $invoice_name,
			]);

			if ( $invoice_id ) {
				$created_timestamp = time();
				$due_timestamp = strtotime( '+15 days', $created_timestamp );
				// Assign Client and the Custom Invoice Number
				update_post_meta( $invoice_id, '_sliced_client', $vendor_id );
				update_post_meta( $invoice_id, '_sliced_invoice_number', $custom_invoice_no );
				update_post_meta( $invoice_id, '_sliced_invoice_due', $due_timestamp );
				$line_items = [];

				$category_codes = get_vendor_fees_categories($vendor_id);
				$price_per_advertising_product = 0;
				$price_per_published_product = 0;				

				if ($category_codes) {
					$price_per_advertising_product = get_vendor_category_fee_cached($category_codes['featuring'],'featuring');
					$price_per_published_product = get_vendor_category_fee_cached($category_codes['publishing'],'publishing');
				}

				// Set the Billable Line Item
				if ($item['feat_pay'] > 0) {
					$line_items[] = [
						'qty'    => $item['feat_pay'],
						//'title'   => "Advertising products fee {$formatted_month}/{$y_val}",
                        'title'   => "Advertising products fee {$previous_month}/{$previous_year}",
						'amount' => $price_per_advertising_product,
						'taxable' => 'on',
					];
				}
				if ($item['pub_pay'] > 0) {
					$line_items[] = [
						'qty'    => $item['pub_pay'],
						//'title'   => "Publishing products fee {$formatted_month}/{$y_val}",
                        'title'   => "Publishing products fee {$previous_month}/{$previous_year}",
						'amount' => $price_per_published_product,
						'taxable' => 'on',
					];
				}
				
				update_post_meta( $invoice_id, '_sliced_items', $line_items );
				
				// Set internal Sliced date meta (optional but recommended for reports)
				//update_post_meta( $invoice_id, '_sliced_invoice_created', strtotime("{$y_val}-{$m_val}-01") );
                update_post_meta( $invoice_id, '_sliced_invoice_created', $created_timestamp);

				// Set payment methods
				update_post_meta( $invoice_id, '_sliced_payment_methods', $selected_payment_methods );

				// Set the invoice status to Unpaid
				wp_set_object_terms( $invoice_id, 'unpaid', 'invoice_status', true );
				$generated_count++;
			}
		}

		echo "<div class='notice notice-success is-dismissible'><p>";
		echo "<strong>Billing Run for {$month_name} {$y_val} Complete:</strong> Created <strong>$generated_count</strong> new invoices.";
		if ($duplicate_count > 0) echo " ($duplicate_count were already in the system).";
		echo "</p></div>";
	}

	// ACTION: Send Emails for Selected Vendors
	if ( isset($_POST['send_emails'])) {

		// SECURITY CHECK: Verify the nonce
        // 'billing_nonce_field' is the name we gave to the wp_nonce_field in the form
        if ( ! isset( $_POST['email_nonce_field'] ) || ! wp_verify_nonce( $_POST['email_nonce_field'], 'bulk_email_sent' ) ) {
            wp_die( 'Security check failed. Please refresh the page and try again.' );
        }
    
		$selected_ids = isset($_POST['vendor_ids']) ? array_map('intval', $_POST['vendor_ids']) : [];
		
		if ( empty($selected_ids) ) {
			echo "<div class='notice notice-error'><p>Please select vendors to email.</p></div>";
		} else {
			$m = $_POST['m_filter'];
			$y = $_POST['y_filter'];
			$period_string = "{$y} " . str_pad($m, 2, '0', STR_PAD_LEFT);
			
			$table = new Vendor_Billing_Table();
			$table->prepare_items();
			
			$sent_count = 0;
			$not_sent_count = 0;
			$error_count = 0;

			// Get selected filter values (defaulting to current date if not set)
			$d_val = isset($_POST['d_filter']) ? $_POST['d_filter'] : date('j');
			$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
			$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');

            $previous_month_year = DateTime::createFromFormat('d-m-Y', "01-$m_val-$y_val");
            $previous_month_year = $previous_month_year->modify( '-1 month' );

            $previous_day = $previous_month_year->format('d');
            $previous_month = $previous_month_year->format('m');
            $previous_year = $previous_month_year->format('Y');
			
			// Formatting variablescategory_code
			$formatted_day = str_pad($d_val, 2, '0', STR_PAD_LEFT); // Ensures 2 digits (MM)
			$formatted_month = str_pad($m_val, 2, '0', STR_PAD_LEFT); // Ensures 2 digits (MM)
			$month_name      = date('F', mktime(0, 0, 0, (int)$m_val, 1));
			$selected_payment_methods = array( 'paypal','stripe');

			foreach ( $table->items as $item ) {
			
				// ONLY process if the vendor is in the selected IDs array
				if ( ! in_array( $item['ID'], $selected_ids ) ) {
					continue;
				}

				if ( $item['email_sent'] === true ) {
					continue;
				}

				$vendor_id = $item['ID'];
				//$custom_invoice_no = "{$vendor_id}-{$formatted_month}{$y_val}";

				// For testing, add day
				//$custom_invoice_no = "{$vendor_id}-{$y_val}{$formatted_month}{$formatted_day}";
                $custom_invoice_no = "{$vendor_id}-{$previous_year}{$previous_month}{$previous_day}";

				$exists = new WP_Query([
					'post_type'      => 'sliced_invoice',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_query'     => [
						[ 'key' => '_sliced_invoice_number', 'value' => $custom_invoice_no ]
					]
				]);

				if ( !$exists->have_posts() ) {
					$not_sent_count++;
					continue;
				}
				
				$invoice_id = $exists->posts[0]->ID;
				try {
					$sliced_notifications = Sliced_Notifications::get_instance();
					$sliced_notifications->send_the_invoice($invoice_id);                
					$sent_count++;
				}
				catch (Exception $e) {
					$error_count++;
				}				
			}

			echo "<div class='notice notice-success is-dismissible'><p>";
			echo "Successfully emailed <strong>$sent_count</strong> invoices.";
			if ($error_count > 0) echo " Failed to send $error_count emails.";
			echo "</p></div>";
		}
	}

    $table = new Vendor_Billing_Table();
    $table->prepare_items();
	// Get current selection for the UI
    $selected_m = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
    $selected_y = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Vendor Billing Management</h1>
        <hr class="wp-header-end">
        
        <form method="post" id="billing-filter-form" action="<?php echo admin_url('admin.php?page=' . $_REQUEST['page']); ?>">            
			<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
			<!-- Hidden fields to keep track of sorting -->
    		<input type="hidden" name="orderby" id="orderby" value="<?php echo esc_attr($_POST['orderby'] ?? 'id'); ?>">
    		<input type="hidden" name="order" id="order" value="<?php echo esc_attr($_POST['order'] ?? 'ASC'); ?>">
			<?php wp_nonce_field('bulk_client_create','client_nonce_field'); ?>
            <?php wp_nonce_field('bulk_invoicing','billing_nonce_field'); ?>
			<?php wp_nonce_field('bulk_email_sent','email_nonce_field'); ?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="m_filter">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                            printf('<option value="%s" %s>%s</option>', 
                                $month, 
                                selected($selected_m, $month, false), 
                                date('F', mktime(0, 0, 0, $i, 1))
                            );
                        }
                        ?>
                    </select>

                    <select name="y_filter">
                        <?php
                        $current_year = (int)date('Y');
                        for ($i = $current_year; $i >= $current_year - 5; $i--) {
                            printf('<option value="%d" %s>%d</option>', 
                                $i, 
                                selected($selected_y, $i, false), 
                                $i
                            );
                        }
                        ?>
                    </select>
					<select name="billing_status_filter" id="billing_status_filter">
						<option value="">All Statuses</option>
						<option value="billable" <?php selected($_POST['billing_status_filter'], 'billable'); ?>>Billable</option>
						<option value="generated" <?php selected($_POST['billing_status_filter'], 'generated'); ?>>Invoice Generated</option>
						<option value="sent" <?php selected($_POST['billing_status_filter'], 'sent'); ?>>Email Sent</option>
					</select>
                    <input type="submit" class="button" value="Filter">
                </div>
				<div class="alignright actions">
					<?php
					$current_search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
					// Renders the standard WordPress search input and button
					//$table->search_box( 'Search', 'search_id' );?>
					<label class="screen-reader-text" for="search_id-search-input">Search:</label>
					<input type="search" id="search_id-search-input" name="s" value="<?php echo $current_search; ?>">
					<input type="submit" id="search-submit" class="button" value="Search">
				</div>
            </div>			
            <input type="submit" name="sync_clients" class="button" value="Create Missing Sliced Clients">			
            <input type="submit" name="gen_invoices" class="button button-primary" value="Generate Invoices for Selected Month">
			<input type="submit" name="send_emails" class="button-primary" value="Email Selected Invoices">

			<?php $table->display(); ?>
        </form>
    </div>
    <?php
}


// 1. Add "Invoices" to Dokan Vendor Dashboard Menu
add_filter( 'dokan_get_dashboard_nav', 'add_sliced_invoices_to_dokan_menu', 1199 );
function add_sliced_invoices_to_dokan_menu( $urls ) {
	
    $urls['invoices'] = array(
        'title'      => __( 'Invoices', 'dokan-lite' ),
        'icon'       => '<i class="fas fa-file-invoice"></i>', // FontAwesome icon
        'url'        => dokan_get_navigation_url( 'invoices' ),
        'pos'        => 55,
		'icon_name'  => 'File',
        'permission' => 'dokan_view_product_menu' // Adjust capability as needed
    );
    return $urls;
}

// 2. Register the custom URL endpoint
add_filter( 'dokan_query_var_filter', 'register_invoices_endpoint' );
function register_invoices_endpoint( $query_vars ) {
    $query_vars[] = 'invoices';
    return $query_vars;
}

add_action( 'dokan_load_custom_template', 'load_invoices_wp_table_template' );
function load_invoices_wp_table_template( $query_vars ) {
	
    if ( isset( $query_vars['invoices'] ) ) {
      	// Load Dokan dashboard structure
        if ( ! function_exists( 'dokan_get_template_part' ) ) return;

        
		echo '<div class="dokan-dashboard-wrap">';		
		dokan_get_template_part( 'global/dashboard-nav', '', array( 'active_menu' => 'invoices' ) );        
                
		// --- THE MAGIC PART: PULLING THE PAGE CONTENT ---
		$page_path = 'client-area'; // Make sure this matches your page slug exactly
		$page_obj  = get_page_by_path( $page_path );

		if ( $page_obj ) {
			echo '<div class="dokan-dashboard-content dokan-vendor-dashboard-area">';
			// We use apply_filters('the_content') so shortcodes/blocks work
			// But we DO NOT use setup_postdata() to keep the sidebar menu safe.
			echo apply_filters( 'the_content', $page_obj->post_content );
			echo '</div>';
		} else {
			echo '<p>Error: Page "client-area" not found. Please check your page slug.</p>';
		}
		// ------------------------------------------------

        echo '</div>';			
		
    }
}

/**
 * Disable Street 2 in Vendor Dashboard but keep in Admin Menu
 */
add_filter( 'dokan_seller_address_fields', 'conditional_disable_street_2', 10 );

function conditional_disable_street_2( $fields ) {
    // If we are NOT in the WordPress Backend, remove the field
    if ( ! is_admin() && isset( $fields['street_2'] ) ) {
        unset( $fields['street_2'] );
    }
    
    return $fields;
}
/*
function bulk_update_vendor_phone_numbers() {
    // 1. Fetch all users with the 'seller' role (Dokan Vendors)
    $args = array(
        'role'    => 'seller',
        'fields'  => 'ID',
    );
    $vendor_ids = get_users( $args );

    if ( empty( $vendor_ids ) ) {
        return;
    }

    foreach ( $vendor_ids as $vendor_id ) {

        // Update Dokan Profile Settings (Serialized) ---
        $profile_settings = get_user_meta( $vendor_id, 'dokan_profile_settings', true );

        if ( is_array( $profile_settings ) && isset( $profile_settings['phone'] ) ) {
            $current_store_phone = $profile_settings['phone'];

			if (strpos($current_store_phone,'1122334455')) {
				// Update the phone value inside the array
				$profile_settings['phone'] = str_replace("1122334455", "6622334455", $current_store_phone);
			
				// update_user_meta will automatically re-serialize the array
				//print_r($vendor_id.'--'.$current_store_phone.'---->'.$profile_settings['phone'].'<br/>');				
				update_user_meta( $vendor_id, 'dokan_profile_settings', $profile_settings );
			}
        }
    }
}

bulk_update_vendor_phone_numbers();

*/



/*add_action( 'admin_menu', 'register_vrender_vendor_category_fee_pageendor_category_fee_menu' );

function register_vendor_category_fee_menu() {
    add_menu_page(
        'Vendor Category Fees',
        'Category Fees',
        'manage_options',
        'vendor-category-fees',
        'render_vendor_category_fee_page',
        'dashicons-money-alt',
        57
    );
}*/

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}





