<?php
// -------------------------------------------------------------------------
// VENDOR SPECIAL INVOICES CONTROLLER (FILTERED BY PHONE STARTING WITH 22)
// -------------------------------------------------------------------------

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * 1. DEFINE PHONE-FILTERED LIST TABLE COMPONENT
 */
class Manager2_Vendor_Invoices_List_Table extends WP_List_Table {

    private $raw_dataset = array();

    public function __construct( $data = array() ) {
        parent::__construct( array(
            'singular' => 'manager2_invoice',
            'plural'   => 'manager2_invoices',
            'ajax'     => false
        ) );
        $this->raw_dataset = $data;
    }

    public function get_columns() {
        return array(
            'cb'             => '<input type="checkbox" />',
            'invoice_number' => __( 'Invoice ID', 'text-domain' ),
            'client_name'    => __( 'Vendor / Account', 'text-domain' ),
            'vendor_phone'   => __( 'Vendor Phone', 'text-domain' ),
            'created_date'   => __( 'Issue Date', 'text-domain' ),
            'due_date'       => __( 'Due Date', 'text-domain' ),
            'total_amount'   => __( 'Gross Amount', 'text-domain' ),
            'status'         => __( 'Status', 'text-domain' ),
            'gateway'        => __( 'Gateway Used', 'text-domain' ),
            'email_status'   => __( 'Email Status', 'text-domain' )
        );
    }

    protected function get_sortable_columns() {
        return array(
            'invoice_number' => array( 'invoice_number', false ),
            'created_date'   => array( 'created_date', true ), // True sets it as the default fallback trigger column
            'total_amount'   => array( 'total_amount', false )
        );
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="invoice_ids[]" value="%d" />', intval( $item['id'] ) );
    }

    public function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '—';
    }

    public function column_invoice_number( $item ) {
        $edit_link = get_edit_post_link( $item['id'] );
        $title_anchor = sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong>',
            esc_url( $edit_link ),
            esc_html( $item['invoice_number'] )
        );
        $actions = array(
            'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Edit/View Details', 'text-domain' ) )
        );
        return $title_anchor . $this->row_actions( $actions );
    }

    public function column_client_name( $item ) {
        if ( ! empty( $item['client_name'] ) ) {
            $output = esc_html( $item['client_name'] );
            if ( ! empty( $item['client_login'] ) ) {
                $output .= ' <span style="color:#8c8f94; font-size:11px;">(' . esc_html( $item['client_login'] ) . ')</span>';
            }
            return $output;
        }
        return '<span style="color:#d63638; font-style:italic;">' . __( 'Unlinked Account', 'text-domain' ) . '</span>';
    }

    public function column_vendor_phone( $item ) {
        if ( ! empty( $item['vendor_phone'] ) ) {
            return sprintf( '<span class="dashicons dashicons-phone" style="font-size:14px; width:14px; height:14px; margin-right:4px; vertical-align:middle; color:#646970;"></span><strong>%s</strong>', esc_html( $item['vendor_phone'] ) );
        }
        return '<span style="color: #8c8f94; font-style: italic; font-size: 12px;">—</span>';
    }

    public function column_total_amount( $item ) {
        return '<strong>' . wc_price( $item['total_amount'] ) . '</strong>';
    }

    public function column_status( $item ) {
        if ( $item['status'] === 'paid' ) {
            return '<span style="background: #e5f5fa; color: #007cba; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px; text-transform: uppercase;">' . __( 'Paid', 'text-domain' ) . '</span>';
        } elseif ( $item['status'] === 'unpaid' ) {
            return '<span style="background: #fcf0f1; color: #d63638; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px; text-transform: uppercase;">' . __( 'Unpaid', 'text-domain' ) . '</span>';
        }
        return '<span style="background: #f0f0f1; color: #50575e; padding: 3px 8px; border-radius: 3px; font-weight: 600; font-size: 11px; text-transform: uppercase;">' . esc_html( $item['status'] ) . '</span>';
    }

    public function column_gateway( $item ) {
        if ( ! empty( $item['gateway'] ) && $item['gateway'] !== '—' ) {
            return sprintf(
                '<span style="font-family: monospace; background: #f0f0f1; padding: 2px 6px; border: 1px solid #dcdcde; border-radius: 3px; text-transform: uppercase; font-size: 11px; font-weight: 600; color: #2c3338;">%s</span>',
                esc_html( $item['gateway'] )
            );
        }
        return '<span style="color: #8c8f94; font-style: italic; font-size: 12px;">—</span>';
    }

    public function column_email_status( $item ) {
        if ( $item['email_sent_ind'] ) {
            return '<span style="color: #46b450; font-weight: 600;"><span class="dashicons dashicons-email-alt" style="font-size:15px; width:15px; height:15px; margin-right:3px; vertical-align:middle;"></span>' . __( 'Sent', 'text-domain' ) . '</span>';
        }
        return '<span style="color: #cca200; font-weight: 600;"><span class="dashicons dashicons-email" style="font-size:15px; width:15px; height:15px; margin-right:3px; vertical-align:middle;"></span>' . __( 'Not Sent', 'text-domain' ) . '</span>';
    }

    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array( $columns, $hidden, $sortable );

        // Updated default to sort by created_date descending
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_date';
        $order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'desc';

        usort( $this->raw_dataset, function( $a, $b ) use ( $orderby, $order ) {
            $val_a = isset( $a[ $orderby ] ) ? $a[ $orderby ] : '';
            $val_b = isset( $b[ $orderby ] ) ? $b[ $orderby ] : '';

            if ( $orderby === 'total_amount' ) {
                $result = floatval( $val_a ) <=> floatval( $val_b );
            } elseif ( $orderby === 'created_date' || $orderby === 'due_date' ) {
                // Safeguard structured textual dates by resolving back to numerical Unix timestamps
                $date_a = ! empty( $val_a ) && $val_a !== '—' ? DateTime::createFromFormat( 'd/m/Y', $val_a ) : false;
                $date_b = ! empty( $val_b ) && $val_b !== '—' ? DateTime::createFromFormat( 'd/m/Y', $val_b ) : false;
                
                $ts_a = $date_a ? $date_a->getTimestamp() : 0;
                $ts_b = $date_b ? $date_b->getTimestamp() : 0;
                
                $result = $ts_a <=> $ts_b;
            } else {
                $result = strnatcasecmp( strval( $val_a ), strval( $val_b ) );
            }
            return ( $order === 'asc' ) ? $result : -$result;
        } );

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $total_items  = count( $this->raw_dataset );

        $this->items = array_slice( $this->raw_dataset, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
    }
}

/**
 * 2. DASHBOARD VIEW RENDERING FUNCTION
 */
function manager2_render_vendor_invoices_dashboard() {
    global $wpdb;
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'text-domain' ) );
    }

    // Enqueue native WordPress jQuery UI Dialog scripts and style dependencies
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );

    $summary_table = $wpdb->prefix . 'billing_period_summary';

    $range_type     = isset( $_GET['range_type'] ) ? sanitize_text_field( $_GET['range_type'] ) : 'by_month';
    $selected_month = isset( $_GET['selected_month'] ) ? sanitize_text_field( $_GET['selected_month'] ) : date( 'm' );
    $selected_year  = isset( $_GET['selected_year'] ) ? sanitize_text_field( $_GET['selected_year'] ) : date( 'Y' );
    $selected_cycle = isset( $_GET['selected_cycle'] ) ? intval( $_GET['selected_cycle'] ) : 0;

    // --- SUBMISSION PROCESSOR (BULK ACTIONS) ---
    if ( isset( $_POST['a1_action'] ) ) {
        
        // Nonce verification
        if ( ! isset( $_POST['bulk-invoices-action-nonce'] ) || ! wp_verify_nonce( $_POST['bulk-invoices-action-nonce'], 'bulk_invoice_action' ) ) {
            wp_die( 'Security check failed. Please refresh the page and try again.' );
        }

        if ( ! empty( $_POST['invoice_ids'] ) && is_array( $_POST['invoice_ids'] ) ) {
            
            // ACTION 1: SEND BULK EMAILS
            if ( $_POST['a1_action'] === 'send_bulk_emails' ) {
                $email_sent_count = 0;
                foreach ( $_POST['invoice_ids'] as $invoice_post_id ) {
                    $inv_id = intval( $invoice_post_id );
                    $client_link  = get_post_meta( $inv_id, '_sliced_client', true );
                    $target_email = '';
                    
                    if ( ! empty( $client_link ) ) {
                        if ( get_post_type( $client_link ) === 'sliced_client' ) {
                            $wp_user_id   = get_post_meta( $client_link, '_wordpress_user_id', true );
                            $user_info    = ! empty( $wp_user_id ) ? get_userdata( $wp_user_id ) : false;
                            $target_email = $user_info ? $user_info->user_email : '';
                        } else {
                            $user_info    = get_userdata( $client_link );
                            $target_email = $user_info ? $user_info->user_email : '';
                        }
                    }

                    if ( ! empty( $target_email ) ) {
                        $sliced_notifications = Sliced_Notifications::get_instance();
                        $sliced_notifications->send_the_invoice( $inv_id );                
                        $email_sent_count++;
                    }               
                }
                printf( 
                    '<div class="notice notice-success is-dismissible" style="margin-top: 15px;"><p><strong>%s %d %s</strong></p></div>',
                    __( 'Successfully dispatched notification alerts to', 'text-domain' ),
                    $email_sent_count,
                    __( 'targeted marketplace vendor addresses.', 'text-domain' )
                );
            }
            
            // ACTION 2: TRASH SELECTED INVOICES
            elseif ( $_POST['a1_action'] === 'trash_selected_invoices' ) {
                $trashed_count = 0;
                foreach ( $_POST['invoice_ids'] as $invoice_post_id ) {
                    $inv_id = intval( $invoice_post_id );
                    
                    if ( wp_trash_post( $inv_id ) ) {
                        $trashed_count++;
                    }
                }
                printf( 
                    '<div class="notice notice-success is-dismissible" style="margin-top: 15px;"><p><strong>%s %d %s</strong></p></div>',
                    __( 'Successfully moved', 'text-domain' ),
                    $trashed_count,
                    __( 'selected invoice records to the trash repository.', 'text-domain' )
                );
            }

        } else {
            printf( 
                '<div class="notice notice-warning is-dismissible" style="margin-top: 15px;"><p>%s</p></div>',
                __( 'Warning: No specific invoice row elements were selected for operations.', 'text-domain' )
            );
        }
    }

    // Fetch master dataset using extraction routine
    $raw_dataset = manager2_get_filtered_vendor_invoices( $range_type, $selected_year, $selected_month, $selected_cycle );
    
    // Grab items filtered by phone prefix 22
    $filtered_dataset = array_filter( $raw_dataset, function( $invoice ) {
        return ( strpos( $invoice['vendor_phone'], '22' ) === 0 );
    } );

    $summary_metrics = a1_calculate_vendor_invoice_metrics( $filtered_dataset );
    $available_years = a1_get_invoice_years_from_db();
    $recorded_cycles = $wpdb->get_results( "SELECT id, from_date, to_date FROM `{$summary_table}` ORDER BY id DESC" );

    $invoices_list_table = new Manager2_Vendor_Invoices_List_Table( $filtered_dataset );
    $invoices_list_table->prepare_items();
    ?>
    <div class="wrap" style="clear: both; margin-top: 20px;">
        <h1 class="wp-heading-inline"><?php _e( 'Manager 2 Invoices (Phone Starts with 22)', 'text-domain' ); ?></h1>
        <hr class="wp-header-end" style="clear: both; margin-bottom: 20px;">

        <div class="a1-filter-panel-container" style="clear: both; width: 100%; display: block; margin: 0 0 30px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); box-sizing: border-box;">
            <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; flex-direction: column; gap: 16px;">
                <input type="hidden" name="page" value="manager2-vendor-invoices-report" />
                
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <input type="radio" id="range_month" name="range_type" value="by_month" <?php checked( $range_type, 'by_month' ); ?> />
                    <label for="range_month" style="font-weight: 600; min-width: 180px; cursor: pointer;"><?php _e( 'Select Month & Year:', 'text-domain' ); ?></label>
                    <select name="selected_month" id="selected_month" style="width: 160px;">
                        <?php 
                        for ( $m = 1; $m <= 12; $m++ ) {
                            $month_val = str_pad( $m, 2, '0', STR_PAD_LEFT );
                            $month_lbl = date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) );
                            echo '<option value="' . esc_attr( $month_val ) . '" ' . selected( $selected_month, $month_val, false ) . '>' . esc_html( $month_lbl ) . '</option>';
                        }
                        ?>
                    </select>                   
                    <select name="selected_year" id="selected_year" style="width: 130px; max-width: 100%;">
                        <?php foreach ( $available_years as $yr ) : ?>
                            <option value="<?php echo esc_attr( $yr ); ?>" <?php selected( $selected_year, $yr ); ?>><?php echo esc_html( $yr ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <input type="radio" id="range_cycle" name="range_type" value="by_recorded_cycle" <?php checked( $range_type, 'by_recorded_cycle' ); ?> />
                    <label for="range_cycle" style="font-weight: 600; min-width: 180px; cursor: pointer;"><?php _e( 'Select Recorded Period:', 'text-domain' ); ?></label>
                    <select name="selected_cycle" id="selected_cycle" style="min-width: 302px;">
                        <?php if ( ! empty( $recorded_cycles ) ) : ?>
                            <?php foreach ( $recorded_cycles as $cycle ) : ?>
                                <?php
                                $label = sprintf( '%s to %s', date( 'd/m/Y', strtotime( $cycle->from_date ) ), date( 'd/m/Y', strtotime( $cycle->to_date ) ) );
                                printf( '<option value="%d" %s>%s</option>', $cycle->id, selected( $selected_cycle, $cycle->id, false ), esc_html( $label ) );
                                ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <option value="0"><?php _e( '-- No previous recorded summary runs found --', 'text-domain' ); ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-top: 4px; padding-top: 14px; border-top: 1px dashed #e5e5e5; display: flex; gap: 10px; align-items: center;">
                    <?php submit_button( __( 'Apply Special Filter', 'text-domain' ), 'primary', 'filter_action', false ); ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=manager2-vendor-invoices-report' ) ); ?>" class="button button-secondary"><?php _e( 'Clear All', 'text-domain' ); ?></a>
                </div>
            </form>
        </div>

        <!-- WordPress jQuery UI Dialog Confirmation Wrapper Structure -->
        <div id="wp-bulk-trash-dialog" title="<?php esc_attr_e( 'Move to Trash Confirmation', 'text-domain' ); ?>" style="display:none;">
            <p style="margin: 10px 0 0 0; font-size: 14px; line-height: 1.5;">
                <span class="dashicons dashicons-warning" style="color: #d63638; font-size: 22px; width: 22px; height: 22px; margin-right: 8px; vertical-align: middle;"></span>
                <?php _e( 'Are you completely sure you want to move the selected invoice entries to the trash repository? This process filters across live vendor lists.', 'text-domain' ); ?>
            </p>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var radioMonth = document.getElementById('range_month');
                var radioCycle = document.getElementById('range_cycle');
                var selectMonth = document.getElementById('selected_month');
                var selectYear  = document.getElementById('selected_year');
                var selectCycle = document.getElementById('selected_cycle');

                function syncFilterDisabledStates() {
                    if (radioMonth && radioMonth.checked) {
                        if(selectMonth) selectMonth.disabled = false;
                        if(selectYear)  selectYear.disabled  = false;
                        if(selectCycle) selectCycle.disabled = true;
                    } else if (radioCycle && radioCycle.checked) {
                        if(selectMonth) selectMonth.disabled = true;
                        if(selectYear)  selectYear.disabled  = true;
                        if(selectCycle) selectCycle.disabled = false;
                    }
                }

                if (radioMonth) radioMonth.addEventListener('change', syncFilterDisabledStates);
                if (radioCycle) radioCycle.addEventListener('change', syncFilterDisabledStates);
                syncFilterDisabledStates();

                // WP Native Dialog Engine Setup using jQuery
                jQuery(document).ready(function($) {
                    var invoiceForm = $('#manager2-invoices-ledger-table-form');
                    var trashTrigger = $('#action-trash-trigger');
                    var dialogBox = $('#wp-bulk-trash-dialog');

                    if (dialogBox.length) {
                        dialogBox.dialog({
                            autoOpen: false,
                            resizable: false,
                            height: "auto",
                            width: 420,
                            modal: true,
                            dialogClass: 'wp-dialog a1-custom-wp-dialog',
                            buttons: [
                                {
                                    text: "<?php echo esc_js( __( 'Yes, Move to Trash', 'text-domain' ) ); ?>",
                                    class: "button button-primary button-link-delete",
                                    click: function() {
                                        $(this).dialog("close");
                                        invoiceForm.append('<input type="hidden" name="a1_action" value="trash_selected_invoices" />');
                                        invoiceForm.submit();
                                    }
                                },
                                {
                                    text: "<?php echo esc_js( __( 'Cancel', 'text-domain' ) ); ?>",
                                    class: "button button-secondary",
                                    click: function() {
                                        $(this).dialog("close");
                                    }
                                }
                            ]
                        });
                    }

                    if (trashTrigger.length) {
                        trashTrigger.on('click', function(e) {
                            e.preventDefault();
                            var checkedCount = invoiceForm.find('input[name="invoice_ids[]"]:checked').length;
                            
                            if (checkedCount === 0) {
                                alert('<?php echo esc_js( __( 'Please check at least one invoice checkbox row before running operations.', 'text-domain' ) ); ?>');
                                return false;
                            }

                            dialogBox.dialog("open");
                        });
                    }
                });
            });
        </script>

        <!-- Custom Styling Overrides -->
        <style>
            .ui-dialog.wp-dialog.a1-custom-wp-dialog {
                z-index: 100050 !important;
                border: 1px solid #ccd0d4 !important;
                box-shadow: 0 3px 6px rgba(0,0,0,0.3) !important;
                background: #fff !important;
                padding: 0 !important;
            }
            .a1-custom-wp-dialog .ui-dialog-titlebar {
                background: #f6f7f7 !important;
                border-bottom: 1px solid #dcdcde !important;
                padding: 10px 15px !important;
            }
            .a1-custom-wp-dialog .ui-dialog-title {
                font-weight: 600 !important;
                color: #1d2327 !important;
                font-size: 14px !important;
            }
            .a1-custom-wp-dialog .ui-dialog-titlebar-close {
                background: none !important;
                border: none !important;
                float: right;
                margin-top: 2px;
                cursor: pointer;
                color: #646970;
            }
            .a1-custom-wp-dialog .ui-dialog-titlebar-close:before {
                content: "\f158";
                font-family: dashicons;
            }
            .a1-custom-wp-dialog .ui-dialog-content {
                padding: 20px 15px !important;
            }
            .a1-custom-wp-dialog .ui-dialog-buttonpane {
                background: #f6f7f7 !important;
                border-top: 1px solid #dcdcde !important;
                margin: 0 !important;
                padding: 10px 15px !important;
                text-align: right;
            }
            .a1-custom-wp-dialog .ui-dialog-buttonpane .button {
                margin-left: 8px !important;
            }
            .a1-custom-wp-dialog .button-link-delete {
                background: #d63638 !important;
                border-color: #d63638 !important;
                color: #fff !important;
            }
            .a1-custom-wp-dialog .button-link-delete:hover {
                background: #b32d2e !important;
                border-color: #b32d2e !important;
            }
        </style>

        <div class="invoice-summary-tiles" style="clear: both; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; width: 100%;">
            <div style="background: #fff; padding: 20px; border-left: 4px solid #46b450; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 12px; color: #646970; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Filtered Paid Amount', 'text-domain' ); ?></div>
                <div style="font-size: 26px; font-weight: 700; color: #1d2327; margin-top: 10px;"><?php echo wc_price( $summary_metrics['total_paid'] ); ?></div>
                <div style="font-size: 12px; color: #8c8f94; margin-top: 5px;"><?php echo sprintf( __( '%d Settled Invoices', 'text-domain' ), $summary_metrics['count_paid'] ); ?></div>
            </div>

            <div style="background: #fff; padding: 20px; border-left: 4px solid #b32d2e; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 12px; color: #646970; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Filtered Unpaid Amount', 'text-domain' ); ?></div>
                <div style="font-size: 26px; font-weight: 700; color: #b32d2e; margin-top: 10px;"><?php echo wc_price( $summary_metrics['total_unpaid'] ); ?></div>
                <div style="font-size: 12px; color: #8c8f94; margin-top: 5px;"><?php echo sprintf( __( '%d Pending Payment', 'text-domain' ), $summary_metrics['count_unpaid'] ); ?></div>
            </div>

            <div style="background: #fff; padding: 20px; border-left: 4px solid #135e96; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); grid-column: span 2;">
                <div style="font-size: 12px; color: #646970; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Earnings Share by Payment Gateway', 'text-domain' ); ?></div>
                <div style="margin-top: 12px;">
                    <?php if ( empty( $summary_metrics['gateways'] ) ) : ?>
                        <div style="font-style: italic; color: #8c8f94; font-size: 13px; padding-top: 5px;"><?php _e( 'No transaction histories registered for this timeframe.', 'text-domain' ); ?></div>
                    <?php else : ?>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <?php foreach ( $summary_metrics['gateways'] as $gateway => $amount ) : ?>
                                <div style="background: #f6f7f7; padding: 8px 15px; border: 1px solid #dcdcde; border-radius: 4px; min-width: 120px;">
                                    <span style="font-weight: 600; text-transform: uppercase; color: #2c3338; font-size: 11px; display: block;"><?php echo esc_html( $gateway ); ?></span>
                                    <span style="font-size: 18px; font-weight: 700; color: #135e96; display: block; margin-top: 4px;"><?php echo wc_price( $amount ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form id="manager2-invoices-ledger-table-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=manager2-vendor-invoices-report' ) ); ?>" style="clear: both; display: block; width: 100%;">
            <?php wp_nonce_field( 'bulk_invoice_action', 'bulk-invoices-action-nonce' ); ?>
            
            <div class="actions bulkactions" style="margin-bottom: 12px; display: flex; align-items: center; gap: 12px;">
                <button type="submit" name="a1_action" value="send_bulk_emails" class="button action button-secondary" style="border-color: #135e96; color: #135e96; display: inline-flex; align-items: center;">
                    <span class="dashicons dashicons-email-alt" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
                    <?php _e( 'Send Emails for Selected Invoices', 'text-domain' ); ?>
                </button>

                <button type="button" id="action-trash-trigger" class="button action" style="background: #fff; border: 1px solid #b32d2e; color: #b32d2e; display: inline-flex; align-items: center;">
                    <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
                    <?php _e( 'Move Selected to Trash', 'text-domain' ); ?>
                </button>
            </div>
            
            <?php $invoices_list_table->display(); ?>
        </form>
    </div>
    <?php
}

// -------------------------------------------------------------------------
// DATA EXTRACTION FUNCTION (WITH DOKAN VENDOR PHONE COLLECTION)
// -------------------------------------------------------------------------

function manager2_get_filtered_vendor_invoices( $range_type = 'by_month', $year = '', $month = '', $cycle_id = 0 ) {
    global $wpdb;

    $args = array(
        'post_type'      => 'sliced_invoice',
        'posts_per_page' => -1,
        'post_status'    => 'any'
    );

    if ( $range_type === 'by_recorded_cycle' && $cycle_id > 0 ) {
        $cycle_row = $wpdb->get_row( $wpdb->prepare( "SELECT from_date, to_date FROM `{$wpdb->prefix}billing_period_summary` WHERE id = %d", $cycle_id ) );
        if ( $cycle_row ) {
            $args['date_query'] = array(
                array(
                    'after'     => $cycle_row->from_date . ' 00:00:00',
                    'before'    => $cycle_row->to_date . ' 23:59:59',
                    'inclusive' => true,
                ),
            );
        }
    } elseif ( ! empty( $year ) && ! empty( $month ) ) {
        $args['date_query'] = array(
            array(
                'year'  => intval( $year ),
                'month' => intval( $month ),
            ),
        );
    }

    $query = new WP_Query( $args );
    $invoices = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();

            $invoice_no  = get_post_meta( $id, '_sliced_invoice_number', true );
            $invoice_no  = ! empty( $invoice_no ) ? $invoice_no : get_the_title();
            
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
                if ( isset( $last_payment['gateway'] ) ) { $resolved_gateway = $last_payment['gateway']; }
                elseif ( isset( $last_payment['method'] ) ) { $resolved_gateway = $last_payment['method']; }
                $gateway_label = ucfirst( str_replace( '_', ' ', $resolved_gateway ) );
            } else {
                $assigned_gateways = get_post_meta( $id, '_sliced_payment_methods', true );
                if ( ! empty( $assigned_gateways ) ) {
                    if ( ! is_array( $assigned_gateways ) ) { $assigned_gateways = array( $assigned_gateways ); }
                    $gateway_label = implode( ' / ', array_map( function($gw){ return ucfirst(str_replace('_', ' ', $gw)); }, $assigned_gateways ) );
                } else { $gateway_label = '—'; }
            }

            $created_ts = get_post_meta( $id, '_sliced_invoice_created', true );
            $due_ts     = get_post_meta( $id, '_sliced_invoice_due', true );
            $date_created = ! empty( $created_ts ) ? date_i18n( 'd/m/Y', intval( $created_ts ) ) : get_the_date( 'd/m/Y' );
            $date_due     = ! empty( $due_ts ) ? date_i18n( 'd/m/Y', intval( $due_ts ) ) : '—';

            $email_sent_ind = get_post_meta( $id, '_sliced_invoice_email_sent', true );

            $client_title = '';
            $user_login   = '';
            $raw_phone    = '';
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
                            $raw_phone = $vendor->get_phone();
                        }
                    }

                    if ( empty( $raw_phone ) ) {
                        $raw_phone = get_user_meta( $wp_user_id, 'billing_phone', true );
                    }
                }
            }

            $clean_phone = preg_replace( '/[^0-9]/', '', $raw_phone );

            $invoices[] = array(
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
                'vendor_phone'   => $clean_phone,
                'email_sent_ind' => ( intval( $email_sent_ind ) > 0 )
            );
        }
        wp_reset_postdata();
    }

    return $invoices;
}