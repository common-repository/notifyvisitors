<?php

/**
 * get nv credentials from get_options
 * 
 */
function nv_get_credentials(){
    $credentials = [];
    $notifyvisitors_settings = get_option('notifyvisitors_settings');
    if(!empty($notifyvisitors_settings) && is_array($notifyvisitors_settings) && !empty($notifyvisitors_settings['nv_brand_id']) && !empty($notifyvisitors_settings['nv_secret_key'])){
        $credentials = [
            'nv_brand_id' => $notifyvisitors_settings['nv_brand_id']??'',
            'nv_secret_key' => $notifyvisitors_settings['nv_secret_key']??''
        ];
    }

    return $credentials;
}

/**
 * plugin uninstall remove from NV and delete options
 * 
 */
function nv_plugin_uninstall() {
    $credentials = nv_get_credentials();
    if(!empty($credentials) && is_array($credentials)){
        $webhook_url = 'https://www.notifyvisitors.com/api/plugin/uninstall/woocommerce/'.$credentials['nv_brand_id'];
        wp_safe_remote_get($webhook_url);

        nv_manage_service_worker($credentials, true);
    }

    delete_option('notifyvisitors_settings');
}

/**
 * for creating/removing service worker file
 * @param array $nv_credentials
 * @param boolean $remove_file
 * 
 */
function nv_manage_service_worker($nv_credentials = [], $remove_file = false){
    if(!empty($nv_credentials) && is_array($nv_credentials) && !empty($nv_credentials['nv_brand_id'])){
        $js_file_path = ABSPATH . 'service-worker.js';

        if (!file_exists($js_file_path) && !$remove_file) {
            $js_content = "var version = '2.1';\nvar NOTIFYVISITORS_BRAIND_ID = '".$nv_credentials['nv_brand_id']."';\nimportScripts('https://cdnp.notifyvisitors.com/js/brand_hosted/push-worker.js');\nimportScripts('https://s3.amazonaws.com/notifypush/cache_worker/config-".$nv_credentials['nv_brand_id'].".js');\nimportScripts('https://cdnp.notifyvisitors.com/js/brand_hosted/cache-worker.js');\n";
            file_put_contents($js_file_path, $js_content);
        }
    
        if(file_exists($js_file_path) && $remove_file){
            unlink($js_file_path);
        }
    }
}