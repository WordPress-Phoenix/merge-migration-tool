(function ( $ ) {
	$( document ).ready( function () {
		$( '.button-next' ).on( 'click', function () {
			console.log( 'testing' );
			$( '.mmt-content' ).block( {
				message   : null,
				overlayCSS: {
					background: '#fff',
					opacity   : 0.6
				}
			} );
			return true;
		} );
	} );
})( jQuery );
