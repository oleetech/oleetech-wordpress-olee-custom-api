<?php
// Define the path to slider.php
$slider_file_path = __DIR__ . '/../slider.php';

// Check if the file exists before including it
if (file_exists($slider_file_path)) {
    require_once $slider_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file slider.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file slider.php does not exist', array('status' => 500));
}

add_action('rest_api_init', function () {
    // Create post
    register_rest_route('custom/v1', '/olee-slider', array(
        'methods' => 'POST',
        'callback' => 'create_olee_slider_post',
        'permission_callback' => 'is_user_logged_in',
    ));

    // Get all posts
    register_rest_route('custom/v1', '/olee-sliders', array(
        'methods' => 'GET',
        'callback' => 'get_all_olee_slider_posts',
        'permission_callback' => '__return_true',
    ));

    // Get post by ID
    register_rest_route('custom/v1', '/olee-slider/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_olee_slider_post',
        'permission_callback' => '__return_true',
    ));

    // Update post by ID
    register_rest_route('custom/v1', '/olee-slider/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'update_olee_slider_post',
        'permission_callback' => 'is_user_logged_in',
    ));

    // Delete post by ID
    register_rest_route('custom/v1', '/olee-slider/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_olee_slider_post',
        'permission_callback' => 'is_user_logged_in',
    ));
});


function create_olee_slider_post($request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $feature_image = $request->get_file_params()['file'];

    $post_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'olee_slider',
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        if (!empty($feature_image)) {
            $image_upload_response = handle_image_upload($request);

            if (!is_wp_error($image_upload_response)) {
                set_post_thumbnail($post_id, $image_upload_response->data['image_id']);
            }
        }

        return rest_ensure_response(array(
            'id' => $post_id,
            'title' => $title,
            'content' => $content,
            'feature_image_url' => isset($image_upload_response->data['image_url']) ? $image_upload_response->data['image_url'] : '',
        ));
    } else {
        return new WP_Error('create_post_error', 'Error creating post', array('status' => 500));
    }
}

function get_all_olee_slider_posts($request) {
    $args = array(
        'post_type' => 'olee_slider',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $posts = $query->posts;

    $response = array();

    foreach ($posts as $post) {
        $feature_image_url = get_the_post_thumbnail_url($post->ID);

        $response[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'feature_image_url' => $feature_image_url,
        );
    }

    return rest_ensure_response($response);
}

function get_olee_slider_post($request) {
    $id = (int) $request['id'];
    $post = get_post($id);

    if ($post && $post->post_type === 'olee_slider') {
        $feature_image_url = get_the_post_thumbnail_url($post->ID);

        return rest_ensure_response(array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'feature_image_url' => $feature_image_url,
        ));
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}


function update_olee_slider_post($request) {
    $post_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $files = $request->get_file_params();
    $file_url = '';

    if (!empty($files['file'])) {
        $file = $files['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error']) && $upload['error'] !== false) {
            return new WP_REST_Response(array('message' => $upload['error']), 500);
        }

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
            $prev_thumbnail_id = get_post_thumbnail_id($post_id);
            if ($prev_thumbnail_id) {
                wp_delete_attachment($prev_thumbnail_id, true);
            }

            set_post_thumbnail($post_id, $attach_id);
            $file_url = wp_get_attachment_url($attach_id);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    } else {
        $file_url = wp_get_attachment_url(get_post_thumbnail_id($post_id));
    }

    $updated_post = array(
        'ID' => $post_id,
        'post_title' => $title,
        'post_content' => $content,
    );

    $post_updated = wp_update_post($updated_post, true);

    if (is_wp_error($post_updated)) {
        return new WP_REST_Response(array('message' => $post_updated->get_error_message()), 500);
    }

    return new WP_REST_Response(array(
        'message' => 'Post updated successfully',
        'id' => $post_id,
        'title' => $title,
        'content' => $content,
        'feature_image_url' => $file_url,
    ), 200);
}


function delete_olee_slider_post($request) {
    $id = (int) $request['id'];
    $post = get_post($id);

    if ($post && $post->post_type === 'olee_slider') {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $deleted = wp_delete_post($id, true);

        if ($deleted) {
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }

            return rest_ensure_response(array(
                'message' => 'Post deleted successfully',
                'post_id' => $id,
            ));
        } else {
            return new WP_Error('delete_post_error', 'Error deleting post', array('status' => 500));
        }
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}

?>