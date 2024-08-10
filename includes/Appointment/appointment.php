<?php

//      _                              _           _                                _   
//     / \     _ __    _ __     ___   (_)  _ __   | |_   _ __ ___     ___   _ __   | |_ 
//    / _ \   | '_ \  | '_ \   / _ \  | | | '_ \  | __| | '_ ` _ \   / _ \ | '_ \  | __|
//   / ___ \  | |_) | | |_) | | (_) | | | | | | | | |_  | | | | | | |  __/ | | | | | |_ 
//  /_/   \_\ | .__/  | .__/   \___/  |_| |_| |_|  \__| |_| |_| |_|  \___| |_| |_|  \__|
//            |_|     |_|                                                               
//


// Custom Post Type Creation for Appointments
function create_appointment_cpt() {
    $labels = array(
        'name' => __('Appointments', 'Post Type General Name'),
        'singular_name' => __('Appointment', 'Post Type Singular Name'),
        'menu_name' => __('Appointments'),
        'all_items' => __(' Appointments'),
        'add_new_item' => __('Add New Appointment'),
        'edit_item' => __('Edit Appointment'),
        'new_item' => __('New Appointment'),
        'view_item' => __('View Appointment'),
        'search_items' => __('Search Appointments'),
        'not_found' => __('Not Found'),
        'not_found_in_trash' => __('Not Found in Trash')
    );

    $args = array(
        'label' => __('Appointments'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'custom-fields', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-calendar-alt',
        'has_archive' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'appointments'),
        'show_in_menu' => 'olee-custom-api', 
        'query_var' => true,
        'taxonomies' => array('category', 'post_tag')
    );

    register_post_type('appointment', $args);
}

add_action('init', 'create_appointment_cpt');

// Adding Custom Meta Box
function appointment_custom_meta_boxes() {
    add_meta_box(
        'appointment_details', // Meta Box ID
        __('Appointment Details'), // Meta Box Title
        'appointment_details_callback', // Callback function to display content in meta box
        'appointment' // Post type where meta box will be displayed
    );
}
add_action('add_meta_boxes', 'appointment_custom_meta_boxes');

// Callback function to display content in meta box
function appointment_details_callback($post) {
    wp_nonce_field(basename(__FILE__), 'appointment_nonce'); // Create nonce field for security check

    $appointment_stored_meta = get_post_meta($post->ID); // Fetch post meta information
    ?>

    <!-- Date Input Field -->
    <label for="appointment_date"><?php _e('Date', 'your-textdomain'); ?></label>
    <input type="date" name="appointment_date" id="appointment_date" value="<?php if (isset($appointment_stored_meta['appointment_date'])) echo $appointment_stored_meta['appointment_date'][0]; ?>" />

    <!-- Time Input Field -->
    <label for="appointment_time"><?php _e('Time', 'your-textdomain'); ?></label>
    <input type="time" name="appointment_time" id="appointment_time" value="<?php if (isset($appointment_stored_meta['appointment_time'])) echo $appointment_stored_meta['appointment_time'][0]; ?>" />

    <!-- Name Input Field -->
    <label for="appointment_name"><?php _e('Name', 'your-textdomain'); ?></label>
    <input type="text" name="appointment_name" id="appointment_name" value="<?php if (isset($appointment_stored_meta['appointment_name'])) echo $appointment_stored_meta['appointment_name'][0]; ?>" />

    <!-- Email Input Field -->
    <label for="appointment_email"><?php _e('Email', 'your-textdomain'); ?></label>
    <input type="email" name="appointment_email" id="appointment_email" value="<?php if (isset($appointment_stored_meta['appointment_email'])) echo $appointment_stored_meta['appointment_email'][0]; ?>" />

    <!-- Phone Input Field -->
    <label for="appointment_phone"><?php _e('Phone', 'your-textdomain'); ?></label>
    <input type="text" name="appointment_phone" id="appointment_phone" value="<?php if (isset($appointment_stored_meta['appointment_phone'])) echo $appointment_stored_meta['appointment_phone'][0]; ?>" />
    
    <!-- Appointment Status Input Field -->
    <label for="appointment_status"><?php _e('Appointment Status', 'your-textdomain'); ?></label>
    <input type="text" name="appointment_status" id="appointment_status" value="<?php if (isset($appointment_stored_meta['appointment_status'])) echo $appointment_stored_meta['appointment_status'][0]; ?>" />
    <?php
}

// Save Meta Information
function save_appointment_meta($post_id) {
    if (!isset($_POST['appointment_nonce']) || !wp_verify_nonce($_POST['appointment_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['appointment_date'])) {
        update_post_meta($post_id, 'appointment_date', sanitize_text_field($_POST['appointment_date']));
    }

    if (isset($_POST['appointment_time'])) {
        update_post_meta($post_id, 'appointment_time', sanitize_text_field($_POST['appointment_time']));
    }

    if (isset($_POST['appointment_name'])) {
        update_post_meta($post_id, 'appointment_name', sanitize_text_field($_POST['appointment_name']));
    }

    if (isset($_POST['appointment_email'])) {
        update_post_meta($post_id, 'appointment_email', sanitize_email($_POST['appointment_email']));
    }

    if (isset($_POST['appointment_phone'])) {
        update_post_meta($post_id, 'appointment_phone', sanitize_text_field($_POST['appointment_phone']));
    }

    if (isset($_POST['appointment_status'])) {
        update_post_meta($post_id, 'appointment_status', sanitize_text_field($_POST['appointment_status']));
    }
}
add_action('save_post', 'save_appointment_meta');

?>