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
	});			
})( jQuery );
