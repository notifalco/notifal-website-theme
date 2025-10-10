<?php
/**
 * Custom Terms and Conditions Template for Notifal Theme
 *
 * This template overrides WooCommerce's default checkout/terms.php template
 * to provide custom styling and terms display for the pay order page.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) {
	$terms_page_id = wc_terms_and_conditions_page_id();
	$terms_page    = $terms_page_id ? get_post( $terms_page_id ) : false;

	if ( $terms_page_id && $terms_page ) {
		$terms_content = apply_filters( 'the_content', $terms_page->post_content );
		?>
		<div class="woocommerce-terms-and-conditions-wrapper notifal-terms-wrapper">
			<div class="woocommerce-terms-and-conditions" style="display: none; max-height: 200px; overflow: auto;">
				<?php echo wp_kses_post( $terms_content ); ?>
			</div>

			<p class="form-row validate-required notifal-terms-checkbox">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox notifal-checkbox-label">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox notifal-checkbox-input" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); // WPCS: input var ok, csrf ok. ?> id="terms" />
					<span class="woocommerce-terms-and-conditions-checkbox-text notifal-checkbox-text">
						<?php
						/* translators: %s: Terms and conditions page title */
						printf(
							__( 'I have read and agree to the website %s', 'notifal' ),
							'<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" class="woocommerce-terms-and-conditions-link notifal-terms-link" target="_blank">' . esc_html( $terms_page->post_title ) . '</a>'
						);
						?>
					</span>
					<span class="required notifal-required">*</span>
				</label>
				<input type="hidden" name="terms-field" value="1" />
			</p>
		</div>
		<?php
	}
}
?>
