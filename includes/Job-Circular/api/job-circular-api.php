<?php

// Define the path to job-circuler.php
$job_circuler = __DIR__ . '/../job-circular.php';

// Check if the file exists before including it
if (file_exists($job_circuler)) {
    require_once $job_circuler;
} else {
    // Handle the error if the file does not exist
    error_log('The file job_circuler.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file job_circuler.php does not exist', array('status' => 500));
}




// Register REST API routes
add_action('rest_api_init', function () {
    // Create job circular
    register_rest_route('custom/v1', '/job-circulars', array(
        'methods' => 'POST',
        'callback' => 'create_job_circular',
        'permission_callback' => 'is_user_logged_in',
    ));

    // Get all job circulars
    register_rest_route('custom/v1', '/job-circulars', array(
        'methods' => 'GET',
        'callback' => 'get_all_job_circulars',
        'permission_callback' => '__return_true', // Allow access to all users
    ));

    // Get a specific job circular
    register_rest_route('custom/v1', '/job-circulars/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_job_circular',
        'permission_callback' => '__return_true', // Allow access to all users
    ));

    // Update a job circular
    register_rest_route('custom/v1', '/update-job-circular/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_job_circular',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param); // Validate ID as numeric
                }
            ),
        ),
    ));

    // Delete a job circular
    register_rest_route('custom/v1', '/job-circulars/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_job_circular',
        'permission_callback' => 'is_user_logged_in',
    ));
});

// Create a job circular
function create_job_circular($request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $feature_image = $request->get_file_params()['file'];

    // Prepare job circular data
    $job_circular_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'job_circular',
    );

    // Insert job circular
    $job_circular_id = wp_insert_post($job_circular_data);

    // If job circular is created successfully
    if ($job_circular_id && !is_wp_error($job_circular_id)) {
        // Upload feature image
        $image_upload_response = handle_image_upload($request);

        // If image upload is successful, set it as the feature image
        if (!is_wp_error($image_upload_response)) {
            set_post_thumbnail($job_circular_id, $image_upload_response->data['image_id']);

            // Update job circular with the featured media
            wp_update_post(array(
                'ID' => $job_circular_id,
                'meta_input' => array(
                    '_thumbnail_id' => $image_upload_response->data['image_id'],
                ),
            ));
        }

        // Get the job circular data
        $job_circular = get_post($job_circular_id);
        $post_date = get_the_date('', $job_circular_id);

        return rest_ensure_response(array(
            'id' => $job_circular_id,
            'title' => $title,
            'content' => $content,
            'feature_image_url' => $image_upload_response->data['image_url'],
            'date' => $post_date, // Add creation date
        ));
    } else {
        return new WP_Error('create_job_circular_error', 'Error creating job circular', array('status' => 500));
    }
}

// Get all job circulars
function get_all_job_circulars($request) {
    $args = array(
        'post_type' => 'job_circular',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Retrieve all job circulars
    );

    $query = new WP_Query($args);
    $job_circulars = $query->posts;

    if (empty($job_circulars)) {
        return new WP_REST_Response(array('message' => 'No job circulars found'), 404);
    }

    $response = array();

    foreach ($job_circulars as $job_circular) {
        $custom_fields = array(
            'application_deadline' => get_post_meta($job_circular->ID, 'application_deadline', true),
            'shortlist' => get_post_meta($job_circular->ID, 'shortlist', true),
            'job_requirements' => get_post_meta($job_circular->ID, 'job_requirements', true),
            'job_responsibilities' => get_post_meta($job_circular->ID, 'job_responsibilities', true),
            'job_skills' => get_post_meta($job_circular->ID, 'job_skills', true),
            'compensation_benefits' => get_post_meta($job_circular->ID, 'compensation_benefits', true),
            'workplace' => get_post_meta($job_circular->ID, 'workplace', true),
            'employment_status' => get_post_meta($job_circular->ID, 'employment_status', true),
            'job_location' => get_post_meta($job_circular->ID, 'job_location', true),
            'company_information' => get_post_meta($job_circular->ID, 'company_information', true),
        );

        $response[] = array(
            'id' => $job_circular->ID,
            'title' => $job_circular->post_title,
            'content' => $job_circular->post_content,
            'feature_image_url' => get_the_post_thumbnail_url($job_circular->ID),
            'date' => get_the_date('', $job_circular->ID),
            'author' => get_the_author_meta('display_name', $job_circular->post_author),
            'custom_fields' => $custom_fields, // Include the custom fields
        );
    }

    return rest_ensure_response($response);
}


// Get a specific job circular
function get_job_circular($request) {
    $id = (int) $request['id'];

    // Get job circular data
    $job_circular = get_post($id);

    if ($job_circular && $job_circular->post_type === 'job_circular') {
        $custom_fields = array(
            'application_deadline' => get_post_meta($job_circular->ID, 'application_deadline', true),
            'shortlist' => get_post_meta($job_circular->ID, 'shortlist', true),
            'job_requirements' => get_post_meta($job_circular->ID, 'job_requirements', true),
            'job_responsibilities' => get_post_meta($job_circular->ID, 'job_responsibilities', true),
            'job_skills' => get_post_meta($job_circular->ID, 'job_skills', true),
            'compensation_benefits' => get_post_meta($job_circular->ID, 'compensation_benefits', true),
            'workplace' => get_post_meta($job_circular->ID, 'workplace', true),
            'employment_status' => get_post_meta($job_circular->ID, 'employment_status', true),
            'job_location' => get_post_meta($job_circular->ID, 'job_location', true),
            'company_information' => get_post_meta($job_circular->ID, 'company_information', true),
        );

        return rest_ensure_response(array(
            'id' => $job_circular->ID,
            'title' => $job_circular->post_title,
            'content' => $job_circular->post_content,
            'feature_image_url' => get_the_post_thumbnail_url($job_circular->ID),
            'date' => get_the_date('', $job_circular->ID),
            'author' => get_the_author_meta('display_name', $job_circular->post_author),
            'custom_fields' => $custom_fields, // Include the custom fields
        ));
    } else {
        return new WP_Error('job_circular_not_found', 'Job circular not found', array('status' => 404));
    }
}


// Update a job circular
function update_job_circular(WP_REST_Request $request) {
    $job_circular_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));

    if (empty($title) || empty($content)) {
        return new WP_REST_Response(array('message' => 'Title or content is empty'), 400);
    }

    // Handle file upload if a new file is provided
    $files = $request->get_file_params();
    $file_path = '';
    $feature_image_url = '';

    if (!empty($files['file'])) {
        $file = $files['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error']) && $upload['error'] !== false) {
            return new WP_REST_Response(array('message' => $upload['error']), 500);
        }

        // Successfully uploaded file
        $file_path = $upload['file'];
        $file_type = wp_check_filetype(basename($file_path), null);
        $attachment = array(
            'guid' => $upload['url'],
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name(basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file_path);

        if (!is_wp_error($attach_id)) {
            // Delete previous featured image if exists
            $prev_thumbnail_id = get_post_thumbnail_id($job_circular_id);
            if ($prev_thumbnail_id) {
                wp_delete_attachment($prev_thumbnail_id, true);
            }

            // Set the new post thumbnail (featured image)
            set_post_thumbnail($job_circular_id, $attach_id);

            // Get the new feature image URL
            $feature_image_url = wp_get_attachment_url($attach_id);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    } else {
        // If no new file is uploaded, get the existing feature image URL
        $feature_image_url = get_the_post_thumbnail_url($job_circular_id);
    }

    // Update job circular's title and content
    $updated_post = array(
        'ID' => $job_circular_id,
        'post_title' => $title,
        'post_content' => $content,
    );

    // Update the post
    $post_updated = wp_update_post($updated_post, true);

    if (is_wp_error($post_updated)) {
        return new WP_REST_Response(array('message' => $post_updated->get_error_message()), 500);
    }

    return rest_ensure_response(array(
        'id' => $job_circular_id,
        'title' => $title,
        'content' => $content,
        'feature_image_url' => $feature_image_url,
        'date' => get_the_date('', $job_circular_id),
        'author' => get_the_author_meta('display_name', get_post_field('post_author', $job_circular_id)),
    ));
}

// Delete a job circular
function delete_job_circular($request) {
    $job_circular_id = (int) $request['id'];

    // Check if the job circular exists
    if (!get_post($job_circular_id)) {
        return new WP_Error('job_circular_not_found', 'Job circular not found', array('status' => 404));
    }

    // Delete the post
    $deleted = wp_delete_post($job_circular_id, true);

    if (!$deleted) {
        return new WP_Error('delete_error', 'Error deleting job circular', array('status' => 500));
    }

    return rest_ensure_response(array('message' => 'Job circular deleted successfully'));
}




?>