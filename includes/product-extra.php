<?php

/**
 * Retrieve all product tags for a given WooCommerce product.
 *
 * @param int|WC_Product $product  The Product ID or WooCommerce Product object.
 * @return WP_Term[]|false         Array of WP_Term objects on success, false on failure.
 */
function get_all_tags_of_product( $product_id ) {
    // If a numerical ID was passed, convert it to the WooCommerce product object
    if ( !is_numeric( $product_id ) ) {
        return false;
    } 

    // Returns an array of WP_Term objects assigned to the 'product_tag' taxonomy
    $product_tags = get_the_terms( $product_id, 'product_tag' );
    return $product_tags;
}

/**
 * Log the precise second a product changes status to published.
 */
add_action( 'transition_post_status', 'log_exact_product_publish_transition', 10, 3 );

function log_exact_product_publish_transition( $new_status, $old_status, $post ) {
    // Only target WooCommerce products transitioning to 'publish'
    if ( 'product' !== $post->post_type || 'publish' !== $new_status ) {
        return;
    }

    // If it was already published, do not overwrite the original activation timestamp
    if ( 'publish' === $old_status ) {
        return;
    }

    // Save the exact approval/live moment to a custom meta key
    update_post_meta( $post->ID, '_exact_product_published_at', current_time( 'mysql' ) );
}

/**
 * Retrieve the exact published transition date logged by log_exact_product_publish_transition().
 * Falls back to the native WooCommerce product creation date if no transition log exists.
 *
 * @param int|WC_Product $product  The Product ID or WooCommerce Product object.
 * @param string         $format   Optional. PHP Date syntax mapping (Defaults to MySQL format).
 * @return string|false            The formatted timestamp string, or false on failure.
 */
function get_exact_product_published_date( $product, $format = 'Y-m-d H:i:s' ) {
    // 1. Resolve the Product ID
    $product_id = is_numeric( $product ) ? intval( $product ) : ( $product instanceof WC_Product ? $product->get_id() : 0 );
    
    if ( ! $product_id ) {
        return false;
    }

    // 2. Fetch directly from our custom transition log meta key
    $raw_date = get_post_meta( $product_id, '_exact_product_published_at', true );
    
    // 3. Fallback: If no custom log exists, default to the native WC creation date
    if ( empty( $raw_date ) ) {
        $wc_product = is_numeric( $product ) ? wc_get_product( $product_id ) : $product;
        if ( $wc_product ) {
            $date_created = $wc_product->get_date_created();
            if ( $date_created ) {
                $raw_date = $date_created->date( 'Y-m-d H:i:s' ); 
            }
        }
    }

    // 4. Format and return the final string output
    if ( ! empty( $raw_date ) ) {
        if ( 'Y-m-d H:i:s' !== $format ) {
            return date( $format, strtotime( $raw_date ) );
        }
        return $raw_date;
    }

    return false;
}

/**
 * Automatically log the timestamp when a tag is assigned to a WooCommerce product.
 *
 * @param int    $object_id  The ID of the object (Product ID).
 * @param array  $terms      An array of term IDs or slugs.
 * @param array  $tt_ids     An array of term taxonomy IDs.
 * @param string $taxonomy   Taxonomy slug (e.g., 'product_tag').
 * @param bool   $append     Whether terms are appended or overwritten.
 */
add_action( 'set_object_terms', 'log_product_tag_assignment_timestamp', 10, 5 );

function log_product_tag_assignment_timestamp( $object_id, $terms, $tt_ids, $taxonomy, $append ) {
    // We only care about WooCommerce product tags
    if ( 'product_tag' !== $taxonomy ) {
        return;
    }

    // Get the product object to confirm it is a valid product CPT
    if ( 'product' !== get_post_type( $object_id ) ) {
        return;
    }

    // Retrieve the existing timestamp logs array or initialize an empty one
    $log = get_post_meta( $object_id, '_product_tag_assignment_dates', true );
    if ( ! is_array( $log ) ) {
        $log = [];
    }

    $current_time = current_time( 'mysql' ); // Matches system server locale
    $has_changes  = false;

    // Standardize term formats to actual term IDs
    foreach ( $tt_ids as $tt_id ) {
        // If we don't have a record of this tag being added yet, log it
        if ( ! isset( $log[ $tt_id ] ) ) {
            $log[ $tt_id ] = $current_time;
            $has_changes   = true;
        }
    }

    // Clean up our metadata: remove logs for tags that were unassigned/deleted
    foreach ( $log as $logged_tt_id => $timestamp ) {
        if ( ! in_array( $logged_tt_id, $tt_ids ) ) {
            unset( $log[ $logged_tt_id ] );
            $has_changes = true;
        }
    }

    // Save the array back to the product meta if changes occurred
    if ( $has_changes ) {
        update_post_meta( $object_id, '_product_tag_assignment_dates', $log );
    }
}

/**
 * Helper function to retrieve the assignment date of a tag on a specific product.
 *
 * @param int $product_id The WooCommerce Product ID.
 * @param int $tag_id     The Term ID of the product tag.
 * @return string|false   The MySQL formatted timestamp string, or false if not tracked.
 */
function get_product_tag_assigned_time( $product_id, $tag_id ) {
    // Get the Term Taxonomy ID from the Term ID
    $term = get_term( $tag_id, 'product_tag' );
    if ( is_wp_error( $term ) || ! $term ) {
        return false;
    }

    $tt_id = $term->term_taxonomy_id;
    $log   = get_post_meta( $product_id, '_product_tag_assignment_dates', true );

    if ( is_array( $log ) && isset( $log[ $tt_id ] ) ) {
        return $log[ $tt_id ]; // Returns "YYYY-MM-DD HH:MM:SS"
    }

    return false;
}
?>