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


?>
    <div class="wrap">
        <h1>WP CRM Bridge</h1>

        <p>Manage your CRM integration and lead synchronization.</p>

        <hr>


        <h2>Integration Overview</h2>

        <table class="widefat striped">
            <tbody>
                <tr>
                    <th>CRM Connection</th>
                    <td>Not configured</td>
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
                </tr>
            </thead>

            <tbody>
                <?php if (empty($recent_leads)) : ?>

                    <!-- Display this message when no leads exist. -->
                    <tr>
                        <td colspan="6">No leads have been submitted yet.</td>
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
