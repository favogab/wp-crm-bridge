<?php

if (!defined('ABSPATH')) {
    exit;
}

function wp_crm_bridge_render_dashboard()
{

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }


    // Retrieve real statistics from the leads database.
    $total_leads = WP_CRM_Bridge_Lead_Repository::count();
    $pending_leads = WP_CRM_Bridge_Lead_Repository::count('pending');
    $successful_leads = WP_CRM_Bridge_Lead_Repository::count('delivered');
    $failed_leads = WP_CRM_Bridge_Lead_Repository::count('failed');

    // Retrieve the latest ten leads for the admin table.
    $recent_leads = WP_CRM_Bridge_Lead_Repository::get_recent(10);

    
    // Check whether both CRM configuration values exist.
    // Configured does not necessarily mean the CRM is online.
    $crm_configured =
        defined('WP_CRM_BRIDGE_API_KEY') &&
        defined('WP_CRM_BRIDGE_API_URL') &&
        WP_CRM_BRIDGE_API_KEY !== '' &&
        WP_CRM_BRIDGE_API_URL !== '';


?>
    <div class="wrap">
        <h1>WP CRM Bridge</h1>

        <p>Manage your CRM integration and lead synchronization.</p>

        <hr>


        <?php
        // Show a generic result after returning from the delivery action.
        $result = isset($_GET['delivery_result'])
            ? sanitize_key(wp_unslash($_GET['delivery_result']))
            : '';

        $messages = [
            'delivered' => 'Lead delivered to the CRM successfully.',
            'failed' => 'CRM delivery failed. The lead can be retried.',
            'already_delivered' => 'This lead was already delivered.',
            'update_failed' => 'CRM accepted the lead, but the local status update failed.',
        ];

        if (isset($messages[$result])) :
        ?>
            <div class="notice notice-info">
                <p><?php echo esc_html($messages[$result]); ?></p>
            </div>
        <?php endif; ?>


        <h2>Integration Overview</h2>

        <table class="widefat striped">
            <tbody>
                <tr>
                    <th>CRM Connection</th>
                    <td>
                        <?php
                        echo esc_html(
                            $crm_configured ? 'Configured' : 'Not configured'
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>Total Leads</th>
                    <td><?php echo esc_html($total_leads); ?></td>
                </tr>
                <tr>
                    <th>Pending Deliveries</th>
                    <td><?php echo esc_html($pending_leads); ?></td>
                </tr>
                <tr>
                    <th>Successful Deliveries</th>
                    <td><?php echo esc_html($successful_leads); ?></td>
                </tr>
                <tr>
                    <th>Failed Deliveries</th>
                    <td><?php echo esc_html($failed_leads); ?></td>
                </tr>
            </tbody>
        </table>


        <h2>Recent Leads</h2>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lead Reference</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created (UTC)</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($recent_leads)) : ?>

                    <!-- Display this message when no leads exist. -->
                    <tr>
                        <td colspan="7">No leads have been submitted yet.</td>
                    </tr>

                <?php else : ?>

                    <?php
                    // Display position starts at 1 for the newest lead.
                    // This is independent of the permanent database ID.
                    $position = 1;
                    ?>

                    <?php foreach ($recent_leads as $lead) : ?>
                        <tr>
                            <td><?php echo esc_html($position++); ?></td>
                            <?php $lead_reference = sprintf('CRM-%06d', (int) $lead->id); ?>
                            <td><?php echo esc_html($lead_reference); ?></td>
                            <td><?php echo esc_html($lead->name); ?></td>
                            <td><?php echo esc_html($lead->email); ?></td>
                            <td><?php echo esc_html($lead->status); ?></td>
                            <td><?php echo esc_html($lead->created_at); ?></td>

                            <td>
                                <?php if ($lead->status === 'delivered') : ?>
                                    Delivered
                                <?php else : ?>
                                    <form method="post"
                                        action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="wp_crm_bridge_send_lead">

                                        <input
                                            type="hidden"
                                            name="lead_id"
                                            value="<?php echo esc_attr($lead->id); ?>">

                                        <?php
                                        // Bind the nonce to this specific lead's delivery action.
                                        wp_nonce_field('wp_crm_bridge_send_' . $lead->id);
                                        ?>

                                        <button type="submit" class="button button-secondary">
                                            Send to CRM
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
            </tbody>
        </table>



        <?php
        // Temporary local development helper for authenticated API tests.
        // Remove this helper before publishing the plugin.
        $test_nonce = wp_create_nonce('wp_rest');
        ?>

        <h2>API Development Test</h2>

        <p>Authenticated REST API testing is enabled for this admin page.</p>

        <div
            id="wp-crm-bridge-test"
            data-nonce="<?php echo esc_attr($test_nonce); ?>"></div>


    </div>

<?php
}
