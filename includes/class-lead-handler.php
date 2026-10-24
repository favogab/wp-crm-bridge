<?php

/**
 * Handles incoming lead submissions through the WordPress REST API.
 *
 * Current scope:
 * - Register an authenticated endpoint.
 * - Validate and sanitize lead information.
 * - Return a JSON response.
 *
 * Database storage and CRM delivery will be added later.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CRM_Bridge_Lead_Handler
{

    /**
     * Register the REST API route.
     *
     * WordPress fires rest_api_init when initializing
     * its REST API routes.
     */
    public static function register_routes()
    {

        register_rest_route('wp-crm-bridge/v1', '/leads', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_lead'],

            // Temporary admin-only access while developing.
            // Public submissions require separate abuse protection.
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Validate an incoming lead and return the result.
     *
     * @param WP_REST_Request $request Incoming HTTP request.
     * @return WP_REST_Response|WP_Error
     */
    public static function create_lead($request)
    {
        // Read the JSON request body.
        $data = $request->get_json_params();

        // The request body must be a JSON object.
        if (!is_array($data)) {
            return new WP_Error(
                'invalid_json',
                'A valid JSON object is required.',
                ['status' => 400]
            );
        }

        // Every required field must exist and contain a string.
        foreach (['name', 'email', 'message'] as $field) {
            if (
                !isset($data[$field]) ||
                !is_string($data[$field])
            ) {
                return new WP_Error(
                    'invalid_field',
                    "{$field} must be provided as text.",
                    ['status' => 400]
                );
            }
        }

        // Reject invalid email addresses before normalization.
        // Sanitization alone is not proof that input is valid.
        $raw_email = trim($data['email']);

        if (!is_email($raw_email)) {
            return new WP_Error(
                'invalid_email',
                'A valid email address is required.',
                ['status' => 400]
            );
        }

        // Validate original input lengths before sanitization.
        // Use character counts so multibyte text is handled
        // more consistently than raw byte lengths.
        foreach (
            [
                'name' => 200,
                'email' => 254,
                'message' => 5000
            ] as $field => $max_length
        ) {

            if (mb_strlen($data[$field], 'UTF-8') > $max_length) {
                return new WP_Error(
                    'field_too_long',
                    "{$field} exceeds the allowed length.",
                    ['status' => 400]
                );
            }
        }

        // Normalize text before it is used by the application.
        $name = sanitize_text_field($data['name']);
        $email = sanitize_email($raw_email);
        $message = sanitize_textarea_field($data['message']);

        // Sanitization may leave a field empty.
        if ($name === '' || $message === '') {
            return new WP_Error(
                'invalid_lead',
                'Name and message are required.',
                ['status' => 400]
            );
        }


        // Keep input sizes bounded before future database storage.
        if (
            strlen($name) > 200 ||
            strlen($email) > 254 ||
            strlen($message) > 5000
        ) {
            return new WP_Error(
                'field_too_long',
                'One or more fields exceed the allowed length.',
                ['status' => 400]
            );
        }


        // Save the lead only after all validation checks have passed.
        $lead_id = WP_CRM_Bridge_Lead_Repository::insert(
            $name,
            $email,
            $message
        );

        // Stop if the database operation failed.
        if (is_wp_error($lead_id)) {
            return new WP_Error(
                'database_error',
                'Unable to save lead.',
                ['status' => 500]
            );
        }

        // Return the generated ID without exposing customer information.
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Lead saved successfully.',
            'lead_id' => $lead_id,
            'status'  => 'pending'
        ], 201);
    }
}
