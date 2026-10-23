<?php
/**
 * Database setup for WP CRM Bridge.
 *
 * Creates the custom leads table when the plugin is activated.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Database {

    public static function create_tables() {
        global $wpdb;

        // Respect the site's configured WordPress table prefix.
        $table_name = $wpdb->prefix . 'crm_bridge_leads';
        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta manages table creation and future schema updates.
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            email varchar(254) NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}
