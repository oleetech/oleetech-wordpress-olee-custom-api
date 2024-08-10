<?php
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
        'show_in_menu' => 'olee-custom-api', 

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
?>