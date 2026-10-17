<?php
$post_statuses = dokan_get_available_post_status( $post->ID );
?>

<div class="dokan-other-options dokan-edit-row dokan-clearfix <?php echo esc_attr( $class ); ?>">
    <div class="dokan-section-heading" data-togglehandler="dokan_other_options">
        <h2><i class="fas fa-cog" aria-hidden="true"></i> <?php esc_html_e( 'Other Options', 'dokan-lite' ); ?></h2>
        <p><?php esc_html_e( 'Set your extra product options', 'dokan-lite' ); ?></p>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <div class="dokan-form-group content-half-part">
            <label for="post_status" class="form-label"><?php esc_html_e( 'Product Status', 'dokan-lite' ); ?></label>
            <p class="short-introduction">Choose one of the following options:<br/>
                •	Draft: The product will not appear in the product list until it’s complete. <br/>
                •	Online: The product is published and visible in the product list.
             </p>
            <select id="post_status" class="dokan-form-control" name="post_status">
                <?php foreach ( $post_statuses as $status => $label ) : // phpcs:ignore ?>
                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $post_status ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
    </div>
</div><!-- .dokan-other-options -->
