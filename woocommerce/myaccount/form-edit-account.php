<?php
/**
 * Edit account form - Custom Notifal Design
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Notifal\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>

<div class="settings-content">
    <div class="settings-header">
        <h2><?php esc_html_e( 'Account Settings', 'notifal' ); ?></h2>
        <p><?php esc_html_e( 'Manage your account information and preferences', 'notifal' ); ?></p>
    </div>

    <div class="settings-form-container">
        <form class="woocommerce-EditAccountForm notifal-account-form" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

            <?php do_action( 'woocommerce_edit_account_form_start' ); ?>

            <!-- Personal Information Section -->
            <div class="settings-section">
                <div class="section-header">
                    <h3><?php esc_html_e( 'Personal Information', 'notifal' ); ?></h3>
                    <p><?php esc_html_e( 'Update your personal details', 'notifal' ); ?></p>
                </div>

                <div class="form-row-grid">
                    <div class="form-group">
                        <label for="account_first_name">
                            <?php esc_html_e( 'First Name', 'notifal' ); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name"
                               autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="account_last_name">
                            <?php esc_html_e( 'Last Name', 'notifal' ); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name"
                               autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" required />
                    </div>
                </div>

                <div class="form-group">
                    <label for="account_display_name">
                        <?php esc_html_e( 'Display Name', 'notifal' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name"
                           value="<?php echo esc_attr( $user->display_name ); ?>" required />
                    <small class="form-help">
                        <?php esc_html_e( 'This is how your name will be displayed publicly.', 'notifal' ); ?>
                    </small>
                </div>

                <div class="form-group">
                    <label for="account_email">
                        <?php esc_html_e( 'Email Address', 'notifal' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email"
                           autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" required />
                </div>
            </div>

            <!-- Password Section -->
            <div class="settings-section">
                <div class="section-header">
                    <h3><?php esc_html_e( 'Change Password', 'notifal' ); ?></h3>
                    <p><?php esc_html_e( 'Update your password for security', 'notifal' ); ?></p>
                </div>

                <div class="form-group">
                    <label for="password_current">
                        <?php esc_html_e( 'Current Password', 'notifal' ); ?>
                    </label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current"
                           autocomplete="current-password" />
                    <small class="form-help">
                        <?php esc_html_e( 'Leave blank to keep current password', 'notifal' ); ?>
                    </small>
                </div>

                <div class="form-row-grid">
                    <div class="form-group">
                        <label for="password_1">
                            <?php esc_html_e( 'New Password', 'notifal' ); ?>
                        </label>
                        <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1"
                               autocomplete="new-password" />
                    </div>

                    <div class="form-group">
                        <label for="password_2">
                            <?php esc_html_e( 'Confirm New Password', 'notifal' ); ?>
                        </label>
                        <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2"
                               autocomplete="new-password" />
                    </div>
                </div>
            </div>

            <?php
                /**
                 * Hook where additional fields should be rendered.
                 */
                do_action( 'woocommerce_edit_account_form_fields' );

                /**
                 * My Account edit account form.
                 */
                do_action( 'woocommerce_edit_account_form' );
            ?>

            <!-- Form Actions -->
            <div class="form-actions">
                <?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
                <button type="submit" class="woocommerce-Button button" name="save_account_details">
                    <i class="fas fa-save"></i>
                    <?php esc_html_e( 'Save Changes', 'notifal' ); ?>
                </button>
                <input type="hidden" name="action" value="save_account_details" />
            </div>

            <?php do_action( 'woocommerce_edit_account_form_end' ); ?>
        </form>
    </div>
</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>