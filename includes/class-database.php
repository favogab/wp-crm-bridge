<?php

/**
 * Database setup for WP CRM Bridge.
 *
 * Creates the custom leads table when the plugin is activated.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Database
{
    public static function create_tables()
{
    global $wpdb;

    // Respect the WordPress installation's table prefix.
    $leads_table = $wpdb->prefix . 'crm_bridge_leads';
    $logs_table = $wpdb->prefix . 'crm_bridge_delivery_logs';

    // Use the database character set and collation configured by WordPress.
    $charset_collate = $wpdb->get_charset_collate();

    /**
     * Leads table:
     * Stores customer submissions and their current delivery state.
     */
    $leads_sql = "CREATE TABLE {$leads_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        email varchar(254) NOT NULL,
        message text NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL,
        attempts int unsigned NOT NULL DEFAULT 0,
        last_attempt_at datetime DEFAULT NULL,
        next_retry_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY status (status)
    ) {$charset_collate};";

    /**
     * Delivery logs table:
     * Records the outcome of every CRM delivery attempt.
     * One lead may have multiple delivery log entries.
     */
    $logs_sql = "CREATE TABLE {$logs_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        lead_id bigint(20) unsigned NOT NULL,
        attempt_number int unsigned NOT NULL,
        result varchar(20) NOT NULL,
        http_status smallint unsigned DEFAULT NULL,
        error_code varchar(100) DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY lead_id (lead_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    // Load WordPress's database schema management function.
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Create or update both tables independently.
    dbDelta($leads_sql);
    dbDelta($logs_sql);
}
}
