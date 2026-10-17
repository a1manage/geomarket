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

wp_enqueue_style("a1geo-custom-style", get_stylesheet_directory_uri() . "/assets/css/a1geo.css", [], "5.7", false);

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

// Prevent direct file access for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define theme-wide directory path constants for cleaner inclusion strings
define( 'THEME_INC_DIR', get_stylesheet_directory() . '/includes/' );

/**
 * Core Component Loading Array
 */
$theme_includes = [    
	'product-extra.php', // Additional properties such as published date and featured assigned date
    'a1-management.php', // Custom WP_List_Tables and admin dashboard panels
];

// Loop through and cleanly require each file
foreach ( $theme_includes as $file ) {
    $file_path = THEME_INC_DIR . $file;	
    
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // Trigger a noticeable admin warning if a file goes missing during local development
        error_log( sprintf( 'Theme error: Unable to locate include file target at %s', $file_path ) );
    }
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

