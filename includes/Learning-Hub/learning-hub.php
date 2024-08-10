<?php
// Register Custom Post Types
function create_custom_post_types_learning_hub() {
    // Custom post type 'LearningHub'
    register_post_type('learninghub', array(
        'labels' => array(
            'name' => __('Learning Hubs', 'textdomain'),
            'singular_name' => __('Learning Hub', 'textdomain'),
        ),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'learninghubs'),
        'show_in_menu' => 'olee-custom-api', // Add this line to set the parent menu
    ));

    // Register custom taxonomy 'LearningHub Category'
    register_taxonomy('learninghub_category', 'learninghub', array(
        'labels' => array(
            'name' => __('LearningHub Categories', 'textdomain'),
            'singular_name' => __('LearningHub Category', 'textdomain'),
        ),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ));

    // Register custom taxonomy 'LearningHub Tag'
    register_taxonomy('learninghub_tag', 'learninghub', array(
        'labels' => array(
            'name' => __('LearningHub Tags', 'textdomain'),
            'singular_name' => __('LearningHub Tag', 'textdomain'),
        ),
        'public' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
    ));
}
add_action('init', 'create_custom_post_types_learning_hub');


// Add a custom meta box to store YouTube embed link
function add_learninghub_metabox() {
    add_meta_box(
        'learninghub_youtube_link',
        __('YouTube Embed Link', 'textdomain'),
        'learninghub_youtube_link_callback',
        'learninghub',
        'side'
    );
}
add_action('add_meta_boxes', 'add_learninghub_metabox');

function learninghub_youtube_link_callback($post) {
    wp_nonce_field('save_learninghub_youtube_link', 'learninghub_youtube_link_nonce');
    $value = get_post_meta($post->ID, '_learninghub_youtube_link', true);
    echo '<label for="learninghub_youtube_link">' . __('YouTube Embed Link', 'textdomain') . '</label>';
    echo '<input type="text" id="learninghub_youtube_link" name="learninghub_youtube_link" value="' . esc_attr($value) . '" size="25" />';
}

function save_learninghub_youtube_link($post_id) {
    if (!isset($_POST['learninghub_youtube_link_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['learninghub_youtube_link_nonce'], 'save_learninghub_youtube_link')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['learninghub_youtube_link'])) {
        $youtube_link = sanitize_text_field($_POST['learninghub_youtube_link']);
        update_post_meta($post_id, '_learninghub_youtube_link', $youtube_link);
    }
}
add_action('save_post', 'save_learninghub_youtube_link');

?>