<?php
// Hook into standard posts loop and custom post types loop
add_filter( 'post_link', 'custom_spectra_meta_permalink_restricted', 10, 3 );
add_filter( 'post_type_link', 'custom_spectra_meta_permalink_restricted', 10, 3 );

/**
 * Filter post links to return an external URL stored in post meta,
 * bypassing administrators, shop managers, and vendors.
 *
 * @param string  $permalink The original post URL.
 * @param WP_Post $post      The current post object.
 * @param bool    $leavename Whether to keep the post name.
 * @return string Modified or original fallback URL.
 */
function custom_spectra_meta_permalink_restricted( $permalink, $post, $leavename ) {
    // 1. Prevent execution if the post object is missing or corrupted
    if ( ! is_a( $post, 'WP_Post' ) ) {
        return $permalink;
    }

    // 2. Check if a user is logged in
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        
        // Define all internal roles that should see standard site links
        $restricted_roles = array( 'administrator', 'shop_manager' ); // Exclude , 'vendor', 'seller'

        // If the current user has any of the restricted roles, return the normal post link
        if ( array_intersect( $restricted_roles, (array) $current_user->roles ) ) {
            return $permalink;
        }
    }

    // 3. For all other visitors, check for the custom meta link layout
    $custom_link = get_post_meta( $post->ID, 'original_product_link', true );

    // Verify that the custom meta field is not blank and contains a valid URL structure
    if ( ! empty( $custom_link ) && filter_var( $custom_link, FILTER_VALIDATE_URL ) ) {
        return esc_url( $custom_link );
    }

    // Default fallback to standard post URL
    return $permalink;
}
