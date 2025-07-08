# Technical Specifications - Version 2

## Module 1: User Interface (UI) Module

### Purpose
To present an interface for selecting completed LearnDash courses and managing certificate element positioning.

### Key Components/Files
- `class-certificate-ui.php`: Handles UI rendering and form processing
- `templates/certificate-selection-form.php`: HTML structure for course selection
- `templates/certificate-position-editor.php`: HTML for the element positioning interface
- `js/certificate-ui.js`: Script for drag-and-drop positioning and coordinate management
- `css/certificate-ui.css`: Styling for the form and positioning interface

### Inputs
- Current User ID (from `wp_get_current_user()`)
- List of completed LearnDash courses (from Data Retrieval Module)
- Background image ID and URL
- Stored element coordinates
- Signature image ID and URL

### Outputs
HTTP POST request containing:
- `selected_course_ids[]`: Array of selected course IDs
- `element_coordinates`: JSON object containing position data for each element
- WordPress nonce for security validation

### Dependencies
- WordPress Core (for admin pages/shortcodes, Media Library)
- Data Retrieval Module (to populate course list)
- jQuery UI (for draggable functionality)

### Technical Considerations
1. **Element Position Management**:
   - Implement drag-and-drop interface using jQuery UI
   - Store coordinates relative to background image dimensions
   - Provide numerical input for fine-tuning coordinates
   - Real-time preview of element positions

2. **Security**:
   - Implement strong nonce verification
   - Validate coordinate values
   - Sanitize all inputs

3. **Media Management**:
   - Integrate with WordPress Media Library
   - Handle image uploads and selection
   - Preview uploaded images

4. **User Experience**:
   - Clear visual feedback during drag-and-drop
   - Intuitive interface for position adjustment
   - Responsive preview area

## Module 2: Data Retrieval Module

### Purpose
To fetch course completion data and user information from LearnDash.

### Key Components/Files
- `class-certificate-data-retriever.php`: Contains methods for fetching data

### Inputs
- `user_id`: The ID of the user for whom to retrieve data
- `course_ids` (optional): Specific course IDs to fetch details for

### Outputs
```php
get_completed_courses( $user_id ):
    Returns an array of associative arrays:
    [
        ['id' => 123, 'title' => 'Course A', 'completion_date' => 'YYYY-MM-DD'],
        ...
    ]

get_user_display_name( $user_id ):
    Returns the user's display name
```

### Dependencies
- LearnDash Plugin
- WordPress Core

### Technical Considerations
1. **LearnDash Integration**:
   - Use LearnDash's official functions
   - Handle course completion status and dates
   - Retrieve course metadata

2. **Performance**:
   - Optimize database queries
   - Cache results where appropriate
   - Handle large course lists efficiently

## Module 3: Position Management Module

### Purpose
To handle the storage and retrieval of element coordinates for certificate generation.

### Key Components/Files
- `class-certificate-position-manager.php`: Methods for coordinate operations

### Inputs
- `background_id`: The ID of the certificate background image
- `element_coordinates`: Array of position data for each element

### Outputs
```php
save_coordinates($background_id, $coordinates):
    Saves the coordinate set for a specific background

get_coordinates($background_id):
    Retrieves coordinates for the specified background
```

### Dependencies
- WordPress Core (Options API)

### Technical Considerations
1. **Coordinate Storage**:
   - Store coordinates per background image
   - Handle multiple coordinate sets
   - Maintain default positions

2. **Validation**:
   - Validate coordinate ranges
   - Ensure all required elements have positions
   - Handle missing or invalid data

3. **Default Handling**:
   - Provide fallback positions
   - Initialize new backgrounds with defaults

## Module 4: Certificate Generation Module

### Purpose
Generate PDF certificates using mPDF with precise element positioning.

### Key Components/Files
- `class-certificate-generator.php`: Main class for PDF generation
- `vendor/mpdf/mpdf`: mPDF library integration

### Inputs
- `user_display_name`: Certificate recipient's name
- `selected_course_details`: Array of course data
- `background_image`: Certificate background image
- `signature_image`: Signature image
- `element_coordinates`: Position data for all elements

### Outputs
A generated PDF file stream using mPDF

### Dependencies
- mPDF Library
- Data Retrieval Module
- Position Management Module

### Technical Considerations
1. **mPDF Configuration**:
   - Set up custom page size and margins
   - Configure font settings
   - Handle background image placement

2. **Element Positioning**:
   - Use mPDF's WriteFixedPosHTML for text elements
   - Position images with exact coordinates
   - Handle different page orientations

3. **Resource Management**:
   - Optimize image handling
   - Manage font resources
   - Control PDF file size

4. **Error Handling**:
   - Handle PDF generation errors
   - Validate all inputs before processing
   - Provide meaningful error messages

## Module 5: Download Management Module

### Purpose
To serve the generated PDF certificate to the user's browser.

### Key Components/Files
- `class-certificate-downloader.php`: Handles the download process

### Inputs
- `pdf_output`: The generated PDF from mPDF
- `filename`: The desired filename for the certificate

### Outputs
HTTP headers and PDF file for download

### Dependencies
- Certificate Generation Module

### Technical Considerations
1. **File Delivery**:
   - Set appropriate HTTP headers
   - Handle large file downloads
   - Clean output buffer

2. **Error Handling**:
   - Handle download interruptions
   - Provide download feedback
   - Clean up temporary files