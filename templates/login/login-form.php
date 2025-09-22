<?php
/**
 * Template: Login/Register Form
 *
 * @package Notifal
 * @since 1.0.0
 */

use NotifalTheme\Login as Login;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_register = isset( $atts['register'] ) && $atts['register'] === 'true';
$show_login = isset( $atts['login'] ) && $atts['login'] === 'true';

// Check URL parameters for register/login preference
$url_register = isset( $_GET['register'] ) && $_GET['register'] === 'true';
$url_login = isset( $_GET['login'] ) && $_GET['login'] === 'true';

// Use URL parameters if present, otherwise use shortcode attributes
if ( $url_register || $url_login ) {
	$show_register = $url_register;
	$show_login = $url_login;
}

// Default to login form if no specific form is requested
if ( ! $show_register && ! $show_login ) {
	$show_login = true;
}
?>
<div class="notifal-login-container">
	<div class="form-message d-none"></div>
	
	<!-- One-Time Code Section (Hidden by default) -->
	<div id="otc-section" class="d-none">
		<div class="otc-header">
			<h2><?php esc_html_e( 'Enter the code sent to your email', 'notifal' ); ?></h2>
		</div>
		<form class="otc-form">
			<div class="otc-inputs-container">
				<input class="otc-input" type="text" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 1', 'notifal' ); ?>">
				<input class="otc-input" type="text" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 2', 'notifal' ); ?>">
				<input class="otc-input" type="text" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 3', 'notifal' ); ?>">
				<input class="otc-input" type="text" maxlength="1" aria-label="<?php esc_attr_e( 'Digit 4', 'notifal' ); ?>">
			</div>
			<button id="verify-otc-btn" class="notifal-btn notifal-btn-primary" type="button"><?php esc_html_e( 'Verify Code', 'notifal' ); ?></button>
		</form>
		
		<div class="otc-footer">
			<div class="otc-resend-message">
				<p><?php printf( esc_html__( 'Didn\'t receive the code? Wait %s seconds to resend.', 'notifal' ), '<span class="otc-counter">120</span>' ); ?></p>
			</div>
			<div class="otc-resend-action d-none">
				<p><a href="#" class="resend-code-link"><?php esc_html_e( 'Resend Code', 'notifal' ); ?></a></p>
			</div>
			<p><a href="#" class="back-to-form-link"><?php esc_html_e( 'Back to login form', 'notifal' ); ?></a></p>
		</div>
	</div>

	<!-- Login Form -->
	<div id="login-form-section" class="<?php echo $show_login ? '' : 'd-none'; ?>">
		<div class="google-signin-container">
			<?php Login::add_continue_google(); ?>
		</div>
		
		<div class="form-divider">
			<span><?php esc_html_e( 'OR', 'notifal' ); ?></span>
		</div>

		<form id="login-form" class="auth-form">
			<div class="form-group">
				<input type="email" id="login-email" placeholder="<?php esc_attr_e( 'Email...', 'notifal' ); ?>" required />
			</div>
			
			<div class="form-group password-group">
				<input type="password" id="login-password" placeholder="<?php esc_attr_e( 'Password...', 'notifal' ); ?>" required />
				<span class="password-toggle" data-target="login-password">
					<i class="eye-icon">🙈</i>
				</span>
			</div>

			<div class="form-group remember-group">
				<label class="remember-label">
					<?php esc_html_e( 'Remember', 'notifal' ); ?>
					<input type="checkbox" id="remember-me" />
					<span class="toggle-switch">
						<span class="toggle-slider"></span>
					</span>
				</label>
			</div>

			<button type="button" id="login-with-password-btn" class="notifal-btn notifal-btn-primary">
				<?php esc_html_e( 'Sign in with Password', 'notifal' ); ?>
			</button>

			<button type="button" id="send-otc-btn" class="notifal-btn notifal-btn-secondary">
				<?php esc_html_e( 'Send One-Time Code', 'notifal' ); ?>
			</button>

			<div class="form-footer">
				<p><?php esc_html_e( "Haven't got an account?", 'notifal' ); ?> <a href="#" class="switch-to-register"><?php esc_html_e( 'Sign Up', 'notifal' ); ?></a></p>
			</div>
		</form>
	</div>

	<!-- Register Form -->
	<div id="register-form-section" class="<?php echo $show_register ? '' : 'd-none'; ?>">
		<div class="google-signin-container">
			<?php Login::add_continue_google(); ?>
		</div>
		
		<div class="form-divider">
			<span><?php esc_html_e( 'OR', 'notifal' ); ?></span>
		</div>

		<form id="register-form" class="auth-form">
			<div class="form-group">
				<input type="text" id="register-firstname" placeholder="<?php esc_attr_e( 'First Name...', 'notifal' ); ?>" required />
			</div>

			<div class="form-group">
				<input type="text" id="register-lastname" placeholder="<?php esc_attr_e( 'Last Name...', 'notifal' ); ?>" required />
			</div>
			
			<div class="form-group">
				<input type="email" id="register-email" placeholder="<?php esc_attr_e( 'Email...', 'notifal' ); ?>" required />
			</div>
			
			<div class="form-group password-group">
				<input type="password" id="register-password" placeholder="<?php esc_attr_e( 'Password...', 'notifal' ); ?>" required />
				<span class="password-toggle" data-target="register-password">
					<i class="eye-icon">🙈</i>
				</span>
				<div class="password-strength-indicator">
					<div class="password-strength-meter">
						<div class="password-strength-bar" id="password-strength-bar"></div>
					</div>
					<div class="password-strength-text" id="password-strength-text">
						<?php esc_html_e( 'Password strength: Weak', 'notifal' ); ?>
					</div>
				</div>
			</div>

			<div class="form-group password-group">
				<input type="password" id="register-confirm-password" placeholder="<?php esc_attr_e( 'Re-Enter Password...', 'notifal' ); ?>" required />
				<span class="password-toggle" data-target="register-confirm-password">
					<i class="eye-icon">🙈</i>
				</span>
			</div>

			<button type="button" id="register-btn" class="notifal-btn notifal-btn-primary">
				<?php esc_html_e( 'Sign Up', 'notifal' ); ?>
			</button>

			<div class="form-footer">
				<p><?php esc_html_e( "Already have an account?", 'notifal' ); ?> <a href="#" class="switch-to-login"><?php esc_html_e( 'Sign In', 'notifal' ); ?></a></p>
			</div>
		</form>
	</div>
</div>