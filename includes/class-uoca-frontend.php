<?php
/**
 * Customer-facing buttons, modal and assets.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the cancel button to My Account, the order-received page and emails.
 */
class UOCA_Frontend {

	/**
	 * Action key used in the My Account orders table.
	 */
	const ACTION_KEY = 'uoca-cancel';

	/**
	 * Whether a button was printed on this page (the modal is only needed then).
	 *
	 * @var bool
	 */
	private $needs_modal = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'orders_list_action' ), 10, 2 );

		// Runs on both the view-order page and the order-received page.
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details_button' ), 10, 1 );

		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_link' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Is this a page where the button can appear?
	 *
	 * @return bool
	 */
	private function is_relevant_page() {
		return function_exists( 'is_account_page' ) && ( is_account_page() || is_order_received_page() );
	}

	/**
	 * Button label for the current mode.
	 *
	 * @return string
	 */
	public static function button_text() {
		return 'direct' === UOCA_Settings::get( 'mode' )
			? UOCA_Settings::get( 'button_text_direct' )
			: UOCA_Settings::get( 'button_text_request' );
	}

	/**
	 * Extra button class block themes style (e.g. "wp-element-button"), as WooCommerce uses.
	 *
	 * @return string
	 */
	public static function theme_button_class() {
		return function_exists( 'wc_wp_theme_get_element_class_name' ) ? (string) wc_wp_theme_get_element_class_name( 'button' ) : '';
	}

	/**
	 * Can the current visitor cancel this order from the front end?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function visitor_can_cancel( $order ) {
		if ( ! $order instanceof WC_Order || ! UOCA_Cancellation::is_order_eligible( $order ) ) {
			return false;
		}

		if ( $order->get_customer_id() ) {
			return is_user_logged_in() && get_current_user_id() === $order->get_customer_id();
		}

		// Guests reach the order-received page through a URL that carries the order key.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only comparison against the order's own key.
		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		return UOCA_Cancellation::current_user_owns_order( $order, $key );
	}

	/**
	 * Add the action to the My Account orders table.
	 *
	 * Without JavaScript the link opens the order page, which has its own button.
	 *
	 * @param array    $actions Order actions.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	public function orders_list_action( $actions, $order ) {
		if ( ! $this->visitor_can_cancel( $order ) ) {
			return $actions;
		}

		$actions[ self::ACTION_KEY ] = array(
			'url'        => add_query_arg( 'uoca_order', $order->get_id(), $order->get_view_order_url() ),
			'name'       => self::button_text(),
			/* translators: %s: order number */
			'aria-label' => sprintf( __( 'Cancel order %s', 'unstoppable-cancel-orders' ), $order->get_order_number() ),
		);

		$this->needs_modal = true;

		return $actions;
	}

	/**
	 * Button under the order details table.
	 *
	 * @param WC_Order $order Order.
	 */
	public function order_details_button( $order ) {
		if ( ! $this->visitor_can_cancel( $order ) ) {
			return;
		}

		$this->needs_modal = true;
		?>
		<p class="uoca-actions">
			<button type="button" class="<?php echo esc_attr( trim( 'button uoca-cancel-button ' . self::theme_button_class() ) ); ?>"
				data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
				<?php if ( ! $order->get_customer_id() ) : ?>
					data-order-key="<?php echo esc_attr( $order->get_order_key() ); ?>"
				<?php endif; ?>
			>
				<?php echo esc_html( self::button_text() ); ?>
			</button>
		</p>
		<?php
	}

	/**
	 * Link in customer order emails (registered customers only).
	 *
	 * Guests have no My Account page to land on, so they get no link.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Whether the email goes to the admin.
	 * @param bool     $plain_text    Plain-text email.
	 */
	public function email_link( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || ! UOCA_Settings::get( 'show_in_emails' ) || ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! $order->get_customer_id() || ! UOCA_Cancellation::is_order_eligible( $order ) ) {
			return;
		}

		$url = $order->get_view_order_url();

		if ( $plain_text ) {
			echo "\n" . esc_html( self::button_text() ) . ': ' . esc_url_raw( $url ) . "\n\n";
			return;
		}
		?>
		<p class="uoca-email-link">
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( self::button_text() ); ?></a>
		</p>
		<?php
	}

	/**
	 * Front-end assets on My Account and order-received pages.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_relevant_page() ) {
			return;
		}

		wp_enqueue_style( 'uoca-frontend', UOCA_PLUGIN_URL . 'assets/css/frontend.css', array(), UOCA_VERSION );
		wp_enqueue_script( 'uoca-frontend', UOCA_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), UOCA_VERSION, true );

		wp_localize_script(
			'uoca-frontend',
			'uocaFrontend',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'uoca_cancel_order' ),
				'reasonField' => UOCA_Settings::get( 'reason_field' ),
				'notesField'  => UOCA_Settings::get( 'notes_field' ),
				'i18n'        => array(
					'processing'     => __( 'Processing…', 'unstoppable-cancel-orders' ),
					'error'          => __( 'Something went wrong. Please try again.', 'unstoppable-cancel-orders' ),
					'reasonRequired' => __( 'Please choose a reason for cancelling.', 'unstoppable-cancel-orders' ),
					'notesRequired'  => __( 'Please add a note explaining the cancellation.', 'unstoppable-cancel-orders' ),
				),
			)
		);
	}

	/**
	 * Print the modal once, in the footer, when a button is on the page.
	 */
	public function render_modal() {
		if ( ! $this->needs_modal || ! $this->is_relevant_page() ) {
			return;
		}

		$direct = 'direct' === UOCA_Settings::get( 'mode' );

		wc_get_template(
			'myaccount/uoca-cancellation-modal.php',
			array(
				'modal_title'   => $direct ? UOCA_Settings::get( 'modal_title_direct' ) : UOCA_Settings::get( 'modal_title_request' ),
				'modal_message' => UOCA_Settings::get( 'modal_message' ),
				'submit_text'   => $direct ? __( 'Cancel Order', 'unstoppable-cancel-orders' ) : __( 'Submit Request', 'unstoppable-cancel-orders' ),
				'reason_field'  => UOCA_Settings::get( 'reason_field' ),
				'reasons'       => UOCA_Cancellation::get_reasons(),
				'notes_field'   => UOCA_Settings::get( 'notes_field' ),
				'button_class'  => self::theme_button_class(),
			),
			'',
			UOCA_PLUGIN_DIR . 'templates/'
		);
	}
}
