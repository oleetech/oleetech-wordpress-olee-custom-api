<?php
/**
 * Plugin Name: Olee Custom Api For Wordpress
 * Plugin URI: https://tutorial.rebelsoftt.com/
 * Description: This plugin allows you to create custom data models and manage them (CRUD) through a dedicated REST API within your WordPress site.
 * Version: 1.0.0
 * Author: Olee Ahmmed
 * Author URI: https://tutorial.rebelsoftt.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-crud-with-custom-api
 * Domain Path: /languages/
 * Requires at least: 5.6
 * Tested up to: 6.1
 */

// Activation Hook
register_activation_hook( __FILE__, 'custom_crud_plugin_activate' );
function custom_crud_plugin_activate() {
    // Activation actions here
    // For example, creating tables, initializing options, etc.
}

// Deactivation Hook
register_deactivation_hook( __FILE__, 'custom_crud_plugin_deactivate' );
function custom_crud_plugin_deactivate() {
    // Deactivation actions here
    // For example, removing tables, clearing caches, etc.
}


//  Admin Menu ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$admin_menu_file = plugin_dir_path(__FILE__) . 'includes/admin-menu.php';

if (file_exists($admin_menu_file)) {
    include_once $admin_menu_file;

}


//  User ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$user_api_file = plugin_dir_path(__FILE__) . 'includes/Users/api/users-api.php';

if (file_exists($user_api_file)) {
    include_once $user_api_file;

}


//  Contact Form ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$contact_form_api_file = plugin_dir_path(__FILE__) . 'includes/Contact-Form/api/contact-form-api.php';

if (file_exists($contact_form_api_file)) {
    include_once $contact_form_api_file;

}


//  Story ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$story_api_file = plugin_dir_path(__FILE__) . 'includes/Story/api/story-api.php';

if (file_exists($story_api_file)) {
    include_once $story_api_file;

}


//  Appointment ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$appointment_api_file = plugin_dir_path(__FILE__) . 'includes/Appointment/api/appointment-api.php';

if (file_exists($appointment_api_file)) {
    include_once $appointment_api_file;

}

//  Question ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$question_api_file = plugin_dir_path(__FILE__) . 'includes/Question-Answer/api/question-api.php';

if (file_exists($question_api_file)) {
    include_once $question_api_file;

}


//  Answer ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$answer_api_file = plugin_dir_path(__FILE__) . 'includes/Question-Answer/api/answer-api.php';

if (file_exists($answer_api_file)) {
    include_once $answer_api_file;

}

//  Learning Hub ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$learning_hub_api_file = plugin_dir_path(__FILE__) . 'includes/Learning-Hub/api/learning-hub-api.php';

if (file_exists($learning_hub_api_file)) {
    include_once $learning_hub_api_file;

}


//  Blog ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$blog_api_file = plugin_dir_path(__FILE__) . 'includes/Blog/blog-api.php';

if (file_exists($blog_api_file)) {
    include_once $blog_api_file;

}

//  Wishlist ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$wishlist_api_file = plugin_dir_path(__FILE__) . 'includes/WhishList/api/wishlist-api.php';

if (file_exists($wishlist_api_file)) {
    include_once $wishlist_api_file;

}


//  Slider ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$slider_api_file = plugin_dir_path(__FILE__) . 'includes/Slider/api/slider-api.php';

if (file_exists($slider_api_file)) {
    include_once $slider_api_file;

}

//  Slider ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$jwt_file = plugin_dir_path(__FILE__) . 'includes/jwt-authentication-for-wp-rest-api/jwt-auth.php';

if (file_exists($jwt_file)) {
    include_once $jwt_file;

}



function register_custom_post_count_endpoint() {
    register_rest_route('custom/v1', '/get_published_posts_count', array(
        'methods' => 'GET',
        'callback' => 'get_published_posts_count',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },    ));
}
add_action('rest_api_init', 'register_custom_post_count_endpoint');

function get_published_posts_count(WP_REST_Request $request) {
    // Get the counts for 'post', 'story', and 'learninghub' post types
    $post_types = array('post', 'story', 'learninghub');
    $counts = array();

    foreach ($post_types as $post_type) {
        $query = new WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1, // Retrieve all posts
        ));
        $counts[$post_type] = $query->found_posts;
    }

    // Get the counts for 'appointment' post type with different statuses
    $appointment_statuses = array('requested', 'approved', 'unapproved');
    $appointment_counts = array();

    foreach ($appointment_statuses as $status) {
        $query = new WP_Query(array(
            'post_type' => 'appointment',
            'post_status' => 'draft',
            'meta_query' => array(
                array(
                    'key' => 'appointment_status',
                    'value' => $status,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1, // Retrieve all posts
        ));
        $appointment_counts[$status] = $query->found_posts;
    }

    // Combine all counts in the response
    $response = array(
        'blog' => $counts['post'],
        'story' => $counts['story'],
        'learninghub' => $counts['learninghub'],
        'appointment' => $appointment_counts,
    );

    return new WP_REST_Response($response, 200);
}


function register_custom_appointment_status_count_endpoint() {
    register_rest_route('custom/v1', '/get_user_status_count', array(
        'methods' => 'GET',
        'callback' => 'get_user_status_count',
        'permission_callback' => 'is_user_logged_in',
    ));
}
add_action('rest_api_init', 'register_custom_appointment_status_count_endpoint');
function get_user_answer_sum($user_id, $question_id) {
    global $wpdb;

    // Get the first and last day of the current month
    $first_day_of_month = date('Y-m-01');
    $last_day_of_month = date('Y-m-t');

    // Query to get the answers' content for the specified question and user, grouped by date, within the current month
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE(post_date) as answer_date, post_content FROM $wpdb->posts 
             WHERE post_type = 'answer' 
             AND post_status = 'publish' 
             AND post_date BETWEEN %s AND %s
             AND ID IN (
                SELECT post_id FROM $wpdb->postmeta 
                WHERE meta_key = 'user_id' 
                AND meta_value = %d
             ) 
             AND ID IN (
                SELECT post_id FROM $wpdb->postmeta 
                WHERE meta_key = 'question_id' 
                AND meta_value = %d
             )
             ORDER BY post_date ASC",
            $first_day_of_month,
            $last_day_of_month,
            $user_id,
            $question_id
        ),
        ARRAY_A
    );

    // Prepare the results in a more readable format
    $date_wise_answers = array();
    foreach ($results as $result) {
        $date = $result['answer_date'];
        if (!isset($date_wise_answers[$date])) {
            $date_wise_answers[$date] = array();
        }
        $date_wise_answers[$date][] = $result['post_content'];
    }

    return $date_wise_answers;
}








function get_user_status_count(WP_REST_Request $request) {
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return new WP_Error('no_user', 'User not logged in', array('status' => 401));
    }

    $appointment_statuses = array('requested', 'approved', 'unapproved');
    $counts = array();

    foreach ($appointment_statuses as $status) {
        $query = new WP_Query(array(
            'post_type' => 'appointment',
            'post_status' => 'draft',
            'author' => $current_user_id,
            'meta_query' => array(
                array(
                    'key' => 'appointment_status',
                    'value' => $status,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1, // Retrieve all posts
        ));
        $counts[$status] = $query->found_posts;
    }

    // Count wishlist_learninghub_ids for the current user
    $wishlist_query = new WP_Query(array(
        'post_type' => 'wishlist',
        'meta_query' => array(
            array(
                'key' => '_wishlist_user_id',
                'value' => $current_user_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1, // Retrieve all wishlist posts
    ));

    $learninghub_ids_count = 0;
    if ($wishlist_query->have_posts()) {
        while ($wishlist_query->have_posts()) {
            $wishlist_query->the_post();
            $learninghub_ids = get_post_meta(get_the_ID(), '_wishlist_learninghub_ids', true);
            if (!empty($learninghub_ids)) {
                $learninghub_ids_array = explode(',', $learninghub_ids);
                $learninghub_ids_count += count($learninghub_ids_array);
            }
        }
    }

    wp_reset_postdata(); // Reset the global post object

    $counts['wishlist_learninghub_ids'] = $learninghub_ids_count;

    // Hardcoded question_id
    $question_id = 358;

    // Get the sums of answers for the hardcoded question date-wise for the current month
    $answer_sums = get_user_answer_sum($current_user_id, $question_id);

    // Include the answer sums in the counts array
    $counts['sleep'] = $answer_sums;

    return rest_ensure_response($counts);
}



?>