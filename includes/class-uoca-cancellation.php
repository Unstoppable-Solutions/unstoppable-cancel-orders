<?php
/**
 * Cancellation rules and processing.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether an order can be cancelled, and cancels it or files a request.
 */
class UOCA_Cancellation {

	/**
	 * Request meta values.
	 */
	const REQUEST_PENDING  = 'pending';
	const REQUEST_APPROVED = 'approved';
	const REQUEST_DECLINED = 'declined';

	/**
	 * Maximum length for customer notes.
	 */
	const NOTES_MAX = 1000;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_uoca_cancel_order', array( $this, 'ajax_cancel_order' ) );
		add_action( 'wp_ajax_nopriv_uoca_cancel_order', array( $this, 'ajax_cancel_order' ) );
	}

	/**
	 * Is this order eligible for cancellation right now?
	 *
	 * Checks the plugin switch, the order status and the customer's role. It
	 * does not check who is asking; see current_user_owns_order().
	 *
	 * @param WC_Order|int $order Order or ID.
	 * @return bool
	 */
	public static function is_order_eligible( $order ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );

		if ( ! $order || ! UOCA_Settings::get( 'enabled' ) ) {
			return false;
		}

		$eligible = false;

		if ( in_array( $order->get_status(), (array) UOCA_Settings::get( 'eligible_statuses', array() ), true ) ) {
			$eligible = self::customer_role_allowed( $order );
		}

		/**
		 * Filter whether an order may be cancelled by its customer.
		 *
		 * Add-ons can use this for extra rules, such as time limits.
		 *
		 * @param bool     $eligible Whether the order is eligible.
		 * @param WC_Order $order    The order.
		 */
		return (bool) apply_filters( 'uoca_can_cancel_order', $eligible, $order );
	}

	/**
	 * Does the order's customer have an allowed role?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private static function customer_role_allowed( $order ) {
		$customer_id = $order->get_customer_id();

		if ( ! $customer_id ) {
			return (bool) UOCA_Settings::get( 'allow_guests' );
		}

		$roles = (array) UOCA_Settings::get( 'eligible_roles', array() );

		// No roles selected means every registered customer may cancel.
		if ( empty( $roles ) ) {
			return true;
		}

		$user = get_userdata( $customer_id );

		return $user && (bool) array_intersect( $roles, (array) $user->roles );
	}

	/**
	 * Is the current visitor the order's customer?
	 *
	 * Registered customers must be logged in as that customer. Guest orders are
	 * verified with the order key, which only the purchaser has.
	 *
	 * @param WC_Order $order     Order.
	 * @param string   $order_key Order key supplied by a guest, if any.
	 * @return bool
	 */
	public static function current_user_owns_order( $order, $order_key = '' ) {
		$customer_id = $order->get_customer_id();

		if ( $customer_id ) {
			return is_user_logged_in() && get_current_user_id() === $customer_id;
		}

		return '' !== $order_key && hash_equals( $order->get_order_key(), $order_key );
	}

	/**
	 * Cancellation reasons shown to customers.
	 *
	 * @return string[]
	 */
	public static function get_reasons() {
		$reasons = (array) UOCA_Settings::get( 'reasons', array() );

		/**
		 * Filter the list of cancellation reasons.
		 *
		 * @param string[] $reasons Reasons.
		 */
		return array_values( array_filter( (array) apply_filters( 'uoca_cancellation_reasons', $reasons ), 'is_string' ) );
	}

	/**
	 * AJAX: cancel an order or submit a cancellation request.
	 */
	public function ajax_cancel_order() {
		check_ajax_referer( 'uoca_cancel_order', 'nonce' );

		$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
		$reason    = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$notes     = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		$order = $order_id ? wc_get_order( $order_id ) : false;

		// Same message for "not found" and "not yours", so order IDs can't be probed.
		if ( ! $order || ! self::current_user_owns_order( $order, $order_key ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to cancel this order.', 'unstoppable-cancel-orders' ) ), 403 );
		}

		if ( ! self::is_order_eligible( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'This order can no longer be cancelled.', 'unstoppable-cancel-orders' ) ) );
		}

		$reason_field = UOCA_Settings::get( 'reason_field' );

		if ( 'off' === $reason_field ) {
			$reason = '';
		} elseif ( '' !== $reason && ! in_array( $reason, self::get_reasons(), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Please choose one of the listed reasons.', 'unstoppable-cancel-orders' ) ) );
		} elseif ( 'required' === $reason_field && '' === $reason ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a reason for cancelling.', 'unstoppable-cancel-orders' ) ) );
		}

		$notes_field = UOCA_Settings::get( 'notes_field' );

		if ( 'off' === $notes_field ) {
			$notes = '';
		} elseif ( 'required' === $notes_field && '' === trim( $notes ) ) {
			wp_send_json_error( array( 'message' => __( 'Please add a note explaining the cancellation.', 'unstoppable-cancel-orders' ) ) );
		}

		$notes = mb_substr( $notes, 0, self::NOTES_MAX );

		if ( 'direct' === UOCA_Settings::get( 'mode' ) ) {
			self::cancel_order( $order, $reason, $notes );
			$message = __( 'Your order has been cancelled.', 'unstoppable-cancel-orders' );
		} else {
			self::submit_request( $order, $reason, $notes );
			$message = __( 'Your cancellation request has been sent. We will let you know once it has been reviewed.', 'unstoppable-cancel-orders' );
		}

		wp_send_json_success( array( 'message' => $message ) );
	}

	/**
	 * Store the customer's reason and notes on the order.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $reason Reason.
	 * @param string   $notes  Notes.
	 */
	private static function store_details( $order, $reason, $notes ) {
		$order->update_meta_data( '_uoca_previous_status', $order->get_status() );
		$order->update_meta_data( '_uoca_reason', $reason );
		$order->update_meta_data( '_uoca_notes', $notes );
		$order->update_meta_data( '_uoca_requested_at', time() );
	}

	/**
	 * Build the order note text.
	 *
	 * @param string $intro  First sentence.
	 * @param string $reason Reason.
	 * @param string $notes  Notes.
	 * @return string
	 */
	private static function note_text( $intro, $reason, $notes ) {
		$text = $intro;

		if ( '' !== $reason ) {
			/* translators: %s: cancellation reason */
			$text .= ' ' . sprintf( __( 'Reason: %s', 'unstoppable-cancel-orders' ), $reason );
		}

		if ( '' !== $notes ) {
			/* translators: %s: customer notes */
			$text .= ' ' . sprintf( __( 'Notes: %s', 'unstoppable-cancel-orders' ), $notes );
		}

		return $text;
	}

	/**
	 * Cancel immediately.
	 *
	 * WooCommerce restores reduced stock itself when an order becomes Cancelled.
	 * No refund is created; refunds stay a manual decision for the store.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $reason Reason.
	 * @param string   $notes  Notes.
	 */
	public static function cancel_order( $order, $reason, $notes ) {
		self::store_details( $order, $reason, $notes );
		$order->update_meta_data( '_uoca_cancelled_by', 'customer' );

		$order->update_status(
			'cancelled',
			self::note_text( __( 'Order cancelled by the customer.', 'unstoppable-cancel-orders' ), $reason, $notes )
		);

		/**
		 * Fires after a customer cancels an order directly.
		 *
		 * @param int $order_id Order ID.
		 */
		do_action( 'uoca_order_cancelled', $order->get_id() );
	}

	/**
	 * File a cancellation request for the store to review.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $reason Reason.
	 * @param string   $notes  Notes.
	 */
	public static function submit_request( $order, $reason, $notes ) {
		self::store_details( $order, $reason, $notes );
		$order->update_meta_data( '_uoca_request_status', self::REQUEST_PENDING );

		$order->update_status(
			UOCA_STATUS_PENDING,
			self::note_text( __( 'Cancellation requested by the customer.', 'unstoppable-cancel-orders' ), $reason, $notes )
		);

		/**
		 * Fires after a customer submits a cancellation request.
		 *
		 * @param int $order_id Order ID.
		 */
		do_action( 'uoca_request_submitted', $order->get_id() );
	}

	/**
	 * Approve a pending request: the order is cancelled.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function approve_request( $order ) {
		if ( ! $order || UOCA_STATUS_PENDING !== $order->get_status() ) {
			return false;
		}

		$order->update_meta_data( '_uoca_request_status', self::REQUEST_APPROVED );
		$order->update_meta_data( '_uoca_processed_by', get_current_user_id() );
		$order->update_meta_data( '_uoca_processed_at', time() );
		$order->update_status( 'cancelled', __( 'Cancellation request approved.', 'unstoppable-cancel-orders' ) );

		/**
		 * Fires after a cancellation request is approved.
		 *
		 * @param int $order_id Order ID.
		 */
		do_action( 'uoca_request_approved', $order->get_id() );

		return true;
	}

	/**
	 * Decline a pending request: the order goes back to its previous status.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function decline_request( $order ) {
		if ( ! $order || UOCA_STATUS_PENDING !== $order->get_status() ) {
			return false;
		}

		$previous = (string) $order->get_meta( '_uoca_previous_status' );

		// Orders put on hold by the earlier build stored the status under another key.
		if ( '' === $previous ) {
			$previous = (string) $order->get_meta( '_cancellation_previous_status' );
		}

		$previous = 0 === strpos( $previous, 'wc-' ) ? substr( $previous, 3 ) : $previous;

		if ( '' === $previous || UOCA_STATUS_PENDING === $previous || ! array_key_exists( 'wc-' . $previous, wc_get_order_statuses() ) ) {
			$previous = 'processing';
		}

		$order->update_meta_data( '_uoca_request_status', self::REQUEST_DECLINED );
		$order->update_meta_data( '_uoca_processed_by', get_current_user_id() );
		$order->update_meta_data( '_uoca_processed_at', time() );
		$order->update_status( $previous, __( 'Cancellation request declined.', 'unstoppable-cancel-orders' ) );

		/**
		 * Fires after a cancellation request is declined.
		 *
		 * @param int $order_id Order ID.
		 */
		do_action( 'uoca_request_declined', $order->get_id() );

		return true;
	}
}
