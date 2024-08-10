<?php
// Define the path to learning-hub.php
$learning_hub_file_path = __DIR__ . '/../learning-hub.php';

// Check if the file exists before including it
if (file_exists($learning_hub_file_path)) {
    require_once $learning_hub_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file learning-hub.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file learning-hub.php does not exist', array('status' => 500));
}


// Add REST API routes for LearningHub
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/learninghub', array(
        'methods' => 'POST',
        'callback' => 'create_learninghub',
        'permission_callback' => 'is_user_logged_in',
    ));
    register_rest_route('custom/v1', '/learninghub/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_learninghub',
        'permission_callback' => '__return_true', // Publicly accessible
    ));
    register_rest_route('custom/v1', '/learninghub', array(
        'methods' => 'GET',
        'callback' => 'get_all_learninghub',
        'permission_callback' => '__return_true', // Publicly accessible
    ));
    register_rest_route('custom/v1', '/learninghub/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_learninghub',
         'permission_callback' => '__return_true', // Publicly accessible
    ));

    register_rest_route('custom/v1', '/learninghub/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_learninghub',
        'permission_callback' => 'is_user_logged_in',
    ));
});


// Function to create LearningHub post
function create_learninghub(WP_REST_Request $request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $youtube_link = sanitize_text_field($request->get_param('youtube_link'));
    $category_name = sanitize_text_field($request->get_param('category'));
    $tags = $request->get_param('tags'); // Assuming tags are sent as an array

    // If category name is not provided, use a default category
    if (empty($category_name)) {
        $category_name = 'Default Category'; // Set your default category name here
    }

    // Check if category exists, if not, create it
    $category = get_term_by('name', $category_name, 'learninghub_category');
    if (!$category) {
        $new_category = wp_insert_term($category_name, 'learninghub_category');
        if (is_wp_error($new_category)) {
            return new WP_Error('category_creation_failed', 'Failed to create category', array('status' => 500));
        }
        $category_id = $new_category['term_id'];
    } else {
        $category_id = $category->term_id;
    }

    // Prepare post data array
    $post_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'learninghub',
    );

    // Create the LearningHub post
    $post_id = wp_insert_post($post_data);

    // If post creation is successful
    if ($post_id && !is_wp_error($post_id)) {
        // Set category
        wp_set_post_terms($post_id, array($category_id), 'learninghub_category');

        // Set tags
        if (!empty($tags)) {
            wp_set_post_terms($post_id, $tags, 'learninghub_tag');
        }

        // Upload the feature image
        $files = $request->get_file_params();
        $image_url = '';
        if (!empty($files['file'])) {
            $file = $files['file'];
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (!isset($upload['error'])) {
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
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $file_path));
                    set_post_thumbnail($post_id, $attach_id);
                    $image_url = wp_get_attachment_url($attach_id);
                }
            }
        }

        // Save YouTube embed link as post meta
        update_post_meta($post_id, '_learninghub_youtube_link', $youtube_link);
        // Get the post author's display name
        $author_name = get_the_author_meta('display_name', get_post_field('post_author', $post_id));

        // Return the post ID and other data
        return rest_ensure_response(array(
            'id' => $post_id,
            'title' => $title,
            'content' => $content,
            'feature_image_url' => $image_url,
            'youtube_link' => $youtube_link,
            'category' => $category_name,
            'tags' => $tags,
			 'author' => $author_name,
             'date' => get_the_date('', $post_id),
        ));
    } else {
        return new WP_Error('create_post_error', 'Error creating LearningHub post', array('status' => 500));
    }
}


function get_learninghub($request) {
    $id = (int) $request['id'];
    $learninghub = get_post($id);

    if ($learninghub && $learninghub->post_type === 'learninghub') {
        $youtube_link = get_post_meta($learninghub->ID, '_learninghub_youtube_link', true);

        // Get categories for the post
        $categories = wp_get_post_terms($learninghub->ID, 'learninghub_category', array('orderby' => 'term_id', 'order' => 'DESC'));
        $category_name = !empty($categories) ? $categories[0]->name : '';

        // Get tags for the post
        $tags = wp_get_post_terms($learninghub->ID, 'learninghub_tag', array('orderby' => 'term_id', 'order' => 'DESC'));
        $tag_names = !empty($tags) ? wp_list_pluck($tags, 'name') : [];
        // Get the post author's display name
        $author_name = get_the_author_meta('display_name', $learninghub->post_author);
        return rest_ensure_response(array(
            'id' => $learninghub->ID,
            'title' => $learninghub->post_title,
            'content' => $learninghub->post_content,
            'youtube_link' => $youtube_link,
            'category' => $category_name,
            'tags' => $tag_names,
			'author' => $author_name ,
            'date' => get_the_date('', $learninghub->ID),
        ));
    } else {
        return new WP_Error('learninghub_not_found', 'Learning Hub not found', array('status' => 404));
    }
}





// Function to fetch all LearningHub posts
function get_all_learninghub() {
    // Query to get all learninghub posts
    $args = array(
        'post_type' => 'learninghub',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    // Initialize an empty array to hold the data
    $posts_data = array();

    // Loop through the posts
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_title = get_the_title();
            $post_content = get_the_content();
            $youtube_link = get_post_meta($post_id, '_learninghub_youtube_link', true);
            $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');

            // Get categories for the post
            $categories = wp_get_post_terms($post_id, 'learninghub_category', array('orderby' => 'term_id', 'order' => 'DESC'));
            $category_name = !empty($categories) ? $categories[0]->name : '';

            // Get tags for the post
            $tags = wp_get_post_terms($post_id, 'learninghub_tag', array('orderby' => 'term_id', 'order' => 'DESC'));
            $tag_names = !empty($tags) ? wp_list_pluck($tags, 'name') : [];

            // Append post data to the array
            $posts_data[] = array(
                'id' => $post_id,
                'title' => $post_title,
                'content' => $post_content,
                'youtube_link' => $youtube_link,
                'featured_image_url' => $featured_image_url,
                'category' => $category_name,
                'tags' => $tag_names,
				'author' => get_the_author(),
				 'date' => get_the_date('', $post_id),
            );
        }
    }

    // Reset post data
    wp_reset_postdata();

    // Return the data
    return rest_ensure_response($posts_data);
}



// Function to update LearningHub post
function update_learninghub(WP_REST_Request $request) {
    $post_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $youtube_link = sanitize_text_field($request->get_param('youtube_link'));
    $category_name = sanitize_text_field($request->get_param('category'));
    $tags = $request->get_param('tags'); // Assuming tags are sent as an array

    // Check if title or content is empty
    if (empty($title) || empty($content)) {
        return new WP_REST_Response(array('message' => 'Title or content is empty'), 400);
    }

    // Handle file upload if a new file is provided
    $files = $request->get_file_params();
    $file_path = '';

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
            $prev_thumbnail_id = get_post_thumbnail_id($post_id);
            if ($prev_thumbnail_id) {
                wp_delete_attachment($prev_thumbnail_id, true);
            }

            // Set the new post thumbnail (featured image)
            set_post_thumbnail($post_id, $attach_id);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    }

    // Check if category exists, if not, create it
    $category = get_term_by('name', $category_name, 'learninghub_category');
    if (!$category) {
        $new_category = wp_insert_term($category_name, 'learninghub_category');
        if (is_wp_error($new_category)) {
            return new WP_Error('category_creation_failed', 'Failed to create category', array('status' => 500));
        }
        $category_id = $new_category['term_id'];
    } else {
        $category_id = $category->term_id;
    }

    // Update post's title, content, and category
    $update_post = array(
        'ID' => $post_id,
        'post_title' => $title,
        'post_content' => $content,
    );

    $updated_post_id = wp_update_post($update_post);

    if (is_wp_error($updated_post_id)) {
        return new WP_REST_Response(array('message' => 'Failed to update LearningHub post'), 500);
    }

    // Set category
    wp_set_post_terms($post_id, array($category_id), 'learninghub_category');

    // Set tags
    if (!empty($tags)) {
        wp_set_post_terms($post_id, $tags, 'learninghub_tag');
    }

    // Save YouTube embed link as post meta
    update_post_meta($post_id, '_learninghub_youtube_link', $youtube_link);

    return new WP_REST_Response(array(
        'message' => 'LearningHub post updated successfully',
        'id' => $updated_post_id,
        'title' => $title,
        'content' => $content,
        'file_uploaded' => $file_path,
        'youtube_link' => $youtube_link,
        'category' => $category_name,
        'tags' => $tags,
    ), 200);
}


function delete_learninghub($request) {
    $id = (int) $request['id'];
    $learninghub = get_post($id);

    if ($learninghub && $learninghub->post_type === 'learninghub') {
        $deleted = wp_delete_post($id, true);

        if ($deleted) {
            return rest_ensure_response(array(
                'message' => 'Learning Hub deleted successfully',
                'id' => $id,
            ));
        } else {
            return new WP_Error('delete_learninghub_error', 'Error deleting Learning Hub', array('status' => 500));
        }
    } else {
        return new WP_Error('learninghub_not_found', 'Learning Hub not found', array('status' => 404));
    }
}
?>