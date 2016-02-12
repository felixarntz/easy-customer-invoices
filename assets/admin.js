( function( $, wp, settings ) {

	$( document ).ready( function() {

		function get_invoice( id, callback ) {
			$.ajax({
				url: settings.api_root + 'wpeci/invoices/' + id,
				method: 'GET',
				data: {
					_wpnonce: settings.api_nonce
				},
				dataType: 'json',
				success: function( result ) {
					if ( result.success ) {
						callback( result.data );
					}
				}
			});
		}

		function get_customer( id, callback ) {
			$.ajax({
				url: settings.api_root + 'wpeci/customers/' + id,
				method: 'GET',
				data: {
					_wpnonce: settings.api_nonce
				},
				dataType: 'json',
				success: function( result ) {
					if ( result.success ) {
						callback( result.data );
					}
				}
			});
		}

		function get_invoice_id( callback ) {
			wp.ajax.post( 'wpeci_make_invoice_id', {
				nonce: settings.ajax_nonce
			}).done( function( response ) {
				callback( response );
			}).fail( function( message ) {

			});
		}
		if ( 'eci_invoice' === $( '#post_type' ).val() ) {
			$( '#title' ).prop( 'readonly', true );
			if ( ! $( '#title' ).val() ) {
				get_invoice_id( function( invoice_id ) {
					$( '#title' ).val( invoice_id );
					$( '#title-prompt-text' ).addClass( 'screen-reader-text' );
				});
			}
		}

		function get_customer_id( callback ) {
			var first_name = $( '#first_name' ).val();
			var last_name = $( '#last_name' ).val();
			var customer_id = '';
			if ( $( '#title' ).val() && $( '#title' ).val() !== $( '#title-prompt-text' ).text() ) {
				customer_id = $( '#title' ).val();
			}

			if ( ! first_name || ! last_name ) {
				return;
			}

			wp.ajax.post( 'wpeci_make_customer_id', {
				nonce: settings.ajax_nonce,
				first_name: first_name,
				last_name: last_name,
				old_id: customer_id
			}).done( function( response ) {
				callback( response );
			}).fail( function( message ) {

			});
		}
		if ( 'eci_customer' === $( '#post_type' ).val() ) {
			$( '#title' ).prop( 'readonly', true );
			$( '#first_name' ).on( 'change', function() {
				get_customer_id( function( customer_id ) {
					$( '#title' ).val( customer_id );
					$( '#title-prompt-text' ).addClass( 'screen-reader-text' );
				});
			});
			$( '#last_name' ).on( 'change', function() {
				get_customer_id( function( customer_id ) {
					$( '#title' ).val( customer_id );
					$( '#title-prompt-text' ).addClass( 'screen-reader-text' );
				});
			});
		}

		var $customer = $( 'select#customer' );
		function showhide_currency_factor() {
			var value = $customer.val();
			if ( ! value ) {
				$( '#currency_factor' ).parents( 'tr' ).hide();
				return;
			}
			get_customer( value, function( customer ) {
				if ( settings.currency !== customer.country.currency ) {
					$( '#currency_factor' ).parents( 'tr' ).show();
				} else {
					$( '#currency_factor' ).parents( 'tr' ).hide();
				}
			});
		}
		if ( 0 < $customer.length ) {
			showhide_currency_factor();
			$customer.on( 'change', showhide_currency_factor );
		}

		var $payment_method = $( 'select#payment_method' );
		function showhide_paypal_fees() {
			var value = $payment_method.val();
			if ( 'paypal' === value ) {
				$( '#paypal_fee_amount' ).parents( 'tr' ).show();
			} else {
				$( '#paypal_fee_amount' ).parents( 'tr' ).hide();
			}
		}
		if ( 0 < $payment_method.length ) {
			showhide_paypal_fees();
			$payment_method.on( 'change', showhide_paypal_fees );
		}

	});

})( jQuery, wp, wpeci_settings );