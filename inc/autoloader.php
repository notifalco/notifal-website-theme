<?php
namespace NotifalTheme;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Autoload class and traits
 *
 * @version 1.0.0
 */

class autoloader {

    /**
     * Classes Path
     *
     * @var string
     */
    private static string $class_folder = THEME_CLASS_PATH;




    /**
     * run autoload
     *
     * @since 1.0.0
     * @return void
     */
    public static function run() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * autoload files
     *
     * @since 1.0.0
     * @param $className
     * @return void
     */
    private static function autoload( $className ) {
        $className = basename(str_replace('\\', '/', $className));
        $className      = preg_replace( '/^' . __NAMESPACE__ . '\\\/', '', $className );
        $main_file_path = array(
            self::$class_folder => self::get_class_path(),
        );

        foreach ( $main_file_path as $file_type => $paths ) {

            foreach ( $paths as $path ) {


                if ( ! empty( $path ) ) {
                    $file = $file_type . $path . $className . '.php';
                } else {
                    $file = $file_type . $className . '.php';
                }

                $file = str_replace( '\\', '/', $file );

                if ( file_exists( $file ) ) {
                    include_once $file;
                }
            }
        }

    }

    /**
     * get class paths
     *
     * @since 1.0.0
     * @return array
     */
    private static function get_class_path(): array
    {

        return array(
            '',
        );
    }

    /**
     * get traits paths
     *
     * @since 1.0.0
     * @return array
     */
    private static function get_elementor_path(): array
    {
        return array(
            '',
        );
    }
}
