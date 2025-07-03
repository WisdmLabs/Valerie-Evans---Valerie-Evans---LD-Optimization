# Technical Specifications - Version 1

## Module 1: User Interface (UI) Module

### Purpose
To present a user-friendly interface for selecting completed LearnDash courses and customizing the certificate's global elements (header, footer, background).

### Key Components/Files
- `class-certificate-ui.php`: Manages the display logic
- `templates/certificate-selection-form.php`: HTML structure for the selection form
- `js/certificate-ui.js` (Optional): Client-side script for minor validation or dynamic updates
- `css/certificate-ui.css`: Styling for the form

### Inputs
- Current User ID (from WordPress `wp_get_current_user()`)
- List of completed LearnDash courses (from Data Retrieval Module)
- Current global certificate settings (header, footer, background - from Settings Module)

### Outputs
HTTP POST request containing:
- `selected_course_ids[]`: Array of selected course IDs
- `custom_header_text`: User-input header text
- `custom_footer_text`: User-input footer text
- `certificate_background_image_id` (or `_url`): ID/URL of the uploaded background image
- WordPress nonce for security validation

### Dependencies
- WordPress Core (for admin pages/shortcodes, Media Uploader API)
- Data Retrieval Module (to populate course list)
- Settings Module (to pre-fill customization fields)

### Technical Considerations
1. **WordPress Integration**: Implement as a WordPress admin page (`add_submenu_page()`) or a front-end page via a shortcode (`add_shortcode()`)
2. **Security**: Use WordPress Nonces (`wp_nonce_field()`, `check_admin_referer()`) to secure form submissions
3. **Media Uploader**: Utilize WordPress's built-in Media Uploader for seamless background image selection and storage
4. **Form Validation**: Basic server-side validation for non-empty fields and valid image uploads
5. **User Experience**: Clear instructions, descriptive labels for fields

## Module 2: Data Retrieval Module

### Purpose
To abstract and encapsulate all data fetching operations from LearnDash and WordPress settings.

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

get_course_details( $course_ids ):
    Returns detailed information for specific courses
```

### Dependencies
- LearnDash Plugin (its internal functions for user course progress and completion status)
- WordPress Core (for `WP_Query`, `get_posts`, `get_user_meta`, `get_option`)

### Technical Considerations
1. **LearnDash API**: 
   - Leverage LearnDash's official functions (`ld_get_user_courses_progress`, `learndash_course_get_last_step_date_completed()`)
   - Ensure compatibility and stability
   - Avoid direct database queries to LearnDash tables unless absolutely necessary

2. **Performance**:
   - Optimize queries for fetching course data
   - Consider transient caching for frequently accessed data

3. **Error Handling**:
   - Gracefully handle cases where LearnDash data might be incomplete
   - Handle cases where courses don't exist

## Module 3: Certificate Generation Module

### Purpose
The core engine for creating the PDF document, combining the template elements with dynamic data.

### Key Components/Files
- `class-certificate-generator.php`: Main class orchestrating PDF creation
- `vendor/tcpdf/tcpdf.php` (or similar for FPDF): The third-party PDF library

### Inputs
- `user_display_name`: The name of the certificate recipient
- `selected_course_details`: Array of course data (title, completion date)
- `header_text`: Custom header content
- `footer_text`: Custom footer content
- `background_image_path`: Full server path to the background image

### Outputs
A generated PDF file stream (not saved to disk directly, but prepared for download)

### Dependencies
- Data Retrieval Module (for course and user data)
- Settings Module (for current customization values)
- TCPDF or FPDF Library

### Technical Considerations
1. **Library Choice**:
   - TCPDF: More feature-rich (Unicode support, HTML rendering)
   - FPDF: Simpler and faster for basic documents

2. **Memory Management**:
   - Optimize image resizing/compression before adding to PDF
   - Monitor memory usage during generation

3. **Font Embedding**:
   - Configure PDF library to embed fonts
   - Use common fonts or allow admin selection from pre-defined set

4. **Layout Precision**:
   - Calculate X/Y coordinates carefully
   - Define fixed positions or use relative positioning

5. **Text Formatting**:
   - Handle multiline text
   - Manage font sizes, colors, and alignments

6. **Error Handling**:
   - Implement robust try-catch blocks
   - Handle PDF generation errors gracefully

## Module 4: Download Management Module

### Purpose
To correctly set HTTP headers and serve the generated PDF file to the user's browser for download.

### Key Components/Files
- `class-certificate-downloader.php`: Handles the download process

### Inputs
- `pdf_output_stream`: The raw PDF data generated by the Certificate Generation Module
- `filename`: The desired filename for the downloaded certificate

### Outputs
HTTP headers and binary PDF data:
```php
header('Content-Type: application/pdf')
header('Content-Disposition: attachment; filename="' . $filename . '"')
header('Content-Length: ' . strlen($pdf_output_stream))
```

### Dependencies
- Certificate Generation Module

### Technical Considerations
1. **Header Control**:
   - Set correct headers for PDF download
   - Ensure headers are sent before any output

2. **Output Buffering**:
   - Use `ob_clean()` if necessary
   - Prevent whitespace before PHP tags

3. **Exit Strategy**:
   - Call `exit;` after sending file
   - Prevent further WordPress processing

## Module 5: Settings/Configuration Module

### Purpose
To manage the storage and retrieval of the global certificate customization options.

### Key Components/Files
- `class-certificate-settings.php`: Encapsulates saving and loading settings

### Inputs
- `header_text`: New header value
- `footer_text`: New footer value
- `background_image_id`: New background image attachment ID

### Outputs
- Boolean success/failure for save operations
- Retrieved settings values

### Dependencies
- WordPress Core (Options API: `update_option()`, `get_option()`)

### Technical Considerations
1. **Option Naming**:
   - Use clear and unique prefixes
   - Example: `yourplugin_certificate_header_text`

2. **Sanitization**:
   - Use `sanitize_text_field()`
   - Use `esc_url_raw()` for URLs

3. **Accessibility**:
   - Follow WordPress accessibility guidelines
   - Ensure admin screen compliance

4. **Default Values**:
   - Provide sensible defaults
   - Handle missing configurations gracefully