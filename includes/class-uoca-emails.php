<?php
/**
 * Email registration.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the request emails and connects WooCommerce's own
 * "Cancelled order" emails to approved requests.
 */
class UOCA_Emails {

	/**
	 * Status transition WooCommerce does not send a cancellation email for by itself.
	 */
	const APPROVED_TRANSITION = 'woocommerce_order_status_' . UOCA_STATUS_PENDING . '_to_cancelled';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
		add_filter( 'woocommerce_email_actions', array( $this, 'register_email_actions' ) );
	}

	/**
	 * Actions WooCommerce should treat as email triggers ("{action}_notification").
	 *
	 * @param array $actions Actions.
	 * @return array
	 */
	public function register_email_actions( $actions ) {
		$actions[] = 'uoca_request_submitted';
		$actions[] = 'uoca_request_declined';
		$actions[] = self::APPROVED_TRANSITION;

		return $actions;
	}

	/**
	 * Add our emails and hook WooCommerce's cancelled-order emails.
	 *
	 * @param array $emails Email objects.
	 * @return array
	 */
	public function register_emails( $emails ) {
		require_once UOCA_PLUGIN_DIR . 'includes/emails/class-uoca-email.php';
		require_once UOCA_PLUGIN_DIR . 'includes/emails/class-uoca-email-admin-new-request.php';
		require_once UOCA_PLUGIN_DIR . 'includes/emails/class-uoca-email-customer-request-received.php';
		require_once UOCA_PLUGIN_DIR . 'includes/emails/class-uoca-email-customer-request-declined.php';

		$emails['UOCA_Email_Admin_New_Request']         = new UOCA_Email_Admin_New_Request();
		$emails['UOCA_Email_Customer_Request_Received'] = new UOCA_Email_Customer_Request_Received();
		$emails['UOCA_Email_Customer_Request_Declined'] = new UOCA_Email_Customer_Request_Declined();

		// Approved request (Pending Cancellation -> Cancelled): use WooCommerce's own emails,
		// which the store already controls under WooCommerce > Settings > Emails.
		foreach ( array( 'WC_Email_Cancelled_Order', 'WC_Email_Customer_Cancelled_Order' ) as $class ) {
			if ( isset( $emails[ $class ] ) && is_callable( array( $emails[ $class ], 'trigger' ) ) ) {
				add_action( self::APPROVED_TRANSITION . '_notification', array( $emails[ $class ], 'trigger' ), 10, 2 );
			}
		}

		return $emails;
	}
}
