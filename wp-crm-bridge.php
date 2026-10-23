<?php
/**
 * Plugin Name: WP CRM Bridge
 * Description: A secure WordPress CRM integration plugin featuring lead synchronization, API communication, automated retries, and delivery monitoring.
 * Version: 0.1.0
 * Author: FavoGab
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Register the plugin's admin menu.
 *
 * Only users with the manage_options capability
 * can access the integration dashboard.
 */
function wp_crm_bridge_register_menu() {
    add_menu_page(
        'WP CRM Bridge',
        'CRM Bridge',
        'manage_options',
        'wp-crm-bridge',
        'wp_crm_bridge_render_dashboard',
        'dashicons-rest-api',
        30
    );
}

// Load the admin dashboard rendering function.
require_once plugin_dir_path(__FILE__) . 'admin/dashboard.php';

// Load the REST API lead handler.
require_once plugin_dir_path(__FILE__) . 'includes/class-lead-handler.php';

// Register the admin menu during WordPress initialization.
add_action('admin_menu', 'wp_crm_bridge_register_menu');

// Register our custom endpoints when the REST API initializes.
add_action(
    'rest_api_init',
    ['WP_CRM_Bridge_Lead_Handler', 'register_routes']
);
