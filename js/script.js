( function( $ ) {
	$( document ).ready( function() {
		$( '.rtng-star-rating' ).removeClass( 'rtng-no-js' );
		$( document ).on( 'change', '.rtng-star-rating.rtng-active .rtng-star input', function() {
			var form = $( this ).closest( '.rtng-form' );

			form.before( '<div class="rtng-form-marker"></div>' );

			var val = $( this ).val(),
				rating_id = $( this ).attr( 'name' ).match( /rtng_rating\[(\d)\]$/ )[1],
				object_id = form.find( 'input[name="rtng_object_id"]' ).val(),
				post_id = form.data( 'id' ),
				object_type = form.find( 'input[name="rtng_object_type"]' ).val();
				show_title = form.find( 'input[name="rtng_show_title"]' ).val();

			form.find( '.rtng-star-rating' ).append( '<span class="rtng-loading"></span>' );

			$.ajax( {
				url: rtng_vars.ajaxurl,
				data: {
					action: 'rtng_add_rating_db',
					rtng_rating_val: val,
					rtng_rating_id: rating_id,
					rtng_object_type: object_type,
					rtng_object_id: object_id,
					rtng_post_id: post_id,
					rtng_show_title: show_title,
					rtng_nonce: rtng_vars.nonce
				},
				type: 'POST',
				success: function( data ) {
					if ( data ) {
						data = JSON.parse( data );

						$( '.rtng-rating-total[data-id="' + post_id + '"]' ).not( '.rtng-form-combined' ).replaceWith( data.total );
						$( '.rtng-form[data-id="' + post_id + '"]' ).not( '.rtng-form-combined' ).replaceWith( data.rate );
						$( '.rtng-form-combined[data-id="' + post_id + '"]' ).replaceWith( data.combined );

						form = $( '.rtng-form-marker + .rtng-form' );

						var $message_block = form.find( '.rtng-thankyou' );
						if ( $message_block.length ) {
							$message_block.replaceWith( data.message );
						} else {
							form.append( data.message );
						}
						setTimeout( function() {
							form.find( '.rtng-thankyou' ).fadeOut();
						}, 5000 );
					}
					form.find( '.rtng-loading' ).remove();
					$( '.rtng-form-marker' ).remove();
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
			if ( $( this ).parent().find( 'input[name^="rtng_rating"]:checked' ).val() ) {
				rating = $( this ).parent().find( 'input[name^="rtng_rating"]:checked' ).val();
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
