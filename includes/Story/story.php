<?php
//   ____    _                            
//  / ___|  | |_    ___    _ __   _   _   
//  \___ \  | __|  / _ \  | '__| | | | |  
//   ___) | | |_  | (_) | | |    | |_| |  
//  |____/   \__|  \___/  |_|     \__, |  
//                                |___/   
//

// Register Custom Post Types
function create_custom_post_types() {
    // Custom post type 'Story'
    register_post_type('story', array(
        'labels' => array(
            'name' => __('Stories', 'textdomain'),
            'singular_name' => __('Story', 'textdomain'),
        ),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'stories'),
        'show_in_menu' => 'olee-custom-api', // Add this line to set the parent menu

    ));


}
add_action('init', 'create_custom_post_types');
?>