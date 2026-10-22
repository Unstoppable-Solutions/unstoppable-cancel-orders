<?php
/**
 * Customer email: cancellation request declined.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UOCA_Email_Customer_Request_Declined' ) ) :

	/**
	 * Tells the customer their request was declined.
	 */
	class UOCA_Email_Customer_Request_Declined extends UOCA_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'uoca_customer_request_declined';
			$this->customer_email = true;
			$this->title          = __( 'Cancellation request declined', 'unstoppable-cancel-orders' );
			$this->description    = __( 'Sent to customers when their cancellation request is declined.', 'unstoppable-cancel-orders' );

			add_action( 'uoca_request_declined_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your cancellation request for order #{order_number}', 'unstoppable-cancel-orders' );
		}

		/**
		 * Default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Cancellation request declined', 'unstoppable-cancel-orders' );
		}

		/**
		 * Intro paragraph.
		 *
		 * @return string
		 */
		protected function get_intro() {
			return __( 'We were unable to cancel this order, so it will continue as normal. Please contact us if you have any questions.', 'unstoppable-cancel-orders' );
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
