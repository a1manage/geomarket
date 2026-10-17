<?php
/**
 * @since 3.6.4
 *
 * @var $product_id int
 * @var $saved_data array
 */

use WeDevs\Dokan\CatalogMode\Helper;

?>
<?php do_action( 'dokan_product_edit_before_catalog_mode', $product_id, $saved_data ); ?>
<!-- ZZZZZZZZZZ - Disable Catalog mode - original file in dokan-lite/templates/product -->
<style>
    .product-edit-new-container .dokan-catalog-mode {
        margin-bottom: 20px;
    }
</style>

<div class="dokan-catalog-mode dokan-clearfix">
</div>    