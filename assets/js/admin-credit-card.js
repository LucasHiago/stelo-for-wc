/*jshint devel: true */
(function( $ ) {
	'use strict';

	$( function() {
		$( '#woocommerce_stelo-credit-card_pass_interest' ).on( 'change', function() {
			var fields = $( '#woocommerce_stelo-credit-card_free_interest, #woocommerce_stelo-credit-card_transaction_rate' ).closest( 'tr' );

			if ( $( this ).is( ':checked' ) ) {
				fields.show();
			} else {
				fields.hide();
			}

		}).change();
	});

}( jQuery ));
