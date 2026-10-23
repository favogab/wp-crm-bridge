<?php

/**
 * Data access layer for CRM leads.
 *
 * Keeps database operations separate from HTTP request handling.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Repository
{

    /**
     * Save a validated lead.
     *
     * @return int|WP_Error Database ID or error.
     */
    public static function insert($name, $email, $message)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crm_bridge_leads';

        // WordPress handles SQL value escaping through wpdb::insert().
        $result = $wpdb->insert(
            $table_name,
            [
                'name'       => $name,
                'email'      => $email,
                'message'    => $message,
                'status'     => 'pending',
                'created_at' => current_time('mysql', true)
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            // Avoid exposing internal database details to API clients.
            return new WP_Error(
                'database_error',
                'Unable to save lead.'
            );
        }

        return (int) $wpdb->insert_id;
    }


    /**
     * Count leads by their delivery status.
     *
     * @param string|null $status Optional status filter.
     * @return int Number of matching leads.
     */
    public static function count($status = null)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crm_bridge_leads';

        // Without a status filter, count every stored lead.
        if ($status === null) {
            return (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table_name}"
            );
        }

        // Prepare dynamic values to prevent SQL injection.
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = %s",
                $status
            )
        );
    }

    /**
     * Retrieve the most recent leads for the admin dashboard.
     *
     * @param int $limit Maximum number of records to return.
     * @return array List of database records.
     */
    public static function get_recent($limit = 10)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crm_bridge_leads';

        // Bound the number of rows to avoid excessive database queries.
        $limit = max(1, min(50, (int) $limit));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, email, status, created_at
             FROM {$table_name}
             ORDER BY id DESC
             LIMIT %d",
                $limit
            )
        );
    }


    /**
     * Find a lead by its permanent database ID.
     *
     * @return object|null
     */
    public static function find($id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crm_bridge_leads';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Update the delivery status of an existing lead.
     *
     * @return bool True if the database operation succeeded.
     */
    public static function update_status($id, $status)
    {
        global $wpdb;

        // Only recognized delivery states may be stored.
        if (!in_array($status, ['pending', 'delivered', 'failed'], true)) {
            return false;
        }

        $table_name = $wpdb->prefix . 'crm_bridge_leads';

        $result = $wpdb->update(
            $table_name,
            ['status' => $status],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        // wpdb::update() returns false on error; zero affected rows
        // does not necessarily indicate a database failure.
        return $result !== false;
    }
}
