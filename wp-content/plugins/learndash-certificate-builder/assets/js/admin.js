jQuery( document ).ready( function ( $ ) {
    // Initialize media uploader.
    var mediaUploader;

    // Function to update canvas dimensions based on background image
    function updateCanvasDimensions() {
        var $canvas = $( '.lcb-canvas' );
        var backgroundId = $( '#lcb_background_image' ).val();

        if ( backgroundId ) {
            // Get the background image URL
            $.ajax( {
                url: lcb_admin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lcb_get_image_data',
                    nonce: lcb_admin.nonce,
                    image_id: backgroundId
                },
                success: function ( response ) {
                    if ( response.success && response.data ) {
                        var imageData = response.data;
                        $canvas.addClass( 'has-background' )
                            .css( {
                                'width': imageData.width + 'px',
                                'height': imageData.height + 'px',
                                'background-image': 'url(' + imageData.url + ')'
                            } );
                    }
                }
            } );
        } else {
            $canvas.removeClass( 'has-background' )
                .css( {
                    'width': '100%',
                    'height': 'auto',
                    'min-height': '400px',
                    'background-image': 'none'
                } );
        }
    }

    // Handle media upload button click.
    $( '.lcb-upload-image' ).click( function ( e ) {
        e.preventDefault();
        var targetId = $( this ).data( 'target' );

        if ( !mediaUploader ) {
            mediaUploader = wp.media( {
                title: 'Select Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            } );
        }

        mediaUploader.off( 'select' ).on( 'select', function () {
            var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
            $( '#' + targetId ).val( attachment.id );

            // Update preview container
            var previewContainer = $( '.lcb-preview-image' ).filter( function () {
                return $( this ).siblings( 'input[type="hidden"]' ).attr( 'id' ) === targetId;
            } );
            previewContainer.html( '<img src="' + attachment.url + '" alt="">' );

            // Show remove button
            var removeButton = $( '.lcb-remove-image[data-target="' + targetId + '"]' );
            removeButton.removeClass( 'lcb-hidden' ).show();

            // If this is a background image, update canvas
            if ( targetId === 'lcb_background_image' ) {
                updateCanvasDimensions();
            }
        } );

        mediaUploader.open();
    } );

    // Handle remove button click.
    $( '.lcb-remove-image' ).click( function ( e ) {
        e.preventDefault();
        var targetId = $( this ).data( 'target' );

        // Clear input value
        $( '#' + targetId ).val( '' );

        // Clear preview
        var previewContainer = $( '.lcb-preview-image' ).filter( function () {
            return $( this ).siblings( 'input[type="hidden"]' ).attr( 'id' ) === targetId;
        } );
        previewContainer.empty();

        // Hide remove button
        $( this ).addClass( 'lcb-hidden' ).hide();

        // If this is a background image, update canvas
        if ( targetId === 'lcb_background_image' ) {
            updateCanvasDimensions();
        }
    } );

    // Initialize draggable elements
    $( '.lcb-draggable-element' ).draggable( {
        containment: '.lcb-canvas',
        stop: function ( event, ui ) {
            var $canvas = $( '.lcb-canvas' );
            var canvasOffset = $canvas.offset();
            var elementOffset = $( this ).offset();

            // Calculate position relative to canvas
            var relativeX = Math.round( elementOffset.left - canvasOffset.left );
            var relativeY = Math.round( elementOffset.top - canvasOffset.top );

            // Update coordinate inputs
            var $element = $( this );
            $element.find( '.lcb-x-coordinate' ).val( relativeX );
            $element.find( '.lcb-y-coordinate' ).val( relativeY );

            // Update hidden input with all coordinates
            updateCoordinatesInput();
        }
    } );

    // Handle manual coordinate input
    $( '.lcb-x-coordinate, .lcb-y-coordinate' ).on( 'change', function () {
        var $element = $( this ).closest( '.lcb-draggable-element' );
        var x = parseInt( $element.find( '.lcb-x-coordinate' ).val() ) || 0;
        var y = parseInt( $element.find( '.lcb-y-coordinate' ).val() ) || 0;

        // Set position relative to canvas
        $element.css( {
            left: x + 'px',
            top: y + 'px'
        } );

        // Update hidden input with all coordinates
        updateCoordinatesInput();
    } );

    // Function to update hidden input with all coordinates.
    function updateCoordinatesInput() {
        var coordinates = {};
        var backgroundId = $( '#lcb_background_image' ).val();

        if ( !backgroundId ) {
            return;
        }

        coordinates[backgroundId] = {};

        $( '.lcb-draggable-element' ).each( function () {
            var $element = $( this );
            var elementId = $element.data( 'element' );
            var x = parseInt( $element.find( '.lcb-x-coordinate' ).val() ) || 0;
            var y = parseInt( $element.find( '.lcb-y-coordinate' ).val() ) || 0;

            coordinates[backgroundId][elementId] = {
                x: x,
                y: y
            };
        } );

        // Update hidden input.
        $( '#lcb_element_coordinates' ).val( JSON.stringify( coordinates ) );
    }

    // Handle save button click.
    $( '.lcb-save-positions' ).click( function ( e ) {
        e.preventDefault();

        var backgroundId = $( '#lcb_background_image' ).val();
        if ( !backgroundId ) {
            alert( 'Please select a background image first.' );
            return;
        }

        var coordinates = {};
        $( '.lcb-draggable-element' ).each( function () {
            var $element = $( this );
            var elementId = $element.data( 'element' );
            var x = parseInt( $element.find( '.lcb-x-coordinate' ).val() ) || 0;
            var y = parseInt( $element.find( '.lcb-y-coordinate' ).val() ) || 0;

            coordinates[elementId] = {
                x: x,
                y: y
            };
        } );

        // Save via AJAX.
        $.ajax( {
            url: lcb_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lcb_save_coordinates',
                nonce: lcb_admin.nonce,
                background_id: backgroundId,
                coordinates: JSON.stringify( coordinates )
            },
            success: function ( response ) {
                if ( response.success ) {
                    alert( 'Positions saved successfully.' );
                    // Update hidden input after successful save.
                    updateCoordinatesInput();
                } else {
                    alert( 'Failed to save positions: ' + response.data );
                }
            },
            error: function () {
                alert( 'Failed to save positions. Please try again.' );
            }
        } );
    } );

    // Initialize canvas dimensions on page load
    updateCanvasDimensions();
} ); 