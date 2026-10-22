/**
 * Unstoppable Cancel Orders: cancellation modal.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.uocaFrontend === 'undefined' ) {
		return;
	}

	var config = window.uocaFrontend;
	var $modal = null;
	var $lastTrigger = null;
	var current = { orderId: 0, orderKey: '' };
	var reloadOnClose = false;

	function modal() {
		if ( ! $modal || ! $modal.length ) {
			$modal = $( '#uoca-modal' );
		}
		return $modal;
	}

	function showError( message ) {
		modal().find( '.uoca-error' ).text( message ).prop( 'hidden', false );
	}

	function clearError() {
		modal().find( '.uoca-error' ).text( '' ).prop( 'hidden', true );
	}

	function openModal( orderId, orderKey, $trigger ) {
		var $m = modal();

		if ( ! $m.length || ! orderId ) {
			return false;
		}

		current.orderId = orderId;
		current.orderKey = orderKey || '';
		$lastTrigger = $trigger || null;

		$m.find( 'form' )[ 0 ].reset();
		$m.find( 'form' ).prop( 'hidden', false );
		$m.find( '.uoca-done' ).prop( 'hidden', true );
		clearError();
		$m.prop( 'hidden', false );
		$( 'body' ).addClass( 'uoca-modal-open' );
		$m.find( 'input, textarea, button' ).filter( ':visible' ).first().trigger( 'focus' );

		return true;
	}

	function closeModal() {
		if ( reloadOnClose ) {
			// Reload so the order status and buttons reflect the change.
			window.location.href = window.location.href.replace( /([?&])uoca_order=\d+&?/, '$1' ).replace( /[?&]$/, '' );
			return;
		}

		modal().prop( 'hidden', true );
		$( 'body' ).removeClass( 'uoca-modal-open' );

		if ( $lastTrigger ) {
			$lastTrigger.trigger( 'focus' );
		}
	}

	function orderIdFromUrl( href ) {
		var match = /[?&]uoca_order=(\d+)/.exec( href || '' );
		return match ? parseInt( match[ 1 ], 10 ) : 0;
	}

	// Button on the order page.
	$( document ).on( 'click', '.uoca-cancel-button', function ( event ) {
		var $button = $( this );
		event.preventDefault();
		openModal( parseInt( $button.data( 'order-id' ), 10 ), $button.data( 'order-key' ), $button );
	} );

	// Link in the My Account orders table. Falls back to the order page if the modal can't open.
	$( document ).on( 'click', 'a.uoca-cancel', function ( event ) {
		var $link = $( this );

		if ( openModal( orderIdFromUrl( $link.attr( 'href' ) ), '', $link ) ) {
			event.preventDefault();
		}
	} );

	$( document ).on( 'click', '[data-uoca-close]', function ( event ) {
		event.preventDefault();
		closeModal();
	} );

	$( document ).on( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && ! modal().prop( 'hidden' ) ) {
			closeModal();
		}
	} );

	$( document ).on( 'submit', '#uoca-form', function ( event ) {
		var $form = $( this );
		var $submit = $form.find( '.uoca-submit-button' );
		var reason = $form.find( 'input[name="uoca_reason"]:checked' ).val() || '';
		var $notes = $form.find( 'textarea[name="uoca_notes"]' );
		var notes = $notes.length ? $.trim( $notes.val() ) : '';

		event.preventDefault();
		clearError();

		if ( 'required' === config.reasonField && $form.find( 'input[name="uoca_reason"]' ).length && ! reason ) {
			showError( config.i18n.reasonRequired );
			return;
		}

		if ( 'required' === config.notesField && $notes.length && ! notes ) {
			showError( config.i18n.notesRequired );
			return;
		}

		if ( ! $submit.data( 'label' ) ) {
			$submit.data( 'label', $submit.text() );
		}

		$submit.prop( 'disabled', true ).text( config.i18n.processing );

		$.post( config.ajaxUrl, {
			action: 'uoca_cancel_order',
			nonce: config.nonce,
			order_id: current.orderId,
			order_key: current.orderKey,
			reason: reason,
			notes: notes
		} )
			.done( function ( response ) {
				if ( response && response.success ) {
					reloadOnClose = true;
					$form.prop( 'hidden', true );
					modal().find( '.uoca-success' ).text( response.data && response.data.message ? response.data.message : '' );
					modal().find( '.uoca-done' ).prop( 'hidden', false ).find( '.uoca-done-button' ).trigger( 'focus' );
					return;
				}

				showError( response && response.data && response.data.message ? response.data.message : config.i18n.error );
				$submit.prop( 'disabled', false ).text( $submit.data( 'label' ) );
			} )
			.fail( function ( xhr ) {
				var json = xhr && xhr.responseJSON;
				showError( json && json.data && json.data.message ? json.data.message : config.i18n.error );
				$submit.prop( 'disabled', false ).text( $submit.data( 'label' ) );
			} );
	} );

	// Arriving from an orders-table link without JavaScript having intercepted it.
	$( function () {
		var orderId = orderIdFromUrl( window.location.search );
		var $button = $( '.uoca-cancel-button[data-order-id="' + orderId + '"]' );

		if ( orderId && $button.length ) {
			openModal( orderId, $button.data( 'order-key' ), $button );
		}
	} );
}( jQuery ) );
