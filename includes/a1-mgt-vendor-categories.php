<?php
// -------------------------------------------------------------------------
// VENDOR Categories SUBMENU CONTROLLER USING THE CORE WP_LIST_TABLE API
// -------------------------------------------------------------------------

// Ensure the core WordPress standard WP_List_Table dependency class is loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Vendor_Categories_List_Table extends WP_List_Table {

    private $fee_cache = [];

    public function __construct() {
        parent::__construct( [
            'singular' => 'vendor_category',
            'plural'   => 'vendor_categories',
            'ajax'     => false
        ] );
        
        $this->preload_fee_metrics();
    }

    private function preload_fee_metrics() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT category_code, category_type, fee_amount FROM product_category_fees", ARRAY_A);
        if ( $results ) {
            foreach ( $results as $row ) {
                $key = strtoupper($row['category_code']) . '_' . strtolower($row['category_type']);
                $this->fee_cache[$key] = floatval($row['fee_amount']);
            }
        }
    }

    private function get_fee_from_cache($code, $type) {
        $key = strtoupper($code) . '_' . strtolower($type);
        return isset($this->fee_cache[$key]) ? $this->fee_cache[$key] : 0.00;
    }

    // 1. Added 'user_login' and 'store_name' columns
    public function get_columns() {
        return [
            'vendor_name'    => 'Display Name',
            'user_login'     => 'Account Name (Username)',
            'store_name'     => 'Store Name',
            'phone'          => 'Phone Number',
            'feat_cat'       => 'Featuring Cat',
            'feat_fee'       => 'Featuring Fee',
            'pub_cat'        => 'Publishing Cat',
            'pub_fee'        => 'Publishing Fee',
        ];
    }

    // 2. Updated sortable configurations
    protected function get_sortable_columns() {
        return [
            'vendor_name' => [ 'display_name', false ],
            'user_login'  => [ 'user_login', false ],
            'store_name'  => [ 'store_name', false ],
            'feat_cat'    => [ 'feat_cat', false ],
            'feat_fee'    => [ 'feat_fee', false ],
            'pub_cat'     => [ 'pub_cat', false ],
            'pub_fee'     => [ 'pub_fee', false ],
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'vendor_name':
                return '<strong>' . esc_html($item['display_name']) . '</strong><br><span class="description">ID: ' . $item['ID'] . '</span>';
            case 'user_login':
                return '<code>' . esc_html($item['user_login']) . '</code>';
            case 'store_name':
                return esc_html($item['store_name'] ? $item['store_name'] : '—');
            case 'phone':
                return esc_html($item['phone'] ? $item['phone'] : '—');
            case 'feat_cat':
            case 'pub_cat':
                return '<code style="font-size:14px; background:#f0f0f1; padding:2px 6px; border-radius:3px;">' . esc_html($item[$column_name]) . '</code>';
            case 'feat_fee':
            case 'pub_fee':
                return '$' . number_format($item[$column_name], 2);
            default:
                return isset($item[$column_name]) ? $item[$column_name] : '';
        }
    }

    public function prepare_items() {
        // 3. Request 'user_login' field along with ID and display_name
        $vendor_args = [
            'role'    => 'seller',
            'fields'  => ['ID', 'display_name', 'user_login'],
            'number'  => -1 
        ];

        $search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        if ( ! empty( $search_term ) ) {
            $vendor_args['search']         = '*' . $search_term . '*';
            $vendor_args['search_columns'] = ['ID', 'display_name', 'user_login', 'user_email'];
        }

        $vendor_query = get_users($vendor_args);
        $data = [];

        foreach ( $vendor_query as $vendor ) {
            // Fetch Dokan Profile Settings to extract Phone and Store Name
            $dokan_settings = get_user_meta( $vendor->ID, 'dokan_profile_settings', true );
            
            // Extract Store Name
            $store_name = ( is_array( $dokan_settings ) && ! empty( $dokan_settings['store_name'] ) ) ? $dokan_settings['store_name'] : '';

            // Extract Phone Number
            $phone = get_user_meta( $vendor->ID, 'billing_phone', true );
            if ( empty( $phone ) && is_array( $dokan_settings ) && ! empty( $dokan_settings['phone'] ) ) {
                $phone = $dokan_settings['phone'];
            }

            $clean_phone = preg_replace( '/[^0-9]/', '', $phone );

            // Slicing Rules: Featuring = Index 3-4, Publishing = Index 5-6
            if ( strlen( $clean_phone ) >= 7 ) {
				$category_codes = get_vendor_fees_categories($vendor->ID);
				if ($category_codes) {
					$feat_cat = $category_codes['featuring'];
                	$pub_cat  = $category_codes['publishing'];
				} else {
					$feat_cat = '00';
                	$pub_cat  = '00';	
				}                
            } else {
                $feat_cat = '00';
                $pub_cat  = '00';
            }
			
			$pub_fee = get_vendor_category_fee_cached($pub_cat, 'Publishing');
			$feat_fee = get_vendor_category_fee_cached($feat_cat, 'Featuring');            
            //$pub_fee  = $this->get_fee_from_cache($pub_cat, 'Publishing');
            //$feat_fee = $this->get_fee_from_cache($feat_cat, 'Featuring');

            $data[] = [
                'ID'           => $vendor->ID,
                'display_name' => $vendor->display_name,
                'user_login'   => $vendor->user_login, // Account Username
                'store_name'   => $store_name,         // Dokan Shop Name
                'phone'        => $phone,
                'feat_cat'     => $feat_cat,
                'feat_fee'     => $feat_fee,
                'pub_cat'      => $pub_cat,
                'pub_fee'      => $pub_fee
            ];
        }

        // Handle Array Sorting Operations
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'display_name';
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

        usort( $data, function( $a, $b ) use ( $orderby, $order ) {
            $val_a = $a[$orderby];
            $val_b = $b[$orderby];

            if ( is_numeric($val_a) && is_numeric($val_b) ) {
                $result = $val_a <=> $val_b;
            } else {
                $result = strnatcasecmp( $val_a, $val_b );
            }

            return ( $order === 'asc' ) ? $result : -$result;
        });

        // Setup Pagination
        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
    }
}

function render_vendor_categories_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $vendorCategoriesTable = new Vendor_Categories_List_Table();
    $vendorCategoriesTable->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Vendor Category Fee Mappings</h1>
        <p class="description">Displays a breakdown of each vendor based on their parsed profile phone configuration data.</p>
        <hr class="wp-header-end">
        
        <form method="get" style="margin-top:15px;">
            <!-- Keeps the user on the specific custom admin submenu screen during tracking loops -->
            <input type="hidden" name="page" value="vendor-categories" />
            
            <?php 
            // Render search layout elements natively inside table control line
            $vendorCategoriesTable->search_box( 'Search Vendors', 'vendor-search-input' ); 
            
            // Output table structures
            $vendorCategoriesTable->display(); 
            ?>
        </form>
    </div>
    <?php
}
