<?php
/**
 * Shared base for this plugin's emails.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UOCA_Email' ) ) :

	/**
	 * Base email: one template, an intro line, and the customer's reason.
	 */
	abstract class UOCA_Email extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->template_html  = 'emails/uoca-cancellation.php';
			$this->template_plain = 'emails/plain/uoca-cancellation.php';
			$this->template_base  = UOCA_PLUGIN_DIR . 'templates/';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			parent::__construct();
		}

		/**
		 * The paragraph shown above the order details.
		 *
		 * @return string
		 */
		abstract protected function get_intro();

		/**
		 * Load the order and fill placeholders.
		 *
		 * @param int $order_id Order ID.
		 * @return bool Whether an order was found.
		 */
		protected function setup_order( $order_id ) {
			$order = $order_id ? wc_get_order( $order_id ) : false;

			if ( ! $order ) {
				return false;
			}

			$this->object                         = $order;
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			return true;
		}

		/**
		 * Send if enabled and addressed.
		 */
		protected function maybe_send() {
			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
		}

		/**
		 * Template arguments.
		 *
		 * @param bool $plain_text Plain text.
		 * @return array
		 */
		protected function template_args( $plain_text ) {
			$order = $this->object;

			return array(
				'order'              => $order,
				'email_heading'      => $this->get_heading(),
				'intro'              => $this->get_intro(),
				'reason'             => $order ? (string) $order->get_meta( '_uoca_reason' ) : '',
				'notes'              => $order ? (string) $order->get_meta( '_uoca_notes' ) : '',
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => ! $this->is_customer_email(),
				'plain_text'         => $plain_text,
				'email'              => $this,
			);
		}

		/**
		 * HTML content.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
		}

		/**
		 * Plain-text content.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
		}

		/**
		 * Default additional content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return '';
		}
	}

endif;
