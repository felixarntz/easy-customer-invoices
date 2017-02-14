( function( $, c3, d3, data ) {

	if ( ! data ) {
		return;
	}

	var columns = [];

	var field_slugs = Object.keys( data.fields );
	for ( var i in field_slugs ) {
		columns.push( [ data.fields[ field_slugs[ i ] ] ] );
	}

	var categories = [];

	for ( var j in data.results ) {
		var result = data.results[ j ];

		for ( var k in field_slugs ) {
			if ( result[ field_slugs[ k ] ] ) {
				columns[ k ].push( result[ field_slugs[ k ] ] );
			} else {
				columns[ k ].push( 0.0 );
			}
		}

		categories.push( result.label );
	}

	var chart = c3.generate({
		bindto: '#chart',
		data: {
			columns: columns
		},
		axis: {
			x: {
				type: 'category',
				categories: categories,
				label: {
					text: data.result_label,
					position: 'outer-center'
				}
			},
			y: {
				label: {
					text: data.amount_label,
					position: 'outer-middle'
				},
				tick: {
					format: d3.format( ', ' + data.currency )
				}
			}
		}
	});

})( jQuery, c3, d3, window.eci_stats_data );
