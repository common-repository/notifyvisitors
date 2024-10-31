<?php

add_action('wp_enqueue_scripts', 'nv_load_viewed_product',2);

/**
 * If on product page, get properties for Viewed Product metric. Enqueue viewed product
 * javascript and pass event data to script.
 */
function nv_load_viewed_product(){
    if (is_product()) {
        $product = wc_get_product();
        $parent_product_id = $product->get_parent_id();
        if ($product->get_parent_id() == 0) {
            $parent_product_id = $product->get_id();
        }

        $categories_array = get_the_terms($product->get_id(), 'product_cat');
        if ($categories_array === false) {
            $categories_array = [];
        }
        $categories = (array) wp_list_pluck($categories_array, 'name');

        $item = [
            'product_name' => (string) $product->get_name(),
            'product_id' => (int) $parent_product_id,
            'variant_id' => (int) $product->get_id(),
            'product_url' => (string) get_permalink($product->get_id()),
            'product_image_url' => (string) wp_get_attachment_url(get_post_thumbnail_id($product->get_id())),
            'product_price' => (float) $product->get_price(),
            'product_collection' => $categories
        ];

        wp_enqueue_script('wnv_viewed_product', plugins_url('assets/js/wnv-viewed-product.js', __FILE__), null, null, true);
        wp_localize_script('wnv_viewed_product', 'item', $item);
    }
}