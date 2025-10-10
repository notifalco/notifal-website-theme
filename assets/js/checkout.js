/**
 * Notifal WooCommerce Checkout - Phone Country Code Integration
 * Automatically adds country code to billing phone field based on selected country
 */

(function($) {
    'use strict';

    /**
     * Checkout Phone Country Code Integration
     */
    const NotifalCheckout = {

        /**
         * Initialize checkout enhancements
         */
        init: function() {
            this.bindEvents();
            this.initCountrySync();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).ready(() => {
                this.handleCountryFieldSync();
                this.handlePhoneFieldFormatting();
            });
        },

        /**
         * Initialize country field synchronization
         */
        initCountrySync: function() {
            // Multiple attempts to ensure sync happens after WooCommerce loads
            setTimeout(() => {
                this.syncBillingCountryToPhone();
            }, 500);

            // Fallback sync after longer delay
            setTimeout(() => {
                this.syncBillingCountryToPhone();
            }, 2000);
        },

        /**
         * Handle country field synchronization
         */
        handleCountryFieldSync: function() {
            const self = this;

            // When billing country changes, update phone field with country code
            $(document).on('change', '#billing_country', function() {
                const selectedCountry = $(this).val();
                if (selectedCountry) {
                    const phoneCode = self.countryCodeToPhoneCode(selectedCountry);
                    if (phoneCode) {
                        self.updatePhoneFieldWithCountryCode(phoneCode);
                    }
                }
            });

            // Also listen for select2 changes (WooCommerce often uses select2)
            $(document).on('select2:select', '#billing_country', function(e) {
                const selectedCountry = e.params.data.id;
                if (selectedCountry) {
                    const phoneCode = self.countryCodeToPhoneCode(selectedCountry);
                    if (phoneCode) {
                        self.updatePhoneFieldWithCountryCode(phoneCode);
                    }
                }
            });
        },

        /**
         * Handle phone field formatting
         */
        handlePhoneFieldFormatting: function() {
            const self = this;

            // Handle phone number input to maintain country code prefix
            $(document).on('input', '#billing_phone', function() {
                const $phoneInput = $(this);
                    const phoneValue = $phoneInput.val();

                // Get current country code
                const $billingCountry = $('#billing_country');
                if ($billingCountry.length && $billingCountry.val()) {
                    const countryCode = $billingCountry.val();
                    const phoneCode = self.countryCodeToPhoneCode(countryCode);

                    if (phoneCode) {
                        // Ensure the phone value starts with the country code
                        if (!phoneValue.startsWith(phoneCode)) {
                            // Remove any existing country code and add the correct one
                            const cleanedNumber = phoneValue.replace(/^\+\d+\s*/, '');
                            $phoneInput.val(phoneCode + ' ' + cleanedNumber);
                        }
                    }
                }
            });
        },

        /**
         * Update phone field with country code prefix
         */
        updatePhoneFieldWithCountryCode: function(phoneCode) {
            const $phoneInput = $('#billing_phone');
            if ($phoneInput.length) {
                const currentValue = $phoneInput.val();

                // If field is empty or doesn't start with current country code, update it
                if (!currentValue || !currentValue.startsWith(phoneCode)) {
                    // Extract the actual phone number (remove any existing country code)
                    const cleanedNumber = currentValue.replace(/^\+\d+\s*/, '').trim();
                    const newValue = phoneCode + (cleanedNumber ? ' ' + cleanedNumber : '');
                    $phoneInput.val(newValue);
                }
            }
        },


        /**
         * Sync billing country to phone field on page load
         */
        syncBillingCountryToPhone: function() {
            const $billingCountry = $('#billing_country');
            if ($billingCountry.length && $billingCountry.val()) {
                const selectedCountry = $billingCountry.val();
                const phoneCode = this.countryCodeToPhoneCode(selectedCountry);

                if (phoneCode) {
                    this.updatePhoneFieldWithCountryCode(phoneCode);
                }
            }
        },

        /**
         * Convert ISO country code to phone country code
         */
        countryCodeToPhoneCode: function(countryCode) {
            const countryToPhoneMap = {
                // North America
                'US': '+1', 'CA': '+1', 'UM': '+1', 'PR': '+1', 'VI': '+1',

                // Europe
                'GB': '+44', 'UK': '+44',
                'FR': '+33',
                'DE': '+49',
                'IT': '+39',
                'ES': '+34',
                'NL': '+31',
                'BE': '+32',
                'CH': '+41',
                'AT': '+43',
                'SE': '+46',
                'NO': '+47',
                'DK': '+45',
                'FI': '+358',
                'PL': '+48',
                'CZ': '+420',
                'SK': '+421',
                'HU': '+36',
                'RO': '+40',
                'BG': '+359',
                'GR': '+30',
                'PT': '+351',
                'IE': '+353',
                'RU': '+7',
                'UA': '+380',
                'BY': '+375',
                'MD': '+373',
                'ME': '+382',
                'RS': '+381',
                'BA': '+387',
                'HR': '+385',
                'SI': '+386',
                'MK': '+389',
                'AL': '+355',
                'XK': '+383',
                'MT': '+356',
                'CY': '+357',
                'LU': '+352',
                'LI': '+423',
                'MC': '+377',
                'SM': '+378',
                'VA': '+39', // Vatican uses Italy's code
                'EE': '+372',
                'LV': '+371',
                'LT': '+370',
                'IS': '+354',

                // Middle East & Asia
                'EG': '+20',
                'ZA': '+27',
                'TR': '+90',
                'IL': '+972',
                'SA': '+966',
                'AE': '+971',
                'QA': '+974',
                'KW': '+965',
                'BH': '+973',
                'OM': '+968',
                'JO': '+962',
                'LB': '+961',
                'IQ': '+964',
                'SY': '+963',
                'YE': '+967',
                'IR': '+98',
                'AF': '+93',
                'PK': '+92',
                'LK': '+94',
                'MM': '+95',
                'NP': '+977',
                'BD': '+880',
                'BT': '+975',
                'MV': '+960',
                'UZ': '+998',
                'KZ': '+7',
                'KG': '+996',
                'TJ': '+992',
                'TM': '+993',
                'AZ': '+994',
                'GE': '+995',
                'AM': '+374',

                // South America
                'BR': '+55',
                'MX': '+52',
                'AR': '+54',
                'CL': '+56',
                'CO': '+57',
                'PE': '+51',
                'VE': '+58',
                'EC': '+593',
                'BO': '+591',
                'PY': '+595',
                'UY': '+598',
                'GY': '+592',
                'SR': '+597',
                'GF': '+594',

                // Oceania
                'AU': '+61',
                'NZ': '+64',
                'FJ': '+679',
                'SB': '+677',
                'VU': '+678',
                'NC': '+687',
                'PF': '+689',
                'CK': '+682',
                'NU': '+683',
                'AS': '+1684',
                'GU': '+1671',
                'MP': '+1670',
                'FM': '+691',
                'MH': '+692',
                'PW': '+680',
                'KI': '+686',
                'TV': '+688',
                'NR': '+674',
                'WS': '+685',

                // Asia Pacific
                'JP': '+81',
                'KR': '+82',
                'CN': '+86',
                'IN': '+91',
                'TH': '+66',
                'MY': '+60',
                'SG': '+65',
                'PH': '+63',
                'ID': '+62',
                'VN': '+84',
                'KH': '+855',
                'LA': '+856',
                'MM': '+95',
                'TL': '+670',
                'BN': '+673',

                // Africa
                'MA': '+212',
                'TN': '+216',
                'DZ': '+213',
                'LY': '+218',
                'EG': '+20',
                'SD': '+249',
                'SS': '+211',
                'ET': '+251',
                'KE': '+254',
                'UG': '+256',
                'TZ': '+255',
                'RW': '+250',
                'BI': '+257',
                'CD': '+243',
                'CG': '+242',
                'GA': '+241',
                'CM': '+237',
                'TD': '+235',
                'CF': '+236',
                'GQ': '+240',
                'AO': '+244',
                'MZ': '+258',
                'MG': '+261',
                'MW': '+265',
                'ZM': '+260',
                'ZW': '+263',
                'BW': '+267',
                'SZ': '+268',
                'LS': '+266',
                'NA': '+264',
                'ZA': '+27',
                'BW': '+267',
                'ZW': '+263',
                'MZ': '+258',
                'ZM': '+260',
                'TZ': '+255',
                'KE': '+254',
                'UG': '+256',
                'RW': '+250',
                'BI': '+257',
                'DJ': '+253',
                'SO': '+252',
                'NE': '+227',
                'NG': '+234',
                'BJ': '+229',
                'BF': '+226',
                'CI': '+225',
                'GH': '+233',
                'TG': '+228',
                'SN': '+221',
                'GM': '+220',
                'GN': '+224',
                'SL': '+232',
                'LR': '+231',
                'ML': '+223',
                'TG': '+228',
                'BJ': '+229',
                'CV': '+238',
                'ST': '+239',
                'GQ': '+240',
                'GA': '+241',
                'CG': '+242',
                'CD': '+243',
                'AO': '+244',
                'GW': '+245',
                'IO': '+246',
                'SC': '+248',
                'MU': '+230',
                'RE': '+262',
                'YT': '+262',
                'KM': '+269',
                'ER': '+291',
                'ZW': '+263'
            };

            return countryToPhoneMap[countryCode.toUpperCase()] || '+1';
        }
    };

    /**
     * Initialize when document is ready and WooCommerce checkout is loaded
     */
    $(document).ready(function() {
        // Only initialize on checkout pages
        if ($('body').hasClass('woocommerce-checkout') || $('form.checkout').length) {
            NotifalCheckout.init();
        } else {
            // Also listen for dynamically loaded checkout forms
            $(document).on('updated_checkout', function() {
                if ($('form.checkout').length) {
                    NotifalCheckout.init();
                }
            });
        }
    });

    /**
     * Order Details Shortcode Functionality
     * Handles domain count increment/decrement and coupon toggle
     */
    var NotifalOrderDetails = {
        /**
         * Track if an AJAX request is currently in progress
         * @type {boolean}
         */
        isProcessing: false,
        /**
         * Initialize order details functionality
         */
        init: function() {
            this.setupCouponToggle();
            this.setupDomainCountButtons();
            this.setupCouponApply();
            this.setupCouponRemove();
            this.setupTooltip();
        },

        /**
         * Setup coupon toggle functionality
         * Shows/hides the coupon input form when clicking "Click here"
         */
        setupCouponToggle: function() {
            $(document).off('click.coupon-toggle').on('click.coupon-toggle', '#notifal-show-coupon', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var $form = $('#notifal-coupon-form');
                $form.stop(true, true).slideToggle(300);

                return false;
            });
        },

        /**
         * Setup domain count increment/decrement buttons
         * Features: Optimistic UI updates, loading states, error handling
         */
        setupDomainCountButtons: function() {
            var self = this;

            // Handle plus/minus button clicks
            $(document).on('click', '.notifal-order-details__btn', function(e) {
                e.preventDefault();

                var $button = $(this);
                var action = $button.data('action');
                var cartKey = $button.data('cart-key');
                var $numberDisplay = $button.siblings('.notifal-order-details__domain-number');
                var currentCount = parseInt($numberDisplay.text());
                var $orderDetails = $('.notifal-order-details');

                // Calculate new count
                var newCount = currentCount;
                if (action === 'increase') {
                    newCount = currentCount + 1;
                } else if (action === 'decrease' && currentCount > 1) {
                    newCount = currentCount - 1;
                }

                // Only update if count changed and not already processing
                if (newCount !== currentCount && !self.isProcessing) {
                    // Set processing flag
                    self.isProcessing = true;

                    // Store original count for rollback
                    var originalCount = currentCount;

                    // Optimistic UI update - update immediately
                    $numberDisplay.text(newCount);

                    // Show loading state
                    $orderDetails.addClass('notifal-order-details--loading');

                    // Update domain count via AJAX
                    self.updateDomainCount(cartKey, newCount, function(success, errorMessage) {
                        if (success) {
                            // AJAX successful - trigger WooCommerce updates and refresh total
                            $(document.body).trigger('update_checkout');
                            $(document.body).trigger('wc_update_cart');

                            // Update the total after WooCommerce processes
                            setTimeout(function() {
                                var checkoutNonce = $('input[name="woocommerce-process-checkout-nonce"]').val();
                                $.ajax({
                                    url: wc_checkout_params.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'notifal_get_updated_total',
                                        security: checkoutNonce
                                    },
                                    success: function(response) {
                                        if (response.success && response.data && response.data.total_html) {
                                            $('.notifal-order-details__total-amount').html(response.data.total_html);
                                        }
                                    },
                                    error: function() {
                                        // Total update failed - not critical, WooCommerce will handle it
                                    },
                                    complete: function() {
                                        // Remove loading state and reset processing flag
                                        $orderDetails.removeClass('notifal-order-details--loading');
                                        self.isProcessing = false;
                                    }
                                });
                            }, 1000);
                        } else {
                            // AJAX failed - rollback optimistic update
                            $numberDisplay.text(originalCount);

                            // Add WooCommerce notice for error
                            self.showWooCommerceNotice(errorMessage || 'Failed to update domain count. Please try again.', 'error');

                            // Remove loading state and reset processing flag
                            $orderDetails.removeClass('notifal-order-details--loading');
                            self.isProcessing = false;
                        }
                    });
                }
            });
        },

        /**
         * Update domain count in cart via AJAX
         *
         * @param {string} cartKey - WooCommerce cart item key
         * @param {number} newCount - New domain count
         * @param {function} callback - Callback function(success, errorMessage)
         */
        updateDomainCount: function(cartKey, newCount, callback) {
            // Get the checkout nonce from the form
            var checkoutNonce = $('input[name="woocommerce-process-checkout-nonce"]').val();

            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'notifal_update_domain_count',
                    cart_key: cartKey,
                    domain_count: newCount,
                    security: checkoutNonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(true);
                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        callback(false, errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'Network error. Please check your connection and try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    callback(false, errorMessage);
                }
            });
        },

        /**
         * Add a WooCommerce notice
         *
         * @param {string} message - Notice message to display
         * @param {string} type - Notice type: 'error', 'success', 'notice'
         */
        showWooCommerceNotice: function(message, type) {
            // Make AJAX call to add WooCommerce notice
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'notifal_add_woocommerce_notice',
                    message: message,
                    type: type,
                    security: $('input[name="woocommerce-process-checkout-nonce"]').val()
                },
                success: function() {
                    // Trigger WooCommerce to refresh notices
                    $(document.body).trigger('update_checkout');
                },
                error: function() {
                    // Fallback: show browser alert if AJAX fails
                    alert(message);
                }
            });
        },

        /**
         * Setup coupon apply functionality
         * Applies coupon code via WooCommerce
         */
        setupCouponApply: function() {
            $(document).on('click', '#notifal-apply-coupon', function(e) {
                e.preventDefault();

                var $button = $(this);
                var $input = $('.notifal-order-details__coupon-input');
                var couponCode = $input.val().trim();

                // Validate coupon code input
                if (!couponCode) {
                    // Manually add error to notices area using WooCommerce's structure
                    var errorHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert"><ul class="woocommerce-error" tabindex="-1"><li>Please enter a coupon code.</li></ul></div></div>';
                    $('.notifal-checkout-notices-fullwidth').html(errorHtml);
                    return;
                }

                // Disable button during processing
                $button.prop('disabled', true).text('Applying...');

                // Apply coupon via AJAX using WooCommerce's built-in method
                $.post({
                    url: wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', 'apply_coupon'),
                    data: {
                        coupon_code: couponCode,
                        security: wc_checkout_params.apply_coupon_nonce
                    },
                    success: function(response) {
                        // Re-enable button
                        $button.prop('disabled', false).text('Apply');

                        // Check if response contains error
                        if (response && response.indexOf('woocommerce-error') !== -1) {
                            // Extract error HTML and display using WooCommerce's structure
                            $('.notifal-checkout-notices-fullwidth').html('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert">' + response + '</div></div>');
                        } else {
                            // Success - clear input and show success message
                            $input.val('');
                            var successHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert"><ul class="woocommerce-message" tabindex="-1"><li>Coupon applied successfully!</li></ul></div></div>';
                            $('.notifal-checkout-notices-fullwidth').html(successHtml);

                            // Hide coupon form after successful application
                            $('#notifal-coupon-form').slideUp(300);

                            // Refresh order details to show applied coupon
                            setTimeout(function() {
                                NotifalOrderDetails.refreshOrderDetails();
                            }, 500);
                        }

                        // Trigger checkout update to refresh totals
                        $(document.body).trigger('update_checkout');
                    },
                    error: function() {
                        // Re-enable button on error
                        $button.prop('disabled', false).text('Apply');

                        // Show generic error using WooCommerce's structure
                        var errorHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert"><ul class="woocommerce-error" tabindex="-1"><li>Failed to apply coupon. Please try again.</li></ul></div></div>';
                        $('.notifal-checkout-notices-fullwidth').html(errorHtml);

                        // Trigger checkout update
                        $(document.body).trigger('update_checkout');
                    }
                });
            });
        },

        /**
         * Setup coupon remove functionality
         * Removes applied coupon from cart
         */
        setupCouponRemove: function() {
            $(document).on('click', '.notifal-order-details__coupon-remove', function(e) {
                e.preventDefault();

                var $button = $(this);
                var couponCode = $button.data('coupon');
                var $couponItem = $button.closest('.notifal-order-details__applied-coupon');

                // Disable button during processing
                $button.prop('disabled', true).text('Removing...');

                // Remove coupon via AJAX using WooCommerce's built-in method
                $.post({
                    url: wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', 'remove_coupon'),
                    data: {
                        coupon: couponCode,
                        security: wc_checkout_params.remove_coupon_nonce
                    },
                    success: function(response) {
                        // Re-enable button
                        $button.prop('disabled', false).text('Remove');

                        // Check if response contains error
                        if (response && response.indexOf('woocommerce-error') !== -1) {
                            // Extract error HTML and display using WooCommerce's structure
                            $('.notifal-checkout-notices-fullwidth').html('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert">' + response + '</div></div>');
                        } else {
                            // Success - remove coupon item from DOM and show success message
                            $couponItem.fadeOut(300, function() {
                                $(this).remove();

                                // If no more coupons, hide the applied coupons container and show initial coupon link
                                if ($('.notifal-order-details__applied-coupon').length === 0) {
                                    $('.notifal-order-details__applied-coupons').remove();
                                    $('.notifal-order-details__coupon .notifal-order-details__coupon-text').text('Have a coupon? ');
                                }

                                // Clear coupon input
                                $('.notifal-order-details__coupon-input').val('');

                                var successHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert"><ul class="woocommerce-message" tabindex="-1"><li>Coupon removed successfully!</li></ul></div></div>';
                                $('.notifal-checkout-notices-fullwidth').html(successHtml);
                            });
                        }

                        // Trigger checkout update to refresh totals
                        $(document.body).trigger('update_checkout');
                    },
                    error: function() {
                        // Re-enable button on error
                        $button.prop('disabled', false).text('Remove');

                        // Show generic error using WooCommerce's structure
                        var errorHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><div role="alert"><ul class="woocommerce-error" tabindex="-1"><li>Failed to remove coupon. Please try again.</li></ul></div></div>';
                        $('.notifal-checkout-notices-fullwidth').html(errorHtml);

                        // Trigger checkout update
                        $(document.body).trigger('update_checkout');
                    }
                });
            });
        },

        /**
         * Setup tooltip functionality for domain info icons
         */
        setupTooltip: function() {
            // Create tooltip element if it doesn't exist
            if (!$('#notifal-tooltip').length) {
                $('body').append('<div id="notifal-tooltip" class="notifal-tooltip"></div>');
            }

            var $tooltip = $('#notifal-tooltip');
            var hideTimeout;

            $(document).on('mouseenter', '.notifal-order-details__domain-info-icon', function(e) {
                var $icon = $(this);
                var tooltipText = $icon.data('tooltip');

                if (tooltipText) {
                    // Clear any pending hide timeout
                    clearTimeout(hideTimeout);

                    $tooltip.html(tooltipText).show();

                    // Position tooltip below the icon
                    var iconOffset = $icon.offset();
                    var iconHeight = $icon.outerHeight();
                    var iconWidth = $icon.outerWidth();
                    var tooltipWidth = $tooltip.outerWidth();
                    var tooltipHeight = $tooltip.outerHeight();

                    // Center the tooltip below the icon
                    var left = iconOffset.left + (iconWidth / 2) - (tooltipWidth / 2);
                    var top = iconOffset.top + iconHeight + 5; // 5px below the icon

                    // Ensure tooltip stays within viewport
                    var windowWidth = $(window).width();
                    var windowHeight = $(window).height();
                    var scrollTop = $(window).scrollTop();

                    if (left < 10) left = 10;
                    if (left + tooltipWidth > windowWidth - 10) left = windowWidth - tooltipWidth - 10;
                    if (top + tooltipHeight > scrollTop + windowHeight - 10) {
                        // If tooltip would go off bottom, show it above the icon instead
                        top = iconOffset.top - tooltipHeight - 5;
                    }

                    $tooltip.css({
                        left: left + 'px',
                        top: top + 'px'
                    });
                }
            });

            // Keep tooltip visible when hovering over it
            $(document).on('mouseenter', '#notifal-tooltip', function() {
                clearTimeout(hideTimeout);
            });

            // Hide tooltip when leaving both icon and tooltip
            $(document).on('mouseleave', '.notifal-order-details__domain-info-icon', function() {
                hideTimeout = setTimeout(function() {
                    $tooltip.hide();
                }, 100); // Small delay to allow moving to tooltip
            });

            $(document).on('mouseleave', '#notifal-tooltip', function() {
                hideTimeout = setTimeout(function() {
                    $tooltip.hide();
                }, 100);
            });
        },

        /**
         * Refresh order details section after coupon changes
         */
        refreshOrderDetails: function() {
            var $orderDetails = $('.notifal-order-details');

            if ($orderDetails.length) {
                // Show loading state
                $orderDetails.addClass('notifal-order-details--loading');

                // Get checkout nonce
                var checkoutNonce = $('input[name="woocommerce-process-checkout-nonce"]').val();

                $.ajax({
                    url: wc_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'notifal_refresh_order_details',
                        security: checkoutNonce
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.html) {
                            // Replace the entire order details element
                            $orderDetails.replaceWith(response.data.html);

                            // Re-initialize the functionality for the new content
                            NotifalOrderDetails.init();
                        } else {
                            // Remove loading state if no valid response
                            $orderDetails.removeClass('notifal-order-details--loading');
                        }
                    },
                    error: function() {
                        // Remove loading state on error
                        $orderDetails.removeClass('notifal-order-details--loading');
                    }
                });
            }
        }
    };

    /**
     * Initialize order details when document is ready
     */
    $(document).ready(function() {
        if ($('.notifal-order-details').length) {
            NotifalOrderDetails.init();
        }
    });

    // Also initialize on checkout updates (in case order details is loaded dynamically)
    $(document).on('updated_checkout', function() {
        if ($('.notifal-order-details').length && !$('.notifal-order-details').hasClass('initialized')) {
            NotifalOrderDetails.init();
            $('.notifal-order-details').addClass('initialized');
        }
    });

})(jQuery);