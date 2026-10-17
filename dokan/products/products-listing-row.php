<?php
use WC_Meta_Data;
/**
 * Template Name: Product listing row template
 *
 * @since 2.9.0
 *
 * @package Dokan
 *
 * @var WP_Post $post Product post object
 * @var WC_Product $product Product object
 * @var string $tr_class Table row class
 * @var string $row_actions Row actions HTML
 * @var string $product_type Product type
 * @var string $product_status Product status
 */


/**
 * Filter the product image attributes
 *
 * @since 3.11
 *
 * @param array $img_kses Image attributes
 */
$img_kses = apply_filters(
    'dokan_product_image_attributes',
    [
        'img' => [
            'alt'         => [],
            'class'       => [],
            'height'      => [],
            'src'         => [],
            'width'       => [],
            'srcset'      => [],
            'data-srcset' => [],
            'data-src'    => [],
        ],
    ]
);

/**
 * Filter the row actions
 *
 * @since 2.9.13
 *
 * @param array $row_actions Row actions HTML
 */
$row_actions_kses = apply_filters(
    'dokan_row_actions_kses',
    [
        'span' => [
            'class' => [],
        ],
        'a' => [
            'href'    => [],
            'onclick' => [],
        ],
    ]
);



/**
 * Filter the price HTML
 *
 * @since 2.9.13
 *
 * @param array $price_kses Price HTML
 */
$price_kses = apply_filters(
    'dokan_price_kses',
    [
        'span' => [
            'class' => [],
        ],
    ]
);
?>
<tr class="<?php echo esc_attr( $tr_class ); ?>">
    <!-- Removed check column -->    
    <!-- Added calculated id-->
    <td data-title="<?php esc_attr_e( 'Id', 'dokan-lite' ); ?>" class="column-thumb">
        <?php 
            $author_id  = get_post_field( 'post_author', $post->ID);
            $vendor     = dokan()->vendor->get($author_id);
            if (str_starts_with($vendor->get_phone(), '11')) {
                echo $post->ID * 10 + 11;
            } else if (str_starts_with($vendor->get_phone(), '22')) {
                echo $post->ID * 10 + 22;    
            } else {
                echo $post->ID * 10;
            }
         ?>
    </td>
    <td data-title="<?php esc_attr_e( 'Image', 'dokan-lite' ); ?>" class="column-thumb">
        <?php if ( current_user_can( 'dokan_edit_product' ) ) { ?>
            <a href="<?php echo esc_url( dokan_edit_product_url( $post->ID ) ); ?>"><?php echo wp_kses( $product->get_image( 'thumbnail' ), $img_kses ); ?></a>
        <?php } else { ?>
            <?php echo wp_kses( $product->get_image( 'thumbnail' ), $img_kses ); ?>
        <?php } ?>
        <?php
            /**
             * Fire an action to add extra content after product image column in product listing table
             *
             * @since 3.11.5
             *
             * @param \WC_Product $product Current product.
             */
            do_action( 'dokan_product_list_table_after_column_content_image', $product );
        ?>
    </td>
    <td data-title="<?php esc_attr_e( 'Name', 'dokan-lite' ); ?>" class="column-primary">
        <?php if ( current_user_can( 'dokan_edit_product' ) ) { ?>
            <strong>
                <a href="<?php echo esc_url( dokan_edit_product_url( $post->ID ) ); ?>">
                    <?php echo esc_html( $product->get_title() ); ?>
                </a>
            </strong>
        <?php } else { ?>
            <strong><a href=""><?php echo esc_html( $product->get_title() ); ?></a></strong>
        <?php } ?>

        <?php
            /**
             * Fire an action to add extra content after product name column in product listing table
             *
             * @since 3.11.5
             *
             * @param \WC_Product $product Current product.
             */
            do_action( 'dokan_product_list_table_after_column_content_name', $product );
        ?>

        <?php if ( ! empty( $row_actions ) ) { ?>
            <div class="row-actions">
                <?php echo wp_kses( $row_actions, $row_actions_kses ); ?>
            </div>
        <?php } ?>

        <button type="button" class="toggle-row"></button>
        <?php
            /**
             * Fire an action to add extra content after product name column in product listing table
             *
             * @since 3.11.5
             *
             * @param \WC_Product $product Current product.
             */
            do_action( 'dokan_product_list_table_after_column_content_name_row_actions', $product );
        ?>
    </td>
    <td class="post-status" data-title="<?php esc_attr_e( 'Status', 'dokan-lite' ); ?>">
        <label class="dokan-label <?php echo esc_attr( dokan_get_post_status_label_class( $post->post_status ) ); ?>"><?php echo esc_html( dokan_get_post_status( $post->post_status ) ); ?></label>

        <?php
            /**
             * Fire an action to add extra content after product status column in product listing table
             *
             * @since 3.11.5
             *
             * @param \WC_Product $product Current product.
             *
             * @return void
             */
            do_action( 'dokan_product_list_table_after_column_content_status', $product );
        ?>
    </td>

    <?php
        /**
         * Fire an action to add extra content after product status column in product listing table
         *
         * @since 2.9.4
         *
         * @param \WP_Post $post Current post.
         * @param \WC_Product $product Current product.
         * @param string $tr_class Table row class.
         * @param string $row_actions Row actions HTML.
         */
        do_action( 'dokan_product_list_table_after_status_table_data', $post, $product, $tr_class, $row_actions );
    ?>
    <!--- ZZZZ Removed SKU, Stock - 03/06/2025-->
    
    <!-- ZZZZ Add Category, Short description -->
    <td data-title="<?php esc_attr_e( 'Category', 'dokan-lite' ); ?>">
        <?php       
        $terms = get_the_terms( $product->ID, 'product_cat');
        $product_cat_name = '';
        foreach ($terms as $term) {
            if ($term->parent != null) {
                $product_cat_name = $term->name;    
                break;
            }
        }        
        echo wp_kses_post( $product_cat_name);        
        ?>
    </td>
    <td data-title="<?php esc_attr_e( 'Short Description', 'dokan-lite' ); ?>">
        <?php               
        if ( $product->get_short_description() ) {
            echo wp_kses_post( $product->get_short_description() );
        } else {
            echo '<span class="na">&ndash;</span>';
        }      
        ?>
    </td>
    <td data-title="<?php esc_attr_e( 'Price', 'dokan-lite' ); ?>">
        <?php
        // ZZZZZ - Use ACF price field instead of original Price field
        $price = '';
        //$myfile = fopen('log_rest.txt', 'a');
        //$data = '';
        $all_data = $product->get_meta_data();
        foreach (array_keys($all_data) as $meta_key) {
            //$tmp = print_r($meta_key, true) . '\n';
            if ($all_data[$meta_key] instanceof WC_Meta_Data) {
                //$tmp = print_r($all_data[$meta_key]->__get('key'), true) . '\n';
                // Consider custom meta key ends with _8 is for price
                $key = $all_data[$meta_key]->__get('key');                
                if (str_ends_with($key, '_8') 
                    && (str_starts_with($key, 'ins') || str_starts_with($key, 'ser') || str_starts_with($key, 'acc')) && empty($price)){
                    $price = print_r($all_data[$meta_key]->__get('value'), true);
                }
            }           
            //$data = $data . '\n ** ' . $tmp;     
        }
        //fwrite($myfile, $data . '\n');			
        //fclose($myfile);        
        
        if ( $product->get_data() ) {
            echo wp_kses_post( $price);
        } else {
            echo '<span class="na">&ndash;</span>';
        }
        
        /*if ( $product->get_price_html() ) {
            echo wp_kses_post( $product->get_price_html() );
        } else {
            echo '<span class="na">&ndash;</span>';
        }*/

        /**
         * Fire an action to add extra content after product price column in product listing table
         *
         * @since 3.11.5
         *
         * @param \WC_Product $product Current product.
         */
        do_action( 'dokan_product_list_table_after_column_content_price', $product );
        ?>
    </td>
    <!--ZZZZZ Removed Earning, Type-->                                            
    
    <td data-title="<?php esc_attr_e( 'Views', 'dokan-lite' ); ?>">
        <?php
            echo (int) get_post_meta( $post->ID, 'pageview', true );

            /**
             * Fire an action to add extra content after product views column in product listing table
             *
             * @since 3.11.5
             *
             * @param \WC_Product $product Current product.
             */
            do_action( 'dokan_product_list_table_after_column_content_views', $product );
        ?>
    </td>
    <td class="post-date" data-title="<?php esc_attr_e( 'Date', 'dokan-lite' ); ?>">
        <?php

        if ( '0000-00-00 00:00:00' === $post->post_date ) {
            $post_published_date = __( 'Unpublished', 'dokan-lite' );
            $human_readable_time = __( 'Unpublished', 'dokan-lite' );
            $time_diff = 0;
        } else {
            // get current time
            $current_time = dokan_current_datetime();
            // set timezone to gmt
            $gmt_time = $current_time->setTimezone( new DateTimeZone( 'UTC' ) );
            // read post gmt time
            $post_time_gmt = $gmt_time->modify( $post->post_date_gmt );

            $format = apply_filters( 'dokan_date_time_format', wc_date_format() . ' ' . wc_time_format() );
            // currently dokan_format_date doesn't support time format, will update this soon,
            // right now we need to send $format from our end.
            // if you need date only remove $format from dokan_format_date function parameter
            $human_readable_time = dokan_format_date( $post_time_gmt->getTimestamp(), $format );
            $post_published_date = apply_filters( 'post_date_column_time', dokan_format_date( $post_time_gmt->getTimestamp() ), $post, 'date', 'all' );

            // get human readable time
            $time_diff = $current_time->getTimestamp() - $post_time_gmt->getTimestamp();
            if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
                /* translators: %s: time difference */
                $human_readable_time = sprintf( __( '%s ago', 'dokan-lite' ), human_time_diff( $post_time_gmt->getTimestamp() ) );
            }
        }

        echo '<abbr title="' . esc_attr( $human_readable_time ) . '">' . esc_html( $post_published_date ) . '</abbr>';
        echo '<div class="status">';

        if ( 'publish' === $post->post_status ) {
            esc_html_e( 'Published', 'dokan-lite' );
        } elseif ( 'future' === $post->post_status ) {
            if ( $time_diff > 0 ) {
                echo '<strong class="attention">' . esc_html__( 'Missed schedule', 'dokan-lite' ) . '</strong>';
            } else {
                esc_html_e( 'Scheduled', 'dokan-lite' );
            }
        } else {
            esc_html_e( 'Last Modified', 'dokan-lite' );
        }

        /**
         * Fire an action to add extra content after product date column in product listing table
         *
         * @since 3.11.5
         *
         * @param \WC_Product $product Current product.
         */
        do_action( 'dokan_product_list_table_after_column_content_date', $product );
        ?>
        </div>
    </td>
</tr>
