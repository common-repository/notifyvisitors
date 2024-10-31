<?php

/**
 * Helper function to normalize normal product.
 *
 * @param array  $item Cart item.
 * @return array Normalized cart item.
 */
function nv_normalize_normal_product($item)
{
    return array(
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'variation_id' => $item['variation_id'],
        'variation' => $item['variation']
    );
}

/**
 * Helper function for composite products.
 *
 * @param array  $container_ids container ids.
 * @param array $values values.
 * @return array Composite product.
 */
function nv_add_encoded_composite($container_ids, $values)
{
    $composite_product = array();
    foreach ($container_ids as $container_id => $container_values) {
        $components = array();
        if (isset($container_values['attributes'])) {
            $components = array(
                'composite_id' => $container_values['composite_id'],
                'composite_quantity' => $values['quantity'],
                'item' => array(
                    'product_id' => $container_values['product_id'],
                    'quantity' => $container_values['quantity'],
                    'container_id' => $container_id,
                    'attributes' => $container_values['attributes'],
                    'variation_id' => isset($container_values['variation_id']) ? $container_values['variation_id'] : null,
                )
            );
        } else {
            $components = array(
                'composite_id' => $container_values['composite_id'],
                'composite_quantity' => $values['quantity'],
                'item' => array(
                    'product_id' => $container_values['product_id'],
                    'quantity' => $container_values['quantity'],
                    'container_id' => $container_id,
                )
            );
        }
        array_push($composite_product, $components);
    }
    return $composite_product;
}


/**
 * Build the wck_cart and return the event_data.
 *
 * @param WC_Cart $cart The woocommerce cart
 * @return array Normalized event data
 */
function nv_build_cart_data($cart) {
    $event_data = array(
        'CurrencySymbol' => get_woocommerce_currency_symbol(),
        'Currency' => get_woocommerce_currency(),
        '$value' => $cart->total,
        '$extra' => array(
            'Items' => array(),
            'SubTotal' => $cart->subtotal,
            'ShippingTotal' => $cart->shipping_total,
            'TaxTotal' => $cart->tax_total,
            'GrandTotal' => $cart->total
        )
    );
    $wck_cart = array();
    $composite_products = array();
    $normal_products = array();
    $all_categories = array();
    $item_names = array();
    $all_tags = array();
    $item_count = 0;

    foreach ($cart->get_cart() as $cart_item_key => $values) {
        $product = $values['data'];
        $parent_product_id = $product->get_parent_id();

        if ($product->get_parent_id() == 0) {
            $parent_product_id = $product->get_id();
        }

        $categories = array();
        $categories_array = get_the_terms($parent_product_id, 'product_cat');
        if ($categories_array && ! is_wp_error($categories_array)) {
            $categories = wp_list_pluck($categories_array, 'name');

            foreach ($categories as $category) {
                array_push($all_categories, $category);
            }
        }
        $tags_array = get_the_terms($parent_product_id, 'product_tag');
        if ($tags_array && ! is_wp_error($tags_array)) {
            $tags = wp_list_pluck($tags_array, 'name');

            foreach ($tags as $tag) {
                array_push($all_tags, $tag);
            }
        }

        $is_composite_child = false;

        $nv_is_chained_product = nv_is_chained_product($values);

        if (class_exists('WC_Composite_Products')) {
            $product_encoded = json_encode($product);
            $is_composite_child = wc_cp_is_composited_cart_item($values);
            $container = wc_cp_get_composited_cart_item_container($values);

            if ($product->get_type() == 'composite') {
                $composite_product = array();

                foreach (wc_cp_get_composited_cart_items($values) as $key => $val) {
                    $composite_product = nv_add_encoded_composite($val['composite_data'], $values);
                    break;
                }
                array_push($composite_products, $composite_product);
            } else {
                if (!$is_composite_child and !$nv_is_chained_product) {
                    $normal_products[$cart_item_key] = nv_normalize_normal_product($values);
                }
            }
        } else {
            if (!$nv_is_chained_product) {
                $normal_products[$cart_item_key] = nv_normalize_normal_product($values);
            }
        }

        $image = wp_get_attachment_url(get_post_thumbnail_id($product->get_id()));

        if ($image == false) {
            $image = wp_get_attachment_url(get_post_thumbnail_id($parent_product_id));
        }

        $event_data['Items'][] = [
            'image' => $image,
            'price' => $values['line_subtotal']??'',
            'original_price' => $values['line_subtotal']??'',
            'quantity' => $values['quantity']??0,
            'product_id' => $parent_product_id,
            'variant_id' => $product->get_id(),
            'product_title' => $product->get_name(),
            'url' => $product->get_permalink(),
            'categories' => $categories,
        ];
        $item_count += $values['quantity']??0;
        $all_categories = array_values(array_unique($all_categories));
        $event_data['Categories'] = $all_categories;
        $all_tags = array_values(array_unique($all_tags));
        $event_data['Tags'] = $all_tags;
        array_push($item_names, $product->get_name());
    }

    $event_data['Quantity'] = $item_count;
    $event_data['ItemNames'] = $item_names;
    $wck_cart['composite'] = $composite_products;
    $wck_cart['normal_products'] = $normal_products;
    $event_data['$extra']['CartRebuildKey'] = base64_encode(json_encode($wck_cart));

    return $event_data;
}

/**
 * Check if product instance of WooCommerce Chained Products plugin: https://woocommerce.com/products/chained-products/
 *
 * @param object $cart_item_properties cart properties key/values.
 * @return boolean
 */
function nv_is_chained_product($cart_item_properties) {

    if (class_exists('WC_Chained_Products') &&  ! empty($cart_item_properties['chained_item_of'])) {
        return true;
    }
    return false;
}
