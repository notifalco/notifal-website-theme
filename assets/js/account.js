/**
 * Notifal WooCommerce My Account - JavaScript Enhancements
 * Handles interactions, transitions, and AJAX functionality
 */

(function($) {
    'use strict';

    /**
     * Account Page Enhancements
     */
    const NotifalAccount = {

        /**
         * Initialize all account enhancements
         */
        init: function() {
            this.bindEvents();
            this.initDownloadButtons();
            this.initFormValidation();
            this.initNavigationEnhancements();
            this.initResponsiveFeatures();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).ready(() => {
                this.handleDownloadButtons();
                this.handleFormSubmissions();
                this.handleNavigationStates();
                this.handleResponsiveInteractions();
            });
        },

        /**
         * Initialize download button functionality
         */
        initDownloadButtons: function() {
            const self = this;

            $('.download-btn').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const product = $btn.data('product');

                self.handleDownload($btn, product);
            });
        },

        /**
         * Handle download button clicks with loading states
         */
        handleDownloadButtons: function() {
            const self = this;

            $(document).on('click', '.download-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const product = $btn.data('product');

                if ($btn.hasClass('downloading')) {
                    return; // Prevent multiple clicks
                }

                self.handleDownload($btn, product);
            });
        },

        /**
         * Process download with security and user feedback
         */
        handleDownload: function($btn, product) {
            const self = this;
            const originalText = $btn.html();

            // Set loading state
            $btn.addClass('downloading')
                .html('<i class="fas fa-spinner fa-spin"></i> Preparing Download...')
                .prop('disabled', true);

            // Simulate preparation time (in real implementation, this would be an AJAX call)
            setTimeout(function() {
                // Create download link
                const downloadUrl = self.getDownloadUrl(product);

                if (downloadUrl) {
                    // Trigger download
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = `notifal-${product}.zip`;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Success feedback
                    $btn.removeClass('downloading')
                        .addClass('success')
                        .html('<i class="fas fa-check"></i> Download Started!')
                        .prop('disabled', false);

                    // Reset after 3 seconds
                    setTimeout(function() {
                        $btn.removeClass('success')
                            .html(originalText)
                            .prop('disabled', false);
                    }, 3000);

                    // Track download event
                    self.trackDownload(product);

                } else {
                    // Error handling
                    $btn.removeClass('downloading')
                        .addClass('error')
                        .html('<i class="fas fa-exclamation-triangle"></i> Download Failed')
                        .prop('disabled', false);

                    // Reset after 3 seconds
                    setTimeout(function() {
                        $btn.removeClass('error')
                            .html(originalText)
                            .prop('disabled', false);
                    }, 3000);
                }
            }, 1500);
        },

        /**
         * Get download URL for product
         */
        getDownloadUrl: function(product) {
            // In real implementation, this would make an AJAX call to get the secure download URL
            if (product === 'lite') {
                return 'https://downloads.wordpress.org/plugin/notifal.2.0.0.zip';
            } else if (product === 'pro') {
                // Check if user has license
                if (typeof notifalUserData !== 'undefined' && notifalUserData.hasLicense) {
                    return 'https://notifal.com/download/notifal-pro-2.0.0.zip';
                }
            }
            return false;
        },

        /**
         * Track download events for analytics
         */
        trackDownload: function(product) {
            // Send analytics event
            if (typeof gtag !== 'undefined') {
                gtag('event', 'download', {
                    'event_category': 'engagement',
                    'event_label': `notifal_${product}_download`
                });
            }

            // You could also send to your own analytics endpoint
            $.ajax({
                url: notifalAccountAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'notifal_track_download',
                    product: product,
                    nonce: notifalAccountAjax.nonce
                }
            });
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            this.validateAccountForm();
            this.validatePasswordFields();
        },

        /**
         * Validate account form fields
         */
        validateAccountForm: function() {
            const $form = $('.notifal-account-form');

            $form.on('submit', function(e) {
                const errors = [];
                let isValid = true;

                // Clear previous errors
                $form.find('.field-error').remove();
                $form.find('.form-group.error').removeClass('error');

                // Validate required fields
                $form.find('[required]').each(function() {
                    const $field = $(this);
                    if (!$field.val().trim()) {
                        errors.push({
                            field: $field.attr('name'),
                            message: $field.attr('data-error') || 'This field is required.'
                        });
                        isValid = false;
                    }
                });

                // Validate email format
                const $email = $form.find('[name="account_email"]');
                if ($email.length && $email.val()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test($email.val())) {
                        errors.push({
                            field: 'account_email',
                            message: 'Please enter a valid email address.'
                        });
                        isValid = false;
                    }
                }

                // Display errors
                errors.forEach(function(error) {
                    const $field = $form.find(`[name="${error.field}"]`);
                    const $fieldGroup = $field.closest('.form-group');

                    $fieldGroup.addClass('error');
                    $fieldGroup.append(`<div class="field-error">${error.message}</div>`);
                });

                if (!isValid) {
                    e.preventDefault();

                    // Scroll to first error
                    const $firstError = $form.find('.field-error').first();
                    if ($firstError.length) {
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 500);
                    }
                }
            });
        },

        /**
         * Validate password fields
         */
        validatePasswordFields: function() {
            const $password1 = $('[name="password_1"]');
            const $password2 = $('[name="password_2"]');

            $password2.on('input', function() {
                const pass1 = $password1.val();
                const pass2 = $(this).val();

                if (pass1 && pass2 && pass1 !== pass2) {
                    $(this).addClass('password-mismatch');
                    if (!$(this).next('.password-error').length) {
                        $(this).after('<div class="password-error">Passwords do not match</div>');
                    }
                } else {
                    $(this).removeClass('password-mismatch');
                    $(this).next('.password-error').remove();
                }
            });
        },


        /**
         * Handle form submissions
         */
        handleFormSubmissions: function() {
            const self = this;

            $('.notifal-account-form').on('submit', function(e) {
                const $form = $(this);
                const $submitBtn = $form.find('[type="submit"]');
                const originalText = $submitBtn.html();

                // Add loading state
                $submitBtn.prop('disabled', true)
                         .html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                // Remove loading state after submission (in case of page reload)
                setTimeout(function() {
                    $submitBtn.prop('disabled', false)
                             .html(originalText);
                }, 3000);
            });
        },

        /**
         * Initialize navigation enhancements
         */
        initNavigationEnhancements: function() {
            this.handleNavigationStates();
            // this.handleMobileNavigation();
        },

        /**
         * Handle navigation active states
         */
        handleNavigationStates: function() {
            // PHP already handles active states correctly, only handle click states
            // Handle navigation clicks
            $('.navigation-menu a').on('click', function() {
                $('.navigation-menu .navigation-item').removeClass('is-active');
                $(this).closest('.navigation-item').addClass('is-active');
            });
        },


        /**
         * Handle responsive features
         */
        initResponsiveFeatures: function() {
            // this.handleMobileNavigation();
            this.handleTableResponsiveness();
        },

        /**
         * Handle mobile navigation menu
         */
        handleMobileNavigation: function() {
            const $nav = $('.notifal-navigation');
            const $menu = $nav.find('.navigation-menu');

            // Create mobile toggle if it doesn't exist
            if (!$nav.find('.mobile-toggle').length) {
                $nav.prepend('<button class="mobile-toggle" aria-label="Toggle navigation"><i class="fas fa-bars"></i></button>');
            }

            const $toggle = $nav.find('.mobile-toggle');

            $toggle.on('click', function() {
                $menu.slideToggle(300);
                $toggle.toggleClass('active');
            });

            // Close menu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.notifal-navigation').length) {
                    $menu.slideUp(300);
                    $toggle.removeClass('active');
                }
            });
        },

        /**
         * Handle table responsiveness
         */
        handleTableResponsiveness: function() {
            // Add data-title attributes for mobile display
            $('.notifal-orders-table tbody td').each(function() {
                const $cell = $(this);
                const headerText = $('.notifal-orders-table thead th').eq($cell.index()).text();
                $cell.attr('data-title', headerText);
            });
        },

        /**
         * Handle responsive interactions
         */
        handleResponsiveInteractions: function() {
            // Handle window resize
            $(window).on('resize', this.debounce(() => {
                this.handleResponsiveLayout();
            }, 250));

            // Initial check
            this.handleResponsiveLayout();
        },

        /**
         * Handle responsive layout adjustments
         */
        handleResponsiveLayout: function() {
            const isMobile = $(window).width() < 768;

            if (isMobile) {
                // Mobile-specific adjustments
                $('.account-layout').addClass('mobile-layout');
            } else {
                // Desktop-specific adjustments
                $('.account-layout').removeClass('mobile-layout');
                $('.navigation-menu').show(); // Ensure menu is visible on desktop
            }
        },

        /**
         * Utility function for debouncing
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        NotifalAccount.init();
    });

    /**
     * Expose to global scope for debugging
     */
    window.NotifalAccount = NotifalAccount;

})(jQuery);

/**
 * ============================================================================
 * AJAX Handler for Account Actions
 * ============================================================================
 */

// AJAX object for WordPress (will be localized in PHP)
// Only create if not already defined by WordPress localization
if (typeof notifalAccountAjax === 'undefined') {
    window.notifalAccountAjax = {
        ajaxurl: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
        nonce: ''
    };
}

// Wrap additional functions in jQuery closure
(function($) {
    'use strict';

    /**
     * Handle AJAX form submissions
     */
    window.notifalHandleAjaxForm = function(formId, action) {
        const $form = $(`#${formId}`);

        $form.on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', action);
            formData.append('nonce', notifalAccountAjax.nonce);

            $.ajax({
                url: notifalAccountAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $form.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Handle success
                        notifalShowMessage('success', response.data.message);
                    } else {
                        // Handle errors
                        notifalShowMessage('error', response.data.message);
                    }
                },
                error: function() {
                    notifalShowMessage('error', 'An error occurred. Please try again.');
                },
                complete: function() {
                    $form.removeClass('loading');
                }
            });
        });
    };

    /**
     * Show notification messages
     */
    window.notifalShowMessage = function(type, message) {
        // Remove existing messages
        $('.notifal-message').remove();

        // Create new message
        const $message = $(`
            <div class="notifal-message ${type}">
                <div class="message-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="message-close" aria-label="Close message">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);

        // Add to page
        $('.content-wrapper').prepend($message);

        // Handle close button
        $message.find('.message-close').on('click', function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    };

    /**
     * ============================================================================
     * Accessibility Enhancements
     * ============================================================================
     */

    /**
     * Handle keyboard navigation
     */
    $(document).on('keydown', '.navigation-menu a', function(e) {
        const $current = $(this);
        const $items = $('.navigation-menu a');
        const currentIndex = $items.index($current);

        switch(e.keyCode) {
            case 38: // Up arrow
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : $items.length - 1;
                $items.eq(prevIndex).focus();
                break;
            case 40: // Down arrow
                e.preventDefault();
                const nextIndex = currentIndex < $items.length - 1 ? currentIndex + 1 : 0;
                $items.eq(nextIndex).focus();
                break;
            case 13: // Enter
            case 32: // Space
                e.preventDefault();
                $current[0].click();
                break;
        }
    });

    /**
     * ============================================================================
     * Performance Optimizations
     * ============================================================================
     */

    /**
     * Lazy load download information
     */
    window.notifalLazyLoadDownloads = function() {
        const $downloadsSection = $('.download-cards-grid');

        if ($downloadsSection.length && !$downloadsSection.hasClass('loaded')) {
            // Use Intersection Observer if available
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            // Load download data
                            notifalLoadDownloadData();
                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe($downloadsSection[0]);
            } else {
                // Fallback for older browsers
                notifalLoadDownloadData();
            }
        }
    };

    /**
     * Load download data via AJAX
     */
    function notifalLoadDownloadData() {
        $.ajax({
            url: notifalAccountAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'notifal_get_download_data',
                nonce: notifalAccountAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update download information
                    $('.download-cards-grid').addClass('loaded');
                    // Update version numbers, etc.
                }
            }
        });
    }

    /**
     * Initialize lazy loading
     */
    $(document).ready(function() {
        notifalLazyLoadDownloads();
    });

})(jQuery);
