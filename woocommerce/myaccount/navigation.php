<?php
/**
 * My Account navigation - Custom Notifal Design
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Notifal\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );

// Get standard WooCommerce menu items from all plugins
$woocommerce_items = apply_filters( 'woocommerce_account_menu_items', array(
    'dashboard'       => __( 'Dashboard', 'woocommerce' ),
    'orders'          => __( 'Orders', 'woocommerce' ),
    'downloads'       => __( 'Downloads', 'woocommerce' ),
    'edit-account'    => __( 'Account details', 'woocommerce' ),
    'customer-logout' => __( 'Logout', 'woocommerce' ),
) );

// Convert WooCommerce items to Notifal format
$nav_items = array();
foreach ( $woocommerce_items as $endpoint => $label ) {
    // Skip logout as it's handled separately
    if ( $endpoint === 'customer-logout' ) {
        continue;
    }

    // Map endpoints to proper icon names
    $icon_mapping = array(
        'dashboard' => 'dashboard',
        'orders' => 'shopping-bag',
        'downloads' => 'download',
        'edit-account' => 'settings',
        'license-manager' => 'key'
    );

    $icon_name = isset( $icon_mapping[$endpoint] ) ? $icon_mapping[$endpoint] : 'circle';

    // Map endpoints to Notifal format
    $nav_items[$endpoint] = array(
        'label' => $label,
        'icon' => function_exists( 'notifal_get_icon_svg' ) ? notifal_get_icon_svg( $icon_name ) : '',
        'url' => wc_get_account_endpoint_url( $endpoint )
    );
}

// Apply Notifal theme customizations
$nav_items = apply_filters( 'notifal_account_navigation_items', $nav_items );

$current_endpoint = WC()->query->get_current_endpoint();
?>

<nav class="woocommerce-my-account__navigation notifal-navigation" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
    <ul class="navigation-menu">
        <?php foreach ( $nav_items as $endpoint => $item ) : ?>
            <?php
            // Determine if this menu item should be active
            if ( $endpoint === 'dashboard' ) {
                // Dashboard is active only when no endpoint is set (empty string)
                $is_active = ( $current_endpoint === '' );
            } else {
                // All other endpoints match exactly
                $is_active = ( $current_endpoint === $endpoint );
            }
            
            $classes = array( 'navigation-item' );
            if ( $is_active ) {
                $classes[] = 'is-active';
            }
            if ( $endpoint === 'edit-account' ) {
                $classes[] = 'settings-item';
            }
            ?>
            <li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                <a href="<?php echo esc_url( $item['url'] ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                    <span class="navigation-icon" aria-hidden="true">
                        <?php echo $item['icon']; ?>
                    </span>
                    <span><?php echo esc_html( $item['label'] ); ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <!-- Logout Item -->
        <li class="navigation-item logout-item">
            <a href="<?php echo esc_url( wc_logout_url() ); ?>">
                <span class="navigation-icon" aria-hidden="true">
                    <?php echo function_exists( 'notifal_get_icon_svg' ) ? notifal_get_icon_svg( 'logout' ) : ''; ?>
                </span>
                <span><?php esc_html_e( 'Log Out', 'notifal' ); ?></span>
            </a>
        </li>
    </ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
