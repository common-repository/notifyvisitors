<?php
add_action('woocommerce_checkout_create_order', 'nv_checkout_event', 10, 1);

/**
 * Trigger checkout (after order creation but before payment completion) event and send data to webhook
 * @param object $order
 * 
 **/
function nv_checkout_event($order) {
    $user_id = $order->get_user_id();
    $user = get_user_by('ID', $user_id);
    $user_data = [
        'user_id' => $user_id,
        'username' => $user->user_login,
        'email' => $user->user_email,
    ];
	
    $order_items = $order->get_items();

    $products = [];
    foreach ($order_items as $item_id => $item) {
        $product = $item->get_product();

        $product_sku = $product->get_sku();
        $product_image_url = get_the_post_thumbnail_url($product->get_id(), 'full');

        // Build product data array
        $product_data = [
            'name' => $product->get_name(),
            'sku' => $product_sku,
            'quantity' => $item->get_quantity(),
            'image' => $product_image_url,
        ];
        $products[] = $product_data;
    }

    // Prepare data to send to the webhook
    $data = [
        'order_id' => $order->get_id(),
        'user_data' => $user_data,
        'products' => $products,
    ];

    nv_webhook($data, 'checkout');
}