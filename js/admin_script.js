( function( $ ) {
	$( document ).ready( function() {
		$( '.rtng_color' ).wpColorPicker();

		$( 'input[name="rtng_combined"]' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#rtng_rate_position' ).hide();
			} else {
				$( '#rtng_rate_position' ).show();
			}
		} ).trigger( 'change' );

		$( 'input[value="in_comment"]' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#rtng_required_rate' ).show();
			} else {
				$( '#rtng_required_rate' ).hide();
			}
		} ).trigger( 'change' );

		$( 'input[name="rtng_add_schema"]' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#rtng_minimun_rating' ).show();
			} else {
				$( '#rtng_minimun_rating' ).hide();
			}
		} ).trigger( 'change' );

		$( '#rtng_rate_position input' ).change( function() {
			if ( $( '#rtng_rate_position input[value="in_comment"]' ).is( ':checked' ) ) {
				$( 'input[name="rtng_check_rating_required"]' ).removeAttr( 'disabled' );
				$( '#rtng_rate_position input[value!="in_comment"]' ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
			} else {
				$( '#rtng_rate_position input' ).removeAttr( 'disabled' );
				$( 'input[name="rtng_check_rating_required"]' ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
			}
		} ).trigger( 'change' );

		$( '#rtng-all-roles' ).on( 'click', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.rtng-role' ).attr( 'checked', 'checked' );
			} else {
				$( '.rtng-role' ).removeAttr( 'checked' );
			}
		} );

		$( '.rtng-role' ).on( 'change', function() {
			var $cb_all = $( '#rtng-all-roles' ),
				$checkboxes = $( '.rtng-role' );
				$enabled_checkboxes = $checkboxes.filter( ':checked' );
			if ( $checkboxes.length > 0 && $checkboxes.length == $enabled_checkboxes.length ) {
				$cb_all.attr( 'checked', 'checked' );
			} else {
				$cb_all.removeAttr( 'checked' );
			}
		} ).trigger( 'change' );

		var testimonials_options = $( '.rtng-show-testimonials' );

		if ( testimonials_options.length > 0 ) {

			var testimonials_input = $( 'input[name="rtng_testimonials"]' );

			testimonials_options.each( function( i, e ) {
				if ( ! testimonials_input.prop( 'checked' ) ) {
					$( e ).hide();
				}
			} );

			testimonials_input.change( function( e ) {
				if ( e.target.checked ) {
					$( '.rtng-show-testimonials' ).show();	
				} else {
					$( '.rtng-show-testimonials' ).hide();
				}
			} );
		}

		$( 'input[name="rtng_options_quantity"]' ).change( function( e ) {
			var 	inputs = $( 'input[name="rtng_testimonials_titles[]"]' ),
					tr = inputs.parents( 'tr' ),
					// numero_sign is № sign in utf-8 hex
					numero_sign = '\u2116',
					value = parseInt( e.target.value ),
					value_min = parseInt( e.target.min ),
					value_max = parseInt( e.target.max );

			if ( value > inputs.length && value <= value_max ) {
				var first_tr = tr.last();
				for ( var i = inputs.length + 1; i <= value; i++ ) {
					var 	last = $( 'input[name="rtng_testimonials_titles[]"]' ).last().parents( 'tr' ),
							input_clone = first_tr.clone();

					input_clone.find( 'th' ).text( first_tr.find( 'th' ).text() + ' ' + numero_sign + ' ' + i );
					input_clone.find( 'input' ).val( '' );
					last.after( input_clone );
				}
			} else if ( value < inputs.length && value >= value_min ) {
				for ( var i = inputs.length; i > value; i-- ) {
					last = $( 'input[name="rtng_testimonials_titles[]"]' ).last().parents( 'tr' ).remove();
				}
			}
		} );
	} );
} )( jQuery );
