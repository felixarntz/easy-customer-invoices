( function( $, wp, settings ) {

	$( document ).ready( function() {

		function get_invoice( id, callback ) {
			$.ajax({
				url: settings.api_root + 'wpeci/invoices/' + id,
				method: 'GET',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', settings.api_nonce );
				},
				dataType: 'json',
				success: function( result ) {
					callback( result );
				}
			});
		}

		function get_customer( id, callback ) {
			$.ajax({
				url: settings.api_root + 'wpeci/customers/' + id,
				method: 'GET',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', settings.api_nonce );
				},
				dataType: 'json',
				success: function( result ) {
					callback( result );
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
			if ( ! value || 0 === value || '0' === value ) {
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
		function showhide_fees() {
			var value = $payment_method.val();
			if ( 'paypal' === value ) {
				$( '#paypal_fee_amount' ).parents( 'tr' ).show();
			} else {
				$( '#paypal_fee_amount' ).parents( 'tr' ).hide();
			}
			if ( 'deposit' === value ) {
				$( '#deposit_fee_amount' ).parents( 'tr' ).show();
			} else {
				$( '#deposit_fee_amount' ).parents( 'tr' ).hide();
			}
		}
		if ( 0 < $payment_method.length ) {
			showhide_fees();
			$payment_method.on( 'change', showhide_fees );
		}

		$( document ).on( 'change', '#contents .wpdlib-input-select', function( e ) {
			var $this = $( this );

			var value = $this.val();
			var label = '';
			$this.children( 'option' ).each( function() {
				if ( $( this ).val() === value ) {
					label = $( this ).text();
				}
			});

			var results = label.match( /\(([0-9\.,]+)/ );

			if ( results && results.length && results[1] ) {
				var amount = parseFloat( results[1] );
				$( '#' + $this.attr( 'id' ).replace( '-effort', '-amount' ) ).val( amount );
			}
		});

	});

})( jQuery, wp, wpeci_settings );
