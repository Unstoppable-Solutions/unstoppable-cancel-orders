<?php
/**
 * Settings storage, defaults, sanitizing and migration.
 *
 * @package Unstoppable_Cancel_Orders
 */

defined( 'ABSPATH' ) || exit;

/**
 * All plugin settings live in one option: uoca_settings.
 */
class UOCA_Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'uoca_settings';

	/**
	 * Per-request cache.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array(
			'enabled'                  => 1,
			'mode'                     => 'request',
			'eligible_statuses'        => array( 'processing', 'on-hold' ),
			'eligible_roles'           => array(),
			'allow_guests'             => 1,
			'reason_field'             => 'required',
			'reasons'                  => array(
				__( 'Changed my mind', 'unstoppable-cancel-orders' ),
				__( 'Found a better price', 'unstoppable-cancel-orders' ),
				__( 'Ordered by mistake', 'unstoppable-cancel-orders' ),
				__( 'Delivery time too long', 'unstoppable-cancel-orders' ),
				__( 'Other', 'unstoppable-cancel-orders' ),
			),
			'notes_field'              => 'optional',
			'button_text_direct'       => __( 'Cancel Order', 'unstoppable-cancel-orders' ),
			'button_text_request'      => __( 'Request Cancellation', 'unstoppable-cancel-orders' ),
			'modal_title_direct'       => __( 'Cancel Order', 'unstoppable-cancel-orders' ),
			'modal_title_request'      => __( 'Request Order Cancellation', 'unstoppable-cancel-orders' ),
			'modal_message'            => __( 'Are you sure you want to cancel this order?', 'unstoppable-cancel-orders' ),
			'show_in_emails'           => 1,
			'remove_data_on_uninstall' => 0,
		);

		/**
		 * Filter the default settings. Add-ons can register their own keys here.
		 *
		 * @param array $defaults Default settings.
		 */
		return apply_filters( 'uoca_settings_defaults', $defaults );
	}

	/**
	 * All settings, merged over defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored      = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
		}

		return self::$cache;
	}

	/**
	 * One setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback when the key is unknown.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Clear the per-request cache.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Statuses a customer may cancel from, keyed by slug without "wc-".
	 *
	 * Final and cancellation statuses are excluded.
	 *
	 * @return array
	 */
	public static function cancellable_status_options() {
		$options = array();
		$exclude = array( 'cancelled', 'refunded', 'failed', 'checkout-draft', UOCA_STATUS_PENDING );

		foreach ( wc_get_order_statuses() as $key => $label ) {
			$slug = 0 === strpos( $key, 'wc-' ) ? substr( $key, 3 ) : $key;

			if ( ! in_array( $slug, $exclude, true ) ) {
				$options[ $slug ] = $label;
			}
		}

		return $options;
	}

	/**
	 * Editable roles, keyed by role slug.
	 *
	 * @return array
	 */
	public static function role_options() {
		$roles = array();

		foreach ( wp_roles()->get_names() as $slug => $name ) {
			$roles[ $slug ] = translate_user_role( $name );
		}

		return $roles;
	}

	/**
	 * Sanitize and validate a full settings submission.
	 *
	 * Keys that are not part of this form (for example ones an add-on stores)
	 * are preserved, so saving this form never resets other data.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$current = self::all();
		$clean   = array();

		$clean['enabled']                  = empty( $input['enabled'] ) ? 0 : 1;
		$clean['allow_guests']             = empty( $input['allow_guests'] ) ? 0 : 1;
		$clean['show_in_emails']           = empty( $input['show_in_emails'] ) ? 0 : 1;
		$clean['remove_data_on_uninstall'] = empty( $input['remove_data_on_uninstall'] ) ? 0 : 1;

		$mode          = isset( $input['mode'] ) ? sanitize_key( $input['mode'] ) : '';
		$clean['mode'] = in_array( $mode, array( 'direct', 'request' ), true ) ? $mode : 'request';

		$fields = array( 'off', 'optional', 'required' );

		$reason_field          = isset( $input['reason_field'] ) ? sanitize_key( $input['reason_field'] ) : '';
		$clean['reason_field'] = in_array( $reason_field, $fields, true ) ? $reason_field : 'optional';

		$notes_field          = isset( $input['notes_field'] ) ? sanitize_key( $input['notes_field'] ) : '';
		$clean['notes_field'] = in_array( $notes_field, $fields, true ) ? $notes_field : 'optional';

		$allowed_statuses           = array_keys( self::cancellable_status_options() );
		$statuses                   = isset( $input['eligible_statuses'] ) ? array_map( 'sanitize_key', (array) $input['eligible_statuses'] ) : array();
		$clean['eligible_statuses'] = array_values( array_intersect( $statuses, $allowed_statuses ) );

		$allowed_roles           = array_keys( self::role_options() );
		$roles                   = isset( $input['eligible_roles'] ) ? array_map( 'sanitize_key', (array) $input['eligible_roles'] ) : array();
		$clean['eligible_roles'] = array_values( array_intersect( $roles, $allowed_roles ) );

		$reasons          = isset( $input['reasons'] ) ? self::parse_reasons( $input['reasons'] ) : array();
		$clean['reasons'] = $reasons;

		if ( 'off' !== $clean['reason_field'] && empty( $reasons ) ) {
			add_settings_error( self::OPTION, 'uoca_no_reasons', __( 'Add at least one cancellation reason, or turn the reason field off. The reason field has been turned off.', 'unstoppable-cancel-orders' ), 'warning' );
			$clean['reason_field'] = 'off';
		}

		$text_keys = array( 'button_text_direct', 'button_text_request', 'modal_title_direct', 'modal_title_request' );

		foreach ( $text_keys as $key ) {
			$value         = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			$clean[ $key ] = '' !== $value ? $value : $current[ $key ];
		}

		$message                = isset( $input['modal_message'] ) ? sanitize_textarea_field( $input['modal_message'] ) : '';
		$clean['modal_message'] = '' !== $message ? $message : $current['modal_message'];

		self::flush();

		return array_merge( $current, $clean );
	}

	/**
	 * Turn a textarea value (one reason per line) or an array into a clean list.
	 *
	 * @param string|array $value Raw value.
	 * @return string[]
	 */
	public static function parse_reasons( $value ) {
		$lines   = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
		$reasons = array();

		foreach ( $lines as $line ) {
			$line = sanitize_text_field( $line );

			if ( '' !== $line && ! in_array( $line, $reasons, true ) ) {
				$reasons[] = mb_substr( $line, 0, 120 );
			}
		}

		return array_slice( $reasons, 0, 25 );
	}

	/**
	 * One-time copy of settings from the earlier "wc-order-cancellation" build.
	 *
	 * The old options are copied, not moved: they are left in place so nothing
	 * is lost if the site rolls back.
	 */
	public static function maybe_migrate() {
		if ( get_option( 'uoca_migrated_from_legacy' ) || false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$legacy_enabled = get_option( 'wc_order_cancellation_enabled', null );

		if ( null === $legacy_enabled ) {
			update_option( 'uoca_migrated_from_legacy', 'none', false );
			return;
		}

		$defaults = self::defaults();
		$settings = $defaults;

		$settings['enabled'] = 'yes' === $legacy_enabled ? 1 : 0;

		$mode             = get_option( 'wc_order_cancellation_mode', 'request' );
		$settings['mode'] = in_array( $mode, array( 'direct', 'request' ), true ) ? $mode : 'request';

		$statuses = (array) get_option( 'wc_order_cancellation_eligible_statuses', array() );
		$statuses = array_map(
			function ( $status ) {
				$status = sanitize_key( $status );
				return 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
			},
			$statuses
		);
		if ( ! empty( $statuses ) ) {
			$settings['eligible_statuses'] = array_values( array_unique( $statuses ) );
		}

		$settings['eligible_roles'] = array_values( array_map( 'sanitize_key', (array) get_option( 'wc_order_cancellation_eligible_roles', array() ) ) );
		$settings['allow_guests']   = 'no' === get_option( 'wc_order_cancellation_allow_guests', 'yes' ) ? 0 : 1;

		$reasons = self::parse_reasons( get_option( 'wc_order_cancellation_reasons', '' ) );
		if ( ! empty( $reasons ) ) {
			$settings['reasons'] = $reasons;
		}

		if ( 'yes' !== get_option( 'wc_order_cancellation_reasons_enabled', 'yes' ) ) {
			$settings['reason_field'] = 'off';
		} else {
			$settings['reason_field'] = 'yes' === get_option( 'wc_order_cancellation_reasons_required', 'yes' ) ? 'required' : 'optional';
		}

		$notes                   = get_option( 'wc_order_cancellation_notes_enabled', 'optional' );
		$settings['notes_field'] = 'disabled' === $notes ? 'off' : ( 'required' === $notes ? 'required' : 'optional' );

		$text_map = array(
			'button_text_direct'  => 'wc_order_cancellation_button_text_direct',
			'button_text_request' => 'wc_order_cancellation_button_text_request',
			'modal_title_direct'  => 'wc_order_cancellation_modal_title_direct',
			'modal_title_request' => 'wc_order_cancellation_modal_title_request',
		);

		foreach ( $text_map as $key => $legacy_key ) {
			$value = sanitize_text_field( (string) get_option( $legacy_key, '' ) );
			if ( '' !== $value ) {
				$settings[ $key ] = $value;
			}
		}

		$message = sanitize_textarea_field( (string) get_option( 'wc_order_cancellation_modal_message', '' ) );
		if ( '' !== $message ) {
			$settings['modal_message'] = $message;
		}

		add_option( self::OPTION, $settings );

		// Mark done only once the copy is confirmed.
		if ( get_option( self::OPTION ) === $settings ) {
			update_option( 'uoca_migrated_from_legacy', time(), false );
		}

		self::flush();
	}
}
