<?php

// Define the path to question.php
$question_file_path = __DIR__ . '/../question.php';

// Check if the file exists before including it
if (file_exists($question_file_path)) {
    require_once $question_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file question.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file question.php does not exist', array('status' => 500));
}


// কাস্টম রেস্ট এপিআই রুট নিবন্ধন করা
function register_custom_api_routes_question_olee() {
    register_rest_route('custom/v1', '/questions', array(
        'methods' => 'POST',
        'callback' => 'create_question',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user

    ));

    register_rest_route('custom/v1', '/questions', array(
        'methods' => 'GET',
        'callback' => 'get_questions',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_question',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'update_question',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_question',
        'permission_callback' => function() {
            return current_user_can('delete_posts');
        },
    ));
}
add_action('rest_api_init', 'register_custom_api_routes_question_olee');


// Create Question Callback Function
function create_question($request) {
    $params = $request->get_json_params();

    // Sanitize and validate input
    $title = sanitize_text_field($params['title']);
    $mcq_options = isset($params['mcq']) ? sanitize_text_field($params['mcq']) : '';

    // Determine question type based on mcq_options
    $question_type = ! empty($mcq_options) ? 'mcq' : 'text';

    // Insert the question post
    $post_id = wp_insert_post(array(
        'post_title' => $title,
        'post_type' => 'question',
        'post_status' => 'publish',
    ));

    if ($post_id && ! is_wp_error($post_id)) {
        // Update post meta with question type and mcq options
        update_post_meta($post_id, '_question_type', $question_type);
        if (! empty($mcq_options)) {
            update_post_meta($post_id, '_mcq_options', $mcq_options);
        }

        // Return success response
        return new WP_REST_Response(array('message' => 'Question created successfully', 'id' => $post_id), 201);
    } else {
        // Handle error case
        $error_message = $post_id->get_error_message();
        return new WP_Error('cannot_create', $error_message, array('status' => 500));
    }
}


// সমস্ত প্রশ্ন পুনরুদ্ধার করা
function get_questions($request) {
    $args = array(
        'post_type' => 'question',
        'posts_per_page' => -1, // Get all posts
        'post_status' => 'publish',
    );

    $questions = get_posts($args);

    $formatted_questions = array_map(function($question) {
        $question_id = $question->ID;
        $question_type = get_post_meta($question_id, '_question_type', true);
        $mcq_options_raw = get_post_meta($question_id, '_mcq_options', true);

        // Convert mcq_options from comma-separated string to array
        $mcq_options = ! empty($mcq_options_raw) ? explode(',', $mcq_options_raw) : [];

        return array(
            'id' => $question_id,
            'title' => $question->post_title,
            'question_type' => $question_type,
            'mcq_options' => $mcq_options, // Return as array
        );
    }, $questions);

    return new WP_REST_Response($formatted_questions, 200);
}

// Function to get a single question
function get_question($request) {
    $question_id = $request['id'];

    $question = get_post($question_id);

    if (! $question || $question->post_type !== 'question') {
        return new WP_Error('not_found', 'Question not found', array('status' => 404));
    }

    // Get question meta
    $question_type = get_post_meta($question_id, '_question_type', true);
    $mcq_options_raw = get_post_meta($question_id, '_mcq_options', true);

    // Convert mcq_options from comma-separated string to array
    $mcq_options = ! empty($mcq_options_raw) ? explode(',', $mcq_options_raw) : [];

    // Prepare response
    $response = array(
        'id' => $question_id,
        'title' => $question->post_title,
        'question_type' => $question_type,
        'mcq_options' => $mcq_options, // Return as array
    );

    return new WP_REST_Response($response, 200);
}



// প্রশ্ন আপডেট করা
function update_question($request) {
    $id = (int) $request['id'];
    $params = $request->get_json_params();
    $title = sanitize_text_field($params['title']);
    $question_type = sanitize_text_field($params['type']);
    $mcq_options = isset($params['mcq']) ? sanitize_text_field($params['mcq']) : '';

    $post = array(
        'ID' => $id,
        'post_title' => $title,
    );

    // Update the question post
    if (wp_update_post($post)) {
        // Update question type and mcq options meta
        update_post_meta($id, '_question_type', $question_type);

        // Handle mcq_options as an array if provided
        if (! empty($mcq_options)) {
            $mcq_options_array = explode(',', $mcq_options);
            update_post_meta($id, '_mcq_options', $mcq_options_array);
        } else {
            // If mcq_options is empty, update as empty array
            update_post_meta($id, '_mcq_options', []);
        }

        return new WP_REST_Response(array('message' => 'Question updated successfully', 'id' => $id), 200);
    }

    return new WP_Error('cannot_update', 'Unable to update question', array('status' => 500));
}

// প্রশ্ন মুছে ফেলা
function delete_question($request) {
    $id = (int) $request['id'];

    if (wp_delete_post($id, true)) {
        return new WP_REST_Response(array('message' => 'Question deleted successfully'), 200);
    }

    return new WP_Error('cannot_delete', 'Unable to delete question', array('status' => 500));
}


?>