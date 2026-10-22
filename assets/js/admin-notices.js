/**
 * Unstoppable Cancel Orders: remember dismissed notices.
 */
( function ( $ ) {
	'use strict';

	if ( typeof window.uocaNotices === 'undefined' ) {
		return;
	}

	$( document ).on( 'click', '.uoca-notice .notice-dismiss', function () {
		var $notice = $( this ).closest( '.uoca-notice' );

		$.post( window.uocaNotices.ajaxUrl, {
			action: 'uoca_dismiss_notice',
			nonce: window.uocaNotices.nonce,
			notice: $notice.data( 'notice' )
		} );
	} );
}( jQuery ) );
