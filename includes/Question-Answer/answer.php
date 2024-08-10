<?php
//      _                                                  
//     / \     _ __    ___  __      __   ___   _ __    ___ 
//    / _ \   | '_ \  / __| \ \ /\ / /  / _ \ | '__|  / _ \
//   / ___ \  | | | | \__ \  \ V  V /  |  __/ | |    |  __/
//  /_/   \_\ |_| |_| |___/   \_/\_/    \___| |_|     \___|
//                                                        




// Register Answer Post Type
function register_answer_post_type() {
    register_post_type('answer', array(
        'labels' => array(
            'name' => __('Answers'),
            'singular_name' => __('Answer'),
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'answers'),
        'supports' => array(
            'title',
            'editor',
            'author',
            'thumbnail', // Featured image
            'excerpt',
            'comments',
            'trackbacks',
            'custom-fields',
            'revisions',
            'page-attributes',
            'post-formats'
        ),
        'taxonomies' => array('category', 'post_tag'), // Enable categories and tags
        'show_in_rest' => true, // Enable REST API support
        'show_in_menu' => 'olee-custom-api', 

    ));
}
add_action('init', 'register_answer_post_type');



function add_answer_meta_box() {
    add_meta_box('answer_meta_box', 'Answer Details', 'render_answer_meta_box', 'answer', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_answer_meta_box');

function render_answer_meta_box($post) {
    $user_id = get_post_meta($post->ID, 'user_id', true);
    $question_id = get_post_meta($post->ID, 'question_id', true);
    ?>
    <label for="user_id">User ID:</label>
    <input type="text" id="user_id" name="user_id" value="<?php echo esc_attr($user_id); ?>" disabled><br>
    <label for="question_id">Question ID:</label>
    <input type="text" id="question_id" name="question_id" value="<?php echo esc_attr($question_id); ?>" disabled><br>
    <?php
}



function save_answer_meta_box_data($post_id) {
    if (array_key_exists('user_id', $_POST)) {
        update_post_meta($post_id, 'user_id', sanitize_text_field($_POST['user_id']));
    }
    if (array_key_exists('question_id', $_POST)) {
        update_post_meta($post_id, 'question_id', sanitize_text_field($_POST['question_id']));
    }
}
add_action('save_post', 'save_answer_meta_box_data');


?>