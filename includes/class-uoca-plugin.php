<?php
/**
 * Plugin bootstrap.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin once WooCommerce is available.
 */
class UOCA_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var UOCA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether the earlier "wc-order-cancellation" plugin is still active.
	 *
	 * @var bool
	 */
	private $legacy_active = false;

	/**
	 * Get the instance.
	 *
	 * @return UOCA_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// The earlier build (Free 1.x or Pro 2.x) uses the WC_Order_Cancellation class.
		$this->legacy_active = class_exists( 'WC_Order_Cancellation', false );

		UOCA_Settings::maybe_migrate();

		$this->includes();

		add_action( 'init', array( $this, 'register_order_status' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_order_status' ) );
		add_filter( 'plugin_action_links_' . UOCA_PLUGIN_BASENAME, array( $this, 'action_links' ) );
		add_filter( 'unstoppable_studio_registered_plugins', array( $this, 'register_with_studio' ) );

		new UOCA_Admin( $this->legacy_active );
		new UOCA_Admin_Notices();
		new UOCA_Emails();

		// Avoid two sets of cancel buttons while the earlier plugin is still active.
		if ( ! $this->legacy_active ) {
			new UOCA_Cancellation();
			new UOCA_Frontend();
		}
	}

	/**
	 * Load class files.
	 */
	private function includes() {
		require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-cancellation.php';
		require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-frontend.php';
		require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-admin.php';
		require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-admin-notices.php';
		require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-emails.php';
	}

	/**
	 * Activation: never overwrites stored data.
	 */
	public static function activate() {
		UOCA_Settings::maybe_migrate();

		if ( false === get_option( 'uoca_install_date', false ) ) {
			add_option( 'uoca_install_date', time(), '', false );
		}
	}

	/**
	 * Register the "Pending Cancellation" order status.
	 */
	public function register_order_status() {
		register_post_status(
			'wc-' . UOCA_STATUS_PENDING,
			array(
				'label'                     => _x( 'Pending Cancellation', 'Order status', 'unstoppable-cancel-orders' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Pending Cancellation <span class="count">(%s)</span>', 'Pending Cancellation <span class="count">(%s)</span>', 'unstoppable-cancel-orders' ),
			)
		);
	}

	/**
	 * Add the status to WooCommerce's list, after "On hold".
	 *
	 * @param array $statuses Order statuses.
	 * @return array
	 */
	public function add_order_status( $statuses ) {
		$new = array();

		foreach ( $statuses as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'wc-on-hold' === $key ) {
				$new[ 'wc-' . UOCA_STATUS_PENDING ] = _x( 'Pending Cancellation', 'Order status', 'unstoppable-cancel-orders' );
			}
		}

		if ( ! isset( $new[ 'wc-' . UOCA_STATUS_PENDING ] ) ) {
			$new[ 'wc-' . UOCA_STATUS_PENDING ] = _x( 'Pending Cancellation', 'Order status', 'unstoppable-cancel-orders' );
		}

		return $new;
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param array $links Action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . UOCA_MENU_SLUG . '&tab=settings' ) ),
			esc_html__( 'Settings', 'unstoppable-cancel-orders' )
		);

		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * Register on the Options Studio Dashboard.
	 *
	 * @param array $plugins Registered plugins.
	 * @return array
	 */
	public function register_with_studio( $plugins ) {
		$plugins['unstoppable-cancel-orders'] = array(
			'name'        => __( 'Order Cancellation', 'unstoppable-cancel-orders' ),
			'description' => __( 'Let customers cancel orders, or request cancellation for your approval.', 'unstoppable-cancel-orders' ),
			'menu_slug'   => UOCA_MENU_SLUG,
			'version'     => UOCA_VERSION,
		);

		return $plugins;
	}

	/**
	 * WooCommerce is required.
	 */
	public function woocommerce_missing_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! current_user_can( 'activate_plugins' ) || ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Unstoppable Cancel Orders for WooCommerce requires WooCommerce to be installed and active.', 'unstoppable-cancel-orders' ); ?></p>
		</div>
		<?php
	}
}
