<?php

// Define the path to Appointment.php
$appointment_file_path = __DIR__ . '/../appointment.php';

// Check if the file exists before including it
if (file_exists($appointment_file_path)) {
    require_once $appointment_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file appointment.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file appointment.php does not exist', array('status' => 500));
}



// Register REST API Routes
function register_appointment_rest_routes() {
    register_rest_route('custom/v1', '/appointment/all', array(
        'methods'             => 'GET',
        'callback'            => 'get_all_appointments',
        'permission_callback' => '__return_true', // Protect with JWT Authentication
    ));
		register_rest_route('custom/v1', '/appointment/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => 'get_appointment_by_id',
            'permission_callback' => '__return_true', // Protect with JWT Authentication
		));
    register_rest_route('custom/v1', '/appointment/create', array(
        'methods'             => 'POST',
        'callback'            => 'create_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user jwt_auth_permission_callback
    ));

    register_rest_route('custom/v1', '/appointment/update/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => 'update_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));

    register_rest_route('custom/v1', '/appointment/delete/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'delete_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));

    register_rest_route('custom/v1', '/appointment/update-status/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => 'update_appointment_status',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));
    
    register_rest_route('custom/v1', '/appointment/user/(?P<user_id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'get_appointments_by_user',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));    
}
add_action('rest_api_init', 'register_appointment_rest_routes');

// Get All Appointments Function
function get_all_appointments() {
    $args = array(
        'post_type' => 'appointment',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft','requested','approved','cancelled')
    );

    $appointments = get_posts($args);
    $data = array();

    foreach ($appointments as $appointment) {
        $data[] = array(
            'id' => $appointment->ID,
//           'status' => $appointment->post_status, // Include the status in the response
            'date' => get_post_meta($appointment->ID, 'appointment_date', true),
            'time' => get_post_meta($appointment->ID, 'appointment_time', true),
            'name' => get_post_meta($appointment->ID, 'appointment_name', true),
            'email' => get_post_meta($appointment->ID, 'appointment_email', true),
            'phone' => get_post_meta($appointment->ID, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment->ID, 'appointment_status', true) // Include custom meta field
        );
    }

    return new WP_REST_Response($data, 200);
}

// Create Appointment Function
function create_appointment(WP_REST_Request $request) {
    $params = $request->get_json_params();

    // Ensure all required fields are present
    if (!isset($params['appointment_date'], $params['appointment_time'], $params['appointment_name'], $params['appointment_email'], $params['appointment_phone'])) {
        return new WP_REST_Response((object) array('message' => 'Missing required fields'), 400);
    }

    $new_appointment = array(
        'post_title'    => sanitize_text_field($params['appointment_name']),
        'post_status'   => 'draft', // Set initial status to 'draft'
        'post_type'     => 'appointment'
    );

    $appointment_id = wp_insert_post($new_appointment);

    if ($appointment_id) {
        // Update meta fields
        update_post_meta($appointment_id, 'appointment_date', sanitize_text_field($params['appointment_date']));
        update_post_meta($appointment_id, 'appointment_time', sanitize_text_field($params['appointment_time']));
        update_post_meta($appointment_id, 'appointment_name', sanitize_text_field($params['appointment_name']));
        update_post_meta($appointment_id, 'appointment_email', sanitize_email($params['appointment_email']));
        update_post_meta($appointment_id, 'appointment_phone', sanitize_text_field($params['appointment_phone']));

        // Optional: Set appointment status if provided
        if (isset($params['appointment_status'])) {
            update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));
        }

        // Prepare the response object
        $response = (object) array(
            'message' => 'Appointment created successfully',
            'id' => $appointment_id,
            'date' => get_post_meta($appointment_id, 'appointment_date', true),
            'time' => get_post_meta($appointment_id, 'appointment_time', true),
            'name' => get_post_meta($appointment_id, 'appointment_name', true),
            'email' => get_post_meta($appointment_id, 'appointment_email', true),
            'phone' => get_post_meta($appointment_id, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment_id, 'appointment_status', true)
        );

        return new WP_REST_Response($response, 201);
    }

    return new WP_REST_Response((object) array('message' => 'Failed to create appointment'), 500);
}


// Get Appointment by ID Function
function get_appointment_by_id(WP_REST_Request $request) {
    $appointment_id = (int) $request['id'];

    // Fetch the appointment post
    $appointment = get_post($appointment_id);
    
    // Check if the post exists and is of type 'appointment'
    if (!$appointment || $appointment->post_type !== 'appointment') {
        return new WP_REST_Response(array('message' => 'Appointment not found'), 404);
    }

    // Prepare the data object
    $data = new stdClass();
    $data->id = $appointment->ID;
//     $data->status = $appointment->post_status;
    $data->date = get_post_meta($appointment->ID, 'appointment_date', true);
    $data->time = get_post_meta($appointment->ID, 'appointment_time', true);
    $data->name = get_post_meta($appointment->ID, 'appointment_name', true);
    $data->email = get_post_meta($appointment->ID, 'appointment_email', true);
    $data->phone = get_post_meta($appointment->ID, 'appointment_phone', true);
    $data->appointment_status = get_post_meta($appointment->ID, 'appointment_status', true);

    return new WP_REST_Response($data, 200);
}


// Update Appointment Function
function update_appointment(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $appointment_id = (int) $request['id'];

    $updated_appointment = array(
        'ID' => $appointment_id,
        'post_title' => isset($params['appointment_name']) ? sanitize_text_field($params['appointment_name']) : '',
        'post_status' => isset($params['status']) ? sanitize_text_field($params['status']) : 'draft'
    );

    $appointment_id = wp_update_post($updated_appointment);

    if ($appointment_id) {
        // Update meta fields
        if (isset($params['appointment_date'])) {
            update_post_meta($appointment_id, 'appointment_date', sanitize_text_field($params['appointment_date']));
        }

        if (isset($params['appointment_time'])) {
            update_post_meta($appointment_id, 'appointment_time', sanitize_text_field($params['appointment_time']));
        }

        if (isset($params['appointment_name'])) {
            update_post_meta($appointment_id, 'appointment_name', sanitize_text_field($params['appointment_name']));
        }

        if (isset($params['appointment_email'])) {
            update_post_meta($appointment_id, 'appointment_email', sanitize_email($params['appointment_email']));
        }

        if (isset($params['appointment_phone'])) {
            update_post_meta($appointment_id, 'appointment_phone', sanitize_text_field($params['appointment_phone']));
        }

        if (isset($params['appointment_status'])) {
            update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));
        }

        // Prepare the response object
        $response = (object) array(
            'message' => 'Appointment updated successfully',
            'id' => $appointment_id,
            'date' => get_post_meta($appointment_id, 'appointment_date', true),
            'time' => get_post_meta($appointment_id, 'appointment_time', true),
            'name' => get_post_meta($appointment_id, 'appointment_name', true),
            'email' => get_post_meta($appointment_id, 'appointment_email', true),
            'phone' => get_post_meta($appointment_id, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment_id, 'appointment_status', true)
        );

        return new WP_REST_Response($response, 200);
    }

    return new WP_REST_Response((object) array('message' => 'Failed to update appointment'), 500);
}




// Delete Appointment Function
function delete_appointment(WP_REST_Request $request) {
    $appointment_id = (int) $request['id'];

    // Fetch appointment details before deleting
    $appointment = get_post($appointment_id);
    if (!$appointment || $appointment->post_type !== 'appointment') {
        return new WP_REST_Response(array('message' => 'Invalid appointment ID'), 404);
    }

    // Prepare the data object for the response
    $data = new stdClass();
    $data->id = $appointment->ID;
//     $data->status = $appointment->post_status;
    $data->date = get_post_meta($appointment->ID, 'appointment_date', true);
    $data->time = get_post_meta($appointment->ID, 'appointment_time', true);
    $data->name = get_post_meta($appointment->ID, 'appointment_name', true);
    $data->email = get_post_meta($appointment->ID, 'appointment_email', true);
    $data->phone = get_post_meta($appointment->ID, 'appointment_phone', true);
    $data->appointment_status = get_post_meta($appointment->ID, 'appointment_status', true);


    // Delete the appointment
    if (wp_delete_post($appointment_id)) {
        return new WP_REST_Response(array('message' => 'Appointment deleted successfully', 'appointment' => $data), 200);
    }

    return new WP_REST_Response(array('message' => 'Failed to delete appointment'), 500);
}


// Update Appointment Status Function
function update_appointment_status(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $appointment_id = (int) $request['id'];

    // Check if appointment ID and status are provided
    if (!$appointment_id || !isset($params['appointment_status'])) {
        return new WP_REST_Response(array('message' => 'Missing appointment ID or appointment status'), 400);
    }

    // Update only appointment_status meta field
    update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));

    // Get the updated appointment post
    $appointment = get_post($appointment_id);

        // Prepare the data object
        $data = new stdClass();
        $data->id = $appointment->ID;
//         $data->status = $appointment->post_status;
        $data->date = get_post_meta($appointment->ID, 'appointment_date', true);
        $data->time = get_post_meta($appointment->ID, 'appointment_time', true);
        $data->name = get_post_meta($appointment->ID, 'appointment_name', true);
        $data->email = get_post_meta($appointment->ID, 'appointment_email', true);
        $data->phone = get_post_meta($appointment->ID, 'appointment_phone', true);
        $data->appointment_status = get_post_meta($appointment->ID, 'appointment_status', true);

    return new WP_REST_Response($data, 200);
}


// Get Appointments by User ID Function
function get_appointments_by_user(WP_REST_Request $request) {
    $user_id = (int) $request['user_id'];

    $args = array(
        'post_type' => 'appointment',
        'posts_per_page' => -1,
        'author' => $user_id,
        'post_status' => array('publish', 'draft')
    );

    $appointments = get_posts($args);
    $data = array();

    foreach ($appointments as $appointment) {
        $data[] = array(
            'id' => $appointment->ID,
//             'status' => $appointment->post_status, // Include the status in the response
            'date' => get_post_meta($appointment->ID, 'appointment_date', true),
            'time' => get_post_meta($appointment->ID, 'appointment_time', true),
            'name' => get_post_meta($appointment->ID, 'appointment_name', true),
            'email' => get_post_meta($appointment->ID, 'appointment_email', true),
            'phone' => get_post_meta($appointment->ID, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment->ID, 'appointment_status', true) // Include custom meta field
        );
    }

    return new WP_REST_Response($data, 200);
}

?>