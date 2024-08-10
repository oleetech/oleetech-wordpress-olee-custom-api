<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom PHP in this file.
 * Only edit this file if you have direct access to it on your server (to fix errors if they happen).
 */

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
    $activation_link = add_query_arg(array('key' => $activation_key, 'user' => $user_id), get_site_url(null, 'wp-json/custom/v1/activate'));
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
        return new WP_Error('invalid_key', 'Invalid activation key', array('status' => 400));
    }

    update_user_meta($user_id, 'account_status', 'active');
    delete_user_meta($user_id, 'activation_key');

    return new WP_REST_Response(array('message' => 'Account activated successfully'), 200);
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
        'permission_callback' => '__return_true',
        'args' => array(
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
                'description' => 'The contact number for the user',
            ),
        ),
    ));
}
add_action('rest_api_init', 'custom_user_update_endpoint');

function handle_user_update($request) {
    $user = wp_get_current_user();

    // Update user meta fields
    if ($request->get_param('first_name')) {
        update_user_meta($user->ID, 'first_name', sanitize_text_field($request->get_param('first_name')));
    }
    if ($request->get_param('last_name')) {
        update_user_meta($user->ID, 'last_name', sanitize_text_field($request->get_param('last_name')));
    }
    if ($request->get_param('marital_status')) {
        update_user_meta($user->ID, 'marital_status', sanitize_text_field($request->get_param('marital_status')));
    }
    if ($request->get_param('nid')) {
        update_user_meta($user->ID, 'nid', sanitize_text_field($request->get_param('nid')));
    }

    // Always update contact number
    $contact_number = sanitize_text_field($request->get_param('contact_number'));
    if (!preg_match('/^[0-9]{11}$/', $contact_number)) {
        return new WP_Error('invalid_contact_number', 'Invalid contact number format (should be 11 digits)', array('status' => 400));
    }
    update_user_meta($user->ID, 'contact_number', $contact_number);

    return new WP_REST_Response(array('message' => 'User information updated successfully.'), 200);
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
    $user_login = sanitize_text_field($parameters['user_login']);

    if (empty($user_login)) {
        return new WP_Error('empty_username', 'Please enter a username or email address.', array('status' => 400));
    }

    if (is_email($user_login)) {
        $user = get_user_by('email', $user_login);
    } else {
        $user = get_user_by('login', $user_login);
    }

    if (!$user) {
        return new WP_Error('invalid_username', 'No user found with this email address or username.', array('status' => 400));
    }

    $reset_key = get_password_reset_key($user);

    if (is_wp_error($reset_key)) {
        return new WP_Error('password_reset_failed', $reset_key->get_error_message(), array('status' => 500));
    }

    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    $message = "Someone has requested a password reset for the following account:\n\n";
    $message .= "Username: " . $user->user_login . "\n\n";
    $message .= "If this was a mistake, just ignore this email and nothing will happen.\n\n";
    $message .= "To reset your password, visit the following address:\n\n";
    $message .= $reset_url . "\n";

    $sent = wp_mail($user->user_email, 'Password Reset Request', $message);

    if ($sent) {
        return new WP_REST_Response('Password reset email has been sent.', 200);
    } else {
        return new WP_Error('email_failed', 'Failed to send password reset email.', array('status' => 500));
    }
}


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
    ));


}
add_action('init', 'create_custom_post_types');
                         
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/story', array(
        'methods' => 'POST',
        'callback' => 'create_story',
        'permission_callback' => 'is_user_logged_in',
    ));

    register_rest_route('custom/v1', '/story/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_story',
        'permission_callback' => 'is_user_logged_in',
    ));



    register_rest_route('custom/v1', '/update-story/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'update_story_custom',
        'permission_callback' => 'is_user_logged_in',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param); // Validate ID as numeric
                }
            ),
        ),
        'show_in_index' => false,
    ));
	
    register_rest_route('custom/v1', '/story/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_story',
        'permission_callback' => 'is_user_logged_in',
    ));
});

function create_story($request) {
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));
    $feature_image = $request->get_file_params()['file'];

    // নতুন স্টোরি তৈরির জন্য পোস্ট অ্যারে প্রস্তুত করছি
    $story_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'story',
    );

    // স্টোরি তৈরি করছি
    $story_id = wp_insert_post($story_data);

    // যদি স্টোরি তৈরি হয়
    if ($story_id && !is_wp_error($story_id)) {
        // ফিচার ইমেজ আপলোড করছি
        $image_upload_response = handle_image_upload($request);

        // ফিচার ইমেজ আপলোড সফল হলে সেটাকে স্টোরির ফিচার ইমেজ হিসেবে সেট করছি
        if (!is_wp_error($image_upload_response)) {
            set_post_thumbnail($story_id, $image_upload_response->data['image_id']);

            // আপডেটিং স্টোরি উইথ ফিচারড মিডিয়া
            wp_update_post(array(
                'ID' => $story_id,
                'meta_input' => array(
                    '_thumbnail_id' => $image_upload_response->data['image_id'],
                ),
            ));
        }

        // স্টোরি আইডি এবং অন্যান্য তথ্য রিটার্ন করছি
        return rest_ensure_response(array(
            'story_id' => $story_id,
            'title' => $title,
            'content' => $content,
            'feature_image_url' => $image_upload_response->data['image_url'],
        ));
    } else {
        return new WP_Error('create_story_error', 'Error creating story', array('status' => 500));
    }
}


function get_story($request) {
    $id = (int) $request['id'];

    // স্টোরি ডেটা প্রাপ্ত করছি
    $story = get_post($id);

    // যদি স্টোরি খুঁজে পাওয়া যায়
    if ($story && $story->post_type === 'story') {
        // ফিচার ইমেজ URL প্রাপ্ত করছি
        $feature_image_url = get_the_post_thumbnail_url($story->ID);

        // স্টোরি ডেটা রিটার্ন করছি
        return rest_ensure_response(array(
            'id' => $story->ID,
            'title' => $story->post_title,
            'content' => $story->post_content,
            'feature_image_url' => $feature_image_url,
        ));
    } else {
        return new WP_Error('story_not_found', 'Story not found', array('status' => 404));
    }
}

// স্টোরি আপডেট করার জন্য কলব্যাক ফাংশন
function update_story_custom(WP_REST_Request $request) {
    $story_id = $request->get_param('id');
    $title = sanitize_text_field($request->get_param('title'));
    $content = sanitize_textarea_field($request->get_param('content'));

    // Check if title or content is empty
    if (empty($title) || empty($content)) {
        return new WP_REST_Response(array('message' => 'Title or content is empty'), 400);
    }

    // Handle file upload if a new file is provided
    $files = $request->get_file_params();
    $file_path = '';

    if (!empty($files['file'])) {
        // Process file upload
        $file = $files['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error']) && $upload['error'] !== false) {
            return new WP_REST_Response(array('message' => $upload['error']), 500);
        }

        // Successfully uploaded file
        $file_path = $upload['file'];
        $file_type = wp_check_filetype(basename($file_path), null);
        $attachment = array(
            'guid' => $upload['url'],
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name(basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file_path);

        if (!is_wp_error($attach_id)) {
            // Delete previous featured image if exists
            $prev_thumbnail_id = get_post_thumbnail_id($story_id);
            if ($prev_thumbnail_id) {
                wp_delete_attachment($prev_thumbnail_id, true);
            }

            // Set the new post thumbnail (featured image)
            set_post_thumbnail($story_id, $attach_id);

            // Update additional post meta or fields as needed
        } else {
            return new WP_REST_Response(array('message' => 'Failed to attach file'), 500);
        }
    }

    // Update story's title and content
    $updated_post = array(
        'ID' => $story_id,
        'post_title' => $title,
        'post_content' => $content,
    );

    // Update the post
    $post_updated = wp_update_post($updated_post, true);

    if (is_wp_error($post_updated)) {
        return new WP_REST_Response(array('message' => $post_updated->get_error_message()), 500);
    }

    // Return response indicating success
    return new WP_REST_Response(array(
        'message' => 'Story updated successfully',
        'story_id' => $story_id,
        'title' => $title,
        'content' => $content,
        'file_uploaded' => !empty($file_path) ? $file_path : null,
    ), 200);
}






function delete_story($request) {
    $id = (int) $request['id'];

    // স্টোরি প্রাপ্ত করছি
    $story = get_post($id);

    // যদি স্টোরি খুঁজে পাওয়া যায়
    if ($story && $story->post_type === 'story') {
        // ফিচার ইমেজ আইডি প্রাপ্ত করছি
        $thumbnail_id = get_post_thumbnail_id($story->ID);

        // স্টোরি ডিলিট করছি
        $deleted = wp_delete_post($id, true);

        // যদি স্টোরি সফলভাবে ডিলিট হয়
        if ($deleted) {
            // ফিচার ইমেজও ডিলিট করছি
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }

            // সফলভাবে ডিলিট হয়েছে রিটার্ন করছি
            return rest_ensure_response(array(
                'message' => 'Story deleted successfully',
                'story_id' => $id,
            ));
        } else {
            return new WP_Error('delete_story_error', 'Error deleting story', array('status' => 500));
        }
    } else {
        return new WP_Error('story_not_found', 'Story not found', array('status' => 404));
    }
}





//                          _                    _        __                            
//    ___    ___    _ __   | |_    __ _    ___  | |_     / _|   ___    _ __   _ __ ___  
//   / __|  / _ \  | '_ \  | __|  / _` |  / __| | __|   | |_   / _ \  | '__| | '_ ` _ \ 
//  | (__  | (_) | | | | | | |_  | (_| | | (__  | |_    |  _| | (_) | | |    | | | | | |
//   \___|  \___/  |_| |_|  \__|  \__,_|  \___|  \__|   |_|    \___/  |_|    |_| |_| |_|
//
//                                                                                     

// https://pahona.org/api/wp-json/custom/v1/contact-form
// {
//     "name": "John Doe",
//     "email": "john.doe@example.com",
//     "subject": "Example Subject",
//     "message": "Hello, this is a test message."
// }

// Register REST API endpoint for contact form submission
function custom_contact_form_endpoint() {
    register_rest_route('custom/v1', '/contact-form', array(
        'methods' => 'POST',
        'callback' => 'handle_contact_form_submission',
        'permission_callback' => '__return_true',
        'args' => array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Name of the person submitting the form',
            ),
            'email' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Email address of the person submitting the form',
            ),
            'subject' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Subject of the message',
            ),
            'message' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Message content',
            ),
        ),
        'schema' => array(
            'type' => 'object',
            'properties' => array(
                'message' => array(
                    'type' => 'string',
                    'description' => 'A message indicating the result of the contact form submission',
                ),
            ),
        ),
    ));
}

add_action('rest_api_init', 'custom_contact_form_endpoint');

// Handle contact form submission callback
function handle_contact_form_submission($request) {
    $name = sanitize_text_field($request->get_param('name'));
    $email = sanitize_email($request->get_param('email'));
    $subject = sanitize_text_field($request->get_param('subject'));
    $message = sanitize_textarea_field($request->get_param('message'));

    // Validate email
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email format', array('status' => 400));
    }

    // Send email to admin or custom email address
    $admin_email = get_option('admin_email');
    $sent = wp_mail($admin_email, $subject, $message, "From: $name <$email>");

    if (!$sent) {
        return new WP_Error('email_send_failed', 'Failed to send email', array('status' => 500));
    }

    return new WP_REST_Response(array('message' => 'Contact form submitted successfully'), 200);
}

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
        'all_items' => __('All Appointments'),
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

// Register REST API Routes
function register_appointment_rest_routes() {
    register_rest_route('custom/v1', '/appointment/all', array(
        'methods'             => 'GET',
        'callback'            => 'get_all_appointments',
        'permission_callback' => '__return_true', // Protect with JWT Authentication
    ));

    register_rest_route('custom/v1', '/appointment/create', array(
        'methods'             => 'POST',
        'callback'            => 'create_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user jwt_auth_permission_callback
    ));

    register_rest_route('custom/v1', '/appointment/update/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => 'update_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));

    register_rest_route('custom/v1', '/appointment/delete/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'callback'            => 'delete_appointment',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));

    register_rest_route('custom/v1', '/appointment/update-status/(?P<id>\d+)', array(
        'methods'             => 'POST',
        'callback'            => 'update_appointment_status',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));
    
    register_rest_route('custom/v1', '/appointment/user/(?P<user_id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'get_appointments_by_user',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user
    ));    
}
add_action('rest_api_init', 'register_appointment_rest_routes');

// Get All Appointments Function
function get_all_appointments() {
    $args = array(
        'post_type' => 'appointment',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft','requested','approved','cancelled')
    );

    $appointments = get_posts($args);
    $data = array();

    foreach ($appointments as $appointment) {
        $data[] = array(
            'id' => $appointment->ID,
            'status' => $appointment->post_status, // Include the status in the response
            'date' => get_post_meta($appointment->ID, 'appointment_date', true),
            'time' => get_post_meta($appointment->ID, 'appointment_time', true),
            'name' => get_post_meta($appointment->ID, 'appointment_name', true),
            'email' => get_post_meta($appointment->ID, 'appointment_email', true),
            'phone' => get_post_meta($appointment->ID, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment->ID, 'appointment_status', true) // Include custom meta field
        );
    }

    return new WP_REST_Response($data, 200);
}

// Create Appointment Function
function create_appointment(WP_REST_Request $request) {
    $params = $request->get_json_params();

    // Ensure all required fields are present
    if (!isset($params['appointment_date'], $params['appointment_time'], $params['appointment_name'], $params['appointment_email'], $params['appointment_phone'])) {
        return new WP_REST_Response(array('message' => 'Missing required fields'), 400);
    }

    $new_appointment = array(
        'post_title'    => sanitize_text_field($params['appointment_name']),
        'post_status'   => 'draft', // Set initial status to 'draft'
        'post_type'     => 'appointment'
    );

    $appointment_id = wp_insert_post($new_appointment);

    if ($appointment_id) {
        // Update meta fields
        update_post_meta($appointment_id, 'appointment_date', sanitize_text_field($params['appointment_date']));
        update_post_meta($appointment_id, 'appointment_time', sanitize_text_field($params['appointment_time']));
        update_post_meta($appointment_id, 'appointment_name', sanitize_text_field($params['appointment_name']));
        update_post_meta($appointment_id, 'appointment_email', sanitize_email($params['appointment_email']));
        update_post_meta($appointment_id, 'appointment_phone', sanitize_text_field($params['appointment_phone']));

        // Optional: Set appointment status if provided
        if (isset($params['appointment_status'])) {
            update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));
        }

        return new WP_REST_Response(array('message' => 'Appointment created successfully', 'appointment_id' => $appointment_id), 201);
    }

    return new WP_REST_Response(array('message' => 'Failed to create appointment'), 500);
}


// Update Appointment Function
function update_appointment(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $appointment_id = (int) $request['id'];

    $updated_appointment = array(
        'ID' => $appointment_id,
        'post_title' => isset($params['appointment_name']) ? sanitize_text_field($params['appointment_name']) : '',
        'post_status' => isset($params['status']) ? sanitize_text_field($params['status']) : 'draft'
    );

    $appointment_id = wp_update_post($updated_appointment);

    if ($appointment_id) {
        // Update meta fields
        if (isset($params['appointment_date'])) {
            update_post_meta($appointment_id, 'appointment_date', sanitize_text_field($params['appointment_date']));
        }

        if (isset($params['appointment_time'])) {
            update_post_meta($appointment_id, 'appointment_time', sanitize_text_field($params['appointment_time']));
        }

        if (isset($params['appointment_name'])) {
            update_post_meta($appointment_id, 'appointment_name', sanitize_text_field($params['appointment_name']));
        }

        if (isset($params['appointment_email'])) {
            update_post_meta($appointment_id, 'appointment_email', sanitize_email($params['appointment_email']));
        }

        if (isset($params['appointment_phone'])) {
            update_post_meta($appointment_id, 'appointment_phone', sanitize_text_field($params['appointment_phone']));
        }

        if (isset($params['appointment_status'])) {
            update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));
        }

        return new WP_REST_Response(array('message' => 'Appointment updated successfully', 'appointment_id' => $appointment_id), 200);
    }

    return new WP_REST_Response(array('message' => 'Failed to update appointment'), 500);
}


// Delete Appointment Function
function delete_appointment(WP_REST_Request $request) {
    $appointment_id = (int) $request['id'];

    if (wp_delete_post($appointment_id)) {
        return new WP_REST_Response(array('message' => 'Appointment deleted successfully'), 200);
    }

    return new WP_REST_Response(array('message' => 'Failed to delete appointment'), 500);
}

// Update Appointment Status Function
function update_appointment_status(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $appointment_id = (int) $request['id'];

    // Check if appointment ID and status are provided
    if (!$appointment_id || !isset($params['appointment_status'])) {
        return new WP_REST_Response(array('message' => 'Missing appointment ID or appointment status'), 400);
    }

    // Update only appointment_status meta field
    update_post_meta($appointment_id, 'appointment_status', sanitize_text_field($params['appointment_status']));

    return new WP_REST_Response(array('message' => 'Appointment status updated successfully', 'appointment_id' => $appointment_id), 200);
}

// Get Appointments by User ID Function
function get_appointments_by_user(WP_REST_Request $request) {
    $user_id = (int) $request['user_id'];

    $args = array(
        'post_type' => 'appointment',
        'posts_per_page' => -1,
        'author' => $user_id,
        'post_status' => array('publish', 'draft')
    );

    $appointments = get_posts($args);
    $data = array();

    foreach ($appointments as $appointment) {
        $data[] = array(
            'id' => $appointment->ID,
            'status' => $appointment->post_status, // Include the status in the response
            'date' => get_post_meta($appointment->ID, 'appointment_date', true),
            'time' => get_post_meta($appointment->ID, 'appointment_time', true),
            'name' => get_post_meta($appointment->ID, 'appointment_name', true),
            'email' => get_post_meta($appointment->ID, 'appointment_email', true),
            'phone' => get_post_meta($appointment->ID, 'appointment_phone', true),
            'appointment_status' => get_post_meta($appointment->ID, 'appointment_status', true) // Include custom meta field
        );
    }

    return new WP_REST_Response($data, 200);
}



//   ___            _         _     _                        _                  _        _                                                  
//   / _ \   _   _  (_)  ___  | |_  (_)   ___    _ __        / \     _ __     __| |      / \     _ __    ___  __      __   ___   _ __    ___ 
//  | | | | | | | | | | / __| | __| | |  / _ \  | '_ \      / _ \   | '_ \   / _` |     / _ \   | '_ \  / __| \ \ /\ / /  / _ \ | '__|  / _ \
//  | |_| | | |_| | | | \__ \ | |_  | | | (_) | | | | |    / ___ \  | | | | | (_| |    / ___ \  | | | | \__ \  \ V  V /  |  __/ | |    |  __/
//   \__\_\  \__,_| |_| |___/  \__| |_|  \___/  |_| |_|   /_/   \_\ |_| |_|  \__,_|   /_/   \_\ |_| |_| |___/   \_/\_/    \___| |_|     \___|
                                                                                                                                          

// Register Question Post Type
function register_question_post_type() {
    register_post_type('question', array(
        'labels' => array(
            'name' => __('Questions'),
            'singular_name' => __('Question'),
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'questions'),
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
    ));
}
add_action('init', 'register_question_post_type');

// মেটা বক্স যোগ করা
function add_question_meta_boxes() {
    add_meta_box(
        'question_option_meta_box', // আইডি
        __('Question Option', 'textdomain'), // শিরোনাম
        'display_question_option_meta_box', // কলব্যাক
        'question', // পোস্ট টাইপ
        'normal', // প্রসঙ্গ
        'default' // অগ্রাধিকার
    );
}
add_action('add_meta_boxes', 'add_question_meta_boxes');


// প্রশ্ন অপশন মেটা বক্স প্রদর্শন করা
function display_question_option_meta_box($post) {
    $question_type = get_post_meta($post->ID, '_question_type', true);
    $mcq_options = get_post_meta($post->ID, '_mcq_options', true);

    // নন্স ফিল্ড যোগ করা
    wp_nonce_field('question_meta_box', 'question_meta_box_nonce');
    ?>
    <label for="question_type"><?php _e('Select Question Type:', 'textdomain'); ?></label>
    <select name="question_type" id="question_type">
        <option value="text" <?php selected($question_type, 'text'); ?>><?php _e('Text', 'textdomain'); ?></option>
        <option value="number" <?php selected($question_type, 'number'); ?>><?php _e('Number', 'textdomain'); ?></option>
        <option value="mcq" <?php selected($question_type, 'mcq'); ?>><?php _e('MCQ', 'textdomain'); ?></option>
    </select>

    <label for="mcq_options" style="margin-top: 20px;"><?php _e('MCQ Options (comma separated):', 'textdomain'); ?></label>
    <input type="text" name="mcq_options" id="mcq_options" value="<?php echo esc_attr($mcq_options); ?>" />
    <?php
}



// মেটা বক্সের ডাটা সংরক্ষণ করা
function save_question_meta_boxes($post_id) {
    // আমাদের নন্স সেট করা হয়েছে কিনা পরীক্ষা করা।
    if (!isset($_POST['question_meta_box_nonce'])) {
        return;
    }

    // নন্স বৈধ কিনা যাচাই করা।
    if (!wp_verify_nonce($_POST['question_meta_box_nonce'], 'question_meta_box')) {
        return;
    }

    // যদি এটি একটি অটোসেভ হয়, তাহলে কিছু করা হবে না।
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // ব্যবহারকারীর অনুমতি যাচাই করা।
    if (isset($_POST['post_type']) && 'question' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
    }
    }

    // প্রশ্ন টাইপ স্যানিটাইজ এবং সংরক্ষণ করা
    if (isset($_POST['question_type'])) {
        $question_type = sanitize_text_field($_POST['question_type']);
        update_post_meta($post_id, '_question_type', $question_type);
    }

    // MCQ অপশন স্যানিটাইজ এবং সংরক্ষণ করা
    if (isset($_POST['mcq_options'])) {
        $mcq_options = sanitize_text_field($_POST['mcq_options']);
        update_post_meta($post_id, '_mcq_options', $mcq_options);
    }
}
add_action('save_post', 'save_question_meta_boxes');


// কাস্টম রেস্ট এপিআই রুট নিবন্ধন করা
function register_custom_api_routes() {
    register_rest_route('custom/v1', '/questions', array(
        'methods' => 'POST',
        'callback' => 'create_question',
        'permission_callback' => 'is_user_logged_in', // Require logged-in user

    ));

    register_rest_route('custom/v1', '/questions', array(
        'methods' => 'GET',
        'callback' => 'get_questions',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_question',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'update_question',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
    ));

    register_rest_route('custom/v1', '/questions/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_question',
        'permission_callback' => function() {
            return current_user_can('delete_posts');
        },
    ));
}
add_action('rest_api_init', 'register_custom_api_routes');


// Create Question Callback Function
function create_question($request) {
    $params = $request->get_json_params();

    // Sanitize and validate input
    $title = sanitize_text_field($params['title']);
    $mcq_options = isset($params['mcq']) ? sanitize_text_field($params['mcq']) : '';

    // Determine question type based on mcq_options
    $question_type = ! empty($mcq_options) ? 'mcq' : 'text';

    // Insert the question post
    $post_id = wp_insert_post(array(
        'post_title' => $title,
        'post_type' => 'question',
        'post_status' => 'publish',
    ));

    if ($post_id && ! is_wp_error($post_id)) {
        // Update post meta with question type and mcq options
        update_post_meta($post_id, '_question_type', $question_type);
        if (! empty($mcq_options)) {
            update_post_meta($post_id, '_mcq_options', $mcq_options);
        }

        // Return success response
        return new WP_REST_Response(array('message' => 'Question created successfully', 'id' => $post_id), 201);
    } else {
        // Handle error case
        $error_message = $post_id->get_error_message();
        return new WP_Error('cannot_create', $error_message, array('status' => 500));
    }
}


// সমস্ত প্রশ্ন পুনরুদ্ধার করা
function get_questions($request) {
    $args = array(
        'post_type' => 'question',
        'posts_per_page' => -1, // Get all posts
        'post_status' => 'publish',
    );

    $questions = get_posts($args);

    $formatted_questions = array_map(function($question) {
        $question_id = $question->ID;
        $question_type = get_post_meta($question_id, '_question_type', true);
        $mcq_options_raw = get_post_meta($question_id, '_mcq_options', true);

        // Convert mcq_options from comma-separated string to array
        $mcq_options = ! empty($mcq_options_raw) ? explode(',', $mcq_options_raw) : [];

        return array(
            'id' => $question_id,
            'title' => $question->post_title,
            'question_type' => $question_type,
            'mcq_options' => $mcq_options, // Return as array
        );
    }, $questions);

    return new WP_REST_Response($formatted_questions, 200);
}

// Function to get a single question
function get_question($request) {
    $question_id = $request['id'];

    $question = get_post($question_id);

    if (! $question || $question->post_type !== 'question') {
        return new WP_Error('not_found', 'Question not found', array('status' => 404));
    }

    // Get question meta
    $question_type = get_post_meta($question_id, '_question_type', true);
    $mcq_options_raw = get_post_meta($question_id, '_mcq_options', true);

    // Convert mcq_options from comma-separated string to array
    $mcq_options = ! empty($mcq_options_raw) ? explode(',', $mcq_options_raw) : [];

    // Prepare response
    $response = array(
        'id' => $question_id,
        'title' => $question->post_title,
        'question_type' => $question_type,
        'mcq_options' => $mcq_options, // Return as array
    );

    return new WP_REST_Response($response, 200);
}



// প্রশ্ন আপডেট করা
function update_question($request) {
    $id = (int) $request['id'];
    $params = $request->get_json_params();
    $title = sanitize_text_field($params['title']);
    $question_type = sanitize_text_field($params['type']);
    $mcq_options = isset($params['mcq']) ? sanitize_text_field($params['mcq']) : '';

    $post = array(
        'ID' => $id,
        'post_title' => $title,
    );

    // Update the question post
    if (wp_update_post($post)) {
        // Update question type and mcq options meta
        update_post_meta($id, '_question_type', $question_type);

        // Handle mcq_options as an array if provided
        if (! empty($mcq_options)) {
            $mcq_options_array = explode(',', $mcq_options);
            update_post_meta($id, '_mcq_options', $mcq_options_array);
        } else {
            // If mcq_options is empty, update as empty array
            update_post_meta($id, '_mcq_options', []);
        }

        return new WP_REST_Response(array('message' => 'Question updated successfully', 'id' => $id), 200);
    }

    return new WP_Error('cannot_update', 'Unable to update question', array('status' => 500));
}

// প্রশ্ন মুছে ফেলা
function delete_question($request) {
    $id = (int) $request['id'];

    if (wp_delete_post($id, true)) {
        return new WP_REST_Response(array('message' => 'Question deleted successfully'), 200);
    }

    return new WP_Error('cannot_delete', 'Unable to delete question', array('status' => 500));
}

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


function register_custom_endpoints() {


	
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
add_action('rest_api_init', 'register_custom_endpoints');








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









public function generate_token(WP_REST_Request $request) {
    $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
    $username = $request->get_param('username');
    $password = $request->get_param('password');

    /** First thing, check the secret key if not exist return an error */
    if (!$secret_key) {
        return new WP_Error(
            'jwt_auth_bad_config',
            __('JWT is not configured properly, please contact the admin', 'wp-api-jwt-auth'),
            ['status' => 403]
        );
    }

    /** Try to authenticate the user with the passed credentials */
    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        // Check if the error is due to an invalid username
        if ($user->get_error_code() === 'invalid_username') {
            return new WP_Error(
                'invalid_username',
                __('Invalid username or password. Please check your credentials and try again.', 'wp-api-jwt-auth'),
                ['status' => 403]
            );
        }

        // For other errors, return the default error message
        return $user;
    }

    // Check if user is an admin
    $is_admin = user_can($user, 'manage_options');

    // If the user is not an admin, check if the account is active
    if (!$is_admin) {
        $account_status = get_user_meta($user->ID, 'account_status', true);
        if ($account_status !== 'active') {
            return new WP_Error(
                'account_not_active',
                __('Your account is not active. Please check your email for the activation link.', 'wp-api-jwt-auth'),
                ['status' => 403]
            );
        }
    }

    /** Valid credentials, the user exists create the according Token */
    $issuedAt = time();
    $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
    $expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

    // Retrieve user's first name and last name
    $first_name = get_user_meta($user->ID, 'first_name', true);
    $last_name = get_user_meta($user->ID, 'last_name', true);
    $user_id = $user->ID;

    // Determine role based on admin status
    $user_role = $is_admin ? 'ADMIN' : 'SUBSCRIBER';

    $token = [
        'iss' => get_bloginfo('url'),
        'iat' => $issuedAt,
        'nbf' => $notBefore,
        'exp' => $expire,
        'data' => [
            'user' => [
                'id' => $user->data->ID,
                'role' => $user_role,
            ],
        ],
    ];

    /** Let the user modify the token data before the sign. */
    $algorithm = $this->get_algorithm();

    if ($algorithm === false) {
        return new WP_Error(
            'jwt_auth_unsupported_algorithm',
            __('Algorithm not supported, see https://www.rfc-editor.org/rfc/rfc7518#section-3', 'wp-api-jwt-auth'),
            ['status' => 403]
        );
    }

    $token = JWT::encode(
        apply_filters('jwt_auth_token_before_sign', $token, $user),
        $secret_key,
        $algorithm
    );

    /** The token is signed, now create the object with no sensible user data to the client */
    $data = [
        'token' => $token,
        'user_email' => $user->data->user_email,
        'user_nickname' => $user->data->user_nicename,
        'user_display_name' => $user->data->display_name,
        'user_id' => $user_id,
        'role' => $user_role,
    ];

    /** Let the user modify the data before send it back */
    return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
}

// 	public function generate_token( WP_REST_Request $request ) {
// 		$secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : false;
// 		$username   = $request->get_param( 'username' );
// 		$password   = $request->get_param( 'password' );

// 		/** First thing, check the secret key if not exist return an error*/
// 		if ( ! $secret_key ) {
// 			return new WP_Error(
// 				'jwt_auth_bad_config',
// 				__( 'JWT is not configured properly, please contact the admin', 'wp-api-jwt-auth' ),
// 				[
// 					'status' => 403,
// 				]
// 			);
// 		}
// 		/** Try to authenticate the user with the passed credentials*/
// 		$user = wp_authenticate( $username, $password );

	

// 		if ( is_wp_error( $user ) ) {
// 			// Check if the error is due to an invalid username
// 			if ( $user->get_error_code() === 'invalid_username' ) {
// 				return new WP_Error(
// 					'invalid_username',
// 					__( 'Invalid username or password. Please check your credentials and try again.', 'wp-api-jwt-auth' ),
// 					[
// 						'status' => 403,
// 					]
// 				);
// 			}

// 			// For other errors, return the default error message
// 			return $user;
// 		}
// 		/** Valid credentials, the user exists create the according Token */
// 		$issuedAt  = time();
// 		$notBefore = apply_filters( 'jwt_auth_not_before', $issuedAt, $issuedAt );
// 		$expire    = apply_filters( 'jwt_auth_expire', $issuedAt + ( DAY_IN_SECONDS * 7 ), $issuedAt );
// 		// Retrieve user's first name and last name
// 		$first_name = get_user_meta( $user->ID, 'first_name', true );
// 		$last_name  = get_user_meta( $user->ID, 'last_name', true );
// 		$user_id    = $user->ID;
		

// 		// Check if user is super admin
// 		$is_super_admin = is_super_admin( $user->ID );		
		
// 		// Determine role based on super admin status
// 		$user_role = $is_super_admin ? 'ADMIN' : 'SUBSCRIBER';		
// 		$token = [
// 			'iss'  => get_bloginfo( 'url' ),
// 			'iat'  => $issuedAt,
// 			'nbf'  => $notBefore,
// 			'exp'  => $expire,
// 			'data' => [
// 				'user' => [
// 					'id' => $user->data->ID,
// 					'role'        => $user_role, 
// 				],
// 			],
// 		];

// 		/** Let the user modify the token data before the sign. */
// 		$algorithm = $this->get_algorithm();

// 		if ( $algorithm === false ) {
// 			return new WP_Error(
// 				'jwt_auth_unsupported_algorithm',
// 				__( 'Algorithm not supported, see https://www.rfc-editor.org/rfc/rfc7518#section-3',
// 					'wp-api-jwt-auth' ),
// 				[
// 					'status' => 403,
// 				]
// 			);
// 		}

// 		$token = JWT::encode(
// 			apply_filters( 'jwt_auth_token_before_sign', $token, $user ),
// 			$secret_key,
// 			$algorithm
// 		);

// 		/** The token is signed, now create the object with no sensible user data to the client*/
// 		$data = [
// 			'token'             => $token,
// 			'user_email'        => $user->data->user_email,
// 			'user_nickname'     => $user->data->user_nicename,
// 			'user_display_name' => $user->data->display_name,
// // 			'user_first_name'   => $first_name,
// // 			'user_last_name'    => $last_name,
// 			'user_id'    => $user_id,
// 			'role'        => $user_role, 
			
// 		];

// 		/** Let the user modify the data before send it back */
// 		return apply_filters( 'jwt_auth_token_before_dispatch', $data, $user );
// 	}	
?>