<?php 

add_action('rest_api_init', function () {
    // Create post
    register_rest_route('custom/v1', '/post', array(
        'methods' => 'POST',
        'callback' => 'create_post_custom',
        'permission_callback' => 'is_user_logged_in',
    ));
    // Get all posts
    register_rest_route('custom/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'get_blog_all_posts_custom',
        'permission_callback' => '__return_true', // Allow access to all users
    ));
    // Get post by ID
    register_rest_route('custom/v1', '/post/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_blog_post_custom',
        'permission_callback' => '__return_true', // Allow access to all users
    ));

    // Update post by ID
    register_rest_route('custom/v1', '/update-post/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_post_custom',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
        'show_in_index' => false,
    ));
    
    // Delete post by ID
    register_rest_route('custom/v1', '/post/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_post_custom',
        'permission_callback' => 'is_user_logged_in',
    ));
});

// Create post callback function
function create_post_custom($request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $category = sanitize_text_field($request->get_param('category')); // Get a single category from the request
    $tags = $request->get_param('tags'); // Get tags from the request
    $feature_image = $request->get_file_params()['file'];

    $post_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'post',
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        $image_upload_response = handle_image_upload($request);

        if (!is_wp_error($image_upload_response)) {
            set_post_thumbnail($post_id, $image_upload_response->data['image_id']);
        }

        // Handle single category
        if (!empty($category)) {
            $category_term = get_term_by('name', $category, 'category');
            if (!$category_term) {
                $new_category = wp_insert_term($category, 'category');
                if (!is_wp_error($new_category)) {
                    $category_id = $new_category['term_id'];
                }
            } else {
                $category_id = $category_term->term_id;
            }
            wp_set_post_categories($post_id, array($category_id));
        }

        // Handle tags
        if (!empty($tags)) {
            $tag_ids = array();
            foreach ($tags as $tag_name) {
                $tag = get_term_by('name', $tag_name, 'post_tag');
                if (!$tag) {
                    $new_tag = wp_insert_term($tag_name, 'post_tag');
                    if (!is_wp_error($new_tag)) {
                        $tag_ids[] = $new_tag['term_id'];
                    }
                } else {
                    $tag_ids[] = $tag->term_id;
                }
            }
            wp_set_post_tags($post_id, $tag_ids);
        }

        // Get the post date
        $post_date = get_the_date('', $post_id);

        return rest_ensure_response(array(
            'id' => $post_id,
            'title' => $title,
            'content' => $content,
            'date' => $post_date,
            'feature_image_url' => $image_upload_response->data['image_url'],
            'category' => $category,
            'tags' => $tags,
        ));
    } else {
        return new WP_Error('create_post_error', 'Error creating post', array('status' => 500));
    }
}



function get_blog_all_posts_custom($request) {
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Retrieve all posts
    );

    $query = new WP_Query($args);
    $posts = $query->posts;

    if (empty($posts)) {
        return new WP_REST_Response(array('message' => 'No posts found'), 404);
    }

    $response = array();

    foreach ($posts as $post) {
        $feature_image_url = get_the_post_thumbnail_url($post->ID);

        // Get single category
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $category = !empty($categories) ? $categories[0] : '';

        // Get tags
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));

        // Get the post date
        $post_date = get_the_date('', $post->ID);

        $response[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'feature_image_url' => $feature_image_url,
            'date' => $post_date,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'category' => $category,
            'tags' => $tags,
        );
    }

    return rest_ensure_response($response);
}

// Get post callback function
function get_blog_post_custom($request) {
    $id = (int) $request['id'];
    $post = get_post($id);

    if ($post && $post->post_type === 'post') {
        $feature_image_url = get_the_post_thumbnail_url($post->ID);

        // Get categories
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $category_name = !empty($categories) ? $categories[0] : ''; // Get the first category or set to empty

        // Get tags
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));

        // Get the post date
        $post_date = get_the_date('', $id);

        return rest_ensure_response(array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'feature_image_url' => $feature_image_url,
            'date' => $post_date,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'category' => $category_name, // Return single category
            'tags' => $tags,
        ));
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}


// Update post callback function
function update_post_custom($request) {
    $post_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $categories = $request->get_param('category'); // Get categories from the request
    $tags = $request->get_param('tags'); // Get tags from the request

    if (empty($title) || empty($content)) {
        return new WP_REST_Response(array('message' => 'Title or content is empty'), 400);
    }

    $files = $request->get_file_params();
    $file_path = '';
    $image_url = '';

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
            $image_url = wp_get_attachment_url($attach_id);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    } else {
        $image_url = wp_get_attachment_url(get_post_thumbnail_id($post_id));
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

    // Get the post date
    $post_date = get_the_date('', $post_id);

    // Handle categories
    $category_name = '';
    if (!empty($categories)) {
        $category_ids = array();
        foreach ($categories as $category_name) {
            $category = get_term_by('name', $category_name, 'category');
            if (!$category) {
                $new_category = wp_insert_term($category_name, 'category');
                if (!is_wp_error($new_category)) {
                    $category_ids[] = $new_category['term_id'];
                    $category_name = $category_name; // Set the first category name
                    break; // Use only the first category
                }
            } else {
                $category_ids[] = $category->term_id;
                $category_name = $category_name; // Set the first category name
                break; // Use only the first category
            }
        }
        wp_set_post_categories($post_id, $category_ids);
    }

    // Handle tags
    if (!empty($tags)) {
        $tag_ids = array();
        foreach ($tags as $tag_name) {
            $tag = get_term_by('name', $tag_name, 'post_tag');
            if (!$tag) {
                $new_tag = wp_insert_term($tag_name, 'post_tag');
                if (!is_wp_error($new_tag)) {
                    $tag_ids[] = $new_tag['term_id'];
                }
            } else {
                $tag_ids[] = $tag->term_id;
            }
        }
        wp_set_post_tags($post_id, $tag_ids);
    }

    return new WP_REST_Response(array(
        'message' => 'Post updated successfully',
        'id' => $post_id,
        'title' => $title,
        'content' => $content,
        'date' => $post_date,
        'feature_image_url' => $image_url,
        'category' => $category_name, // Return single category
        'tags' => $tags,
    ), 200);
}


// Delete post callback function
function delete_post_custom($request) {
    $id = (int) $request['id'];
    $post = get_post($id);

    if ($post && $post->post_type === 'post') {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $deleted = wp_delete_post($id, true);

        if ($deleted) {
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }

            return rest_ensure_response(array(
                'message' => 'Post deleted successfully',
                'id' => $id,
            ));
        } else {
            return new WP_Error('delete_post_error', 'Error deleting post', array('status' => 500));
        }
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}


?>