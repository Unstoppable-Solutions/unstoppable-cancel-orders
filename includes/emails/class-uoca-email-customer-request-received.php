<?php
/**
 * Customer email: cancellation request received.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UOCA_Email_Customer_Request_Received' ) ) :

	/**
	 * Confirms to the customer that their request was received.
	 */
	class UOCA_Email_Customer_Request_Received extends UOCA_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'uoca_customer_request_received';
			$this->customer_email = true;
			$this->title          = __( 'Cancellation request received', 'unstoppable-cancel-orders' );
			$this->description    = __( 'Sent to customers to confirm their cancellation request.', 'unstoppable-cancel-orders' );

			add_action( 'uoca_request_submitted_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'We received your cancellation request for order #{order_number}', 'unstoppable-cancel-orders' );
		}

		/**
		 * Default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Cancellation request received', 'unstoppable-cancel-orders' );
		}

		/**
		 * Intro paragraph.
		 *
		 * @return string
		 */
		protected function get_intro() {
			return __( 'We have received your request to cancel this order. We will review it and let you know the outcome.', 'unstoppable-cancel-orders' );
		}

		/**
		 * Trigger.
		 *
		 * @param int $order_id Order ID.
		 */
		public function trigger( $order_id ) {
			$this->setup_locale();

			if ( $this->setup_order( $order_id ) ) {
				$this->recipient = $this->object->get_billing_email();
				$this->maybe_send();
			}

			$this->restore_locale();
		}
	}

endif;
