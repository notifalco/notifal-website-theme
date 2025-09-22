<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
$user = get_user_by('login',$user_login);
$new_user = get_userdata( $user->ID );
$user_name = $new_user->first_name;

/* translators: %s: Customer username */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_name ?? $user_login ) ); ?></p>
<?php /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */ ?>
<p>Thank you for registering on our website! 🎉 You’ve taken the first step towards transforming your website with Notifal.</p>
<p>
    Here’s what you can do next:<br>
    1️⃣ <b>Explore Notifal</b> – Dive into all the features that help you boost sales and engagement, build trust, and engage your visitors.<br>
    2️⃣ <b>Download the Plugin</b> – If you haven’t already, <a href="https://wordpress.org/plugins/notifal/">get Notifal Lite here</a> and start setting it up on your website.<br>
    3️⃣ <b>Learn & Grow</b> –  <a href="https://notifal.com/blog/">Check out our blog and tutorials</a>  for tips on how to maximize your website’s potential with FOMO and social proof strategies.
</p>
<p>
    Need assistance? Our support team is just an email away! Reply to this email if you need assistance, and we’ll be happy to help.
</p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
