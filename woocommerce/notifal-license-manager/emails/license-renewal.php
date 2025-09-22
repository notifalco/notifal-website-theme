<?php
/**
 * License Renewal Email Template - Theme Override
 *
 * @package NotifalLicenseManager
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);

// Determine urgency styling based on days remaining
$urgency_class = 'info';
$urgency_emoji = '⏰';
$urgency_color = '#007cba';
if ($days_until_expiry <= 3) {
    $urgency_class = 'danger';
    $urgency_emoji = '🚨';
    $urgency_color = '#dc3545';
} elseif ($days_until_expiry <= 7) {
    $urgency_class = 'warning';
    $urgency_emoji = '⚠️';
    $urgency_color = '#ffc107';
}
?>

<?php /* translators: %s: Customer display name */ ?>
<p><?php printf(__('Hi %s,', 'notifal-license-manager'), esc_html($user->display_name)); ?></p>

<p><?php echo $urgency_emoji; ?> Your <strong>Notifal Pro license</strong> <?php printf(_n('will expire in %d day', 'will expire in %d days', $days_until_expiry, 'notifal-license-manager'), $days_until_expiry); ?>! Don't miss out on the amazing features that have been boosting your sales and engagement. 🚀</p>

<p>
    Continue enjoying all the <strong>Pro benefits</strong> that make your notifications stand out:
</p>

<ul>
    <li>✨ <strong>Advanced notification customization</strong> — tailor every detail to fit your brand</li>
    <li>🎨 <strong>Exclusive Pro templates</strong> — create stunning, high-converting notifications in seconds</li>
    <li>🤝 <strong>More engagement tools</strong> — connect with your visitors in smarter, more impactful ways</li>
    <li>📈 <strong>Premium analytics</strong> — track performance and optimize your strategy</li>
</ul>

<h3><?php _e('License Details:', 'notifal-license-manager'); ?></h3>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;" border="1" bordercolor="#eee">
    <tbody>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('License Key:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><code style="background: #f1f1f1; padding: 4px 8px; border-radius: 3px;"><?php echo esc_html($license->license_key); ?></code></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Domain:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html($license->domain); ?></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Expires:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px; color: <?php echo $urgency_color; ?>; font-weight: bold;"><?php echo esc_html(date('M j, Y', strtotime($license->expires_at))); ?></td>
        </tr>
        <tr>
            <th scope="row" style="text-align: left; border: 1px solid #eee; padding: 12px; background: #f8f8f8;"><?php _e('Days Remaining:', 'notifal-license-manager'); ?></th>
            <td style="text-align: left; border: 1px solid #eee; padding: 12px; color: <?php echo $urgency_color; ?>; font-weight: bold; font-size: 18px;"><?php echo esc_html($days_until_expiry); ?> <?php echo _n('day', 'days', $days_until_expiry, 'notifal-license-manager'); ?></td>
        </tr>
    </tbody>
</table>

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <h3 style="margin-top: 0; color: #856404;"><?php _e('Don\'t let your success stop here!', 'notifal-license-manager'); ?></h3>
    <p style="margin-bottom: 20px;"><?php _e('Renew now to continue boosting your sales and engagement without interruption:', 'notifal-license-manager'); ?></p>
    <a href="<?php echo esc_url(nlm_get_renewal_url($license)); ?>"
       style="background-color: #7B2CBF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px; margin-right: 10px;">
        <?php _e('Renew License Now', 'notifal-license-manager'); ?>
    </a>
</div>

<h3><?php _e('Why Renew Your Notifal Pro License?', 'notifal-license-manager'); ?></h3>
<ul>
    <li><?php _e('Continue using all premium Notifal Pro features that drive results', 'notifal-license-manager'); ?></li>
    <li><?php _e('Keep your domain active and all your custom configurations', 'notifal-license-manager'); ?></li>
    <li><?php _e('Maintain your optimized settings that boost engagement', 'notifal-license-manager'); ?></li>
    <li><?php _e('Receive ongoing updates, new features, and priority support', 'notifal-license-manager'); ?></li>
    <li><?php _e('Keep building trust and credibility with your audience', 'notifal-license-manager'); ?></li>
</ul>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?php echo esc_url(wc_get_account_endpoint_url('license-manager')); ?>"
       style="background-color: #f0f0f0; color: #333; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">
        <?php _e('View License Manager', 'notifal-license-manager'); ?>
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
    Thank you for being part of the Notifal Pro family! 🥂
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
