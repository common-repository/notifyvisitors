<?php

/**
* The last woocommerce_add_to_cart hook has priority of 20 so we need to make sure we
* we fire after it. The higher the number, the later the function executes.
*/
add_action('woocommerce_add_to_cart', 'nv_added_to_cart_event', 10, 6);;

/**
 * If the param is an instance of a WP_Error, returns
 * an empty array. If the param is not a WP_Error then
 * runs strip_tags and explode to return an array of strings.
 *
 * @param string $list String of product terms.
 * @return array
 */
function nv_strip_explode($list) {
    if ($list instanceof WP_Error) {
        return [];
    }
    return explode(', ', strip_tags($list));
}

/**
 * Set wck_cart data then build the Added Item and return the array
 * of the full cart data.
 *
 * @param object $added_product Added product data.
 * @param object $cart Cart data.
 * @return array
 */
function nv_build_add_to_cart_data($added_product, $quantity, $cart) {
    $wck_cart = nv_build_cart_data($cart);
    $added_product_id = $added_product->get_id();

    $formatted_data = [
        'items' => $wck_cart['Items'],
        'total_price' => (float) $cart->total,
        'original_total_price' => (float) $cart->total,
        'categories' => (array) nv_strip_explode(wc_get_product_category_list($added_product_id))
    ];

    return $formatted_data;
}

/**
 * Set customer identity, call nv_build_add_to_cart_data and then call nv_track_request
 * to trigger the event.
 *
 * @param string $cart_item_key Unique key for item in cart.
 * @param int $product_id ID of item added to cart.
 * @param int $quantity Quantity of item added to cart.
 * @returns null
 */
function nv_added_to_cart_event($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    $added_product = wc_get_product($product_id);
    if (!$added_product instanceof WC_Product) {
        return;
    }

    $cart_data = nv_build_add_to_cart_data($added_product, $quantity, WC()->cart);
	
	$product_permalink = $added_product->get_permalink();
    nv_event_api($cart_data,$product_permalink);
}

/**
 * To hit nv_add_to_cart event api using webhook
 *
 * @param array $cart_data.
 * @param string $product_permalink.
 * @returns null
 */
function nv_event_api($cart_data = [], $product_permalink = '') {
    if(!empty($cart_data) && is_array($cart_data)){
        $nv_credentials = nv_get_credentials();
        if(!empty($nv_credentials) && is_array($nv_credentials)){
            $params = [];
            $params['bid_e'] = $nv_credentials['nv_secret_key'];
            $params['bid'] = $nv_credentials['nv_brand_id'];
            $params['t'] = 420;
            $params['trafficSource'] = '';
            $params['isPwa'] = 0;
            $params['pageUrl'] = !empty(get_permalink()) ? get_permalink() : ($product_permalink??'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $params['gmOffset'] = 19800;
            $params['screenWidth'] = '';
            $params['screenHeight'] = '';
            $params['storage'] = '{"session":{},"local":{}}';
            $params['linkreferrer'] = '';
            $params['incognito'] = 0;
            $params['event_name'] = 'nv_add_to_cart';
            $params['attributes'] = json_encode($cart_data);
            $params['ltv'] = 10;
            $params['scope'] = 1;
            $params['js_callback'] = 'nv_anal_json3';

            $cookie_data = '';
            foreach ($_COOKIE as $key => $value) {
                if (strpos($key, '_nv') === 0) {
					$cookie_data .= $key.'='.$value.';';
                }
            }
            $params['cookieData'] = $cookie_data;

            $webhook_url = add_query_arg($params, 'https://analytics.notifyvisitors.com/brand/t1/event');
            $response = wp_safe_remote_get($webhook_url);

            if (is_wp_error($response)) {
                error_log('Webhook request failed: ' . $response->get_error_message());
            }
        }
    }
}