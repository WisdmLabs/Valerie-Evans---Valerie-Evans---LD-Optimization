jQuery( document ).ready( function ( $ ) {
    // Initialize media uploader.
    let mediaUploader;

    // Function to update canvas dimensions based on background image
    function updateCanvasDimensions() {
        let $canvas = $( '.lcb-canvas' );
        let backgroundId = $( '#lcb_background_image' ).val();

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
                        let imageData = response.data;
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
        let targetId = $( this ).data( 'target' );

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
            let attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
            $( '#' + targetId ).val( attachment.id );

            // Update preview container
            let previewContainer = $( '.lcb-preview-image' ).filter( function () {
                return $( this ).siblings( 'input[type="hidden"]' ).attr( 'id' ) === targetId;
            } );
            previewContainer.html( '<img src="' + attachment.url + '" alt="">' );

            // Show remove button
            let removeButton = $( '.lcb-remove-image[data-target="' + targetId + '"]' );
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
        let targetId = $( this ).data( 'target' );

        // Clear input value
        $( '#' + targetId ).val( '' );

        // Clear preview
        let previewContainer = $( '.lcb-preview-image' ).filter( function () {
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
            let $canvas = $( '.lcb-canvas' );
            let canvasOffset = $canvas.offset();
            let elementOffset = $( this ).offset();

            // Calculate position relative to canvas
            let relativeX = Math.round( elementOffset.left - canvasOffset.left );
            let relativeY = Math.round( elementOffset.top - canvasOffset.top );

            // Update coordinate inputs
            let $element = $( this );
            $element.find( '.lcb-x-coordinate' ).val( relativeX );
            $element.find( '.lcb-y-coordinate' ).val( relativeY );

            // Update hidden input with all coordinates
            updateCoordinatesInput();
        }
    } );

    // Handle manual coordinate input
    $( '.lcb-x-coordinate, .lcb-y-coordinate' ).on( 'change', function () {
        let $element = $( this ).closest( '.lcb-draggable-element' );
        let x = parseInt( $element.find( '.lcb-x-coordinate' ).val() ) || 0;
        let y = parseInt( $element.find( '.lcb-y-coordinate' ).val() ) || 0;

        // Set position relative to canvas
        $element.css( {
            left: x + 'px',
            top: y + 'px'
        } );

        // Update hidden input with all coordinates
        updateCoordinatesInput();
    } );

    // Function to update coordinates input with current positions
    function updateCoordinatesInput() {
        let coordinates = {};
        let backgroundId = $( '#lcb_background_image' ).val() || 'default';

        // Get current coordinates for all elements
        $( '.lcb-draggable-element' ).each( function () {
            let $element = $( this );
            let elementId = $element.data( 'element' );
            let x = parseInt( $element.find( '.lcb-x-coordinate' ).val() ) || 0;
            let y = parseInt( $element.find( '.lcb-y-coordinate' ).val() ) || 0;

            coordinates[elementId] = {
                x: x,
                y: y
            };

            // Add font settings for username, course list and page number
            if ( ['user_name', 'course_list', 'page_number'].includes( elementId ) ) {
                // Get font size value directly from the input
                let $fontSizeInput = $element.find( '.lcb-font-size' );
                let fontSize = parseInt( $fontSizeInput.get( 0 ).value ) || ( elementId === 'user_name' ? 24 : 18 );
                console.log( elementId + ' font size input current value:', fontSize );

                coordinates[elementId].font_size = fontSize;
                coordinates[elementId].font_family = $element.find( '.lcb-font-family' ).val() || 'Arial';
                coordinates[elementId].text_transform = $element.find( '.lcb-text-transform' ).val() || 'none';

                console.log( 'Updated font settings for ' + elementId + ':', coordinates[elementId] );
            }
        } );

        // Get existing coordinates for other backgrounds
        let existingCoordinates = JSON.parse( $( 'input[name="lcb_element_coordinates"]' ).val() || '{}' );
        existingCoordinates[backgroundId] = coordinates;

        // Update hidden input
        $( 'input[name="lcb_element_coordinates"]' ).val( JSON.stringify( existingCoordinates ) );
        console.log( 'Updated coordinates:', existingCoordinates );
    }

    // Update coordinates when font settings change
    $( '.lcb-font-size, .lcb-font-family, .lcb-text-transform' ).on( 'change input', function () {
        console.log( 'Font setting changed:', $( this ).val() );
        updateCoordinatesInput();
    } );

    updateCanvasDimensions();
} ); 
