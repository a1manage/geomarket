<?php

// Filter data before saving, filter will be run in class-acf-for-dokan-pro.php
add_filter( 'zz_custom_acf_fields', 'filter_dokan_acf_fields_by_category', 10, 1 );
/**
 * Clean up ACF fields that do not belong to the selected product category in Dokan.
 * * @param int $errors
  */
function filter_dokan_acf_fields_by_category( $postdata ) {
    // 1. Get the selected categories from the submitted data
    // Dokan usually passes categories in 'chosen_product_cat' or 'product_cat'
    $selected_cats = isset( $postdata['chosen_product_cat'] ) ? (array) $postdata['chosen_product_cat'] : [];       

    if ( empty( $selected_cats ) && isset( $postdata['product_cat'] ) ) {
        $selected_cats = (array) $postdata['product_cat'];
    }

    if ( empty( $selected_cats ) ) {
        return; // No category selected, nothing to filter
    }

    // 2. Get all ACF field groups
    $groups = acf_get_field_groups();
    if ( empty( $groups ) ) {
        return;
    }

    $allowed_fields = [];
  
    // 3. Identify which fields are actually allowed for the selected categories
    foreach ( $groups as $group ) {
        $location = $group['location'];
        
        $group_matches = false;

        foreach ( $location as $rule_group ) {
            foreach ( $rule_group as $rule ) {
                // Check if the rule is targetting product categories
                if ( $rule['param'] === 'post_taxonomy') {                    
                    if (strpos( $rule['value'], 'product_cat:' ) === 0 ) {                        
                        $rule_cat_slug = str_replace( 'product_cat:', '', $rule['value'] );                        
                        $term = get_term_by( 'slug', sanitize_title( $rule_cat_slug ), 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) {                        
                            $rule_cat_id = (int) $term->term_id;                        
                            if ( $rule['operator'] === '==' && in_array( $rule_cat_id, $selected_cats ) ) {
                                $group_matches = true;
                                break 2;
                            }
                        }                        
                    }
                }                
                // If your rule maps directly to forms or post types globally, handle accordingly
            }
        }

        // If the field group matches the selected category, harvest its field keys/names
        if ( $group_matches ) {
            $fields = acf_get_fields( $group['key'] );
            if ( $fields ) {
                foreach ( $fields as $field ) {
                    $allowed_fields[] = $field['name'];
                }
            }
        }
    }
    
    // Allow 2 common fields
    $allowed_fields[] = 'datasheet_link';
    $allowed_fields[] = 'original_product_link';    

    // 4. Look at the $postdata data to see what ACF fields Dokan is trying to save
    // ACF fields in Dokan frontend typically look like $postdata['acf']['field_key'] or directly via standard post data
    if ( isset( $postdata['acf'] ) && is_array( $postdata['acf'] ) ) {
        foreach ( $postdata['acf'] as $field_key => $value ) {
            $field = acf_get_field( $field_key );            
            if ( $field && ! in_array( $field['name'], $allowed_fields ) ) {
                // Remove it from the POST payload so ACF doesn't process it
                unset( $postdata['acf'][$field_key] );
            }
        }
    }
    return $postdata;
}

function ts_filter_dokan_acf_fields_by_category( $product_id, $postdata  ) {
    // use $_POST data instead of $postdata as it may be filtered 
    // and doesn't contain data which will be used for deleting unnecessary data later
    // 1. Get the selected categories from the submitted data
    // Dokan usually passes categories in 'chosen_product_cat' or 'product_cat'    
    $selected_cats = isset( $_POST['chosen_product_cat'] ) ? (array) $_POST['chosen_product_cat'] : [];    

    if ( empty( $selected_cats ) && isset( $_POST['product_cat'] ) ) {
        $selected_cats = (array) $_POST['product_cat'];
    }

    if ( empty( $selected_cats ) ) {
        return; // No category selected, nothing to filter
    }

    // 2. Get all ACF field groups
    $groups = acf_get_field_groups();
    if ( empty( $groups ) ) {
        return;
    }

    $allowed_fields = [];
  
    // 3. Identify which fields are actually allowed for the selected categories
    foreach ( $groups as $group ) {
        $location = $group['location'];
        
        $group_matches = false;

        foreach ( $location as $rule_group ) {
            foreach ( $rule_group as $rule ) {                       
                
                // Check if the rule is targetting product categories
                if ( $rule['param'] === 'post_taxonomy') {
                    
                    if (strpos( $rule['value'], 'product_cat:' ) === 0 ) {                    
                        $rule_cat_slug = str_replace( 'product_cat:', '', $rule['value'] );                    
                        $term = get_term_by( 'slug', sanitize_title( $rule_cat_slug ), 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) {                            
                            $rule_cat_id = (int) $term->term_id;                            
                            if ( $rule['operator'] === '==' && in_array( $rule_cat_id, $selected_cats ) ) {
                                $group_matches = true;
                                break 2;
                            }
                        }                        
                    }
                }                
                // If your rule maps directly to forms or post types globally, handle accordingly
            }
        }

        // If the field group matches the selected category, harvest its field keys/names
        if ( $group_matches ) {
            $fields = acf_get_fields( $group['key'] );
            if ( $fields ) {
                foreach ( $fields as $field ) {
                    $allowed_fields[] = $field['name'];
                }
            }
        }
    }
    
    // Allow 2 common fields
    $allowed_fields[] = 'datasheet_link';
    $allowed_fields[] = 'original_product_link';    

    // 4. Look at the $_POST data to see what ACF fields Dokan is trying to save
    // ACF fields in Dokan frontend typically look like $_POST['acf']['field_key'] or directly via standard post data
    if ( isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ) {
        foreach ( $_POST['acf'] as $field_key => $value ) {
            $field = acf_get_field( $field_key );            
            if ( $field && ! in_array( $field['name'], $allowed_fields ) ) {
                // Remove it from the POST payload so ACF doesn't process it
                unset( $_POST['acf'][$field_key] );
                // Clean up any stale data already in the database for this product
                delete_field( $field_key, $product_id );
            }
        }
    }
}

// Hook into both product creation and updates in the Dokan Vendor Dashboard
add_action( 'dokan_new_product_added', 'ts_filter_dokan_acf_fields_by_category', 12, 2 );
add_action( 'dokan_product_updated', 'ts_filter_dokan_acf_fields_by_category', 12, 2 );

