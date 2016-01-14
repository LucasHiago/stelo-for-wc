/* global stelo_wc_credit_card_params, Stelo */
/*jshint devel: true */
(function( $ ) {
	'use strict';

	$( function() {

		var stelo_submit = false;

		/**
		 * Process the credit card data when submit the checkout form.
		 */
		Stelo.setAccountID( stelo_wc_credit_card_params.account_id );

		if ( 'yes' === stelo_wc_credit_card_params.is_sandbox ) {
			Stelo.setTestMode( true );
		}

		$( 'form.checkout' ).on( 'checkout_place_order_stelo-credit-card', function() {
			return formHandler( this );
		});

		$( 'form#order_review' ).submit( function() {
			return formHandler( this );
		});

		$( 'body' ).on( 'checkout_error', function () {
			$( '.stelo-token' ).remove();
		});
		$( 'form.checkout, form#order_review' ).on( 'change', '#stelo-credit-card-fields input', function() {
			$( '.stelo-token' ).remove();
		});

		/**
		 * Form Handler.
		 *
		 * @param  {object} form
		 *
		 * @return {bool}
		 */
		function formHandler( form ) {
			if ( stelo_submit ) {
				stelo_submit = false;

				return true;
			}

			if ( ! $( '#payment_method_stelo-credit-card' ).is( ':checked' ) ) {
				return true;
			}

			var $form          = $( form ),
				cardExpiry     = $form.find( '#stelo-card-expiry' ).val().replace( ' ', '' ),
				creditCardForm = $( '#stelo-credit-card-fields', $form ),
				errorHtml      = '';

			// Fixed card expiry for stelo.
			$form.find( '#stelo-card-expiry' ).val( cardExpiry );

			Stelo.createPaymentToken( form, function( data ) {
				if ( data.errors ) {

					$( '.woocommerce-error', creditCardForm ).remove();

					errorHtml += '<ul>';
					$.each( data.errors, function ( key, value ) {
						var errorMessage = value;

						if ( 'is_invalid' === errorMessage ) {
							errorMessage = stelo_wc_credit_card_params.i18n_is_invalid;
						}

						errorHtml += '<li>' + stelo_wc_credit_card_params[ 'i18n_' + key + '_field' ] + ' ' + errorMessage + '.</li>';
					});
					errorHtml += '</ul>';

					creditCardForm.prepend( '<div class="woocommerce-error">' + errorHtml + '</div>' );
				} else {
					// Remove any old hash input.
					$( '.stelo-token', $form ).remove();

					// Add the hash input.
					$form.append( $( '<input class="stelo-token" name="stelo_token" type="hidden" />' ).val( data.id ) );

					// Submit the form.
					stelo_submit = true;
					$form.submit();
				}
			});

			return false;
		}
	});

}( jQuery ));
