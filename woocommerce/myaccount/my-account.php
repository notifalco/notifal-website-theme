<?php
/**
 * My Account page - Custom Notifal Design
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Notifal\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Custom My Account layout with sidebar navigation
 */
?>
<div class="woocommerce-my-account notifal-my-account">
    <div class="container">
        <div class="account-layout">
            <!-- Left Sidebar Navigation -->
            <aside class="account-sidebar">
                <?php
                /**
                 * My Account navigation.
                 *
                 * @since 2.6.0
                 */
                do_action( 'woocommerce_account_navigation' );
                ?>
            </aside>

            <!-- Main Content Area -->
            <main class="account-content">
                <div class="content-wrapper">
                    <?php
                    /**
                     * My Account content.
                     *
                     * @since 2.6.0
                     */
                    do_action( 'woocommerce_account_content' );
                    ?>
                </div>
            </main>
        </div>
    </div>
</div>
