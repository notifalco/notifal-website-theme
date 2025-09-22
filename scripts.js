/**
 * Notifal Theme Scripts
 * Common JavaScript functionality for all pages
 */

// Notifal User Authentication Menu JavaScript
(function($) {
	'use strict';

	class NotifalAuthMenu {
		constructor() {
			this.init();
		}

		init() {
			this.bindEvents();
			this.setupKeyboardNavigation();
			this.setupClickOutside();
		}

		bindEvents() {
			$(document).on('click', '.notifal-auth-menu__trigger', this.handleTriggerClick.bind(this));
		}

		handleTriggerClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $trigger = $(e.currentTarget);
			const $menu = $trigger.siblings('.notifal-auth-menu__dropdown');
			const isExpanded = $trigger.attr('aria-expanded') === 'true';

			// Close all other menus first
			this.closeAllMenus();

			if (!isExpanded) {
				this.openMenu($trigger, $menu);
			}
		}

		openMenu($trigger, $menu) {
			$trigger.attr('aria-expanded', 'true');
			$menu.attr('aria-hidden', 'false');


			// Announce to screen readers
			this.announceToScreenReader('User menu opened');
		}

		closeMenu($trigger, $menu) {
			$trigger.attr('aria-expanded', 'false');
			$menu.attr('aria-hidden', 'true');

		

			// Return focus to trigger
			$trigger.focus();

			// Announce to screen readers
			this.announceToScreenReader('User menu closed');
		}

		closeAllMenus() {
			$('.notifal-auth-menu__trigger[aria-expanded="true"]').each((index, trigger) => {
				const $trigger = $(trigger);
				const $menu = $trigger.siblings('.notifal-auth-menu__dropdown');
				this.closeMenu($trigger, $menu);
			});
		}

		setupClickOutside() {
			$(document).on('click', (e) => {
				const $target = $(e.target);
				const $menuContainer = $target.closest('.notifal-auth-menu');

				// If click is outside any auth menu, close all menus
				if ($menuContainer.length === 0) {
					this.closeAllMenus();
				}
			});
		}

		setupKeyboardNavigation() {
			$(document).on('keydown', '.notifal-auth-menu__dropdown .notifal-auth-menu__item', this.handleItemKeydown.bind(this));
			$(document).on('keydown', '.notifal-auth-menu__trigger', this.handleTriggerKeydown.bind(this));
		}

		handleTriggerKeydown(e) {
			const $trigger = $(e.currentTarget);
			const $menu = $trigger.siblings('.notifal-auth-menu__dropdown');
			const isExpanded = $trigger.attr('aria-expanded') === 'true';

			switch (e.key) {
				case 'Enter':
				case ' ':
				case 'ArrowDown':
					e.preventDefault();
					if (!isExpanded) {
						this.openMenu($trigger, $menu);
					} else {
						const $firstItem = $menu.find('.notifal-auth-menu__item').first();
						$firstItem.focus();
					}
					break;

				case 'ArrowUp':
					e.preventDefault();
					if (isExpanded) {
						const $lastItem = $menu.find('.notifal-auth-menu__item').last();
						$lastItem.focus();
					}
					break;

				case 'Escape':
					if (isExpanded) {
						e.preventDefault();
						this.closeMenu($trigger, $menu);
					}
					break;
			}
		}

		handleItemKeydown(e) {
			const $currentItem = $(e.currentTarget);
			const $menu = $currentItem.closest('.notifal-auth-menu__dropdown');
			const $trigger = $menu.siblings('.notifal-auth-menu__trigger');
			const $items = $menu.find('.notifal-auth-menu__item');
			const currentIndex = $items.index($currentItem);

			switch (e.key) {
				case 'ArrowDown':
					e.preventDefault();
					const $nextItem = $items.eq(currentIndex + 1);
					if ($nextItem.length) {
						$nextItem.focus();
					} else {
						$items.first().focus();
					}
					break;

				case 'ArrowUp':
					e.preventDefault();
					const $prevItem = $items.eq(currentIndex - 1);
					if ($prevItem.length) {
						$prevItem.focus();
					} else {
						$items.last().focus();
					}
					break;

				case 'Home':
					e.preventDefault();
					$items.first().focus();
					break;

				case 'End':
					e.preventDefault();
					$items.last().focus();
					break;

				case 'Escape':
					e.preventDefault();
					this.closeMenu($trigger, $menu);
					break;

				case 'Enter':
				case ' ':
					e.preventDefault();
					// Allow default link behavior
					window.location.href = $currentItem.attr('href');
					break;

				case 'Tab':
					// If tabbing out of menu, close it
					setTimeout(() => {
						if (!$menu.find(':focus').length) {
							this.closeMenu($trigger, $menu);
						}
					}, 10);
					break;
			}
		}

		announceToScreenReader(message) {
			// Create a temporary element for screen reader announcements
			const $announcer = $('#notifal-sr-announcer');
			if (!$announcer.length) {
				$('body').append('<div id="notifal-sr-announcer" aria-live="polite" aria-atomic="true" class="sr-only"></div>');
			}

			$('#notifal-sr-announcer').text(message);

			// Clear the message after a short delay
			setTimeout(() => {
				$('#notifal-sr-announcer').text('');
			}, 1000);
		}

		setupResponsiveBehavior() {
			$(window).on('resize', () => {
				// Close menus on mobile when resizing to desktop
				if ($(window).width() > 768) {
					this.closeAllMenus();
				}
			});
		}
	}

	// Screen reader only styles
	const srOnlyStyles = `
		.sr-only {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0, 0, 0, 0);
			white-space: nowrap;
			border: 0;
		}
	`;

	// Add screen reader styles if not already present
	if (!$('#notifal-auth-sr-styles').length) {
		$('head').append(`<style id="notifal-auth-sr-styles">${srOnlyStyles}</style>`);
	}

	// Initialize when document is ready
	$(document).ready(() => {
		new NotifalAuthMenu();
	});

	// Handle dynamic content (if menus are loaded via AJAX)
	$(document).on('notifal_auth_menu_loaded', () => {
		new NotifalAuthMenu();
	});

})(jQuery);

/**
 * Additional common theme scripts can be added here
 */
