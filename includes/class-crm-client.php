
<?php
/**
 * Sends validated WordPress leads to the external CRM API.
 *
 * Responsibilities:
 * - Read the configured CRM endpoint and API key.
 * - Send an authenticated JSON request.
 * - Validate the CRM acknowledgment.
 * - Return success or a WordPress error.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_CRM_Client {

    /**
     * Deliver a stored lead to the CRM.
     *
     * @param object $lead Database record.
     * @return true|WP_Error
     */
    public static function send($lead) {

        // Fail safely when CRM configuration is missing.
        if (
            !defined('WP_CRM_BRIDGE_API_KEY') ||
            !defined('WP_CRM_BRIDGE_API_URL')
        ) {
            return new WP_Error(
                'crm_not_configured',
                'CRM connection is not configured.'
            );
        }

        $reference = sprintf('CRM-%06d', (int) $lead->id);

        $payload = [
            'reference' => $reference,
            'name'      => $lead->name,
            'email'     => $lead->email,
            'message'   => $lead->message,
        ];

        // WordPress handles the HTTP transport and JSON request.
        $response = wp_remote_post(
            WP_CRM_BRIDGE_API_URL,
            [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key'    => WP_CRM_BRIDGE_API_KEY,
                ],
                'body' => wp_json_encode($payload),
            ]
        );

        // Network failures must not mark the lead as delivered.
        if (is_wp_error($response)) {
            return new WP_Error(
                'crm_connection_failed',
                'Unable to connect to the CRM service.'
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Require an explicit acknowledgment for this exact lead.
        if (
            !in_array($status_code, [200, 201], true) ||
            !is_array($body) ||
            ($body['success'] ?? false) !== true ||
            ($body['reference'] ?? '') !== $reference
        ) {
            return new WP_Error(
                'crm_delivery_failed',
                'CRM did not confirm successful delivery.'
            );
        }

        return true;
    }
}
