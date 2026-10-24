<?php

/**
 * Coordinates scheduled CRM delivery retries.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Retry
{

    const MAX_ATTEMPTS = 3;


    /**
     * Attempt delivery and retry only recoverable failures.
     *
     * @param int $lead_id WordPress database lead ID.
     */
    public static function process($lead_id)
    {

        $lead = WP_CRM_Bridge_Lead_Repository::find($lead_id);

        if (!$lead || $lead->status === 'delivered') {
            return;
        }

        // Never exceed the maximum number of delivery attempts.
        if ((int) $lead->attempts >= self::MAX_ATTEMPTS) {
            return;
        }

        $attempts = WP_CRM_Bridge_Lead_Repository::increment_attempts($lead_id);

        if ($attempts === false) {
            return;
        }

        $result = WP_CRM_Bridge_CRM_Client::send($lead);

        // Record the outcome of this delivery attempt.
        $log_result = is_wp_error($result) ? 'failed' : 'delivered';

        $error_code = is_wp_error($result)
            ? $result->get_error_code()
            : null;

        $log_id = WP_CRM_Bridge_Lead_Log_Repository::insert(
            $lead_id,
            $attempts,
            $log_result,
            null,
            $error_code
        );

        if (is_wp_error($log_id)) {
            error_log('WP CRM Bridge: Unable to save delivery log.');
        }

        if (!is_wp_error($result)) {

            // Delivery succeeded. Clear any outstanding scheduled event.
            wp_clear_scheduled_hook('wp_crm_bridge_retry_lead', [$lead_id]);

            WP_CRM_Bridge_Lead_Repository::update_status(
                $lead_id,
                'delivered'
            );

            return;
        }

        // Record the failed delivery attempt.
        WP_CRM_Bridge_Lead_Repository::update_status(
            $lead_id,
            'failed'
        );

        // Only network, rate-limit and server failures are retried.
        $retryable_errors = [
            'crm_connection_failed',
            'crm_temporary_failure',
        ];

        if (!in_array($result->get_error_code(), $retryable_errors, true)) {
            // A permanent or ambiguous failure needs investigation.
            // No automatic retry is scheduled.
            return;
        }

        // Stop once the maximum attempt count has been reached.
        if ($attempts >= self::MAX_ATTEMPTS) {
            return;
        }

        // Exponential-style backoff for our three-attempt policy.
        $delays = [
            1 => 60,  // Retry after one minute.
            2 => 300, // Retry after five minutes.
        ];

        $timestamp = time() + $delays[$attempts];

        // Prevent multiple scheduled events for the same lead.
        if (!wp_next_scheduled('wp_crm_bridge_retry_lead', [$lead_id])) {

            $scheduled = wp_schedule_single_event(
                $timestamp,
                'wp_crm_bridge_retry_lead',
                [$lead_id],
                true
            );

            if (!is_wp_error($scheduled) && $scheduled) {
                WP_CRM_Bridge_Lead_Repository::set_next_retry(
                    $lead_id,
                    $timestamp
                );
            }
        }
    }
}
