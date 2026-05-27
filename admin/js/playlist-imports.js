( function ( $ ) {
	'use strict';

	function setStatus( message ) {
		$( '#rsplr-import-status' ).text( message );
	}

	function continueImport( playlistPostId ) {
		$.post( RSPLRPlaylistImports.ajaxUrl, {
			action: 'rsplr_continue_playlist_import',
			nonce: RSPLRPlaylistImports.nonce,
			playlist_post_id: playlistPostId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				setStatus( response && response.data && response.data.message ? response.data.message : RSPLRPlaylistImports.i18n.failed );
				$( '#rsplr-import-playlist-button' ).prop( 'disabled', false );
				return;
			}

			var data = response.data;
			setStatus( RSPLRPlaylistImports.i18n.running + ' ' + data.imported_count + '/' + data.total_videos );

			if ( data.done ) {
				setStatus( data.status === 'failed' ? RSPLRPlaylistImports.i18n.failed : RSPLRPlaylistImports.i18n.complete );
				window.location.reload();
				return;
			}

			window.setTimeout( function () {
				continueImport( playlistPostId );
			}, 350 );
		} ).fail( function () {
			setStatus( RSPLRPlaylistImports.i18n.failed );
			$( '#rsplr-import-playlist-button' ).prop( 'disabled', false );
		} );
	}

	$( function () {
		$( '#rsplr-playlist-import-form' ).on( 'submit', function ( event ) {
			event.preventDefault();

			var $button = $( '#rsplr-import-playlist-button' );
			$button.prop( 'disabled', true );
			setStatus( RSPLRPlaylistImports.i18n.starting );

			$.post( RSPLRPlaylistImports.ajaxUrl, {
				action: 'rsplr_start_playlist_import',
				nonce: RSPLRPlaylistImports.nonce,
				playlist_name: $( '#rsplr-playlist-name' ).val(),
				playlist_url: $( '#rsplr-playlist-url' ).val()
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					setStatus( response && response.data && response.data.message ? response.data.message : RSPLRPlaylistImports.i18n.failed );
					$button.prop( 'disabled', false );
					return;
				}

				continueImport( response.data.id );
			} ).fail( function () {
				setStatus( RSPLRPlaylistImports.i18n.failed );
				$button.prop( 'disabled', false );
			} );
		} );
	} );
}( jQuery ) );
