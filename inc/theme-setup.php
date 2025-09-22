<?php

/**
 * Theme setup goes here
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function notifal_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height' => 100, 'width' => 350, 'flex-height' => true, 'flex-width'  => true]);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption','script','style']);
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support('elementor-experiments');
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'automatic-feed-links' );
    add_post_type_support( 'page', 'excerpt' );


    // Register Menu
    register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'notifal-theme' ) ] );
    register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'notifal-theme' ) ] );



}
add_action('after_setup_theme', 'notifal_theme_setup');

/**
 * Enqueue theme scripts and styles
 */
function notifal_enqueue_scripts() {
    // Enqueue main scripts file
    wp_enqueue_script(
        'notifal-scripts',
        get_template_directory_uri() . '/scripts.js',
        array('jquery'),
        NOTIFAL_THEME_VERSION,
        true
    );

    // Localize script for AJAX and accessibility
    wp_localize_script('notifal-scripts', 'notifal_auth_menu', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('notifal_auth_menu_nonce'),
        'is_mobile' => wp_is_mobile()
    ));
}
add_action('wp_enqueue_scripts', 'notifal_enqueue_scripts');