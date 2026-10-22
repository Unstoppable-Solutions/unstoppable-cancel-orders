<?php
/**
 * Plugin Name:          Unstoppable Cancel Orders for WooCommerce
 * Plugin URI:           https://unstoppablesolutions.app/plugins/unstoppable-cancel-orders/
 * Description:          Let customers cancel eligible WooCommerce orders from My Account, or send a cancellation request for you to approve or decline.
 * Version:              2.0.0
 * Author:               Unstoppable Solutions
 * Author URI:           https://unstoppablesolutions.app
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          unstoppable-cancel-orders
 * Domain Path:          /languages
 * Requires at least:    6.5
 * Tested up to:         7.1
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * WC requires at least: 7.0
 * WC tested up to:      11.2
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/*
 * ============================================================================
 * UNSTOPPABLE OPTIONS STUDIO - SHARED SHELL (v1.2.0)
 * ============================================================================
 * Shared by every Unstoppable Solutions plugin. The first plugin to load
 * defines these functions; the function_exists() guard makes the others skip
 * them.
 * ============================================================================
 */

if ( ! function_exists( 'unstoppable_options_studio_init' ) ) {

	/**
	 * Register the shell hooks.
	 */
	function unstoppable_options_studio_init() {
		add_action( 'admin_menu', 'unstoppable_options_studio_create_menu', 9 );
		add_action( 'admin_enqueue_scripts', 'unstoppable_options_studio_admin_css' );
		add_action( 'admin_bar_menu', 'unstoppable_options_studio_admin_bar', 100 );
		// The admin bar appears in the admin and on the front end.
		add_action( 'admin_enqueue_scripts', 'unstoppable_options_studio_admin_bar_css' );
		add_action( 'wp_enqueue_scripts', 'unstoppable_options_studio_admin_bar_css' );
	}

	/**
	 * Create the Options Studio parent menu and its Dashboard page.
	 */
	function unstoppable_options_studio_create_menu() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// An older copy of the shell in another plugin may already have created it.
		global $menu;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'unstoppable-studio' === $item[2] ) {
					return;
				}
			}
		}

		add_menu_page(
			esc_html__( 'Options Studio', 'unstoppable-cancel-orders' ),
			esc_html__( 'Options Studio', 'unstoppable-cancel-orders' ),
			'manage_woocommerce',
			'unstoppable-studio',
			'unstoppable_options_studio_dashboard',
			'dashicons-screenoptions',
			56
		);

		add_submenu_page(
			'unstoppable-studio',
			esc_html__( 'Dashboard', 'unstoppable-cancel-orders' ),
			esc_html__( 'Dashboard', 'unstoppable-cancel-orders' ),
			'manage_woocommerce',
			'unstoppable-studio',
			'unstoppable_options_studio_dashboard'
		);
	}

	/**
	 * Render the Options Studio Dashboard.
	 */
	function unstoppable_options_studio_dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-cancel-orders' ) );
		}

		$plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );
		?>
		<div class="wrap unstoppable-studio-dashboard">
			<h1><?php esc_html_e( 'Options Studio', 'unstoppable-cancel-orders' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Your suite of WooCommerce tools by Unstoppable Solutions.', 'unstoppable-cancel-orders' ); ?></p>

			<div class="unstoppable-studio-plugins">
				<?php if ( empty( $plugins ) || ! is_array( $plugins ) ) : ?>
					<p><?php esc_html_e( 'No plugins registered yet.', 'unstoppable-cancel-orders' ); ?></p>
				<?php else : ?>
					<?php foreach ( $plugins as $plugin ) : ?>
						<div class="unstoppable-plugin-card">
							<h3><?php echo esc_html( isset( $plugin['name'] ) ? $plugin['name'] : '' ); ?></h3>
							<p><?php echo esc_html( isset( $plugin['description'] ) ? $plugin['description'] : '' ); ?></p>
							<?php if ( ! empty( $plugin['menu_slug'] ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . sanitize_key( $plugin['menu_slug'] ) ) ); ?>" class="button button-primary">
									<?php esc_html_e( 'Open', 'unstoppable-cancel-orders' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="unstoppable-studio-footer">
				<p>
					<a href="<?php echo esc_url( 'https://unstoppablesolutions.app' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit Unstoppable Solutions', 'unstoppable-cancel-orders' ); ?></a>
					|
					<a href="<?php echo esc_url( 'https://unstoppablesolutions.app/support' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Support', 'unstoppable-cancel-orders' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Admin styles: gold menu icon and Dashboard cards.
	 *
	 * An empty registered handle plus wp_add_inline_style() is the supported
	 * way to attach small generated CSS. Never print a <style> tag.
	 */
	function unstoppable_options_studio_admin_css() {
		wp_register_style( 'unstoppable-studio-admin', false, array(), '1.2.0' );
		wp_enqueue_style( 'unstoppable-studio-admin' );

		$css = '
			#adminmenu .toplevel_page_unstoppable-studio .wp-menu-image:before { color: #f0b849; }
			#adminmenu .toplevel_page_unstoppable-studio:hover .wp-menu-image:before,
			#adminmenu .toplevel_page_unstoppable-studio.current .wp-menu-image:before { color: #fff; }
			.unstoppable-studio-plugins { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
			.unstoppable-plugin-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px; max-width: 100%; box-sizing: border-box; }
			.unstoppable-plugin-card h3 { margin-top: 0; }
			.unstoppable-plugin-card p { color: #646970; }
			.unstoppable-studio-footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccd0d4; }
		';

		wp_add_inline_style( 'unstoppable-studio-admin', $css );
	}

	/**
	 * Add Options Studio to the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	function unstoppable_options_studio_admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'unstoppable-studio',
				'title' => '<span class="ab-icon dashicons dashicons-screenoptions" aria-hidden="true"></span>' . esc_html__( 'Options Studio', 'unstoppable-cancel-orders' ),
				'href'  => admin_url( 'admin.php?page=unstoppable-studio' ),
				'meta'  => array(
					'title' => esc_attr__( 'Options Studio Dashboard', 'unstoppable-cancel-orders' ),
				),
			)
		);

		$plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );

		if ( ! is_array( $plugins ) ) {
			return;
		}

		foreach ( $plugins as $plugin_id => $plugin ) {
			if ( empty( $plugin['menu_slug'] ) || empty( $plugin['name'] ) ) {
				continue;
			}

			$wp_admin_bar->add_node(
				array(
					'id'     => 'unstoppable-studio-' . sanitize_key( $plugin_id ),
					'parent' => 'unstoppable-studio',
					'title'  => esc_html( $plugin['name'] ),
					'href'   => admin_url( 'admin.php?page=' . sanitize_key( $plugin['menu_slug'] ) ),
				)
			);
		}
	}

	/**
	 * Admin bar icon styles, enqueued in the admin and on the front end.
	 */
	function unstoppable_options_studio_admin_bar_css() {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_register_style( 'unstoppable-studio-adminbar', false, array( 'admin-bar' ), '1.2.0' );
		wp_enqueue_style( 'unstoppable-studio-adminbar' );

		$css = '
			#wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon:before { content: "\f180"; top: 2px; }
			#wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon { margin-right: 6px; }
			#wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item { color: #f0b849; }
			#wpadminbar #wp-admin-bar-unstoppable-studio:hover > .ab-item { color: #fff; background: #32373c; }
		';

		wp_add_inline_style( 'unstoppable-studio-adminbar', $css );
	}

	unstoppable_options_studio_init();
}

/*
 * ============================================================================
 * END SHARED SHELL
 * ============================================================================
 */

define( 'UOCA_VERSION', '2.0.0' );
define( 'UOCA_PLUGIN_FILE', __FILE__ );
define( 'UOCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UOCA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UOCA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'UOCA_MENU_SLUG', 'uoca-settings' );

/**
 * Order status (without the "wc-" prefix) for orders awaiting a cancellation decision.
 *
 * The slug is kept from earlier releases so existing orders keep their status.
 */
define( 'UOCA_STATUS_PENDING', 'pending-cancel' );

require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-settings.php';
require_once UOCA_PLUGIN_DIR . 'includes/class-uoca-plugin.php';

register_activation_hook( __FILE__, array( 'UOCA_Plugin', 'activate' ) );

add_action( 'plugins_loaded', array( 'UOCA_Plugin', 'instance' ) );

// Declared early, before WooCommerce initialises its features.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', UOCA_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', UOCA_PLUGIN_FILE, true );
		}
	}
);
