<?php
/**
 * Vendor enable email to vendors.
 *
 * An email sent to the vendor(s) when a he or she is enabled by the admin
 *
 * @class       Dokan_Email_Vendor_Enable
 * @version     2.7.6
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
//ZZZZZZZZZZZ - Disable email heading
//echo $email_heading;

$rp_link = $data['{rp_link}'];
?>
<div style="text-align:left;color:#3c3c3c">
    <p>
        <?php 
            echo sprintf( __( 'Congratulations %s!', 'dokan' ), $data['display_name'] );                                                         
        ?>

    <br/>
        <?php 
            //ZZZZZZ - Added by Huy nguyen
            echo sprintf( __( 'Your vendor account is activated %s!', 'dokan' ), $data['display_name']);
        ?>
    <br/>
        <?php
        // ZZZZZZ - Always send the reset password link
        // Only send the set new password link if the user hasn't set their password during sign-up.
        //if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated && $set_password_url ) {
            /* translators: URL follows */
            echo sprintf( __( 'If you have not setup your password. Please use this link to <a href="%s" target="_blank">reset your password</a> then login.', 'dokan' ), $rp_link) ; 	
        //}
        ?>

    <br/>
        <?php echo sprintf( __( 'If you already setup password. Please <a href="%s" target="_blank">login</a> here', 'dokan' ), wc_get_page_permalink( 'myaccount' ) ) ; ?>
    <br/>
    <?php

    echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
    ?>
    </p>    
</div>    
