# Technical Specifications - Version 2

## Module 1: User Interface (UI) Module

### Purpose
To present an interface for selecting completed LearnDash courses and providing a rich editor for the certificate's HTML/CSS template.

### Key Components/Files
- `class-certificate-ui.php`: Handles UI rendering and form processing
- `templates/certificate-selection-form.php`: HTML structure for course selection
- `templates/certificate-template-editor.php`: HTML for the template editing area
- `js/certificate-ui.js`: Script for integrating TinyMCE or similar editor
- `css/certificate-ui.css`: Styling for the form and editor

### Inputs
- Current User ID (from `wp_get_current_user()`)
- List of completed LearnDash courses (from Data Retrieval Module)
- Current HTML/CSS template content (from Template Management Module)

### Outputs
HTTP POST request containing:
- `selected_course_ids[]`: Array of selected course IDs
- `certificate_html_template`: The updated HTML/CSS content for the certificate
- WordPress nonce for security validation

### Dependencies
- WordPress Core (for admin pages/shortcodes, TinyMCE integration)
- Data Retrieval Module (to populate course list)
- Template Management Module (to pre-fill editor with current template)

### Technical Considerations
1. **HTML Editor Integration**:
   - Integrate a robust HTML editor (e.g., TinyMCE, CodeMirror, or a simple textarea)
   - Allow administrators to write/paste HTML and CSS
   - Provide clear instructions on using placeholders (e.g., `{{user_name}}`, `{{course_list}}`)

2. **Security**:
   - Implement strong nonce verification (`wp_nonce_field()`, `check_admin_referer()`)
   - Prevent CSRF attacks

3. **Input Sanitization**:
   - Sanitize and escape all user-provided HTML/CSS
   - Prevent XSS vulnerabilities
   - Be careful with raw HTML input

4. **User Experience**:
   - Clearly explain available placeholders
   - Provide example HTML/CSS templates

## Module 2: Data Retrieval Module

### Purpose
To abstract and encapsulate all data fetching operations from LearnDash.

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
- LearnDash Plugin (its internal functions for user course progress and completion status)
- WordPress Core (for `get_user_by`, `get_post`)

### Technical Considerations
1. **LearnDash API**:
   - Use LearnDash's official functions (`ld_get_user_courses_progress`, `learndash_course_get_last_step_date_completed()`)
   - Prioritize compatibility and future-proofing

2. **Performance**:
   - Optimize database queries for efficiency
   - Handle large numbers of courses/users

3. **Error Handling**:
   - Implement robust error handling
   - Handle missing or incomplete course data

## Module 3: Template Management Module

### Purpose
To handle the storage, retrieval, and management of the editable HTML and CSS template used for certificate generation.

### Key Components/Files
- `class-certificate-template-manager.php`: Methods for template operations

### Inputs
- `template_content`: The raw HTML/CSS string for the certificate

### Outputs
```php
save_template( $template_content ):
    Saves the content, returns boolean for success

get_template():
    Retrieves the current template content
```

### Dependencies
- WordPress Core (Options API: `update_option()`, `get_option()`)

### Technical Considerations
1. **Storage Location**:
   - Consider using custom post type's meta field (`update_post_meta()`)
   - Alternative: Custom database table for versioning
   - Avoid `wp_option()` for large templates

2. **Sanitization**:
   - Re-sanitize/validate inputs before saving
   - Implement multiple layers of validation

3. **Default Template**:
   - Provide robust default HTML/CSS template
   - Ensure working certificate without custom template

4. **Versioning** (Advanced):
   - Consider implementing template versioning
   - Allow rollbacks if needed

## Module 4: Certificate Generation Module

### Purpose
The core engine for populating the HTML template with dynamic data and converting it into a PDF document.

### Key Components/Files
- `class-certificate-generator.php`: Main class orchestrating the process
- `vendor/mpdf/mpdf/src/Mpdf.php` (or `dompdf/dompdf/src/Dompdf.php`): Third-party HTML-to-PDF library

### Inputs
- `user_display_name`: The name of the certificate recipient
- `selected_course_details`: Array of course data (title, completion date)
- `certificate_html_template`: The raw HTML/CSS template string

### Outputs
A generated PDF file stream (raw binary data)

### Dependencies
- Data Retrieval Module (for course and user data)
- Template Management Module (for the HTML/CSS template)
- mPDF or Dompdf Library

### Technical Considerations
1. **Placeholder Replacement**:
   - Implement robust string replacement
   - Use `str_replace()` or regex-based replacement
   - Handle `{{user_name}}`, `{{course_list}}`, etc.

2. **Dynamic Course List Rendering**:
   - Generate HTML snippets for course lists
   - Support both `<ul>` and `<table>` formats

3. **HTML/CSS Compatibility**:
   - Test complex layouts thoroughly
   - Be aware of PDF library limitations
   - Limited support for modern CSS features

4. **Resource Handling**:
   - Resolve image paths correctly
   - Support absolute URLs for resources

5. **Memory & Performance**:
   - Optimize included images
   - Reduce memory footprint
   - Monitor generation time

6. **Error Handling**:
   - Implement try-catch blocks
   - Handle conversion errors gracefully

## Module 5: Download Management Module

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
   - Ensure proper content type and disposition

2. **Output Buffering**:
   - Use `ob_clean()` when needed
   - Prevent unwanted output

3. **Script Termination**:
   - Call `exit;` after sending file
   - Prevent interference with download