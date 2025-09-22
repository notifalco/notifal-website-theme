// Google OAuth Helper Functions (must be global for Google to access)
function parseJwt(token) {
	try {
		var base64Url = token.split('.')[1];
		var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
		var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
			return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
		}).join(''));
		return JSON.parse(jsonPayload);
	} catch (error) {
		throw error;
	}
}

function onSignIn(response) {
	try {
		const responsePayload = parseJwt(response.credential);
		
		let login_data = {
			'id': responsePayload.sub,
			'name': responsePayload.given_name,
			'surname': responsePayload.family_name,
			'image_url': responsePayload.picture,
			'email': responsePayload.email,
		}
		
		let list, index;
		list = document.getElementsByClassName('login-with-google');
		
		for (index = 0; index < list.length; ++index) {
			list[index].setAttribute("data-action", JSON.stringify(login_data));
		}
		
		if (list[0]) {
			list[0].click();
		}
	} catch (error) {
		// Silent error handling - could add user-friendly message here if needed
	}
}

jQuery(document).ready(function($) {
	
	// Messages
	const messages = {
		generalError: "Something went wrong, try again.",
		registeredSuccess: "You're all set! Redirecting...",
		loggedInSuccess: "You've successfully logged in! Redirecting...",
		registrationFailed: "Registration failed. Please try again.",
		registrationExpired: "Registration session expired. Please register again.",
		userNotFound: "No user found with these details. Please register first.",
		registrationLoginFailed: "Registration completed but login failed. Please try logging in manually.",
		invalidPassword: "Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.",
		invalidEmail: "Please enter a valid email address.",
		incorrectCredentials: "Incorrect email or password. Try login with one-time code.",
		codeSetSuccess: "The code has been sent successfully! Please check your email inbox.",
		codeNotSent: "The code couldn't be sent. Please try again.",
		invalidCode: "Code is not valid!",
		otcTooShort: "The one-time code must be 4 characters long.",
		tooManyRequests: "You've requested the one-time code too many times. Please try again later.",
		passwordMismatch: "Passwords do not match. Please check.",
		alreadyRegistered: "This email is already registered.",
		googleFailedRegister: "Signing up with Google failed. Try again.",
		googleFailedLogin: "Signing in with Google failed. Try again.",
		nameValidation: "Name must be at least 2 characters and contain only letters and spaces.",
		passwordWeak: "Password strength: Weak",
		passwordFair: "Password strength: Fair",
		passwordGood: "Password strength: Good",
		passwordStrong: "Password strength: Strong"
	};

	let otcCounter = 120;
	let otcInterval;
	let currentEmail = '';
	let otcSource = 'login'; // 'login' or 'register'

	// Form switching with slide animations
	$(document).on('click', '.switch-to-register', function(e) {
		e.preventDefault();
		$('#otc-section').hide();
		$('#login-form-section').slideUp(300, function() {
			$('#register-form-section').removeClass('d-none').css('display', 'none').slideDown(300);
		});
		clearMessage();
	});

	$(document).on('click', '.switch-to-login', function(e) {
		e.preventDefault();
		$('#otc-section').hide();
		$('#register-form-section').slideUp(300, function() {
			$('#login-form-section').removeClass('d-none').css('display', 'none').slideDown(300);
		});
		clearMessage();
	});

	$(document).on('click', '.back-to-form-link', function(e) {
		e.preventDefault();
		$('#otc-section').slideUp(300, function() {
			if (otcSource === 'register') {
				$('#login-form-section').addClass('d-none');
				$('#register-form-section').removeClass('d-none').css('display', 'none').slideDown(300);
			} else {
				$('#register-form-section').addClass('d-none');
				$('#login-form-section').removeClass('d-none').css('display', 'none').slideDown(300);
			}
		});
		clearMessage();
		resetOtcInputs();
		clearInterval(otcInterval);
	});

	// Password toggle
	$(document).on('click', '.password-toggle', function() {
		const targetId = $(this).data('target');
		const input = $('#' + targetId);
		const icon = $(this).find('.eye-icon');

		if (input.attr('type') === 'password') {
			input.attr('type', 'text');
			icon.text('🐵'); // Monkey with opened eyes - seeing
		} else {
			input.attr('type', 'password');
			icon.text('🙈'); // Monkey covering eyes - not seeing
		}
	});

	// Initialize password strength indicator on page load
	updatePasswordStrengthIndicator('');

	// Real-time password strength checking
	$(document).on('input', '#register-password', function() {
		const password = $(this).val();
		updatePasswordStrengthIndicator(password);
	});

	// Login with password
	$(document).on('click', '#login-with-password-btn', function(e) {
		e.preventDefault();
		
		const email = $('#login-email').val().trim();
		const password = $('#login-password').val().trim();
		const remember = $('#remember-me').is(':checked');
		
		clearErrors();
		
		if (!validateEmail(email)) {
			showError('#login-email', messages.invalidEmail);
			return;
		}
		
		if (password.length < 8) {
			showError('#login-password', messages.invalidPassword);
			return;
		}
		
		const $btn = $(this);
		setButtonLoading($btn, true);
		
		$.ajax({
			type: 'POST',
			url: notifal_login_user.ajax_url,
			data: {
				action: 'notifal_login_user',
				loginData: {
					email: email,
					password: password,
					remember: remember
				},
				nonce: notifal_login_user.nonce
			},
			success: function(response) {
				if (response === 'Logged in successfully') {
					showMessage(messages.loggedInSuccess, 'success');
					setTimeout(() => window.location.reload(), 2000);
				} else if (response === 'User is not registered') {
					showMessage(messages.userNotFound + ' <a href="#" class="switch-to-register">Sign up here</a>', 'error');
				} else if (response === 'Failed to login') {
					showMessage(messages.incorrectCredentials, 'error');
				} else {
					showMessage(messages.generalError, 'error');
				}
			},
			error: function() {
				showMessage(messages.generalError, 'error');
			},
			complete: function() {
				setButtonLoading($btn, false);
			}
		});
	});

	// Send one-time code
	$(document).on('click', '#send-otc-btn', function(e) {
		e.preventDefault();
		
		const email = $('#login-email').val().trim();
		
		clearErrors();
		
		if (!validateEmail(email)) {
			showError('#login-email', messages.invalidEmail);
			return;
		}
		
		currentEmail = email;
		const $btn = $(this);
		setButtonLoading($btn, true);
		
		$.ajax({
			type: 'POST',
			url: notifal_send_one_time_code.ajax_url,
			data: {
				action: 'notifal_send_one_time_code',
				sendCodeData: {
					email: email,
					counter: otcCounter
				},
				nonce: notifal_send_one_time_code.nonce
			},
			success: function(response) {
				if (response === 'Code sent successfully') {
					showOtcSection('login');
					showMessage(messages.codeSetSuccess, 'success');
					startOtcCounter();
				} else if (response === 'User does not exist') {
					showMessage(messages.userNotFound + ' <a href="#" class="switch-to-register">Sign up here</a>', 'error');
				} else if (response === 'Code could not be sent') {
					showMessage(messages.codeNotSent, 'error');
				} else if (response === 'You cannot request again') {
					showMessage(messages.tooManyRequests, 'error');
				} else {
					showMessage(messages.generalError, 'error');
				}
			},
			error: function() {
				showMessage(messages.generalError, 'error');
			},
			complete: function() {
				setButtonLoading($btn, false);
			}
		});
	});

	// Register
	$(document).on('click', '#register-btn', function(e) {
		e.preventDefault();
		
		const firstName = $('#register-firstname').val().trim();
		const lastName = $('#register-lastname').val().trim();
		const email = $('#register-email').val().trim();
		const password = $('#register-password').val().trim();
		const confirmPassword = $('#register-confirm-password').val().trim();
		
		clearErrors();
		
		if (!validateName(firstName)) {
			showError('#register-firstname', messages.nameValidation);
			return;
		}
		
		if (!validateName(lastName)) {
			showError('#register-lastname', messages.nameValidation);
			return;
		}
		
		if (!validateEmail(email)) {
			showError('#register-email', messages.invalidEmail);
			return;
		}

		const passwordStrength = validatePasswordStrength(password);
		if (!passwordStrength.isValid) {
			showError('#register-password', messages.invalidPassword);
			return;
		}
		
		if (password !== confirmPassword) {
			showError('#register-confirm-password', messages.passwordMismatch);
			return;
		}
		
		const $btn = $(this);
		setButtonLoading($btn, true);
		
		$.ajax({
			type: 'POST',
			url: notifal_register_user.ajax_url,
			data: {
				action: 'notifal_register_user',
				register_data: {
					reg_name: firstName,
					reg_surname: lastName,
					reg_email: email,
					reg_pass: password,
					counter: otcCounter
				},
				nonce: notifal_register_user.nonce
			},
			success: function(response) {
				if (response === 'Code sent successfully') {
					currentEmail = email;
					showOtcSection('register');
					showMessage(messages.codeSetSuccess, 'success');
					startOtcCounter();
				} else if (response === 'User is already registered') {
					showMessage(messages.alreadyRegistered + ' <a href="#" class="switch-to-login">Sign in here</a>', 'error');
				} else if (response === 'Code could not be sent') {
					showMessage(messages.codeNotSent, 'error');
				} else if (response === 'You cannot request again') {
					showMessage(messages.tooManyRequests, 'error');
				} else {
					showMessage(messages.generalError, 'error');
				}
			},
			error: function() {
				showMessage(messages.generalError, 'error');
			},
			complete: function() {
				setButtonLoading($btn, false);
			}
		});
	});

	// Verify OTC
	$(document).on('click', '#verify-otc-btn', function(e) {
		e.preventDefault();
		
		const code = getOtcCode();
		
		if (code.length !== 4) {
			showMessage(messages.otcTooShort, 'error');
			return;
		}
		
		const $btn = $(this);
		setButtonLoading($btn, true);
		
		// Check if we're in registration mode or login mode based on otcSource
		const isRegistration = otcSource === 'register';
		const actionUrl = isRegistration ? notifal_register_with_otc.ajax_url : notifal_login_with_otc.ajax_url;
		const actionName = isRegistration ? 'notifal_register_with_otc' : 'notifal_login_with_otc';
		const nonce = isRegistration ? notifal_register_with_otc.nonce : notifal_login_with_otc.nonce;
		
		const ajaxData = {
			action: actionName,
			otc_data: {
				entered_otc: code
			},
			nonce: nonce
		};
		
		if (!isRegistration) {
			ajaxData.otc_data.email = currentEmail;
			ajaxData.otc_data.remember = $('#remember-me').is(':checked');
		}
		
		$.ajax({
			type: 'POST',
			url: actionUrl,
			data: ajaxData,
			success: function(response) {
				if (response === 'Logged in successfully' || response === 'Registration completed') {
					const message = isRegistration ? messages.registeredSuccess : messages.loggedInSuccess;
					showMessage(message, 'success');
					setTimeout(() => window.location.reload(), 2000);
				} else if (response === 'Registration failed') {
					showMessage(messages.registrationFailed, 'error');
				} else if (response === 'Registration session expired. Please register again.') {
					showMessage(messages.registrationExpired, 'error');
				} else if (response === 'Registration completed but login failed. Please try logging in manually.') {
					showMessage(messages.registrationLoginFailed, 'error');
				} else if (response === 'Login failed') {
					showMessage(messages.userNotFound, 'error');
				} else if (response === 'Code is not valid') {
					showMessage(messages.invalidCode, 'error');
					resetOtcInputs();
				} else {
					showMessage(messages.generalError, 'error');
				}
			},
			error: function() {
				showMessage(messages.generalError, 'error');
			},
			complete: function() {
				setButtonLoading($btn, false);
			}
		});
	});

	// OTC input handling
	$(document).on('keyup', '.otc-input', function(e) {
		const $this = $(this);
		const value = $this.val();
		
		if (value.length === 1) {
			const $next = $this.next('.otc-input');
			if ($next.length) {
				$next.focus();
			} else {
				// Auto-submit when all fields are filled
				if (getOtcCode().length === 4) {
					$('#verify-otc-btn').click();
				}
			}
		} else if (value.length === 0 && e.keyCode === 8) {
			const $prev = $this.prev('.otc-input');
			if ($prev.length) {
				$prev.focus();
			}
		}
	});

	// Resend code
	$(document).on('click', '.resend-code-link', function(e) {
		e.preventDefault();
		$('#send-otc-btn').click();
	});

	// Google authentication
	let googleLoginInProgress = false;
	
	$(document).on('click', '.login-with-google', function(e) {
		e.preventDefault();
		
		// Prevent multiple simultaneous requests
		if (googleLoginInProgress) {
			return;
		}
		
		const dataAction = $(this).attr('data-action');
		if (!dataAction) return;
		
		let googleData;
		try {
			googleData = JSON.parse(dataAction);
		} catch (error) {
			showMessage(messages.generalError, 'error');
			return;
		}
		
		const $btn = $(this);
		googleLoginInProgress = true;
		setButtonLoading($btn, true);
		
		// Clear any existing messages
		clearMessage();
		
		$.ajax({
			type: 'POST',
			url: notifal_continue_with_google.ajax_url,
			data: {
				action: 'notifal_continue_with_google',
				google_data: googleData,
				nonce: notifal_continue_with_google.nonce
			},
			success: function(response) {
				if (response === 'Logged in successfully') {
					showMessage(messages.loggedInSuccess, 'success');
					setTimeout(() => {
						window.location.href = '/my-account';
					}, 2000);
				} else if (response === 'Registration failed') {
					showMessage(messages.googleFailedRegister, 'error');
				} else if (response === 'Login failed') {
					showMessage(messages.googleFailedLogin, 'error');
				} else if (response === 'Invalid email address') {
					showMessage(messages.invalidEmail, 'error');
				} else if (response === 'Security check failed') {
					showMessage(messages.generalError, 'error');
				} else {
					showMessage(messages.generalError, 'error');
				}
			},
			error: function(xhr, status, error) {
				showMessage(messages.generalError, 'error');
			},
			complete: function() {
				googleLoginInProgress = false;
				setButtonLoading($btn, false);
			}
		});
	});

	// Helper functions
	function validateEmail(email) {
		const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return regex.test(email);
	}

	function validateName(name) {
		const regex = /^[A-Za-z\s]{2,}$/;
		return regex.test(name);
	}

	function validatePasswordStrength(password) {
		let strength = 0;
		const checks = {
			length: password.length >= 8,
			lowercase: /[a-z]/.test(password),
			uppercase: /[A-Z]/.test(password),
			number: /[0-9]/.test(password),
			special: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)
		};

		// Count passed checks
		Object.values(checks).forEach(check => {
			if (check) strength++;
		});

		return {
			strength: strength,
			checks: checks,
			isValid: strength === 5
		};
	}

	function updatePasswordStrengthIndicator(password) {
		const strengthResult = validatePasswordStrength(password);
		const $bar = $('#password-strength-bar');
		const $text = $('#password-strength-text');

		// Check if elements exist (only present on login/registration pages)
		if ($bar.length === 0 || $text.length === 0) {
			return false;
		}

		// Update visual bar
		const percentage = (strengthResult.strength / 5) * 100;

		// Set the width with multiple approaches for better compatibility
		$bar.css('width', percentage + '%');
		$bar[0].style.setProperty('width', percentage + '%', 'important');

		// Remove all existing classes first
		$bar.removeClass('weak fair good strong');
		$text.removeClass('weak fair good strong');

		// Update bar color and text based on strength
		if (strengthResult.strength <= 2) {
			$bar.addClass('weak');
			$text.addClass('weak').text(messages.passwordWeak);
		} else if (strengthResult.strength <= 3) {
			$bar.addClass('fair');
			$text.addClass('fair').text(messages.passwordFair);
		} else if (strengthResult.strength <= 4) {
			$bar.addClass('good');
			$text.addClass('good').text(messages.passwordGood);
		} else if (strengthResult.strength === 5) {
			$bar.addClass('strong');
			$text.addClass('strong').text(messages.passwordStrong);
		}

		return strengthResult.isValid;
	}

	function showError(selector, message) {
		$(selector).addClass('error');
		showMessage(message, 'error');
	}

	function clearErrors() {
		$('.form-group input').removeClass('error');
		clearMessage();
	}

	function showMessage(message, type = 'error') {
		const $messageEl = $('.form-message');
		$messageEl.removeClass('notifal-error notifal-success d-none');

		if (type === 'success') {
			$messageEl.addClass('notifal-success');
		} else {
			$messageEl.addClass('notifal-error');
		}

		$messageEl.html(message).removeClass('d-none');
		
		// Auto-hide after 5 seconds for errors
		if (type === 'error') {
			setTimeout(() => {
				$messageEl.addClass('d-none');
			}, 5000);
		}
	}

	function clearMessage() {
		$('.form-message').addClass('d-none');
	}

	function setButtonLoading($btn, loading) {
		if (loading) {
			$btn.addClass('loading').prop('disabled', true);
		} else {
			$btn.removeClass('loading').prop('disabled', false);
		}
	}

	function showOtcSection(source = 'login') {
		otcSource = source;
		$('#login-form-section').addClass('d-none');
		$('#register-form-section').addClass('d-none');
		$('#otc-section').removeClass('d-none').show();

		// Update footer text based on source
		const footerText = source === 'register' ? 'Back to registration form' : 'Back to login form';
		$('.back-to-form-link').text(footerText);

		setTimeout(function() {
			$('.otc-input:first').focus();
		}, 100);
	}

	function getOtcCode() {
		let code = '';
		$('.otc-input').each(function() {
			code += $(this).val();
		});
		return code;
	}

	function resetOtcInputs() {
		$('.otc-input').val('');
		$('.otc-input:first').focus();
	}

	function startOtcCounter() {
		otcCounter = 120;
		$('.otc-resend-message').removeClass('d-none');
		$('.otc-resend-action').addClass('d-none');
		
		otcInterval = setInterval(function() {
			otcCounter--;
			$('.otc-counter').text(otcCounter);
			
			if (otcCounter <= 0) {
				clearInterval(otcInterval);
				$('.otc-resend-message').addClass('d-none');
				$('.otc-resend-action').removeClass('d-none');
			}
		}, 1000);
	}

	// Initialize Google Sign-In when the page loads
	function initializeGoogleSignIn() {
		if (typeof google !== 'undefined' && google.accounts) {
			try {
				google.accounts.id.initialize({
					client_id: '829503977063-phocnh7kavh5hne1mks9b0s28v7ni8ri.apps.googleusercontent.com',
					callback: onSignIn,
					auto_select: false,
					cancel_on_tap_outside: true
				});
				
				// Render the Google Sign-In button
				const signInButtons = document.querySelectorAll('.g_id_signin');
				
				signInButtons.forEach(function(button) {
					// Clear any existing content first
					button.innerHTML = '';
					google.accounts.id.renderButton(button, {
						type: 'standard',
						size: 'large',
						theme: 'outline',
						text: 'continue_with',
						shape: 'rectangular',
						logo_alignment: 'left'
					});
				});
			} catch (e) {
				// Silent error handling
			}
		}
	}

	// Try to initialize Google Sign-In multiple times
	var googleInitAttempts = 0;
	var maxGoogleInitAttempts = 10;
	
	function tryInitializeGoogle() {
		googleInitAttempts++;
		
		if (typeof google !== 'undefined' && google.accounts) {
			initializeGoogleSignIn();
		} else if (googleInitAttempts < maxGoogleInitAttempts) {
			setTimeout(tryInitializeGoogle, 500);
		}
	}

	// Start trying to initialize when document is ready
	$(document).ready(function() {
		setTimeout(tryInitializeGoogle, 100);
	});

	// Also try when window loads
	$(window).on('load', function() {
		setTimeout(tryInitializeGoogle, 500);
	});
});
