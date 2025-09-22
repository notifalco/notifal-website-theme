<?php
/**
 * Notifal Theme User Authentication Menu Shortcode
 *
 * Handles user authentication menu display with avatar and dropdown functionality
 *
 * @package Notifal
 * @since 1.0.0
 */

namespace NotifalTheme;

use WC_Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AuthMenu {

	public function __construct() {
		add_shortcode( 'user_auth_menu', array( $this, 'render_auth_menu' ) );
	}

	/**
	 * Render the authentication menu shortcode
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public function render_auth_menu( $atts = array() ) {
		$atts = shortcode_atts( array(
			'show_avatar' => 'true',
			'avatar_size' => '32',
			'dropdown_position' => 'right'
		), $atts );

		ob_start();

		if ( is_user_logged_in() ) {
			$this->render_logged_in_menu( $atts );
		} else {
			$this->render_login_link();
		}

		return ob_get_clean();
	}

	/**
	 * Render login link for non-logged-in users
	 */
	private function render_login_link() {
		$login_url = home_url( '/login' );
		?>
		<div class="notifal-auth-menu notifal-auth-menu--guest">
			<a href="<?php echo esc_url( $login_url ); ?>" class="notifal-auth-menu__login-link">
				<?php esc_html_e( 'Login', 'notifal' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render user menu for logged-in users
	 *
	 * @param array $atts Shortcode attributes
	 */
	private function render_logged_in_menu( $atts ) {
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$display_name = $current_user->display_name;
		$avatar_size = intval( $atts['avatar_size'] );
		$show_avatar = $atts['show_avatar'] === 'true';
		$dropdown_position = esc_attr( $atts['dropdown_position'] );

		// Get user avatar URL
		$avatar_url = $this->get_user_avatar_url( $user_id, $avatar_size );

		// Get current page for active state detection
		$current_url = $this->get_current_url();

		// Define menu items
		$menu_items = $this->get_menu_items();

		?>
		<div class="notifal-auth-menu notifal-auth-menu--user" data-dropdown-position="<?php echo $dropdown_position; ?>">
			<button
				class="notifal-auth-menu__trigger"
				aria-haspopup="true"
				aria-expanded="false"
				aria-label="<?php printf( esc_attr__( 'User menu for %s', 'notifal' ), esc_attr( $display_name ) ); ?>"
			>
				<?php if ( $show_avatar ) : ?>
					<?php if ( $avatar_url ) : ?>
						<img
							src="<?php echo esc_url( $avatar_url ); ?>"
							alt="<?php printf( esc_attr__( 'Avatar for %s', 'notifal' ), esc_attr( $display_name ) ); ?>"
							class="notifal-auth-menu__avatar"
							width="<?php echo $avatar_size; ?>"
							height="<?php echo $avatar_size; ?>"
						/>
					<?php else : ?>
						<!-- Fallback avatar when no image is available -->
						<div class="notifal-auth-menu__avatar notifal-auth-menu__avatar--fallback" style="width: <?php echo $avatar_size; ?>px; height: <?php echo $avatar_size; ?>px;">
							<svg width="<?php echo $avatar_size; ?>" height="<?php echo $avatar_size; ?>" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
								<circle cx="16" cy="16" r="16" fill="#e0e0e0"/>
								<circle cx="16" cy="12" r="5" fill="#9e9e9e"/>
								<path d="M6 26c0-5.5 4.5-10 10-10s10 4.5 10 10" fill="#9e9e9e"/>
							</svg>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<span class="notifal-auth-menu__username">
					<?php echo esc_html( $display_name ); ?>
				</span>

				<svg class="notifal-auth-menu__dropdown-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>

			<div class="notifal-auth-menu__dropdown" role="menu" aria-hidden="true">
				<?php foreach ( $menu_items as $item ) : ?>
					<?php
					$is_active = $this->is_menu_item_active( $item['url'], $current_url );
					$item_classes = 'notifal-auth-menu__item';
					if ( $is_active ) {
						$item_classes .= ' notifal-auth-menu__item--active';
					}
					?>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						class="<?php echo esc_attr( $item_classes ); ?>"
						role="menuitem"
						aria-current="<?php echo $is_active ? 'page' : 'false'; ?>"
					>
						<?php if ( $item['icon'] ) : ?>
							<span class="notifal-auth-menu__item-icon" aria-hidden="true">
								<?php echo $item['icon']; ?>
							</span>
						<?php endif; ?>

						<span class="notifal-auth-menu__item-text">
							<?php echo esc_html( $item['label'] ); ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get user avatar URL
	 *
	 * @param int $user_id User ID
	 * @param int $size Avatar size
	 * @return string|false Avatar URL or false if no avatar
	 */
	private function get_user_avatar_url( $user_id, $size ) {
		// Check for WooCommerce avatar first
		if ( function_exists( 'get_avatar_url' ) ) {
			$avatar_url = get_avatar_url( $user_id );
			if ( $avatar_url ) {
				return $avatar_url;
			}
		}

		// Check for Google avatar from user meta (for Google OAuth users)
		$google_picture = get_user_meta( $user_id, 'google_picture_url', true );
		if ( $google_picture ) {
			return $google_picture;
		}

		// Check for manually stored Gmail avatar
		$stored_gmail_avatar = get_user_meta( $user_id, 'gmail_profile_picture', true );
		if ( $stored_gmail_avatar ) {
			return $stored_gmail_avatar;
		}

		// Check for WordPress avatar (includes Gravatar) - this is the most reliable for Gmail users
		$avatar_url = get_avatar_url( $user_id, array( 
			'size' => $size,
			'default' => 'mp' // Use mystery person default instead of blank
		) );
		
		// Always return the Gravatar URL (even if it's the default)
		// This ensures Gmail users see their Gravatar if they have one
		if ( $avatar_url ) {
			return $avatar_url;
		}

		return false;
	}

	/**
	 * Get Gmail profile picture for Gmail addresses
	 *
	 * @param string $email Gmail address
	 * @param int $size Image size
	 * @return string|false Avatar URL or false if not available
	 */
	private function get_gmail_profile_picture( $email, $size ) {
		if ( ! strpos( $email, '@gmail.com' ) ) {
			return false;
		}

		// Extract username from Gmail address
		$username = str_replace( '@gmail.com', '', $email );

		// Try Gmail profile picture URL patterns (these may not work without OAuth)
		$gmail_urls = array(
			"https://lh3.googleusercontent.com/a/{$username}=s{$size}",
			"https://www.google.com/s2/photos/profile/{$username}?sz={$size}",
		);

		// Check if any Gmail URL is accessible (with timeout)
		foreach ( $gmail_urls as $url ) {
			if ( $this->is_url_accessible( $url ) ) {
				return $url;
			}
		}

		// Fallback: Try to get from user meta if they have a stored Gmail avatar
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			$stored_gmail_avatar = get_user_meta( $user->ID, 'gmail_profile_picture', true );
			if ( $stored_gmail_avatar ) {
				return $stored_gmail_avatar;
			}
		}

		return false;
	}

	/**
	 * Check if URL is accessible
	 *
	 * @param string $url URL to check
	 * @return bool True if accessible
	 */
	private function is_url_accessible( $url ) {
		// Use WordPress HTTP API to check if URL exists
		$response = wp_remote_head( $url, array(
			'timeout' => 3, // Reduced timeout for better performance
			'redirection' => 5,
			'user-agent' => 'WordPress/Notifal-Theme',
			'headers' => array(
				'Accept' => 'image/*',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		// Check for successful response codes
		return in_array( $status_code, array( 200, 301, 302 ) );
	}

	/**
	 * Update Gmail profile picture for user
	 *
	 * @param int $user_id User ID
	 * @param string $avatar_url Avatar URL to store
	 */
	public static function update_gmail_avatar( $user_id, $avatar_url ) {
		if ( ! empty( $avatar_url ) ) {
			update_user_meta( $user_id, 'gmail_profile_picture', sanitize_url( $avatar_url ) );
		}
	}

	/**
	 * Get menu items for the dropdown
	 *
	 * @return array Menu items configuration
	 */
	private function get_menu_items() {
		$menu_items = array();

		// Licenses
		$menu_items[] = array(
			'label' => __( 'Licenses', 'notifal' ),
			'url'   => $this->get_woocommerce_endpoint_url( 'licenses' ),
			'icon'  => $this->get_icon_svg( 'key' )
		);

		// Orders
		$menu_items[] = array(
			'label' => __( 'Orders', 'notifal' ),
			'url'   => $this->get_woocommerce_endpoint_url( 'orders' ),
			'icon'  => $this->get_icon_svg( 'shopping-bag' )
		);

		// Support
		$menu_items[] = array(
			'label' => __( 'Support', 'notifal' ),
			'url'   => $this->get_woocommerce_endpoint_url( 'support' ),
			'icon'  => $this->get_icon_svg( 'chat' )
		);

		// Downloads
		$menu_items[] = array(
			'label' => __( 'Downloads', 'notifal' ),
			'url'   => $this->get_woocommerce_endpoint_url( 'downloads' ),
			'icon'  => $this->get_icon_svg( 'download' )
		);

		// Settings
		$menu_items[] = array(
			'label' => __( 'Settings', 'notifal' ),
			'url'   => $this->get_woocommerce_endpoint_url( 'edit-account' ),
			'icon'  => $this->get_icon_svg( 'settings' )
		);

		// Log Out
		$menu_items[] = array(
			'label' => __( 'Log Out', 'notifal' ),
			'url'   => $this->get_woocommerce_logout_url(),
			'icon'  => $this->get_icon_svg( 'logout' )
		);

		return $menu_items;
	}

	/**
	 * Get WooCommerce account endpoint URL
	 *
	 * @param string $endpoint Endpoint name
	 * @return string URL
	 */
	private function get_woocommerce_endpoint_url( $endpoint ) {
		if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
			return wc_get_account_endpoint_url( $endpoint );
		}

		// Fallback to my-account page with endpoint
		$account_page_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		return $account_page_url ? add_query_arg( $endpoint, '', $account_page_url ) : home_url( '/my-account/' );
	}

	/**
	 * Get WooCommerce logout URL
	 *
	 * @return string URL
	 */
	private function get_woocommerce_logout_url() {
		// Get WooCommerce my-account page URL
		$account_page_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );

		if ( $account_page_url ) {
			// Create WooCommerce logout URL: my-account/customer-logout/?_wpnonce=<nonce>
			$logout_url = trailingslashit( $account_page_url ) . 'customer-logout/';
			$logout_url = add_query_arg( '_wpnonce', wp_create_nonce( 'customer-logout' ), $logout_url );

			return $logout_url;
		}

		// Fallback to WordPress logout URL
		return wp_logout_url( home_url() );
	}

	/**
	 * Get SVG icon for menu items (uses shared function)
	 *
	 * @param string $icon_name Icon name
	 * @return string SVG markup
	 */
	private function get_icon_svg( $icon_name ) {
		// Use the shared icon function from functions.php
		if ( function_exists( 'notifal_get_icon_svg' ) ) {
			return notifal_get_icon_svg( $icon_name );
		}

		// Fallback if function doesn't exist
		return '';
	}

	/**
	 * Get current URL for active state detection
	 *
	 * @return string Current URL
	 */
	private function get_current_url() {
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Check if menu item is active based on current URL
	 *
	 * @param string $item_url Menu item URL
	 * @param string $current_url Current page URL
	 * @return bool Whether item is active
	 */
	private function is_menu_item_active( $item_url, $current_url ) {
		// Remove protocol and domain for comparison
		$item_path = wp_parse_url( $item_url, PHP_URL_PATH );
		$current_path = wp_parse_url( $current_url, PHP_URL_PATH );

		if ( ! $item_path || ! $current_path ) {
			return false;
		}

		// Check if current path starts with item path
		return strpos( $current_path, $item_path ) === 0;
	}
}
