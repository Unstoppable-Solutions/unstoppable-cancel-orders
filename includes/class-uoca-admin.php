<?php
/**
 * Admin screen under Options Studio, and request handling in the order editor.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * One Options Studio menu item with Dashboard and Settings tabs.
 */
class UOCA_Admin {

	/**
	 * Settings API group.
	 */
	const SETTINGS_GROUP = 'uoca_settings_group';

	/**
	 * Hook suffix of our page.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Whether the earlier build is still active.
	 *
	 * @var bool
	 */
	private $legacy_active;

	/**
	 * Constructor.
	 *
	 * @param bool $legacy_active Whether the earlier build is still active.
	 */
	public function __construct( $legacy_active = false ) {
		$this->legacy_active = (bool) $legacy_active;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'option_page_capability_' . self::SETTINGS_GROUP, array( __CLASS__, 'settings_capability' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_uoca_process_request', array( $this, 'handle_request_decision' ) );

		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'woocommerce_order_action_uoca_approve_cancellation', array( $this, 'order_action_approve' ) );
		add_action( 'woocommerce_order_action_uoca_decline_cancellation', array( $this, 'order_action_decline' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_request_details' ) );
	}

	/**
	 * Register the single submenu item.
	 */
	public function add_admin_menu() {
		$this->hook_suffix = (string) add_submenu_page(
			'unstoppable-studio',
			__( 'Order Cancellation', 'unstoppable-cancel-orders' ),
			__( 'Order Cancellation', 'unstoppable-cancel-orders' ),
			'manage_woocommerce',
			UOCA_MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the settings option.
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			UOCA_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'UOCA_Settings', 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Capability options.php requires for our settings group (defaults to manage_options).
	 *
	 * @return string
	 */
	public static function settings_capability() {
		return 'manage_woocommerce';
	}

	/**
	 * Assets on our screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'uoca-admin', UOCA_PLUGIN_URL . 'assets/css/admin.css', array(), UOCA_VERSION );

		// WooCommerce's enhanced selects for the status and role pickers.
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
	}

	/**
	 * Tabs. Add-ons can add their own through the filter.
	 *
	 * @return array
	 */
	private function get_tabs() {
		$tabs = array(
			'dashboard' => array(
				'label' => __( 'Dashboard', 'unstoppable-cancel-orders' ),
				'icon'  => 'dashicons-dashboard',
			),
			'settings'  => array(
				'label' => __( 'Settings', 'unstoppable-cancel-orders' ),
				'icon'  => 'dashicons-admin-generic',
			),
		);

		/**
		 * Filter the admin tabs.
		 *
		 * @param array $tabs Tabs keyed by slug.
		 */
		return apply_filters( 'uoca_admin_tabs', $tabs );
	}

	/**
	 * Current tab, validated against the tab list.
	 *
	 * @return string
	 */
	private function get_current_tab() {
		// Read-only navigation value; it changes no state, so no nonce is needed.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';

		return array_key_exists( $tab, $this->get_tabs() ) ? $tab : 'dashboard';
	}

	/**
	 * Tab URL.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	private function tab_url( $tab ) {
		return admin_url( 'admin.php?page=' . UOCA_MENU_SLUG . '&tab=' . $tab );
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-cancel-orders' ) );
		}

		$current_tab = $this->get_current_tab();
		?>
		<div class="wrap uoca-wrap">
			<h1><?php esc_html_e( 'Order Cancellation', 'unstoppable-cancel-orders' ); ?></h1>

			<?php if ( $this->legacy_active ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'An earlier version of this plugin ("Unstoppable WC Cancel Orders") is still active. Your settings have been copied here. Deactivate the earlier version on the Plugins screen to switch over; until then, its cancel buttons stay in use and this plugin\'s are hidden.', 'unstoppable-cancel-orders' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php settings_errors(); ?>

			<nav class="uoca-tabs-nav" aria-label="<?php esc_attr_e( 'Plugin sections', 'unstoppable-cancel-orders' ); ?>">
				<?php foreach ( $this->get_tabs() as $tab_id => $tab ) : ?>
					<a href="<?php echo esc_url( $this->tab_url( $tab_id ) ); ?>"
						class="uoca-tab-link<?php echo ( $current_tab === $tab_id ) ? ' active' : ''; ?>"
						<?php echo ( $current_tab === $tab_id ) ? 'aria-current="page"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="uoca-tab-content">
				<?php
				switch ( $current_tab ) {
					case 'settings':
						$this->render_settings_tab();
						break;
					case 'dashboard':
						$this->render_dashboard_tab();
						break;
					default:
						/**
						 * Render an add-on tab.
						 *
						 * @param string $current_tab Tab slug.
						 */
						do_action( 'uoca_render_admin_tab', $current_tab );
						break;
				}
				?>
			</div>

			<?php $this->render_settings_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Dashboard: status summary and pending requests.
	 */
	private function render_dashboard_tab() {
		$pending = wc_get_orders(
			array(
				'status'  => array( 'wc-' . UOCA_STATUS_PENDING ),
				'limit'   => 20,
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		);

		$direct  = 'direct' === UOCA_Settings::get( 'mode' );
		$enabled = (bool) UOCA_Settings::get( 'enabled' );
		?>
		<div class="uoca-card">
			<h2><?php esc_html_e( 'Status', 'unstoppable-cancel-orders' ); ?></h2>
			<ul class="uoca-status-list">
				<li>
					<strong><?php esc_html_e( 'Customer cancellations:', 'unstoppable-cancel-orders' ); ?></strong>
					<?php echo $enabled ? esc_html__( 'On', 'unstoppable-cancel-orders' ) : esc_html__( 'Off', 'unstoppable-cancel-orders' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Mode:', 'unstoppable-cancel-orders' ); ?></strong>
					<?php echo $direct ? esc_html__( 'Customers cancel immediately', 'unstoppable-cancel-orders' ) : esc_html__( 'Customers send a request for you to approve', 'unstoppable-cancel-orders' ); ?>
				</li>
			</ul>
			<p>
				<a class="button" href="<?php echo esc_url( $this->tab_url( 'settings' ) ); ?>"><?php esc_html_e( 'Change settings', 'unstoppable-cancel-orders' ); ?></a>
			</p>
		</div>

		<div class="uoca-card">
			<h2><?php esc_html_e( 'Pending cancellation requests', 'unstoppable-cancel-orders' ); ?></h2>

			<?php if ( empty( $pending ) ) : ?>
				<p><?php esc_html_e( 'There are no cancellation requests waiting for a decision.', 'unstoppable-cancel-orders' ); ?></p>
			<?php else : ?>
				<table class="widefat striped uoca-requests">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Order', 'unstoppable-cancel-orders' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Customer', 'unstoppable-cancel-orders' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Requested', 'unstoppable-cancel-orders' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Reason', 'unstoppable-cancel-orders' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Total', 'unstoppable-cancel-orders' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Decision', 'unstoppable-cancel-orders' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending as $order ) : ?>
							<?php $this->render_request_row( $order ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<a href="<?php echo esc_url( $this->orders_list_url() ); ?>"><?php esc_html_e( 'View all Pending Cancellation orders', 'unstoppable-cancel-orders' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Orders list filtered to Pending Cancellation (HPOS or posts).
	 *
	 * @return string
	 */
	private function orders_list_url() {
		$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		return $hpos
			? admin_url( 'admin.php?page=wc-orders&status=wc-' . UOCA_STATUS_PENDING )
			: admin_url( 'edit.php?post_type=shop_order&post_status=wc-' . UOCA_STATUS_PENDING );
	}

	/**
	 * One request row.
	 *
	 * @param WC_Order $order Order.
	 */
	private function render_request_row( $order ) {
		$requested = (int) $order->get_meta( '_uoca_requested_at' );
		$reason    = (string) $order->get_meta( '_uoca_reason' );
		$notes     = (string) $order->get_meta( '_uoca_notes' );

		// Requests filed by the earlier build.
		if ( '' === $reason ) {
			$reason = (string) $order->get_meta( '_cancellation_reason' );
			$notes  = (string) $order->get_meta( '_cancellation_notes' );
		}

		$name = trim( $order->get_formatted_billing_full_name() );
		?>
		<tr>
			<td>
				<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
					<?php
					/* translators: %s: order number */
					echo esc_html( sprintf( __( 'Order #%s', 'unstoppable-cancel-orders' ), $order->get_order_number() ) );
					?>
				</a>
			</td>
			<td><?php echo esc_html( '' !== $name ? $name : $order->get_billing_email() ); ?></td>
			<td>
				<?php
				echo $requested
					? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $requested ) )
					: '&mdash;';
				?>
			</td>
			<td>
				<?php echo '' !== $reason ? esc_html( $reason ) : '&mdash;'; ?>
				<?php if ( '' !== $notes ) : ?>
					<br /><span class="description"><?php echo esc_html( $notes ); ?></span>
				<?php endif; ?>
			</td>
			<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			<td class="uoca-decision">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="uoca_process_request" />
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
					<?php wp_nonce_field( 'uoca_process_request_' . $order->get_id(), 'uoca_nonce' ); ?>
					<button type="submit" name="decision" value="approve" class="button button-primary"><?php esc_html_e( 'Approve', 'unstoppable-cancel-orders' ); ?></button>
					<button type="submit" name="decision" value="decline" class="button"><?php esc_html_e( 'Decline', 'unstoppable-cancel-orders' ); ?></button>
				</form>
			</td>
		</tr>
		<?php
	}

	/**
	 * Handle Approve / Decline from the Dashboard tab.
	 */
	public function handle_request_decision() {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		check_admin_referer( 'uoca_process_request_' . $order_id, 'uoca_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage orders.', 'unstoppable-cancel-orders' ), 403 );
		}

		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		$done     = false;

		if ( $order && 'approve' === $decision ) {
			$done = UOCA_Cancellation::approve_request( $order );
		} elseif ( $order && 'decline' === $decision ) {
			$done = UOCA_Cancellation::decline_request( $order );
		}

		if ( $done ) {
			$message = 'approve' === $decision
				/* translators: %s: order number */
				? sprintf( __( 'Cancellation approved. Order #%s is now cancelled.', 'unstoppable-cancel-orders' ), $order->get_order_number() )
				/* translators: %s: order number */
				: sprintf( __( 'Cancellation declined. Order #%s is back to its previous status.', 'unstoppable-cancel-orders' ), $order->get_order_number() );

			UOCA_Admin_Notices::add_flash_notice( esc_html( $message ), 'success' );
		} else {
			UOCA_Admin_Notices::add_flash_notice( esc_html__( 'That request could not be updated. It may already have been handled.', 'unstoppable-cancel-orders' ), 'error' );
		}

		wp_safe_redirect( $this->tab_url( 'dashboard' ) );
		exit;
	}

	/**
	 * Approve / Decline in the order editor's "Order actions" box.
	 *
	 * WooCommerce checks the nonce and capability when the order is saved.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order (WooCommerce 6.0+).
	 * @return array
	 */
	public function order_actions( $actions, $order = null ) {
		if ( $order instanceof WC_Order && UOCA_STATUS_PENDING === $order->get_status() ) {
			$actions['uoca_approve_cancellation'] = __( 'Approve cancellation request', 'unstoppable-cancel-orders' );
			$actions['uoca_decline_cancellation'] = __( 'Decline cancellation request', 'unstoppable-cancel-orders' );
		}

		return $actions;
	}

	/**
	 * Order action: approve.
	 *
	 * @param WC_Order $order Order.
	 */
	public function order_action_approve( $order ) {
		UOCA_Cancellation::approve_request( $order );
	}

	/**
	 * Order action: decline.
	 *
	 * @param WC_Order $order Order.
	 */
	public function order_action_decline( $order ) {
		UOCA_Cancellation::decline_request( $order );
	}

	/**
	 * Show the customer's reason in the order editor.
	 *
	 * @param WC_Order $order Order.
	 */
	public function order_request_details( $order ) {
		$reason = (string) $order->get_meta( '_uoca_reason' );
		$notes  = (string) $order->get_meta( '_uoca_notes' );
		$state  = (string) $order->get_meta( '_uoca_request_status' );

		if ( '' === $reason && '' === $notes && '' === $state && '' === (string) $order->get_meta( '_uoca_cancelled_by' ) ) {
			return;
		}

		$states = array(
			UOCA_Cancellation::REQUEST_PENDING  => __( 'Awaiting your decision', 'unstoppable-cancel-orders' ),
			UOCA_Cancellation::REQUEST_APPROVED => __( 'Approved', 'unstoppable-cancel-orders' ),
			UOCA_Cancellation::REQUEST_DECLINED => __( 'Declined', 'unstoppable-cancel-orders' ),
		);
		?>
		<div class="uoca-order-details">
			<h3><?php esc_html_e( 'Customer cancellation', 'unstoppable-cancel-orders' ); ?></h3>
			<?php if ( isset( $states[ $state ] ) ) : ?>
				<p><strong><?php esc_html_e( 'Request:', 'unstoppable-cancel-orders' ); ?></strong> <?php echo esc_html( $states[ $state ] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $reason ) : ?>
				<p><strong><?php esc_html_e( 'Reason:', 'unstoppable-cancel-orders' ); ?></strong> <?php echo esc_html( $reason ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $notes ) : ?>
				<p><strong><?php esc_html_e( 'Notes:', 'unstoppable-cancel-orders' ); ?></strong> <?php echo esc_html( $notes ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Settings tab: one form holding every setting, so nothing is lost on save.
	 */
	private function render_settings_tab() {
		$s      = UOCA_Settings::all();
		$name   = UOCA_Settings::OPTION;
		$fields = array(
			'off'      => __( 'Do not show', 'unstoppable-cancel-orders' ),
			'optional' => __( 'Show, optional', 'unstoppable-cancel-orders' ),
			'required' => __( 'Show, required', 'unstoppable-cancel-orders' ),
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="uoca-settings-form">
			<?php settings_fields( self::SETTINGS_GROUP ); ?>

			<div class="uoca-card">
				<h2><?php esc_html_e( 'General', 'unstoppable-cancel-orders' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Customer cancellations', 'unstoppable-cancel-orders' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> />
								<?php esc_html_e( 'Let customers cancel orders', 'unstoppable-cancel-orders' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'unstoppable-cancel-orders' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Mode', 'unstoppable-cancel-orders' ); ?></legend>
								<label>
									<input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="request" <?php checked( 'request', $s['mode'] ); ?> />
									<?php esc_html_e( 'Request: the customer asks, you approve or decline', 'unstoppable-cancel-orders' ); ?>
								</label><br />
								<label>
									<input type="radio" name="<?php echo esc_attr( $name ); ?>[mode]" value="direct" <?php checked( 'direct', $s['mode'] ); ?> />
									<?php esc_html_e( 'Direct: the order is cancelled immediately', 'unstoppable-cancel-orders' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Requests appear on the Dashboard tab and in the order editor\'s Order actions box. Reduced stock is restored by WooCommerce when an order is cancelled. Refunds are not created automatically.', 'unstoppable-cancel-orders' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uoca-eligible-statuses"><?php esc_html_e( 'Cancellable order statuses', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<select id="uoca-eligible-statuses" name="<?php echo esc_attr( $name ); ?>[eligible_statuses][]" multiple class="wc-enhanced-select uoca-select">
								<?php foreach ( UOCA_Settings::cancellable_status_options() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( in_array( $slug, (array) $s['eligible_statuses'], true ) ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The button only appears on orders in these statuses.', 'unstoppable-cancel-orders' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uoca-eligible-roles"><?php esc_html_e( 'Customer roles', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<select id="uoca-eligible-roles" name="<?php echo esc_attr( $name ); ?>[eligible_roles][]" multiple class="wc-enhanced-select uoca-select" data-placeholder="<?php esc_attr_e( 'All registered customers', 'unstoppable-cancel-orders' ); ?>">
								<?php foreach ( UOCA_Settings::role_options() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( in_array( $slug, (array) $s['eligible_roles'], true ) ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Leave empty to allow every registered customer.', 'unstoppable-cancel-orders' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Guest orders', 'unstoppable-cancel-orders' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[allow_guests]" value="1" <?php checked( ! empty( $s['allow_guests'] ) ); ?> />
								<?php esc_html_e( 'Let guest customers cancel from the order confirmation page', 'unstoppable-cancel-orders' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Order emails', 'unstoppable-cancel-orders' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_in_emails]" value="1" <?php checked( ! empty( $s['show_in_emails'] ) ); ?> />
								<?php esc_html_e( 'Add a cancel link to customer order emails while the order can still be cancelled', 'unstoppable-cancel-orders' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>

			<div class="uoca-card">
				<h2><?php esc_html_e( 'Reasons and notes', 'unstoppable-cancel-orders' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="uoca-reason-field"><?php esc_html_e( 'Reason', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<select id="uoca-reason-field" name="<?php echo esc_attr( $name ); ?>[reason_field]">
								<?php foreach ( $fields as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $s['reason_field'] ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uoca-reasons"><?php esc_html_e( 'Reason choices', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<textarea id="uoca-reasons" name="<?php echo esc_attr( $name ); ?>[reasons]" rows="7" class="large-text"><?php echo esc_textarea( implode( "\n", (array) $s['reasons'] ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One reason per line. Customers pick one.', 'unstoppable-cancel-orders' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="uoca-notes-field"><?php esc_html_e( 'Additional notes', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<select id="uoca-notes-field" name="<?php echo esc_attr( $name ); ?>[notes_field]">
								<?php foreach ( $fields as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $s['notes_field'] ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div class="uoca-card">
				<h2><?php esc_html_e( 'Text', 'unstoppable-cancel-orders' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$this->text_row( 'button_text_direct', __( 'Button (direct mode)', 'unstoppable-cancel-orders' ), $s );
					$this->text_row( 'button_text_request', __( 'Button (request mode)', 'unstoppable-cancel-orders' ), $s );
					$this->text_row( 'modal_title_direct', __( 'Popup title (direct mode)', 'unstoppable-cancel-orders' ), $s );
					$this->text_row( 'modal_title_request', __( 'Popup title (request mode)', 'unstoppable-cancel-orders' ), $s );
					?>
					<tr>
						<th scope="row"><label for="uoca-modal-message"><?php esc_html_e( 'Popup message', 'unstoppable-cancel-orders' ); ?></label></th>
						<td>
							<textarea id="uoca-modal-message" name="<?php echo esc_attr( $name ); ?>[modal_message]" rows="3" class="large-text"><?php echo esc_textarea( $s['modal_message'] ); ?></textarea>
						</td>
					</tr>
				</table>
			</div>

			<div class="uoca-card">
				<h2><?php esc_html_e( 'Data', 'unstoppable-cancel-orders' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'On uninstall', 'unstoppable-cancel-orders' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[remove_data_on_uninstall]" value="1" <?php checked( ! empty( $s['remove_data_on_uninstall'] ) ); ?> />
								<?php esc_html_e( 'Delete this plugin\'s settings when it is deleted', 'unstoppable-cancel-orders' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Orders, order notes and cancellation details stored on orders are never deleted.', 'unstoppable-cancel-orders' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * A text input row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param array  $s     Settings.
	 */
	private function text_row( $key, $label, $s ) {
		$id = 'uoca-' . str_replace( '_', '-', $key );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( UOCA_Settings::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $s[ $key ] ); ?>" class="regular-text" /></td>
		</tr>
		<?php
	}

	/**
	 * Quiet footer links on our own screen.
	 */
	private function render_settings_footer() {
		?>
		<p class="uoca-settings-footer">
			<a href="<?php echo esc_url( 'https://unstoppablesolutions.app/support' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'unstoppable-cancel-orders' ); ?></a>
			|
			<a href="<?php echo esc_url( 'https://unstoppablesolutions.app/plugins/unstoppable-cancel-orders/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Pro add-on', 'unstoppable-cancel-orders' ); ?></a>
		</p>
		<?php
	}
}

