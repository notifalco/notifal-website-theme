<?php
/**
 * Agency Transfer Email Template - Theme Override
 *
 * @package NotifalLicenseManager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php _e('Hello,', 'notifal-license-manager'); ?></p>

<p>🎁 Great news! <strong><?php echo esc_html($agency_user->display_name); ?></strong> has transferred a <strong>Notifal Pro license</strong> to you! We're absolutely thrilled to welcome you to the Pro family. 🚀</p>

<p>
    You have received a 1-year Notifal Pro license that you can activate on any domain. This license will unlock a whole new level of features designed to help you <strong>boost sales, engage your audience, and build trust</strong> more effectively than ever.
</p>

<div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <h3 style="margin-top: 0; color: #2d5a2d;"><?php _e('Ready to get started?', 'notifal-license-manager'); ?></h3>
    <p style="margin-bottom: 20px;"><?php _e('Click the button below to accept your license and start using Notifal Pro:', 'notifal-license-manager'); ?></p>
    <a href="<?php echo esc_url(add_query_arg(array('nlm_confirm_transfer' => $confirmation_token), wc_get_account_endpoint_url('license-manager'))); ?>"
       style="background-color: #7B2CBF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px;">
        <?php _e('Accept License & Get Started', 'notifal-license-manager'); ?>
    </a>
</div>

<p>
    As a Pro member, you'll unlock amazing features:
</p>

<ul>
    <li>✨ <strong>Advanced notification customization</strong> — tailor every detail to fit your brand</li>
    <li>🎨 <strong>Exclusive Pro templates</strong> — create stunning, high-converting notifications in seconds</li>
    <li>🤝 <strong>More engagement tools</strong> — connect with your visitors in smarter, more impactful ways</li>
</ul>

<h3><?php _e('Important Information:', 'notifal-license-manager'); ?></h3>
<ul>
    <li><?php _e('This license can only be used once per domain', 'notifal-license-manager'); ?></li>
    <li><?php _e('If a domain already has an active Notifal Pro license, it cannot receive this agency license', 'notifal-license-manager'); ?></li>
    <li><?php _e('The license period starts when you accept this invitation', 'notifal-license-manager'); ?></li>
    <li><?php _e('You can transfer the license to different domains (with 5-day cooldown)', 'notifal-license-manager'); ?></li>
</ul>

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <p style="margin: 0; color: #856404;"><strong><?php _e('Time Limit:', 'notifal-license-manager'); ?></strong> <?php _e('This invitation will expire in 7 days. If you do not accept it within that time, the license will be returned to the agency.', 'notifal-license-manager'); ?></p>
</div>

<p>
    Need assistance? Our support team is here to help! You can:
</p>
<ul>
    <li>📧 Email us at <a href="mailto:support@notifal.com">support@notifal.com</a></li>
    <li>💬 Reply directly to this email</li>
    <li>🎫 Contact the agency that sent you this license</li>
</ul>

<p>
    Welcome aboard, and here's to your success with Notifal Pro! 🥂
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ?? false ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
