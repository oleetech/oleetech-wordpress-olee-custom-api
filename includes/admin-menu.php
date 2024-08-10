<?php

// Add the admin menu and submenu
function olee_custom_api_admin_menu() {
    // Add main menu
    add_menu_page(
        'Olee Custom Api',         // Page title
        'Olee Custom Api',         // Menu title
        'manage_options',          // Capability
        'olee-custom-api',         // Menu slug
        'olee_custom_api_main_page', // Function to display the main page content
        'dashicons-admin-generic'  // Icon URL or dashicons
    );

    // Add submenu
    add_submenu_page(
        'olee-custom-api',         // Parent slug
        'User Api',                // Page title
        'User Api',                // Menu title
        'manage_options',          // Capability
        'olee-custom-api-user',    // Menu slug
        'olee_custom_api_user_page' // Function to display the submenu page content
    );
     // Add Contact Us submenu
     add_submenu_page(
        'olee-custom-api',         // Parent slug
        'Contact Us',              // Page title
        'Contact Us',              // Menu title
        'manage_options',          // Capability
        'olee-custom-api-contact', // Menu slug
        'olee_custom_api_contact_page' // Function to display the submenu page content
    );   
}
add_action('admin_menu', 'olee_custom_api_admin_menu');

// Function to display the main menu page content
function olee_custom_api_main_page() {
    ?>
    <div class="wrap">
        <h1>Olee Custom Api</h1>
        <p>Welcome to the Olee Custom Api main page.</p>
    </div>
    <?php
}




?>