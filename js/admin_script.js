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

		$( '#rtng_rate_position input' ).change( function() {
			if ( $( '#rtng_rate_position input[value="in_comment"]' ).is( ':checked' ) ) {
				$( '#rtng_rate_position input[value!="in_comment"]' ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
			} else {
				$( '#rtng_rate_position input' ).removeAttr( 'disabled' );
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
	} );
} )( jQuery );
