<?php

use WeDevs\Dokan\ProductCategory\Helper;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
global $post;

$from_shortcode = false;
$new_product    = false;

if (
    ! isset( $post->ID )
    && (
        ! isset( $_GET['_dokan_edit_product_nonce'] ) ||
        ! wp_verify_nonce( sanitize_key( $_GET['_dokan_edit_product_nonce'] ), 'dokan_edit_product_nonce' ) ||
        ! isset( $_GET['product_id'] )
    )
) {
    wp_die( esc_html__( 'Access Denied, No product found', 'dokan-lite' ) );
}

if ( isset( $post->ID ) && $post->ID && 'product' === $post->post_type ) {
    $post_id      = $post->ID;
    $post_title   = $post->post_title;
    $post_content = $post->post_content;
    $post_excerpt = $post->post_excerpt;
    $post_status  = $post->post_status;
    $product      = wc_get_product( $post_id );
}

if ( isset( $_GET['product_id'] ) ) {
    $post_id = intval( $_GET['product_id'] );
    $post    = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        dokan_get_template_part(
            'global/dokan-error',
            '',
            array(
                'deleted' => false,
                'message' => __( 'This product is no longer available', 'dokan-lite' )
            )
        );
        return;
    }

    $post_status    = $post->post_status;
    $auto_draft     = 'auto-draft' === $post_status;
    $post_title     = $auto_draft ? '' : $post->post_title;
    $post_content   = $auto_draft ? '' : $post->post_content;
    $post_excerpt   = $post->post_excerpt;
    $product        = wc_get_product( $post_id );
    $from_shortcode = true;
}

if ( isset( $_GET['product_id'] ) && 0 === absint( $_GET['product_id'] ) ) {
    $post_id      = intval( $_GET['product_id'] );
    $post_title   = '';
    $post_content = '';
    $post_excerpt = '';
    $post_status  = 'auto-draft';
    $product      = new WC_Product( $post_id );

    $product->set_name( $post_title );
    $product->set_status( $post_status );
    $post_id = $product->save();
    wp_update_post(
        [
            'ID'          => $post_id,
            'post_author' => dokan_get_current_user_id(),
        ]
    );

    $post           = get_post( $post_id );
    $from_shortcode = true;
    $new_product    = true;
}

if ( $new_product && ! current_user_can( 'dokan_edit_product' ) ) {
	wp_die( esc_html__( 'Access Denied', 'dokan-lite' ) );
}

if ( ! $new_product && ! dokan_is_product_author( $post_id ) ) {
    wp_die( esc_html__( 'Access Denied', 'dokan-lite' ) );
}

$_regular_price         = get_post_meta( $post_id, '_regular_price', true );
$_sale_price            = get_post_meta( $post_id, '_sale_price', true );
$is_discount            = ! empty( $_sale_price );
$_sale_price_dates_from = get_post_meta( $post_id, '_sale_price_dates_from', true );
$_sale_price_dates_to   = get_post_meta( $post_id, '_sale_price_dates_to', true );

$_sale_price_dates_from = ! empty( $_sale_price_dates_from ) ? date_i18n( 'Y-m-d', $_sale_price_dates_from ) : '';
$_sale_price_dates_to   = ! empty( $_sale_price_dates_to ) ? date_i18n( 'Y-m-d', $_sale_price_dates_to ) : '';
$show_schedule          = false;

if ( ! empty( $_sale_price_dates_from ) && ! empty( $_sale_price_dates_to ) ) {
    $show_schedule = true;
}

$_featured        = get_post_meta( $post_id, '_featured', true );
$terms            = wp_get_object_terms( $post_id, 'product_type' );
$product_type     = ( ! empty( $terms ) ) ? sanitize_title( current( $terms )->name ) : 'simple';
$variations_class = ( $product_type === 'simple' ) ? 'dokan-hide' : '';
$_visibility      = ( version_compare( WC_VERSION, '2.7', '>' ) ) ? $product->get_catalog_visibility() : get_post_meta( $post_id, '_visibility', true );

if ( ! $from_shortcode ) {
    get_header();
}

if ( ! empty( $_GET['errors'] ) ) {
    dokan()->dashboard->templates->products->set_errors( array_map( 'sanitize_text_field', wp_unslash( $_GET['errors'] ) ) );
}

/**
 * Action hook to fire before dokan dashboard wrap
 *
 *  @since 2.4
 */
do_action( 'dokan_dashboard_wrap_before', $post, $post_id );
?>

<?php do_action( 'dokan_dashboard_wrap_start' ); ?>

    <div class="dokan-dashboard-wrap">

        <?php
        /**
         * Action took to fire before dashboard content.
         *
         *  @hooked get_dashboard_side_navigation
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_before' );

        /**
         * Action hook to fire before product content area.
         *
         * @since 2.4
         */
        do_action( 'dokan_before_product_content_area' );
        ?>

        <div class="dokan-dashboard-content dokan-product-edit">

            <?php
            /**
             * Action hook to fire inside product content area before
             *
             *  @since 2.4
             */
            do_action( 'dokan_product_content_inside_area_before' );

            if ( $new_product ) {
                do_action( 'dokan_new_product_before_product_area' );
            }
            ?>

            <header class="dokan-dashboard-header dokan-clearfix">
                <h1 class="entry-title">
                    <?php
                    if ( $new_product || 'auto-draft' === $post->post_status ) {
                        esc_html_e( 'Add New Product', 'dokan-lite' );
                    } else {
                        esc_html_e( 'Edit Product', 'dokan-lite' );
                    }

                    /**
                     * Render the contents before product edit status label.
                     *
                     * @since 3.13.1
                     *
                     * @param \WC_Product $product
                     */
                    do_action( 'dokan_before_product_edit_status_label', $product );
                    ?>

                    <span class="dokan-label <?php echo esc_attr( dokan_get_post_status_label_class( $post->post_status ) ); ?> dokan-product-status-label">
                        <?php echo esc_html( dokan_get_post_status( $post->post_status ) ); ?>
                    </span>

                    <?php
                    /**
                     * Render the contents after product edit status label.
                     *
                     * @since 3.13.1
                     *
                     * @param \WC_Product $product
                     */
                    do_action( 'dokan_after_product_edit_status_label', $product );
                    ?>

                    <?php if ( $post->post_status === 'publish' ) : ?>
                        <span class="dokan-right">
                            <a class="dokan-btn dokan-btn-theme dokan-btn-sm" href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank"><?php esc_html_e( 'View Product', 'dokan-lite' ); ?></a>
                        </span>
                    <?php endif; ?>

                    <?php if ( $_visibility === 'hidden' ) : ?>
                        <span class="dokan-right dokan-label dokan-label-default dokan-product-hidden-label"><i class="far fa-eye-slash"></i> <?php esc_html_e( 'Hidden', 'dokan-lite' ); ?></span>
                    <?php endif; ?>

                    <?php
                    /**
                     * Render the contents after view product button.
                     *
                     * @since 3.13.1
                     *
                     * @param \WC_Product $product
                     */
                    do_action( 'dokan_edit_product_after_view_product_button', $product );
                    ?>
                </h1>
            </header><!-- .entry-header -->

            <div class="product-edit-new-container product-edit-container">
                <?php if ( dokan()->dashboard->templates->products->has_errors() ) : ?>
                    <div class="dokan-alert dokan-alert-danger">
                        <a class="dokan-close" data-dismiss="alert">&times;</a>

                        <?php foreach ( dokan()->dashboard->templates->products->get_errors() as $error ) : ?>
                            <strong><?php esc_html_e( 'Error!', 'dokan-lite' ); ?></strong> <?php echo esc_html( $error ); ?>.<br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'success' ) : ?>
                    <div class="dokan-message">
                        <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                        <strong><?php esc_html_e( 'Success!', 'dokan-lite' ); ?></strong> <?php esc_html_e( 'The product has been saved successfully.', 'dokan-lite' ); ?>
                        
                        <?php 
                            /// ZZZZ - Change the condition to hide the view product link 
                            //if ( $post->post_status === 'publish' ) :
                            if ( is_null($post->post_status) ) : ?>
                            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank"><?php esc_html_e( 'View Product &rarr;', 'dokan-lite' ); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( apply_filters( 'dokan_can_post', true ) ) : ?>
                    <?php if ( dokan_is_seller_enabled( get_current_user_id() ) ) : ?>
                        <form class="dokan-product-edit-form" role="form" method="post" id="post">

                            <?php do_action( 'dokan_product_data_panel_tabs' ); ?>
                            <?php do_action( 'dokan_product_edit_before_main' ); ?>

                            <div class="dokan-form-top-area">

                                <div class="content-half-part dokan-product-meta">

                                    <div id="dokan-product-title-area" class="dokan-form-group">
                                        <input type="hidden" name="dokan_product_id" id="dokan-edit-product-id" value="<?php echo esc_attr( $post_id ); ?>"/>
                                        <p style="font-weight: bold;font-size:9pt;">After adding your new product, go to our website’s homepage to find it and check that all the details are correct. You can edit your product information anytime if you need to make changes. If you have any issues, look at other products in the same category for reference or email us, we’re happy to help.</p>        
                                        <label for="post_title" class="form-label"><?php esc_html_e( 'Name', 'dokan-lite' ); ?></label>
                                        <p class="short-introduction">This name is for your reference only and won’t appear on the website. The displayed name should be included in the Short Description section below.</p>
                                        <?php
                                        dokan_post_input_box(
                                            $post_id,
                                            'post_title',
                                            [
                                                'placeholder' => __( 'Product name..', 'dokan-lite' ),
                                                'value'       => $post_title,
                                            ]
                                        );
                                        ?>
                                        <div class="dokan-product-title-alert dokan-hide">
                                            <?php esc_html_e( 'Please enter product title!', 'dokan-lite' ); ?>
                                        </div>

                                        <div id="edit-slug-box" class="hide-if-no-js"></div>
                                        <?php 
                                            // ZZZZZ - Disable Permalink - Huy Nguyen 18/04/2025
                                            //wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false ); 
                                        ?>
                                        <input type="hidden" name="editable-post-name" class="dokan-hide" id="editable-post-name-full-dokan">
                                        <input type="hidden" value="<?php echo esc_attr( $post->post_name ); ?>" name="edited-post-name" class="dokan-hide" id="edited-post-name-dokan">
                                    </div>

                                    <?php $product_types = apply_filters( 'dokan_product_types', [ 'simple' => __( 'Simple', 'dokan-lite' ) ] ); ?>                                    
                                    <!-- ZZZZ - Force product type to Simple type-->
                                    <input type="hidden" id="product_type" name="product_type" value="simple">
                                    
                                    <!-- ZZZZ - Hide downloadable/virtual part-->
                                    <div style="display:none">
                                        <?php do_action( 'dokan_product_edit_after_title', $post, $post_id ); ?>
                                    </div>
                                    <div class="dokan-form-group">
                                        <?php
                                        $data = Helper::get_saved_products_category( $post_id );
                                        $data['from'] = 'edit_product';

                                        dokan_get_template_part( 'products/dokan-category-header-ui', '', $data );
                                        ?>
                                    </div>
                                    <!-- ZZZZ - Hide Price part - Huy Nguyen - 24/04/2025-->                                    
                                    <div class="dokan-form-group">
                                    <?php do_action( 'dokan_product_edit_after_pricing', $post, $post_id ); ?>
                                    </div>
                                    <!-- ZZZZZ - Removed Product tags part - Huy Nguyen - 24/04/2025-->
                                    <div class="dokan-form-group" id="product_tag_edit" style="visibility:hidden">                                        
                                    </div>

                                    <?php do_action( 'dokan_product_edit_after_product_tags', $post, $post_id ); ?>
                                </div><!-- .content-half-part -->

                                <div class="content-half-part featured-image">

                                    <div class="dokan-feat-image-upload dokan-new-product-featured-img">
                                        <?php
                                        $wrap_class        = ' dokan-hide';
                                        $instruction_class = '';
                                        $feat_image_id     = 0;

                                        if ( has_post_thumbnail( $post_id ) ) {
                                            $wrap_class        = '';
                                            $instruction_class = ' dokan-hide';
                                            $feat_image_id     = get_post_thumbnail_id( $post_id );
                                        }
                                        ?>

                                        <div class="instruction-inside<?php echo esc_attr( $instruction_class ); ?>">
                                            <input type="hidden" name="feat_image_id" class="dokan-feat-image-id" value="<?php echo esc_attr( $feat_image_id ); ?>">

                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <a href="#" style="font-size:16px;font-weight:bold;color:#1d5089;" class="dokan-feat-image-btn btn btn-sm"><?php esc_html_e( 'Upload a product/service image (maximum size 5MB)', 'dokan-lite' ); ?></a>
                                        </div>

                                        <div class="image-wrap<?php echo esc_attr( $wrap_class ); ?>">
                                            <a class="close dokan-remove-feat-image">&times;</a>
                                            <?php if ( $feat_image_id ) : ?>
                                                <?php
                                                echo get_the_post_thumbnail(
                                                    $post_id,
                                                    apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ),
                                                    [
                                                        'height' => '',
                                                        'width'  => '',
                                                    ]
                                                );
                                                ?>
                                            <?php else : ?>
                                                <img height="" width="" src="" alt="">
                                            <?php endif; ?>
                                        </div>
                                    </div><!-- .dokan-feat-image-upload -->

                                        <div class="dokan-product-gallery">
                                            <div class="dokan-side-body" id="dokan-product-images">
                                                <div id="product_images_container">
                                                    <ul class="product_images dokan-clearfix">
                                                        <?php
                                                        $product_images = get_post_meta( $post_id, '_product_image_gallery', true );
                                                        $gallery = explode( ',', $product_images );

                                                        if ( $gallery ) :
                                                            foreach ( $gallery as $image_id ) :
                                                                if ( empty( $image_id ) ) :
                                                                    continue;
                                                                endif;

                                                                $attachment_image = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                                                                ?>
                                                                <li class="image" data-attachment_id="<?php echo esc_attr( $image_id ); ?>">
                                                                    <img src="<?php echo esc_url( $attachment_image[0] ); ?>" alt="">
                                                                    <a href="#" class="action-delete" title="<?php esc_attr_e( 'Delete image', 'dokan-lite' ); ?>">&times;</a>
                                                                </li>
                                                                <?php
                                                            endforeach;
                                                        endif;
                                                        ?>
                                                        <li class="add-image add-product-images tips" data-title="<?php esc_html_e( 'Add gallery image', 'dokan-lite' ); ?>">
                                                            <a href="#" class="add-product-images"><i class="fas fa-plus" aria-hidden="true"></i></a>
                                                        </li>
                                                    </ul>

                                                    <input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( $product_images ); ?>">
                                                </div>
                                            </div>
                                        </div> <!-- .product-gallery -->

                                    <?php do_action( 'dokan_product_gallery_image_count' ); ?>

                                </div><!-- .content-half-part -->
                            </div><!-- .dokan-form-top-area -->

                            <div class="dokan-product-short-description hide-editor-tabs">
                                <label for="post_excerpt" class="form-label"><?php esc_html_e( 'Short Description', 'dokan-lite' ); ?></label>
                                <p class="short-introduction">This section includes two fields: a Title (the name shown to visitors) and a Short Description (maximum 800 characters in total).</p>
                                <?php                                
                                wp_editor(
                                    $post_excerpt,
                                    'post_excerpt',
                                    apply_filters(
                                        'dokan_product_short_description',
                                        [
                                            'editor_height' => 50,
                                            'quicktags'     => true,
                                            'media_buttons' => false,
                                            'teeny'         => false,
                                            'editor_class'  => 'post_excerpt'
                                        ]
                                    )
                                );
                                ?>
                            </div>
                            <div class="dokan-product-short-description-alert dokan-hide">
                                <?php esc_html_e( 'The description content should be between 1 and 650 words.', 'dokan-lite' ); ?>
                            </div>
                            <!-- ZZZZ Hide product description - Huy Nguyen 25/04/2025-->                                                       
                            <?php do_action( 'dokan_new_product_form', $post, $post_id ); ?>
                            <?php do_action( 'dokan_product_edit_after_main', $post, $post_id ); ?>

                            <?php 
                                // ZZZZZ - Disable parts after Inventory such as Specifications, Linked Products,...
                                // Huy Nguyen 18/04/2025
                                do_action( 'dokan_product_edit_after_inventory_variants', $post, $post_id ); 
                            ?>

                            <?php if ( $post_id ) : ?>
                                <?php do_action( 'dokan_product_edit_after_options', $post_id ); ?>
                            <?php endif; ?>

                            <?php wp_nonce_field( 'dokan_edit_product', 'dokan_edit_product_nonce' ); ?>

                            <input type="hidden" name="dokan_product_id" id="dokan_product_id" value="<?php echo esc_attr( $post_id ); ?>" />
                            <!--hidden input for Firefox issue-->
                            <input type="hidden" name="dokan_update_product" value="<?php esc_attr_e( 'Save Product', 'dokan-lite' ); ?>"/>
                            <input type="submit" name="dokan_update_product" id="publish" class="dokan-btn dokan-btn-theme dokan-btn-lg dokan-right" value="<?php esc_attr_e( 'Save Product', 'dokan-lite' ); ?>"/>
                            <div class="dokan-clearfix"></div>
                        </form>
                    <?php else : ?>
                        <div class="dokan-alert dokan-alert">
                            <?php echo esc_html( dokan_seller_not_enabled_notice() ); ?>
                        </div>
                    <?php endif; ?>

                <?php else : ?>

                    <?php do_action( 'dokan_can_post_notice' ); ?>

                <?php endif; ?>
            </div> <!-- #primary .content-area -->

            <?php
            /**
             * Action took to fire inside product content after.
             *
             *  @since 2.4
             */
            do_action( 'dokan_product_content_inside_area_after' );
            ?>
        </div>

        <?php
        /**
         * Action took to fire after dashboard content.
         *
         *  @since 2.4
         */
        do_action( 'dokan_dashboard_content_after' );

        /**
         * Action took to fire after product content area.
         *
         *  @since 2.4
         */
        do_action( 'dokan_after_product_content_area' );
        ?>

    </div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>

<div class="dokan-clearfix"></div>

<?php

/**
 * Action hook to fire after dokan dashboard wrap
 *
 *  @since 2.4
 */
do_action( 'dokan_dashboard_wrap_after', $post, $post_id );

wp_reset_postdata();

if ( ! $from_shortcode ) {
    get_footer();
}

// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
?>
