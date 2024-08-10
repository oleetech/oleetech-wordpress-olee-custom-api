<?php

// Function to display the Contact Us submenu page content
function olee_custom_api_contact_page() {
    $site_url = home_url('/wp-json/custom/v1/contact-form');
    ?>
    <div class="wrap">
        <h1>Contact Us</h1>
        <p>Welcome to the Contact Us page.</p>
        <h2>Contact Us API Documentation</h2>
        <p><strong>Endpoint:</strong> <code><?php echo esc_url($site_url); ?></code></p>
        <p><strong>Method:</strong> POST</p>
        <p><strong>Request Body (JSON):</strong></p>
        <pre>
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "subject": "Example Subject",
    "message": "Hello, this is a test message."
}
        </pre>
        <p><strong>Response (JSON):</strong></p>
        <pre>
{
    "message": "Contact form submitted successfully"
}
        </pre>
    </div>
    <?php
}



//                          _                    _        __                            
//    ___    ___    _ __   | |_    __ _    ___  | |_     / _|   ___    _ __   _ __ ___  
//   / __|  / _ \  | '_ \  | __|  / _` |  / __| | __|   | |_   / _ \  | '__| | '_ ` _ \ 
//  | (__  | (_) | | | | | | |_  | (_| | | (__  | |_    |  _| | (_) | | |    | | | | | |
//   \___|  \___/  |_| |_|  \__|  \__,_|  \___|  \__|   |_|    \___/  |_|    |_| |_| |_|
//
//                                                                                     

// https://pahona.org/api/wp-json/custom/v1/contact-form
// {
//     "name": "John Doe",
//     "email": "john.doe@example.com",
//     "subject": "Example Subject",
//     "message": "Hello, this is a test message."
// }

// Register REST API endpoint for contact form submission
function custom_contact_form_endpoint() {
    register_rest_route('custom/v1', '/contact-form', array(
        'methods' => 'POST',
        'callback' => 'handle_contact_form_submission',
        'permission_callback' => '__return_true',
        'args' => array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Name of the person submitting the form',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Email address of the person submitting the form',
            ),
            'subject' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Subject of the message',
            ),
            'message' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Message content',
            ),
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of the contact form submission',
                ),
            ),
        ),
    ));
}

add_action('rest_api_init', 'custom_contact_form_endpoint');

// Handle contact form submission callback
function handle_contact_form_submission($request) {
    $name = sanitize_text_field($request->get_param('name'));
    $email = sanitize_email($request->get_param('email'));
    $subject = sanitize_text_field($request->get_param('subject'));
    $message = sanitize_textarea_field($request->get_param('message'));

    // Validate email
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email format', array('status' => 400));
    }

    // Send email to admin or custom email address
    $admin_email = get_option('admin_email');
    $sent = wp_mail($admin_email, $subject, $message, "From: $name <$email>");

    if (!$sent) {
        return new WP_Error('email_send_failed', 'Failed to send email', array('status' => 500));
    }

    return new WP_REST_Response(array('message' => 'Contact form submitted successfully'), 200);
}





?>