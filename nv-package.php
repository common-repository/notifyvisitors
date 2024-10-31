<?php

/**
 * Plugin Name: NotifyVisitors for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/notifyvisitors/
 * Description: NotifyVisitors offers marketing automation software that involves email marketing, SMS marketing, WhatsApp, push notifications, popups, and signup forms. Easy-to-use interface with pre-built automations and ready-made email templates, allows you to build and run personalized campaigns without any effort. Our in-depth analytics help you track campaign performance, increase traffic, sales and revenue, and improve website conversion rate. Fast onboarding with our expert support team.
 * Version: 1.0
 * Author: NotifyVisitors
 * Author URI: https://www.notifyvisitors.com
 * 
 */
class NotifyVisitors_Integration {

    function __construct() {
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            include('wnv-utils.php');
            include('wnv-integration-code.php');
            include('wnv-viewed-product.php');
            include('wnv-cart-rebuild.php');
            include('wnv-added-to-cart.php');
            include('wnv-user-login.php');
        }
        define('NV_PLUGIN_URL', plugin_dir_url(__FILE__));
        
        register_activation_hook(NV_PLUGIN_URL, array($this, 'nv_plugin_activate'));
        add_action('admin_menu', array($this, 'nv_create_menu'), 1);
    }
	
	function nv_plugin_activate() {
        wp_redirect(admin_url('admin.php?page=notifyvisitors_settings'));
		exit;
    }

    function nv_create_menu() {
        add_menu_page('NotifyVisitors', 'NotifyVisitors', 'manage_options', 'notifyvisitors_settings', array($this, 'settings_page'), NV_PLUGIN_URL. 'assets/img/notifyvisitors-logo.png');
        add_filter('plugin_action_links_' . NV_PLUGIN_URL, array($this, 'plugin_settings_link'));
    }

    function nv_plugin_integration(){
        if($_GET['page'] == 'notifyvisitors_settings' && !empty($_GET['user_id'])){
            $user_id = explode('||',base64_decode($_GET['user_id']));
            $notifyvisitors_settings['nv_brand_id'] = $user_id[0];
            $notifyvisitors_settings['nv_secret_key'] = $user_id[1];
            update_option('notifyvisitors_settings', $notifyvisitors_settings);

            register_uninstall_hook(__FILE__, 'nv_plugin_uninstall');

            nv_manage_service_worker($notifyvisitors_settings);
        }
    }

    function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
		
        $this->nv_plugin_integration();

        $nv_credentials = nv_get_credentials();

        wp_enqueue_style('wnv-admin-settings', NV_PLUGIN_URL . 'assets/css/wnv-admin.css');
?>
        <div class="wnv-settings">
            <div class="wnv-content-wrapper">
                <div class="wnv-content">
                    <div class="wnv-logo">
                        <img src="<?php echo NV_PLUGIN_URL. 'assets/img/notifyvisitors-woocommerce.png' ?>">
                    </div>
                    <div class="wnv-content-subtitles">
                        <?php if(!empty($nv_credentials) && is_array($nv_credentials)){ ?>
                            <span class="wnv-content-title">NotifyVisitors Account Connected.</span>
                        <?php } else { ?>
                            <span class="wnv-content-title">Connect Your NotifyVisitors Account.</span>
                        <?php } ?>
                        <span class="wnv-content-subtitle">Increase Sales With Email Marketing Automation Software, SMS, Push Notifications, Forms, Popups</span>
                        <div class="wnv-content-feature">
                            <p>Using NotifyVisitors, you can: </p>
                            <ul>
                                <li>Send personalized messages, email newsletters to target segments</li>
                                <li>Automations - Welcome series, Cart Abandonment, post purchase recommendations</li>
                                <li>Pre-built and customizable email templates with Drag And Drop Content Editor</li>
                                <li>Grow email, sms marketing list with Exit Intent Popups and Sign-up Forms</li>
                                <li>Fast onboarding, quick support, start for free with all features, 1000 emails/mo</li>
                            </ul>
                        </div>
                        <?php
                        // Check if WooCommerce is active
                        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                        ?>
                        <div class="connect-buttons">
                            <fieldset class="connect-button">
                                <?php if(!empty($nv_credentials) && is_array($nv_credentials)){ ?>
                                    <a class="button button-primary wnv-button-one" href="https://analytics.notifyvisitors.com/brand/cms/configuration/woocommerce">Connected</a>
                                <?php } else { ?>
                                    <a id="wnv_oauth_connect" class="button button-primary wnv-button-one" href="https://www.notifyvisitors.com/api/plugin/integration/woocommerce?store_url=<?= get_home_url(); ?>">Connect Your Account</a>
                                <?php } ?>
                            </fieldset>
                        </div>
                        <?php } else {
                            echo '<div class="wrap"><h3 style="color:#F36969">Either WooCommerce is not installed or it is not active.</h3></div>';
                        } ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}

new NotifyVisitors_Integration();