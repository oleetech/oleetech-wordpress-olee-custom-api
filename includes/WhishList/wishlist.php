<?php

// Register the 'wishlist' custom post type
function register_wishlist_post_type() {
    register_post_type('wishlist', array(
        'labels' => array(
            'name' => __('Wishlists', 'textdomain'),
            'singular_name' => __('Wishlist', 'textdomain'),
        ),
        'public' => true,
        'supports' => array('title'),
        'has_archive' => false,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'wishlists'),
        'show_in_menu' => 'olee-custom-api', // Add this line to set the parent menu
    ));
}
add_action('init', 'register_wishlist_post_type');

function add_wishlist_meta_boxes() {
    add_meta_box('wishlist_meta_box', 'Wishlist Details', 'wishlist_meta_box_callback', 'wishlist', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_wishlist_meta_boxes');

function wishlist_meta_box_callback($post) {
    // Retrieve existing values
    $user_id = get_post_meta($post->ID, '_wishlist_user_id', true);
    $learninghub_ids = get_post_meta($post->ID, '_wishlist_learninghub_ids', true);

    // Display meta box form
    ?>
    <label for="wishlist_user_id">User ID:</label>
    <input type="text" id="wishlist_user_id" name="wishlist_user_id" value="<?php echo esc_attr($user_id); ?>" />

    <label for="wishlist_learninghub_ids">LearningHub IDs (comma separated):</label>
    <input type="text" id="wishlist_learninghub_ids" name="wishlist_learninghub_ids" value="<?php echo esc_attr($learninghub_ids); ?>" />
    <?php
}

function save_wishlist_meta_box_data($post_id) {
    if (array_key_exists('wishlist_user_id', $_POST)) {
        update_post_meta($post_id, '_wishlist_user_id', sanitize_text_field($_POST['wishlist_user_id']));
    }
    if (array_key_exists('wishlist_learninghub_ids', $_POST)) {
        update_post_meta($post_id, '_wishlist_learninghub_ids', sanitize_text_field($_POST['wishlist_learninghub_ids']));
    }
}
add_action('save_post', 'save_wishlist_meta_box_data');
function set_wishlist_title($post_id) {
    if (get_post_type($post_id) === 'wishlist') {
        $user_id = get_post_meta($post_id, '_wishlist_user_id', true);
        $user = get_userdata($user_id);

        if ($user) {
            $username = $user->user_login;
            $post_title = $username . "'s Wishlist";

            $post_data = array(
                'ID' => $post_id,
                'post_title' => $post_title,
            );

            wp_update_post($post_data);
        }
    }
}
add_action('save_post', 'set_wishlist_title');

?>