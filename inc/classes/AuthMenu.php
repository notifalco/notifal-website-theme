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
			'url'   => $this->get_woocommerce_endpoint_url( 'license-manager' ),
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
	 * Get SVG icon for menu items
	 *
	 * @param string $icon_name Icon name
	 * @return string SVG markup
	 */
	private function get_icon_svg( $icon_name ) {
		$icons = array(
			'key' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>',
			'shopping-bag' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-check" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0"/><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>',
			'chat' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left-dots" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M5 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/></svg>',
			'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 0 0-.708.708z"/></svg>',
			'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16"><path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z"/></svg>',
			'logout' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-door-closed" viewBox="0 0 16 16"><path d="M3 2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v13h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3zm1 13h8V2H4z"/><path d="M9 9a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/></svg>'
		);

		return isset( $icons[ $icon_name ] ) ? $icons[ $icon_name ] : '';
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
