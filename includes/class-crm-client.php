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

class WP_CRM_Bridge_CRM_Client
{


    /**
     * Send a stored WordPress lead to the CRM.
     *
     * @param object $lead Stored lead record.
     * @return true|WP_Error
     */
    public static function send($lead)
    {

        // Missing credentials or endpoint cannot be fixed by retrying.
        if (
            !defined('WP_CRM_BRIDGE_API_KEY') ||
            !defined('WP_CRM_BRIDGE_API_URL') ||
            WP_CRM_BRIDGE_API_KEY === '' ||
            WP_CRM_BRIDGE_API_URL === ''
        ) {
            return new WP_Error(
                'crm_not_configured',
                'CRM connection is not configured.'
            );
        }

        // Keep the reference identical across all delivery attempts.
        $reference = sprintf('WP-LOCAL-CRM-%06d', (int) $lead->id);

        $payload = [
            'reference' => $reference,
            'name'      => $lead->name,
            'email'     => $lead->email,
            'message'   => $lead->message,
        ];

        // Permit HTTP only for the local development CRM.
        $url = WP_CRM_BRIDGE_API_URL;
        $parts = wp_parse_url($url);

        $is_local = is_array($parts)
            && ($parts['scheme'] ?? '') === 'http'
            && ($parts['host'] ?? '') === '127.0.0.1'
            && (int) ($parts['port'] ?? 0) === 8000
            && ($parts['path'] ?? '') === '/api/leads'
            && !isset($parts['user'], $parts['pass'])
            && !isset($parts['query'])
            && !isset($parts['fragment']);

        if (!$is_local) {
            return new WP_Error(
                'crm_untrusted_destination',
                'CRM destination is not approved.'
            );
        }

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

        // Network errors and timeouts may be temporary.
        if (is_wp_error($response)) {
            return new WP_Error(
                'crm_connection_failed',
                'Unable to connect to the CRM service.'
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);

        // These HTTP responses normally require human intervention.
        if (in_array($status_code, [400, 401, 403, 404, 409, 422], true)) {
            return new WP_Error(
                'crm_permanent_failure',
                'CRM rejected the delivery request.',
                ['http_status' => $status_code]
            );
        }

        // Rate limits and server failures may recover later.
        if ($status_code === 429 || $status_code >= 500) {
            return new WP_Error(
                'crm_temporary_failure',
                'CRM is temporarily unable to accept the lead.',
                ['http_status' => $status_code]
            );
        }

        // Unexpected responses should not trigger unlimited retries.
        if (!in_array($status_code, [200, 201], true)) {
            return new WP_Error(
                'crm_unexpected_response',
                'CRM returned an unexpected HTTP response.',
                ['http_status' => $status_code]
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // HTTP success alone is insufficient: verify the acknowledgment.
        if (
            !is_array($body) ||
            ($body['success'] ?? false) !== true ||
            ($body['reference'] ?? '') !== $reference
        ) {
            return new WP_Error(
                'crm_invalid_acknowledgment',
                'CRM response did not confirm this lead.'
            );
        }

        return true;
    }
}
