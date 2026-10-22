<?php
/**
 * Cancellation email (plain text).
 *
 * Override by copying to yourtheme/woocommerce/emails/plain/uoca-cancellation.php.
 *
 * @package Unstoppable_Cancel_Orders
 *
 * @var WC_Order $order              Order.
 * @var string   $email_heading      Heading.
 * @var string   $intro              Intro paragraph.
 * @var string   $reason             Customer's reason.
 * @var string   $notes              Customer's notes.
 * @var string   $additional_content Additional content.
 * @var bool     $sent_to_admin      Sent to admin.
 * @var bool     $plain_text         Plain text.
 * @var WC_Email $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email hooks, invoked as WooCommerce's own templates do.

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

if ( ! $sent_to_admin ) {
	/* translators: %s: customer first name */
	echo esc_html( sprintf( __( 'Hi %s,', 'unstoppable-cancel-orders' ), $order->get_billing_first_name() ) ) . "\n\n";
}

echo esc_html( $intro ) . "\n\n";

if ( '' !== $reason ) {
	echo esc_html__( 'Reason:', 'unstoppable-cancel-orders' ) . ' ' . esc_html( $reason ) . "\n";
}

if ( '' !== $notes ) {
	echo esc_html__( 'Notes:', 'unstoppable-cancel-orders' ) . ' ' . esc_html( $notes ) . "\n";
}

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n" . wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
