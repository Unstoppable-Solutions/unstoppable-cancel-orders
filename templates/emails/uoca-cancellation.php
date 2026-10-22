<?php
/**
 * Cancellation email (HTML).
 *
 * Override by copying to yourtheme/woocommerce/emails/uoca-cancellation.php.
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php if ( ! $sent_to_admin ) : ?>
	<p>
		<?php
		/* translators: %s: customer first name */
		printf( esc_html__( 'Hi %s,', 'unstoppable-cancel-orders' ), esc_html( $order->get_billing_first_name() ) );
		?>
	</p>
<?php endif; ?>

<p><?php echo esc_html( $intro ); ?></p>

<?php if ( '' !== $reason || '' !== $notes ) : ?>
	<p>
		<?php if ( '' !== $reason ) : ?>
			<strong><?php esc_html_e( 'Reason:', 'unstoppable-cancel-orders' ); ?></strong> <?php echo esc_html( $reason ); ?><br />
		<?php endif; ?>
		<?php if ( '' !== $notes ) : ?>
			<strong><?php esc_html_e( 'Notes:', 'unstoppable-cancel-orders' ); ?></strong> <?php echo esc_html( $notes ); ?>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
