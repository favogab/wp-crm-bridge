# WP CRM Bridge

A custom WordPress plugin that securely captures website leads, stores them in MySQL, and delivers them to an external CRM through an authenticated REST API.

## Features

- Custom WordPress REST endpoint for lead submissions
- Input validation and secure database storage
- Authenticated communication with an external CRM
- Admin dashboard showing lead delivery statuses
- Manual delivery and retry controls
- Scheduled retries for temporary delivery failures
- Duplicate prevention through unique lead references
- Database-backed delivery attempt logging

## Architecture

The integration consists of two independent applications:

1. **WordPress Plugin (PHP + MySQL):** Receives, validates, stores, and manages leads.
2. **CRM Service (Python + FastAPI + SQLite):** Accepts authenticated lead deliveries and stores CRM records.

**Data flow:**

`Client → WordPress REST API → MySQL → CRM Client → FastAPI → SQLite`

Delivery results are recorded in WordPress and displayed in the admin dashboard.

## Local Development

The plugin was developed using:

- WordPress and PHP
- LocalWP
- MySQL
- Python FastAPI (separate CRM service)

### Installation

1. Clone the repository into `wp-content/plugins/wp-crm-bridge`.
2. Start the WordPress site.
3. Configure the CRM API URL and API key in the local WordPress configuration.
4. Activate WP CRM Bridge from the WordPress Plugins page.
5. Open the WP CRM Bridge admin dashboard.

Never commit real API keys or environment secrets.

## Lead Delivery

New leads are stored locally with a `pending` status.

When delivery is attempted, the plugin sends an authenticated JSON request to the CRM.

A confirmed delivery changes the status to `delivered`. Failed deliveries remain visible for troubleshooting and may be retried according to the configured retry rules.

## Delivery Monitoring

The WordPress admin dashboard displays:

- Total leads
- Pending deliveries
- Successful deliveries
- Failed deliveries
- Recent lead records and available actions

A separate delivery log table records delivery attempts and their outcomes.

## Security

The integration includes:

- Input validation
- Administrator capability checks
- WordPress nonces for administrative actions
- API key authentication
- Prepared database queries
- Output escaping
- Restricted delivery statuses
- Generic client-facing error messages

## Testing

The integration was tested locally across WordPress, MySQL, FastAPI, and SQLite.

A successful end-to-end test confirmed that a lead could be submitted through WordPress, stored locally, delivered to the CRM, marked as delivered, and recorded in the delivery log.

## ## Development Status

Local development and end-to-end integration testing completed. Production deployment and further hardening remain future work.

## Roadmap

WP CRM Bridge is being developed toward a WordPress enquiry management and CRM integration platform.

### v0.1 — Integration Foundation (Completed)
- Lead submission through a custom WordPress REST API
- MySQL lead storage
- Authenticated delivery to a Python CRM service
- Delivery status dashboard
- Retry handling and delivery logs
- Input validation and security controls

### v0.2 — Enquiry Management (Planned)
- Enquiry inbox with full message details
- Enquiry statuses independent of CRM delivery statuses
- Internal notes and follow-up tracking
- Standalone operation without an external CRM

### v0.3 — Form Integrations (Planned)
- Elementor Forms integration
- Contact Form 7 integration
- Shared lead-processing pipeline

### v0.4 — Built-in Form Builder (Planned)
- Create and manage contact forms
- Shortcode and Gutenberg block support
- Configurable fields and validation

### v0.5 — CRM Integrations (Planned)
- Configurable external CRM connections
- Field mapping
- Additional CRM providers

## Development Status

Version 0.1 has been tested in a local development environment.

The current release is a working integration prototype, not a production-ready WordPress plugin. Features listed in the roadmap are not yet implemented.