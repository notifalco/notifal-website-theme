<?php
/**
 * My Account Dashboard - Custom Notifal Design
 *
 * Shows the dashboard with license management and quick access cards.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Notifal\Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
?>

<div class="dashboard-welcome">
    <div class="welcome-header">
        <h1 style="color: #000000;"><?php printf( esc_html__( 'Welcome back, %s!', 'notifal' ), esc_html( $current_user->display_name ) ); ?></h1>
        <p><?php esc_html_e( 'Easily manage your account, track orders, access licenses, and get support when you need it.', 'notifal' ); ?></p>
    </div>
</div>

<div class="dashboard-content">
    <!-- Quick Stats Cards -->
    <?php
    $dashboard_stats = array(
        'orders' => array(
            'icon' => 'shopping-bag',
            'value' => wc_get_customer_order_count( get_current_user_id() ),
            'label' => __( 'Total Orders', 'notifal' ),
            'action_url' => wc_get_account_endpoint_url( 'orders' ),
            'action_text' => __( 'View Orders', 'notifal' )
        ),
        'support' => array(
            'icon' => 'chat',
            'value' => notifal_get_support_ticket_display_value(),
            'label' => __( 'Support Tickets', 'notifal' ),
            'action_url' => wc_get_account_endpoint_url( 'support' ),
            'action_text' => __( 'Get Support', 'notifal' )
        )
    );

    // Allow plugins to add/modify dashboard stats
    $dashboard_stats = apply_filters( 'notifal_dashboard_stats', $dashboard_stats );
    ?>

    <div class="dashboard-stats">
        <?php foreach ( $dashboard_stats as $stat_key => $stat ) : ?>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <?php echo function_exists( 'notifal_get_icon_svg' ) ? notifal_get_icon_svg( $stat['icon'] ) : ''; ?>
                    </div>
                    <div class="stat-content">
                        <strong><?php echo wp_kses_post( $stat['value'] ); ?></strong>
                        <p><?php echo esc_html( $stat['label'] ); ?></p>
                    </div>
                </div>
                <?php if ( ! empty( $stat['action_url'] ) && ! empty( $stat['action_text'] ) ) : ?>
                    <div class="stat-action">
                        <a href="<?php echo esc_url( $stat['action_url'] ); ?>" class="btn btn-primary">
                            <?php echo esc_html( $stat['action_text'] ); ?>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Orders Section -->
    <?php
    $customer_orders = wc_get_orders( array(
        'customer' => get_current_user_id(),
        'limit' => 3,
        'orderby' => 'date',
        'order' => 'DESC',
    ) );

    if ( ! empty( $customer_orders ) ) : ?>
        <div class="dashboard-section recent-orders">
            <div class="section-header">
                <h2><?php esc_html_e( 'Recent Orders', 'notifal' ); ?></h2>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="view-all-link">
                    <?php esc_html_e( 'View All Orders', 'notifal' ); ?> <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="orders-preview">
                <?php foreach ( $customer_orders as $order ) : ?>
                    <div class="order-preview-card">
                        <div class="order-info">
                            <h4><?php printf( esc_html__( 'Order #%s', 'notifal' ), esc_html( $order->get_order_number() ) ); ?></h4>
                            <p class="order-date"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></p>
                            <p class="order-status status-<?php echo esc_attr( $order->get_status() ); ?>">
                                <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                            </p>
                        </div>
                        <div class="order-total">
                            <span class="total-amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
