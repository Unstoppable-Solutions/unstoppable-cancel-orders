<?php
/**
 * Admin notices, shown on this plugin's own screen only.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Welcome notice and one-time flash messages.
 */
class UOCA_Admin_Notices {

	/**
	 * Option/transient prefix.
	 */
	const PREFIX = 'uoca';

	/**
	 * Notices that may be dismissed.
	 *
	 * @var string[]
	 */
	const DISMISSIBLE = array( 'welcome' );

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		add_action( 'wp_ajax_uoca_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Is the current screen our plugin's page?
	 *
	 * @return bool
	 */
	private function is_own_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && false !== strpos( $screen->id, UOCA_MENU_SLUG );
	}

	/**
	 * Dismiss script on our screen only.
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_own_screen() ) {
			return;
		}

		wp_enqueue_script( 'uoca-admin-notices', UOCA_PLUGIN_URL . 'assets/js/admin-notices.js', array( 'jquery' ), UOCA_VERSION, true );

		wp_localize_script(
			'uoca-admin-notices',
			'uocaNotices',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'uoca_dismiss_notice' ),
			)
		);
	}

	/**
	 * Print notices.
	 */
	public function display_notices() {
		if ( ! $this->is_own_screen() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$this->maybe_show_welcome_notice();
		$this->display_flash_notices();

		/**
		 * Lets add-ons print notices on this screen.
		 */
		do_action( 'uoca_admin_notices' );
	}

	/**
	 * One-time welcome notice.
	 */
	private function maybe_show_welcome_notice() {
		if ( get_option( self::PREFIX . '_welcome_notice_dismissed' ) ) {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible uoca-notice" data-notice="welcome">
			<p>
				<strong><?php esc_html_e( 'Unstoppable Cancel Orders', 'unstoppable-cancel-orders' ); ?></strong> &mdash;
				<?php esc_html_e( 'Thanks for installing! Choose which orders customers can cancel on the Settings tab.', 'unstoppable-cancel-orders' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . UOCA_MENU_SLUG . '&tab=settings' ) ); ?>" class="button button-primary uoca-notice-button">
					<?php esc_html_e( 'Go to Settings', 'unstoppable-cancel-orders' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * One-time messages queued by add_flash_notice().
	 */
	private function display_flash_notices() {
		$key     = self::PREFIX . '_flash_notices_' . get_current_user_id();
		$notices = get_transient( $key );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		delete_transient( $key );

		foreach ( $notices as $notice ) {
			if ( empty( $notice['message'] ) ) {
				continue;
			}

			$type = isset( $notice['type'] ) && in_array( $notice['type'], array( 'success', 'error', 'warning', 'info' ), true )
				? $notice['type']
				: 'info';

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				wp_kses_post( $notice['message'] )
			);
		}
	}

	/**
	 * Queue a one-time notice for the current user's next page load.
	 *
	 * @param string $message Message.
	 * @param string $type    success|error|warning|info.
	 */
	public static function add_flash_notice( $message, $type = 'success' ) {
		$key     = self::PREFIX . '_flash_notices_' . get_current_user_id();
		$notices = get_transient( $key );
		$notices = is_array( $notices ) ? $notices : array();

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( $key, $notices, MINUTE_IN_SECONDS );
	}

	/**
	 * AJAX: remember a dismissal.
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'uoca_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( null, 403 );
		}

		$notice = isset( $_POST['notice'] ) ? sanitize_key( wp_unslash( $_POST['notice'] ) ) : '';

		if ( ! in_array( $notice, self::DISMISSIBLE, true ) ) {
			wp_send_json_error( null, 400 );
		}

		update_option( self::PREFIX . '_' . $notice . '_notice_dismissed', 1, false );

		wp_send_json_success();
	}
}
