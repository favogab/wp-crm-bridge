
<?php
/**
 * Stores CRM delivery attempt history.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Log_Repository
{
    /**
     * Record one delivery attempt.
     *
     * @return int|WP_Error Inserted log ID or error.
     */
    public static function insert(
        $lead_id,
        $attempt_number,
        $result,
        $http_status = null,
        $error_code = null
    ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'crm_bridge_delivery_logs';

        // Only allow known delivery results.
        if (!in_array($result, ['delivered', 'failed'], true)) {
            return new WP_Error(
                'invalid_log_result',
                'Invalid delivery log result.'
            );
        }

        $inserted = $wpdb->insert(
            $table_name,
            [
                'lead_id'        => absint($lead_id),
                'attempt_number' => absint($attempt_number),
                'result'         => $result,
                'http_status'    => $http_status === null
                    ? null
                    : absint($http_status),
                'error_code'     => $error_code === null
                    ? null
                    : sanitize_key($error_code),
                'created_at'     => current_time('mysql', true),
            ],
            ['%d', '%d', '%s', '%d', '%s', '%s']
        );

        if ($inserted === false) {
            return new WP_Error(
                'log_insert_failed',
                'Unable to record CRM delivery attempt.'
            );
        }

        return (int) $wpdb->insert_id;
    }
}
