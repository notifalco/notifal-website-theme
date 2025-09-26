<?php
/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

<p>🎉 Congratulations — you’ve successfully upgraded to <strong>Notifal Pro</strong>! We’re absolutely thrilled to welcome you to the Pro family. 🚀</p>

<p>
    As a Pro member, you now unlock a whole new level of features designed to help you <strong>boost sales, engage your audience, and build trust</strong> more effectively than ever:
</p>

<ul>
    <li>✨ <strong>Advanced notification customization</strong> — tailor every detail to fit your brand</li>
    <li>🎨 <strong>Exclusive Pro templates</strong> — create stunning, high-converting notifications in seconds</li>
    <li>🤝 <strong>More engagement tools</strong> — connect with your visitors in smarter, more impactful ways</li>
</ul>

<p>
    Ready to dive in? Here’s how to get started:
</p>

<ol>
    <li>⬇️ <strong>Download your Pro version:</strong> <a href="<?php echo esc_url(wc_get_account_endpoint_url('downloads')); ?>">Visit My Downloads</a></li>
    <li>🔧 <strong>Activate your license:</strong> <a href="<?php echo esc_url(wc_get_account_endpoint_url('license-manager')); ?>">Manage Your Licenses</a></li>
    <li>🚀 <strong>Complete the Notifal Pro setup</strong> — follow the onboarding process to configure all features for maximum results.</li>
</ol>

<p>
    💡 Pro Tip: The faster you get set up, the faster you’ll start seeing those engagement and sales numbers rise!
</p>

<p>
    Need assistance? Our support team is here to help! You can:
</p>
<ul>
    <li>📧 Email us at <a href="mailto:support@notifal.com">support@notifal.com</a></li>
    <li>💬 Reply directly to this email</li>
    <li>🎫 Contact us through your <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">account dashboard</a></li>
</ul>

<p>
    Welcome aboard, and here’s to your success with Notifal Pro! 🥂
</p>

<?php
// do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
//do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
//do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );



/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
