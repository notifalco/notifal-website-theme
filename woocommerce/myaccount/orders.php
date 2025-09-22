<?php
/**
 * Orders - Custom Notifal Design
 *
 * Shows orders on the account page with enhanced design and status indicators.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Notifal\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Define variables that WooCommerce core passes to the template
$current_page    = empty( $current_page ) ? 1 : absint( $current_page );
$customer_orders = wc_get_orders(
	apply_filters(
		'woocommerce_my_account_my_orders_query',
		array(
			'customer' => get_current_user_id(),
			'page'     => $current_page,
			'paginate' => true,
		)
	)
);
$has_orders      = 0 < $customer_orders->total;
$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<div class="orders-content">
    <?php if ( $has_orders ) : ?>

        <div class="orders-header">
            <h2><?php esc_html_e( 'Your Orders', 'notifal' ); ?></h2>
            <p><?php esc_html_e( 'View and manage all your orders', 'notifal' ); ?></p>
        </div>

        <div class="orders-table-container">
            <table class="woocommerce-orders-table notifal-orders-table">
                <thead>
                    <tr>
                        <th class="order-number"><?php esc_html_e( 'Order ID', 'notifal' ); ?></th>
                        <th class="order-date"><?php esc_html_e( 'Date', 'notifal' ); ?></th>
                        <th class="order-status"><?php esc_html_e( 'Status', 'notifal' ); ?></th>
                        <th class="order-total"><?php esc_html_e( 'Total', 'notifal' ); ?></th>
                        <th class="order-actions"><?php esc_html_e( 'Actions', 'notifal' ); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ( $customer_orders->orders as $customer_order ) {
                        $order      = wc_get_order( $customer_order );
                        $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                        $order_status = $order->get_status();
                        ?>
                        <tr class="order-row status-<?php echo esc_attr( $order_status ); ?>">
                            <td class="order-number" data-title="<?php esc_attr_e( 'Order ID', 'notifal' ); ?>">
                                <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="order-link">
                                    #<?php echo esc_html( $order->get_order_number() ); ?>
                                </a>
                            </td>

                            <td class="order-date" data-title="<?php esc_attr_e( 'Date', 'notifal' ); ?>">
                                <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
                                    <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'M j, Y' ) ); ?>
                                </time>
                            </td>

                            <td class="order-status" data-title="<?php esc_attr_e( 'Status', 'notifal' ); ?>">
                                <span class="status-indicator status-<?php echo esc_attr( $order_status ); ?>">
                                    <span class="status-dot"></span>
                                    <?php echo esc_html( wc_get_order_status_name( $order_status ) ); ?>
                                </span>
                            </td>

                            <td class="order-total" data-title="<?php esc_attr_e( 'Total', 'notifal' ); ?>">
                                <span class="total-amount">
                                    <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                                </span>
                            </td>

                            <td class="order-actions" data-title="<?php esc_attr_e( 'Actions', 'notifal' ); ?>">
                                <?php
                                $actions = wc_get_account_orders_actions( $order );

                                if ( ! empty( $actions ) ) {
                                    foreach ( $actions as $key => $action ) {
                                        $button_class = 'btn btn-secondary action-' . sanitize_html_class( $key );

                                        if ( $key === 'pay' ) {
                                            $button_class = 'btn btn-success';
                                        } elseif ( $key === 'view' ) {
                                            $button_class = 'btn btn-primary';
                                        }

                                        echo '<a href="' . esc_url( $action['url'] ) . '" class="' . esc_attr( $button_class ) . '">' . esc_html( $action['name'] ) . '</a>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

        <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
            <div class="orders-pagination">
                <?php if ( 1 !== $current_page ) : ?>
                    <a class="pagination-btn prev-btn" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">
                        <i class="fas fa-chevron-left"></i> <?php esc_html_e( 'Previous', 'notifal' ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
                    <a class="pagination-btn next-btn" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
                        <?php esc_html_e( 'Next', 'notifal' ); ?> <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else : ?>

        <!-- Empty Orders State -->
        <div class="empty-orders-state">
            <div class="empty-state-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3><?php esc_html_e( 'No Orders Yet :(', 'notifal' ); ?></h3>
            <p><?php esc_html_e( 'You haven\'t placed any orders yet.', 'notifal' ); ?></p>
            <a href="<?php echo esc_url( get_permalink('660') ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'Get Notifal Pro', 'notifal' ); ?>
            </a>
        </div>

    <?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
