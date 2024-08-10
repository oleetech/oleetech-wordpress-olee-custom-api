<?php

// Define the path to wishlist.php
$wishlist_file_path = __DIR__ . '/../wishlist.php';

// Check if the file exists before including it
if (file_exists($wishlist_file_path)) {
    require_once $wishlist_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file wishlist.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file wishlist.php does not exist', array('status' => 500));
}



// Register REST API routes
function register_wishlist_api_routes() {
    // Route for adding to wishlist
    register_rest_route('custom/v1', '/add-to-wishlist', array(
        'methods' => 'POST',
        'callback' => 'handle_add_to_wishlist',
        'permission_callback' => function () {
            return is_user_logged_in(); // Ensure user is logged in
        },
    ));

    // Route for removing from wishlist
    register_rest_route('custom/v1', '/remove-from-wishlist', array(
        'methods' => 'POST',
        'callback' => 'handle_remove_from_wishlist',
        'permission_callback' => function () {
            return is_user_logged_in(); // Ensure user is logged in
        },
    ));

    // Route for getting learning hubs
    register_rest_route('custom/v1', '/get-learning-hubs', array(
        'methods' => 'GET',
        'callback' => 'handle_get_learning_hubs',
        'permission_callback' => function () {
            return is_user_logged_in(); // Ensure user is logged in
        },
    ));
}
add_action('rest_api_init', 'register_wishlist_api_routes');

function get_single_learning_hub_response($wishlist_id, $learninghub_id) {
    $post = get_post($learninghub_id);
    if (!$post) {
        return new WP_Error('not_found', 'Learning Hub not found', array('status' => 404));
    }

    $categories = wp_get_post_terms($learninghub_id, 'learninghub_category', array('orderby' => 'term_id', 'order' => 'DESC'));
    $category_name = !empty($categories) ? $categories[0]->name : '';
    $tags = wp_get_post_terms($learninghub_id, 'learninghub_tag', array('orderby' => 'term_id', 'order' => 'DESC'));
    $tag_names = !empty($tags) ? wp_list_pluck($tags, 'name') : [];

    $learning_hub = array(
        'wishlist_id' => $wishlist_id,
        'id' => $learninghub_id,
        'title' => get_the_title($learninghub_id),
        'content' => get_the_content(null, false, $learninghub_id),
        'youtube_link' => get_post_meta($learninghub_id, '_learninghub_youtube_link', true),
        'category' => $category_name,
        'tags' => $tag_names,
        'author' => get_the_author_meta('display_name', $post->post_author),
        'date' => get_the_date('', $learninghub_id),
    );

    return rest_ensure_response($learning_hub);
}


// Handle adding to the wishlist
function handle_add_to_wishlist(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_Error('unauthorized', 'You are not authorized to perform this action', array('status' => 401));
    }

    $user_id = get_current_user_id();
    $learninghub_id = $request->get_param('learninghub_id');

    $args = array(
        'post_type' => 'wishlist',
        'meta_query' => array(
            array(
                'key' => '_wishlist_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    );
    $existing_wishlists = get_posts($args);

    if (empty($existing_wishlists)) {
        $wishlist_post = array(
            'post_title' => get_userdata($user_id)->user_login . ' Wishlist',
            'post_type' => 'wishlist',
            'post_status' => 'publish',
            'meta_input' => array(
                '_wishlist_user_id' => $user_id,
                '_wishlist_learninghub_ids' => $learninghub_id
            )
        );
        $wishlist_id = wp_insert_post($wishlist_post);
    } else {
        $wishlist_id = $existing_wishlists[0]->ID;
        $learninghub_ids = get_post_meta($wishlist_id, '_wishlist_learninghub_ids', true);
        $learninghub_ids = explode(',', $learninghub_ids);
        
        if (!in_array($learninghub_id, $learninghub_ids)) {
            $learninghub_ids[] = $learninghub_id;
            update_post_meta($wishlist_id, '_wishlist_learninghub_ids', implode(',', $learninghub_ids));
        } else {
            return new WP_Error('already_in_wishlist', 'Learning Hub is already in the wishlist', array('status' => 400));
        }
    }

    return get_single_learning_hub_response($wishlist_id, $learninghub_id);
}


// Handle removing from the wishlist
function handle_remove_from_wishlist(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_Error('unauthorized', 'You are not authorized to perform this action', array('status' => 401));
    }

    $user_id = get_current_user_id();
    $learninghub_id = $request->get_param('learninghub_id');

    $args = array(
        'post_type' => 'wishlist',
        'meta_query' => array(
            array(
                'key' => '_wishlist_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    );
    $existing_wishlists = get_posts($args);

    if (empty($existing_wishlists)) {
        return new WP_Error('wishlist_not_found', 'No wishlist found for this user', array('status' => 404));
    }

    $wishlist_id = $existing_wishlists[0]->ID;
    $learninghub_ids = get_post_meta($wishlist_id, '_wishlist_learninghub_ids', true);
    $learninghub_ids = explode(',', $learninghub_ids);

    if (in_array($learninghub_id, $learninghub_ids)) {
        $learninghub_ids = array_diff($learninghub_ids, array($learninghub_id));
        update_post_meta($wishlist_id, '_wishlist_learninghub_ids', implode(',', $learninghub_ids));
    } else {
        return new WP_Error('not_in_wishlist', 'Learning Hub is not in the wishlist', array('status' => 400));
    }

    // Prepare the response
    $response = get_single_learning_hub_response($wishlist_id, $learninghub_id);

    // If response is empty, return an empty array
    if (empty($response)) {
        return array();
    }

    return $response;
}


// Get Learning Hubs from Wishlist
// function handle_get_learning_hubs(WP_REST_Request $request) {
//     if (!is_user_logged_in()) {
//         return new WP_Error('unauthorized', 'You are not authorized to perform this action', array('status' => 401));
//     }

//     $user_id = get_current_user_id();

//     $args = array(
//         'post_type' => 'wishlist',
//         'meta_query' => array(
//             array(
//                 'key' => '_wishlist_user_id',
//                 'value' => $user_id,
//                 'compare' => '='
//             )
//         )
//     );
//     $existing_wishlists = get_posts($args);

//     if (empty($existing_wishlists)) {
//         return new WP_Error('wishlist_not_found', 'No wishlist found for this user', array('status' => 404));
//     }

//     $post_id = $existing_wishlists[0]->ID;
//     $learninghub_ids = get_post_meta($post_id, '_wishlist_learninghub_ids', true);
//     $learninghub_ids = explode(',', $learninghub_ids);

//     $query_args = array(
//         'post_type' => 'learninghub',
//         'post__in' => $learninghub_ids,
//         'posts_per_page' => -1,
//     );
//     $query = new WP_Query($query_args);

//     $learning_hubs = array();
//     if ($query->have_posts()) {
//         while ($query->have_posts()) {
//             $query->the_post();
//             $categories = wp_get_post_terms(get_the_ID(), 'learninghub_category', array('orderby' => 'term_id', 'order' => 'DESC'));
//             $category_name = !empty($categories) ? $categories[0]->name : '';
//             $tags = wp_get_post_terms(get_the_ID(), 'learninghub_tag', array('orderby' => 'term_id', 'order' => 'DESC'));
//             $tag_names = !empty($tags) ? wp_list_pluck($tags, 'name') : [];

//             $learning_hubs[] = array(
//                 'id' => get_the_ID(),
//                 'title' => get_the_title(),
//                 'content' => get_the_content(),
//                 'youtube_link' => get_post_meta(get_the_ID(), '_learninghub_youtube_link', true),
//                 'category' => $category_name,
//                 'tags' => $tag_names,
//                 'author' => get_the_author(),
//                 'date' => get_the_date('', get_the_ID()),
//             );
//         }
//         wp_reset_postdata();
//     }

//     return rest_ensure_response(array(
//         'success' => true,
//         'learning_hubs' => $learning_hubs
//     ));
// }
// 
// 

function handle_get_learning_hubs(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_Error('unauthorized', 'You are not authorized to perform this action', array('status' => 401));
    }

    $user_id = get_current_user_id();

    $args = array(
        'post_type' => 'wishlist',
        'meta_query' => array(
            array(
                'key' => '_wishlist_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    );
    $existing_wishlists = get_posts($args);

    if (empty($existing_wishlists)) {
        return new WP_Error('wishlist_not_found', 'No wishlist found for this user', array('status' => 404));
    }

    $post_id = $existing_wishlists[0]->ID;
    $learninghub_ids = get_post_meta($post_id, '_wishlist_learninghub_ids', true);
    $learninghub_ids = explode(',', $learninghub_ids);

    $query_args = array(
        'post_type' => 'learninghub',
        'post__in' => $learninghub_ids,
        'posts_per_page' => -1,
    );
    $query = new WP_Query($query_args);

    $learning_hubs = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $categories = wp_get_post_terms(get_the_ID(), 'learninghub_category', array('orderby' => 'term_id', 'order' => 'DESC'));
            $category_name = !empty($categories) ? $categories[0]->name : '';
            $tags = wp_get_post_terms(get_the_ID(), 'learninghub_tag', array('orderby' => 'term_id', 'order' => 'DESC'));
            $tag_names = !empty($tags) ? wp_list_pluck($tags, 'name') : [];

            $learning_hubs[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'content' => get_the_content(),
                'youtube_link' => get_post_meta(get_the_ID(), '_learninghub_youtube_link', true),
                'category' => $category_name,
                'tags' => $tag_names,
                'author' => get_the_author(),
                'date' => get_the_date('', get_the_ID()),
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($learning_hubs);
}

?>