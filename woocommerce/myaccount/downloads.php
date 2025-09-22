<?php
/**
 * Downloads
 *
 * Shows downloads on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/downloads.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action( 'woocommerce_before_account_downloads', $has_downloads ); ?>

<div class="downloads-content">
    <div class="downloads-header">
        <h2><?php esc_html_e( 'Your Downloads', 'notifal' ); ?></h2>
       
    </div>

    <!-- Notifal Product Downloads -->
    <div class="download-cards-grid">
        <?php
        // Get available download versions
        $download_versions = notifal_get_download_versions();
        ?>

        <!-- Notifal Lite Card -->
        <div class="download-card">
            <div class="card-header">
                <div class="product-icon">
                    <img src="https://notifal.com/wp-content/uploads/2025/06/Notifal-header-logo-mobile.svg" alt="Notifal Logo" style="width: 48px; height: 48px; object-fit: contain;">
                </div>
                <div class="product-info">
                    <h3><?php esc_html_e( 'Notifal Lite', 'notifal' ); ?></h3>
                    <span class="version-badge">
                        <?php esc_html_e( 'Version', 'notifal' ); ?>
                        <?php 
                        $latest_lite = isset( $download_versions['lite']['versions'][0] ) ? $download_versions['lite']['versions'][0]['version'] : '1.3.2';
                        echo esc_html( $latest_lite );
                        ?>
                    </span>
                </div>
            </div>

            <div class="card-content">
                
                <!-- Version Selector -->
                <div class="version-selector">
                    <label for="lite-version-select"><?php esc_html_e( 'Select Version:', 'notifal' ); ?></label>
                    <select id="lite-version-select" class="version-select" data-product="lite">
                        <?php if ( isset( $download_versions['lite']['versions'] ) ) : ?>
                            <?php foreach ( $download_versions['lite']['versions'] as $version ) : ?>
                                <option value="<?php echo esc_attr( $version['version'] ); ?>" data-url="<?php echo esc_url( $version['download_url'] ); ?>">
                                    <?php echo esc_html( $version['version'] ); ?>
                                    <?php if ( $version['is_latest'] ) : ?>
                                        (<?php esc_html_e( 'Latest', 'notifal' ); ?>)
                                    <?php endif; ?>
                                    <?php if ( ! empty( $version['date'] ) ) : ?>
                                        - <?php echo esc_html( date( 'M j, Y', strtotime( $version['date'] ) ) ); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <ul class="features-list">
                        <li><?php esc_html_e( 'Basic notification display rules', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Basic notifciation content filtering', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Basic analytics & reporting', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Only one active notification at a time', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Access to basic tags', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Basic Timing', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Basic Behavior', 'notifal' ); ?></li>
                </ul>
            </div>

            <div class="card-actions">
                <button type="button" class="btn btn-primary download-btn" data-product="lite">
                    <i class="fas fa-download"></i>
                    <?php esc_html_e( 'Download Lite', 'notifal' ); ?>
                </button>
            </div>
        </div>

        <!-- Notifal Pro Card -->
        <?php if ( isset( $download_versions['pro'] ) ) : ?>
        <div class="download-card pro-available">
            <div class="card-header">
                <div class="product-icon" style="position: relative;">
                    <img src="https://notifal.com/wp-content/uploads/2025/06/Notifal-header-logo-mobile.svg" alt="Notifal Logo" style="width: 48px; height: 48px; object-fit: contain;">
                    <span class="pro-badge" style="position: absolute; bottom: -11px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #7B2CBF, #6B24B3); color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Pro</span>
                </div>
                <div class="product-info">
                    <h3><?php esc_html_e( 'Notifal Pro', 'notifal' ); ?></h3>
                    <span class="version-badge">
                        <?php esc_html_e( 'Version', 'notifal' ); ?>
                        <?php 
                        $latest_pro = isset( $download_versions['pro']['versions'][0] ) ? $download_versions['pro']['versions'][0]['version'] : '2.0.0';
                        echo esc_html( $latest_pro );
                        ?>
                    </span>
                </div>
            </div>

            <div class="card-content">
                <p><?php echo esc_html( $download_versions['pro']['description'] ?? 'Premium version with advanced features and priority support.' ); ?></p>
                
                <!-- Version Selector -->
                <div class="version-selector">
                    <label for="pro-version-select"><?php esc_html_e( 'Select Version:', 'notifal' ); ?></label>
                    <select id="pro-version-select" class="version-select" data-product="pro">
                        <?php if ( isset( $download_versions['pro']['versions'] ) ) : ?>
                            <?php foreach ( $download_versions['pro']['versions'] as $version ) : ?>
                                <option value="<?php echo esc_attr( $version['version'] ); ?>" data-url="<?php echo esc_url( $version['download_url'] ); ?>">
                                    <?php echo esc_html( $version['version'] ); ?>
                                    <?php if ( $version['is_latest'] ) : ?>
                                        (<?php esc_html_e( 'Latest', 'notifal' ); ?>)
                                    <?php endif; ?>
                                    <?php if ( ! empty( $version['date'] ) ) : ?>
                                        - <?php echo esc_html( date( 'M j, Y', strtotime( $version['date'] ) ) ); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <ul class="features-list">
                    <?php if ( isset( $download_versions['pro']['features'] ) ) : ?>
                        <?php foreach ( $download_versions['pro']['features'] as $feature ) : ?>
                            <li>✓ <?php echo esc_html( $feature ); ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="card-actions">
                <button type="button" class="btn btn-primary download-btn" data-product="pro">
                    <i class="fas fa-download"></i>
                    <?php esc_html_e( 'Download Pro', 'notifal' ); ?>
                </button>
            </div>
        </div>
        <?php else : ?>
        <!-- Pro Locked Card -->
        <div class="download-card pro-locked">
            <div class="card-header">
                <div class="product-icon" style="position: relative;">
                    <img src="https://notifal.com/wp-content/uploads/2025/06/Notifal-header-logo-mobile.svg" alt="Notifal Logo" style="width: 48px; height: 48px; object-fit: contain;">
                    <span class="pro-badge" style="position: absolute; bottom: -11px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #7B2CBF, #6B24B3); color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Pro</span>
                </div>
                <div class="product-info">
                    <h3><?php esc_html_e( 'Notifal Pro', 'notifal' ); ?></h3>
                    <span class="version-badge"><?php esc_html_e( 'License Required', 'notifal' ); ?></span>
                </div>
            </div>

            <div class="card-content">
                <div class="locked-content">
                    <div class="lock-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p><?php esc_html_e( 'Upgrade to Pro to unlock premium features and priority support.', 'notifal' ); ?></p>
                    <ul class="features-list">
                        <li><?php esc_html_e( 'Advanced notification display rules', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Advance notifciation content filtering', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Advanced analytics & reporting', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Multi Notification Support', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Tags Generator', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Advanced Timing', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Advanced Behavior', 'notifal' ); ?></li>
                        <li><?php esc_html_e( 'Priority support', 'notifal' ); ?></li>
                    </ul>
                </div>
            </div>

            <div class="card-actions">
                <a href="/pricing" class="btn btn-secondary">
                    🚀 <?php esc_html_e( 'Upgrade to Pro', 'notifal' ); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- WooCommerce Downloads (if any) -->
    <?php if ( $has_downloads ) : ?>
        <div class="woocommerce-downloads-section">
            <h3><?php esc_html_e( 'Additional Downloads', 'notifal' ); ?></h3>

            <?php do_action( 'woocommerce_before_available_downloads' ); ?>
            <?php do_action( 'woocommerce_available_downloads', $downloads ); ?>
            <?php do_action( 'woocommerce_after_available_downloads' ); ?>
        </div>
    <?php endif; ?>

    <!-- Download Instructions -->
    <div class="download-instructions">
        <div class="instructions-card">
            <div class="instructions-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="instructions-content">
                <h4><?php esc_html_e( 'Installation Instructions', 'notifal' ); ?></h4>
                <ol>
                    <li><?php esc_html_e( 'Download your preferred version using the buttons above.', 'notifal' ); ?></li>
                    <li><?php esc_html_e( 'Log in to your WordPress admin dashboard.', 'notifal' ); ?></li>
                    <li><?php esc_html_e( 'Go to Plugins > Add New > Upload Plugin.', 'notifal' ); ?></li>
                    <li><?php esc_html_e( 'Choose the downloaded ZIP file and click Install Now.', 'notifal' ); ?></li>
                    <li><?php esc_html_e( 'Activate the plugin and configure your settings.', 'notifal' ); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle download button clicks
    $('.download-btn').on('click', function(e) {
        e.preventDefault();
        
        const product = $(this).data('product');
        const versionSelect = $(`#${product}-version-select`);
        const selectedVersion = versionSelect.val();
        const selectedOption = versionSelect.find('option:selected');
        const downloadUrl = selectedOption.data('url');
        
        if (!selectedVersion) {
            alert('<?php esc_html_e( 'Please select a version to download.', 'notifal' ); ?>');
            return;
        }
        
        // For WordPress.org downloads, redirect directly
        if (downloadUrl && downloadUrl.includes('downloads.wordpress.org')) {
            window.open(downloadUrl, '_blank');
            return;
        }
        
        // For Pro downloads, use our secure endpoint
        if (product === 'pro') {
            // Generate nonce for the specific version
            $.ajax({
                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                type: 'POST',
                data: {
                    action: 'notifal_generate_download_nonce',
                    product: product,
                    version: selectedVersion,
                    security: '<?php echo wp_create_nonce( 'notifal_download_nonce' ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        const downloadLink = `<?php echo home_url( '/my-account/downloads/' ); ?>?notifal_download=1&product=${product}&version=${selectedVersion}&nonce=${response.data.nonce}`;
                        window.location.href = downloadLink;
                    } else {
                        alert(response.data.message || '<?php esc_html_e( 'Error generating download link.', 'notifal' ); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e( 'Error processing download request.', 'notifal' ); ?>');
                }
            });
        }
    });
    
    // Update download button text when version changes
    $('.version-select').on('change', function() {
        const product = $(this).data('product');
        const selectedVersion = $(this).val();
        const downloadBtn = $(`.download-btn[data-product="${product}"]`);
        
        if (selectedVersion) {
            const productName = product === 'pro' ? 'Pro' : 'Lite';
            downloadBtn.html(`<i class="fas fa-download"></i> <?php esc_html_e( 'Download', 'notifal' ); ?> ${productName} ${selectedVersion}`);
        }
    });
    
    // Initialize button text
    $('.version-select').each(function() {
        const product = $(this).data('product');
        const selectedVersion = $(this).val();
        const downloadBtn = $(`.download-btn[data-product="${product}"]`);
        
        if (selectedVersion) {
            const productName = product === 'pro' ? 'Pro' : 'Lite';
            downloadBtn.html(`<i class="fas fa-download"></i> <?php esc_html_e( 'Download', 'notifal' ); ?> ${productName} ${selectedVersion}`);
        }
    });
});
</script>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
