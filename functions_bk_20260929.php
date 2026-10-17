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

wp_enqueue_style("a1geo-custom-style", get_stylesheet_directory_uri() . "/assets/css/a1geo.css", [], "1.7", false);

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
	$block_formats = ["Title=h2", "Description=p"];
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

	// Retrieve administrative email address dynamically
	//$admin_email = get_option('admin_email');
    $admin_email = 'jzhe@zzgeo.com';

	// Preserve existing headers if present or initialize array, then append CC
	$headers = isset($email['headers']) ? (array) $email['headers'] : [];
	$headers[] = 'Bcc: ' . $admin_email;
	$email['headers'] = $headers;

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
		$pos = str_starts_with($vendor->get_phone(), '11');
		if ($pos === false) {
			$pos = str_starts_with($vendor->get_phone(), '22');
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

			// --- NEW DOMAIN MATCH VALIDATION ---
			if ( ! empty( $_POST['company_url'] ) ) {
				// Extract email domain (e.g., user@company.com -> company.com)
				$email_parts = explode( '@', $email );
				$email_domain = strtolower( trim( end( $email_parts ) ) );

				// Clean and extract host from company URL (e.g., https://www.company.com/about -> www.company.com)
				$company_url = trim( $_POST['company_url'] );
				// Prepend scheme if missing so parse_url can accurately process the host
				if ( ! preg_match( '~^https?://~i', $company_url ) ) {
					$company_url = 'http://' . $company_url;
				}
				$url_host = strtolower( parse_url( $company_url, PHP_URL_HOST ) );

				// Strip 'www.' if present to ensure reliable matching
				$email_domain_clean = preg_replace( '/^www\./', '', $email_domain );
				$url_host_clean     = preg_replace( '/^www\./', '', $url_host );

				// Compare domains. If they do not match, throw an error.
				if ( $email_domain_clean !== $url_host_clean ) {
					throw new Exception( __( 'Your email domain must match your company website domain.', 'woocommerce' ) );
				}
			}
			// --- END OF NEW DOMAIN MATCH VALIDATION ---

			// Send registration information to admin, don't create user
			try {
				$wcEmail = new WC_Email();
				$adminUser = new WP_User(33); // admin@a1-geomarket.com
				$admin_email = $adminUser->get('user_email');				

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
				// Log administrative email errors silently to avoid disrupting user experience
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Admin Email Fail: ' . $e->getMessage() );
				}
			}
			
		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage(), 'error' );
			}
		}
	}
}

// Redirect product category page and product page to correct page
function category_template_redirect() {
    if ( is_admin() ) {
        return;
    }

    $redirect_page_id = 21; // Default fallback target page ID (Home Page)
    $should_redirect  = false;
    $target_url       = '';

    // Normalize the current requested URI path string
    $current_uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : '';
    
    if ( str_contains( $current_uri, '?' ) ) {
        $parts = explode( '?', $current_uri );
        $current_uri = trim( $parts[0], '/' );
    }

    // 1. DYNAMIC MATCH: INTERCEPT VENDOR STORES AND AUTHOR ARCHIVES
    if ( 
        ( function_exists( 'dokan_is_store_page' ) && dokan_is_store_page() ) || 
        is_author() ||
        preg_match( '#^(store|author)/[^/]+/?$#i', $current_uri ) 
    ) {
        $vendor_id = 0;

        // Try extracting author context using standard queried object schemas or user queries
        $custom_object = get_queried_object();
        if ( isset( $custom_object->ID ) ) {
            $vendor_id = $custom_object->ID; // Handles Dokan shop queries
        } elseif ( is_author() || ( $custom_object instanceof WP_User ) ) {
            $vendor_id = get_queried_object_id(); // Handles author page nodes
        }

        $target_sub_cat = null;

        // If we found a valid user ID context, check their published product catalog
        if ( $vendor_id ) {
            $vendor_product_ids = get_posts( array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'author'         => $vendor_id,
                'posts_per_page' => -1,
                'fields'         => 'ids'
            ) );

            if ( ! empty( $vendor_product_ids ) ) {
                $vendor_categories = wp_get_object_terms( $vendor_product_ids, 'product_cat', array(
                    'orderby' => 'name',
                    'order'   => 'ASC'
                ) );

                if ( ! is_wp_error( $vendor_categories ) && ! empty( $vendor_categories ) ) {
                    // Loop and isolate the first matching SUB-CATEGORY (parent != 0)
                    foreach ( $vendor_categories as $cat ) {
                        if ( 0 !== (int) $cat->parent ) { 
                            $target_sub_cat = $cat;
                            break; 
                        }
                    }

                    // Strict Sub-Category constraint enforcement:
                    // If no child category exists for this user, do NOT fall back to main category.
                    // It will remain empty here and fallback cleanly to home page below.
                }
            }
        }

        // 2. CONFIGURE EXACT TARGET URL OR FALLBACK TO HOME PAGE
        if ( ! empty( $target_sub_cat ) ) {
            // Force flat structural URL format: domain.com/sub-category-slug/
            $target_url      = home_url( '/' . $target_sub_cat->slug . '/' );
            $should_redirect = true;
        } else {
            // Explicit rule: If no sub-category context can be found, target home page (ID 21 or home link)
            $target_url      = home_url( '/' ); 
            $should_redirect = true;
        }
    }
    // 3. HANDLE PRODUCT CATEGORY PAGES
    else if ( is_product_category() ) { 
        $term = get_queried_object();   
        if ( ! empty( $term->slug ) ) {
            $page = get_page_by_path( $term->slug );
            if ( ! empty( $page ) ) {
                $redirect_page_id = $page->ID;
                $should_redirect  = true;
            }
        }
    } 
    // 4. HANDLE SINGLE PRODUCT PAGES
    else if ( is_product() ) {           
        $product_id = get_queried_object_id();
        
        if ( $product_id ) {
            $categories = get_the_terms( $product_id, 'product_cat' );
            
            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                $primary_category = null;
                
                foreach ( $categories as $category ) {
                    if ( 0 !== (int) $category->parent ) {
                        $primary_category = $category;
                        break; 
                    }
                }
                
                if ( empty( $primary_category ) ) {
                    $primary_category = reset( $categories );
                }
                
                if ( ! empty( $primary_category->slug ) ) {
                    $page = get_page_by_path( $primary_category->slug );
                    if ( ! empty( $page ) ) {
                        $redirect_page_id = $page->ID;
                        $should_redirect  = true;
                    }           
                }   
            }
        }
    }

    // 5. EXECUTE REDIRECTION PROCESS SECURELY WITH TERMINATION EXITS
    if ( $should_redirect ) {
        if ( empty( $target_url ) ) {
            if ( is_page( $redirect_page_id ) ) {
                return;
            }
            $target_url = get_permalink( $redirect_page_id );
        }

        if ( ! empty( $target_url ) && ! is_wp_error( $target_url ) ) {
            wp_redirect( $target_url );  
            exit; 
        }
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
	'remove-unncessary-acf-data.php', // Remove data before saving product
	'export-data.php', // Export data daily	
	'advertisement-extra.php', // Advertisement extra
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

/**
 * Create the custom access log database table on theme switch/activation
 * Note: Table name is now strictly 'page_access_logs' (no prefix)
 */
/*function a1geo_create_access_logs_table() {
    global $wpdb;
    $table_name = 'page_access_logs'; 
    
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        page_id bigint(20) DEFAULT 0 NOT NULL,
        page_url text NOT NULL,
        ip_address varchar(45) NOT NULL,
        user_agent text NOT NULL,
        accessed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY page_id (page_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
add_action( 'after_switch_theme', 'a1geo_create_access_logs_table' );*/

/**
 * Helper function to ensure the current month's log table exists.
 *
 * @param string $table_name The target monthly table name (e.g., 'page_access_logs_2026_09').
 */
function a1geo_ensure_monthly_access_log_table( $table_name ) {
    global $wpdb;

    // Fast check to see if table already exists in DB
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
        return;
    }

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        page_id bigint(20) DEFAULT 0 NOT NULL,
        page_url text NOT NULL,
        ip_address varchar(45) NOT NULL,
        user_agent text NOT NULL,
        accessed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY page_id (page_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/** Hook to record page access into monthly tables */
function a1geo_record_page_access() {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    // --- EXCLUSION FILTERS ---
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? trim( $_SERVER['REQUEST_URI'], '/' ) : '';
    
    if ( str_contains( $request_uri, '?' ) ) {
        $parts = explode( '?', $request_uri );
        $request_uri = trim( $parts[0], '/' );
    }

    if ( 
        preg_match( '#^(store|author)/[^/]+/?$#i', $request_uri ) || 
        preg_match( '#^\.well-known/.*$#i', $request_uri ) 
    ) {
        return; // Early exit
    }
    // --- END EXCLUSION FILTERS ---

    global $wp;
    $current_url = home_url( add_query_arg( array(), $wp->request ) );

    if ( str_contains( $current_url, 'wp-content' ) ) {
        return;
    }

    global $wpdb;

    // Dynamically generate table name per month: page_access_logs_YYYY_MM
    $table_name = 'page_access_logs_' . date( 'Y_m' ); 

    // Ensure the monthly table exists before attempting insertion
    a1geo_ensure_monthly_access_log_table( $table_name );

    $current_user_id = get_current_user_id();
    $current_page_id = get_queried_object_id();

    // Grab Visitor IP safely
    $ip_address = '0.0.0.0';
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip_array = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $ip_address = trim( $ip_array[0] );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    
    $ip_address = sanitize_text_field( $ip_address );

    if ( str_starts_with( $ip_address, '::ffff:' ) ) {
        $ip_address = substr( $ip_address, 7 );
    }

    $user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 500 ) : 'Unknown';

    $wpdb->insert(
        $table_name,
        array(
            'user_id'    => $current_user_id,
            'page_id'    => (int) $current_page_id,
            'page_url'   => esc_url_raw( $current_url ),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
        ),
        array( '%d', '%d', '%s', '%s', '%s' )
    );
}
add_action( 'template_redirect', 'a1geo_record_page_access' );

/**
 * Programmatically import Dokan vendors from a 5-column CSV file.
 * CSV Structure: [0] Account Name, [1] Store Name, [2] Email, [3] Password, [4] Country Code
 *
 * @param string $csv_file_path      Full server path to the input CSV file.
 * @param string $output_skipped_csv Full server path where skipped records will be written.
 */
function import_dokan_vendors_from_csv( $csv_file_path, $output_skipped_csv ) {
    if ( ! file_exists( $csv_file_path ) || ! is_readable( $csv_file_path ) ) {
        error_log( "Dokan Import Error: Source file not found or unreadable at {$csv_file_path}" );
        return;
    }

    $input_handle = fopen( $csv_file_path, 'r' );
    if ( $input_handle === false ) {
        return;
    }

    // Open file stream for outputting skipped rows
    $skipped_handle = fopen( $output_skipped_csv, 'w' );
    if ( $skipped_handle !== false ) {
        // Write CSV Header for the skipped file (6th column is reason)
        fputcsv( $skipped_handle, array( 'Account Name', 'Store Name', 'Email', 'Password', 'Country', 'Skip Reason' ) );
    }

    $row_count    = 0;
    $imported     = 0;
    $skipped      = 0;
    $phone_number = '2223344556677';

    // Get list of valid WooCommerce 2-letter country codes if available
    $valid_countries = function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_countries() : array();

    while ( ( $data = fgetcsv( $input_handle, 1000, ',' ) ) !== false ) {
        $row_count++;

        // Helper function to handle skipping logic consistently
        $record_skip = function( $reason ) use ( &$skipped, $skipped_handle, $data ) {
            $skipped++;
            if ( $skipped_handle !== false ) {
                $row_to_write   = $data;
                $row_to_write[] = $reason; // Append reason as 6th column
                fputcsv( $skipped_handle, $row_to_write );
            }
        };

        // Skip input header row if present
        if ( $row_count === 1 && ! is_email( trim( $data[2] ?? '' ) ) ) {
            continue;
        }

        $username   = sanitize_user( trim( $data[0] ?? '' ) );
        $store_name = sanitize_text_field( trim( $data[1] ?? '' ) );
        $email      = sanitize_email( trim( $data[2] ?? '' ) );
        $password   = trim( $data[3] ?? '' );
        $raw_country= strtoupper( sanitize_text_field( trim( $data[4] ?? '' ) ) );

        // Validate Country Code: If valid ISO code, use it; otherwise ignore and leave empty
        $country = '';
        if ( ! empty( $raw_country ) ) {
            if ( ! empty( $valid_countries ) ) {
                // If WC is active, check against official country codes
                if ( isset( $valid_countries[ $raw_country ] ) ) {
                    $country = $raw_country;
                }
            } elseif ( strlen( $raw_country ) === 2 ) {
                // Fallback check if WC isn't loaded: ensure it's a 2-character code
                $country = $raw_country;
            }
        }

        // 1. Basic Field Validation (Username, Email, Password are mandatory)
        if ( empty( $username ) || empty( $email ) || ! is_email( $email ) || empty( $password ) ) {
            $record_skip( 'Invalid or missing user data (username, valid email, or password required).' );
            continue;
        }

        // 2. Check for existing Username or Email
        if ( username_exists( $username ) ) {
            $record_skip( "Username '{$username}' already exists." );
            continue;
        }

        if ( email_exists( $email ) ) {
            $record_skip( "Email '{$email}' already exists." );
            continue;
        }

        // 3. Create WordPress User
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $record_skip( 'wp_create_user error: ' . $user_id->get_error_message() );
            continue;
        }

        // Set role to Dokan vendor ('seller')
        $user = new WP_User( $user_id );
        $user->set_role( 'seller' );

        // 4. Configure Dokan Selling & Publishing Permissions
        update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
        update_user_meta( $user_id, 'dokan_publishing', 'yes' );

        // 5. Build & Save Dokan Profile Settings
        $profile_settings = array(
            'store_name' => $store_name,
            'phone'      => $phone_number,
            'address'    => array(
                'street_1' => '',
                'street_2' => '',
                'city'     => '',
                'zip'      => '',
                'state'    => '',
                'country'  => $country, // Populated if valid, empty string if ignored
            ),
        );

        update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );
        update_user_meta( $user_id, 'dokan_store_name', $store_name );

        $imported++;
    }

    fclose( $input_handle );
    if ( $skipped_handle !== false ) {
        fclose( $skipped_handle );
    }

    error_log( "Dokan Vendor Import Summary: {$imported} imported, {$skipped} skipped out of {$row_count} entries." );
}

add_action( 'init', function() {
    // Check if current URL path contains '/import/' and the query parameter is set
    $current_path = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );

    if ( rtrim( $current_path, '/' ) === '/import' && isset( $_GET['run_vendor_import'] ) && $_GET['run_vendor_import'] == '1' ) {
        
        // Ensure only administrators can execute the import
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access: You must be logged in as an Administrator to run this import.', 'Unauthorized', array( 'response' => 403 ) );
        }

        $csv_input   = ABSPATH . 'wp-content/uploads/vendors.csv';
        $csv_skipped = ABSPATH . 'wp-content/uploads/skipped_vendors.csv';

        import_dokan_vendors_from_csv( $csv_input, $csv_skipped );

        wp_die( 'Vendor import process complete! Check <code>wp-content/uploads/skipped_vendors.csv</code> for any records that failed.', 'Import Complete' );
    }
});


add_action( 'wp_head', function() {
    if ( is_front_page() ) {
        echo '<link rel="preload" as="image" href="' . esc_url( get_theme_file_uri( 'assets/img/bg.jpg' ) ) . '" fetchpriority="high">' . "\n";
    }
}, 1 );

/**
 * Inject Dynamic JSON-LD Schema Markup into <head>
 */
add_action( 'wp_head', 'add_custom_json_ld_schema' );

function add_custom_json_ld_schema() {
    
    // 1. HOMEPAGE SCHEMA
    if ( is_front_page() ) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => get_bloginfo( 'name' ),
            'url'      => home_url( '/' ),
            'logo'     => esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ),
        ];
        echo '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }

    // 2. CUSTOM PRODUCT TABLE TEMPLATE PAGES & CATEGORIES (CollectionPage Schema)
    elseif ( is_page_template( 'template-page-centered-fullwidth-product-table.php' ) || is_product_category() || is_page_template( 'template-homepage-a1geo.php' ) ) {
        
        $page_title = get_the_title();
        
        // Check if a custom hand-written excerpt exists first
        if ( has_excerpt() ) {
            $page_description = get_the_excerpt();
        } else {
            // Strip out raw shortcodes (like [wpdatatable...]) and HTML tags
            $raw_content      = get_the_content();
            $clean_content    = strip_shortcodes( $raw_content ); // Removes [wpdatatable ...]
            $page_description = wp_strip_all_tags( $clean_content );
        }

        // Clean up extra white spaces / line breaks
        $page_description = trim( preg_replace( '/\s+/', ' ', $page_description ) );

        // Fallback: If stripping shortcodes leaves the string empty, use a clean fallback
        if ( empty( $page_description ) ) {
            $page_description = sprintf( 'Browse and compare %s on A1 Geo Market.', $page_title );
        }

        // Truncate to a reasonable length for meta schema (e.g., 200 chars)
        if ( mb_strlen( $page_description ) > 200 ) {
            $page_description = mb_substr( $page_description, 0, 197 ) . '...';
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $page_title,
            'url'         => getCurrentUrl(),
            'description' => $page_description,
        ];

        echo '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }

    // 3. SINGLE PRODUCT PAGES (Product Schema)
    elseif ( is_product() ) {
        global $product;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            $product = wc_get_product( get_the_ID() );
        }

        if ( $product ) {
            $image_id  = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

            $schema = [
                '@context'    => 'https://schema.org',
                '@type'       => 'Product',
                'name'        => $product->get_name(),
                'image'       => $image_url,
                'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
                'sku'         => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
                'offers'      => [
                    '@type'         => 'Offer',
                    'price'         => $product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url'           => get_permalink( $product->get_id() ),
                ]
            ];
            echo '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
        }
    }
}

/**
 * Helper function to get the clean, canonical URL with trailing slash
 */
if ( ! function_exists( 'getCurrentUrl' ) ) {
    function getCurrentUrl() {
        // Try getting official canonical URL first
        $canonical_url = wp_get_canonical_url();

        if ( $canonical_url ) {
            return $canonical_url;
        }

        // Fallback using home_url and trailing slash
        global $wp;
        return user_trailingslashit( home_url( add_query_arg( [], $wp->request ) ) );
    }
}

/**
 * Update wpDataTables advanced_settings JSON field
 */
/*add_action( 'admin_init', 'update_wpdatatables_pagination_top' );

function update_wpdatatables_pagination_top() {
    global $wpdb;

    $table_id = NULL; // Set your wpDataTable ID here (or set to null to update ALL tables)
    $table_name = $wpdb->prefix . 'wpdatatables';

    if ( $table_id ) {
        // Fetch specific table
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, advanced_settings FROM {$table_name} WHERE id = %d", $table_id ) );
    } else {
        // Fetch all tables
        $rows = $wpdb->get_results( "SELECT id, advanced_settings FROM {$table_name}" );
    }

    if ( ! empty( $rows ) ) {
        foreach ( $rows as $row ) {
            // Decode existing JSON array, or fallback to empty array if null
            $settings = ! empty( $row->advanced_settings ) ? json_decode( $row->advanced_settings, true ) : [];

            if ( ! is_array( $settings ) ) {
                $settings = [];
            }

            // Set pagination_top to 1
            $settings['pagination_top'] = 1;

            // Re-encode JSON back to database
            $updated_json = json_encode( $settings );

            $wpdb->update(
                $table_name,
                [ 'advanced_settings' => $updated_json ],
                [ 'id' => $row->id ],
                [ '%s' ],
                [ '%d' ]
            );
        }
    }
}*/


/**
 * Remove Pingback Header Link (<link rel="pingback" href="...">)
 */
// 1. Unhook the pingback header output from wp_head
add_action( 'init', 'remove_pingback_header_link' );
function remove_pingback_header_link() {
    remove_action( 'wp_head', 'pingback_header' );
}

// 2. Remove the X-Pingback HTTP response header
add_filter( 'wp_headers', 'remove_x_pingback_header' );
function remove_x_pingback_header( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
}

// 3. Disable pingback processing completely
add_filter( 'xmlrpc_methods', 'disable_pingback_xmlrpc_methods' );
function disable_pingback_xmlrpc_methods( $methods ) {
    unset( $methods['pingback.ping'] );
    unset( $methods['pingback.extensions.getPingbacks'] );
    return $methods;
}

/**
 * Remove WordPress Shortlink Tag (<link rel='shortlink' href='...'>)
 */
// 1. Remove shortlink tag from HTML <head>
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

// 2. Remove shortlink HTTP header from server responses
remove_action( 'template_redirect', 'wp_shortlink_header', 11, 0 );



/**
 * Render a Clickable Category Toggle Menu excluding 'Uncategorized'.
 */
function custom_display_desktop_categories_menu() {
    // 1. Get the term ID for 'uncategorized' dynamically
    $uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
    $exclude_id = $uncategorized_term ? $uncategorized_term->term_id : '';

    // 2. Fetch top-level product categories
    $categories = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'exclude'    => array( $exclude_id ),
        'parent'     => 0, // Top-level categories only
    ) );

    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
        <div class="custom-category-dropdown d-none d-lg-block">
            <!-- Trigger Button -->
            <button type="button" id="category-toggle-btn" class="category-toggle-btn">
                <span><?php esc_html_e( 'Category', 'electro' ); ?></span>
                <i class="fa fa-bars" aria-hidden="true"></i>
            </button>

            <!-- Product Categories List -->
            <ul id="category-dropdown-list" class="category-dropdown-list" style="display: none;">
                <?php foreach ( $categories as $category ) : 
                    // Fetch subcategories for each parent
                    $sub_categories = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'parent'     => $category->term_id,
                    ) );
                    $has_children = ! empty( $sub_categories ) && ! is_wp_error( $sub_categories );
                ?>
                    <li class="category-item <?php echo $has_children ? 'has-children' : ''; ?>">
                        <a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
                            <?php echo esc_html( $category->name ); ?>
                        </a>

                        <?php if ( $has_children ) : ?>
                            <ul class="sub-category-list">
                                <?php foreach ( $sub_categories as $sub_cat ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url( get_term_link( $sub_cat ) ); ?>">
                                            <?php echo esc_html( $sub_cat->name ); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('category-toggle-btn');
            const categoryList = document.getElementById('category-dropdown-list');

            // Toggle dropdown visibility on clicking the Category button
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isHidden = categoryList.style.display === 'none';
                categoryList.style.display = isHidden ? 'block' : 'none';
            });

            // Prevent parent category items from navigating if they contain children
            const parentItems = categoryList.querySelectorAll('.has-children > a');
            parentItems.forEach(function(parentLink) {
                parentLink.addEventListener('click', function(e) {
                    e.preventDefault(); // Stop redirection on parent click
                });
            });

            // Close menu when clicking anywhere outside
            document.addEventListener('click', function(e) {
                if (!toggleBtn.contains(e.target) && !categoryList.contains(e.target)) {
                    categoryList.style.display = 'none';
                }
            });
        });
        </script>
    <?php endif;
}
//add_action( 'electro_navbar_v11', 'custom_display_desktop_categories_menu', 15 );


/**
 * Output a visually hidden image at the top of the body for Google crawlers.
 */
add_action( 'wp_body_open', function() {
    $upload_dir       = wp_upload_dir();
    $custom_image_url = $upload_dir['baseurl'] . '/2026/08/geophysical_market.png';
    $destination_url  = home_url( '/' );
    ?>
    <div class="google-crawler-image" aria-hidden="true" style="position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important;">
        <a href="<?php echo esc_url( $destination_url ); ?>">
            <img src="<?php echo esc_url( $custom_image_url ); ?>" alt="Geophysical market" width="1200" height="630" />
        </a>
    </div>
    <?php
} );


/**
 * Helper function to ensure the dedicated external click log table exists (without prefix).
 */
function a1geo_ensure_external_click_log_table() {
    global $wpdb;

    $table_name = 'external_click_logs';

    // Fast check to see if table already exists in DB
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
        return $table_name;
    }

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        target_url text NOT NULL,
        source_url text NOT NULL,
        ip_address varchar(45) NOT NULL,
        user_agent text NOT NULL,
        clicked_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    return $table_name;
}

/**
 * Register REST API Route
 */
function a1geo_register_external_click_rest_route() {
    register_rest_route( 'a1geo/v1', '/track-external-click', array(
        'methods'             => 'POST',
        'callback'            => 'a1geo_handle_external_click_rest',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'a1geo_register_external_click_rest_route' );

/**
 * Enqueue tracker variables for JS
 */
function a1geo_enqueue_tracker_scripts() {
    wp_localize_script( 'a1geo-vendor-registration', 'a1geo_api_obj', array(
        'endpoint' => rest_url( 'a1geo/v1/track-external-click' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ));
}
add_action( 'wp_enqueue_scripts', 'a1geo_enqueue_tracker_scripts', 201 );

/**
 * REST API Callback
 */
function a1geo_handle_external_click_rest( WP_REST_Request $request ) {
    $nonce = $request->get_header( 'x_wp_nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_REST_Response( array( 'error' => 'Invalid security nonce' ), 403 );
    }

    $target_url = esc_url_raw( $request->get_param( 'target_url' ) );
    $source_url = esc_url_raw( $request->get_param( 'source_url' ) );

    if ( empty( $target_url ) ) {
        return new WP_REST_Response( array( 'error' => 'Missing target URL' ), 400 );
    }

    global $wpdb;

    // Get table name without prefix
    $table_name = a1geo_ensure_external_click_log_table();

    $current_user_id = get_current_user_id();

    // IP Extraction
    $ip_address = '0.0.0.0';
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip_array   = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $ip_address = trim( $ip_array[0] );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    $ip_address = sanitize_text_field( $ip_address );
    if ( str_starts_with( $ip_address, '::ffff:' ) ) {
        $ip_address = substr( $ip_address, 7 );
    }

    $user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 500 ) : 'Unknown';

    // Insert record directly into external_click_logs
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'user_id'    => $current_user_id,
            'target_url' => $target_url,
            'source_url' => $source_url,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );

    if ( $inserted ) {
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Click logged' ), 200 );
    }

    return new WP_REST_Response( array( 'error' => 'Database insertion failed' ), 500 );
}

function a1geo_enqueue_external_click_tracker() {
    // Path to your script file relative to theme or plugin directory
    wp_enqueue_script(
        'a1geo-external-tracker',
        get_stylesheet_directory_uri() . '/assets/js/external-tracker.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );

    // Pass REST endpoint and Nonce object to the enqueued JS file
    wp_localize_script( 'a1geo-external-tracker', 'a1geo_api_obj', array(
        'endpoint' => rest_url( 'a1geo/v1/track-external-click' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ));
}
add_action( 'wp_enqueue_scripts', 'a1geo_enqueue_external_click_tracker' );

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

