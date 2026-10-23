
<?php
/**
 * Coordinates manual delivery of stored leads to the CRM.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Delivery {

    public static function handle() {

        // Require administrator capability and a valid action nonce.
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to deliver leads.');
        }

        $lead_id = isset($_POST['lead_id'])
            ? absint(wp_unslash($_POST['lead_id']))
            : 0;

        check_admin_referer('wp_crm_bridge_send_' . $lead_id);

        $lead = WP_CRM_Bridge_Lead_Repository::find($lead_id);

        if (!$lead) {
            wp_die('Lead not found.');
        }

        // Already-delivered leads should not be sent again manually.
        if ($lead->status === 'delivered') {
            self::redirect('already_delivered');
        }

        $result = WP_CRM_Bridge_CRM_Client::send($lead);

        if (is_wp_error($result)) {
            // Keep failed attempts visible for future retry handling.
            WP_CRM_Bridge_Lead_Repository::update_status(
                $lead_id,
                'failed'
            );

            self::redirect('failed');
        }

        $updated = WP_CRM_Bridge_Lead_Repository::update_status(
            $lead_id,
            'delivered'
        );

        self::redirect($updated ? 'delivered' : 'update_failed');
    }

    /**
     * Return to the admin dashboard without exposing CRM secrets.
     */
    private static function redirect($result) {
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
