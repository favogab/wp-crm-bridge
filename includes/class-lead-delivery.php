
<?php
/**
 * Handles manual delivery requests from the WordPress admin dashboard.
 *
 * Responsibilities:
 * - Verify administrator permissions.
 * - Validate the request nonce and lead ID.
 * - Delegate delivery to the retry coordinator.
 * - Redirect back to the dashboard with a result message.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Delivery
{
    /**
     * Process a manual "Send to CRM" request.
     */
    public static function handle()
    {
        // Only administrators can initiate manual deliveries.
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to deliver leads.');
        }

        // Convert the submitted lead ID into a positive integer.
        $lead_id = isset($_POST['lead_id'])
            ? absint(wp_unslash($_POST['lead_id']))
            : 0;

        if ($lead_id === 0) {
            wp_die('Invalid lead ID.');
        }

        // Verify that the request came from our authorized admin form.
        check_admin_referer('wp_crm_bridge_send_' . $lead_id);

        // Retrieve the lead from the WordPress database.
        $lead = WP_CRM_Bridge_Lead_Repository::find($lead_id);

        if (!$lead) {
            wp_die('Lead not found.');
        }

        // Prevent manually resending a successfully delivered lead.
        if ($lead->status === 'delivered') {
            self::redirect('already_delivered');
        }

        // The retry coordinator handles the HTTP request,
        // attempt tracking, status updates, and retry scheduling.
        WP_CRM_Bridge_Lead_Retry::process($lead_id);

        // Reload the lead because its status may have changed.
        $updated_lead = WP_CRM_Bridge_Lead_Repository::find($lead_id);

        if ($updated_lead && $updated_lead->status === 'delivered') {
            self::redirect('delivered');
        }

        // A failed attempt may already have a retry scheduled.
        self::redirect('failed');
    }

    /**
     * Return to the dashboard without exposing CRM credentials.
     *
     * @param string $result Result identifier for the admin notice.
     */
    private static function redirect($result)
    {
        $url = add_query_arg(
            [
                'page' => 'wp-crm-bridge',
                'delivery_result' => $result,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }
}
