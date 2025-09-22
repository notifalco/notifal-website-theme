<?php
namespace NotifalTheme;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Assets {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [$this,'enqueue_front_assets'],100 );
    }

    public function enqueue_front_assets(){
        // Enqueue styles
        wp_enqueue_style( 'notifal-theme', get_stylesheet_uri() );

        // Enqueue global script
        wp_enqueue_script( 'notifal-theme-global', THEME_ASSETS_PATH . 'js/global.js', ['jquery'], NOTIFAL_THEME_VERSION,true );
    }

}