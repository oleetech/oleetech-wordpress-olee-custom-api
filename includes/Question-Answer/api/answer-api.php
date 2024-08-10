<?php


// Define the path to answer.php
$answer_file_path = __DIR__ . '/../answer.php';

// Check if the file exists before including it
if (file_exists($answer_file_path)) {
    require_once $answer_file_path;
} else {
    // Handle the error if the file does not exist
    error_log('The file answer.php does not exist.');
    // You can also return an error response if this is part of an API
    return new WP_Error('file_not_found', 'The required file answer.php does not exist', array('status' => 500));
}



function register_custom_endpoints_answer_olee() {


	
    // Endpoint for creating an answer
    register_rest_route('custom/v1', '/answers', array(
        'methods' => 'POST',
        'callback' => 'create_answer',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user

    ));
    
		// Endpoint for creating multiple answers
		register_rest_route('custom/v1', '/multiple-answers', array(
			'methods' => 'POST',
			'callback' => 'create_multiple_answers',
			'permission_callback' => 'is_user_logged_in', // Require logged-in user
		));
	
       // Endpoint for retrieving all answers
        register_rest_route('custom/v1', '/answers', array(
            'methods' => 'GET',
            'callback' => 'get_all_answers',
            'permission_callback' => '__return_true', // Allow public access
        ));
    
        // Endpoint for retrieving a specific answer by ID
        register_rest_route('custom/v1', '/answers/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'get_answer',
            'permission_callback' => '__return_true', // Allow public access
        ));    

    // Endpoint for updating an answer
    register_rest_route('custom/v1', '/answers/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'update_answer',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user

    ));

    // Endpoint for deleting an answer
    register_rest_route('custom/v1', '/answers/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_answer',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user

    ));

	// Register endpoint for getting answers by question ID
    register_rest_route('custom/v1', '/question-answers/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_answers_by_question',
        'permission_callback' => '__return_true', // Allow public access
    ));
	
	
    // Endpoint for getting questions and answers by user ID
    register_rest_route('custom/v1', '/user/questions-answers/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_questions_answers_by_user',
        'permission_callback' => '__return_true', // Allow public access
    ));  
	
	// Endpoint for getting answers by specific date and user ID
	register_rest_route('custom/v1', '/answers-by-date/(?P<date>\d{4}-\d{2}-\d{2})', array(
		'methods' => 'GET',
		'callback' => 'get_answers_by_date_and_user',
		'permission_callback' => 'is_user_logged_in', // Require logged-in user
	));	
	
    // Endpoint for getting answers by specific date for all users
    register_rest_route('custom/v1', '/answers-by-date', array(
        'methods' => 'GET',
        'callback' => 'get_answers_by_date',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));
	
    // Endpoint for getting answers by date range for all users
    register_rest_route('custom/v1', '/answers-by-date-range', array(
        'methods' => 'GET',
        'callback' => 'get_answers_by_date_range',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));	
	
    // Endpoint for getting answers by date range and user ID
    register_rest_route('custom/v1', '/user-answers-by-date-range/(?P<user_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_user_answers_by_date_range',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));	
}
add_action('rest_api_init', 'register_custom_endpoints_answer_olee');








function create_answer(WP_REST_Request $request) {
    // অনুরোধের JSON প্যারামিটারগুলি পান
    $params = $request->get_json_params();

    // সমস্ত প্রয়োজনীয় ক্ষেত্র উপস্থিত আছে তা নিশ্চিত করুন
    // যদি 'question_id' এবং 'content' ফিল্ডগুলি প্রদান করা না হয় তাহলে চেক করুন
    if (!isset($params['question_id'], $params['content'])) {
        // প্রয়োজনীয় ক্ষেত্রগুলি অনুপস্থিত থাকলে একটি ত্রুটি প্রতিক্রিয়া প্রদান করুন
        return new WP_REST_Response(array('message' => 'প্রয়োজনীয় ক্ষেত্রগুলি অনুপস্থিত'), 400);
    }

    // বর্তমান ব্যবহারকারীর আইডি পান
    // বর্তমানে লগ ইন করা ব্যবহারকারীর আইডি পান
    $user_id = get_current_user_id();
    if (!$user_id) {
        // যদি ব্যবহারকারী প্রমাণিত না হয় তাহলে একটি ত্রুটি প্রতিক্রিয়া প্রদান করুন
        return new WP_REST_Response(array('message' => 'ব্যবহারকারী প্রমাণিত নয়'), 401);
    }

    // বর্তমান তারিখ পান
    // 'YYYY-MM-DD' ফর্ম্যাটে বর্তমান তারিখ পান
    $current_date = date('Y-m-d');

    // চেক করুন ব্যবহারকারী আজকের দিনে এই প্রশ্নের উত্তর দিয়েছে কিনা
    // বর্তমানে লগ ইন করা ব্যবহারকারীর জন্য নির্দিষ্ট প্রশ্নের জন্য বর্তমান তারিখে বিদ্যমান উত্তরগুলি অনুসন্ধান করার জন্য ক্যোয়ারির আর্গুমেন্টগুলি সংজ্ঞায়িত করুন
    $args = array(
        'post_type' => 'answer', // 'answer' পোস্ট টাইপে অনুসন্ধান করুন
        'post_status' => 'publish', // শুধুমাত্র প্রকাশিত উত্তরগুলির জন্য দেখুন
        'author' => $user_id, // বর্তমান ব্যবহারকারীর আইডি দ্বারা ফিল্টার করুন
        'meta_query' => array( // প্রশ্ন আইডি দ্বারা ফিল্টার করতে মেটা ক্যোয়ারি
            array(
                'key' => 'question_id',
                'value' => $params['question_id'],
                'compare' => '='
            )
        ),
        'date_query' => array( // বর্তমান তারিখ দ্বারা ফিল্টার করতে তারিখ ক্যোয়ারি
            array(
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d')
            )
        )
    );

    // বিদ্যমান উত্তরগুলি খুঁজতে ক্যোয়ারি সম্পাদন করুন
    $existing_answers = get_posts($args);

    // যদি কোনও বিদ্যমান উত্তর থাকে তা পরীক্ষা করুন
    if (!empty($existing_answers)) {
        // ব্যবহারকারী আজকের দিনে প্রশ্নের উত্তর দিয়ে থাকলে একটি ত্রুটি প্রতিক্রিয়া প্রদান করুন
        return new WP_REST_Response(array('message' => 'আপনি আজকের দিনে এই প্রশ্নের উত্তর ইতিমধ্যেই দিয়েছেন'), 400);
    }

    // একটি নতুন উত্তর তৈরি করুন
    // নতুন উত্তর পোস্টের বিস্তারিত সংজ্ঞায়িত করুন
    $new_answer = array(
        'post_title'    => 'প্রশ্ন #' . $params['question_id'] . ' এর জন্য উত্তর', // নতুন উত্তর পোস্টের শিরোনাম
        'post_content'  => sanitize_textarea_field($params['content']), // উত্তরের বিষয়বস্তু পরিষ্কার এবং সেট করুন
        'post_status'   => 'publish', // স্ট্যাটাস 'publish' হিসাবে সেট করুন
        'post_type'     => 'answer', // পোস্ট টাইপ 'answer' হিসাবে সেট করুন
        'post_author'   => $user_id, // লেখককে বর্তমান ব্যবহারকারী হিসাবে সেট করুন
    );

    // নতুন উত্তর পোস্টটি ডাটাবেসে প্রবেশ করান
    $answer_id = wp_insert_post($new_answer);

    // উত্তরটি সফলভাবে তৈরি হয়েছে কিনা তা পরীক্ষা করুন
    if ($answer_id) {
        // প্রয়োজনে মেটা ফিল্ড আপডেট করুন, উদাহরণস্বরূপ, প্রশ্নের সাথে লিঙ্ক
        // নতুন উত্তর পোস্টে 'question_id' এবং 'user_id' মেটা ফিল্ড যোগ করুন
        update_post_meta($answer_id, 'question_id', sanitize_text_field($params['question_id']));
        update_post_meta($answer_id, 'user_id', sanitize_text_field($user_id));

        // নতুন উত্তর আইডি সহ একটি সফল প্রতিক্রিয়া প্রদান করুন
        return new WP_REST_Response(array('message' => 'উত্তর সফলভাবে তৈরি হয়েছে', 'answer_id' => $answer_id), 201);
    }

    // উত্তর তৈরি ব্যর্থ হলে একটি ত্রুটি প্রতিক্রিয়া প্রদান করুন
    return new WP_REST_Response(array('message' => 'উত্তর তৈরি ব্যর্থ হয়েছে'), 500);
}

function create_multiple_answers(WP_REST_Request $request) {
    // Get the JSON parameters from the request
    $params = $request->get_json_params();

    // Check if 'answers' array is provided in the request body
    if (!isset($params) || !is_array($params)) {
        return new WP_REST_Response(array('message' => 'অনুরোধের JSON প্যারামিটার "answers" অথবা এটি একটি অ্যারে হতে হবে'), 400);
    }

    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response(array('message' => 'ব্যবহারকারী প্রমাণিত নয়'), 401);
    }

    // Prepare an array to store response messages and created answer IDs
    $responses = array();

    // Process each answer in the 'answers' array
    foreach ($params as $answer) {
        // Check if 'id' and 'answer' fields are present
        if (!isset($answer['id'], $answer['answer'])) {
            $responses[] = array('message' => 'প্রয়োজনীয় ক্ষেত্রগুলি অনুপস্থিত');
            continue; // Skip to the next answer if fields are missing
        }

        // Create a new answer post
        $new_answer = array(
            'post_title' => 'প্রশ্ন #' . $answer['id'] . ' এর জন্য উত্তর', // Post title
            'post_content' => sanitize_textarea_field($answer['answer']), // Answer content
            'post_status' => 'publish', // Post status
            'post_type' => 'answer', // Post type
            'post_author' => $user_id, // Post author
        );

        // Insert the answer post into the database
        $answer_id = wp_insert_post($new_answer);

        // Check if answer creation was successful
        if ($answer_id) {
            // Update post meta fields 'question_id' and 'user_id'
            update_post_meta($answer_id, 'question_id', sanitize_text_field($answer['id']));
            update_post_meta($answer_id, 'user_id', sanitize_text_field($user_id));
            $question_id = sanitize_text_field($answer['id']);

            // Add successful response message and answer details to responses array
            $responses[] = array(
//                 'message' => 'উত্তর সফলভাবে তৈরি হয়েছে',
				'id' => (int) $answer_id,
				'question_id' => (int) $question_id,
                'answer' => $new_answer['post_content'],
            );
        } else {
            // Add error response message to responses array
            $responses[] = array('message' => 'উত্তর তৈরি ব্যর্থ হয়েছে');
        }
    }

    // Return the overall response containing messages and IDs
    return new WP_REST_Response($responses, 200);
}


function update_answer(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $answer_id = (int) $request['id'];

    // Check if the answer ID is valid
    if (empty($answer_id)) {
        return new WP_REST_Response(array('message' => 'Invalid answer ID'), 400);
    }

    $updated_answer = array(
        'ID'           => $answer_id,
        'post_title'   => isset($params['content']) ? sanitize_text_field($params['content']) : '',
        // You can update other post fields as needed
    );

    // Update the answer post
    $result = wp_update_post($updated_answer);

    if ($result instanceof WP_Error) {
        return new WP_REST_Response(array('message' => 'Failed to update answer'), 500);
    }

    // Update meta fields if necessary
    // Example: Update question_id meta field
    if (isset($params['question_id'])) {
        update_post_meta($answer_id, 'question_id', sanitize_text_field($params['question_id']));
    }

    // Retrieve updated post data to get question_id
    $updated_post = get_post($answer_id);
    $question_id = get_post_meta($answer_id, 'question_id', true);
    $answer_content = $updated_post->post_title; // Assuming 'post_title' is used for answer content

    // Return response with updated answer ID and question ID
    return new WP_REST_Response(array(
        'message'   => 'Answer updated successfully',
        'answer_id' => $answer_id,
 		'answer'        => $answer_content,		
        'question_id' => $question_id,
    ), 200);
}


//
function delete_answer(WP_REST_Request $request) {
    $answer_id = (int) $request['id'];

    $result = wp_delete_post($answer_id, true); // Set true to force delete

    if (!$result) {
        return new WP_REST_Response(array('message' => 'Failed to delete answer'), 500);
    }

    return new WP_REST_Response(array('message' => 'Answer deleted successfully'), 200);
}




// Get All Answers
function get_all_answers(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'numberposts' => -1
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => $answer->ID,
//             'title' => $answer->post_title,
            'content' => $answer->post_content,
//             'author' => get_the_author_meta('display_name', $answer->post_author),
//             'date' => $answer->post_date,
//             'categories' => wp_get_post_terms($answer->ID, 'category', array('fields' => 'names')),
//             'tags' => wp_get_post_terms($answer->ID, 'post_tag', array('fields' => 'names')),
//             'featured_image' => get_the_post_thumbnail_url($answer->ID, 'full'),
            'question_id' => get_post_meta($answer->ID, 'question_id', true)
        );
    }

    return new WP_REST_Response($data, 200);
}

// Get Single Answer by ID
function get_answer(WP_REST_Request $request) {
    $id = $request['id'];
    $answer = get_post($id);

    if (empty($answer) || $answer->post_type != 'answer') {
        return new WP_REST_Response(array('message' => 'Answer not found'), 404);
    }

    $data = array(
        'id' => $answer->ID,
//         'title' => $answer->post_title,
        'answer' => $answer->post_content,
//         'author' => get_the_author_meta('display_name', $answer->post_author),
//         'date' => $answer->post_date,
//         'categories' => wp_get_post_terms($answer->ID, 'category', array('fields' => 'names')),
//         'tags' => wp_get_post_terms($answer->ID, 'post_tag', array('fields' => 'names')),
//         'featured_image' => get_the_post_thumbnail_url($answer->ID, 'full'),
        'q_id' => get_post_meta($answer->ID, 'question_id', true)
    );

    return new WP_REST_Response($data, 200);
}


// Callback function to get questions and answers by user ID
function get_questions_answers_by_user(WP_REST_Request $request) {
    $user_id = (int) $request['id'];
    
    // Get answers by user ID
    $answers_args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'author' => $user_id,
        'numberposts' => -1
    );

    $answers = get_posts($answers_args);
    $data = array();

    foreach ($answers as $answer) {
        // Get the question related to the answer
        $question_id = get_post_meta($answer->ID, 'question_id', true);
        $question = get_post($question_id);

        $data[] = array(
            'answer' => array(
                'id' => $answer->ID,
                'title' => $answer->post_title,
                'content' => $answer->post_content,
                'author' => get_the_author_meta('display_name', $answer->post_author),
                'date' => $answer->post_date,
                'featured_image' => get_the_post_thumbnail_url($answer->ID, 'full')
            ),
            'question' => array(
                'id' => $question->ID,
                'title' => $question->post_title,
                'content' => $question->post_content,
                'author' => get_the_author_meta('display_name', $question->post_author),
                'date' => $question->post_date,
                'featured_image' => get_the_post_thumbnail_url($question->ID, 'full')
            )
        );
    }

    return new WP_REST_Response($data, 200);
}

// Callback function to get answers by question ID
function get_answers_by_question(WP_REST_Request $request) {
    $question_id = (int) $request['id'];
    
    // Query for answers related to the question ID
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'question_id',
                'value' => $question_id,
                'compare' => '=',
            ),
        ),
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => $answer->ID,
            'title' => $answer->post_title,
            'content' => $answer->post_content,
            'author' => get_the_author_meta('display_name', $answer->post_author),
            'date' => $answer->post_date,
            'categories' => wp_get_post_terms($answer->ID, 'category', array('fields' => 'names')),
            'tags' => wp_get_post_terms($answer->ID, 'post_tag', array('fields' => 'names')),
            'featured_image' => get_the_post_thumbnail_url($answer->ID, 'full'),
            'question_id' => get_post_meta($answer->ID, 'question_id', true),
        );
    }

    return new WP_REST_Response($data, 200);
}

function get_answers_by_date_and_user(WP_REST_Request $request) {
    // Get the date from the request
    $date = $request['date'];
    
    // Get the current user ID from the JWT token
    $user_id = get_current_user_id();
    if (!$user_id) {
        return new WP_REST_Response(array('message' => 'User not authenticated'), 401);
    }
    
    // Query for answers related to the specific date and user ID
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'author' => $user_id,
        'date_query' => array(
            array(
                'year' => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day' => date('d', strtotime($date)),
            ),
        ),
		'orderby' => 'ID',  // Order by post ID (ascending)
        'order' => 'ASC',   // Order in ascending order
        'numberposts' => -1
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => (int) $answer->ID,
            'answer' => $answer->post_content,
            'question_id' => (int) get_post_meta($answer->ID, 'question_id', true)
        );
    }

    return new WP_REST_Response($data, 200);
}


function get_answers_by_date(WP_REST_Request $request) {
    // Get date from the request parameters
    $date = $request->get_param('date');

    // Validate date
    if (empty($date)) {
        return new WP_REST_Response(array('message' => 'Date parameter is required'), 400);
    }

    // Query for answers on the specific date
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'date_query' => array(
            array(
                'year'  => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day'   => date('d', strtotime($date)),
            ),
        ),
		 'orderby' => 'ID',  // Order by post ID (ascending)
        'order' => 'ASC',   // Order in ascending order
        'numberposts' => -1
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => $answer->ID,
            'content' => $answer->post_content,
            'user_id' => $answer->post_author,
            'question_id' => get_post_meta($answer->ID, 'question_id', true)
        );
    }

    return new WP_REST_Response($data, 200);
}


function get_answers_by_date_range(WP_REST_Request $request) {
    // Get start and end dates from the request parameters
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');

    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        return new WP_REST_Response(array('message' => 'Start date and end date parameters are required'), 400);
    }

    // Query for answers within the date range
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'date_query' => array(
            'after'     => $start_date,
            'before'    => $end_date,
            'inclusive' => true,
        ),
        'numberposts' => -1
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => $answer->ID,
            'content' => $answer->post_content,
            'user_id' => $answer->post_author,
            'question_id' => get_post_meta($answer->ID, 'question_id', true)
        );
    }

    return new WP_REST_Response($data, 200);
}


function get_user_answers_by_date_range(WP_REST_Request $request) {
    // Get user ID, start date, and end date from the request parameters
    $user_id = $request->get_param('user_id');
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');

    // Validate user ID and dates
    if (empty($user_id) || empty($start_date) || empty($end_date)) {
        return new WP_REST_Response(array('message' => 'User ID, start date, and end date parameters are required'), 400);
    }

    // Query for answers within the date range for the specific user
    $args = array(
        'post_type' => 'answer',
        'post_status' => 'publish',
        'author' => $user_id,
        'date_query' => array(
            'after'     => $start_date,
            'before'    => $end_date,
            'inclusive' => true,
        ),
        'numberposts' => -1
    );

    $answers = get_posts($args);
    $data = array();

    foreach ($answers as $answer) {
        $data[] = array(
            'id' => $answer->ID,
            'content' => $answer->post_content,
            'user_id' => $answer->post_author,
            'question_id' => get_post_meta($answer->ID, 'question_id', true)
        );
    }

    return new WP_REST_Response($data, 200);
}




?>