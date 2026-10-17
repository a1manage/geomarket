<?php require get_stylesheet_directory() . "/inc/structure/custom-header.php"; ?>

<?php
add_action("wp_enqueue_scripts", "electro_custom_enqueue_styles");
add_action("wp_enqueue_scripts", "additional_electro_enqueue_scripts");
add_action("electro_masthead_v11", "custom_navbar_search", 21);

add_action("woocommerce_before_customer_login_form", "remove_electro_customer_login_form_action", -10);

function remove_electro_customer_login_form_action()
{
	remove_action("woocommerce_before_customer_login_form", "electro_wrap_customer_login_form", 0);
}

// ZZZZ Remove vendor biography field
add_action("dokan_settings_form_bottom", "remove_dokan_biography", 1);

function remove_dokan_biography()
{
	remove_action("dokan_settings_form_bottom", "render_biography_form", 10);
}

// ZZZZZZZZZZZZ - Add new function to add javascript
if (!function_exists("additional_vendor_registration_scripts")) {
	function additional_vendor_registration_scripts()
	{
		// ZZZZZZZ - Added a1geo-vendor-registration js - Huy Nguyen 10/05/2025
		wp_enqueue_script(
			"a1geo-vendor-registration",
			get_stylesheet_directory_uri() . "/assets/js/a1geo-vendor-registration.js",
			["jquery"],
			false, // $electro_version
			true
		);
	}
}
add_action("wp_enqueue_scripts", "additional_vendor_registration_scripts", 200);

function electro_custom_enqueue_styles()
{
	wp_enqueue_style("electro-custom-style", get_stylesheet_uri());
}

wp_enqueue_style("a1geo-custom-style", get_stylesheet_directory_uri() . "/assets/css/a1geo.css", [], "5.6", false);

if (!function_exists("electro_dokan_product_edit_add_specifications")) {
	function electro_dokan_product_edit_add_specifications($post, $post_id)
	{
		// Return empty to override the original function
	}
}

function remove_product_row_action($row_action, $post)
{
	if (empty($post->ID)) {
		return $row_action;
	}
	// Remove Quick Edit and View actions
	unset($row_action["quick-edit"]);
	unset($row_action["view"]);
	return $row_action;
}

if (!function_exists("custom_navbar_search")) {
	/**
	 * Displays search box in navbar
	 */
	function custom_navbar_search()
	{
		electro_get_template("sections/custom-navbar-search.php");
	}
}

if (!function_exists("add_custom_query_var")) {
	function add_custom_query_var($vars)
	{
		$vars[] = "wdt_search";
		return $vars;
	}
}
add_filter("query_vars", "add_custom_query_var");

add_filter("dokan_product_row_actions", "remove_product_row_action", 20, 2);

//// ZZZZZ - Added to prevent user to login after registering
/**
 * @snippet       Deny Login Upon Registration @ WooCommerce My Account
 * @how-to        businessbloomer.com/woocommerce-customization
 * @author        Rodolfo Melogli, Business Bloomer
 * @compatible    WooCommerce 3.7
 * @community     https://businessbloomer.com/club/
 */

add_filter("woocommerce_registration_auth_new_customer", "__return_false");
?>

<?php
add_filter("dokan_product_short_description", "dokan_product_short_description_editor");
function dokan_product_short_description_editor($settings)
{
	?>
    <script>
        jQuery(document).ready(function($) {
            var descriptionEditor = tinymce.get('post_excerpt'); 
            var minimumWords = 1;
			var maximumWords = 650;
			var characterLimit = 800;
            var $saveButton = $('#publish'); 

            $('body').on( 'click', '#publish', function(e) {
            	e.preventDefault();
				var shortDescAlertObj = $('.dokan-product-short-description-alert');
				var descriptionText = descriptionEditor.getContent({ format: 'text' });				
				//var characterCount = descriptionText.split(' ').filter(function(n) { return n != '' }).length;				
				var wordsArray = descriptionText.match(/\b\w+\b/g);
				var characterCount = wordsArray ? wordsArray.length: 0;
				if (characterCount < minimumWords || characterCount > maximumWords) {
					shortDescAlertObj.removeClass('dokan-hide');					
					shortDescAlertObj.html('The description word count is ' + characterCount + ' .It should be between ' + minimumWords + ' and ' + maximumWords +  ' words.');
					$('.dokan-product-short-description').get(0).scrollIntoView();
					return false;
				}
				if (descriptionText.length > characterLimit) {
					shortDescAlertObj.removeClass('dokan-hide');					
					shortDescAlertObj.html('The description character limit is ' + characterLimit + '.');
					$('.dokan-product-short-description').get(0).scrollIntoView();
					return false;
				}
				shortDescAlertObj.addClass('dokan-hide');
				$(this).trigger('submit');
            } );
        });
    </script>
    <?php return $settings;
}
?>

<?php
function dokan_custom_seller_registration_required_fields($required_fields)
{
	$required_fields["company_name"] = __("Please enter your Company Name", "dokan-custom");
	$required_fields["company_url"] = __("Please enter your Company website", "dokan-custom");

	return $required_fields;
}

add_filter("dokan_seller_registration_required_fields", "dokan_custom_seller_registration_required_fields");

//add_action("dokan_new_seller_created", "dokan_custom_new_seller_created", 60, 2);

add_action("dokan_before_create_vendor", "dokan_custom_new_seller_created", 60, 2);

function dokan_custom_new_seller_created($vendor_id, $data)
{
	update_user_meta($vendor_id, "shipping_address_1", $data['shipping_address_1']);
	update_user_meta($vendor_id, "shipping_state", $data['shipping_state']);
}

/* Add custom profile fields (call in theme : echo $curauth->fieldname;) */

add_action("dokan_seller_meta_fields", "my_show_extra_profile_fields");

function my_show_extra_profile_fields($user)
{
	?>

    <?php
    if (!current_user_can("manage_woocommerce")) {
    	return;
    }
    if (!user_can($user, "dokandar")) {
    	return;
    }
    $company_name = get_user_meta($user->ID, "dokan_custom_company_name", true);
    $company_url = get_user_meta($user->ID, "dokan_custom_company_url", true);
	$free_start_date = get_user_meta($user->ID, "dokan_free_start_date", true);
    ?>
        <tr>
            <th><?php esc_html_e("Company Name", "dokan-lite"); ?></th>
            <td>
                <input type="text" name="company_name" class="regular-text" value="<?php echo esc_attr(
                	$company_name
                   ); ?>"/>
            </td>
        </tr>
		<tr>
            <th><?php esc_html_e("Company URL", "dokan-lite"); ?></th>
            <td>
                <input type="text" name="company_url" class="regular-text" value="<?php echo esc_attr(
                   	$company_url
                 ); ?>"/>
            </td>
        </tr>
		<tr>
            <th><?php esc_html_e("Free start date", "dokan-lite"); ?></th>
            <td>
                <input type="text" name="free_start_date" class="regular-text" value="<?php echo esc_attr(
                   	$free_start_date
                 ); ?>"/>
            </td>
        </tr>
		
    <?php
}

add_action("personal_options_update", "my_save_extra_profile_fields");
add_action("edit_user_profile_update", "my_save_extra_profile_fields");

function my_save_extra_profile_fields($user_id)
{
	if (!current_user_can("manage_woocommerce")) {
		return;
	}
	update_usermeta($user_id, "dokan_custom_company_name", $_POST["company_name"]);
	update_usermeta($user_id, "dokan_custom_company_url", $_POST["company_url"]);
}

/**
 * Change the block formats available in TinyMCE.
 *
 * @link http://codex.wordpress.org/TinyMCE_Custom_Styles
 *
 * @param array $init Default settings to be overridden.
 *
 * @return array The modified $init array.
 */
function cdils_change_mce_block_formats($init)
{
	$block_formats = ["Title=h3", "Description=p"];
	$init["block_formats"] = implode(";", $block_formats);

	return $init;
}
add_filter("tiny_mce_before_init", "cdils_change_mce_block_formats");

/**
 * Customize the new user notification email sent to the user.
 *
 * @param array $email The email array.
 * @param WP_User $user The new user object.
 * @param string $blogname The blog name.
 * @return array Modified email array.
 */
function custom_new_user_notification_email_user($email, $user, $blogname)
{
	$key = get_password_reset_key($user);
	if (is_wp_error($key)) {
		return;
	}

	$resest_password_link = network_site_url(
		"wp-login.php?login=" . rawurlencode($user->user_login) . "&key=$key&action=rp",
		"login"
	);
	//$login_link = wp_login_url();
	$fullname = $user->first_name . " " . $user->last_name;	
	$myaccount_page_id = wc_get_page_id("myaccount");
	$login_link = get_permalink($myaccount_page_id);

	$email["subject"] = sprintf(__("Welcome to %s - Your Account Details"), $blogname);
	$email["message"] = sprintf(__("Dear %s,") . "\r\n\r\n", $fullname);
	$email["message"] .=
		__(
			"Thank you for joining Geo Market. We’re excited to have you onboard and will do our best to help you showcase your products and services to customers worldwide."
		) . "\r\n\r\n";
	$email["message"] .= __("Below are your account details:") . "\r\n\r\n";
	$email["message"] .= sprintf(__("Username: %s"), $user->user_login) . "\r\n\r\n";
	$email["message"] .= __("Password: To set your password, please click the link below:") . "\r\n";
	$email["message"] .= sprintf(__("%s"), $resest_password_link) . "\r\n\r\n";
	$email["message"] .= __("Once your password is set, you can log in to your account here:") . "\r\n";
	$email["message"] .= sprintf(__("%s"), $login_link) . "\r\n\r\n";
	$email["message"] .=
		__(
			"After logging in, please:
			 \r\n 1. Upload your company logo (this logo will link to your website):
			 \r\n     - Go to Settings → Store
			 \r\n     - Click Upload Photo, choose an image, and fill in any details on the right-hand panel if necessary.
			 \r\n     - Click Select and Crop. You can crop the image or select Skip Cropping to keep the original photo.
			 \r\n     - Update or complete your company information at this step (you can also return to edit it later).
			 \r\n     - Click Update Settings to save your logo and changes.
			 \r\n 2. Add your products or services: Follow the instructions in the attached file How_to_add_product.pdf.
			 \r\n 3. Check your listings: Browse the website to confirm that your products or services appear as intended."
		) . "\r\n\r\n";
	$email["message"] .=
		__(
			"Please also review the attached file Disclaimer_for_sellers.pdf. If you have any questions or disagree with any part of it, kindly let us know."
		) . "\r\n";
	$email["message"] .=
		__("Thank you again for your support. If you need any assistance, please contact your account manager or email us at admin@a1-geomarket.com.") .
		"\r\n";
	$email["message"] .= __("Warm regards,") . "\r\n\r\n";
	$email["message"] .= __("The Geo Market Team") . "\r\n";

	// Add attachments of disclaimer and tutorial
	$theme_path = get_stylesheet_directory();
	$attachments = [
		$theme_path . "/assets/doc/Disclaimer_for_sellers.pdf",
		$theme_path . "/assets/doc/How_to_add_product.pdf",
	];
	$email["attachments"] = $attachments;

	return $email;
}
add_filter("wp_new_user_notification_email", "custom_new_user_notification_email_user", 10, 3);

/**
 * Set the email content type to HTML.
 *
 * @return string Content type.
 */
function set_html_mail_content_type()
{
	return "text/html";
}
add_filter("wp_mail_content_type", "set_html_mail_content_type");

// To remove the content type filter after sending the email to avoid affecting other emails
add_action("wp_mail_succeeded", "reset_mail_content_type");
function reset_mail_content_type()
{
	remove_filter("wp_mail_content_type", "set_html_mail_content_type");
}

/**
 * Validation add for product cover image, temporarily disabled as it caused other issues
 *
 * @param array $errors
 * @return array $errors
 */
/*function dokan_can_add_product_validation_customized( $errors ) {
	  $postdata       = wp_unslash( $_POST );
	  $featured_image = absint( sanitize_text_field( $postdata['feat_image_id'] ) );
	  
	  if ( isset( $postdata['feat_image_id'] ) && empty( $featured_image ) && ! in_array( 'Please upload a product cover image' , $errors ) ) {
		  $errors[] = 'Please upload a product cover image';
	  }	  
	  return $errors;
	}
	add_filter( 'dokan_can_add_product', 'dokan_can_add_product_validation_customized', 35, 1 );
	add_filter( 'dokan_can_edit_product', 'dokan_can_add_product_validation_customized', 35, 1 );

	function dokan_new_product_popup_validation_customized( $errors, $data ) {	  
	  if ( isset( $data['feat_image_id'] ) && ! $data['feat_image_id'] ) {
		return new WP_Error( 'no-image', __( 'Please select AT LEAST ONE Picture', 'dokan-lite' ) );
	  }
	}
	add_filter( 'dokan_new_product_popup_args', 'dokan_new_product_popup_validation_customized', 35, 2 );*/

// Add filter to customize the My Account dropdown menu
add_filter(
	"woocommerce_account_menu_items",
	function ($items, $endpoints) {
		unset($items["dashboard"]);
		unset($items["orders"]);
		unset($items["downloads"]);
		unset($items["edit-address"]);
		unset($items["edit-account"]);
		unset($items["customer-logout"]);
		$items["dokan-dashboard"] = __("Dashboard", "woocommerce");
		$items["customer-logout"] = __("Log out", "woocommerce");
		return $items;
	},
	10,
	2
);

add_filter(
	"woocommerce_get_endpoint_url",
	function ($url, $endpoint, $value, $permalink) {
		if ($endpoint === "dokan-dashboard") {
			$dashboard_page_id = get_page_by_path("dashboard");
			$dashboard_link = get_permalink($dashboard_page_id);
			$url = $dashboard_link;
		}
		return $url;
	},
	10,
	4
);

// Add filter to redirect after login from woocommerce
add_filter("login_redirect", "my_dokan_login_redirect", 10, 3);
function my_dokan_login_redirect($redirect_to, $requested_redirect_to, $user)
{
	// Redirect logged-in users from the My Account page to another page
	// Replace 'your-custom-page-slug' with the slug of your desired page
	if (dokan_is_user_seller($user->ID)) {
		$dashboard_page_id = get_page_by_path("dashboard");
		$redirect_to = get_permalink($dashboard_page_id);
	}
	return $redirect_to;
}

/// Add more parts for mobile footer
if (!function_exists("electro_footer_address")) {
	/**
	 * Displays shop address at the footer
	 */
	function custom_mobile_footer_address()
	{
		// Default values and can be overwritten either via filters or via Theme Options
		$footer_address_title = apply_filters("electro_footer_address_title", __("Contact info", "electro"));
		$footer_address = apply_filters(
			"electro_footer_address_content",
			__("17 Princess Road, London, Greater London NW1 8JR, UK", "electro")
		);

		if (apply_filters("electro_footer_address", true) && !empty($footer_address)): ?>
			<div class="container">
				<div class="custom-footer-address">
					<strong class="footer-address-title"><?php echo esc_html($footer_address_title); ?></strong>
					<address><?php echo wp_kses_post(nl2br($footer_address)); ?></address>
				</div>
			</div>

		<?php endif;
	}
}
add_action("electro_footer_v2_handheld", "custom_mobile_footer_address", 71);
add_action("electro_footer_v2_handheld", "electro_copyright_bar_v2", 72);

add_action('pre_get_posts', 'dokan_exclude_categories_from_vendor_dashboard',999);
function dokan_exclude_categories_from_vendor_dashboard($query) {
	// Check if it's a Dokan store page and if a product is being edited or created
	if (function_exists('dokan_is_store_page') && dokan_is_store_page() && $query->is_admin() && ($query->get('post_type') == 'product' || $query->get('action') == 'dokan-new-product')) {
		// Get the IDs of the categories you want to hide
		$hidden_categories = array(65,67); // Hide Accessories

		$tax_query = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'id',
				'terms'    => $hidden_categories,
				'operator' => 'NOT IN',
			),
		);
		$query->set('tax_query', $tax_query);
	}
}

// Override the function in themes/electro/include/structure/footer-v2.php
if ( ! function_exists( 'electro_copyright_bar_v2' ) ) {
    /**
     * Displays the copyright bar
     */
    function electro_copyright_bar_v2() {

        $website_title_with_url     = sprintf( '<a href="%s">%s</a>', esc_url( home_url( '/' ) ), get_bloginfo( 'name' ) );
        $footer_copyright_text      = apply_filters( 'electro_footer_copyright_text', sprintf( __( '&copy; %s - All Rights Reserved', 'electro' ), $website_title_with_url ) );
        $credit_card_icons          = apply_filters( 'electro_footer_credit_card_icons', '' );

        if ( apply_filters( 'electro_enable_footer_credit_block', true ) ) : ?>

        <div class="copyright-bar copyright-bar-custom">
            <div class="container">
                <div class="float-start copyright"><?php echo wp_kses_post( $footer_copyright_text ); ?></div>
                <div class="float-end payment"><?php echo wp_kses_post( $credit_card_icons ); ?></div>
            </div>
        </div><?php

        endif;
    }
}

// Override the function in themes/electro/include/structure/footer-v2.php
if ( ! function_exists( 'electro_mobile_footer_v2_wrap_open' ) ) {
    /**
     * Mobile Footer wrap open
     */
    function electro_mobile_footer_v2_wrap_open() {
        $classes = '';
        if( apply_filters( 'electro_mobile_footer_v2_light_bg', false ) ) {
            $classes = 'light';
        }
        ?><div class="handheld-footer d-lg-none pt-2 v2 <?php echo esc_attr( $classes ); ?>"><?php
    }
}

// Add custom columns into Woocommerce --> Products
add_filter( 'manage_edit-product_columns', 'add_custom_products_column',15 );
function add_custom_products_column($columns){

   //remove column
   //unset( $columns['tags'] );

   //add columns
   $columns['calculated_id'] = __( 'Product id'); 
   $columns['user_name'] = __( 'Username'); 

   return $columns;
}

add_action( 'manage_product_posts_custom_column', 'render_custom_product_columns', 10, 2 );

function render_custom_product_columns( $column, $postid ) {
    if ( $column == 'calculated_id' ) {        
		$author_id  = get_post_field( 'post_author', $postid);		
		$calculated_id = $postid * 10;
		$vendor     = dokan()->vendor->get($author_id);
		$pos = strpos($vendor->get_phone(), '11');
		if ($pos === false) {
			$pos = strpos($vendor->get_phone(), '22');
			if ($pos !== false) {
				$calculated_id = $calculated_id + 22;	
			}
		} else {
			$calculated_id = $calculated_id + 11;
		};
		echo $calculated_id;
    } else if ( $column == 'user_name' ) {
		$author_id  = get_post_field( 'post_author', $postid);	
		$user_by_id = get_user_by( 'id', $author_id ); 
		//$vendor     = dokan()->vendor->get($author_id);
		//echo $vendor->get_store_name();
		echo $user_by_id->user_login;
	}
	
}

// Remove the default process_registration action
remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'process_registration' ), 20 );

// Add custom action
add_action( 'wp_loaded', 'my_custom_registration_handler', 20 );

function my_custom_registration_handler() {
	$nonce_value = isset( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? wp_unslash( $_POST['woocommerce-register-nonce'] ) : $nonce_value; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( isset( $_POST['register'], $_POST['email'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
		$username = 'no' === get_option( 'woocommerce_registration_generate_username' ) && isset( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$password = 'no' === get_option( 'woocommerce_registration_generate_password' ) && isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$email    = wp_unslash( $_POST['email'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// ZZZZZZZZZZZZZZZZZZZZZZZ - Get Company name and url
		//$myfile = fopen('log_rest.txt', 'w');
		try {
			//fwrite($myfile, 'Trying to register user \n');
			//fwrite($myfile, 'Company name \n');
			$txt = $_POST['company_name'];
			//fwrite($myfile, $txt . '\n');
			//fwrite($myfile, 'Company url \n');
			$txt = $_POST['company_url'];
			//fwrite($myfile, $txt . '\n');			
			// Try to send email
			$wcEmail = new WC_Email();
			$txt = 'After creating email \n';
			//fwrite($myfile, $txt . '\n');

			// ZZZZ- Update admin email
			//$adminUser = new WP_User(1);
			$adminUser = new WP_User(33); // admin@a1-geomarket.com
			//$adminUser = new WP_User(41); // Only for testing
			$admin_email = $adminUser->get('user_email');

			$txt = 'Admin email: ' . $admin_email;
			//fwrite($myfile, $txt . '\n');
			$email_title = 'New user registering notification';
			$email_content = '<div style="text-align: left;"><p>New user information</p>'
						   .'<p>Email: ' . $email . '</p>'
						   .'<p>First Name: ' . $_POST['fname'] . '</p>'
						   .'<p>Last Name: ' . $_POST['lname'] . '</p>'
						   .'<p>Company Name: ' . $_POST['company_name'] . '</p>'
						   .'<p>Company URL: ' . $_POST['company_url'] . '</p>'					           
						   .'</div>';

			$wcEmail->send($admin_email, $email_title, $email_content, $wcEmail->get_headers(), $wcEmail->get_attachments());
			$message = 'Your registration information is sent to administrator to review. Please wait for the next email.';
			wc_add_notice( '<strong>' . __( 'Success:', 'woocommerce' ) . '</strong> ' . $message, 'success' );
			return;
		} catch (Exception $e){
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			//fwrite($myfile, $e->getMessage() . "\n");
		}
		//fclose($myfile);

		try {
			$validation_error  = new WP_Error();
			$validation_error  = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password, $email );
			$validation_errors = $validation_error->get_error_messages();

			if ( 1 === count( $validation_errors ) ) {
				throw new Exception( $validation_error->get_error_message() );
			} elseif ( $validation_errors ) {
				foreach ( $validation_errors as $message ) {
					wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $message, 'error' );
				}
				throw new Exception();
			}

			$new_customer = wc_create_new_customer( sanitize_email( $email ), wc_clean( $username ), $password );

			if ( is_wp_error( $new_customer ) ) {
				throw new Exception( $new_customer->get_error_message() );
			}

			if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
				wc_add_notice( __( 'Your account was created successfully and a password has been sent to your email address.', 'woocommerce' ) );
			} else {
				wc_add_notice( __( 'Your account was created successfully. Your login details have been sent to your email address.', 'woocommerce' ) );
			}

			// Only redirect after a forced login - otherwise output a success notice.
			if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $new_customer ) ) {
				wc_set_customer_auth_cookie( $new_customer );

				if ( ! empty( $_POST['redirect'] ) ) {
					$redirect = wp_sanitize_redirect( wp_unslash( $_POST['redirect'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				} elseif ( wc_get_raw_referer() ) {
					$redirect = wc_get_raw_referer();
				} else {
					$redirect = wc_get_page_permalink( 'myaccount' );
				}

				wp_redirect( wp_validate_redirect( apply_filters( 'woocommerce_registration_redirect', $redirect ), wc_get_page_permalink( 'myaccount' ) ) ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				exit;
			}
		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}
}

// Redirect product category page and product page to correct page
function category_template_redirect(){
	
	$redirect_page_id = 21; // default to home page
    if( is_product_category()){	
		$term = get_queried_object();	
		$page = get_page_by_path($term->slug);
		if (!empty($page)) {
			$redirect_page_id = $page->ID;      					
		}
		wp_redirect( get_permalink( $redirect_page_id ) ); 	
    } else if( is_product()){			
		the_post();
		global $product;
		if (!empty($product)) {
			$product_id = $product->get_id();		
			// get an array of category objects
			$categories = get_the_terms( $product_id, 'product_cat');
			
			$primary_category = null;
			foreach ($categories as $category) {
				// Only check for sub category
				if ($category->parent != null) {
					$primary_category = $category;
				}
			}
			
			if (!empty($primary_category)) {
				$page = get_page_by_path($primary_category->slug);
				if (!empty($page)) {
					$redirect_page_id = $page->ID;      							
				}			
			}	
		}
		wp_redirect( get_permalink( $redirect_page_id ) ); 	
    }
	    
}
add_action( 'template_redirect', 'category_template_redirect' );

// Unqueue the themes bootstrap, and load the wpdatatable bootstrap to avoid confliction - considerting to upgrade wp datatable
function bootstrap_dequeue_script() {
    $page_content = get_the_content();
    $pageContainsWpdt = has_shortcode($page_content, 'wpdatatable');
	if ($pageContainsWpdt) {
        wp_dequeue_script('bootstrap-js');	
		wp_deregister_script( 'bootstrap-js'); 
    }
	
}
add_action('wp_print_scripts', 'bootstrap_dequeue_script', 100);

// Add a WP cron task to update all product data from view to table
add_action('wp_loaded', 'bl_refresh_product_data_exc');
function bl_refresh_product_data_exc() {
	if( ! wp_next_scheduled( 'auto_refresh_product_table_hook' ) ) {
		wp_schedule_event( 
			//time() + HOUR_IN_SECONDS, // after 1 hour
			time(),
			'hourly', // custom time interval, or you can use 'daily'
			'auto_refresh_product_table_hook' 
		);
	}
};

add_action( 'auto_refresh_product_table_hook', 'auto_refresh_product_table' );
function auto_refresh_product_table() {
	global $wpdb;
	
	// ZZZZZZZZZZZZZZZZZZZZZZZ - Log wcron to file
	$myfile = fopen('log_rest.txt', 'w');
	try {		
		fwrite($myfile, 'Trying to update all products from view to table - '.date("Y-m-d H:i:s"));
		$wpdb->query('REPLACE INTO all_products_calculated_tbl SELECT * FROM all_products_calculated');	
		return;
	} catch (Exception $e){
		echo 'Caught exception: ',  $e->getMessage(), "\n";		
		fwrite($myfile, $e->getMessage());
	}
	fclose($myfile);	
};

add_action('wp_head', 'preload_background');
function preload_background() {
?>
<link rel="preload" href="<?php echo esc_url( get_theme_file_uri( 'assets/img/bg.jpg' ));?>">     			
<?php			
}


/**
 * Remove 'Payment Methods' from the WooCommerce My Account menu
 */
add_filter( 'woocommerce_account_menu_items', 'custom_remove_my_account_links', 99 );

function custom_remove_my_account_links( $menu_links ) {    
    // This targets the standard WooCommerce 'Payment Methods' link
    if ( isset( $menu_links['payment-methods'] ) ) {
        unset( $menu_links['payment-methods'] );
    }

    return $menu_links;
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
			'pub_count'        => 'Published',
			'pub_pay'          => 'Pub (Pay Req)',
			'feat_count'       => 'Featured (Tags)',
			'feat_pay'         => 'Feat (Pay Req)',
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
			'pub_pay'      => [ 'pub_pay', false ], 
            'feat_count'   => [ 'feat_count', false ], 
			'feat_pay'     => [ 'feat_pay', false ], 
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
		$price_per_advertising_product = 1;
		$price_per_published_product = 1;
		// Get the search string
    	$search = isset( $_POST['s'] ) ? sanitize_text_field( $_POST['s'] ) : '';
		$status_filter = isset($_POST['billing_status_filter']) ? $_POST['billing_status_filter'] : '';
        $columns = $this->get_columns();
		$sortableColumns = $this->get_sortable_columns();
        $this->_column_headers = [ $columns, [], $sortableColumns ];

		$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
		$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');
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
			$pub_pay_req  = $this->count_vendor_prods($v_id, 'publish', 'published-payment-required');

			// Counts products with any tag starting with 'featured-'
			$feat_total   = $this->count_vendor_prods($v_id, 'publish', '', true); 

			// Counts products with 'featured-' tag AND specific brand
			$feat_pay_req = $this->count_vendor_prods($v_id, 'publish', 'featured-payment-required', true);

			$total_amount = ($feat_pay_req * $price_per_advertising_product) + ($pub_pay_req * $price_per_published_product);

			$custom_invoice_no = "{$v_id}-{$formatted_month}{$y_val}";
			
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
				'pub_pay'    => $pub_pay_req,
				'feat_count' => $feat_total,
				'feat_pay'   => $feat_pay_req,
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
		$cutoff_date = "{$y}-{$m}-01 00:00:00";
		
		$args = [
			'post_type'      => 'product',
			'post_status'    => $status,
			'author'         => $vendor_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => [
				[ 'before' => $cutoff_date ] 
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
add_action('admin_menu', function() {    

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
	
});

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
		$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
		$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');
		
		// Formatting variables
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
			$invoice_name = sprintf( 'Invoice for %s - %s/%s', $item['store_name'], $formatted_month, $y_val );

			// 2. RULE: Invoice Number (INV- + ID + yyyy + MM)
			// Example: "INV-42202604"
			$custom_invoice_no = "{$vendor_id}-{$formatted_month}{$y_val}";
			print_r($custom_invoice_no);

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

				// Set the Billable Line Item
				if ($item['feat_pay'] > 0) {
					$line_items[] = [
						'qty'    => $item['feat_pay'],
						'title'   => "Advertising products fee {$formatted_month}/{$y_val}",
						'amount' => $price_per_advertising_product,
						'taxable' => 'on',
					];
				}
				if ($item['pub_pay'] > 0) {
					$line_items[] = [
						'qty'    => $item['pub_pay'],
						'title'   => "Publishing products fee {$formatted_month}/{$y_val}",
						'amount' => $price_per_published_product,
						'taxable' => 'on',
					];
				}
				
				update_post_meta( $invoice_id, '_sliced_items', $line_items );
				
				// Set internal Sliced date meta (optional but recommended for reports)
				update_post_meta( $invoice_id, '_sliced_invoice_created', strtotime("{$y_val}-{$m_val}-01") );

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
			$m_val = isset($_POST['m_filter']) ? $_POST['m_filter'] : date('m');
			$y_val = isset($_POST['y_filter']) ? $_POST['y_filter'] : date('Y');
			
			// Formatting variables
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
				$custom_invoice_no = "{$vendor_id}-{$formatted_month}{$y_val}";

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

			if (!strpos($current_store_phone,'1122334455')) {
				// Update the phone value inside the array
				$profile_settings['phone'] = $current_store_phone.'1122334455';
			
				// update_user_meta will automatically re-serialize the array
				//print_r($vendor_id.'--'.$current_store_phone.'---->'.$profile_settings['phone'].'<br/>');				
				update_user_meta( $vendor_id, 'dokan_profile_settings', $profile_settings );
			}
        }
    }
}

bulk_update_vendor_phone_numbers();
*/

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
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
                               placeholder="e.g. ELEC01">
                        <p class="description">6-character unique identifier.</p>
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

add_action( 'admin_menu', 'register_vendor_category_fee_menu' );

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
}

// EXECUTION: 
// It is highly recommended to hook this to an admin action or run it once manually.
// bulk_update_vendor_phone_numbers();

// Add Google tag
/*add_action('wp_head', 'add_googleanalytics');
function add_googleanalytics() {
?>
	<script async src="https://www.googletagmanager.com/gtag/js?id=AW-973343263"></script>
	<script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'AW-973343263'); </script>
	<script> gtag('event', 'conversion', {'send_to': 'AW-973343263/9vqHCIuw2-8BEJ-UkNAD'}); </script>
	<script> gtag('event', 'conversion', {'send_to': 'AW-973343263/ISTMCNCQ6e8BEJ-UkNAD'}); </script>		
<?php } */
?>

