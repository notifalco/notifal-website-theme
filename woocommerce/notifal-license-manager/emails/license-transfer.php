<?php
/**
 * License Transfer Email Template - Theme Override
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

<p>🔄 Your <strong>Notifal Pro license</strong> has been successfully transferred to a new domain! Your Pro features are now active on your new site. 🚀</p>

<h3><?php _e('Transfer Details:', 'notifal-license-manager'); ?></h3>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">
    <tbody>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('License Key:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><code style="background: #f1f1f1; padding: 4px 8px; border-radius: 3px;"><?php echo esc_html($license_key); ?></code></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Old Domain:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px; color: #999;"><?php echo esc_html($old_domain); ?></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('New Domain:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px; color: #46b450; font-weight: bold;"><?php echo esc_html($new_domain); ?></td>
        </tr>
    </tbody>
</table>

<p>
    Your license is now active on the new domain and you can continue using all <strong>Notifal Pro features</strong>:
</p>

<ul>
    <li>✨ <strong>Advanced notification customization</strong> — tailor every detail to fit your brand</li>
    <li>🎨 <strong>Exclusive Pro templates</strong> — create stunning, high-converting notifications in seconds</li>
    <li>🤝 <strong>More engagement tools</strong> — connect with your visitors in smarter, more impactful ways</li>
</ul>

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3 style="margin-top: 0; color: #856404;"><?php _e('Security Notice:', 'notifal-license-manager'); ?></h3>
    <p style="margin-bottom: 0; color: #856404;"><?php _e('If you did not initiate this transfer, please contact our support team immediately.', 'notifal-license-manager'); ?></p>
</div>

<h3><?php _e('Important Reminders:', 'notifal-license-manager'); ?></h3>
<ul>
    <li><?php _e('You can transfer your license every 5 days to prevent abuse', 'notifal-license-manager'); ?></li>
    <li><?php _e('Each transfer resets the 5-day cooldown period', 'notifal-license-manager'); ?></li>
    <li><?php _e('Make sure your new domain is ready to use Notifal Pro before transferring', 'notifal-license-manager'); ?></li>
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
    Here's to your continued success with Notifal Pro! 🥂
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
