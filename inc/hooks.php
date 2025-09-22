<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Replace header and footer with Elementor templates
add_action('get_header', function () {
    if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('header')) {
        get_header();
    }
});

add_action('get_footer', function () {
    if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('footer')) {
        get_footer();
    }
});

/**
 * Login redirect functionality
 * Redirect to login page if user tries to access default WordPress login
 */
add_action('init', function() {
    $login_url = home_url('/login/');

    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && !isset($_GET['action'])) {
        if (!is_user_logged_in()) {
            wp_safe_redirect($login_url);
            exit;
        }
    }
});