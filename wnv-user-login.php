<?php
add_action('wp_enqueue_scripts', 'nv_user_login');

function nv_user_login() {
    if (is_user_logged_in() && !is_admin()) {
        // Get the current user
        $current_user = wp_get_current_user();
        
		// Start a session if not already started
		if (!session_id()) {
			session_start();
		}

		if (!isset($_SESSION['wnv_logged_in_user']) || (isset($_SESSION['wnv_logged_in_user']) && $_SESSION['wnv_logged_in_user'] !== $current_user->ID)) {
			
			// Get the customer object
            $customer = new WC_Customer($current_user->ID);
			
			$_SESSION['wnv_logged_in_user'] = $current_user->ID;

            // Get customer data
            $customer_data = [ 
                'customer_id' => (int) $current_user->ID,
                'name' => (string) $current_user->user_login,
                'email' => (string) $current_user->user_email,
                'first_name' => (string) $customer->get_first_name(),
                'last_name' => (string) $customer->get_last_name(),
                'mobile' => (string) $customer->get_billing_phone()
            ];
    
            wp_enqueue_script('wnv_user_login_script', plugin_dir_url(__FILE__) . 'assets/js/wnv-user-login.js', null, null, true);
            wp_localize_script('wnv_user_login_script', 'customer_data', $customer_data);
		}
    }
}