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
 * Validate phone number based on country code
 *
 * @param string $phone_number The phone number to validate
 * @param string $country_code The country code (e.g., '+1', '+44')
 * @return bool True if valid, false otherwise
 */
function notifal_validate_phone_number( $phone_number, $country_code ) {
    if ( empty( $phone_number ) || empty( $country_code ) ) {
        return false;
    }

    // Remove all non-numeric characters
    $cleaned = preg_replace( '/\D/', '', $phone_number );

    // Validation rules by country
    $rules = array(
        '+1' => array( 'min' => 10, 'max' => 10 ), // US/Canada
        '+7' => array( 'min' => 10, 'max' => 10 ), // Russia
        '+20' => array( 'min' => 10, 'max' => 10 ), // Egypt
        '+27' => array( 'min' => 9, 'max' => 9 ),  // South Africa
        '+30' => array( 'min' => 10, 'max' => 10 ), // Greece
        '+31' => array( 'min' => 9, 'max' => 9 ),   // Netherlands
        '+32' => array( 'min' => 9, 'max' => 9 ),   // Belgium
        '+33' => array( 'min' => 9, 'max' => 9 ),   // France
        '+34' => array( 'min' => 9, 'max' => 9 ),   // Spain
        '+36' => array( 'min' => 9, 'max' => 9 ),   // Hungary
        '+39' => array( 'min' => 10, 'max' => 10 ), // Italy
        '+40' => array( 'min' => 10, 'max' => 10 ), // Romania
        '+41' => array( 'min' => 9, 'max' => 10 ),  // Switzerland
        '+43' => array( 'min' => 10, 'max' => 11 ), // Austria
        '+44' => array( 'min' => 10, 'max' => 11 ), // UK
        '+45' => array( 'min' => 8, 'max' => 8 ),   // Denmark
        '+46' => array( 'min' => 9, 'max' => 9 ),   // Sweden
        '+47' => array( 'min' => 8, 'max' => 8 ),   // Norway
        '+48' => array( 'min' => 9, 'max' => 9 ),   // Poland
        '+49' => array( 'min' => 10, 'max' => 11 ), // Germany
        '+55' => array( 'min' => 11, 'max' => 11 ), // Brazil
        '+61' => array( 'min' => 9, 'max' => 9 ),   // Australia
        '+65' => array( 'min' => 8, 'max' => 8 ),   // Singapore
        '+81' => array( 'min' => 10, 'max' => 11 ), // Japan
        '+82' => array( 'min' => 10, 'max' => 11 ), // South Korea
        '+86' => array( 'min' => 11, 'max' => 11 ), // China
        '+91' => array( 'min' => 10, 'max' => 10 ), // India
        '+971' => array( 'min' => 9, 'max' => 9 )   // UAE
    );

    $rule = isset( $rules[ $country_code ] ) ? $rules[ $country_code ] : null;

    if ( ! $rule ) {
        // Generic validation for countries not in the list
        return strlen( $cleaned ) >= 7 && strlen( $cleaned ) <= 15;
    }

    return strlen( $cleaned ) >= $rule['min'] && strlen( $cleaned ) <= $rule['max'];
}

/**
 * Sanitize phone number input
 *
 * @param string $phone_number The phone number to sanitize
 * @return string Sanitized phone number
 */
function notifal_sanitize_phone_number( $phone_number ) {
    if ( empty( $phone_number ) ) {
        return '';
    }

    // Allow only numbers, spaces, hyphens, parentheses, and plus sign
    return preg_replace( '/[^\d\s\-\(\)\+]/', '', $phone_number );
}

/**
 * Combine phone country code and number into full phone number
 *
 * @param string $country_code The country code (e.g., '+1')
 * @param string $phone_number The phone number
 * @return string Full phone number
 */
function notifal_combine_phone_number( $country_code, $phone_number ) {
    if ( empty( $country_code ) || empty( $phone_number ) ) {
        return '';
    }

    // Clean the phone number
    $cleaned_phone = preg_replace( '/\D/', '', $phone_number );

    // Remove country code prefix if it already exists in phone number
    $code_numeric = preg_replace( '/\D/', '', $country_code );
    if ( strpos( $cleaned_phone, $code_numeric ) === 0 ) {
        $cleaned_phone = substr( $cleaned_phone, strlen( $code_numeric ) );
    }

    return $country_code . $cleaned_phone;
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
 * Enqueue checkout-specific styles and scripts
 */
function notifal_enqueue_checkout_assets() {
    // Only load on checkout pages
    if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
        return;
    }

    // Enqueue checkout styles
    wp_enqueue_style(
        'notifal-checkout',
        THEME_ASSETS_PATH . 'css/checkout.css',
        array(),
        NOTIFAL_THEME_VERSION
    );

    // Enqueue checkout scripts
    wp_enqueue_script(
        'notifal-checkout',
        THEME_ASSETS_PATH . 'js/checkout.js',
        array( 'jquery', 'wc-checkout' ),
        NOTIFAL_THEME_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'notifal_enqueue_checkout_assets' );

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
    // Remove support-tickets endpoint added by plugin
    if ( isset( $items['support-tickets'] ) ) {
        unset( $items['support-tickets'] );
    }

    // Rename 'edit-account' to 'settings'
    if ( isset( $items['edit-account'] ) ) {
        $items['edit-account'] = __( 'Settings', 'notifal' );
    }

    // Add support endpoint after license-manager
    if ( isset( $items['license-manager'] ) ) {
        // Find the position of license-manager
        $position = array_search( 'license-manager', array_keys( $items ) );
        if ( $position !== false ) {
            // Insert support after license-manager
            $position++;
            $items = array_slice( $items, 0, $position, true ) +
                    array( 'support' => __( 'Support', 'notifal' ) ) +
                    array_slice( $items, $position, null, true );
        } else {
            // Fallback: just add it at the end
            $items['support'] = __( 'Support', 'notifal' );
        }
    } else {
        // If license-manager doesn't exist, add support at the end
        $items['support'] = __( 'Support', 'notifal' );
    }

    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'notifal_custom_account_menu_items' );

/**
 * Add custom account endpoints
 */
function notifal_add_custom_endpoints() {
    // Add custom endpoint for support or other features
    add_rewrite_endpoint( 'support', EP_PAGES );
}
add_action( 'init', 'notifal_add_custom_endpoints' );

/**
 * Handle custom endpoint content for Support
 */
function notifal_account_support_endpoint() {
    echo '<div class="notifal-support-portal">';
    echo do_shortcode( '[fluent_support_portal]' );
    echo '</div>';
}
add_action( 'woocommerce_account_support_endpoint', 'notifal_account_support_endpoint' );

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
    // Note: 'support' is now handled by the endpoint-specific action
    $theme_endpoints = array( 'dashboard', 'downloads', 'edit-account' );

    if ( in_array( $endpoint, $theme_endpoints ) ) {
        // Remove default WooCommerce content ONLY for these specific endpoints
        remove_action( 'woocommerce_account_content', 'woocommerce_account_content', 10 );

        // Load appropriate template based on endpoint
        switch ( $endpoint ) {
            case 'dashboard':
                wc_get_template( 'myaccount/dashboard.php' );
                break;
            case 'downloads':
                wc_get_template( 'myaccount/downloads.php' );
                break;
            case 'edit-account':
                wc_get_template( 'myaccount/form-edit-account.php' );
                break;
        }
    }
    // For all other endpoints (like license-manager, support), let WooCommerce handle them normally
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

/**
 * Flush rewrite rules when theme is activated to ensure custom endpoints work
 */
function notifal_flush_rewrite_rules() {
    if ( get_option( 'notifal_theme_activated' ) !== 'yes' ) {
        flush_rewrite_rules();
        update_option( 'notifal_theme_activated', 'yes' );
    }
}
add_action( 'after_setup_theme', 'notifal_after_setup_theme' );
add_action( 'init', 'notifal_init_download_handlers' );
add_action( 'init', 'notifal_flush_rewrite_rules' );

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

/**
 * ============================================================================
 * Fluent Support Email Template Override - WooCommerce Styled
 * ============================================================================
 */

/**
 * Override Fluent Support email template by filtering the final email body
 *
 * @param array $mail_data The wp_mail data array
 * @return array Modified mail data
 */
function notifal_override_fluent_support_email_body($mail_data) {
    // Only process Fluent Support emails
    if (!isset($mail_data['message']) || !is_string($mail_data['message'])) {
        return $mail_data;
    }

    // Check if this is a Fluent Support email by looking for specific markers
    $message = $mail_data['message'];
    $is_fluent_email = (strpos($message, 'fs_comment') !== false || strpos($message, 'fluent-support') !== false);

    // Log for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Notifal: Checking email for Fluent Support markers. Is Fluent email: ' . ($is_fluent_email ? 'YES' : 'NO'));
        if ($is_fluent_email) {
            error_log('Notifal: Applying WooCommerce email template to Fluent Support email. Subject: ' . ($mail_data['subject'] ?? 'No subject'));
        }
    }

    if (!$is_fluent_email) {
        // Not a Fluent Support email, return unchanged
        return $mail_data;
    }

    // Extract email subject for heading
    $email_heading = isset($mail_data['subject']) ? $mail_data['subject'] : __('Support Update', 'notifal');

    // Extract email body content (remove existing template wrapper)
    $email_body = $message;

    // Remove existing Fluent Support template wrapper if present
    if (preg_match('/<div class="fs_comment[^>]*>(.*?)<\/div>/s', $message, $matches)) {
        $email_body = $matches[1];
    }

    // Generate WooCommerce-styled email
    $woocommerce_email = notifal_generate_woocommerce_email($email_body, $email_heading);

    // Update the mail data with WooCommerce-styled email
    $mail_data['message'] = $woocommerce_email;

    // Log completion for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Notifal: Successfully applied WooCommerce email template to Fluent Support email.');
    }

    return $mail_data;
}
add_filter('wp_mail', 'notifal_override_fluent_support_email_body', 10, 1);

/**
 * Generate WooCommerce-styled email HTML
 *
 * @param string $email_body The email content
 * @param string $email_heading The email heading
 * @return string WooCommerce-styled email HTML
 */
function notifal_generate_woocommerce_email($email_body, $email_heading) {
    // Get WooCommerce email settings
    $bg          = get_option( 'woocommerce_email_background_color', '#f7f7f7' );
    $body        = get_option( 'woocommerce_email_body_background_color', '#ffffff' );
    $base        = get_option( 'woocommerce_email_base_color', '#0f4a5e' );
    $base_text   = wc_light_or_dark( $base, '#202020', '#ffffff' );
    $text        = get_option( 'woocommerce_email_text_color', '#3c3c3c' );
    $footer_text = get_option( 'woocommerce_email_footer_text_color', '#3c3c3c' );

    // Pick a contrasting color for links.
    $link_color = wc_hex_is_light( $base ) ? $base : $base_text;
    if ( wc_hex_is_light( $body ) ) {
        $link_color = wc_hex_is_light( $base ) ? $base_text : $base;
    }

    $bg_darker_10    = wc_hex_darker( $bg, 10 );
    $body_darker_10  = wc_hex_darker( $body, 10 );
    $base_lighter_20 = wc_hex_lighter( $base, 20 );
    $base_lighter_40 = wc_hex_lighter( $base, 40 );
    $text_lighter_20 = wc_hex_lighter( $text, 20 );
    $text_lighter_40 = wc_hex_lighter( $text, 40 );

    // Get site logo if available
    $logo_url = '';
    $header_image = get_option( 'woocommerce_email_header_image' );
    if ( $header_image ) {
        $logo_url = $header_image;
    }

    // Generate footer
    $footer_content = sprintf(
        __('This email is a service from %s. Support provided by our team.', 'notifal'),
        get_bloginfo('name')
    );

    ob_start();
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color: <?php echo esc_attr( $bg ); ?>; padding: 0; text-align: center;">
    <table width="100%" id="outer_wrapper" style="background-color: <?php echo esc_attr( $bg ); ?>;">
        <tr>
            <td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
            <td width="600">
                <div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" style="margin: 0 auto; padding: 70px 0; -webkit-text-size-adjust: none !important; width: 100%; max-width: 600px;">
                    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper">
                        <tr>
                            <td align="center" valign="top">
                                <?php if ( $logo_url ) : ?>
                                    <p style="margin-top:0; text-align: center;"><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" style="display: block; margin: 0 auto;" /></p>
                                <?php endif; ?>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" style="box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important; background-color: <?php echo esc_attr( $body ); ?>; border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>; border-radius: 40px !important;">
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Header -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-color: <?php echo esc_attr( $base ); ?>; border-radius: 40px 40px 0 0 !important; color: <?php echo esc_attr( $base_text ); ?>; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                                                <tr>
                                                    <td id="header_wrapper" style="padding: 36px 48px; display: block;">
                                                        <h1 style="color: <?php echo esc_attr( $base_text ); ?>; background-color: inherit; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>; text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;"><?php echo esc_html( $email_heading ); ?></h1>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Header -->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Body -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                                <tr>
                                                    <td valign="top" id="body_content" style="background-color: <?php echo esc_attr( $body ); ?>; border-radius: 40px;">
                                                        <!-- Content -->
                                                        <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td valign="top" id="body_content_inner_cell">
                                                                    <div id="body_content_inner" style="color: <?php echo esc_attr( $text_lighter_20 ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 150%; text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;">
                                                                        <?php echo wp_kses_post( $email_body ); ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <!-- End Content -->
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Body -->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" valign="top">
                                            <!-- Footer -->
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer">
                                                <tr>
                                                    <td valign="top">
                                                        <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td colspan="2" valign="middle" id="credit" style="padding: 0; border-radius: 6px; color: <?php echo esc_attr( $footer_text ); ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 12px; line-height: 150%; text-align: center; padding: 24px 0;">
                                                                    <?php echo wp_kses_post( $footer_content ); ?>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- End Footer -->
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
        </tr>
    </table>
</body>
</html>
    <?php
    return ob_get_clean();
}

/**
 * ============================================================================
 * Custom Redirects for fluent support page
 * ============================================================================
 */

/**
 * Redirect /support to WooCommerce support endpoint
 */
function notifal_redirect_support_to_woocommerce() {
    // Check if current URL is /support
    if ( isset( $_SERVER['REQUEST_URI'] ) && trim( $_SERVER['REQUEST_URI'], '/' ) === 'support' ) {
        // Redirect to WooCommerce support endpoint in my account
        $support_url = wc_get_account_endpoint_url( 'support' );
        if ( $support_url && $support_url !== wc_get_account_endpoint_url( '' ) ) {
            wp_redirect( $support_url );
            exit;
        }
    }
}
add_action( 'template_redirect', 'notifal_redirect_support_to_woocommerce' );

/**
 * ============================================================================
 * Support Ticket Count Functions for Dashboard
 * ============================================================================
 */

/**
 * Get support ticket counts for current user using Fluent Support plugin
 *
 * @return array Array containing total and open ticket counts
 *               Format: ['total' => int, 'open' => int]
 */
function notifal_get_user_support_ticket_counts() {
    // Initialize default values for ticket counts
    $counts = array(
        'total' => 0, // Total number of tickets created by user
        'open' => 0   // Number of tickets with open status (new, active, on-hold)
    );

    // Check if Fluent Support plugin classes are available
    if ( ! class_exists( 'FluentSupport\App\Models\Ticket' ) || ! class_exists( 'FluentSupport\App\Services\CustomerPortalService' ) ) {
        return $counts;
    }

    try {
        // Get current logged-in user ID
        $user_id = get_current_user_id();

        // Return default counts if no user is logged in
        if ( ! $user_id ) {
            return $counts;
        }

        // Initialize customer service for Fluent Support
        $customer_service = new \FluentSupport\App\Services\CustomerPortalService();

        // Resolve customer using current user IP address
        $customer = $customer_service->resolveCustomer( null, $_SERVER['REMOTE_ADDR'] ?? '' );

        // Return default counts if customer not found in Fluent Support
        if ( ! $customer ) {
            return $counts;
        }

        // Query total number of tickets for this customer
        $counts['total'] = \FluentSupport\App\Models\Ticket::where( 'customer_id', $customer->id )->count();

        // Define open ticket statuses for counting
        $open_statuses = array( 'new', 'active', 'on-hold' );

        // Query number of open tickets for this customer
        $counts['open'] = \FluentSupport\App\Models\Ticket::where( 'customer_id', $customer->id )
            ->whereIn( 'status', $open_statuses )
            ->count();

    } catch ( Exception $e ) {
        // Return default counts on error to prevent dashboard issues
        $counts = array( 'total' => 0, 'open' => 0 );
    }

    // Return the calculated ticket counts
    return $counts;
}

/**
 * Get formatted support ticket display value for dashboard
 *
 * Returns formatted string showing total tickets and open tickets in parentheses.
 * Format: "total (<span class="text-red">open</span>)" or "0" if no tickets exist.
 *
 * @return string Formatted display value for dashboard stats with open count in red
 */
function notifal_get_support_ticket_display_value() {
    // Get ticket counts from Fluent Support
    $counts = notifal_get_user_support_ticket_counts();

    // Return '0' if user has no tickets at all
    if ( $counts['total'] === 0 ) {
        return '0';
    }

    // Return formatted string: "total (<span class="text-red">open</span>)" - e.g., "5 (<span class="text-red">2</span>)" means 5 total, 2 open
    return sprintf(
        '%d (<span class="text-red">%d</span>)', // Format: total (<span class="text-red">open</span>)
        $counts['total'], // Total number of tickets
        $counts['open']   // Number of open tickets (highlighted in red)
    );
}

/**
 * ============================================================================
 * Shortcodes
 * ============================================================================
 */

/**
 * Display Notifal Pro product price with sale styling
 * Shortcode: [notifal-pro-price]
 *
 * Shows the current price of product ID 2412, with sale price prominently displayed
 * and regular price crossed out if there's a sale.
 *
 * @param array $atts Shortcode attributes (unused in this implementation)
 * @return string Formatted price HTML with proper styling
 */
function notifal_pro_price_shortcode( $atts ) {
    // Define the product ID for Notifal Pro
    $product_id = 2412;

    // Get the product object
    $product = wc_get_product( $product_id );

    // Check if product exists and is purchasable
    if ( ! $product || ! $product->is_purchasable() ) {
        return '<span class="notifal-price-error">' . esc_html__( 'Product not available', 'notifal' ) . '</span>';
    }

    // Get price information
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    $price = $product->get_price();

    // Sanitize and format prices
    $currency_symbol = get_woocommerce_currency_symbol();
    $formatted_regular_price = wc_price( $regular_price );
    $formatted_sale_price = $sale_price ? wc_price( $sale_price ) : '';
    $formatted_price = wc_price( $price );

    // Build the price display HTML
    $price_html = '<div class="notifal-pro-price-container">';

    if ( $product->is_on_sale() && $sale_price ) {
        // Product is on sale - show sale price prominently, regular price crossed out
        $price_html .= '<div class="notifal-price-sale">';
        $price_html .= '<span class="notifal-sale-price">' . $formatted_sale_price . '</span>';
        $price_html .= '<span class="notifal-regular-price-crossed">' . $formatted_regular_price . '</span>';
        $price_html .= '</div>';
    } else {
        // Regular price only
        $price_html .= '<div class="notifal-price-regular">';
        $price_html .= '<span class="notifal-regular-price">' . $formatted_price . '</span>';
        $price_html .= '</div>';
    }

    $price_html .= '</div>';

    return $price_html;
}
add_shortcode( 'notifal-pro-price', 'notifal_pro_price_shortcode' );

/**
 * Get phone country codes for dropdown selection
 *
 * @return array Array of country codes with country names
 */
function notifal_get_phone_country_codes() {
    return array(
        '' => __( 'Select country code', 'notifal' ),
        '+1' => '🇺🇸 United States (+1)',
        '+7' => '🇷🇺 Russia (+7)',
        '+20' => '🇪🇬 Egypt (+20)',
        '+27' => '🇿🇦 South Africa (+27)',
        '+30' => '🇬🇷 Greece (+30)',
        '+31' => '🇳🇱 Netherlands (+31)',
        '+32' => '🇧🇪 Belgium (+32)',
        '+33' => '🇫🇷 France (+33)',
        '+34' => '🇪🇸 Spain (+34)',
        '+36' => '🇭🇺 Hungary (+36)',
        '+39' => '🇮🇹 Italy (+39)',
        '+40' => '🇷🇴 Romania (+40)',
        '+41' => '🇨🇭 Switzerland (+41)',
        '+43' => '🇦🇹 Austria (+43)',
        '+44' => '🇬🇧 United Kingdom (+44)',
        '+45' => '🇩🇰 Denmark (+45)',
        '+46' => '🇸🇪 Sweden (+46)',
        '+47' => '🇳🇴 Norway (+47)',
        '+48' => '🇵🇱 Poland (+48)',
        '+49' => '🇩🇪 Germany (+49)',
        '+51' => '🇵🇪 Peru (+51)',
        '+52' => '🇲🇽 Mexico (+52)',
        '+53' => '🇨🇺 Cuba (+53)',
        '+54' => '🇦🇷 Argentina (+54)',
        '+55' => '🇧🇷 Brazil (+55)',
        '+56' => '🇨🇱 Chile (+56)',
        '+57' => '🇨🇴 Colombia (+57)',
        '+58' => '🇻🇪 Venezuela (+58)',
        '+60' => '🇲🇾 Malaysia (+60)',
        '+61' => '🇦🇺 Australia (+61)',
        '+62' => '🇮🇩 Indonesia (+62)',
        '+63' => '🇵🇭 Philippines (+63)',
        '+64' => '🇳🇿 New Zealand (+64)',
        '+65' => '🇸🇬 Singapore (+65)',
        '+66' => '🇹🇭 Thailand (+66)',
        '+81' => '🇯🇵 Japan (+81)',
        '+82' => '🇰🇷 South Korea (+82)',
        '+84' => '🇻🇳 Vietnam (+84)',
        '+86' => '🇨🇳 China (+86)',
        '+90' => '🇹🇷 Turkey (+90)',
        '+91' => '🇮🇳 India (+91)',
        '+92' => '🇵🇰 Pakistan (+92)',
        '+93' => '🇦🇫 Afghanistan (+93)',
        '+94' => '🇱🇰 Sri Lanka (+94)',
        '+95' => '🇲🇲 Myanmar (+95)',
        '+98' => '🇮🇷 Iran (+98)',
        '+212' => '🇲🇦 Morocco (+212)',
        '+213' => '🇩🇿 Algeria (+213)',
        '+216' => '🇹🇳 Tunisia (+216)',
        '+218' => '🇱🇾 Libya (+218)',
        '+220' => '🇬🇲 Gambia (+220)',
        '+221' => '🇸🇳 Senegal (+221)',
        '+222' => '🇲🇷 Mauritania (+222)',
        '+223' => '🇲🇱 Mali (+223)',
        '+224' => '🇬🇳 Guinea (+224)',
        '+225' => '🇨🇮 Ivory Coast (+225)',
        '+226' => '🇧🇫 Burkina Faso (+226)',
        '+227' => '🇳🇪 Niger (+227)',
        '+228' => '🇹🇬 Togo (+228)',
        '+229' => '🇧🇯 Benin (+229)',
        '+230' => '🇲🇺 Mauritius (+230)',
        '+231' => '🇱🇷 Liberia (+231)',
        '+232' => '🇸🇱 Sierra Leone (+232)',
        '+233' => '🇬🇭 Ghana (+233)',
        '+234' => '🇳🇬 Nigeria (+234)',
        '+235' => '🇹🇩 Chad (+235)',
        '+236' => '🇨🇫 Central African Republic (+236)',
        '+237' => '🇨🇲 Cameroon (+237)',
        '+238' => '🇨🇻 Cape Verde (+238)',
        '+239' => '🇸🇹 Sao Tome and Principe (+239)',
        '+240' => '🇬🇶 Equatorial Guinea (+240)',
        '+241' => '🇬🇦 Gabon (+241)',
        '+242' => '🇨🇬 Republic of the Congo (+242)',
        '+243' => '🇨🇩 Democratic Republic of the Congo (+243)',
        '+244' => '🇦🇴 Angola (+244)',
        '+245' => '🇬🇼 Guinea-Bissau (+245)',
        '+246' => '🇮🇴 British Indian Ocean Territory (+246)',
        '+248' => '🇸🇨 Seychelles (+248)',
        '+249' => '🇸🇩 Sudan (+249)',
        '+250' => '🇷🇼 Rwanda (+250)',
        '+251' => '🇪🇹 Ethiopia (+251)',
        '+252' => '🇸🇴 Somalia (+252)',
        '+253' => '🇩🇯 Djibouti (+253)',
        '+254' => '🇰🇪 Kenya (+254)',
        '+255' => '🇹🇿 Tanzania (+255)',
        '+256' => '🇺🇬 Uganda (+256)',
        '+257' => '🇧🇮 Burundi (+257)',
        '+258' => '🇲🇿 Mozambique (+258)',
        '+260' => '🇿🇲 Zambia (+260)',
        '+261' => '🇲🇬 Madagascar (+261)',
        '+262' => '🇷🇪 Reunion (+262)',
        '+263' => '🇿🇼 Zimbabwe (+263)',
        '+264' => '🇳🇦 Namibia (+264)',
        '+265' => '🇲🇼 Malawi (+265)',
        '+266' => '🇱🇸 Lesotho (+266)',
        '+267' => '🇧🇼 Botswana (+267)',
        '+268' => '🇸🇿 Eswatini (+268)',
        '+269' => '🇰🇲 Comoros (+269)',
        '+290' => '🇸🇭 Saint Helena (+290)',
        '+291' => '🇪🇷 Eritrea (+291)',
        '+297' => '🇦🇼 Aruba (+297)',
        '+298' => '🇫🇴 Faroe Islands (+298)',
        '+299' => '🇬🇱 Greenland (+299)',
        '+350' => '🇬🇮 Gibraltar (+350)',
        '+351' => '🇵🇹 Portugal (+351)',
        '+352' => '🇱🇺 Luxembourg (+352)',
        '+353' => '🇮🇪 Ireland (+353)',
        '+354' => '🇮🇸 Iceland (+354)',
        '+355' => '🇦🇱 Albania (+355)',
        '+356' => '🇲🇹 Malta (+356)',
        '+357' => '🇨🇾 Cyprus (+357)',
        '+358' => '🇫🇮 Finland (+358)',
        '+359' => '🇧🇬 Bulgaria (+359)',
        '+370' => '🇱🇹 Lithuania (+370)',
        '+371' => '🇱🇻 Latvia (+371)',
        '+372' => '🇪🇪 Estonia (+372)',
        '+373' => '🇲🇩 Moldova (+373)',
        '+374' => '🇦🇲 Armenia (+374)',
        '+375' => '🇧🇾 Belarus (+375)',
        '+376' => '🇦🇩 Andorra (+376)',
        '+377' => '🇲🇨 Monaco (+377)',
        '+378' => '🇸🇲 San Marino (+378)',
        '+380' => '🇺🇦 Ukraine (+380)',
        '+381' => '🇷🇸 Serbia (+381)',
        '+382' => '🇲🇪 Montenegro (+382)',
        '+383' => '🇽🇰 Kosovo (+383)',
        '+385' => '🇭🇷 Croatia (+385)',
        '+386' => '🇸🇮 Slovenia (+386)',
        '+387' => '🇧🇦 Bosnia and Herzegovina (+387)',
        '+389' => '🇲🇰 North Macedonia (+389)',
        '+420' => '🇨🇿 Czech Republic (+420)',
        '+421' => '🇸🇰 Slovakia (+421)',
        '+423' => '🇱🇮 Liechtenstein (+423)',
        '+500' => '🇫🇰 Falkland Islands (+500)',
        '+501' => '🇧🇿 Belize (+501)',
        '+502' => '🇬🇹 Guatemala (+502)',
        '+503' => '🇸🇻 El Salvador (+503)',
        '+504' => '🇭🇳 Honduras (+504)',
        '+505' => '🇳🇮 Nicaragua (+505)',
        '+506' => '🇨🇷 Costa Rica (+506)',
        '+507' => '🇵🇦 Panama (+507)',
        '+508' => '🇵🇲 Saint Pierre and Miquelon (+508)',
        '+509' => '🇭🇹 Haiti (+509)',
        '+590' => '🇬🇵 Guadeloupe (+590)',
        '+591' => '🇧🇴 Bolivia (+591)',
        '+592' => '🇬🇾 Guyana (+592)',
        '+593' => '🇪🇨 Ecuador (+593)',
        '+594' => '🇬🇫 French Guiana (+594)',
        '+595' => '🇵🇾 Paraguay (+595)',
        '+596' => '🇲🇶 Martinique (+596)',
        '+597' => '🇸🇷 Suriname (+597)',
        '+598' => '🇺🇾 Uruguay (+598)',
        '+599' => '🇨🇼 Curacao (+599)',
        '+670' => '🇹🇱 Timor-Leste (+670)',
        '+672' => '🇦🇶 Antarctica (+672)',
        '+673' => '🇧🇳 Brunei (+673)',
        '+674' => '🇳🇷 Nauru (+674)',
        '+675' => '🇵🇬 Papua New Guinea (+675)',
        '+676' => '🇹🇴 Tonga (+676)',
        '+677' => '🇸🇧 Solomon Islands (+677)',
        '+678' => '🇻🇺 Vanuatu (+678)',
        '+679' => '🇫🇯 Fiji (+679)',
        '+680' => '🇵🇼 Palau (+680)',
        '+681' => '🇼🇫 Wallis and Futuna (+681)',
        '+682' => '🇨🇰 Cook Islands (+682)',
        '+683' => '🇳🇺 Niue (+683)',
        '+684' => '🇦🇸 American Samoa (+684)',
        '+685' => '🇼🇸 Samoa (+685)',
        '+686' => '🇰🇮 Kiribati (+686)',
        '+687' => '🇳🇨 New Caledonia (+687)',
        '+688' => '🇹🇻 Tuvalu (+688)',
        '+689' => '🇵🇫 French Polynesia (+689)',
        '+690' => '🇹🇰 Tokelau (+690)',
        '+691' => '🇫🇲 Micronesia (+691)',
        '+692' => '🇲🇭 Marshall Islands (+692)',
        '+850' => '🇰🇵 North Korea (+850)',
        '+852' => '🇭🇰 Hong Kong (+852)',
        '+853' => '🇲🇴 Macau (+853)',
        '+855' => '🇰🇭 Cambodia (+855)',
        '+856' => '🇱🇦 Laos (+856)',
        '+880' => '🇧🇩 Bangladesh (+880)',
        '+886' => '🇹🇼 Taiwan (+886)',
        '+960' => '🇲🇻 Maldives (+960)',
        '+961' => '🇱🇧 Lebanon (+961)',
        '+962' => '🇯🇴 Jordan (+962)',
        '+963' => '🇸🇾 Syria (+963)',
        '+964' => '🇮🇶 Iraq (+964)',
        '+965' => '🇰🇼 Kuwait (+965)',
        '+966' => '🇸🇦 Saudi Arabia (+966)',
        '+967' => '🇾🇪 Yemen (+967)',
        '+968' => '🇴🇲 Oman (+968)',
        '+970' => '🇵🇸 Palestine (+970)',
        '+971' => '🇦🇪 United Arab Emirates (+971)',
        '+972' => '🇮🇱 Israel (+972)',
        '+973' => '🇧🇭 Bahrain (+973)',
        '+974' => '🇶🇦 Qatar (+974)',
        '+975' => '🇧🇹 Bhutan (+975)',
        '+976' => '🇲🇳 Mongolia (+976)',
        '+977' => '🇳🇵 Nepal (+977)',
        '+992' => '🇹🇯 Tajikistan (+992)',
        '+993' => '🇹🇲 Turkmenistan (+993)',
        '+994' => '🇦🇿 Azerbaijan (+994)',
        '+995' => '🇬🇪 Georgia (+995)',
        '+996' => '🇰🇬 Kyrgyzstan (+996)',
        '+998' => '🇺🇿 Uzbekistan (+998)',
    );
}


/**
 * Get default country code based on geolocation
 *
 * @return string Default country code
 */
function notifal_get_default_country_code() {
    // Try to get from WooCommerce geolocation
    if ( class_exists( 'WC_Geolocation' ) ) {
        $geolocation = new WC_Geolocation();
        $user_ip_address = $geolocation->get_ip_address();
        $user_geoip_data = $geolocation->geolocate_ip( $user_ip_address );


        if ( isset( $user_geoip_data['country'] ) && ! empty( $user_geoip_data['country'] ) ) {
            $country_code = $user_geoip_data['country'];
            $phone_code = notifal_country_code_to_phone_code( $country_code );


            return $phone_code;
        }
    }


    // Fallback to US
    return '+1';
}

/**
 * Set default billing country based on geolocation
 *
 * @param string $default_country Default country code
 * @return string Modified default country code
 */
function notifal_set_default_billing_country( $default_country ) {

    // Only set geolocation country if no country is already set (avoid overriding user choices)
    if ( empty( $default_country ) || $default_country === WC()->countries->get_base_country() ) {
        if ( class_exists( 'WC_Geolocation' ) ) {
            $geolocation = new WC_Geolocation();
            $user_ip_address = $geolocation->get_ip_address();
            $user_geoip_data = $geolocation->geolocate_ip( $user_ip_address );

            if ( isset( $user_geoip_data['country'] ) && ! empty( $user_geoip_data['country'] ) ) {
                $geo_country = $user_geoip_data['country'];


                return $geo_country;
            }
        }
    }

    return $default_country;
}

/**
 * Set default billing state based on geolocation
 *
 * @param string $default_state Default state code
 * @return string Modified default state code
 */
function notifal_set_default_billing_state( $default_state ) {
    // Only set geolocation state if no state is already set
    if ( empty( $default_state ) ) {
        if ( class_exists( 'WC_Geolocation' ) ) {
            $geolocation = new WC_Geolocation();
            $user_ip_address = $geolocation->get_ip_address();
            $user_geoip_data = $geolocation->geolocate_ip( $user_ip_address );

            if ( isset( $user_geoip_data['state'] ) && ! empty( $user_geoip_data['state'] ) ) {
                $geo_state = $user_geoip_data['state'];


                return $geo_state;
            }
        }
    }

    return $default_state;
}

/**
 * Convert ISO country code to phone country code
 *
 * @param string $country_code ISO country code (e.g., 'US')
 * @return string Phone country code (e.g., '+1')
 */
function notifal_country_code_to_phone_code( $country_code ) {
    $country_to_phone_map = array(
        'US' => '+1', 'CA' => '+1', // North America
        'GB' => '+44', 'UK' => '+44', // United Kingdom
        'FR' => '+33', // France
        'DE' => '+49', // Germany
        'IT' => '+39', // Italy
        'ES' => '+34', // Spain
        'NL' => '+31', // Netherlands
        'BE' => '+32', // Belgium
        'CH' => '+41', // Switzerland
        'AT' => '+43', // Austria
        'SE' => '+46', // Sweden
        'NO' => '+47', // Norway
        'DK' => '+45', // Denmark
        'FI' => '+358', // Finland
        'PL' => '+48', // Poland
        'CZ' => '+420', // Czech Republic
        'SK' => '+421', // Slovakia
        'HU' => '+36', // Hungary
        'RO' => '+40', // Romania
        'BG' => '+359', // Bulgaria
        'GR' => '+30', // Greece
        'PT' => '+351', // Portugal
        'IE' => '+353', // Ireland
        'RU' => '+7', // Russia
        'EG' => '+20', // Egypt
        'ZA' => '+27', // South Africa
        'BR' => '+55', // Brazil
        'MX' => '+52', // Mexico
        'AR' => '+54', // Argentina
        'CL' => '+56', // Chile
        'CO' => '+57', // Colombia
        'PE' => '+51', // Peru
        'VE' => '+58', // Venezuela
        'AU' => '+61', // Australia
        'NZ' => '+64', // New Zealand
        'JP' => '+81', // Japan
        'KR' => '+82', // South Korea
        'CN' => '+86', // China
        'IN' => '+91', // India
        'TH' => '+66', // Thailand
        'MY' => '+60', // Malaysia
        'SG' => '+65', // Singapore
        'PH' => '+63', // Philippines
        'ID' => '+62', // Indonesia
        'VN' => '+84', // Vietnam
        'TR' => '+90', // Turkey
        'IL' => '+972', // Israel
        'SA' => '+966', // Saudi Arabia
        'AE' => '+971', // UAE
        'QA' => '+974', // Qatar
        'KW' => '+965', // Kuwait
        'BH' => '+973', // Bahrain
        'OM' => '+968', // Oman
        'JO' => '+962', // Jordan
        'LB' => '+961', // Lebanon
        'MA' => '+212', // Morocco
        'TN' => '+216', // Tunisia
        'DZ' => '+213', // Algeria
        'LY' => '+218', // Libya
        'SD' => '+249', // Sudan
        'SS' => '+211', // South Sudan
        'ET' => '+251', // Ethiopia
        'KE' => '+254', // Kenya
        'UG' => '+256', // Uganda
        'TZ' => '+255', // Tanzania
        'RW' => '+250', // Rwanda
        'BI' => '+257', // Burundi
        'MW' => '+265', // Malawi
        'ZM' => '+260', // Zambia
        'ZW' => '+263', // Zimbabwe
        'BW' => '+267', // Botswana
        'NA' => '+264', // Namibia
        'AO' => '+244', // Angola
        'MZ' => '+258', // Mozambique
        'MG' => '+261', // Madagascar
        'MU' => '+230', // Mauritius
        'RE' => '+262', // Reunion
        'SC' => '+248', // Seychelles
        'KM' => '+269', // Comoros
        'DJ' => '+253', // Djibouti
        'SO' => '+252', // Somalia
        'ER' => '+291', // Eritrea
        'IQ' => '+964', // Iraq
        'SY' => '+963', // Syria
        'YE' => '+967', // Yemen
        'IR' => '+98',  // Iran
        'AF' => '+93',  // Afghanistan
        'PK' => '+92',  // Pakistan
        'LK' => '+94',  // Sri Lanka
        'NP' => '+977', // Nepal
        'BD' => '+880', // Bangladesh
        'BT' => '+975', // Bhutan
        'MV' => '+960', // Maldives
        'UZ' => '+998', // Uzbekistan
        'KG' => '+996', // Kyrgyzstan
        'TJ' => '+992', // Tajikistan
        'TM' => '+993', // Turkmenistan
        'AZ' => '+994', // Azerbaijan
        'GE' => '+995', // Georgia
        'AM' => '+374', // Armenia
        'EC' => '+593', // Ecuador
        'BO' => '+591', // Bolivia
        'PY' => '+595', // Paraguay
        'UY' => '+598', // Uruguay
        'GY' => '+592', // Guyana
        'SR' => '+597', // Suriname
        'FJ' => '+679', // Fiji
        'SB' => '+677', // Solomon Islands
        'VU' => '+678', // Vanuatu
        'NC' => '+687', // New Caledonia
        'PF' => '+689', // French Polynesia
        'CK' => '+682', // Cook Islands
        'NU' => '+683', // Niue
        'AS' => '+1684', // American Samoa
        'GU' => '+1671', // Guam
        'MP' => '+1670', // Northern Mariana Islands
        'FM' => '+691', // Micronesia
        'MH' => '+692', // Marshall Islands
        'PW' => '+680', // Palau
        'KI' => '+686', // Kiribati
        'TV' => '+688', // Tuvalu
        'NR' => '+674', // Nauru
        'WS' => '+685', // Samoa
        'KH' => '+855', // Cambodia
        'LA' => '+856', // Laos
        'TL' => '+670', // Timor-Leste
        'BN' => '+673', // Brunei
        'NE' => '+227', // Niger
        'BJ' => '+229', // Benin
        'BF' => '+226', // Burkina Faso
        'CI' => '+225', // Ivory Coast
        'GH' => '+233', // Ghana
        'TG' => '+228', // Togo
        'SN' => '+221', // Senegal
        'GM' => '+220', // Gambia
        'GN' => '+224', // Guinea
        'SL' => '+232', // Sierra Leone
        'LR' => '+231', // Liberia
        'ML' => '+223', // Mali
        'GW' => '+245', // Guinea-Bissau
        'CV' => '+238', // Cape Verde
        'ST' => '+239', // Sao Tome and Principe
        'GQ' => '+240', // Equatorial Guinea
        'GA' => '+241', // Gabon
        'CG' => '+242', // Republic of the Congo
        'CD' => '+243', // Democratic Republic of the Congo
        'YT' => '+262', // Mayotte
        'KM' => '+269', // Comoros
    );

    return isset( $country_to_phone_map[ strtoupper( $country_code ) ] ) ? $country_to_phone_map[ strtoupper( $country_code ) ] : '+1';
}

/**
 * Parse phone number into country code and number parts
 *
 * @param string $full_phone Full phone number
 * @return array Array with 'country_code' and 'phone_number' keys
 */
function notifal_parse_phone_number( $full_phone ) {
    if ( empty( $full_phone ) ) {
        return array( 'country_code' => '', 'phone_number' => '' );
    }

    // Check if phone starts with a country code
    $country_codes = array_keys( notifal_get_phone_country_codes() );
    $matched_code = '';

    foreach ( $country_codes as $code ) {
        if ( empty( $code ) ) continue;

        if ( strpos( $full_phone, $code ) === 0 ) {
            // Found a matching country code
            $matched_code = $code;
            $phone_number = substr( $full_phone, strlen( $code ) );
            break;
        }
    }

    if ( $matched_code ) {
        return array(
            'country_code' => $matched_code,
            'phone_number' => trim( $phone_number )
        );
    }

    // No country code found, return as-is
    return array(
        'country_code' => '',
        'phone_number' => $full_phone
    );
}

/**
 * Get country flag emoji for country code
 *
 * @param string $country_code Phone country code
 * @return string Flag emoji
 */
function notifal_get_country_flag( $country_code ) {
    $flag_map = array(
        '+1' => '🇺🇸', // US/Canada
        '+7' => '🇷🇺', // Russia
        '+20' => '🇪🇬', // Egypt
        '+27' => '🇿🇦', // South Africa
        '+30' => '🇬🇷', // Greece
        '+31' => '🇳🇱', // Netherlands
        '+32' => '🇧🇪', // Belgium
        '+33' => '🇫🇷', // France
        '+34' => '🇪🇸', // Spain
        '+36' => '🇭🇺', // Hungary
        '+39' => '🇮🇹', // Italy
        '+40' => '🇷🇴', // Romania
        '+41' => '🇨🇭', // Switzerland
        '+43' => '🇦🇹', // Austria
        '+44' => '🇬🇧', // UK
        '+45' => '🇩🇰', // Denmark
        '+46' => '🇸🇪', // Sweden
        '+47' => '🇳🇴', // Norway
        '+48' => '🇵🇱', // Poland
        '+49' => '🇩🇪', // Germany
        '+51' => '🇵🇪', // Peru
        '+52' => '🇲🇽', // Mexico
        '+53' => '🇨🇺', // Cuba
        '+54' => '🇦🇷', // Argentina
        '+55' => '🇧🇷', // Brazil
        '+56' => '🇨🇱', // Chile
        '+57' => '🇨🇴', // Colombia
        '+58' => '🇻🇪', // Venezuela
        '+60' => '🇲🇾', // Malaysia
        '+61' => '🇦🇺', // Australia
        '+62' => '🇮🇩', // Indonesia
        '+63' => '🇵🇭', // Philippines
        '+64' => '🇳🇿', // New Zealand
        '+65' => '🇸🇬', // Singapore
        '+66' => '🇹🇭', // Thailand
        '+81' => '🇯🇵', // Japan
        '+82' => '🇰🇷', // South Korea
        '+84' => '🇻🇳', // Vietnam
        '+86' => '🇨🇳', // China
        '+90' => '🇹🇷', // Turkey
        '+91' => '🇮🇳', // India
        '+92' => '🇵🇰', // Pakistan
        '+93' => '🇦🇫', // Afghanistan
        '+94' => '🇱🇰', // Sri Lanka
        '+95' => '🇲🇲', // Myanmar
        '+98' => '🇮🇷', // Iran
        '+212' => '🇲🇦', // Morocco
        '+213' => '🇩🇿', // Algeria
        '+971' => '🇦🇪', // UAE
    );

    return isset( $flag_map[ $country_code ] ) ? $flag_map[ $country_code ] : '🌍';
}

/**
 * Get country name from phone country code
 *
 * @param string $country_code Phone country code
 * @return string Country name
 */
function notifal_get_country_name_from_code( $country_code ) {
    $name_map = array(
        '+1' => 'United States/Canada',
        '+7' => 'Russia',
        '+20' => 'Egypt',
        '+27' => 'South Africa',
        '+30' => 'Greece',
        '+31' => 'Netherlands',
        '+32' => 'Belgium',
        '+33' => 'France',
        '+34' => 'Spain',
        '+36' => 'Hungary',
        '+39' => 'Italy',
        '+40' => 'Romania',
        '+41' => 'Switzerland',
        '+43' => 'Austria',
        '+44' => 'United Kingdom',
        '+45' => 'Denmark',
        '+46' => 'Sweden',
        '+47' => 'Norway',
        '+48' => 'Poland',
        '+49' => 'Germany',
        '+51' => 'Peru',
        '+52' => 'Mexico',
        '+53' => 'Cuba',
        '+54' => 'Argentina',
        '+55' => 'Brazil',
        '+56' => 'Chile',
        '+57' => 'Colombia',
        '+58' => 'Venezuela',
        '+60' => 'Malaysia',
        '+61' => 'Australia',
        '+62' => 'Indonesia',
        '+63' => 'Philippines',
        '+64' => 'New Zealand',
        '+65' => 'Singapore',
        '+66' => 'Thailand',
        '+81' => 'Japan',
        '+82' => 'South Korea',
        '+84' => 'Vietnam',
        '+86' => 'China',
        '+90' => 'Turkey',
        '+91' => 'India',
        '+92' => 'Pakistan',
        '+93' => 'Afghanistan',
        '+94' => 'Sri Lanka',
        '+95' => 'Myanmar',
        '+98' => 'Iran',
        '+212' => 'Morocco',
        '+213' => 'Algeria',
        '+971' => 'United Arab Emirates',
    );

    return isset( $name_map[ $country_code ] ) ? $name_map[ $country_code ] : 'Unknown Country';
}

/**
 * Get phone placeholder based on country code
 *
 * @param string $country_code Phone country code
 * @return string Placeholder text
 */
function notifal_get_phone_placeholder( $country_code ) {
    $placeholders = array(
        '+1' => '(555) 123-4567',
        '+44' => '7123 456789',
        '+33' => '01 23 45 67 89',
        '+49' => '030 1234567',
        '+91' => '98765 43210',
        '+86' => '138 0013 8000',
        '+81' => '090-1234-5678',
        '+82' => '010-1234-5678',
        '+61' => '0412 345 678',
        '+55' => '(11) 91234-5678',
        '+52' => '55 1234 5678',
        '+7' => '+7 (999) 123-45-67',
        '+971' => '50 123 4567',
    );

    return isset( $placeholders[ $country_code ] ) ? $placeholders[ $country_code ] : 'Enter phone number';
}

/**
 * Display WooCommerce billing fields only on checkout page
 * Shortcode: [notifal_billing_fields]
 *
 * This shortcode displays only the billing address fields from WooCommerce checkout,
 * excluding shipping fields. It should only be used on checkout-related pages.
 *
 * @param array $atts Shortcode attributes (unused in this implementation)
 * @return string Formatted billing fields HTML with proper security
 */
function notifal_billing_fields_shortcode( $atts ) {
    // Ensure WooCommerce is active
    if ( ! function_exists( 'WC' ) ) {
        return '<p class="notifal-error">' . esc_html__( 'WooCommerce is required for billing fields.', 'notifal' ) . '</p>';
    }

    // Initialize checkout if not already done
    if ( ! WC()->checkout ) {
        WC()->initialize_checkout();
    }

    // Get the checkout instance
    $checkout = WC()->checkout;
    if ( ! $checkout ) {
        return '<p class="notifal-error">' . esc_html__( 'Checkout is not available.', 'notifal' ) . '</p>';
    }

    // Ensure WooCommerce checkout styles and scripts are loaded
    if ( function_exists( 'wc_get_page_id' ) && is_page( wc_get_page_id( 'checkout' ) ) ) {
        // On checkout page, styles should already be loaded
    } else {
        // Load checkout styles and scripts for shortcode usage
        wp_enqueue_style( 'woocommerce-general' );
        wp_enqueue_style( 'woocommerce-layout' );
        wp_enqueue_style( 'woocommerce-smallscreen' );
        wp_enqueue_style( 'woocommerce_frontend_styles' );

        // Load checkout scripts
        wp_enqueue_script( 'wc-checkout' );
    }

    // Start output buffering for the entire shortcode
    ob_start();

    // Start the billing fields container
    echo '<div class="woocommerce-billing-fields notifal-billing-only">';
    echo '<h3>' . esc_html__( 'Billing details', 'woocommerce' ) . '</h3>';

    // Trigger action before billing form
    do_action( 'woocommerce_before_checkout_billing_form', $checkout );

    // Add nonce field
    wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );

    echo '<div class="woocommerce-billing-fields__field-wrapper">';

    // Define only the required billing fields with custom classes for layout
    $required_fields = array(
        'billing_first_name' => array(
            'type'        => 'text',
            'label'       => __( 'First name', 'woocommerce' ),
            'placeholder' => __( 'First name', 'woocommerce' ),
            'required'    => true,
            'class'       => array( 'form-row', 'notifal-row-1', 'notifal-field-left' ),
            'autocomplete' => 'given-name',
        ),
        'billing_last_name' => array(
            'type'        => 'text',
            'label'       => __( 'Last name', 'woocommerce' ),
            'placeholder' => __( 'Last name', 'woocommerce' ),
            'required'    => true,
            'class'       => array( 'form-row', 'notifal-row-1', 'notifal-field-right' ),
            'autocomplete' => 'family-name',
        ),
        'billing_email' => array(
            'type'        => 'email',
            'label'       => __( 'Email address', 'woocommerce' ),
            'placeholder' => __( 'Email address', 'woocommerce' ),
            'required'    => true,
            'class'       => array( 'form-row', 'notifal-row-2', 'notifal-field-left' ),
            'autocomplete' => 'email',
        ),
        'billing_phone' => array(
            'type'        => 'tel',
            'label'       => __( 'Phone number', 'woocommerce' ),
            'placeholder' => __( 'Phone number', 'woocommerce' ),
            'required'    => false,
            'class'       => array( 'form-row', 'notifal-row-2', 'notifal-field-right' ),
            'autocomplete' => 'tel',
        ),
        'billing_country' => array(
            'type'        => 'country',
            'label'       => __( 'Country / Region', 'woocommerce' ),
            'placeholder' => __( 'Country / Region', 'woocommerce' ),
            'required'    => true,
            'class'       => array( 'form-row', 'notifal-row-3', 'notifal-field-left' ),
            'autocomplete' => 'country',
        ),
        'billing_state' => array(
            'type'        => 'state',
            'label'       => __( 'State / County', 'woocommerce' ),
            'placeholder' => __( 'State / County', 'woocommerce' ),
            'required'    => false,
            'class'       => array( 'form-row', 'notifal-row-3', 'notifal-field-right' ),
            'autocomplete' => 'address-level1',
            'validate'    => array(), // Allow WooCommerce to handle validation dynamically
        ),
    );

    // Output all fields normally (WooCommerce will handle them)
    foreach ( $required_fields as $key => $field ) {
        // Sanitize field key for security
        $field_key = sanitize_key( $key );

        // Get field value from checkout
        $field_value = $checkout->get_value( $field_key );

        // Special handling for state field to ensure it's always optional
        if ( $field_key === 'billing_state' ) {
            $field['required'] = false;
            // Remove any required class that WooCommerce might add
            if ( isset( $field['class'] ) && is_array( $field['class'] ) ) {
                $field['class'] = array_diff( $field['class'], array( 'validate-required' ) );
            }
        }

        woocommerce_form_field( $field_key, $field, $field_value );
    }

    echo '</div>';

    // Trigger action after billing form
    do_action( 'woocommerce_after_checkout_billing_form', $checkout );

    // Add account creation fields if user is not logged in and registration is enabled
    if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) {
        echo '<div class="woocommerce-account-fields">';

        if ( ! $checkout->is_registration_required() ) {
            $create_account_checked = ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) );
            echo '<p class="form-row form-row-wide create-account">';
            echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">';
            echo '<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" ' . checked( $create_account_checked, true, false ) . ' type="checkbox" name="createaccount" value="1" />';
            echo '<span>' . esc_html__( 'Create an account?', 'woocommerce' ) . '</span>';
            echo '</label>';
            echo '</p>';
        }

        // Trigger action before registration form
        do_action( 'woocommerce_before_checkout_registration_form', $checkout );

        // Add account fields if they exist
        if ( $checkout->get_checkout_fields( 'account' ) ) {
            echo '<div class="create-account">';
            foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) {
                // Sanitize field key
                $field_key = sanitize_key( $key );
                $field_value = $checkout->get_value( $field_key );
                woocommerce_form_field( $field_key, $field, $field_value );
            }
            echo '<div class="clear"></div>';
            echo '</div>';
        }

        // Trigger action after registration form
        do_action( 'woocommerce_after_checkout_registration_form', $checkout );

        echo '</div>';
    }

    echo '</div>';

    // Get the buffered content and return it
    $output = ob_get_clean();
    return $output;
}
add_shortcode( 'notifal_billing_fields', 'notifal_billing_fields_shortcode' );

/**
 * Shortcode to display payment methods and checkout button
 *
 * @param array $atts Shortcode attributes (currently unused)
 * @return string HTML output for payment methods and checkout button
 */
function notifal_payment_checkout_shortcode( $atts ) {
    // Ensure WooCommerce is active
    if ( ! function_exists( 'WC' ) ) {
        return '<p class="notifal-error">' . esc_html__( 'WooCommerce is required for payment checkout.', 'notifal' ) . '</p>';
    }

    // Initialize checkout if not already done
    if ( ! WC()->checkout ) {
        WC()->initialize_checkout();
    }

    // Get the checkout instance
    $checkout = WC()->checkout;
    if ( ! $checkout ) {
        return '<p class="notifal-error">' . esc_html__( 'Checkout is not available.', 'notifal' ) . '</p>';
    }

    // Ensure WooCommerce checkout styles and scripts are loaded
    if ( function_exists( 'wc_get_page_id' ) && is_page( wc_get_page_id( 'checkout' ) ) ) {
        // On checkout page, styles should already be loaded
    } else {
        // Load checkout styles and scripts for shortcode usage
        wp_enqueue_style( 'woocommerce-general' );
        wp_enqueue_style( 'woocommerce-layout' );
        wp_enqueue_style( 'woocommerce-smallscreen' );
        wp_enqueue_style( 'woocommerce_frontend_styles' );

        // Load checkout scripts
        wp_enqueue_script( 'wc-checkout' );
    }

    // Start output buffering for the entire shortcode
    ob_start();

    // Trigger action before payment section
    do_action( 'woocommerce_review_order_before_payment' );

    // Start the payment container
    echo '<div id="payment" class="woocommerce-checkout-payment notifal-payment-checkout">';

    // Check if cart needs payment
    if ( WC()->cart && WC()->cart->needs_payment() ) {
        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
        WC()->payment_gateways()->set_current_gateway( $available_gateways );

        // Output payment methods
        if ( ! empty( $available_gateways ) ) {
            echo '<ul class="wc_payment_methods payment_methods methods">';
            foreach ( $available_gateways as $gateway ) {
                // Sanitize gateway ID for security
                $gateway_id = sanitize_key( $gateway->id );

                echo '<li class="wc_payment_method payment_method_' . esc_attr( $gateway_id ) . '">';
                echo '<input id="payment_method_' . esc_attr( $gateway_id ) . '" type="radio" class="input-radio" name="payment_method" value="' . esc_attr( $gateway_id ) . '" ' . checked( $gateway->chosen, true, false ) . ' />';
                echo '<label for="payment_method_' . esc_attr( $gateway_id ) . '">' . wp_kses_post( $gateway->get_title() ) . ' ' . wp_kses_post( $gateway->get_icon() ) . '</label>';

                // Output description in a styled box (only show for chosen gateway and only if description exists)
                if ( $gateway->chosen && $gateway->get_description() ) {
                    echo '<div class="payment_box payment_method_' . esc_attr( $gateway_id ) . '_description">';
                    echo wp_kses_post( wpautop( wptexturize( $gateway->get_description() ) ) );
                    echo '</div>';
                }

                if ( $gateway->has_fields() ) {
                    echo '<div class="payment_box payment_method_' . esc_attr( $gateway_id ) . '" ' . ( $gateway->chosen ? '' : 'style="display:none;"' ) . '>';
                    $gateway->payment_fields();
                    echo '</div>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<ul class="wc_payment_methods payment_methods methods">';
            echo '<li>';
            wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ), 'notice' );
            echo '</li>';
            echo '</ul>';
        }
    }

    // Add privacy policy text before the button (inside the payment container)
    $privacy_policy_text = get_option( 'woocommerce_checkout_privacy_policy_text', '' );
    if ( ! empty( $privacy_policy_text ) ) {
        // Process the privacy policy text and replace [privacy_policy] shortcode
        $privacy_page_id = wc_privacy_policy_page_id();
        if ( $privacy_page_id && get_post_status( $privacy_page_id ) === 'publish' ) {
            $privacy_link = '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '" target="_blank">' . esc_html__( 'privacy policy', 'notifal' ) . '</a>';
            $privacy_policy_text = str_replace( '[privacy_policy]', $privacy_link, $privacy_policy_text );
        } else {
            // Remove the shortcode if no privacy page is set
            $privacy_policy_text = str_replace( '[privacy_policy]', esc_html__( 'privacy policy', 'notifal' ), $privacy_policy_text );
        }
        echo '<div class="woocommerce-privacy-policy-text">';
        echo wp_kses_post( wpautop( wptexturize( $privacy_policy_text ) ) );
        echo '</div>';
    }

    // Output the place order section
    echo '<div class="form-row place-order">';

    // NoScript fallback for browsers without JavaScript
    echo '<noscript>';
    printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ), '<em>', '</em>' );
    echo '<br/><button type="submit" class="button alt' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update totals', 'woocommerce' ) . '">' . esc_html__( 'Update totals', 'woocommerce' ) . '</button>';
    echo '</noscript>';

    // Output terms and conditions
    wc_get_template( 'checkout/terms.php' );

    // Trigger action before submit button
    do_action( 'woocommerce_review_order_before_submit' );

    // Output the place order button
    $order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Checkout Now', 'woocommerce' ) );
    echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' );

    // Trigger action after submit button
    do_action( 'woocommerce_review_order_after_submit' );

    // Add nonce field for security
    wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );

    // Add custom terms text (before closing place-order div)
    echo '<p class="notifal-terms-text">';
    echo wp_kses(
        sprintf(
            __( 'By clicking "%1$s", you confirm that you\'ve read and agree to our %2$s.', 'notifal' ),
            esc_html( $order_button_text ),
            '<a href="' . esc_url( get_permalink( wc_get_page_id( 'terms' ) ) ) . '" target="_blank">' . esc_html__( 'Terms of Use', 'notifal' ) . '</a>'
        ),
        array(
            'a' => array(
                'href' => array(),
                'target' => array(),
            ),
        )
    );
    echo '</p>';

    echo '</div>'; // End place-order div

    echo '</div>'; // End payment div

    // Trigger action after payment section
    do_action( 'woocommerce_review_order_after_payment' );

    // Get the buffered content and return it
    $output = ob_get_clean();
    return $output;
}
add_shortcode( 'notifal_payment_checkout', 'notifal_payment_checkout_shortcode' );

/**
 * Complete checkout form wrapper shortcode
 *
 * This shortcode creates the complete checkout form that wraps all checkout sections.
 * Use this in Elementor instead of individual shortcodes to ensure proper form submission.
 *
 * Usage: [notifal_checkout_form]
 *
 * This shortcode will automatically include:
 * - Checkout notices
 * - Billing fields
 * - Order details
 * - Payment methods and checkout button
 *
 * @param array $atts Shortcode attributes (currently unused)
 * @return string HTML output for complete checkout form
 */
function notifal_checkout_form_shortcode( $atts ) {
    // Ensure WooCommerce is active
    if ( ! function_exists( 'WC' ) ) {
        return '<p class="notifal-error">' . esc_html__( 'WooCommerce is required for checkout.', 'notifal' ) . '</p>';
    }

    // Check if this is a gateway return scenario or pay for order request
    $order_id = 0;
    $order_key = '';
    $is_order_pay_page = false;
    $is_pay_for_order = false;

    // Check for order-pay page (gateway return or pay for order)
    if ( is_wc_endpoint_url( 'order-pay' ) ) {
        global $wp;
        $order_id = absint( $wp->query_vars['order-pay'] );
        $order_key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
        $is_order_pay_page = true;
        $is_pay_for_order = isset( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] === 'true';
    } else {
        // Check for regular query parameters
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $order_key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
    }


    // Handle pay for order requests (when user clicks "Try Again" on failed orders)
    if ( $is_pay_for_order && $order_id && $order_key ) {
        return notifal_pay_for_order_shortcode( array( 'order_id' => $order_id, 'order_key' => $order_key ) );
    }

    // If we have an order ID and key, this might be a gateway return
    if ( $order_id && $order_key ) {
        $order = wc_get_order( $order_id );

        // Verify the order key matches
        if ( $order && $order->get_order_key() === $order_key ) {
            $order_status = $order->get_status();

            // Log order status check
            error_log( 'Notifal Checkout: Order found - Status: ' . $order_status . ', Is Paid: ' . ( $order->is_paid() ? 'true' : 'false' ) );

            // Check if payment was successful
            if ( in_array( $order_status, array( 'completed', 'processing' ) ) && $order->is_paid() ) {
                // Log successful payment
                error_log( 'Notifal Checkout: Showing success template for order ' . $order_id );
                // Show success template
                if ( shortcode_exists( 'elementor-template' ) ) {
                    return do_shortcode( '[elementor-template id="3096"]' );
                }
            } elseif ( in_array( $order_status, array( 'failed', 'cancelled', 'pending' ) ) ) {
                // Log failed payment
                error_log( 'Notifal Checkout: Showing failed payment template for order ' . $order_id );
                // Show failed payment template
                return notifal_failed_payment_shortcode( array( 'order_id' => $order_id ) );
            }
        } else {
            // Log order key mismatch or order not found
            error_log( 'Notifal Checkout: Order not found or key mismatch - Order ID: ' . $order_id );
        }
    }

    // If we're on an order-pay page but didn't find a valid order above, show error
    if ( $is_order_pay_page && ! $order_id ) {
        return '<p class="notifal-error">' . esc_html__( 'Invalid order.', 'notifal' ) . '</p>';
    }

    // Check if cart has items (only for normal checkout, not order-pay pages)
    if ( ! $is_order_pay_page && ( ! WC()->cart || WC()->cart->is_empty() ) ) {
        return '<p class="notifal-error">' . esc_html__( 'Your cart is empty.', 'notifal' ) . '</p>';
    }

    // Initialize checkout if not already done
    if ( ! WC()->checkout ) {
        WC()->initialize_checkout();
    }

    // Ensure WooCommerce checkout styles and scripts are loaded
    wp_enqueue_style( 'woocommerce-general' );
    wp_enqueue_style( 'woocommerce-layout' );
    wp_enqueue_style( 'woocommerce-smallscreen' );
    wp_enqueue_style( 'woocommerce_frontend_styles' );
    wp_enqueue_script( 'wc-checkout' );
    wp_enqueue_script( 'wc-cart-fragments' );

    // Start output buffering
    ob_start();

    // Remove WooCommerce default coupon form since we have custom coupon handling in order details
    remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

    // Trigger action before checkout form
    do_action( 'woocommerce_before_checkout_form', WC()->checkout );

    // Start the checkout form
    echo '<form name="checkout" method="post" class="checkout woocommerce-checkout notifal-checkout-form" action="' . esc_url( wc_get_checkout_url() ) . '" enctype="multipart/form-data">';

    // Full width checkout notices at the top
    echo '<div class="notifal-checkout-notices-fullwidth">';
    echo do_shortcode( '[notifal_checkout_notices]' );
    echo '</div>';

    // Main checkout content container
    echo '<div class="notifal-checkout-content">';

    // Checkout heading (always first)
    echo '<div class="notifal-checkout-heading notifal-checkout-section">';
    echo '<h1>' . esc_html__( 'Checkout', 'notifal' ) . '</h1>';
    echo '<p>' . esc_html__( 'and unlock your next level of growth', 'notifal' ) . '</p>';
    echo '</div>';

    // Billing fields section
    echo '<div class="notifal-checkout-billing-section notifal-checkout-section">';
    echo do_shortcode( '[notifal_billing_fields]' );
    echo '</div>';

    // Order details section
    echo '<div class="notifal-checkout-order-section notifal-checkout-section">';
    echo do_shortcode( '[notifal_order_details]' );
    echo '</div>';

    // Payment section
    echo '<div class="notifal-checkout-payment-section notifal-checkout-section">';
    echo do_shortcode( '[notifal_payment_checkout]' );
    echo '</div>';

    // Elementor template section
    if ( shortcode_exists( 'elementor-template' ) ) {
        echo '<div class="notifal-checkout-elementor-section notifal-checkout-section">';
        echo do_shortcode( '[elementor-template id="3037"]' );
        echo '</div>';
    }

    echo '</div>'; // End checkout content

    // Trigger action after checkout form
    do_action( 'woocommerce_after_checkout_form', WC()->checkout );

    // Close the form
    echo '</form>';

    // Get the buffered content and return it
    $output = ob_get_clean();
    return $output;
}
add_shortcode( 'notifal_checkout_form', 'notifal_checkout_form_shortcode' );

/**
 * Order information shortcode
 *
 * This shortcode displays order information in a table format.
 * Can be used independently or within other shortcodes.
 *
 * Usage: [notifal_order_info order_id="123"] or [notifal_order_info]
 * If order_id is not provided, it will try to get the current order from checkout context.
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output for order information table
 */
function notifal_order_info_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => 0,
    ), $atts );

    $order_id = absint( $atts['order_id'] );
    $order = null;

    // If order_id is not provided, try to get it from current checkout context
    if ( ! $order_id ) {
        // Check if we're on an order-pay page
        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            global $wp;
            $order_id = absint( $wp->query_vars['order-pay'] );
        }
        // Check if there's an order being processed in checkout
        elseif ( function_exists( 'WC' ) && WC()->checkout && WC()->checkout->get_value( 'order_id' ) ) {
            $order_id = absint( WC()->checkout->get_value( 'order_id' ) );
        }
    }

    // Get the order if we have an ID
    if ( $order_id ) {
        $order = wc_get_order( $order_id );
    }

    if ( ! $order ) {
        return '<p class="notifal-error">' . esc_html__( 'Order not found.', 'notifal' ) . '</p>';
    }

    // Ensure checkout styles are loaded
    wp_enqueue_style( 'notifal-checkout' );

    // Start output buffering
    ob_start();

    ?>
    <div class="order-info">
        <h3><?php esc_html_e( 'Order Information', 'notifal' ); ?></h3>
        <table class="order-info-table">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Order ID:', 'notifal' ); ?></strong></td>
                    <td><?php echo esc_html( $order->get_order_number() ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Order Total:', 'notifal' ); ?></strong></td>
                    <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Order Date:', 'notifal' ); ?></strong></td>
                    <td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Payment Method:', 'notifal' ); ?></strong></td>
                    <td><?php echo esc_html( $order->get_payment_method_title() ); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Order Status:', 'notifal' ); ?></strong></td>
                    <td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php

    // Get the buffered content and return it
    return ob_get_clean();
}
add_shortcode( 'notifal_order_info', 'notifal_order_info_shortcode' );

/**
 * Failed payment shortcode for checkout
 *
 * This shortcode displays a custom failed payment page with eye-catching design,
 * helpful content, and action buttons for support and retry.
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output for failed payment page
 */
function notifal_failed_payment_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => 0,
    ), $atts );

    $order_id = absint( $atts['order_id'] );
    $order = $order_id ? wc_get_order( $order_id ) : null;

    // Ensure checkout styles are loaded
    wp_enqueue_style( 'notifal-checkout' );

    // Start output buffering
    ob_start();

    ?>
    <div class="notifal-failed-payment">
        <div class="failed-icon">☹️</div>
        <h1><?php esc_html_e( 'Payment Failed', 'notifal' ); ?></h1>
        <p><?php esc_html_e( 'We\'re sorry, but your payment could not be processed at this time. This can happen for various reasons such as insufficient funds, card declined, or technical issues.', 'notifal' ); ?></p>

        <div class="action-buttons">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'support' ) ); ?>" class="action-button support-button">
                <?php esc_html_e( 'Open a Support Ticket', 'notifal' ); ?>
            </a>
            <?php if ( $order ) : ?>
                <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="action-button retry-button">
                    <?php esc_html_e( 'Try Again', 'notifal' ); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="action-button retry-button">
                    <?php esc_html_e( 'Try Again', 'notifal' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ( $order ) : ?>
            <?php echo do_shortcode( '[notifal_order_info order_id="' . $order_id . '"]' ); ?>
        <?php endif; ?>
    </div>
    <?php

    // Get the buffered content and return it
    return ob_get_clean();
}
add_shortcode( 'notifal_failed_payment', 'notifal_failed_payment_shortcode' );

/**
 * Pay for order shortcode
 *
 * This shortcode displays the pay for order form, similar to WooCommerce's form-pay.php template
 * but with Notifal theme styling and integration.
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output for pay for order form
 */
function notifal_pay_for_order_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'order_id' => 0,
        'order_key' => '',
    ), $atts );

    $order_id = absint( $atts['order_id'] );
    $order_key = sanitize_text_field( $atts['order_key'] );

    // Validate order
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_order_key() !== $order_key ) {
        return '<p class="notifal-error">' . esc_html__( 'Invalid order.', 'notifal' ) . '</p>';
    }

    // Check permissions (similar to WooCommerce)
    if ( ! current_user_can( 'pay_for_order', $order_id ) && ! is_user_logged_in() ) {
        wc_print_notice( esc_html__( 'Please log in to your account below to continue to the payment form.', 'woocommerce' ), 'notice' );
        woocommerce_login_form(
            array(
                'redirect' => $order->get_checkout_payment_url(),
            )
        );
        return '';
    }

    // Check if order needs payment
    if ( ! $order->needs_payment() ) {
        return '<p class="notifal-error">' . sprintf( esc_html__( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance.', 'woocommerce' ), wc_get_order_status_name( $order->get_status() ) ) . '</p>';
    }

    // Ensure checkout styles are loaded
    wp_enqueue_style( 'notifal-checkout' );
    wp_enqueue_script( 'wc-checkout' );

    // Start output buffering
    ob_start();

    // Set customer data for payment processing
    WC()->customer->set_props(
        array(
            'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
            'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
            'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
        )
    );
    WC()->customer->save();

    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    if ( count( $available_gateways ) ) {
        current( $available_gateways )->set_current();
    }

    $order_button_text = apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'woocommerce' ) );
    $totals = $order->get_order_item_totals();

    ?>
    <div class="notifal-pay-for-order">
        <!-- Checkout heading (always first) -->
        <div class="notifal-checkout-heading notifal-checkout-section">
            <h1><?php esc_html_e( 'Complete Your Payment', 'notifal' ); ?></h1>
            <p><?php esc_html_e( 'Please complete your payment to finalize your order.', 'notifal' ); ?></p>
        </div>

        <!-- Main checkout content container -->
        <div class="notifal-checkout-content">
            <!-- Payment section (left column) -->
            <div class="notifal-checkout-payment-section notifal-checkout-section">
                <form id="order_review" method="post" class="notifal-checkout-form">
                    <h3><?php esc_html_e( 'Payment Method', 'notifal' ); ?></h3>
                    <?php if ( $order->needs_payment() ) : ?>
                        <ul class="wc_payment_methods payment_methods methods">
                            <?php
                            if ( ! empty( $available_gateways ) ) {
                                foreach ( $available_gateways as $gateway ) {
                                    wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
                                }
                            } else {
                                echo '<li>';
                                wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' );
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    <?php endif; ?>

                    <!-- Pay Button in Payment Section -->
                    <div class="notifal-pay-order-actions">
                        <?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

                        <input type="hidden" name="woocommerce_pay" value="1" />
                        <?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>

                        <?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt notifal-pay-button' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); ?>

                        <?php do_action( 'woocommerce_pay_order_after_submit' ); ?>
                    </div>
                </form>
            </div>

            <!-- Order details section (right column) -->
            <div class="notifal-checkout-order-section notifal-checkout-section">
                <h3><?php esc_html_e( 'Order Summary', 'notifal' ); ?></h3>
                <table class="shop_table notifal-order-summary-table">
                    <thead>
                        <tr>
                            <th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
                            <th class="product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( count( $order->get_items() ) > 0 ) : ?>
                            <?php foreach ( $order->get_items() as $item_id => $item ) : ?>
                                <?php
                                if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
                                    continue;
                                }
                                ?>
                                <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
                                    <td class="product-name">
                                        <?php
                                        echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
                                        do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
                                        wc_display_item_meta( $item );
                                        do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
                                        ?>
                                    </td>
                                    <td class="product-total"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php if ( $totals ) : ?>
                            <?php foreach ( $totals as $total ) : ?>
                                <tr>
                                    <th scope="row" colspan="1"><?php echo $total['label']; ?></th>
                                    <td class="product-total"><?php echo $total['value']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tfoot>
                </table>

                <!-- Terms -->
                <?php wc_get_template( 'checkout/terms.php' ); ?>

                <!-- Elementor template section -->
                <?php if ( shortcode_exists( 'elementor-template' ) ) : ?>
                    <div class="notifal-checkout-elementor-section notifal-checkout-section">
                        <?php echo do_shortcode( '[elementor-template id="3037"]' ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php

    // Get the buffered content and return it
    return ob_get_clean();
}
add_shortcode( 'notifal_pay_for_order', 'notifal_pay_for_order_shortcode' );

/**
 * Order details shortcode for checkout
 *
 * This shortcode displays the order details with product information, domain count selector,
 * coupon field, pricing breakdown, and customer testimonial.
 *
 * @param array $atts Shortcode attributes (unused)
 * @return string HTML output for order details
 */
function notifal_order_details_shortcode( $atts ) {
    // Ensure WooCommerce is active
    if ( ! function_exists( 'WC' ) ) {
        return '<p class="notifal-error">' . esc_html__( 'WooCommerce is required for order details.', 'notifal' ) . '</p>';
    }

    // Check if cart has items
    if ( ! WC()->cart || WC()->cart->is_empty() ) {
        return '<p class="notifal-error">' . esc_html__( 'Your cart is empty.', 'notifal' ) . '</p>';
    }

    // Initialize checkout if not already done
    if ( ! WC()->checkout ) {
        WC()->initialize_checkout();
    }

    // Start output buffering
    ob_start();

    // Get cart items
    $cart_items = WC()->cart->get_cart();
    
    // Start the order details container
    echo '<div class="notifal-order-details notifal-theme-hero-section-box">';

    // Heading
    echo '<h2 class="notifal-order-details__heading">' . esc_html__( 'Order Details', 'notifal' ) . '</h2>';

    
    // Loop through cart items
    foreach ( $cart_items as $cart_item_key => $cart_item ) {
        // Get product object
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        
        if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
            continue;
        }
        
        // Get product details
        $product_id = $_product->get_id();
        $product_name = $_product->get_name();
        $product_price = $_product->get_price();
        $product_image = $_product->get_image_id();
        
        // Check if product has domain count enabled
        $enable_domain_count = get_post_meta( $product_id, '_nlm_enable_domain_count', true ) === 'yes';
        $is_agency = get_post_meta( $product_id, '_nlm_is_agency_product', true ) === 'yes';
        
        // Get current domain/license count from cart item
        $domain_count = 1;
        if ( isset( $cart_item['nlm_domain_count'] ) ) {
            $domain_count = intval( $cart_item['nlm_domain_count'] );
        } elseif ( isset( $cart_item['nlm_agency_licenses'] ) ) {
            $domain_count = intval( $cart_item['nlm_agency_licenses'] );
        }
        
        // Start product row
        echo '<div class="notifal-order-details__product">';
        
        // Product icon/image
        echo '<div class="notifal-order-details__product-icon">';
        if ( $product_image ) {
            echo wp_get_attachment_image( $product_image, 'thumbnail', false, array( 'class' => 'notifal-order-details__icon-img' ) );
        } else {
            // Default icon placeholder
            echo '<div class="notifal-order-details__icon-placeholder"></div>';
        }
        echo '</div>';
        
        // Product info and domain count
        echo '<div class="notifal-order-details__product-info">';
        
        // Product name
        echo '<h3 class="notifal-order-details__product-name">' . esc_html( $product_name ) . '</h3>';
        
        // Price
        echo '<div class="notifal-order-details__product-price">';

        // Check if product is on sale
        if ( $_product->is_on_sale() ) {
            $regular_price = $_product->get_regular_price();
            $sale_price = $_product->get_sale_price();

            // Show regular price with strikethrough
            echo '<span class="notifal-order-details__price-regular">' . wc_price( $regular_price ) . '</span>';
            // Show sale price
            echo '<span class="notifal-order-details__price-sale">' . wc_price( $sale_price ) . '</span>';
        } else {
            // Show regular price only
            echo '<span class="notifal-order-details__price-amount">' . wc_price( $product_price ) . '</span>';
        }

        echo '<span class="notifal-order-details__price-period">/year</span>';
        echo '</div>';
        
        echo '</div>'; // Close product-info
        
        // Domain count selector (if enabled)
        if ( $enable_domain_count ) {
            echo '<div class="notifal-order-details__domain-count">';
            
            echo '<div class="notifal-order-details__domain-count-input">';
            // Minus button
            echo '<button type="button" class="notifal-order-details__btn notifal-order-details__btn--minus" data-cart-key="' . esc_attr( $cart_item_key ) . '" data-action="decrease">-</button>';
            
            // Domain count display
            echo '<span class="notifal-order-details__domain-number">' . esc_html( $domain_count ) . '</span>';
            
            // Plus button
            echo '<button type="button" class="notifal-order-details__btn notifal-order-details__btn--plus" data-cart-key="' . esc_attr( $cart_item_key ) . '" data-action="increase">+</button>';
            echo '</div>';
            echo '<div class="notifal-order-details__domain-count-label">';

            // Label with info icon
            $label = $is_agency ? __( 'Licenses', 'notifal' ) : __( 'Domains', 'notifal' );
            echo '<span class="notifal-order-details__domain-label">' . esc_html( $label ) . '</span>';

            // Info icon with tooltip
            $tooltip_text = '';
            if ( ! $is_agency ) {
                // Get domain pricing info from product meta or calculate
                $extra_domain_price = get_post_meta( $product_id, '_nlm_extra_domain_price', true );
                if ( empty( $extra_domain_price ) ) {
                    $extra_domain_price = $product_price * 0.5; // Default 50% of base price
                }

                $tooltip_text = sprintf(
                    __( 'Each extra domain costs %s. For more domains, consider our %s.', 'notifal' ),
                    wc_price( $extra_domain_price ),
                    '<a href="' . esc_url( get_permalink( 872 ) ) . '" target="_blank">' . esc_html__( 'agency plan', 'notifal' ) . '</a>'
                );
            } else {
                $tooltip_text = __( 'Multiple license activations included.', 'notifal' );
            }

            echo '<span class="notifal-order-details__domain-info-icon" data-tooltip="' . esc_attr( $tooltip_text ) . '">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">';
            echo '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>';
            echo '<path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>';
            echo '</svg>';
            echo '</span>';

            echo '</div>';
            echo '</div>'; // Close domain-count
        }
        
        echo '</div>'; // Close product
    }
    
    // Coupon section
    echo '<div class="notifal-order-details__coupon">';

    // Get applied coupons
    $applied_coupons = WC()->cart->get_applied_coupons();
    $has_applied_coupons = ! empty( $applied_coupons );

    if ( $has_applied_coupons ) {
        // Show applied coupons
        echo '<div class="notifal-order-details__applied-coupons">';
        foreach ( $applied_coupons as $coupon_code ) {
            $coupon = new WC_Coupon( $coupon_code );
            $discount_amount = WC()->cart->get_coupon_discount_amount( $coupon_code );
            $discount_tax = WC()->cart->get_coupon_discount_tax_amount( $coupon_code );
            $total_discount = $discount_amount + $discount_tax;

            echo '<div class="notifal-order-details__applied-coupon">';
            echo '<span class="notifal-order-details__coupon-code">' . esc_html( $coupon_code ) . '</span>';
            echo '<span class="notifal-order-details__coupon-discount">(' . wc_price( $total_discount ) . ' ' . esc_html__( 'off', 'notifal' ) . ')</span>';
            echo '<button type="button" class="notifal-order-details__coupon-remove" data-coupon="' . esc_attr( $coupon_code ) . '">' . esc_html__( 'Remove', 'notifal' ) . '</button>';
            echo '</div>';
        }
        echo '</div>';

        // Show "Add another coupon" link
        echo '<span class="notifal-order-details__coupon-text">' . esc_html__( 'Have another coupon? ', 'notifal' ) . '</span>';
        echo '<a href="#" class="notifal-order-details__coupon-link" id="notifal-show-coupon">' . esc_html__( 'Click here', 'notifal' ) . '</a>';
    } else {
        // Show initial coupon link
        echo '<span class="notifal-order-details__coupon-text">' . esc_html__( 'Have a coupon? ', 'notifal' ) . '</span>';
        echo '<a href="#" class="notifal-order-details__coupon-link" id="notifal-show-coupon">' . esc_html__( 'Click here', 'notifal' ) . '</a>';
    }

    // Coupon form (hidden by default)
    $coupon_input_value = $has_applied_coupons ? $applied_coupons[0] : '';
    echo '<div class="notifal-order-details__coupon-form" id="notifal-coupon-form" style="display:none;">';
    echo '<input type="text" name="coupon_code" class="notifal-order-details__coupon-input" placeholder="' . esc_attr__( 'Enter coupon code', 'notifal' ) . '" value="' . esc_attr( $coupon_input_value ) . '" />';
    echo '<button type="button" class="notifal-order-details__coupon-apply" id="notifal-apply-coupon">' . esc_html__( 'Apply', 'notifal' ) . '</button>';
    echo '</div>';
    echo '</div>'; // Close coupon
    
    // Pricing breakdown
    echo '<div class="notifal-order-details__pricing">';
    
    // Total Order
    echo '<div class="notifal-order-details__total">';
    echo '<span class="notifal-order-details__total-label">' . esc_html__( 'Total Order', 'notifal' ) . '</span>';
    echo '<span class="notifal-order-details__total-amount">' . wc_price( WC()->cart->get_total( 'edit' ) ) . '</span>';
    echo '</div>';
    
    echo '</div>'; // Close pricing
    
    // Testimonial section
    echo '<div class="notifal-order-details__testimonial">';
    echo '<div class="notifal-order-details__testimonial-quote">';
    echo '<span class="notifal-order-details__quote-mark">“</span>';
    echo '<p class="notifal-order-details__quote-text">';
    echo esc_html__( 'Just one notification showing recent buyers, the discount, and time left ', 'notifal' );
    echo '<strong>' . esc_html__( 'boosted our sales by 330%', 'notifal' ) . '</strong>';
    echo esc_html__( '!', 'notifal' );
    echo '</p>';
    echo '<p class="notifal-order-details__quote-author">' . esc_html__( 'Marketing Manager at Elecsaz', 'notifal' ) . '</p>';
    echo '</div>';
    echo '</div>'; // Close testimonial
    
    echo '</div>'; // Close notifal-order-details
    
    // Get the buffered content and return it
    $output = ob_get_clean();
    return $output;
}
add_shortcode( 'notifal_order_details', 'notifal_order_details_shortcode' );

/**
 * AJAX handler to update domain count in cart
 *
 * This function handles AJAX requests from the order details shortcode
 * to update the domain count for a specific cart item.
 *
 * @since 1.0.0
 */
function notifal_ajax_update_domain_count() {
    try {
        // Verify nonce for security
        check_ajax_referer( 'woocommerce-process_checkout', 'security' );

        // Get and sanitize the cart key
        $cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( $_POST['cart_key'] ) : '';

        // Get and validate the domain count
        $domain_count = isset( $_POST['domain_count'] ) ? intval( $_POST['domain_count'] ) : 1;

        // Validate domain count range
        if ( $domain_count < 1 ) {
            $domain_count = 1;
        }
        if ( $domain_count > 100 ) { // Reasonable upper limit
            wp_send_json_error( array( 'message' => __( 'Domain count cannot exceed 100.', 'notifal' ) ) );
            return;
        }

        // Check if cart key is valid
        if ( empty( $cart_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request. Please refresh the page and try again.', 'notifal' ) ) );
            return;
        }

        // Get cart
        $cart = WC()->cart;
        if ( ! $cart ) {
            wp_send_json_error( array( 'message' => __( 'Shopping cart is not available. Please refresh the page.', 'notifal' ) ) );
            return;
        }

        // Get cart item
        $cart_item = $cart->get_cart_item( $cart_key );
        if ( ! $cart_item ) {
            wp_send_json_error( array( 'message' => __( 'Product not found in cart. Please refresh the page.', 'notifal' ) ) );
            return;
        }
    } catch ( Exception $e ) {
        wp_send_json_error( array( 'message' => __( 'An unexpected error occurred. Please try again.', 'notifal' ) ) );
        return;
    }

    // Update domain count in cart item data
    if ( isset( $cart_item['nlm_domain_count'] ) ) {
        // Update regular domain count
        $cart->cart_contents[ $cart_key ]['nlm_domain_count'] = $domain_count;
    } elseif ( isset( $cart_item['nlm_agency_licenses'] ) ) {
        // Update agency license count
        $cart->cart_contents[ $cart_key ]['nlm_agency_licenses'] = $domain_count;
    } else {
        // If neither is set, this might be a new addition - set domain count
        $cart->cart_contents[ $cart_key ]['nlm_domain_count'] = $domain_count;
    }

    // Save cart to session
    $cart->set_session();

    // Force recalculate totals (this should trigger nlm_calculate_domain_price)
    $cart->calculate_totals();

    // Get updated cart total
    $cart_total = $cart->get_total();

    // Return success response with updated data
    wp_send_json_success( array(
        'message' => esc_html__( 'Domain count updated successfully.', 'notifal' ),
        'domain_count' => $domain_count,
        'cart_total' => $cart_total,
        'cart_total_formatted' => wp_strip_all_tags( wc_price( $cart_total ) )
    ) );
}
add_action( 'wp_ajax_notifal_update_domain_count', 'notifal_ajax_update_domain_count' );
add_action( 'wp_ajax_nopriv_notifal_update_domain_count', 'notifal_ajax_update_domain_count' );

/**
 * Add notifal order details to WooCommerce checkout fragments
 *
 * @param array $fragments
 * @return array
 */
function notifal_add_order_details_to_checkout_fragments( $fragments ) {
    if ( function_exists( 'notifal_order_details_shortcode' ) ) {
        $total = WC()->cart->get_total( 'edit' );
        $fragments['.notifal-order-details__total-amount'] = '<span class="notifal-order-details__total-amount">' . wc_price( $total ) . '</span>';
    }
    return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'notifal_add_order_details_to_checkout_fragments' );

/**
 * Add checkout notices to WooCommerce fragments for AJAX updates
 *
 * @param array $fragments
 * @return array
 */
function notifal_add_checkout_notices_to_fragments( $fragments ) {
    // Add notices wrapper to fragments for AJAX updates
    if ( function_exists( 'wc_print_notices' ) ) {
        ob_start();
        echo '<div class="woocommerce-notices-wrapper notifal-checkout-notices">';
        wc_print_notices();
        echo '</div>';
        $notices_html = ob_get_clean();

        $fragments['.woocommerce-notices-wrapper'] = $notices_html;
        $fragments['.notifal-checkout-notices'] = $notices_html;
    }

    return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'notifal_add_checkout_notices_to_fragments' );

/**
 * Preserve payment checkout section content and styling during WooCommerce AJAX updates
 *
 * This completely replaces WooCommerce's payment section with our custom shortcode output
 *
 * @param array $fragments
 * @return array
 */
function notifal_preserve_payment_checkout_styling( $fragments ) {
    // Replace WooCommerce's payment section with our custom shortcode output
    if ( function_exists( 'notifal_payment_checkout_shortcode' ) ) {
        // Temporarily set doing_ajax to false to prevent script enqueuing during AJAX
        $original_doing_ajax = wp_doing_ajax();
        if ( $original_doing_ajax ) {
            // Mock the doing_ajax function to return false during shortcode execution
            add_filter( 'wp_doing_ajax', '__return_false', 999 );
        }

        // Generate our custom payment section HTML
        $custom_payment_html = notifal_payment_checkout_shortcode( array() );

        // Restore original doing_ajax state
        if ( $original_doing_ajax ) {
            remove_filter( 'wp_doing_ajax', '__return_false', 999 );
        }

        // Replace WooCommerce's payment fragment with our custom HTML
        // Our shortcode already includes the proper #payment ID and classes
        $fragments['#payment'] = $custom_payment_html;
    }

    return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'notifal_preserve_payment_checkout_styling', 20 );

/**
 * AJAX handler to get updated total for notifal order details
 *
 * @return void
 */
function notifal_ajax_get_updated_total() {
    try {
        // Verify nonce for security
        check_ajax_referer( 'woocommerce-process_checkout', 'security' );

        // Ensure cart is available
        if ( ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'Shopping cart is not available.', 'notifal' ) ) );
            return;
        }

        // Force recalculate totals to ensure we have the latest
        WC()->cart->calculate_totals();

        // Save cart session to ensure persistence
        WC()->cart->set_session();

        // Get the updated cart total
        $total = WC()->cart->get_total( 'edit' );

        // Return success response with formatted total
        wp_send_json_success( array(
            'total_html' => wc_price( $total )
        ) );
    } catch ( Exception $e ) {
        wp_send_json_error( array( 'message' => __( 'Failed to calculate total. Please refresh the page.', 'notifal' ) ) );
    }
}
add_action( 'wp_ajax_notifal_get_updated_total', 'notifal_ajax_get_updated_total' );
add_action( 'wp_ajax_nopriv_notifal_get_updated_total', 'notifal_ajax_get_updated_total' );

/**
 * Shortcode to display WooCommerce checkout notices
 * Use this in Elementor to show checkout notices in custom locations
 *
 * This creates a WooCommerce-style notice system that works with Elementor checkout pages.
 * Notices will automatically update via AJAX when checkout is updated.
 *
 * Usage in Elementor:
 * 1. Add a "Shortcode" widget anywhere in your checkout template
 * 2. Use: [notifal_checkout_notices]
 *
 * Attributes:
 * - type: 'all' (default), 'error', 'success', 'info' - Filter notice types
 * - class: 'notifal-checkout-notices' (default) - Custom CSS class
 *
 * Example: [notifal_checkout_notices type="error" class="my-custom-notices"]
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function notifal_checkout_notices_shortcode( $atts ) {
    // Set default attributes
    $atts = shortcode_atts( array(
        'type' => 'all', // all, error, success, info
        'class' => 'notifal-checkout-notices',
        'exclude_coupon' => 'true' // Exclude coupon messages since they're handled in order details
    ), $atts, 'notifal_checkout_notices' );

    // Start output buffering
    ob_start();

    // Check if we're on a checkout page or have notices
    if ( function_exists( 'wc_print_notices' ) && ( is_checkout() || WC()->session ) ) {
        // Get current notices
        $notices = wc_get_notices();
        $has_notices = false;

        // Filter out coupon notices if exclude_coupon is true
        if ( $atts['exclude_coupon'] === 'true' ) {
            foreach ( $notices as $notice_type => $notice_array ) {
                foreach ( $notice_array as $key => $notice ) {
                    // Only filter out the default WooCommerce coupon message, not error messages
                    if ( is_string( $notice ) && $notice_type === 'notice' &&
                        strpos( $notice, 'Have a coupon?' ) !== false &&
                        strpos( $notice, 'Click here to enter your code' ) !== false ) {
                        unset( $notices[$notice_type][$key] );
                    }
                }
            }
        }

        // Check if we still have notices to display
        foreach ( $notices as $notice_type => $notice_array ) {
            if ( ! empty( $notice_array ) ) {
                $has_notices = true;
                break;
            }
        }

        if ( $has_notices ) {
            echo '<div class="woocommerce-notices-wrapper ' . esc_attr( $atts['class'] ) . '">';

            // Temporarily replace notices for filtering
            $original_notices = WC()->session->get( 'wc_notices', array() );
            WC()->session->set( 'wc_notices', $notices );

            // Print notices based on type
            switch ( $atts['type'] ) {
                case 'error':
                    wc_print_notices();
                    break;
                case 'success':
                    wc_print_notices();
                    break;
                case 'info':
                    wc_print_notices();
                    break;
                case 'all':
                default:
                    wc_print_notices();
                    break;
            }

            // Restore original notices
            WC()->session->set( 'wc_notices', $original_notices );

            echo '</div>';
        }
    }

    return ob_get_clean();
}
add_shortcode( 'notifal_checkout_notices', 'notifal_checkout_notices_shortcode' );

/**
 * AJAX handler to add WooCommerce notices
 *
 * @return void
 */
function notifal_ajax_add_woocommerce_notice() {
    try {
        // Verify nonce for security
        check_ajax_referer( 'woocommerce-process_checkout', 'security' );

        // Get and sanitize inputs
        $message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'error';

        // Validate inputs
        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => 'Message is required' ) );
            return;
        }

        // Validate notice type
        $valid_types = array( 'error', 'success', 'notice' );
        if ( ! in_array( $type, $valid_types ) ) {
            $type = 'error';
        }

        // Add WooCommerce notice
        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( $message, $type );
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => 'WooCommerce not available' ) );
        }

    } catch ( Exception $e ) {
        wp_send_json_error( array( 'message' => 'Failed to add notice' ) );
    }
}
add_action( 'wp_ajax_notifal_add_woocommerce_notice', 'notifal_ajax_add_woocommerce_notice' );
add_action( 'wp_ajax_nopriv_notifal_add_woocommerce_notice', 'notifal_ajax_add_woocommerce_notice' );

// AJAX handler for refreshing order details
add_action( 'wp_ajax_notifal_refresh_order_details', 'notifal_ajax_refresh_order_details' );
add_action( 'wp_ajax_nopriv_notifal_refresh_order_details', 'notifal_ajax_refresh_order_details' );

/**
 * AJAX handler to refresh order details HTML
 *
 * @since 1.0.0
 */
function notifal_ajax_refresh_order_details() {
    try {
        // Verify nonce for security
        check_ajax_referer( 'woocommerce-process_checkout', 'security' );

        // Get the order details HTML
        $html = notifal_order_details_shortcode( array() );

        wp_send_json_success( array(
            'html' => $html
        ) );

    } catch ( Exception $e ) {
        wp_send_json_error( array( 'message' => 'Failed to refresh order details' ) );
    }
}

/**
 * Check if phone number has meaningful content beyond country code
 *
 * @param string $phone_number The phone number to check
 * @param string $country_code The country code
 * @return bool True if phone has meaningful content, false if it's just country code or empty
 */
function notifal_has_meaningful_phone_content( $phone_number, $country_code ) {
    if ( empty( $phone_number ) || empty( $country_code ) ) {
        return false;
    }

    // Parse the phone number to separate country code from actual number
    $phone_parts = notifal_parse_phone_number( $phone_number );

    // If phone has a country code and the remaining number part is empty or just whitespace
    if ( ! empty( $phone_parts['country_code'] ) ) {
        $remaining_number = trim( $phone_parts['phone_number'] );
        return ! empty( $remaining_number );
    }

    // If no country code in phone, check if the phone has actual digits
    // Remove any non-numeric characters and check if there's content beyond what might be a country code
    $cleaned = preg_replace( '/\D/', '', $phone_number );
    $country_code_numeric = preg_replace( '/\D/', '', $country_code );

    // If the phone number starts with the country code, check if there's more
    if ( strpos( $cleaned, $country_code_numeric ) === 0 ) {
        $remaining_digits = substr( $cleaned, strlen( $country_code_numeric ) );
        return ! empty( $remaining_digits );
    }

    // If phone doesn't start with country code, consider it meaningful if it has digits
    return strlen( $cleaned ) > 0;
}

/**
 * Validate phone field during checkout
 */
function notifal_validate_checkout_phone_fields() {
    if ( isset( $_POST['billing_phone'] ) && isset( $_POST['billing_country'] ) ) {
        $phone_number = sanitize_text_field( $_POST['billing_phone'] );
        $country_code = sanitize_text_field( $_POST['billing_country'] );

    
        // Validate phone number (only if meaningfully provided, since it's optional)
        if ( ! empty( $phone_number ) && ! empty( $country_code ) ) {
            $phone_country_code = notifal_country_code_to_phone_code( $country_code );
            $has_meaningful_content = notifal_has_meaningful_phone_content( $phone_number, $phone_country_code );

            // Check if the phone field has meaningful content beyond just the country code
            if ( $has_meaningful_content ) {
                // Extract country code from phone number if present, otherwise use country selection
                $phone_parts = notifal_parse_phone_number( $phone_number );


                if ( ! empty( $phone_parts['country_code'] ) ) {
                    // Phone already has country code, validate it
                    $is_valid = notifal_validate_phone_number( $phone_parts['phone_number'], $phone_parts['country_code'] );

                    if ( ! $is_valid ) {
                        wc_add_notice( __( 'Please enter a valid phone number.', 'notifal' ), 'error' );
                    }
                } else {
                    // No country code in phone, use selected country
                    if ( $phone_country_code && ! notifal_validate_phone_number( $phone_number, $phone_country_code ) ) {
                        wc_add_notice( __( 'Please enter a valid phone number for the selected country.', 'notifal' ), 'error' );
                    }
                }
            }
        } 
    }
}

/**
 * Sanitize phone fields during checkout
 *
 * @param array $data Posted checkout data
 * @return array Modified checkout data
 */
function notifal_sanitize_checkout_phone_fields( $data ) {
    if ( isset( $data['billing_phone'] ) ) {
        $data['billing_phone'] = notifal_sanitize_phone_number( $data['billing_phone'] );
    }

    return $data;
}

/**
 * Process integrated phone field when creating order
 *
 * @param WC_Order $order The order object
 */
function notifal_process_phone_fields_on_order( $order ) {
    if ( isset( $_POST['billing_phone'] ) ) {
        $phone_value = notifal_sanitize_phone_number( $_POST['billing_phone'] );

        // Parse the phone number to extract country code and number
        $phone_parts = notifal_parse_phone_number( $phone_value );

        if ( ! empty( $phone_parts['country_code'] ) && ! empty( $phone_parts['phone_number'] ) ) {
            // Phone already has country code, use as-is
            $order->set_billing_phone( $phone_value );

            // Store separate meta for admin use
            $order->update_meta_data( '_billing_phone_country_code', $phone_parts['country_code'] );
            $order->update_meta_data( '_billing_phone_number', $phone_parts['phone_number'] );
        } else {
            // No country code in phone, check if we have billing country
            if ( isset( $_POST['billing_country'] ) ) {
                $billing_country = sanitize_text_field( $_POST['billing_country'] );
                $country_code = notifal_country_code_to_phone_code( $billing_country );

                if ( $country_code ) {
                    // Combine country code with phone number
                    $full_phone = notifal_combine_phone_number( $country_code, $phone_value );
                    $order->set_billing_phone( $full_phone );

                    // Store separate meta for admin use
                    $order->update_meta_data( '_billing_phone_country_code', $country_code );
                    $order->update_meta_data( '_billing_phone_number', $phone_value );
                } else {
                    // Fallback: use phone as-is
                    $order->set_billing_phone( $phone_value );
                }
            } else {
                // Fallback: use phone as-is
                $order->set_billing_phone( $phone_value );
            }
        }
    }
}

// WooCommerce phone field validation and processing hooks
add_action( 'woocommerce_checkout_process', 'notifal_validate_checkout_phone_fields' );
add_action( 'woocommerce_checkout_create_order', 'notifal_process_phone_fields_on_order' );
add_filter( 'woocommerce_checkout_posted_data', 'notifal_sanitize_checkout_phone_fields' );

// Ensure state field is always optional
add_filter( 'woocommerce_default_address_fields', 'notifal_make_state_field_optional', 100 );
add_filter( 'woocommerce_get_country_locale', 'notifal_override_state_locale', 100 );
add_filter( 'woocommerce_checkout_fields', 'notifal_make_checkout_state_optional', 100 );

// Disable WooCommerce default terms validation since we use custom terms text
add_filter( 'woocommerce_checkout_show_terms', '__return_false' );

// Make billing address fields optional for downloadable products
add_filter( 'woocommerce_checkout_fields', 'notifal_make_billing_address_optional', 100 );

/**
 * Make state field always optional in default address fields
 *
 * @param array $fields Default address fields
 * @return array Modified address fields
 */
function notifal_make_state_field_optional( $fields ) {
    if ( isset( $fields['state'] ) ) {
        $fields['state']['required'] = false;
    }
    return $fields;
}

/**
 * Override country locale to make state field optional for all countries
 *
 * @param array $locale Country locale settings
 * @return array Modified locale settings
 */
function notifal_override_state_locale( $locale ) {
    foreach ( $locale as $country_code => &$country_locale ) {
        if ( isset( $country_locale['state'] ) ) {
            $country_locale['state']['required'] = false;
        }
    }
    return $locale;
}

/**
 * Make state field optional in checkout fields
 *
 * @param array $fields Checkout fields
 * @return array Modified checkout fields
 */
function notifal_make_checkout_state_optional( $fields ) {
    if ( isset( $fields['billing']['billing_state'] ) ) {
        $fields['billing']['billing_state']['required'] = false;
        // Remove required validation class
        if ( isset( $fields['billing']['billing_state']['class'] ) && is_array( $fields['billing']['billing_state']['class'] ) ) {
            $fields['billing']['billing_state']['class'] = array_diff( $fields['billing']['billing_state']['class'], array( 'validate-required' ) );
        }
    }
    return $fields;
}

/**
 * Make billing address fields optional for downloadable products
 *
 * @param array $fields Checkout fields
 * @return array Modified checkout fields
 */
function notifal_make_billing_address_optional( $fields ) {
    // Make billing address fields optional since products are downloadable
    $address_fields = array( 'billing_address_1', 'billing_city', 'billing_postcode' );

    foreach ( $address_fields as $field_key ) {
        if ( isset( $fields['billing'][ $field_key ] ) ) {
            $fields['billing'][ $field_key ]['required'] = false;
            // Remove required validation class
            if ( isset( $fields['billing'][ $field_key ]['class'] ) && is_array( $fields['billing'][ $field_key ]['class'] ) ) {
                $fields['billing'][ $field_key ]['class'] = array_diff( $fields['billing'][ $field_key ]['class'], array( 'validate-required' ) );
            }
        }
    }

    return $fields;
}

// WooCommerce geolocation hooks for billing fields
add_filter( 'default_checkout_billing_country', 'notifal_set_default_billing_country', 10, 1 );
add_filter( 'default_checkout_billing_state', 'notifal_set_default_billing_state', 10, 1 );

/**
 * Redirect cart page based on cart contents
 *
 * This function automatically redirects users from the cart page:
 * - If cart has items: redirects to checkout page
 * - If cart is empty: redirects to pricing page
 *
 * @since 1.0.0
 * @return void
 */
function notifal_redirect_cart_page() {
    // Only run on frontend cart page
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ! is_cart() ) {
        return;
    }

    // Ensure WooCommerce is active and properly loaded
    if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
        return;
    }

    // Ensure WooCommerce session and cart are initialized
    if ( ! WC()->session || ! WC()->cart ) {
        return;
    }

    // Check cart status and redirect accordingly
    if ( WC()->cart->is_empty() ) {
        // Cart is empty - redirect to pricing page
        $redirect_url = home_url( '/pricing' );
    } else {
        // Cart has items - redirect to checkout page
        $redirect_url = wc_get_checkout_url();
    }

    // Perform the redirect
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'wp', 'notifal_redirect_cart_page' );

/**
 * Handle empty cart functionality via URL parameter
 *
 * This function allows emptying the WooCommerce cart by adding ?empty_cart=true
 * to any URL. After emptying, it redirects based on cart status:
 * - If cart is empty: redirects to pricing page
 * - If cart has items: redirects to checkout page
 * It includes proper security checks and sanitization.
 *
 * @since 1.0.0
 * @return void
 */
function notifal_handle_empty_cart_url() {
    // Only process on frontend, not in admin or during AJAX/cron
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    // Check if empty_cart parameter is set in the URL
    if ( ! isset( $_GET['empty_cart'] ) ) {
        return;
    }

    // Sanitize and validate the parameter value
    $empty_cart = sanitize_text_field( wp_unslash( $_GET['empty_cart'] ) );

    // Only proceed if the parameter is exactly 'true'
    if ( 'true' !== $empty_cart ) {
        return;
    }

    // Ensure WooCommerce is active and properly loaded
    if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
        return;
    }

    // Ensure WooCommerce session and cart are initialized
    if ( ! WC()->session || ! WC()->cart ) {
        return;
    }

    // Empty the cart
    WC()->cart->empty_cart();

    // Determine redirect URL based on cart status after emptying
    if ( WC()->cart->is_empty() ) {
        // Cart is empty - redirect to pricing page
        $redirect_url = home_url( '/pricing' );
    } else {
        // Cart has items - redirect to checkout page
        $redirect_url = wc_get_checkout_url();
    }

    // Redirect to appropriate page
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'wp_loaded', 'notifal_handle_empty_cart_url' );

/**
 * Automatically complete orders containing downloadable products
 *
 * When an order is paid and contains only downloadable products,
 * automatically change the status from processing to completed
 * since no physical processing/shipping is required.
 *
 * @param int $order_id The order ID
 * @return void
 */
function notifal_auto_complete_downloadable_orders( $order_id ) {
    // Log the function call for debugging
    // Get the order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return;
    }

    // Check if order is already completed
    if ( $order->get_status() === 'completed' ) {
        return;
    }

    // Check if order contains downloadable products
    $has_downloadable = false;
    foreach ( $order->get_items() as $item ) {
        // Get the product from the order item
        $product = $item->get_product();

        // Check if product exists and is downloadable
        if ( $product && $product->is_downloadable() ) {
            $has_downloadable = true;
            break; // No need to check further items
        }
    }

    // If order contains downloadable products, complete it automatically
    if ( $has_downloadable ) {
        // Update order status to completed
        $order->update_status( 'completed', __( 'Order automatically completed - contains downloadable products.', 'notifal' ) );

        // Log the status change
    }
}
add_action( 'woocommerce_payment_complete', 'notifal_auto_complete_downloadable_orders', 10, 1 );



