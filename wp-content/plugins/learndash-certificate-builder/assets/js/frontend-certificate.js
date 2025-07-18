jQuery( document ).ready( function ( $ ) {
    $( '#lcb-generate-form' ).on( 'submit', function ( e ) {
        e.preventDefault();
        var $form = $( this );
        var $button = $form.find( 'button[type="submit"]' );
        var $messages = $( '#lcb-form-messages' );
        var stream_mode = $form.find( 'input[name="stream_mode"]:checked' ).val() === '1';

        // Check if at least one course is selected
        if ( !$form.find( 'input[name="course_ids[]"]:checked' ).length ) {
            $messages.html( '<div class="notice notice-error"><p>' + lcb_frontend.select_course + '</p></div>' );
            return;
        }

        // Disable button and show loading state
        $button.prop( 'disabled', true ).text( lcb_frontend.generating );

        // Submit form data
        $.ajax( {
            url: lcb_frontend.ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            xhrFields: {
                responseType: stream_mode ? 'blob' : 'blob'  // Always use blob for PDF
            },
            success: function ( response, status, xhr ) {
                var conten_type = xhr.getResponseHeader( 'content-type' );
                if ( conten_type && conten_type.indexOf( 'application/pdf' ) !== -1 ) {
                    // Handle PDF response
                    var blob = new Blob( [response], { type: 'application/pdf' } );
                    var url = window.URL.createObjectURL( blob );
                    if ( stream_mode ) {
                        // Open PDF in new tab
                        var new_tab = window.open( '', '_blank' );
                        if ( new_tab ) {
                            new_tab.location.href = url;
                        } else {
                            $messages.html( '<div class="notice notice-error"><p>' + lcb_frontend.popup_blocked + '</p></div>' );
                        }
                    } else {
                        // Download PDF
                        var a = document.createElement( 'a' );
                        var content_disposition = xhr.getResponseHeader( 'content-disposition' );
                        var filename = 'certificate.pdf';
                        if ( content_disposition ) {
                            var filename_match = content_disposition.match( /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/ );
                            if ( filename_match && filename_match[1] ) {
                                filename = filename_match[1].replace( /['"]/g, '' );
                            }
                        }
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild( a );
                        a.click();
                        document.body.removeChild( a );
                        window.URL.revokeObjectURL( url );
                    }
                    $messages.html( '<div class="notice notice-success"><p>' + lcb_frontend.success + '</p></div>' );
                } else {
                    try {
                        // Try to parse error response
                        var jsonResponse = JSON.parse( new TextDecoder().decode( response ) );
                        $messages.html( '<div class="notice notice-error"><p>' + ( jsonResponse.data || lcb_frontend.generation_failed ) + '</p></div>' );
                    } catch ( e ) {
                        $messages.html( '<div class="notice notice-error"><p>' + lcb_frontend.generation_failed + '</p></div>' );
                    }
                }
            },
            error: function ( xhr, status, error ) {
                $messages.html( '<div class="notice notice-error"><p>' + lcb_frontend.connection_failed + '</p></div>' );
            },
            complete: function () {
                // Reset button state
                $button.prop( 'disabled', false ).text( lcb_frontend.generate_certificate );
            }
        } );
    } );
} ); 