<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Functions.php
 * Initializes theme setup, loads necessary files, and autoloads classes.
 */

// Define path constants
define( 'THEME_INC_PATH', get_template_directory() . '/inc/' );
define( 'THEME_CLASS_PATH', THEME_INC_PATH . 'classes/' );
define( 'THEME_ASSETS_PATH', get_template_directory_uri() . '/assets/' );
define( 'NOTIFAL_THEME_VERSION', '1.0.0' );


require THEME_INC_PATH . 'theme-setup.php';

require THEME_INC_PATH . 'hooks.php';

require THEME_INC_PATH . 'functions.php';

require THEME_CLASS_PATH . 'NotifalTheme.php';

require THEME_CLASS_PATH . 'Login.php';
require THEME_CLASS_PATH . 'AuthMenu.php';

// Initialize login functionality
if ( class_exists( '\NotifalTheme\Login' ) ) {
    new \NotifalTheme\Login();
}

// Initialize authentication menu functionality
if ( class_exists( '\NotifalTheme\AuthMenu' ) ) {
    new \NotifalTheme\AuthMenu();
}

// Initialize global avatar replacement system
add_action( 'init', 'notifal_init_global_avatar_system' );

/**
 * Helper function to set Gmail profile picture for current user
 * Usage: notifal_set_gmail_avatar('https://example.com/avatar.jpg');
 *
 * @param string $avatar_url The avatar URL to set
 * @return bool True on success, false on failure
 */
function notifal_set_gmail_avatar( $avatar_url ) {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();
    return \NotifalTheme\AuthMenu::update_gmail_avatar( $user_id, $avatar_url );
}

/**
 * Helper function to get Gmail profile picture for current user
 *
 * @return string|false Avatar URL or false if not found
 */
function notifal_get_gmail_avatar() {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();
    return get_user_meta( $user_id, 'gmail_profile_picture', true );
}

/**
 * Debug function to check avatar sources for current user
 * Useful for troubleshooting avatar display issues
 *
 * @return array Debug information about available avatars
 */
function notifal_debug_user_avatars() {
    if ( ! is_user_logged_in() ) {
        return array( 'error' => 'User not logged in' );
    }

    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    $debug = array(
        'user_id' => $user_id,
        'user_email' => $user->user_email,
        'is_gmail' => strpos( $user->user_email, '@gmail.com' ) !== false,
        'avatar_sources' => array(),
        'recommendations' => array(),
    );

    // Check WooCommerce avatar
    if ( function_exists( 'get_avatar_url' ) ) {
        $wc_avatar = get_avatar_url( $user_id );
        $debug['avatar_sources']['woocommerce'] = $wc_avatar ?: 'not set';
    }

    // Check Google OAuth avatar
    $google_avatar = get_user_meta( $user_id, 'google_picture_url', true );
    $debug['avatar_sources']['google_oauth'] = $google_avatar ?: 'not set';

    // Check stored Gmail avatar
    $gmail_avatar = get_user_meta( $user_id, 'gmail_profile_picture', true );
    $debug['avatar_sources']['gmail_stored'] = $gmail_avatar ?: 'not set';

    // Check WordPress/Gravatar avatar
    $wp_avatar = get_avatar_url( $user_id, array( 'size' => 32 ) );
    $debug['avatar_sources']['wordpress_gravatar'] = $wp_avatar ?: 'not set';
    
    // Check if Gravatar has an actual avatar (not default)
    $gravatar_check = get_avatar_url( $user_id, array( 'size' => 32, 'default' => '404' ) );
    $gravatar_response = wp_remote_head( $gravatar_check, array( 'timeout' => 3 ) );
    $has_gravatar = ! is_wp_error( $gravatar_response ) && wp_remote_retrieve_response_code( $gravatar_response ) === 200;
    $debug['has_gravatar'] = $has_gravatar;

    // Add recommendations
    if ( ! $google_avatar && ! $gmail_avatar && ! $has_gravatar ) {
        if ( $debug['is_gmail'] ) {
            $debug['recommendations'][] = 'Create a Gravatar account at https://gravatar.com using your Gmail address';
            $debug['recommendations'][] = 'Or manually set an avatar using: notifal_set_gmail_avatar("your-image-url")';
        } else {
            $debug['recommendations'][] = 'Create a Gravatar account at https://gravatar.com using your email address';
        }
    }

    if ( $debug['is_gmail'] && ! $google_avatar ) {
        $debug['recommendations'][] = 'For best results, register/login using Google OAuth to get your Google profile picture automatically';
    }

    return $debug;
}

/**
 * Helper function to display avatar setup instructions for current user
 * Outputs HTML with recommendations for setting up an avatar
 */
function notifal_show_avatar_setup_guide() {
    if ( ! is_user_logged_in() ) {
        echo '<p>Please log in to see avatar setup instructions.</p>';
        return;
    }

    $debug = notifal_debug_user_avatars();
    $user = wp_get_current_user();
    
    echo '<div class="notifal-avatar-setup-guide">';
    echo '<h3>Avatar Setup for ' . esc_html( $user->display_name ) . '</h3>';
    
    // Show current status
    if ( $debug['has_gravatar'] || $debug['avatar_sources']['google_oauth'] !== 'not set' || $debug['avatar_sources']['gmail_stored'] !== 'not set' ) {
        echo '<p style="color: green;">✅ You have an avatar configured!</p>';
    } else {
        echo '<p style="color: orange;">⚠️ No avatar found. Here\'s how to set one up:</p>';
    }
    
    // Show recommendations
    if ( ! empty( $debug['recommendations'] ) ) {
        echo '<ul>';
        foreach ( $debug['recommendations'] as $recommendation ) {
            echo '<li>' . esc_html( $recommendation ) . '</li>';
        }
        echo '</ul>';
    }
    
    // Show technical details
    echo '<details><summary>Technical Details</summary>';
    echo '<pre>' . esc_html( print_r( $debug, true ) ) . '</pre>';
    echo '</details>';
    
    echo '</div>';
}

/**
 * Initialize global avatar replacement system
 * This hooks into WordPress core avatar functions to replace avatars site-wide
 */
function notifal_init_global_avatar_system() {
    // Hook into avatar URL generation (affects all avatar displays site-wide)
    add_filter( 'get_avatar_url', 'notifal_replace_avatar_url_globally', 10, 3 );
    
    // Hook into avatar data (for get_avatar_data function)
    add_filter( 'get_avatar_data', 'notifal_replace_avatar_data_globally', 10, 2 );
    
    // Hook into pre_get_avatar for early replacement (optional, for performance)
    add_filter( 'pre_get_avatar', 'notifal_replace_avatar_html_globally', 10, 3 );
}

/**
 * Globally replace avatar URLs for users with Google profile pictures
 * This affects ALL avatar displays: comments, user lists, admin, etc.
 *
 * @param string $url         The avatar URL
 * @param mixed  $id_or_email User ID, email, or user object
 * @param array  $args        Avatar arguments
 * @return string Modified avatar URL
 */
function notifal_replace_avatar_url_globally( $url, $id_or_email, $args ) {
    $user_id = notifal_get_user_id_from_avatar_param( $id_or_email );
    
    if ( ! $user_id ) {
        return $url; // Not a valid user, return original URL
    }
    
    // Check if user has Google profile picture from OAuth login
    $google_picture = get_user_meta( $user_id, 'google_picture_url', true );
    if ( ! empty( $google_picture ) ) {
        return $google_picture;
    }
    
    // Check if user has manually stored Gmail avatar
    $gmail_picture = get_user_meta( $user_id, 'gmail_profile_picture', true );
    if ( ! empty( $gmail_picture ) ) {
        return $gmail_picture;
    }
    
    // Return original URL if no Google/Gmail avatar found
    return $url;
}

/**
 * Globally replace avatar data for users with Google profile pictures
 *
 * @param array $args        Avatar data
 * @param mixed $id_or_email User ID, email, or user object
 * @return array Modified avatar data
 */
function notifal_replace_avatar_data_globally( $args, $id_or_email ) {
    $user_id = notifal_get_user_id_from_avatar_param( $id_or_email );
    
    if ( ! $user_id ) {
        return $args; // Not a valid user, return original args
    }
    
    // Check if user has Google profile picture from OAuth login
    $google_picture = get_user_meta( $user_id, 'google_picture_url', true );
    if ( ! empty( $google_picture ) ) {
        $args['url'] = $google_picture;
        $args['found_avatar'] = true; // Mark as found to avoid default
        return $args;
    }
    
    // Check if user has manually stored Gmail avatar
    $gmail_picture = get_user_meta( $user_id, 'gmail_profile_picture', true );
    if ( ! empty( $gmail_picture ) ) {
        $args['url'] = $gmail_picture;
        $args['found_avatar'] = true; // Mark as found to avoid default
        return $args;
    }
    
    // Return original args if no Google/Gmail avatar found
    return $args;
}

/**
 * Globally replace avatar HTML for users with Google profile pictures
 * This is called before the avatar is generated, allowing complete control
 *
 * @param string|null $avatar      The avatar HTML (null means generate normally)
 * @param mixed       $id_or_email User ID, email, or user object
 * @param array       $args        Avatar arguments
 * @return string|null Avatar HTML or null to continue normal processing
 */
function notifal_replace_avatar_html_globally( $avatar, $id_or_email, $args ) {
    $user_id = notifal_get_user_id_from_avatar_param( $id_or_email );
    
    if ( ! $user_id ) {
        return $avatar; // Not a valid user, continue normal processing
    }
    
    // Check if user has Google profile picture from OAuth login
    $google_picture = get_user_meta( $user_id, 'google_picture_url', true );
    $gmail_picture = get_user_meta( $user_id, 'gmail_profile_picture', true );
    
    $custom_avatar_url = $google_picture ?: $gmail_picture;
    
    if ( empty( $custom_avatar_url ) ) {
        return $avatar; // No custom avatar, continue normal processing
    }
    
    // Generate custom avatar HTML
    $size = isset( $args['size'] ) ? (int) $args['size'] : 96;
    $alt = isset( $args['alt'] ) ? esc_attr( $args['alt'] ) : '';
    $class = isset( $args['class'] ) ? esc_attr( $args['class'] ) : '';
    
    // Build class list
    $class_list = array( 'avatar', 'avatar-' . $size, 'photo' );
    if ( ! empty( $class ) ) {
        if ( is_array( $class ) ) {
            $class_list = array_merge( $class_list, $class );
        } else {
            $class_list[] = $class;
        }
    }
    
    // Add custom class for Google avatars
    $class_list[] = 'notifal-google-avatar';
    
    $avatar_html = sprintf(
        '<img alt="%s" src="%s" srcset="%s 2x" class="%s" height="%d" width="%d" />',
        $alt,
        esc_url( $custom_avatar_url ),
        esc_url( $custom_avatar_url ),
        esc_attr( implode( ' ', $class_list ) ),
        $size,
        $size
    );
    
    return $avatar_html;
}

/**
 * Helper function to extract user ID from avatar parameter
 *
 * @param mixed $id_or_email User ID, email, WP_User, WP_Post, or WP_Comment object
 * @return int|false User ID or false if not found
 */
function notifal_get_user_id_from_avatar_param( $id_or_email ) {
    $user_id = false;
    
    if ( is_numeric( $id_or_email ) ) {
        // Direct user ID
        $user_id = (int) $id_or_email;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        // Email address
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) {
            $user_id = $user->ID;
        }
    } elseif ( $id_or_email instanceof WP_User ) {
        // WP_User object
        $user_id = $id_or_email->ID;
    } elseif ( $id_or_email instanceof WP_Post ) {
        // WP_Post object (author)
        $user_id = (int) $id_or_email->post_author;
    } elseif ( $id_or_email instanceof WP_Comment ) {
        // WP_Comment object
        if ( $id_or_email->user_id ) {
            $user_id = (int) $id_or_email->user_id;
        } elseif ( ! empty( $id_or_email->comment_author_email ) ) {
            $user = get_user_by( 'email', $id_or_email->comment_author_email );
            if ( $user ) {
                $user_id = $user->ID;
            }
        }
    }
    
    // Verify user exists
    if ( $user_id && get_user_by( 'ID', $user_id ) ) {
        return $user_id;
    }
    
    return false;
}

/**
 * ============================================================================
 * WooCommerce My Account - Custom Functions
 * ============================================================================
 */

/**
 * Verify nonce for security
 *
 * @param string $action The nonce action name
 * @return bool True if nonce is valid, false otherwise
 */
function notifal_verify_nonce( $action ) {
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
        return false;
    }
    return true;
}

/**
 * Safely output text with escaping
 *
 * @param string $value The value to escape and output
 * @return string The escaped value
 */
function notifal_safe_output( $value ) {
    return esc_html( $value );
}

/**
 * Safely output URLs with escaping
 *
 * @param string $url The URL to escape
 * @return string The escaped URL
 */
function notifal_safe_url( $url ) {
    return esc_url( $url );
}

/**
 * Check if user has an active license
 *
 * @param int|null $user_id The user ID to check (defaults to current user)
 * @return bool True if user has active license, false otherwise
 */
function notifal_has_active_license( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( ! $user_id ) {
        return false;
    }

    // Check for active license via License Manager plugin
    if ( function_exists( 'nlm_get_user_active_license_count' ) ) {
        return nlm_get_user_active_license_count( $user_id ) > 0;
    }

    // Fallback: check user meta for license status (for backward compatibility)
    $license_status = get_user_meta( $user_id, 'notifal_license_status', true );

    return $license_status === 'active';
}

/**
 * Get download versions for Notifal products
 *
 * @return array Array of available downloads
 */
function notifal_get_download_versions() {
    $downloads = array();
    $cache_key = 'notifal_download_versions';
    $cached_versions = get_transient( $cache_key );

    if ( false !== $cached_versions ) {
        return $cached_versions;
    }

    // Get WordPress.org API data for Lite version
    $lite_versions = notifal_fetch_wp_org_versions();
    
    // Get Pro versions if user has active license
    $pro_versions = array();
    if ( notifal_has_active_license() ) {
        $pro_versions = notifal_fetch_pro_versions();
    }

    $downloads = array(
        'lite' => array(
            'name' => 'Notifal Lite',
            'description' => 'Free version with essential features for WordPress notifications.',
            'versions' => $lite_versions,
            'features' => array(
                'Basic notification system',
                'Email templates', 
                'User management',
                'Community support'
            )
        )
    );

    if ( ! empty( $pro_versions ) ) {
        $downloads['pro'] = array(
            'name' => 'Notifal Pro',
            'description' => 'Premium version with advanced features and priority support.',
            'versions' => $pro_versions,
            'features' => array(
                'Advanced notification rules',
                'Custom email templates',
                'Analytics & reporting',
                'Priority support',
                'Elementor integration',
                'FOMO marketing tools'
            )
        );
    }

    // Cache for 1 hour
    set_transient( $cache_key, $downloads, HOUR_IN_SECONDS );

    return $downloads;
}

/**
 * Fetch all available versions from WordPress.org
 *
 * @return array Array of version data
 */
function notifal_fetch_wp_org_versions() {
    $api_url = 'https://api.wordpress.org/plugins/info/1.0/notifal.json';
    $response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );
    
    $versions = array();
    
    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        // Fallback to default version if API fails
        return array(
            array(
                'version' => '1.3.2',
                'download_url' => 'https://downloads.wordpress.org/plugin/notifal.1.3.2.zip',
                'date' => '2025-07-07',
                'is_latest' => true
            )
        );
    }
    
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( ! $data || ! isset( $data['version'] ) ) {
        return $versions;
    }

    // Get latest version info
    $latest_version = array(
        'version' => $data['version'],
        'download_url' => isset( $data['download_link'] ) ? $data['download_link'] : "https://downloads.wordpress.org/plugin/notifal.{$data['version']}.zip",
        'date' => isset( $data['last_updated'] ) ? date( 'Y-m-d', strtotime( $data['last_updated'] ) ) : date( 'Y-m-d' ),
        'is_latest' => true,
        'tested_up_to' => isset( $data['tested'] ) ? $data['tested'] : '',
        'requires_wp' => isset( $data['requires'] ) ? $data['requires'] : ''
    );
    
    $versions[] = $latest_version;
    
    // Try to get historical versions from the versions array if available
    if ( isset( $data['versions'] ) && is_array( $data['versions'] ) ) {
        $version_numbers = array_keys( $data['versions'] );
        
        // Sort versions in descending order (newest first)
        usort( $version_numbers, 'version_compare' );
        $version_numbers = array_reverse( $version_numbers );
        
        // Limit to last 5 versions to avoid too many options
        $version_numbers = array_slice( $version_numbers, 0, 5 );
        
        foreach ( $version_numbers as $version_num ) {
            if ( $version_num === $data['version'] ) {
                continue; // Skip latest as we already added it
            }
            
            $versions[] = array(
                'version' => $version_num,
                'download_url' => "https://downloads.wordpress.org/plugin/notifal.{$version_num}.zip",
                'date' => '', // Historical dates not available in API
                'is_latest' => false,
                'tested_up_to' => '',
                'requires_wp' => ''
            );
        }
    }
    
    return $versions;
}

/**
 * Fetch Pro versions from our server
 *
 * @return array Array of Pro version data
 */
function notifal_fetch_pro_versions() {
    // Check if Pro files directory exists and create if needed
    notifal_ensure_pro_files_directory();
    
    // This would typically fetch from your server's API
    // For now, returning static data - you should replace this with actual API call to your server
    
    $pro_versions = array(
        array(
            'version' => '2.0.0',
            'download_url' => home_url( '/my-account/download/?product=pro&version=2.0.0' ),
            'date' => '2025-01-15',
            'is_latest' => true,
            'changelog' => 'Advanced Elementor integration, Enhanced analytics dashboard'
        ),
        array(
            'version' => '1.9.5',
            'download_url' => home_url( '/my-account/download/?product=pro&version=1.9.5' ),
            'date' => '2024-12-20',
            'is_latest' => false,
            'changelog' => 'Bug fixes and performance improvements'
        )
    );
    
    // TODO: Replace with actual API call to your server
    // Example implementation:
    /*
    $api_url = 'https://notifal.com/api/pro-versions';
    $response = wp_remote_get( $api_url, array( 
        'timeout' => 10,
        'headers' => array(
            'Authorization' => 'Bearer ' . get_option( 'notifal_api_key' )
        )
    ) );
    
    if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $data && isset( $data['versions'] ) ) {
            return $data['versions'];
        }
    }
    */
    
    return $pro_versions;
}

/**
 * Ensure Pro files directory exists
 *
 * @return bool True if directory exists or was created successfully
 */
function notifal_ensure_pro_files_directory() {
    $uploads_dir = wp_upload_dir();
    $pro_files_dir = $uploads_dir['basedir'] . '/notifal-pro-files/';
    
    if ( ! file_exists( $pro_files_dir ) ) {
        wp_mkdir_p( $pro_files_dir );
        
        // Create .htaccess for security
        $htaccess_content = "Order Deny,Allow\nDeny from all\n";
        file_put_contents( $pro_files_dir . '.htaccess', $htaccess_content );
        
        // Create index.php for security
        file_put_contents( $pro_files_dir . 'index.php', "<?php // Silence is golden" );
    }
    
    return file_exists( $pro_files_dir );
}

/**
 * Handle download request with security checks
 *
 * @return void
 */
function notifal_handle_download_request() {
    // Only handle requests to the download endpoint
    if ( ! isset( $_GET['product'] ) || ! isset( $_GET['version'] ) ) {
        return;
    }

    // Verify user is logged in
    if ( ! is_user_logged_in() ) {
        wp_die( esc_html__( 'You must be logged in to download files.', 'notifal' ), 'Unauthorized', array( 'response' => 403 ) );
    }

    $product = sanitize_text_field( $_GET['product'] );
    $version = sanitize_text_field( $_GET['version'] );

    // Verify nonce for security
    if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], "download_{$product}_{$version}" ) ) {
        wp_die( esc_html__( 'Security check failed.', 'notifal' ), 'Security Error', array( 'response' => 403 ) );
    }

    $downloads = notifal_get_download_versions();

    // Check if product exists and user has access
    if ( $product === 'pro' && ! notifal_has_active_license() ) {
        wp_die( esc_html__( 'You need an active Notifal Pro license to download this version.', 'notifal' ), 'License Required', array( 'response' => 403 ) );
    }

    if ( ! isset( $downloads[ $product ] ) ) {
        wp_die( esc_html__( 'Invalid download request.', 'notifal' ), 'Invalid Request', array( 'response' => 404 ) );
    }

    // Find the specific version
    $version_data = null;
    foreach ( $downloads[ $product ]['versions'] as $ver ) {
        if ( $ver['version'] === $version ) {
            $version_data = $ver;
            break;
        }
    }

    if ( ! $version_data ) {
        wp_die( esc_html__( 'Version not found.', 'notifal' ), 'Version Not Found', array( 'response' => 404 ) );
    }

    // For WordPress.org downloads, redirect directly
    if ( strpos( $version_data['download_url'], 'downloads.wordpress.org' ) !== false ) {
        wp_redirect( $version_data['download_url'] );
        exit;
    }

    // For Pro versions, serve from our server
    if ( $product === 'pro' ) {
        notifal_serve_pro_file( $version );
        exit;
    }
}

/**
 * Serve Pro version file with security checks
 *
 * @param string $version Version to download
 * @return void
 */
function notifal_serve_pro_file( $version ) {
    // Define the file path (you should customize this path)
    $uploads_dir = wp_upload_dir();
    $pro_files_dir = $uploads_dir['basedir'] . '/notifal-pro-files/';
    $file_path = $pro_files_dir . "notifal-pro-{$version}.zip";

    // Check if file exists
    if ( ! file_exists( $file_path ) ) {
        wp_die( esc_html__( 'File not found on server.', 'notifal' ), 'File Not Found', array( 'response' => 404 ) );
    }

    // Log the download attempt
    notifal_log_download( get_current_user_id(), 'pro', $version );

    // Set headers for file download
    $file_size = filesize( $file_path );
    $file_name = "notifal-pro-{$version}.zip";

    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
    header( 'Content-Length: ' . $file_size );
    header( 'Cache-Control: no-cache, must-revalidate' );
    header( 'Expires: 0' );

    // Clean any previous output
    if ( ob_get_level() ) {
        ob_end_clean();
    }

    // Serve the file
    readfile( $file_path );
    exit;
}

/**
 * Log download activity
 *
 * @param int $user_id User ID
 * @param string $product Product type
 * @param string $version Version downloaded
 * @return void
 */
function notifal_log_download( $user_id, $product, $version ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'notifal_downloads';

    // Create table if it doesn't exist
    $wpdb->query( "
        CREATE TABLE IF NOT EXISTS {$table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            product varchar(20) NOT NULL,
            version varchar(20) NOT NULL,
            download_date datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY download_date (download_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    " );

    // Insert download record
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'product' => $product,
            'version' => $version,
            'ip_address' => notifal_get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );
}

/**
 * Get client IP address
 *
 * @return string The client IP address
 */
function notifal_get_client_ip() {
    $ip_headers = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );

    foreach ( $ip_headers as $header ) {
        if ( ! empty( $_SERVER[ $header ] ) ) {
            $ip = $_SERVER[ $header ];
            // Handle comma-separated IPs (like X-Forwarded-For)
            if ( strpos( $ip, ',' ) !== false ) {
                $ip = trim( explode( ',', $ip )[0] );
            }
            // Validate IP
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }

    return '127.0.0.1'; // Default fallback
}

/**
 * Sanitize and validate form data
 *
 * @param array $data The form data to sanitize
 * @return array The sanitized data
 */
function notifal_sanitize_form_data( $data ) {
    $sanitized = array();

    foreach ( $data as $key => $value ) {
        if ( is_array( $value ) ) {
            $sanitized[ $key ] = notifal_sanitize_form_data( $value );
        } else {
            // Sanitize based on field type
            if ( strpos( $key, 'email' ) !== false ) {
                $sanitized[ $key ] = sanitize_email( $value );
            } elseif ( strpos( $key, 'url' ) !== false ) {
                $sanitized[ $key ] = esc_url_raw( $value );
            } else {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }
    }

    return $sanitized;
}

/**
 * Validate account form data
 *
 * @param array $data The form data to validate
 * @return array Array of validation errors
 */
function notifal_validate_account_form( $data ) {
    $errors = array();

    // Validate first name
    if ( empty( $data['account_first_name'] ) ) {
        $errors['first_name'] = __( 'First name is required.', 'notifal' );
    }

    // Validate last name
    if ( empty( $data['account_last_name'] ) ) {
        $errors['last_name'] = __( 'Last name is required.', 'notifal' );
    }

    // Validate display name
    if ( empty( $data['account_display_name'] ) ) {
        $errors['display_name'] = __( 'Display name is required.', 'notifal' );
    }

    // Validate email
    if ( empty( $data['account_email'] ) ) {
        $errors['email'] = __( 'Email address is required.', 'notifal' );
    } elseif ( ! is_email( $data['account_email'] ) ) {
        $errors['email'] = __( 'Please enter a valid email address.', 'notifal' );
    }

    // Validate password confirmation
    if ( ! empty( $data['password_1'] ) && $data['password_1'] !== $data['password_2'] ) {
        $errors['password'] = __( 'Passwords do not match.', 'notifal' );
    }

    return $errors;
}

/**
 * Get WooCommerce order status color class
 *
 * @param string $status The order status
 * @return string The CSS class for the status
 */
function notifal_get_order_status_class( $status ) {
    $status_classes = array(
        'completed' => 'completed',
        'processing' => 'processing',
        'on-hold' => 'on-hold',
        'pending' => 'pending',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'failed' => 'failed'
    );

    return isset( $status_classes[ $status ] ) ? $status_classes[ $status ] : 'pending';
}

/**
 * Format order status for display
 *
 * @param string $status The order status
 * @return string The formatted status
 */
function notifal_format_order_status( $status ) {
    $status_labels = array(
        'completed' => __( 'Completed', 'notifal' ),
        'processing' => __( 'Processing', 'notifal' ),
        'on-hold' => __( 'On Hold', 'notifal' ),
        'pending' => __( 'Pending Payment', 'notifal' ),
        'cancelled' => __( 'Cancelled', 'notifal' ),
        'refunded' => __( 'Refunded', 'notifal' ),
        'failed' => __( 'Failed', 'notifal' )
    );

    return isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );
}

/**
 * Custom navigation items filter
 *
 * @param array $nav_items Default navigation items
 * @return array Modified navigation items
 */
function notifal_custom_navigation_items( $nav_items ) {
    // Allow plugins/themes to modify navigation items
    return apply_filters( 'notifal_navigation_items', $nav_items );
}
add_filter( 'notifal_account_navigation_items', 'notifal_custom_navigation_items' );

/**
 * ============================================================================
 * WordPress Hooks, Filters, and Asset Enqueuing
 * ============================================================================
 */

/**
 * Enqueue account-specific styles and scripts
 */
function notifal_enqueue_account_assets() {
    // Only load on account pages
    if ( ! is_account_page() && ! is_wc_endpoint_url() ) {
        return;
    }

    // Font Awesome removed - using inline SVG icons instead

    // Enqueue account styles
    wp_enqueue_style(
        'notifal-account',
        THEME_ASSETS_PATH . 'css/account.css',
        array(),
        NOTIFAL_THEME_VERSION
    );

    // Enqueue account scripts
    wp_enqueue_script(
        'notifal-account',
        THEME_ASSETS_PATH . 'js/account.js',
        array( 'jquery' ),
        NOTIFAL_THEME_VERSION,
        true
    );

    // Localize script for AJAX
    wp_localize_script( 'notifal-account', 'notifalAccountAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'notifal_account_nonce' ),
        'strings' => array(
            'loading' => __( 'Loading...', 'notifal' ),
            'error'   => __( 'An error occurred. Please try again.', 'notifal' ),
            'success' => __( 'Success!', 'notifal' ),
        ),
    ) );

    // Localize user data for JavaScript
    wp_localize_script( 'notifal-account', 'notifalUserData', array(
        'hasLicense' => notifal_has_active_license(),
        'userId'     => get_current_user_id(),
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'notifal_enqueue_account_assets' );

/**
 * Handle AJAX download tracking
 */
function notifal_ajax_track_download() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'notifal_account_nonce' ) ) {
        wp_die( 'Security check failed' );
    }

    // Verify user is logged in
    if ( ! is_user_logged_in() ) {
        wp_die( 'User not logged in' );
    }

    $product = sanitize_text_field( $_POST['product'] );
    $user_id = get_current_user_id();

    // Log the download
    $log_entry = array(
        'user_id'    => $user_id,
        'product'    => $product,
        'timestamp'  => current_time( 'timestamp' ),
        'ip_address' => notifal_get_client_ip(),
        'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
    );

    // Store in user meta or custom table
    $download_logs = get_user_meta( $user_id, 'notifal_download_logs', true );
    if ( ! is_array( $download_logs ) ) {
        $download_logs = array();
    }

    $download_logs[] = $log_entry;

    // Keep only last 50 logs to prevent meta bloat
    if ( count( $download_logs ) > 50 ) {
        $download_logs = array_slice( $download_logs, -50 );
    }

    update_user_meta( $user_id, 'notifal_download_logs', $download_logs );

    // Allow plugins to hook into download tracking
    do_action( 'notifal_download_tracked', $log_entry );

    wp_send_json_success( array(
        'message' => __( 'Download tracked successfully', 'notifal' ),
    ) );
}
add_action( 'wp_ajax_notifal_track_download', 'notifal_ajax_track_download' );

/**
 * Handle AJAX request for download data
 */
function notifal_ajax_get_download_data() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'notifal_account_nonce' ) ) {
        wp_send_json_error( 'Security check failed' );
    }

    // Verify user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'User not logged in' );
    }

    $downloads = notifal_get_download_versions();

    wp_send_json_success( array(
        'downloads' => $downloads,
        'has_license' => notifal_has_active_license(),
    ) );
}
add_action( 'wp_ajax_notifal_get_download_data', 'notifal_ajax_get_download_data' );

/**
 * Add custom body classes for account pages
 */
function notifal_account_body_classes( $classes ) {
    if ( is_account_page() || is_wc_endpoint_url() ) {
        $classes[] = 'notifal-account-page';
        $classes[] = 'woocommerce-account-page';

        // Add specific endpoint classes
        if ( is_wc_endpoint_url( 'dashboard' ) ) {
            $classes[] = 'account-dashboard';
        } elseif ( is_wc_endpoint_url( 'orders' ) ) {
            $classes[] = 'account-orders';
        } elseif ( is_wc_endpoint_url( 'downloads' ) ) {
            $classes[] = 'account-downloads';
        } elseif ( is_wc_endpoint_url( 'edit-account' ) ) {
            $classes[] = 'account-settings';
        }
    }

    return $classes;
}
add_filter( 'body_class', 'notifal_account_body_classes' );

/**
 * Modify WooCommerce account menu items
 */
function notifal_custom_account_menu_items( $items ) {
    // Rename 'edit-account' to 'settings'
    if ( isset( $items['edit-account'] ) ) {
        $items['edit-account'] = __( 'Settings', 'notifal' );
    }

    // Remove unwanted items or add new ones
    // $items['custom-endpoint'] = __( 'Custom Page', 'notifal' );

    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'notifal_custom_account_menu_items' );

/**
 * Add custom account endpoints
 */
function notifal_add_custom_endpoints() {
    // Add custom endpoint for support or other features
    // add_rewrite_endpoint( 'support', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'notifal_add_custom_endpoints' );

/**
 * Handle custom endpoint content
 */
function notifal_custom_endpoint_content() {
    // Add content for custom endpoints
    // if ( is_wc_endpoint_url( 'support' ) ) {
    //     do_action( 'notifal_support_page_content' );
    // }
}
// add_action( 'woocommerce_account_content', 'notifal_custom_endpoint_content' );

/**
 * Add custom dashboard widgets or content
 */
function notifal_before_dashboard_content() {
    // Hook for adding content before dashboard
    do_action( 'notifal_before_dashboard' );
}
add_action( 'woocommerce_before_account_content', 'notifal_before_dashboard_content' );

function notifal_after_dashboard_content() {
    // Hook for adding content after dashboard
    do_action( 'notifal_after_dashboard' );
}
add_action( 'woocommerce_after_account_content', 'notifal_after_dashboard_content' );

/**
 * Customize WooCommerce account navigation
 * This function replaces the default WooCommerce navigation to prevent duplication
 */
function notifal_account_navigation() {
    // Remove default WooCommerce navigation to prevent duplication
    // The default function runs at priority 10, so we remove it and run at priority 5
    remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', 10 );

    // Use our custom navigation template
    wc_get_template( 'myaccount/navigation.php' );
}
// Run at priority 5 to ensure we remove the default before it runs
add_action( 'woocommerce_account_navigation', 'notifal_account_navigation', 5 );

/**
 * Override WooCommerce account content ONLY for theme's custom templates
 * Let WooCommerce handle all other endpoints normally
 */
function notifal_account_content() {
    // Get current endpoint
    $endpoint = WC()->query->get_current_endpoint();

    // Only override specific endpoints that have custom theme templates
    $theme_endpoints = array( 'dashboard', 'orders', 'downloads', 'edit-account' );
    
    if ( in_array( $endpoint, $theme_endpoints ) ) {
        // Remove default WooCommerce content ONLY for these specific endpoints
        remove_action( 'woocommerce_account_content', 'woocommerce_account_content', 10 );

        // Load appropriate template based on endpoint
        switch ( $endpoint ) {
            case 'dashboard':
                wc_get_template( 'myaccount/dashboard.php' );
                break;
            case 'orders':
                wc_get_template( 'myaccount/orders.php' );
                break;
            case 'downloads':
                wc_get_template( 'myaccount/downloads.php' );
                break;
            case 'edit-account':
                wc_get_template( 'myaccount/form-edit-account.php' );
                break;
        }
    }
    // For all other endpoints (like license-manager), let WooCommerce handle them normally
}
// Run at priority 5 to ensure we can remove the default before it runs
add_action( 'woocommerce_account_content', 'notifal_account_content', 5 );

/**
 * Add security headers for downloads
 */
function notifal_download_security_headers() {
    if ( is_wc_endpoint_url( 'downloads' ) ) {
        // Add security headers for download pages
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
    }
}
add_action( 'wp_head', 'notifal_download_security_headers' );

/**
 * Handle download redirects securely
 */
function notifal_handle_download_redirect() {
    if ( isset( $_GET['notifal_download'] ) && isset( $_GET['product'] ) ) {
        $product = sanitize_text_field( $_GET['product'] );

        // Verify user can download this product
        if ( is_user_logged_in() ) {
            notifal_handle_download( $product );
        } else {
            wp_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }
    }
}
add_action( 'wp', 'notifal_handle_download_redirect' );

/**
 * Add custom meta boxes or admin functionality for license management
 */
function notifal_add_license_management() {
    // This would integrate with the License Manager plugin
    // Add user profile fields, admin pages, etc.
    if ( current_user_can( 'manage_options' ) ) {
        // Add admin functionality for license management
        add_action( 'show_user_profile', 'notifal_add_user_license_fields' );
        add_action( 'edit_user_profile', 'notifal_add_user_license_fields' );
        add_action( 'personal_options_update', 'notifal_save_user_license_fields' );
        add_action( 'edit_user_profile_update', 'notifal_save_user_license_fields' );
    }
}
add_action( 'admin_init', 'notifal_add_license_management' );

/**
 * Add license fields to user profile
 */
function notifal_add_user_license_fields( $user ) {
    ?>
    <h3><?php esc_html_e( 'Notifal License Management', 'notifal' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="notifal_license_status"><?php esc_html_e( 'License Status', 'notifal' ); ?></label></th>
            <td>
                <select name="notifal_license_status" id="notifal_license_status">
                    <option value="" <?php selected( get_user_meta( $user->ID, 'notifal_license_status', true ), '' ); ?>>
                        <?php esc_html_e( 'No License', 'notifal' ); ?>
                    </option>
                    <option value="active" <?php selected( get_user_meta( $user->ID, 'notifal_license_status', true ), 'active' ); ?>>
                        <?php esc_html_e( 'Active', 'notifal' ); ?>
                    </option>
                    <option value="expired" <?php selected( get_user_meta( $user->ID, 'notifal_license_status', true ), 'expired' ); ?>>
                        <?php esc_html_e( 'Expired', 'notifal' ); ?>
                    </option>
                </select>
                <p class="description"><?php esc_html_e( 'Set the user\'s Notifal Pro license status.', 'notifal' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="notifal_license_key"><?php esc_html_e( 'License Key', 'notifal' ); ?></label></th>
            <td>
                <input type="text" name="notifal_license_key" id="notifal_license_key"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, 'notifal_license_key', true ) ); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e( 'The user\'s license key for Notifal Pro.', 'notifal' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save license fields from user profile
 */
function notifal_save_user_license_fields( $user_id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['notifal_license_status'] ) ) {
        update_user_meta( $user_id, 'notifal_license_status', sanitize_text_field( $_POST['notifal_license_status'] ) );
    }

    if ( isset( $_POST['notifal_license_key'] ) ) {
        update_user_meta( $user_id, 'notifal_license_key', sanitize_text_field( $_POST['notifal_license_key'] ) );
    }
}

/**
 * ============================================================================
 * Shared Icon Functions
 * ============================================================================
 */

/**
 * Get SVG icon for menu items (shared between AuthMenu and WooCommerce navigation)
 *
 * @param string $icon_name Icon name
 * @return string SVG markup
 */
function notifal_get_icon_svg( $icon_name ) {
    $icons = array(
        'key' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>',
        'shopping-bag' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-check" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0"/><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>',
        'chat' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left-dots" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M5 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>',
        'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 0 0-.708.708z"/></svg>',
        'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16"><path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z"/></svg>',
        'logout' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-door-closed" viewBox="0 0 16 16"><path d="M3 2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v13h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3zm1 13h8V2H4z"/><path d="M9 9a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/></svg>',
        'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16">
  <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/>
  <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/>
</svg>',
        'licenses' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>'
    );

    return isset( $icons[ $icon_name ] ) ? $icons[ $icon_name ] : '';
}

/**
 * ============================================================================
 * Theme Activation and Setup
 * ============================================================================
 */

/**
 * Set up theme defaults and register support for various WordPress features
 */
function notifal_after_setup_theme() {
    // Add theme support for various features
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list' ) );
    add_theme_support( 'woocommerce' );

    // Add WooCommerce specific theme support
    add_theme_support( 'woocommerce', array(
        'thumbnail_image_width' => 300,
        'single_image_width'    => 600,
        'product_grid'          => array(
            'default_rows'    => 3,
            'min_rows'        => 2,
            'max_rows'        => 8,
            'default_columns' => 4,
            'min_columns'     => 2,
            'max_columns'     => 5,
        ),
    ) );
}

/**
 * Initialize download handlers
 */
function notifal_init_download_handlers() {
    // Handle download requests on init
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }
    
    // Check if this is a download request
    if ( isset( $_GET['notifal_download'] ) ) {
        notifal_handle_download_request();
    }
}
add_action( 'after_setup_theme', 'notifal_after_setup_theme' );
add_action( 'init', 'notifal_init_download_handlers' );

// AJAX handlers for download nonce generation
add_action( 'wp_ajax_notifal_generate_download_nonce', 'notifal_generate_download_nonce_ajax' );
add_action( 'wp_ajax_nopriv_notifal_generate_download_nonce', 'notifal_generate_download_nonce_ajax' );

/**
 * AJAX handler for generating download nonces
 */
function notifal_generate_download_nonce_ajax() {
    // Verify security nonce
    if ( ! wp_verify_nonce( $_POST['security'] ?? '', 'notifal_download_nonce' ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'notifal' ) ) );
    }
    
    // Verify user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => esc_html__( 'You must be logged in to download files.', 'notifal' ) ) );
    }
    
    $product = sanitize_text_field( $_POST['product'] ?? '' );
    $version = sanitize_text_field( $_POST['version'] ?? '' );
    
    // Verify Pro access if needed
    if ( $product === 'pro' && ! notifal_has_active_license() ) {
        wp_send_json_error( array( 'message' => esc_html__( 'You need an active Notifal Pro license to download this version.', 'notifal' ) ) );
    }
    
    // Generate specific nonce for this download
    $download_nonce = wp_create_nonce( "download_{$product}_{$version}" );
    
    wp_send_json_success( array( 'nonce' => $download_nonce ) );
}