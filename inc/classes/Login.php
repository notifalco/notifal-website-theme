<?php
/**
 * Notifal Theme Login System
 *
 * Handles user authentication, registration, and one-time code functionality
 *
 * @package Notifal
 * @since 1.0.0
 */

namespace NotifalTheme;

use WC_Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login {

	public static string $login_asset_path = '';
	public static int $resend_time = 120;

	public function __construct() {
		self::$login_asset_path = get_template_directory_uri() . '/assets/login/';

		add_shortcode( 'notifal_login', array( $this, 'add_login_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_scripts' ) );

		// AJAX handlers with proper WordPress security
		add_action( 'wp_ajax_nopriv_notifal_login_user', array( $this, 'notifal_login_user' ) );
		add_action( 'wp_ajax_nopriv_notifal_send_one_time_code', array( $this, 'notifal_send_one_time_code' ) );
		add_action( 'wp_ajax_nopriv_notifal_login_with_otc', array( $this, 'notifal_login_with_otc' ) );
		add_action( 'wp_ajax_nopriv_notifal_register_user', array( $this, 'notifal_register_user' ) );
		add_action( 'wp_ajax_nopriv_notifal_register_with_otc', array( $this, 'notifal_register_with_otc' ) );
		add_action( 'wp_ajax_nopriv_notifal_continue_with_google', array( $this, 'notifal_continue_with_google' ) );

		add_action( 'wp_head', array( $this, 'add_google_auth_meta' ) );
		add_action( 'wp', array( $this, 'init_login' ) );
	}

	public function init_login() {
		if ( is_admin() ) {
			return;
		}

		// Use page slugs instead of IDs
		$login_page_slug = 'login';
		$account_page_slug = 'my-account';

		// Skip redirects when Elementor is in editing mode
		$is_elementor_editing = $this->is_elementor_editing();

		if ( is_page( $login_page_slug ) && is_user_logged_in() && ! $is_elementor_editing ) {
			if ( ! empty( $_SESSION['back_after_login'] ) ) {
				$redirect_url = esc_url( $_SESSION['back_after_login'] );
			} else {
				$redirect_url = home_url( '/' . $account_page_slug );
			}
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( is_page( $account_page_slug ) && ! is_user_logged_in() && ! $is_elementor_editing ) {
			$redirect_url = home_url( '/' . $login_page_slug );
			$_SESSION['back_after_login'] = esc_url( "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Check if Elementor is currently in editing mode
	 *
	 * @return bool
	 */
	private function is_elementor_editing() {
		// Check for Elementor preview/editing parameters
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}

		// Check for Elementor action parameter
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) {
			return true;
		}

		// Check if Elementor editor is active
		if ( isset( $_GET['elementor'] ) ) {
			return true;
		}

		// Check for Elementor AJAX requests
		if ( isset( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'elementor' ) === 0 ) {
			return true;
		}

		// Check if we're in the Elementor editor
		if ( defined( 'ELEMENTOR_VERSION' ) && isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			// Additional check for Elementor editor
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'elementor-template' ) ) {
				return true;
			}
		}

		return false;
	}

	public function add_login_form( $atts = array() ) {
		// Temporarily commented for testing - uncomment when done
		// if ( is_user_logged_in() ) {
		//     return '';
		// }

		// Ensure scripts and styles are enqueued when form is displayed
		$this->enqueue_front_scripts();

		$atts = shortcode_atts( array(
			'register' => 'false',
			'login'    => 'false'
		), $atts );

		ob_start();
		include( get_template_directory() . '/templates/login/login-form.php' );
		return ob_get_clean();
	}

	public function enqueue_front_scripts() {
		wp_enqueue_script( 'notifal-login', self::$login_asset_path . 'login.js', array( 'jquery' ), wp_get_theme()->get( 'Version' ), true );
		wp_enqueue_style( 'notifal-login', self::$login_asset_path . 'login.css', array(), wp_get_theme()->get( 'Version' ), 'all' );

		// Localize script with proper AJAX URL and nonce
		wp_localize_script( 'notifal-login', 'notifal_login_user', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
		wp_localize_script( 'notifal-login', 'notifal_send_one_time_code', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
		wp_localize_script( 'notifal-login', 'notifal_login_with_otc', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
		wp_localize_script( 'notifal-login', 'notifal_register_user', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
		wp_localize_script( 'notifal-login', 'notifal_register_with_otc', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
		wp_localize_script( 'notifal-login', 'notifal_continue_with_google', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'notifal_login_nonce' )
		) );
	}

	public function notifal_login_user() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'notifal' ) );
		}

		$login_data = $this->sanitize_login_data( $_POST['loginData'] ?? array() );
		$user_name  = sanitize_email( $login_data['email'] ?? '' );
		$password   = sanitize_text_field( $login_data['password'] ?? '' );
		$remember   = isset( $login_data['remember'] ) && $login_data['remember'] === 'true';

		$login = $this->notifal_log_in_user( $user_name, $password, $remember );
		echo esc_html( $login );
		wp_die();
	}

	public static function notifal_log_in_user( $user_name, $password, $remember ) {
		$user = get_user_by( 'email', $user_name );
		if ( ! $user ) {
			return esc_html__( 'Invalid email address', 'notifal' );
		}

		$user_name = $user->user_login;

		$credential = array(
			'user_login'    => sanitize_user( $user_name ),
			'user_password' => $password,
			'remember'      => $remember
		);

		if ( username_exists( $user_name ) ) {
			$login = wp_signon( $credential, false );
			if ( is_wp_error( $login ) ) {
				return esc_html__( 'Failed to login', 'notifal' );
			} else {
				wp_set_auth_cookie( $user->ID, $remember );
				return esc_html__( 'Logged in successfully', 'notifal' );
			}
		} else {
			return esc_html__( 'User is not registered', 'notifal' );
		}
	}

	public function notifal_send_one_time_code() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'notifal' ) );
		}

		if ( self::exceeds_otc() ) {
			echo esc_html__( 'You cannot request again', 'notifal' );
			wp_die();
		}

		$email   = sanitize_email( $_POST['sendCodeData']['email'] ?? '' );
		$counter = intval( $_POST['sendCodeData']['counter'] ?? 0 );

		$user = get_user_by( 'email', $email );
		if ( ! empty( $user ) ) {
			if ( $counter == self::$resend_time ) {
				$email_sent = $this->send_otc( $email );
				if ( $email_sent ) {
					echo esc_html__( 'Code sent successfully', 'notifal' );
				} else {
					echo esc_html__( 'Code could not be sent', 'notifal' );
				}
			} else {
				echo esc_html__( 'Code sent successfully', 'notifal' );
			}
		} else {
			echo esc_html__( 'User does not exist', 'notifal' );
		}
		wp_die();
	}

	public function notifal_login_with_otc() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'notifal' ) );
		}

		$otc_data     = $this->sanitize_otc_data( $_POST['otc_data'] ?? array() );
		$entered_otc  = sanitize_text_field( $otc_data['entered_otc'] ?? '' );
		$remember     = isset( $otc_data['remember'] ) && $otc_data['remember'] === 'true';
		$email        = sanitize_email( $otc_data['email'] ?? '' );

		$session_key = 'notifal_otcs_' . md5( $email . $_SERVER['REMOTE_ADDR'] );
		$valid_codes = get_transient( $session_key ) ?: array();
		if ( in_array( $entered_otc, $valid_codes ) ) {
			$user = get_user_by( 'email', $email );
			$user_id = $user ? $user->ID : 0;
			if ( ! empty( $user ) ) {
				$this->notifal_login_by_id( $user_id, $remember );
				echo esc_html__( 'Logged in successfully', 'notifal' );
			} else {
				echo esc_html__( 'Login failed', 'notifal' );
			}
		} else {
			echo esc_html__( 'Code is not valid', 'notifal' );
		}
		wp_die();
	}

	public function notifal_register_user() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'notifal' ) );
		}

		if ( self::exceeds_otc() ) {
			echo esc_html__( 'You cannot request again', 'notifal' );
			wp_die();
		}

		$register_data = $this->sanitize_register_data( $_POST['register_data'] ?? array() );
		$reg_name      = sanitize_text_field( $register_data['reg_name'] ?? '' );
		$reg_surname   = sanitize_text_field( $register_data['reg_surname'] ?? '' );
		$reg_email     = sanitize_email( $register_data['reg_email'] ?? '' );
		$reg_pass      = sanitize_text_field( $register_data['reg_pass'] ?? '' );
		$counter       = intval( $register_data['counter'] ?? 0 );

		// Validate password strength
		$password_validation = $this->validate_password_strength( $reg_pass );
		if ( ! $password_validation['is_valid'] ) {
			echo esc_html( implode( ' ', $password_validation['errors'] ) );
			wp_die();
		}

		// Generate unique username based on email
		$base_username = sanitize_user( $reg_email );
		$username = $base_username;
		$counter = 1;

		// Ensure username is unique
		while ( username_exists( $username ) ) {
			$username = $base_username . '_' . $counter;
			$counter++;
			// Prevent infinite loop
			if ( $counter > 100 ) {
				$username = $base_username . '_' . time();
				break;
			}
		}

		$user_data = array(
			'ID'           => null,
			'user_pass'    => $reg_pass,
			'user_login'   => $username,
			'user_email'   => $reg_email,
			'first_name'   => $reg_name,
			'last_name'    => $reg_surname,
		);

		if ( email_exists( $reg_email ) ) {
			echo esc_html__( 'User is already registered', 'notifal' );
		} else {
			// Always send the email on registration (whether first time or resend)
			$email_sent = $this->send_otc( $reg_email, $reg_name );
			if ( $email_sent ) {
				$register_session_key = 'notifal_reg_data_' . md5( $_SERVER['REMOTE_ADDR'] );
				set_transient( $register_session_key, $user_data, 900 ); // 15 minutes expiry
				echo esc_html__( 'Code sent successfully', 'notifal' );
			} else {
				echo esc_html__( 'Code could not be sent', 'notifal' );
			}
		}
		wp_die();
	}

	public function notifal_register_with_otc() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'notifal' ) );
		}

		$otc_data    = $this->sanitize_otc_data( $_POST['otc_data'] ?? array() );
		$entered_otc = sanitize_text_field( $otc_data['entered_otc'] ?? '' );

		// First get registration data to get the email for session key
		$register_session_key = 'notifal_reg_data_' . md5( $_SERVER['REMOTE_ADDR'] );
		$register_data = get_transient( $register_session_key ) ?: array();

		// Check if registration data exists and is valid
		if ( empty( $register_data ) || ! isset( $register_data['user_email'] ) || ! isset( $register_data['user_login'] ) ) {
			echo esc_html__( 'Registration session expired. Please register again.', 'notifal' );
			wp_die();
		}

		$email = $register_data['user_email'];

		// Now check OTC with the correct session key (email + IP)
		$session_key = 'notifal_otcs_' . md5( $email . $_SERVER['REMOTE_ADDR'] );
		$valid_codes = get_transient( $session_key ) ?: array();

		if ( in_array( $entered_otc, $valid_codes ) ) {
			$user_register = wp_insert_user( $register_data );

			if ( is_wp_error( $user_register ) ) {
				echo esc_html__( 'Registration failed', 'notifal' );
			} else {
				$user_id_register = $user_register;

				// Verify user was created successfully before attempting login
				$user = get_user_by( 'ID', $user_id_register );
				if ( ! $user ) {
					echo esc_html__( 'Registration failed', 'notifal' );
					wp_die();
				}

				// Log in the user automatically after successful registration
				$login_success = $this->notifal_login_by_id( $user_id_register );

				if ( ! $login_success ) {
					echo esc_html__( 'Registration completed but login failed. Please try logging in manually.', 'notifal' );
					wp_die();
				}

				$update_data = array(
					'first_name'         => $register_data['first_name'] ?? '',
					'last_name'          => $register_data['last_name'] ?? '',
					'billing_first_name' => $register_data['first_name'] ?? '',
					'billing_last_name'  => $register_data['last_name'] ?? '',
					'billing_email'      => $register_data['user_email'] ?? '',
				);

				foreach ( $update_data as $key => $value ) {
					update_user_meta( $user_id_register, $key, sanitize_text_field( $value ) );
				}

				if ( class_exists( 'WC_Emails' ) ) {
					$wc = new WC_Emails();
					$wc->customer_new_account( $user_id_register );
				}

				echo esc_html__( 'Registration completed', 'notifal' );
			}
		} else {
			echo esc_html__( 'Code is not valid', 'notifal' );
		}
		wp_die();
	}

	public function notifal_login_by_id( $id, $remember = false ) {
		if ( empty( $id ) || ! is_numeric( $id ) ) {
			return false;
		}
		
		$user = get_user_by( 'ID', $id );
		if ( ! $user ) {
			return false;
		}
		
		wp_clear_auth_cookie();
		wp_set_current_user( $id );
		wp_set_auth_cookie( $id, $remember );
		
		return true;
	}

	public static function exceeds_otc() {
		$session_key = 'notifal_otcs_' . md5( $_SERVER['REMOTE_ADDR'] );
		$sent_codes = get_transient( $session_key ) ?: array();
		return sizeof( $sent_codes ) > 5;
	}

	public function send_otc( $email, $name = '' ) {
		$validation_code = $this->generate_validation_code();
		$user = get_user_by( 'email', $email );

		if ( empty( $name ) ) {
			$name = $user ? $user->first_name : '';
		}

		// Store temporary code for email sending (not used for validation)

		ob_start();
		$email_heading = esc_html__( 'Notifal - Your One-Time Code', 'notifal' );
		wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
		?>
		<div>
			<p><?php printf( esc_html__( 'Hi %s', 'notifal' ), esc_html( $name ) ); ?></p>
			<p><?php printf( esc_html__( 'Here\'s your one-time code to access the %1$s Notifal %2$s website panel:', 'notifal' ), '<a href="' . esc_url( home_url() ) . '">', '</a>' ); ?></p>
			<p><b style="font-size: 17px"><?php echo esc_html( $validation_code ); ?></b><br><br>
				<?php esc_html_e( 'Enter it on the login page to access your account securely.', 'notifal' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'If you didn\'t request this code, you can safely ignore this email. Rest assured, your account is secure.', 'notifal' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Need help? Contact our support team.', 'notifal' ); ?>
			</p>
			<a href="<?php echo esc_url( home_url() ); ?>"><b>Notifal</b></a>
		</div>
		<?php
		wc_get_template( 'emails/email-footer.php' );
		$message = ob_get_clean();
		$subject = esc_html__( 'Your One-Time Code', 'notifal' );
		$headers = 'Content-Type: text/html\r\n';

		$is_email_sent = WC()->mailer()->send( $email, $subject, $message, $headers );
		if ( $is_email_sent ) {
			// Store codes with both email and IP for validation
			$email_session_key = 'notifal_otcs_' . md5( $email . $_SERVER['REMOTE_ADDR'] );
			$stored_codes = get_transient( $email_session_key ) ?: array();
			$stored_codes[] = $validation_code;
			set_transient( $email_session_key, $stored_codes, 900 ); // 15 minutes expiry
			
			// Also store global count per IP for rate limiting
			$ip_session_key = 'notifal_otcs_' . md5( $_SERVER['REMOTE_ADDR'] );
			$ip_codes = get_transient( $ip_session_key ) ?: array();
			$ip_codes[] = $validation_code;
			set_transient( $ip_session_key, $ip_codes, 900 ); // 15 minutes expiry
			
			return true;
		}
		return false;
	}

	/**
	 * Generate one-time code
	 *
	 * @return string
	 */
	public static function generate_validation_code(): string {
		$characters = '1234567890';
		$characters_length = strlen( $characters );
		$random_number = '';
		for ( $i = 0; $i < 4; $i++ ) {
			$random_number .= $characters[ rand( 0, $characters_length - 1 ) ];
		}
		return $random_number;
	}

	public function add_google_auth_meta() {
		?>
		<script src="https://accounts.google.com/gsi/client" async defer></script>
		<meta name="google-signin-client_id" content="829503977063-phocnh7kavh5hne1mks9b0s28v7ni8ri.apps.googleusercontent.com">
		<?php
	}

	public static function add_continue_google() {
		ob_start();
		?>
		<div id="g_id_onload"
			 data-client_id="829503977063-phocnh7kavh5hne1mks9b0s28v7ni8ri.apps.googleusercontent.com"
			 data-context="signin"
			 data-ux_mode="popup"
			 data-callback="onSignIn"
			 data-auto_prompt="false">
		</div>
		<div class="g_id_signin"
			 data-type="standard"
			 data-size="large"
			 data-theme="outline"
			 data-text="continue_with"
			 data-shape="circle"
			 data-logo_alignment="left">
		</div>
		<a class="login-with-google" style="display: none !important;" href="#" data-action=""><?php esc_html_e( 'Login with Google', 'notifal' ); ?></a>
		<?php
		echo ob_get_clean();
	}

	public function notifal_continue_with_google() {
		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'notifal_login_nonce' ) ) {
			echo esc_html__( 'Security check failed', 'notifal' );
			wp_die();
		}

		$google_data = $this->sanitize_google_data( $_POST['google_data'] ?? array() );
		$id          = sanitize_text_field( $google_data['id'] ?? '' );
		$name        = sanitize_text_field( $google_data['name'] ?? '' );
		$surname     = sanitize_text_field( $google_data['surname'] ?? '' );
		$image_url   = sanitize_url( $google_data['image_url'] ?? '' );
		$email       = sanitize_email( $google_data['email'] ?? '' );

		// Validate email
		if ( empty( $email ) || ! is_email( $email ) ) {
			echo esc_html__( 'Invalid email address', 'notifal' );
			wp_die();
		}

		// Check if user already exists by email
		$user = get_user_by( 'email', $email );

		if ( $user ) {
			// User exists - log them in
			$user_id = $user->ID;

			// Log in the user
			$login_result = $this->notifal_login_by_id( $user_id, true );

			if ( $login_result ) {
				// Update Google metadata
				update_user_meta( $user_id, 'google_id', sanitize_text_field( $id ) );
				update_user_meta( $user_id, 'google_picture_url', sanitize_url( $image_url ) );

				echo esc_html__( 'Logged in successfully', 'notifal' );
			} else {
				echo esc_html__( 'Login failed', 'notifal' );
			}
		} else {
			// User doesn't exist - register new user
			$reg_pass = wp_generate_password( 12, true, true );

			// Generate unique username based on email
			$base_username = sanitize_user( $email );
			$username = $base_username;
			$counter = 1;

			// Ensure username is unique
			while ( username_exists( $username ) ) {
				$username = $base_username . '_' . $counter;
				$counter++;
				// Prevent infinite loop
				if ( $counter > 100 ) {
					$username = $base_username . '_' . time();
					break;
				}
			}

			$user_data = array(
				'user_pass'  => $reg_pass,
				'user_login' => $username,
				'user_email' => $email,
				'first_name' => $name,
				'last_name'  => $surname,
				'role'       => 'customer' // Set default role
			);

			$user_registered = wp_insert_user( $user_data );
			if ( is_wp_error( $user_registered ) ) {
				// Check if the error is because user already exists (double check)
				if ( isset( $user_registered->errors['existing_user_email'] ) || isset( $user_registered->errors['existing_user_login'] ) ) {
					// User was created between our check and insert - try to login instead
					$user = get_user_by( 'email', $email );
					if ( $user ) {
						$user_id = $user->ID;
						$this->notifal_login_by_id( $user_id, true );
						update_user_meta( $user_id, 'google_id', sanitize_text_field( $id ) );
						update_user_meta( $user_id, 'google_picture_url', sanitize_url( $image_url ) );
						echo esc_html__( 'Logged in successfully', 'notifal' );
					} else {
						echo esc_html__( 'Login failed', 'notifal' );
					}
				} else {
					// Try to create user with a different username if login conflict
					$alt_username = sanitize_user( $email ) . '_' . time();
					$user_data['user_login'] = $alt_username;
					$user_registered = wp_insert_user( $user_data );

					if ( is_wp_error( $user_registered ) ) {
						echo esc_html__( 'Registration failed', 'notifal' );
					} else {
						$user_id_register = $user_registered;
						$this->notifal_login_by_id( $user_id_register, true );

						$update_data = array(
							'first_name'         => $name,
							'last_name'          => $surname,
							'billing_first_name' => $name,
							'billing_last_name'  => $surname,
							'billing_email'      => $email,
							'google_id'          => $id,
							'google_picture_url' => $image_url,
						);

						foreach ( $update_data as $key => $value ) {
							update_user_meta( $user_id_register, $key, sanitize_text_field( $value ) );
						}

						if ( class_exists( 'WC_Emails' ) ) {
							$wc = new WC_Emails();
							$wc->customer_new_account( $user_id_register );
						}

						echo esc_html__( 'Logged in successfully', 'notifal' );
					}
				}
			} else {
				// Get the newly created user
				$user_id_register = $user_registered;

				// Log in the user immediately after registration
				$this->notifal_login_by_id( $user_id_register, true );

				// Update user metadata
				$update_data = array(
					'first_name'         => $name,
					'last_name'          => $surname,
					'billing_first_name' => $name,
					'billing_last_name'  => $surname,
					'billing_email'      => $email,
					'google_id'          => $id,
					'google_picture_url' => $image_url,
				);

				foreach ( $update_data as $key => $value ) {
					update_user_meta( $user_id_register, $key, sanitize_text_field( $value ) );
				}

				// Send welcome email if WooCommerce is available
				if ( class_exists( 'WC_Emails' ) ) {
					$wc = new WC_Emails();
					$wc->customer_new_account( $user_id_register );
				}

				echo esc_html__( 'Logged in successfully', 'notifal' );
			}
		}
		wp_die();
	}

	/**
	 * Sanitize login data
	 *
	 * @param array $data
	 * @return array
	 */
	private function sanitize_login_data( $data ) {
		return array(
			'email'    => sanitize_email( $data['email'] ?? '' ),
			'password' => sanitize_text_field( $data['password'] ?? '' ),
			'remember' => sanitize_text_field( $data['remember'] ?? '' ),
		);
	}

	/**
	 * Sanitize OTC data
	 *
	 * @param array $data
	 * @return array
	 */
	private function sanitize_otc_data( $data ) {
		return array(
			'entered_otc' => sanitize_text_field( $data['entered_otc'] ?? '' ),
			'remember'    => sanitize_text_field( $data['remember'] ?? '' ),
			'email'       => sanitize_email( $data['email'] ?? '' ),
		);
	}

	/**
	 * Sanitize register data
	 *
	 * @param array $data
	 * @return array
	 */
	private function sanitize_register_data( $data ) {
		return array(
			'reg_name'    => sanitize_text_field( $data['reg_name'] ?? '' ),
			'reg_surname' => sanitize_text_field( $data['reg_surname'] ?? '' ),
			'reg_email'   => sanitize_email( $data['reg_email'] ?? '' ),
			'reg_pass'    => sanitize_text_field( $data['reg_pass'] ?? '' ),
			'counter'     => intval( $data['counter'] ?? 0 ),
		);
	}

	/**
	 * Sanitize Google data
	 *
	 * @param array $data
	 * @return array
	 */
	private function sanitize_google_data( $data ) {
		return array(
			'id'        => sanitize_text_field( $data['id'] ?? '' ),
			'name'      => sanitize_text_field( $data['name'] ?? '' ),
			'surname'   => sanitize_text_field( $data['surname'] ?? '' ),
			'image_url' => sanitize_url( $data['image_url'] ?? '' ),
			'email'     => sanitize_email( $data['email'] ?? '' ),
		);
	}

	/**
	 * Validate password strength
	 *
	 * @param string $password
	 * @return array
	 */
	private function validate_password_strength( $password ) {
		$errors = array();
		$strength = 0;

		// Minimum length
		if ( strlen( $password ) < 8 ) {
			$errors[] = esc_html__( 'Password must be at least 8 characters long.', 'notifal' );
		} else {
			$strength += 1;
		}

		// Check for lowercase letters
		if ( ! preg_match( '/[a-z]/', $password ) ) {
			$errors[] = esc_html__( 'Password must contain at least one lowercase letter.', 'notifal' );
		} else {
			$strength += 1;
		}

		// Check for uppercase letters
		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			$errors[] = esc_html__( 'Password must contain at least one uppercase letter.', 'notifal' );
		} else {
			$strength += 1;
		}

		// Check for numbers
		if ( ! preg_match( '/[0-9]/', $password ) ) {
			$errors[] = esc_html__( 'Password must contain at least one number.', 'notifal' );
		} else {
			$strength += 1;
		}

		// Check for special characters
		if ( ! preg_match( '/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password ) ) {
			$errors[] = esc_html__( 'Password must contain at least one special character.', 'notifal' );
		} else {
			$strength += 1;
		}

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
			'strength' => $strength
		);
	}
}
