<?php

// Custom Post Type Creation for Job Circular
function job_circular_custom_post_type() {
    $labels = array(
        'name' => __('Job Circulars', 'textdomain'),
        'singular_name' => __('Job Circular', 'textdomain'),
        'menu_name' => __('Job Circulars'),
        'all_items' => __('All Job Circulars'),
        'add_new_item' => __('Add New Job Circular'),
        'edit_item' => __('Edit Job Circular'),
        'new_item' => __('New Job Circular'),
        'view_item' => __('View Job Circular'),
        'search_items' => __('Search Job Circulars'),
        'not_found' => __('No Job Circulars Found'),
        'not_found_in_trash' => __('No Job Circulars Found in Trash'),
    );

    $args = array(
        'label' => __('Job Circulars'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'revisions'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-portfolio',
        'has_archive' => true,
        'rewrite' => array('slug' => 'job-circulars'),
        'show_in_menu' => 'olee-custom-api',
        'taxonomies' => array('category', 'post_tag'),
    );

    register_post_type('job_circular', $args);
}
add_action('init', 'job_circular_custom_post_type');

// Adding Custom Meta Box for Job Circular
function job_circular_custom_meta_boxes() {
    add_meta_box(
        'job_details', // Meta Box ID
        __('Job Details', 'textdomain'), // Meta Box Title
        'job_details_callback', // Callback function to display content
        'job_circular' // Post type
    );
}
add_action('add_meta_boxes', 'job_circular_custom_meta_boxes');

// Callback function to display content in the Meta Box
function job_details_callback($post) {
    wp_nonce_field(basename(__FILE__), 'job_nonce'); // Nonce for security

    $stored_meta = get_post_meta($post->ID); // Get stored metadata

    // Set full-width style for input and textarea fields
    $full_width_style = 'style="width: 100%;"';

    ?>

    <!-- Application Deadline Field -->
    <p>
        <label for="application_deadline"><?php _e('Application Deadline', 'textdomain'); ?></label><br />
        <input type="text" name="application_deadline" id="application_deadline" value="<?php if (isset($stored_meta['application_deadline'])) echo esc_attr($stored_meta['application_deadline'][0]); ?>" <?php echo $full_width_style; ?> />
    </p>

    <!-- Shortlist Field -->
    <p>
        <label for="shortlist"><?php _e('Shortlist (comma-separated)', 'textdomain'); ?></label><br />
        <textarea name="shortlist" id="shortlist" rows="4" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['shortlist'])) echo esc_textarea(implode(', ', $stored_meta['shortlist'])); ?></textarea>
    </p>

    <!-- Job Requirements Field -->
    <p>
        <label for="job_requirements"><?php _e('Requirements', 'textdomain'); ?></label><br />
        <textarea name="job_requirements" id="job_requirements" rows="10" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['job_requirements'])) echo esc_textarea($stored_meta['job_requirements'][0]); ?></textarea>
    </p>

    <!-- Job Responsibilities Field -->
    <p>
        <label for="job_responsibilities"><?php _e('Responsibilities', 'textdomain'); ?></label><br />
        <textarea name="job_responsibilities" id="job_responsibilities" rows="10" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['job_responsibilities'])) echo esc_textarea($stored_meta['job_responsibilities'][0]); ?></textarea>
    </p>

    <!-- Job Skills Field -->
    <p>
        <label for="job_skills"><?php _e('Skills', 'textdomain'); ?></label><br />
        <textarea name="job_skills" id="job_skills" rows="10" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['job_skills'])) echo esc_textarea($stored_meta['job_skills'][0]); ?></textarea>
    </p>

    <!-- Compensation and Benefits Field -->
    <p>
        <label for="compensation_benefits"><?php _e('Compensation & Benefits', 'textdomain'); ?></label><br />
        <textarea name="compensation_benefits" id="compensation_benefits" rows="10" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['compensation_benefits'])) echo esc_textarea($stored_meta['compensation_benefits'][0]); ?></textarea>
    </p>

    <!-- Workplace Field -->
    <p>
        <label for="workplace"><?php _e('Workplace', 'textdomain'); ?></label><br />
        <input type="text" name="workplace" id="workplace" value="<?php if (isset($stored_meta['workplace'])) echo esc_attr($stored_meta['workplace'][0]); ?>" <?php echo $full_width_style; ?> />
    </p>

    <!-- Employment Status Field -->
    <p>
        <label for="employment_status"><?php _e('Employment Status', 'textdomain'); ?></label><br />
        <input type="text" name="employment_status" id="employment_status" value="<?php if (isset($stored_meta['employment_status'])) echo esc_attr($stored_meta['employment_status'][0]); ?>" <?php echo $full_width_style; ?> />
    </p>

    <!-- Job Location Field -->
    <p>
        <label for="job_location"><?php _e('Job Location', 'textdomain'); ?></label><br />
        <input type="text" name="job_location" id="job_location" value="<?php if (isset($stored_meta['job_location'])) echo esc_attr($stored_meta['job_location'][0]); ?>" <?php echo $full_width_style; ?> />
    </p>

    <!-- Company Information Field -->
    <p>
        <label for="company_information"><?php _e('Company Information', 'textdomain'); ?></label><br />
        <textarea name="company_information" id="company_information" rows="10" <?php echo $full_width_style; ?>><?php if (isset($stored_meta['company_information'])) echo esc_textarea($stored_meta['company_information'][0]); ?></textarea>
    </p>

    <?php
}

// Saving Job Circular Meta Data
function save_job_meta($post_id) {
    // Verify nonce for security
    if (!isset($_POST['job_nonce']) || !wp_verify_nonce($_POST['job_nonce'], basename(__FILE__))) {
        return;
    }

    // Check for autosave and user permissions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // List of fields to update
    $fields = array('job_requirements', 'job_responsibilities', 'job_skills', 'compensation_benefits', 'company_information', 'application_deadline', 'shortlist');
    
    // Sanitize and save textarea fields
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            if ($field == 'shortlist') {
                // Convert comma-separated string to array and serialize
                $shortlist = array_map('trim', explode(',', sanitize_textarea_field($_POST[$field])));
                update_post_meta($post_id, $field, $shortlist);
            } elseif ($field == 'application_deadline') {
                // Save as a plain text field
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                // Use wp_kses_post to allow HTML content while sanitizing
                update_post_meta($post_id, $field, wp_kses_post($_POST[$field]));
            }
        }
    }
}

add_action('save_post', 'save_job_meta');

?>