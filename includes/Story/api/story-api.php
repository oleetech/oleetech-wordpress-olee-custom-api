<?php

// Define the path to story.php
$story_file_path = __DIR__ . '/../story.php';

// Check if the file exists before including it
if (file_exists($story_file_path)) {
    require_once $story_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file story.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file story.php does not exist', array('status' => 500));
}



add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/story', array(
        'methods' => 'POST',
        'callback' => 'create_story',
        'permission_callback' => 'is_user_logged_in',
    ));

	    register_rest_route('custom/v1', '/stories', array(
        'methods' => 'GET',
        'callback' => 'get_all_stories_custom',
        'permission_callback' => '__return_true', // Allow access to all users
    ));
    register_rest_route('custom/v1', '/story/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_story',
        'permission_callback' => '__return_true', // Allow access to all users
    ));



    register_rest_route('custom/v1', '/update-story/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_story_custom',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param); // Validate ID as numeric
                }
            ),
        ),
        'show_in_index' => false,
    ));
	
    register_rest_route('custom/v1', '/story/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_story',
        'permission_callback' => 'is_user_logged_in',
    ));
});

function create_story($request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $feature_image = $request->get_file_params()['file'];

    // নতুন স্টোরি তৈরির জন্য পোস্ট অ্যারে প্রস্তুত করছি
    $story_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'story',
    );

    // স্টোরি তৈরি করছি
    $story_id = wp_insert_post($story_data);

    // যদি স্টোরি তৈরি হয়
    if ($story_id && !is_wp_error($story_id)) {
        // ফিচার ইমেজ আপলোড করছি
        $image_upload_response = handle_image_upload($request);

        // ফিচার ইমেজ আপলোড সফল হলে সেটাকে স্টোরির ফিচার ইমেজ হিসেবে সেট করছি
        if (!is_wp_error($image_upload_response)) {
            set_post_thumbnail($story_id, $image_upload_response->data['image_id']);

            // আপডেটিং স্টোরি উইথ ফিচারড মিডিয়া
            wp_update_post(array(
                'ID' => $story_id,
                'meta_input' => array(
                    '_thumbnail_id' => $image_upload_response->data['image_id'],
                ),
            ));
        }
        // স্টোরি ডেটা প্রাপ্ত করছি
        $story = get_post($story_id);
		$post_date = get_the_date('', $story_id);
        // স্টোরি আইডি এবং অন্যান্য তথ্য রিটার্ন করছি
        return rest_ensure_response(array(
            'id' => $story_id,
            'title' => $title,
            'content' => $content,
            'feature_image_url' => $image_upload_response->data['image_url'],
			'date' => $post_date, // Add creation date

        ));
    } else {
        return new WP_Error('create_story_error', 'Error creating story', array('status' => 500));
    }
}

// Callback function to handle the request
function get_all_stories_custom($request) {
    $args = array(
        'post_type' => 'story',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Retrieve all stories
    );

    $query = new WP_Query($args);
    $stories = $query->posts;

    if (empty($stories)) {
        return new WP_REST_Response(array('message' => 'No stories found'), 404);
    }

    $response = array();

    foreach ($stories as $story) {
        $response[] = array(
            'id' => $story->ID,
            'title' => $story->post_title,
            'content' => $story->post_content,
            'excerpt' => $story->post_excerpt,
            'feature_image_url' => get_the_post_thumbnail_url($story->ID),
            'date' => get_the_date('', $story->ID),
            'author' => get_the_author_meta('display_name', $story->post_author),
        );
    }

    return rest_ensure_response($response);
}

function get_story($request) {
    $id = (int) $request['id'];

    // স্টোরি ডেটা প্রাপ্ত করছি
    $story = get_post($id);

    // যদি স্টোরি খুঁজে পাওয়া যায়
    if ($story && $story->post_type === 'story') {
        // ফিচার ইমেজ URL প্রাপ্ত করছি
        $feature_image_url = get_the_post_thumbnail_url($story->ID);

        // স্টোরি ডেটা রিটার্ন করছি
        return rest_ensure_response(array(
            'id' => $story->ID,
            'title' => $story->post_title,
            'content' => $story->post_content,
            'feature_image_url' => $feature_image_url,
			'date' => get_the_date('', $story->ID),
			'author' => get_the_author_meta('display_name', $story->post_author),

        ));
    } else {
        return new WP_Error('story_not_found', 'Story not found', array('status' => 404));
    }
}

// স্টোরি আপডেট করার জন্য কলব্যাক ফাংশন
function update_story_custom(WP_REST_Request $request) {
    $story_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));

    // Check if title or content is empty
    if (empty($title) || empty($content)) {
        return new WP_REST_Response(array('message' => 'Title or content is empty'), 400);
    }

    // Handle file upload if a new file is provided
    $files = $request->get_file_params();
    $file_path = '';
    $feature_image_url = '';

    if (!empty($files['file'])) {
        // Process file upload
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
            $prev_thumbnail_id = get_post_thumbnail_id($story_id);
            if ($prev_thumbnail_id) {
                wp_delete_attachment($prev_thumbnail_id, true);
            }

            // Set the new post thumbnail (featured image)
            set_post_thumbnail($story_id, $attach_id);

            // Get the new feature image URL
            $feature_image_url = wp_get_attachment_url($attach_id);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    } else {
        // If no new file is uploaded, get the existing feature image URL
        $feature_image_url = get_the_post_thumbnail_url($story_id);
    }

    // Update story's title and content
    $updated_post = array(
        'ID' => $story_id,
        'post_title' => $title,
        'post_content' => $content,
    );

    // Update the post
    $post_updated = wp_update_post($updated_post, true);

    if (is_wp_error($post_updated)) {
        return new WP_REST_Response(array('message' => $post_updated->get_error_message()), 500);
    }

    // Get the updated post date
    $updated_post_data = get_post($story_id);
    $post_date = $updated_post_data->post_date;

    // Return response indicating success
    return new WP_REST_Response(array(
        'message' => 'Story updated successfully',
        'id' => $story_id,
        'title' => $title,
        'content' => $content,
        'date' => get_the_date('', $story_id),
        'feature_image_url' => $feature_image_url,
        'file_uploaded' => !empty($file_path) ? $file_path : null,
    ), 200);
}


function delete_story($request) {
    $id = (int) $request['id'];

    // স্টোরি প্রাপ্ত করছি
    $story = get_post($id);

    // যদি স্টোরি খুঁজে পাওয়া যায়
    if ($story && $story->post_type === 'story') {
        // ফিচার ইমেজ আইডি প্রাপ্ত করছি
        $thumbnail_id = get_post_thumbnail_id($story->ID);

        // স্টোরি ডিলিট করছি
        $deleted = wp_delete_post($id, true);

        // যদি স্টোরি সফলভাবে ডিলিট হয়
        if ($deleted) {
            // ফিচার ইমেজও ডিলিট করছি
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }

            // সফলভাবে ডিলিট হয়েছে রিটার্ন করছি
            return rest_ensure_response(array(
                'message' => 'Story deleted successfully',
                'id' => $id,
            ));
        } else {
            return new WP_Error('delete_story_error', 'Error deleting story', array('status' => 500));
        }
    } else {
        return new WP_Error('story_not_found', 'Story not found', array('status' => 404));
    }
}

?>