( function( $ ) {
	$( document ).ready( function() {
		$( '.rtng-star-rating' ).removeClass( 'rtng-no-js' );
		$( '.rtng-add-button' ).hide();

		$( document ).on( 'change', '.rtng-star-rating.rtng-active .rtng-star input', function() {
			var form = $( this ).closest( '.rtng-form' );
			var val = $( this ).val(),
				object_id = form.find( 'input[name="rtng_object_id"]' ).val(),
				post_id = form.data( 'id' ),
				object_type = form.find( 'input[name="rtng_object_type"]' ).val();

			form.find( '.rtng-star-rating' ).html( '<span class="rtng-loading"></span>' );

			$.ajax( {
				url: rtng_vars.ajaxurl,
				data: {
					action: 'rtng_add_rating_db',
					rtng_rating_val: val,
					rtng_object_id: object_id,
					rtng_post_id: post_id,
					rtng_nonce: rtng_vars.nonce
				},
				type: 'POST',
				success: function( data ) {

					$( '.rtng-rating-total[data-id="' + post_id + '"]' ).each( function() {
						if ( $( this ).next( 'form.rtng-form' ).length > 0 ) {
							$( this ).next( 'form.rtng-form' ).remove();
							$( this ).html( data );							
						} else {
							$( this ).html( data ).find( '.rtng-form' ).remove();
						}
					});

					$( 'form.rtng-form[data-id="' + post_id + '"]' ).each( function() {
						if ( $( this ).prev( '.rtng-rating-total' ).length == 0 ) {
							$( this ).html( data ).find( '.rtng-rating-total' ).remove();					
						}
					});
					
					setTimeout( function() {
						$( '.rtng-rating-total[data-id="' + post_id + '"]' ).find( '.rtng-thankyou' ).remove();						
					}, 5000 );						
				}
			} );
		});
		$( document ).on( 'mouseenter', '.rtng-star-rating.rtng-active .rtng-star', function() {
			$( this )
				.nextUntil( $( this ), '.rtng-star' )
				.children( 'span' )
				.removeClass( 'dashicons-star-filled dashicons-star-half' )
				.addClass( 'dashicons-star-empty' );
			$( this )
				.prevUntil( $( this ), '.rtng-star' )
				.children( 'span' )
				.removeClass( 'dashicons-star-empty dashicons-star-half' )
				.addClass( 'dashicons-star-filled rtng-hovered' );
			$( this )
				.children( 'span' )
				.removeClass( 'dashicons-star-empty dashicons-star-half' )
				.addClass( 'dashicons-star-filled rtng-hovered' );
		}).on( 'mouseleave', '.rtng-star-rating.rtng-active .rtng-star', function() {
			if ( $( this ).parent().find( 'input[name="rtng_rating"]:checked' ).val() ) {
				rating = $( this ).parent().find( 'input[name="rtng_rating"]:checked' ).val();
			} else {
				rating = $( this ).parent().attr( 'data-rating' );
				rating = ( rating / 100 ) * 5;
			}
			if ( undefined != rating ) {
				list = $( this ).parent().children( '.rtng-star' );
				list.children( 'span' )
					.removeClass( 'dashicons-star-filled rtng-hovered' )
					.addClass( 'dashicons-star-empty' );
				list.slice( 0, rating )
					.children( 'span' )
						.removeClass( 'dashicons-star-empty' )
						.addClass( 'dashicons-star-filled' );
				if ( ( rating * 10 % 10 ) != 0 && rating != 0 ) {
					list.slice( parseFloat( rating ) - 0.5, parseFloat( rating ) + 0.5 )
						.children( 'span' )
							.removeClass( 'dashicons-star-empty' )
							.addClass( 'dashicons-star-half' );
				}
			}
		});	
	});			
})( jQuery );
