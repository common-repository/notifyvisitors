<?php

add_action('wp_enqueue_scripts', 'nv_integration_script');

function nv_integration_script() {
    $nv_credentials = nv_get_credentials();
    if(!empty($nv_credentials) && is_array($nv_credentials)){
        wp_enqueue_script('wnv-integration-code', NV_PLUGIN_URL . 'assets/js/notifyvisitors.js');
        wp_localize_script('wnv-integration-code', 'nv_credentials', $nv_credentials);
    }
}