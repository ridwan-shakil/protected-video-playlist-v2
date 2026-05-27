( function ( $ ) {
	'use strict';

	$( function () {
		$( document ).on( 'click', '[data-rsplr-settings-tab]', function ( event ) {
			event.preventDefault();

			var tab = $( this ).data( 'rsplr-settings-tab' );

			$( '[data-rsplr-settings-tab]' ).removeClass( 'nav-tab-active' );
			$( this ).addClass( 'nav-tab-active' );

			$( '[data-rsplr-settings-panel]' ).attr( 'hidden', true );
			$( '[data-rsplr-settings-panel="' + tab + '"]' ).removeAttr( 'hidden' );
		} );
	} );
}( jQuery ) );
