(function( $ ) {
	'use strict';

    $(function() {

        $( '.exopite-cam-delete-cache-js' ).on('click', function(event) {
            event.preventDefault();

            var res = confirm( $( this ).data('confirm') );

            if ( res == true ) {

                var $that = $( this );

                $that.addClass( 'loading' );

                $.ajax({
                    url: $( this ).data('ajaxurl'),
                    type: 'POST',
                    data: {
                        action: 'exopite_cam_delete_cache',
                    },
                    success: function( response ) {
                        $that.removeClass( 'loading' );
                        $that.addClass( 'respond respond-success' );
                        setTimeout( function() {
                            $that.removeClass( 'respond respond-success' );
                        }, 3000);
                    },
                    error: function( xhr, status, error ) {
                        console.log( 'Status: ' + xhr.status );
                        console.log( 'Error: ' + xhr.responseText );
                        $that.removeClass( 'loading' );
                        $that.addClass( 'respond respond-error' );
                    }
                });

            }

            return false;

        });

    });

})( jQuery );
