<?php
/**
* Vendor enable email to vendors.
*
* An email sent to the vendor(s) when a he or she is enabled by the admin
*
* @class    Dokan_Email_Vendor_Disable
* @version  2.7.6
*
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// ZZZZZ - Disable heading
//do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<div style="text-align:left;color:#3c3c3c">
    <p>
        <?php sprintf( __( 'Hello %s', 'dokan' ), $data['display_name'] ); ?>
        <?php _e( 'Sorry, your vendor account is deactivated.', 'dokan' ); ?>
        <?php _e( 'You can\'t sell or upload product anymore. To activate your account please contact with the admin.', 'dokan' ); ?>
        <?php
            // ZZZZ - Enabled by Huy Nguyen
            echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
            // ZZZZ - Disabled by Huy Nguyen
            //do_action( 'woocommerce_email_footer', $email );
        ?>
    </p>
</div>    