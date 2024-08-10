<?php
function register_olee_slider_post_type() {
    $args = array(
        'label'                 => 'Sliders', // The name of the post type displayed in the WordPress admin
        'description'           => 'Custom post type for managing sliders', // Description of the post type
        'public'                => true, // Whether the post type is accessible on the front-end and in the admin
        'exclude_from_search'   => false, // Whether the post type is excluded from search results
        'publicly_queryable'    => true, // Whether queries can be made for posts of this type
        'show_ui'               => true, // Whether to generate a default UI for managing this post type in the admin
        'show_in_menu' => 'olee-custom-api', 
        'show_in_admin_bar'     => true, // Whether to show the post type in the admin bar
        'show_in_nav_menus'     => true, // Whether to show this post type in navigation menus
        'show_in_rest'          => true, // Whether to enable REST API support
        'rest_base'             => 'olee_slider', // Base route for REST API endpoints
        'menu_position'         => 5, // Position in the admin menu (5 is below 'Posts')
        'menu_icon'             => 'dashicons-format-image', // Icon for the post type in the admin menu
        'capability_type'       => 'post', // The type of capabilities this post type will use
        'hierarchical'          => false, // Whether the post type should be hierarchical (like pages)
        'supports'              => array(
            'title',          // Post title
            'editor',         // Post content editor
            'author',         // Post author
            'thumbnail',      // Featured image
            'excerpt',        // Post excerpt
            'comments',       // Post comments
            'revisions',      // Post revisions
            'custom-fields',  // Custom fields
        ),
        'taxonomies'            => array('category', 'post_tag'), // Taxonomies associated with this post type
        'has_archive'           => true, // Whether there should be an archive page for this post type
        'rewrite'               => array('slug' => 'olee-slider'), // Rewrite rules for the post type
        'query_var'             => true, // Whether this post type can be queried via query variables
        'can_export'            => true, // Whether to allow exporting this post type
        'rest_controller_class' => 'WP_REST_Posts_Controller', // REST controller class for this post type
    );
    register_post_type('olee_slider', $args);
}
add_action('init', 'register_olee_slider_post_type');


?>