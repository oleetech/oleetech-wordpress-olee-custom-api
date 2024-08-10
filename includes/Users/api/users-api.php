<?php

//  User ফাইল অন্তর্ভুক্ত করা হচ্ছে, যদি ফাইল থাকে তাহলে
$user_file = plugin_dir_path(__FILE__) . 'includes/Users/users.php';

if (file_exists($user_file)) {
    include_once $user_file;

}

// Function to display the submenu page content
function olee_custom_api_user_page() {
    ?>
    <div class="wrap">
        <h1>User Api</h1>
        <p>Welcome to the User Api page.</p>
    </div>
    <?php
}


// Limit the number of revisions to 5
function limit_post_revisions($num, $post) {
    return 5;
}
add_filter('wp_revisions_to_keep', 'limit_post_revisions', 10, 2);

// Function to delete older revisions, keeping only the latest 5
function delete_old_revisions($post_id) {
    // Get all revisions for the post
    $revisions = wp_get_post_revisions($post_id);

    // If there are more than 5 revisions, delete the older ones
    if (count($revisions) > 5) {
        $revisions_to_delete = array_slice($revisions, 5);

        foreach ($revisions_to_delete as $revision) {
            wp_delete_post_revision($revision->ID);
        }
    }
}

// Hook into the 'save_post' action to delete old revisions after the post is saved
add_action('save_post', 'delete_old_revisions');


//   ___                                        _   _           _                       _ 
//  |_ _|  _ __ ___     __ _    __ _    ___    | | | |  _ __   | |   ___     __ _    __| |
//   | |  | '_ ` _ \   / _` |  / _` |  / _ \   | | | | | '_ \  | |  / _ \   / _` |  / _` |
//   | |  | | | | | | | (_| | | (_| | |  __/   | |_| | | |_) | | | | (_) | | (_| | | (_| |
//  |___| |_| |_| |_|  \__,_|  \__, |  \___|    \___/  | .__/  |_|  \___/   \__,_|  \__,_|
//                             |___/                   |_|                                

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/upload-image', array(
        'methods' => 'POST',
        'callback' => 'handle_image_upload',
        'permission_callback' => 'is_user_logged_in',
    ));
});


function handle_image_upload($request) {
    if (!empty($_FILES['file'])) {
        // Set variables
        $file = $_FILES['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error']) && $upload['error'] != 0) {
            return new WP_Error('upload_error', $upload['error']);
        } else {
            $filename = $upload['file'];
            $filetype = wp_check_filetype(basename($filename), null);
            $wp_upload_dir = wp_upload_dir();

            // Prepare an array of post data for the attachment.
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                'post_mime_type' => $filetype['type'],
                'post_title' => sanitize_file_name(basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // Insert the attachment.
            $attach_id = wp_insert_attachment($attachment, $filename);

            // Generate the metadata for the attachment, and update the database record.
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Return the ID and URL of the uploaded image.
            $image_url = wp_get_attachment_url($attach_id);
            return rest_ensure_response(array(
                'image_id' => $attach_id,
                'image_url' => $image_url
            ));
        }
    } else {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }
}

//      _              _     _                        ___                                        _   _           _                       _ 
//     / \     _   _  | |_  | |__     ___    _ __    |_ _|  _ __ ___     __ _    __ _    ___    | | | |  _ __   | |   ___     __ _    __| |
//    / _ \   | | | | | __| | '_ \   / _ \  | '__|    | |  | '_ ` _ \   / _` |  / _` |  / _ \   | | | | | '_ \  | |  / _ \   / _` |  / _` |
//   / ___ \  | |_| | | |_  | | | | | (_) | | |       | |  | | | | | | | (_| | | (_| | |  __/   | |_| | | |_) | | | | (_) | | (_| | | (_| |
//  /_/   \_\  \__,_|  \__| |_| |_|  \___/  |_|      |___| |_| |_| |_|  \__,_|  \__, |  \___|    \___/  | .__/  |_|  \___/   \__,_|  \__,_|
//                                                                              |___/                   |_|                                

// Add a custom field to user profile
add_action('show_user_profile', 'add_profile_picture_field');
add_action('edit_user_profile', 'add_profile_picture_field');

function add_profile_picture_field($user) {
    ?>
    <h3><?php _e('Profile Picture Information', 'your-textdomain'); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="profile_picture_url"><?php _e('Profile Picture URL', 'your-textdomain'); ?></label></th>
            <td>
                <input type="text" name="profile_picture_url" id="profile_picture_url" value="<?php echo esc_attr(get_user_meta($user->ID, 'profile_picture_url', true)); ?>" class="regular-text" /><br />
                <span class="description"><?php _e('Please enter your profile picture URL.', 'your-textdomain'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

// Save custom field data
add_action('personal_options_update', 'save_profile_picture_field');
add_action('edit_user_profile_update', 'save_profile_picture_field');

function save_profile_picture_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'profile_picture_url', $_POST['profile_picture_url']);
}



add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/upload-author-image', array(
        'methods' => 'POST',
        'callback' => 'handle_author_image_upload',
        'permission_callback' => 'is_user_logged_in',
    ));

    register_rest_route('custom/v1', '/get-author-image', array(
        'methods' => 'GET',
        'callback' => 'get_author_image',
        'permission_callback' => 'is_user_logged_in',
    ));
});

function handle_author_image_upload($request) {
    if (!empty($_FILES['file'])) {
        $file = $_FILES['file'];
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_Error('no_user', 'User not logged in', array('status' => 403));
        }

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error']) && $upload['error'] != 0) {
            return new WP_Error('upload_error', $upload['error']);
        } else {
            $filename = $upload['file'];
            $filetype = wp_check_filetype(basename($filename), null);
            $wp_upload_dir = wp_upload_dir();

            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                'post_mime_type' => $filetype['type'],
                'post_title' => sanitize_file_name(basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $filename);

            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $image_url = wp_get_attachment_url($attach_id);

            // Update the user's profile picture URL
            update_user_meta($user_id, 'profile_picture_url', $image_url);

            return rest_ensure_response(array(
                'image_id' => $attach_id,
                'image_url' => $image_url
            ));
        }
    } else {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }
}

function get_author_image($request) {
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('no_user', 'User not logged in', array('status' => 403));
    }

    // Get the profile picture URL from user meta
    $image_url = get_user_meta($user_id, 'profile_picture_url', true);

    if (!$image_url) {
        return new WP_Error('no_image', 'No image found for this user', array('status' => 404));
    }

    return rest_ensure_response(array(
        'image_url' => $image_url
    ));
}


//   ____                 _                          __        __                     _                                       _                               
//   / ___|  _   _   ___  | |_    ___    _ __ ___     \ \      / /   ___    _ __    __| |  _ __    _ __    ___   ___   ___    | |       ___     __ _    ___    
//  | |     | | | | / __| | __|  / _ \  | '_ ` _ \     \ \ /\ / /   / _ \  | '__|  / _` | | '_ \  | '__|  / _ \ / __| / __|   | |      / _ \   / _` |  / _ \   
//  | |___  | |_| | \__ \ | |_  | (_) | | | | | | |     \ V  V /   | (_) | | |    | (_| | | |_) | | |    |  __/ \__ \ \__ \   | |___  | (_) | | (_| | | (_) |  
//   \____|  \__,_| |___/  \__|  \___/  |_| |_| |_|      \_/\_/     \___/  |_|     \__,_| | .__/  |_|     \___| |___/ |___/   |_____|  \___/   \__, |  \___/   
//                                                                                       |_|                                                  |___/           


// Add custom logo to the login page
function custom_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo get_stylesheet_directory_uri(); ?>/images/custom-logo.png');
            height: 65px;
            width: 320px;
            background-size: contain;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>
<?php }
add_action('login_enqueue_scripts', 'custom_login_logo');

// Change the URL of the logo
function custom_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'custom_login_logo_url');

// Change the title attribute of the logo link
function custom_login_logo_url_title() {
    return 'Your Site Name and Info';
}
add_filter('login_headertitle', 'custom_login_logo_url_title');



// Adding CORS headers
function add_cors_headers() {
    // Replace '*' with your React application URL during production
    if (!headers_sent() && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization");
        header("Access-Control-Max-Age: 86400"); // 1 day
        header("Content-Length: 0");
        header("Content-Type: text/plain");
        exit(0);
    }
}
add_action('init', 'add_cors_headers');


//    ____                 _                           _                             
//   / ___|  _   _   ___  | |_    ___    _ __ ___     | |       ___     __ _    ___  
//  | |     | | | | / __| | __|  / _ \  | '_ ` _ \    | |      / _ \   / _` |  / _ \ 
//  | |___  | |_| | \__ \ | |_  | (_) | | | | | | |   | |___  | (_) | | (_| | | (_) |
//   \____|  \__,_| |___/  \__|  \___/  |_| |_| |_|   |_____|  \___/   \__, |  \___/ 
//                                                                     |___/         

function register_custom_api_endpoints() {
    // Register a REST route for getting settings
    register_rest_route( 'wp/v2/', '/settings', array(
        'methods'  => 'GET',
        'callback' => 'get_custom_settings',
        'permission_callback' => '__return_true', // Make it publicly accessible
    ));
}

add_action( 'rest_api_init', 'register_custom_api_endpoints' );



function get_custom_settings() {
    // Fetch settings data
    $settings = array(
        'title'             => get_bloginfo( 'name' ),
        'description'       => get_bloginfo( 'description' ),
        'logo'              => get_custom_logo_url(), // Ensure this function exists or implement it
        'url'               => home_url(),
        'admin_email'       => get_option( 'admin_email' ),
        'posts_per_page'    => get_option( 'posts_per_page', 10 ),
        'timezone'          => get_option( 'timezone_string', 'UTC' ),
        'date_format'       => get_option( 'date_format', 'F j, Y' ),
        'time_format'       => get_option( 'time_format', 'g:i a' ),
        'start_of_week'     => get_option( 'start_of_week', 0 ), // 0 (Sunday) to 6 (Saturday)
        'default_category'  => get_option( 'default_category', 0 ),
        'uploads_use_yearmonth_folders' => get_option( 'uploads_use_yearmonth_folders', 1 ) ? 'yes' : 'no',
        'permalink_structure' => get_option( 'permalink_structure', '' ),
        'comments_per_page' => get_option( 'comments_per_page', 10 ),
        'comment_registration' => get_option( 'comment_registration', 0 ) ? 'enabled' : 'disabled',
        'users_can_register' => get_option( 'users_can_register', 0 ) ? 'yes' : 'no',
        'blog_public' => get_option( 'blog_public', 1 ) ? 'public' : 'private',
        // Add more settings as needed
    );

    return new WP_REST_Response( $settings, 200 );
}

// Permission callback function for JWT Authentication
function jwt_auth_permission_callback() {
    // Check if the current user has a valid JWT token
    $user = wp_get_current_user();

    if ( empty( $user ) || ! $user->exists() ) {
        return false; // User is not logged in
    }

    // Check if the user's token is valid
    $token = JWT::decode_token(); // Replace with your JWT token decoding function

    if ( is_wp_error( $token ) ) {
        return false; // Invalid token
    }

    // Optionally, you can perform additional checks here, such as user capabilities or roles

    return true; // User has a valid JWT token
}


// Function to get custom logo URL, implement if not existing
function get_custom_logo_url() {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );

    return $logo ? $logo[0] : '';
}

//                       _             _                             _                 
//   _ __     ___    ___  | |_          | |__    _   _           ___  | |  _   _    __ _ 
//  | '_ \   / _ \  / __| | __|  _____  | '_ \  | | | |  _____  / __| | | | | | |  / _` |
//  | |_) | | (_) | \__ \ | |_  |_____| | |_) | | |_| | |_____| \__ \ | | | |_| | | (_| |
//  | .__/   \___/  |___/  \__|         |_.__/   \__, |         |___/ |_|  \__,_|  \__, |
//  |_|                                          |___/                             |___/ 
//
//
// Callback function to retrieve post by slug
// function get_post_by_slug_callback( $request ) {
//     $slug = $request['slug'];

//     // Query the post by its slug
//     $post = get_page_by_path( $slug, OBJECT, 'post' );

//     if ( ! $post ) {
//         return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
//     }

//     // Get author data
//     $author = get_userdata($post->post_author);

//     // Get post data
//     $post_data = array(
//         'id'             => $post->ID,
//         'title'          => get_the_title($post->ID),
//         'content'        => apply_filters('the_content', $post->post_content),
//         'excerpt'        => get_post_field('post_excerpt', $post->ID), // Attempt to get excerpt
//         'date'           => get_the_date('c', $post),  // ISO 8601 format
//         'author'         => array(
//             'id'            => $author->ID,
//             'name'          => $author->display_name,
//             'url'           => get_author_posts_url($author->ID),
//         ),
//         'categories'     => wp_get_post_categories($post->ID, array('fields' => 'names')),
//         'tags'           => wp_get_post_terms($post->ID, 'post_tag', array('fields' => 'names')),
//     );

//     // Check if excerpt is empty, generate one if needed
//     if (empty($post_data['excerpt'])) {
//         $post_data['excerpt'] = wp_trim_words($post->post_content, 55); // 55 words excerpt length
//     }

//     // Check if there's a featured image associated with the post
//     if (has_post_thumbnail($post->ID)) {
//         $image_id = get_post_thumbnail_id($post->ID);
//         $image_url = wp_get_attachment_image_url($image_id, 'full');  // Change 'full' to another size if necessary
//         $post_data['featured_image'] = $image_url;
//     } else {
//         $post_data['featured_image'] = null;  // Or specify a default image URL
//     }

//     // Return post data
//     return rest_ensure_response($post_data);
// }


// // Register custom REST API endpoint
// function register_post_by_slug_endpoint() {
//     register_rest_route( 'custom/v1', '/post-by-slug/(?P<slug>[a-zA-Z0-9-]+)', array(
//         'methods'             => 'GET',
//         'callback'            => 'get_post_by_slug_callback',
//         'permission_callback' => '__return_true', // Adjust permissions as needed
//     ) );
// }

// add_action( 'rest_api_init', 'register_post_by_slug_endpoint' );

//                 _                                                        _                                                        
//    __ _    ___  | |_           _ __ ___     ___   _ __    _   _          | |__    _   _           _ __     __ _   _ __ ___     ___ 
//   / _` |  / _ \ | __|         | '_ ` _ \   / _ \ | '_ \  | | | |         | '_ \  | | | |         | '_ \   / _` | | '_ ` _ \   / _ \
//  | (_| | |  __/ | |_          | | | | | | |  __/ | | | | | |_| |         | |_) | | |_| |         | | | | | (_| | | | | | | | |  __/
//   \__, |  \___|  \__|  _____  |_| |_| |_|  \___| |_| |_|  \__,_|  _____  |_.__/   \__, |  _____  |_| |_|  \__,_| |_| |_| |_|  \___|
//   |___/               |_____|                                    |_____|          |___/  |_____|                                   
//

// function register_menu_rest_route() {
//     register_rest_route('custom/v1', '/menus/(?P<menu_name>[a-zA-Z0-9_-]+)', array(
//         'methods'  => 'GET',
//         'callback' => 'get_menu_by_name',
//         'permission_callback' => '__return_true', // Allow public access
//     ));
// }

// add_action('rest_api_init', 'register_menu_rest_route');

// function get_menu_by_name($data) {
//     $menu_name = $data['menu_name'];
//     $menu = wp_get_nav_menu_items($menu_name);
    
//     if (empty($menu)) {
//         return new WP_Error('no_menu', 'Invalid menu location', array('status' => 404));
//     }

//     return $menu;
// }



//   ____                   _         _                    _     _                 
//  |  _ \    ___    __ _  (_)  ___  | |_   _ __    __ _  | |_  (_)   ___    _ __  
//  | |_) |  / _ \  / _` | | | / __| | __| | '__|  / _` | | __| | |  / _ \  | '_ \ 
//  |  _ <  |  __/ | (_| | | | \__ \ | |_  | |    | (_| | | |_  | | | (_) | | | | |
//  |_| \_\  \___|  \__, | |_| |___/  \__| |_|     \__,_|  \__| |_|  \___/  |_| |_|
//                  |___/                                                          

// https://wp.kreatech.ca/wp-json/custom/v1/register/
// {
//     "username": "john_doe",
//     "email": "john.doe@example.com",
//     "password": "securepassword123",
//     "contact_number": "1234567890"
// }

function custom_user_registration_endpoint() {
    register_rest_route('custom/v1', '/register', array(
        'methods' => 'POST',
        'callback' => 'handle_user_registration',
        'permission_callback' => '__return_true',
        'args' => array(
            'username' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The username for the new user',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The email for the new user',
            ),
            'password' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The password for the new user',
            ),
			
		   'first_name' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'The first name of the user',
            ),
            'last_name' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'The last name of the user',
            ),
            'marital_status' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'The marital status of the user',
            ),
            'nid' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'The National ID of the user',
            ),
            'contact_number' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The contact number for the new user',
            ),
            // Additional fields that will be updated later
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of the registration process',
                ),
            ),
        ),
    ));
}

add_action('rest_api_init', 'custom_user_registration_endpoint');

function handle_user_registration($request) {
    // ইনপুট থেকে ফিল্ডগুলি সংগ্রহ করুন এবং পরিষ্কার করুন
    $email = sanitize_email($request->get_param('email'));
    $password = sanitize_text_field($request->get_param('password'));
    $username = sanitize_user($request->get_param('username'));
    $contact_number = sanitize_text_field($request->get_param('contact_number'));
    $nid = sanitize_text_field($request->get_param('nid'));
    $first_name = sanitize_text_field($request->get_param('first_name'));
    $last_name = sanitize_text_field($request->get_param('last_name'));
    $marital_status = sanitize_text_field($request->get_param('marital_status'));

    // ইমেল যাচাই করুন
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email format', array('status' => 400));
    }

    // ইমেল আগে থেকে আছে কিনা পরীক্ষা করুন
    if (email_exists($email)) {
        return new WP_Error('email_exists', 'Email already registered', array('status' => 400));
    }

    // যোগাযোগ নম্বর যাচাই করুন (ধরা হয়েছে এটি সংখ্যাসূচক এবং নির্দিষ্ট ফরম্যাটে)
    if (!preg_match('/^[0-9]{11}$/', $contact_number)) {
        return new WP_Error('invalid_contact_number', 'Invalid contact number format (should be 11 digits)', array('status' => 400));
    }

    // ইউজারনেম আগে থেকে আছে কিনা পরীক্ষা করুন
    if (username_exists($username)) {
        return new WP_Error('username_exists', 'Username already taken', array('status' => 400));
    }

    // ইউজার তৈরি করুন
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_Error('registration_failed', 'Registration failed', array('status' => 400));
    }

    // অতিরিক্ত ইউজার মেটা আপডেট করুন যদি দেয়া হয়ে থাকে
    if (!empty($nid)) {
        update_user_meta($user_id, 'nid', $nid);
    }
    if (!empty($first_name)) {
        update_user_meta($user_id, 'first_name', $first_name);
    }
    if (!empty($last_name)) {
        update_user_meta($user_id, 'last_name', $last_name);
    }
    if (!empty($marital_status)) {
        update_user_meta($user_id, 'marital_status', $marital_status);
    }

    // আবশ্যিক ইউজার মেটা আপডেট করুন
    update_user_meta($user_id, 'contact_number', $contact_number);
    update_user_meta($user_id, 'account_status', 'pending');

    // অ্যাক্টিভেশন কী জেনারেট করুন
    $activation_key = wp_generate_password(20, false);
    update_user_meta($user_id, 'activation_key', $activation_key);

    // কনফার্মেশন ইমেল পাঠান
//     $activation_link = add_query_arg(array('key' => $activation_key, 'user' => $user_id), get_site_url(null, 'wp-json/custom/v1/activate'));
// কাস্টম URL নির্ধারণ করুন
$custom_url = 'https://pahona.org/active-account';

// কাস্টম URL এ query arguments যোগ করুন
$activation_link = add_query_arg(
    array(
        'key' => $activation_key,
        'user' => $user_id
    ), 
    $custom_url
);
	
	wp_mail($email, 'Confirm your registration', 'Click on the following link to activate your account: ' . $activation_link);

    // রেজিস্ট্রেশনের তথ্য এবং সফল মেসেজ ফিরিয়ে দিন
    $response_data = array(
        'message' => 'Registration successful. Please check your email to activate your account.',
        'user_id' => $user_id,
        'email' => $email,
        'username' => $username,
        'contact_number' => $contact_number,
        'nid' => $nid,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'marital_status' => $marital_status,
        'activation_key' => $activation_key,
    );

    return new WP_REST_Response($response_data, 200);
}



function custom_user_activation_endpoint() {
    register_rest_route('custom/v1', '/activate', array(
        'methods' => 'GET',
        'callback' => 'handle_user_activation',
        'permission_callback' => '__return_true',
        'args' => array(
            'key' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The activation key sent to the user\'s email',
            ),
            'user' => array(
                'required' => true,
                'type' => 'integer',
                'description' => 'The ID of the user to be activated',
            ),
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of the activation process',
                ),
            ),
        ),
    ));
}

add_action('rest_api_init', 'custom_user_activation_endpoint');

function handle_user_activation($request) {
    $activation_key = sanitize_text_field($request->get_param('key'));
    $user_id = sanitize_text_field($request->get_param('user'));

    $saved_activation_key = get_user_meta($user_id, 'activation_key', true);

    if ($saved_activation_key !== $activation_key) {
        return new WP_REST_Response((object)array(
            'status' => false,
            'message' => 'Invalid activation key'
        ), 400);
    }

    update_user_meta($user_id, 'account_status', 'active');
    delete_user_meta($user_id, 'activation_key');

    return new WP_REST_Response((object)array(
        'status' => true,
        'message' => 'Account activated successfully'
    ), 200);
}


//      _             _     _                   _              _   _                         _                 _____                       _   _ 
//     / \      ___  | |_  (_) __   __   __ _  | |_    ___    | | | |  ___    ___   _ __    | |__    _   _    | ____|  _ __ ___     __ _  (_) | |
//    / _ \    / __| | __| | | \ \ / /  / _` | | __|  / _ \   | | | | / __|  / _ \ | '__|   | '_ \  | | | |   |  _|   | '_ ` _ \   / _` | | | | |
//   / ___ \  | (__  | |_  | |  \ V /  | (_| | | |_  |  __/   | |_| | \__ \ |  __/ | |      | |_) | | |_| |   | |___  | | | | | | | (_| | | | | |
//  /_/   \_\  \___|  \__| |_|   \_/    \__,_|  \__|  \___|    \___/  |___/  \___| |_|      |_.__/   \__, |   |_____| |_| |_| |_|  \__,_| |_| |_|
//                                                                                                   |___/                                       

function register_activate_user_endpoint() {
    register_rest_route('custom/v1', '/activate_user', array(
        'methods' => 'POST',
        'callback' => 'handle_activate_user',
        'permission_callback' => function () {
            return current_user_can('manage_options'); // Only admins can activate users
        },
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The email of the user to activate',
            ),
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of the activation process',
                ),
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_activate_user_endpoint');

function handle_activate_user($request) {
    // Get and sanitize the email from the request
    $email = sanitize_email($request->get_param('email'));

    // Verify the email is valid
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email format', array('status' => 400));
    }

    // Check if the user with the given email exists
    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    // Update the user's account status to 'active'
    update_user_meta($user->ID, 'account_status', 'active');

    // Prepare the response
    $response_data = array(
        'message' => 'User activated successfully.',
        'user_id' => $user->ID,
        'email' => $email,
    );

    return new WP_REST_Response($response_data, 200);
}


//   ____                       _        _             _     _                   _     _                                         _   _ 
//  / ___|    ___   _ __     __| |      / \      ___  | |_  (_) __   __   __ _  | |_  (_)   ___    _ __      _ __ ___     __ _  (_) | |
//  \___ \   / _ \ | '_ \   / _` |     / _ \    / __| | __| | | \ \ / /  / _` | | __| | |  / _ \  | '_ \    | '_ ` _ \   / _` | | | | |
//   ___) | |  __/ | | | | | (_| |    / ___ \  | (__  | |_  | |  \ V /  | (_| | | |_  | | | (_) | | | | |   | | | | | | | (_| | | | | |
//  |____/   \___| |_| |_|  \__,_|   /_/   \_\  \___|  \__| |_|   \_/    \__,_|  \__| |_|  \___/  |_| |_|   |_| |_| |_|  \__,_| |_| |_|
                                                                                                                                    

function register_send_activation_email_endpoint() {
    register_rest_route('custom/v1', '/send_activation_email', array(
        'methods' => 'POST',
        'callback' => 'handle_send_activation_email',
        'permission_callback' => function () {
            return current_user_can('manage_options'); // Only admins can send activation emails
        },
        'args' => array(
            'email' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The email of the user to send the activation link',
            ),
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of sending the activation email',
                ),
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_send_activation_email_endpoint');

function handle_send_activation_email($request) {
    // Get and sanitize the email from the request
    $email = sanitize_email($request->get_param('email'));

    // Verify the email is valid
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email format', array('status' => 400));
    }

    // Check if the user with the given email exists
    $user = get_user_by('email', $email);
    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    // Generate an activation key
    $activation_key = wp_generate_password(20, false);
    update_user_meta($user->ID, 'activation_key', $activation_key);

    // Prepare the activation link
    $activation_link = add_query_arg(array(
        'key' => $activation_key,
        'user' => $user->ID,
    ), get_site_url(null, 'wp-json/custom/v1/activate'));

    // Send the activation email
    $subject = 'Activate Your Account';
    $message = 'Click the following link to activate your account: ' . $activation_link;
    wp_mail($email, $subject, $message);

    // Prepare the response
    $response_data = array(
        'message' => 'Activation email sent successfully.',
        'email' => $email,
        'activation_link' => $activation_link,
    );

    return new WP_REST_Response($response_data, 200);
}


//   _                       _         
//  | |       ___     __ _  (_)  _ __  
//  | |      / _ \   / _` | | | | '_ \ 
//  | |___  | (_) | | (_| | | | | | | |
//  |_____|  \___/   \__, | |_| |_| |_|
//                   |___/             

// https://pahona.org/api/wp-json/jwt-auth/v1/token/
// {
//   "username": "testuser",
//   "password": "password123"
// }

//    ____                 _                           _                       _         
//   / ___|  _   _   ___  | |_    ___    _ __ ___     | |       ___     __ _  (_)  _ __  
//  | |     | | | | / __| | __|  / _ \  | '_ ` _ \    | |      / _ \   / _` | | | | '_ \ 
//  | |___  | |_| | \__ \ | |_  | (_) | | | | | | |   | |___  | (_) | | (_| | | | | | | |
//   \____|  \__,_| |___/  \__|  \___/  |_| |_| |_|   |_____|  \___/   \__, | |_| |_| |_|
//                                                                     |___/             

// https://pahona.org/api/wp-json/jwt-auth/v1/token/
function custom_login_with_email_and_get_jwt_token( $email, $password ) {
    // Retrieve user by email
    $user = get_user_by( 'email', $email );

    if ( ! $user ) {
        return new WP_Error( 'user_not_found', 'User not found for the provided email.', array( 'status' => 404 ) );
    }

    $username = $user->user_login;

    // Prepare data for JWT token request
    $token_request_data = array(
        'username' => $username,
        'password' => $password,
    );

    // Send POST request to external JWT token endpoint
    $token_request = wp_remote_post( 'https://pahona.org/api/wp-json/jwt-auth/v1/token/', array(
        'method'    => 'POST',
        'body'      => $token_request_data,
        'sslverify' => false,  // Set to true in production unless you have SSL issues
    ) );

    if ( is_wp_error( $token_request ) ) {
        return new WP_Error( 'jwt_token_error', 'Error sending JWT token request.', array( 'status' => 500 ) );
    }

    $token_response = json_decode( wp_remote_retrieve_body( $token_request ) );

    if ( isset( $token_response->token ) ) {
        // Token received successfully
        return $token_response->token;
    } else {
        return new WP_Error( 'jwt_token_error', 'Invalid response from JWT token endpoint.', array( 'status' => 500 ) );
    }
}


//   _   _                         ___           _____         
//  | | | |  ___    ___   _ __    |_ _|  _ __   |  ___|   ___  
//  | | | | / __|  / _ \ | '__|    | |  | '_ \  | |_     / _ \ 
//  | |_| | \__ \ |  __/ | |       | |  | | | | |  _|   | (_) |
//   \___/  |___/  \___| |_|      |___| |_| |_| |_|      \___/ 
//     
//                                                                                                                   

// https://pahona.org/api/wp-json/custom/v1/user-info/{id}
function custom_user_info_endpoint() {
    register_rest_route('custom/v1', '/user-info/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_user_info',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                },
                'required' => true,
                'type' => 'integer',
                'description' => 'User ID',
            ),
        ),
    ));
}
add_action('rest_api_init', 'custom_user_info_endpoint');

function get_user_info($request) {
    $user_id = $request->get_param('id');
    $user = get_userdata($user_id);

    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', array('status' => 404));
    }

    $response = array(
        'ID' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'contact_number' => get_user_meta($user->ID, 'contact_number', true),
        'nid' => get_user_meta($user->ID, 'nid', true),
        'marital_status' => get_user_meta($user->ID, 'marital_status', true),
        'roles' => $user->roles, // Adding user roles to the response
    );

    return new WP_REST_Response($response, 200);
}



//   _   _                         ___           _____             _   _               _           _          
//  | | | |  ___    ___   _ __    |_ _|  _ __   |  ___|   ___     | | | |  _ __     __| |   __ _  | |_    ___ 
//  | | | | / __|  / _ \ | '__|    | |  | '_ \  | |_     / _ \    | | | | | '_ \   / _` |  / _` | | __|  / _ \
//  | |_| | \__ \ |  __/ | |       | |  | | | | |  _|   | (_) |   | |_| | | |_) | | (_| | | (_| | | |_  |  __/
//   \___/  |___/  \___| |_|      |___| |_| |_| |_|      \___/     \___/  | .__/   \__,_|  \__,_|  \__|  \___|
//                                                                        |_|                                 
// https://pahona.org/api/wp-json/custom/v1/update-user-info
function custom_user_update_endpoint() {
    register_rest_route('custom/v1', '/update-user-info', array(
        'methods' => 'POST',
        'callback' => 'handle_user_update',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'contact_number' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'The contact number for the user',
            ),
            'nid' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'The National ID of the user',
            ),
        ),
    ));
}
add_action('rest_api_init', 'custom_user_update_endpoint');

function handle_user_update($request) {
    // Ensure the user is logged in and get their ID
    $user_id = get_current_user_id();

    if (!$user_id) {
        return new WP_Error('not_logged_in', 'You must be logged in to update your information.', array('status' => 401));
    }

    // Get and sanitize the input fields
    $contact_number = sanitize_text_field($request->get_param('contact_number'));
    $nid = sanitize_text_field($request->get_param('nid'));

    // Validate the contact number format
    if (!preg_match('/^[0-9]{11}$/', $contact_number)) {
        return new WP_Error('invalid_contact_number', 'Invalid contact number format (should be 11 digits)', array('status' => 400));
    }

    // Update the user meta fields
    update_user_meta($user_id, 'contact_number', $contact_number);

    if (!empty($nid)) {
        update_user_meta($user_id, 'nid', $nid);
    }

    // Return a success response
    $response_data = array(
        'message' => 'User information updated successfully.',
        'user_id' => $user_id,
        'contact_number' => $contact_number,
        'nid' => $nid,
    );

    return new WP_REST_Response($response_data, 200);
}



//   ____           _          _                        _   _                     
//  |  _ \    ___  | |   ___  | |_    ___      __ _    | | | |  ___    ___   _ __ 
//  | | | |  / _ \ | |  / _ \ | __|  / _ \    / _` |   | | | | / __|  / _ \ | '__|
//  | |_| | |  __/ | | |  __/ | |_  |  __/   | (_| |   | |_| | \__ \ |  __/ | |   
//  |____/   \___| |_|  \___|  \__|  \___|    \__,_|    \___/  |___/  \___| |_|   
                      
// POST https://pahona.org/api/wp-json/custom/v1/delete-user/{user_id}

function custom_delete_user_endpoint() {
    register_rest_route('custom/v1', '/delete-user/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'handle_delete_user',
        'permission_callback' => 'custom_user_delete_permissions_check',
    ));
}

add_action('rest_api_init', 'custom_delete_user_endpoint');

function custom_user_delete_permissions_check($request) {
    // Check if current user can delete users
    if (!current_user_can('delete_users')) {
        return new WP_Error('rest_forbidden', esc_html__('You do not have permissions to delete users.'), array('status' => 403));
    }
    return true;
}

function handle_delete_user($request) {
    $user_id = (int) $request['id'];

    // Validate user ID
    if (empty($user_id)) {
        return new WP_Error('invalid_user_id', 'Invalid user ID', array('status' => 400));
    }

    // Delete user
    $deleted = wp_delete_user($user_id);

    if (!$deleted) {
        return new WP_Error('delete_failed', 'Failed to delete user', array('status' => 500));
    }

    return new WP_REST_Response(array('message' => 'User deleted successfully'), 200);
}




//   ____    _____   ____    _____   _____     ____       _      ____    ____   __        __   ___    ____    ____  
//  |  _ \  | ____| / ___|  | ____| |_   _|   |  _ \     / \    / ___|  / ___|  \ \      / /  / _ \  |  _ \  |  _ \ 
//  | |_) | |  _|   \___ \  |  _|     | |     | |_) |   / _ \   \___ \  \___ \   \ \ /\ / /  | | | | | |_) | | | | |
//  |  _ <  | |___   ___) | | |___    | |     |  __/   / ___ \   ___) |  ___) |   \ V  V /   | |_| | |  _ <  | |_| |
//  |_| \_\ |_____| |____/  |_____|   |_|     |_|     /_/   \_\ |____/  |____/     \_/\_/     \___/  |_| \_\ |____/ 
                                                                                                                 



// function custom_rest_password_reset() {
//     register_rest_route('custom/v1', '/password-reset', array(
//         'methods' => 'POST',
//         'callback' => 'custom_handle_password_reset',
//         'permission_callback' => '__return_true',
//     ));
// }
// add_action('rest_api_init', 'custom_rest_password_reset');

// function custom_handle_password_reset(WP_REST_Request $request) {
//     $parameters = $request->get_json_params();
//     $user_login = sanitize_text_field($parameters['user_login']);

//     if (empty($user_login)) {
//         return new WP_Error('empty_username', 'Please enter a username or email address.', array('status' => 400));
//     }

//     if (is_email($user_login)) {
//         $user = get_user_by('email', $user_login);
//     } else {
//         $user = get_user_by('login', $user_login);
//     }

//     if (!$user) {
//         return new WP_Error('invalid_username', 'No user found with this email address or username.', array('status' => 400));
//     }

//     $reset_key = get_password_reset_key($user);

//     if (is_wp_error($reset_key)) {
//         return new WP_Error('password_reset_failed', $reset_key->get_error_message(), array('status' => 500));
//     }

//     $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
//     $message = "Someone has requested a password reset for the following account:\n\n";
//     $message .= "Username: " . $user->user_login . "\n\n";
//     $message .= "If this was a mistake, just ignore this email and nothing will happen.\n\n";
//     $message .= "To reset your password, visit the following address:\n\n";
//     $message .= $reset_url . "\n";

//     $sent = wp_mail($user->user_email, 'Password Reset Request', $message);

//     if ($sent) {
//         return new WP_REST_Response('Password reset email has been sent.', 200);
//     } else {
//         return new WP_Error('email_failed', 'Failed to send password reset email.', array('status' => 500));
//     }
// }


function custom_rest_password_reset() {
    register_rest_route('custom/v1', '/password-reset', array(
        'methods' => 'POST',
        'callback' => 'custom_handle_password_reset',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'custom_rest_password_reset');

function custom_handle_password_reset(WP_REST_Request $request) {
    $parameters = $request->get_json_params();
    $user_email = sanitize_email($parameters['email']); // Using email instead of user_login

    if (empty($user_email) || !is_email($user_email)) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'Please enter a valid email address.'
        ), 400);
    }

    $user = get_user_by('email', $user_email);

    if (!$user) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'No user found with this email address.'
        ), 400);
    }

    $reset_key = get_password_reset_key($user);

    if (is_wp_error($reset_key)) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => $reset_key->get_error_message()
        ), 500);
    }

    $reset_url = "pahona.org/reset-password?key=$reset_key&email=" . rawurlencode($user->user_email);

    $message = "Someone has requested a password reset for the following account:\n\n";
    $message .= "Username: " . $user->user_login . "\n\n";
    $message .= "If this was a mistake, just ignore this email and nothing will happen.\n\n";
    $message .= "To reset your password, visit the following address:\n\n";
    $message .= $reset_url . "\n";

    $sent = wp_mail($user->user_email, 'Password Reset Request', $message);

    if ($sent) {
        return new WP_REST_Response(array(
            'status' => true,
            'message' => 'Password reset email has been sent.'
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'Failed to send password reset email.'
        ), 500);
    }
}

function custom_rest_password_update() {
    register_rest_route('custom/v1', '/password-update', array(
        'methods' => 'POST',
        'callback' => 'custom_handle_password_update',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'custom_rest_password_update');

function custom_handle_password_update(WP_REST_Request $request) {
    $parameters = $request->get_json_params();
    $user_email = sanitize_email($parameters['email']); // Using email
    $reset_key = sanitize_text_field($parameters['key']);
    $new_password = sanitize_text_field($parameters['password']);

    if (empty($user_email) || empty($reset_key) || empty($new_password)) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'Missing required parameters.'
        ), 400);
    }

    $user = get_user_by('email', $user_email);
    if (!$user) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'Invalid email.'
        ), 400);
    }

    $check_key = check_password_reset_key($reset_key, $user->user_login);
    if (is_wp_error($check_key)) {
        return new WP_REST_Response(array(
            'status' => false,
            'message' => 'Invalid or expired reset key.'
        ), 400);
    }

    reset_password($user, $new_password);

    return new WP_REST_Response(array(
        'status' => true,
        'message' => 'Password has been reset successfully.'
    ), 200);
}


//   ____                                                      _      ____   _                                    
//  |  _ \    __ _   ___   ___  __      __   ___    _ __    __| |    / ___| | |__     __ _   _ __     __ _    ___ 
//  | |_) |  / _` | / __| / __| \ \ /\ / /  / _ \  | '__|  / _` |   | |     | '_ \   / _` | | '_ \   / _` |  / _ \
//  |  __/  | (_| | \__ \ \__ \  \ V  V /  | (_) | | |    | (_| |   | |___  | | | | | (_| | | | | | | (_| | |  __/
//  |_|      \__,_| |___/ |___/   \_/\_/    \___/  |_|     \__,_|    \____| |_| |_|  \__,_| |_| |_|  \__, |  \___|
//                                                                                                   |___/        
// Not Send Email...
// 

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/change-password', array(
        'methods' => 'POST',
        'callback' => 'handle_change_password',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function handle_change_password(WP_REST_Request $request) {
    // Get the current logged-in user ID
    $user_id = get_current_user_id();

    // Get the new password from the request
    $new_password = sanitize_text_field($request->get_param('new_password'));
    $current_password = sanitize_text_field($request->get_param('current_password'));

    // Check if the current password matches
    $user = get_user_by('ID', $user_id);
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        return new WP_Error('incorrect_password', 'The current password is incorrect.', array('status' => 403));
    }

    // Validate the new password (you can add more validation if needed)
    if (empty($new_password) || strlen($new_password) < 6) {
        return new WP_Error('invalid_password', 'The new password must be at least 6 characters long.', array('status' => 400));
    }

    // Update the user's password
    wp_set_password($new_password, $user_id);

    // Send a response
    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Password changed successfully.',
    ));
}


// Register the custom REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/users', [
        'methods' => 'GET',
        'callback' => 'get_custom_user_list',
//         'permission_callback' => 'is_user_logged_in',
		'permission_callback' => '__return_true',


    ]);
});


/**
 * Callback function to handle the custom user list endpoint
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function get_custom_user_list(WP_REST_Request $request) {
    $args = [
        'role__not_in' => ['Administrator'],
        'orderby' => 'registered',
        'order' => 'DESC',
    ];

    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    if (empty($users)) {
        return new WP_REST_Response([], 200);
    }

    $user_list = [];

    foreach ($users as $user) {
        // Get the 'account_status' meta field
        $account_status = get_user_meta($user->ID, 'account_status', true);
        $is_active = ($account_status === 'active') ? true : false; // Convert to boolean

        $user_list[] = [
            'id' => $user->ID,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'contact_number' => get_user_meta($user->ID, 'contact_number', true),
            'nid' => get_user_meta($user->ID, 'nid', true),
            'registered_date' => $user->user_registered,
            'is_active' => $is_active,
        ];
    }

    return new WP_REST_Response($user_list, 200);
}


//                                            _   _   _   
//   _   _   ___    ___   _ __      ___    __| | (_) | |_ 
//  | | | | / __|  / _ \ | '__|    / _ \  / _` | | | | __|
//  | |_| | \__ \ |  __/ | |      |  __/ | (_| | | | | |_ 
//   \__,_| |___/  \___| |_|       \___|  \__,_| |_|  \__|
//                                                       
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/update-user', array(
        'methods' => 'POST', // POST পদ্ধতি ব্যবহার হবে
        'callback' => 'update_user_details', // কলব্যাক ফাংশনের নাম
        'permission_callback' => 'is_user_logged_in',
    ));
});

// ইউজার আপডেট করার জন্য ফাংশন তৈরি করুন
function update_user_details($request) {
    // রিকোয়েস্ট থেকে ডেটা নিন এবং স্যানিটাইজ করুন
    $password = sanitize_text_field($request->get_param('password'));
    $email = sanitize_email($request->get_param('email'));
    $contact_number = sanitize_text_field($request->get_param('contact_number'));
    $nid = sanitize_text_field($request->get_param('nid'));

    // বর্তমান ইউজার আইডি নিন
    $user_id = get_current_user_id();
    
    // ইউজার তথ্য লোড করুন
    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'User not found', array('status' => 404));
    }

    // পাসওয়ার্ড আপডেট করা হলে
    if (!empty($password)) {
        // পাসওয়ার্ড স্ট্রং কিনা চেক করুন
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return new WP_Error('weak_password', 'Password should be at least 8 characters long and include at least one uppercase letter and one number', array('status' => 400));
        }
        wp_set_password($password, $user_id);
    }

    // ইমেইল আপডেট করা হলে
    if (!empty($email) && $email !== $user->user_email) {
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
        }
        if (email_exists($email)) {
            return new WP_Error('email_exists', 'Email already exists', array('status' => 400));
        }
        wp_update_user(array('ID' => $user_id, 'user_email' => $email));
    }

    // কন্টাক্ট নম্বর আপডেট করা হলে
    if (!empty($contact_number) && $contact_number !== get_user_meta($user_id, 'contact_number', true)) {
        // কন্টাক্ট নম্বর ইউনিক কিনা চেক করুন
        $existing_user = get_users(array(
            'meta_key' => 'contact_number',
            'meta_value' => $contact_number,
            'number' => 1,
            'fields' => 'ID'
        ));
        if (!empty($existing_user) && $existing_user[0] != $user_id) {
            return new WP_Error('contact_number_exists', 'Contact number already exists', array('status' => 400));
        }
        update_user_meta($user_id, 'contact_number', $contact_number);
    }

    // এনআইডি আপডেট করা হলে
    if (!empty($nid) && $nid !== get_user_meta($user_id, 'nid', true)) {
        // এনআইডি ইউনিক কিনা চেক করুন
        $existing_user = get_users(array(
            'meta_key' => 'nid',
            'meta_value' => $nid,
            'number' => 1,
            'fields' => 'ID'
        ));
        if (!empty($existing_user) && $existing_user[0] != $user_id) {
            return new WP_Error('nid_exists', 'NID already exists', array('status' => 400));
        }
        update_user_meta($user_id, 'nid', $nid);
    }

    // সফল হলে মেসেজ রিটার্ন করুন
    return array('message' => 'User details updated successfully');
}


//   _   _                          ____                           _       __  __                           _     _                  _              
//  | | | |  ___    ___   _ __     / ___|   ___    _   _   _ __   | |_    |  \/  |   __ _    ___    _ __   | |_  | |__   __      __ (_)  ___    ___ 
//  | | | | / __|  / _ \ | '__|   | |      / _ \  | | | | | '_ \  | __|   | |\/| |  / _` |  / _ \  | '_ \  | __| | '_ \  \ \ /\ / / | | / __|  / _ \
//  | |_| | \__ \ |  __/ | |      | |___  | (_) | | |_| | | | | | | |_    | |  | | | (_| | | (_) | | | | | | |_  | | | |  \ V  V /  | | \__ \ |  __/
//   \___/  |___/  \___| |_|       \____|  \___/   \__,_| |_| |_|  \__|   |_|  |_|  \__,_|  \___/  |_| |_|  \__| |_| |_|   \_/\_/   |_| |___/  \___|
                                                                                                                                                 


// Register the new API endpoint
function register_user_registration_count_endpoint() {
    register_rest_route('custom/v1', '/user_registration_count', array(
        'methods' => 'GET',
        'callback' => 'get_user_registration_count',
          'permission_callback' => function () {
            return current_user_can('manage_options');
        },  
    ));
}
add_action('rest_api_init', 'register_user_registration_count_endpoint');

// function get_user_registration_count(WP_REST_Request $request) {
//     global $wpdb;

//     // Initialize an array to store user counts by month
//     $user_counts = array();

// //     // Get the first user registration date
// //     $first_user_registered = $wpdb->get_var("SELECT MIN(user_registered) FROM $wpdb->users");
// //     if (!$first_user_registered) {
// //         return rest_ensure_response(array('message' => 'No users found.'));
// //     }

// $first_user_registered = $wpdb->get_var(
//     $wpdb->prepare(
//         "SELECT MIN(u.user_registered)
//         FROM $wpdb->users u
//         INNER JOIN $wpdb->prefix"."usermeta um ON u.ID = um.user_id
//         WHERE um.meta_key = %s AND um.meta_value = %s",
//         'account_status', // Adjust this key based on your actual meta key
//         'active' // Adjust this value based on your activation criteria
//     )
// );
// if (!$first_user_registered) {
//     return rest_ensure_response(array('message' => 'No activated users found.'));
// }	
//     // Get the start date from the first registration date
//     $start_date = new DateTime($first_user_registered);

//     // Get the current date
//     $current_date = new DateTime();

//     // Loop through each month from the start date to the current date
//     for ($date = clone $start_date; $date <= $current_date; $date->modify('+1 month')) {
//         $year_month = $date->format('Y-m');
//         $year = (int) $date->format('Y'); // Ensure year is an integer
//         $month = $date->format('F'); // Full month name

//         // Initialize count for the month to 0
//         if (!isset($user_counts[$year_month])) {
//             $user_counts[$year_month] = 0;
//         }

//         // Get the user count for the current month
//         $query = $wpdb->prepare(
//             "SELECT COUNT(*) FROM $wpdb->users WHERE DATE_FORMAT(user_registered, '%%Y-%%m') = %s",
//             $year_month
//         );
//         $count = $wpdb->get_var($query);

//         $user_counts[$year_month] += $count;
//     }

//     // Prepare the cumulative counts
//     $cumulative_counts = array();
//     $total_count = 0;

//     foreach ($user_counts as $year_month => $count) {
//         $date = DateTime::createFromFormat('Y-m', $year_month);
//         $month = $date->format('F'); // Full month name
//         $year = (int) $date->format('Y'); // Ensure year is an integer

//         $total_count += $count;
//         $cumulative_counts[] = array(
//             'month' => $month,
//             'year' => $year,
//             'user_count' => $total_count
//         );
//     }

//     return rest_ensure_response($cumulative_counts);
// }
function get_user_registration_count(WP_REST_Request $request) {
    global $wpdb;

    // Define the meta key and value for active users
    $user_status_meta_key = 'account_status'; // Replace with your actual meta key for user status
    $active_status = 'active'; // Replace with the value that indicates the user is active

    // Get the first user registration date for active subscribers
    $first_user_registered = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MIN(u.user_registered)
            FROM $wpdb->users u
            INNER JOIN $wpdb->prefix"."usermeta um ON u.ID = um.user_id
            WHERE um.meta_key = %s AND um.meta_value = %s
            AND EXISTS (
                SELECT 1 FROM $wpdb->prefix"."usermeta um2
                WHERE um2.user_id = u.ID
                AND um2.meta_key = %s
                AND um2.meta_value LIKE %s
            )",
            $user_status_meta_key,
            $active_status,
            $wpdb->prefix . 'capabilities', // Meta key for user roles
            '%subscriber%' // Meta value indicating the user role
        )
    );

    if (!$first_user_registered) {
        return rest_ensure_response(array('message' => 'No active subscribers found.'));
    }

    // Get the start date from the first registration date
    $start_date = new DateTime($first_user_registered);

    // Get the current date
    $current_date = new DateTime();

    // Initialize an array to store user counts by month
    $user_counts = array();

    // Loop through each month from the start date to the current date
    for ($date = clone $start_date; $date <= $current_date; $date->modify('+1 month')) {
        $year_month = $date->format('Y-m');
        $year = (int) $date->format('Y'); // Ensure year is an integer
        $month = $date->format('F'); // Full month name

        // Initialize count for the month to 0
        if (!isset($user_counts[$year_month])) {
            $user_counts[$year_month] = 0;
        }

        // Get the user count for the current month, filtering for active subscribers
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->users u
            INNER JOIN $wpdb->prefix"."usermeta um ON u.ID = um.user_id
            WHERE DATE_FORMAT(u.user_registered, '%%Y-%%m') = %s
            AND um.meta_key = %s AND um.meta_value LIKE %s
            AND EXISTS (
                SELECT 1 FROM $wpdb->prefix"."usermeta um2
                WHERE um2.user_id = u.ID
                AND um2.meta_key = %s
                AND um2.meta_value = %s
            )",
            $year_month,
            $wpdb->prefix . 'capabilities', // Meta key for user roles
            '%subscriber%', // Meta value indicating the user role
            $user_status_meta_key,
            $active_status
        );
        $count = $wpdb->get_var($query);

        $user_counts[$year_month] += $count;
    }

    // Prepare the cumulative counts
    $cumulative_counts = array();
    $total_count = 0;

    foreach ($user_counts as $year_month => $count) {
        $date = DateTime::createFromFormat('Y-m', $year_month);
        $month = $date->format('F'); // Full month name
        $year = (int) $date->format('Y'); // Ensure year is an integer

        $total_count += $count;
        $cumulative_counts[] = array(
            'month' => $month,
            'year' => $year,
            'user_count' => $total_count
        );
    }

    return rest_ensure_response($cumulative_counts);
}





?>