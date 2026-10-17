<?php
/**
 * Login Form (by default)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if( isset( $_GET['action'] ) == 'register' ) {
    wc_get_template( 'myaccount/form-register-single-page.php' );
} else {
    wc_get_template( 'myaccount/form-login-single-page.php' );
}
?>