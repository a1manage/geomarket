<?php
// -------------------------------------------------------------------------
// VENDOR Categories SUBMENU CONTROLLER USING THE CORE WP_LIST_TABLE API
// -------------------------------------------------------------------------

// Ensure the core WordPress standard WP_List_Table dependency class is loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Vendor_Category_Fee_List extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'vendor_fee',
            'plural'   => 'vendor_fees',
            'ajax'     => false
        ] );
    }

    // Define the columns
    public function get_columns() {
        return [
            'category_code' => 'Category Code',
            'category_type' => 'Type',
            'fee_amount'    => 'Fee Amount',
            'updated_at'    => 'Last Updated',
        ];
    }

    // Make columns sortable
    protected function get_sortable_columns() {
        return [
            'category_code' => [ 'category_code', false ],
			'category_type' => [ 'category_type', false ],
            'fee_amount'    => [ 'fee_amount', false ],
            'updated_at'    => [ 'updated_at', true ],
        ];
    }

    // Render the data for each column
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'category_code':
                $edit_url = admin_url( "admin.php?page=vendor-category-fees&action=edit&code={$item['category_code']}&type={$item['category_type']}" );
                $delete_url = admin_url( "admin.php?page=vendor-category-fees&action=delete&code={$item['category_code']}&type={$item['category_type']}" );
                
                $actions = [
                    'edit'   => sprintf( '<a href="%s">Edit</a>', $edit_url ),
                    'delete' => sprintf( '<a href="%s" style="color:red;" onclick="return confirm(\'Delete this fee?\')">Delete</a>', $delete_url ),
                ];
                return sprintf( '<strong>%1$s</strong> %2$s', $item['category_code'], $this->row_actions( $actions ) );
            
            case 'fee_amount':
                return '$' . number_format( $item[ $column_name ], 2 );
            
            default:
                return $item[ $column_name ];
        }
    }

    // Prepare the items (Querying the DB)
    public function prepare_items() {
        global $wpdb;
        $table_name = 'vendor_category_fees';
        
        $orderby = ! empty( $_GET['orderby'] ) ? sanitize_sql_orderby( $_GET['orderby'] ) : 'created_at';
        $order   = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

        $query = "SELECT * FROM $table_name ORDER BY $orderby $order";
        
        $data = $wpdb->get_results( $query, ARRAY_A );
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count( $data );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
    }
}

function render_vendor_category_fee_page() {
    global $wpdb;
    $table_name = 'vendor_category_fees';
    $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';

    echo '<div class="wrap"><h1 class="wp-heading-inline">Vendor Category Fees</h1>';
    echo '<a href="' . admin_url( 'admin.php?page=vendor-category-fees&action=add' ) . '" class="page-title-action">Add New</a><hr class="wp-header-end">';

    // Handle Deletion logic
    if ( $action == 'delete' && isset( $_GET['code'], $_GET['type'] ) ) {
        $wpdb->delete( $table_name, [ 
            'category_code' => sanitize_text_field( $_GET['code'] ), 
            'category_type' => sanitize_text_field( $_GET['type'] ) 
        ] );
        echo '<div class="updated"><p>Fee removed.</p></div>';
        $action = 'list';
    }

    if ( $action == 'add' || $action == 'edit' ) {
        // Reuse the form function from the previous response
        render_fee_form( $table_name );
    } else {
        // Use our new WP_List_Table class
        $feeListTable = new Vendor_Category_Fee_List();
        $feeListTable->prepare_items();
        
        echo '<form method="post">';
        $feeListTable->display();
        echo '</form>';
    }
    echo '</div>';
}

function render_fee_form( $table_name ) {
    global $wpdb;
    
    // 1. Capture the "Old" identification from the URL (if editing)
    $old_code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
    $old_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
    $fee_val  = 0;

    // 2. Load existing data for the form fields
    if ( $old_code && $old_type ) {
        $row = $wpdb->get_row( $wpdb->prepare( 
            "SELECT * FROM $table_name WHERE category_code = %s AND category_type = %s", 
            $old_code, $old_type 
        ) );
        if ( $row ) {
            $fee_val = $row->fee_amount;
        }
    }

    // 3. Handle Form Submission
    if ( isset( $_POST['save_fee'] ) ) {
        $new_code = strtoupper( substr( sanitize_text_field( $_POST['cat_code'] ), 0, 6 ) );
        $new_type = sanitize_text_field( $_POST['cat_type'] );
        $new_fee  = floatval( $_POST['fee_amount'] );

        /**
         * PRIMARY KEY LOGIC:
         * If the user changed the Code or the Type, the Primary Key has changed.
         * We must delete the old record first, otherwise $wpdb->replace will 
         * just create a duplicate row instead of updating the existing one.
         */
        if ( $old_code && $old_type ) {
            if ( $old_code !== $new_code || $old_type !== $new_type ) {
                $wpdb->delete( $table_name, [ 
                    'category_code' => $old_code, 
                    'category_type' => $old_type 
                ] );
            }
        }

        // Insert or Update the record
        $wpdb->replace( $table_name, [
            'category_code' => $new_code,
            'category_type' => $new_type,
            'fee_amount'    => $new_fee,
            'updated_at'    => current_time( 'mysql' )
        ] );

		delete_vendor_category_fee_cache();

        echo '<div class="updated"><p>Fee configuration saved successfully!</p></div>';
        echo '<script>window.location.href="' . admin_url('admin.php?page=vendor-category-fees') . '";</script>';
        return;
    }

    $fee_types = ['Publishing', 'Featuring'];
    $list_url  = admin_url('admin.php?page=vendor-category-fees');
    ?>
    
    <div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="cat_code">Category Code</label></th>
                    <td>
                        <input type="text" name="cat_code" id="cat_code" 
                               value="<?php echo esc_attr( $old_code ); ?>" 
                               class="regular-text" maxlength="6" required
                               placeholder="e.g. 15">
                        <p class="description">2-number unique identifier</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cat_type">Fee Type</label></th>
                    <td>
                        <select name="cat_type" id="cat_type" class="postform" style="min-width: 200px;" required>
                            <option value="">Select a Type...</option>
                            <?php foreach ( $fee_types as $ft ) : ?>
                                <option value="<?php echo $ft; ?>" <?php selected( $old_type, $ft ); ?>>
                                    <?php echo $ft; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="fee_amount">Fee Amount</label></th>
                    <td>
                        <input type="number" step="0.01" name="fee_amount" id="fee_amount" 
                               value="<?php echo esc_attr( $fee_val ); ?>" 
                               class="small-text" required>
                    </td>
                </tr>
            </table>

            <div class="submit-wrapper" style="display: flex; align-items: center; gap: 20px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <?php submit_button( 'Save Fee Settings', 'primary', 'save_fee', false ); ?>
                
                <a href="<?php echo esc_url( $list_url ); ?>" style="text-decoration: none; color: #d63638; font-weight: 500;">
                    <span class="dashicons dashicons-arrow-left-alt" style="font-size: 18px; vertical-align: middle;"></span> 
                    Cancel and Return to List
                </a>
            </div>
        </form>
    </div>
    <?php
}