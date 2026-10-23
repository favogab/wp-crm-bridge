<?php

if (!defined('ABSPATH')) {
    exit;
}

function wp_crm_bridge_render_dashboard()
{

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

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
                    <td>0</td>
                </tr>
                <tr>
                    <th>Successful Deliveries</th>
                    <td>0</td>
                </tr>
                <tr>
                    <th>Failed Deliveries</th>
                    <td>0</td>
                </tr>
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
