<?php
/**
 * Admin email: new cancellation request.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UOCA_Email_Admin_New_Request' ) ) :

	/**
	 * Sent to the store when a customer requests a cancellation.
	 */
	class UOCA_Email_Admin_New_Request extends UOCA_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'uoca_admin_new_request';
			$this->title       = __( 'Cancellation request (admin)', 'unstoppable-cancel-orders' );
			$this->description = __( 'Sent to the store when a customer asks to cancel an order.', 'unstoppable-cancel-orders' );

			add_action( 'uoca_request_submitted_notification', array( $this, 'trigger' ), 10, 1 );

			parent::__construct();

			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] Cancellation request for order #{order_number}', 'unstoppable-cancel-orders' );
		}

		/**
		 * Default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Cancellation request', 'unstoppable-cancel-orders' );
		}

		/**
		 * Intro paragraph.
		 *
		 * @return string
		 */
		protected function get_intro() {
			return __( 'A customer has asked to cancel this order. Approve or decline it from Options Studio > Order Cancellation, or from the Order actions box in the order editor.', 'unstoppable-cancel-orders' );
		}

		/**
		 * Trigger.
		 *
		 * @param int $order_id Order ID.
		 */
		public function trigger( $order_id ) {
			$this->setup_locale();

			if ( $this->setup_order( $order_id ) ) {
				$this->maybe_send();
			}

			$this->restore_locale();
		}

		/**
		 * Settings fields: add a recipient field.
		 */
		public function init_form_fields() {
			parent::init_form_fields();

			$this->form_fields = array_merge(
				array(
					'enabled'   => $this->form_fields['enabled'],
					'recipient' => array(
						'title'       => __( 'Recipient(s)', 'unstoppable-cancel-orders' ),
						'type'        => 'text',
						/* translators: %s: admin email */
						'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'unstoppable-cancel-orders' ), '<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>' ),
						'placeholder' => '',
						'default'     => '',
						'desc_tip'    => true,
					),
				),
				$this->form_fields
			);
		}
	}

endif;
