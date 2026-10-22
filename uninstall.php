<?php
/**
 * Uninstall.
 *
 * Settings are removed only when "Delete this plugin's settings when it is
 * deleted" is ticked. Orders, order notes and the cancellation details stored
 * on orders are never touched. Options left by the earlier
 * "wc-order-cancellation" plugin belong to that plugin and are not removed.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$uoca_settings = get_option( 'uoca_settings', array() );

if ( is_array( $uoca_settings ) && ! empty( $uoca_settings['remove_data_on_uninstall'] ) ) {
	delete_option( 'uoca_settings' );
	delete_option( 'uoca_migrated_from_legacy' );
	delete_option( 'uoca_install_date' );
	delete_option( 'uoca_welcome_notice_dismissed' );

	// Plugin-owned WooCommerce email settings.
	delete_option( 'woocommerce_uoca_admin_new_request_settings' );
	delete_option( 'woocommerce_uoca_customer_request_received_settings' );
	delete_option( 'woocommerce_uoca_customer_request_declined_settings' );
}
