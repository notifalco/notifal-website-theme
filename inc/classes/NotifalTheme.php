<?php
namespace NotifalTheme;

/**
 * Main class of theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NotifalTheme {


    /**
     * assets handler
     *
     * @since 1.0.0
     * @var assets
     */
    public assets $assets;

    /**
     * plugin instance
     *
     * @var NotifalTheme|null
     */
    public static ?NotifalTheme $instance = null;




    /**
     * Theme constructor
     * initialize the plugin
     */
    public function __construct() {
        $this->register_autoloader();
        add_action( 'init', array( $this, 'init_plugin' ), 99 );
    }

    /**
     * Register Autoloader
     *
     * Autoload all classes and traits
     *
     * @since 1.0.0
     * @return void
     * @access private
     */
    private function register_autoloader() {
        require_once THEME_INC_PATH . 'autoloader.php';
        autoloader::run();
    }


    /**
     * init component
     *
     * @return void
     */
    public function init_plugin() {
        $this->init_components();
    }

    /**
     * init components
     *
     * init the plugin classes and components
     *
     * @since 1.0.0
     * @return void
     * @access private
     */
    private function init_components() {
        $this->assets = new Assets();
    }


    /**
     * Clone
     * Disable class cloning and throw an error on object clone.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'miropet-theme' ), MIROPET_THEME_VERSION );
    }

    /**
     * Wakeup
     * Disable unserializing of the class.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'miropet-theme' ), MIROPET_THEME_VERSION );
    }


    /**
     * get instance of class
     *
     * @return NotifalTheme instance of the class.
     */
    public static function get_instance(): ?NotifalTheme
    {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
NotifalTheme::get_instance();
