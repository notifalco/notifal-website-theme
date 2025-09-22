<?php
/**
 * License Activated Email Template - Theme Override
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

<?php /* translators: %s: Customer display name */ ?>
<p><?php printf(__('Hi %s,', 'notifal-license-manager'), esc_html($user->display_name)); ?></p>

<p>🎉 Great news! Your <strong>Notifal Pro license</strong> has been successfully activated! We're absolutely thrilled to welcome you to the Pro family. 🚀</p>

<p>
    As a Pro member, you now unlock a whole new level of features designed to help you <strong>boost sales, engage your audience, and build trust</strong> more effectively than ever:
</p>

<ul>
    <li>✨ <strong>Advanced notification customization</strong> — tailor every detail to fit your brand</li>
    <li>🎨 <strong>Exclusive Pro templates</strong> — create stunning, high-converting notifications in seconds</li>
    <li>🤝 <strong>More engagement tools</strong> — connect with your visitors in smarter, more impactful ways</li>
</ul>

<h3><?php _e('License Details:', 'notifal-license-manager'); ?></h3>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">
    <tbody>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('License Key:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><code style="background: #f1f1f1; padding: 4px 8px; border-radius: 3px;"><?php echo esc_html($license_key); ?></code></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Domain:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html($domain); ?></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Status:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><span style="color: #46b450; font-weight: bold;"><?php _e('Active', 'notifal-license-manager'); ?></span></td>
        </tr>
    </tbody>
</table>

<p>
    Ready to dive in? Here's how to get the most out of your Pro license:
</p>

<ol>
    <li>🔧 <strong>Manage your license:</strong> <a href="<?php echo esc_url(wc_get_account_endpoint_url('license-manager')); ?>">Visit License Manager</a></li>
    <li>📚 <strong>Access Pro features:</strong> Your activated license unlocks all premium functionality</li>
    <li>🚀 <strong>Customize notifications:</strong> Start creating high-converting notifications that drive results</li>
</ol>

<h3><?php _e('Important Notes:', 'notifal-license-manager'); ?></h3>
<ul>
    <li><?php _e('You can transfer your license to any domain whenever needed', 'notifal-license-manager'); ?></li>
    <li><?php _e('Transfer is allowed every 5 days to prevent abuse', 'notifal-license-manager'); ?></li>
    <li><?php _e('License period starts from your payment date, not activation date', 'notifal-license-manager'); ?></li>
</ul>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?php echo esc_url(wc_get_account_endpoint_url('license-manager')); ?>"
       style="background-color: #7B2CBF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px;">
        <?php _e('Manage Your Licenses', 'notifal-license-manager'); ?>
    </a>
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
