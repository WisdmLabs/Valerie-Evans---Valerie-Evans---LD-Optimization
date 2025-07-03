# Implementation Tasks - Version 1

## Module 1: User Interface (UI) Module

### Purpose
Provide the user interface for course selection and certificate customization.

### Tasks

#### Plugin Main File Setup
1. Create the main plugin file (`your-plugin-name.php`)
2. Define plugin header information:
   - Name
   - Description
   - Author
   - Version
3. Include necessary files for each module

#### Admin Page/Shortcode Creation
##### Option A (Frontend Shortcode)
1. Hook into `init` action
2. Use `add_shortcode()` to register a new shortcode (e.g., `[learndash_custom_certificate]`)
3. Define the callback function for rendering the shortcode content

#### HTML Form Structure
1. In the UI rendering callback function, create an HTML form
2. Add a hidden input field for WordPress nonce (`wp_nonce_field()`) for security
3. For each completed LearnDash course:
   - Display course title and completion date
   - Add a checkbox input (`<input type="checkbox">`) with:
     - `name="selected_course_ids[]"`
     - `value="[course_id]"`
4. Add text input fields for:
   - `custom_header_text` (`<input type="text">` or `<textarea>`)
   - `custom_footer_text` (`<input type="text">` or `<textarea>`)
5. Add a file input field for certificate background:
   - `<input type="file">`
   - Implement WordPress Media Uploader API
   - Requires enqueueing media scripts
6. Add a "Generate Certificate" submit button

#### Form Submission Handling
1. In the UI rendering callback, check for form submission (`$_POST` data)
2. Security:
   - Verify nonce using `check_admin_referer()` (admin page) or `wp_verify_nonce()` (front-end)
   - If nonce check fails, use `wp_die()` or return error message
3. Validate and sanitize all inputs:
   - Use `sanitize_text_field()`
   - Use `esc_url_raw()`
4. Handle background image upload:
   - Use `wp_handle_upload()` for media library storage
   - Get URL or attachment ID
5. Pass collected and sanitized data to Certificate Generation Module

## Module 2: Data Retrieval Module

### Purpose
Fetch necessary data from LearnDash and WordPress.

### Tasks

#### Define Data Retrieval Class/Functions
1. Create `class-certificate-data-retriever.php`
2. Define method `get_completed_courses($user_id)`:
   ```php
   public function get_completed_courses($user_id) {
       // Use LearnDash functions:
       // - learndash_user_get_course_progress()
       // - learndash_course_get_last_step_date_completed()
       
       // Return structured array of completed courses
       return [
           'course_id' => [
               'title' => 'Course Title',
               'completion_date' => 'YYYY-MM-DD'
           ]
       ];
   }
   ```
3. Define method `get_user_display_name($user_id)`:
   ```php
   public function get_user_display_name($user_id) {
       $user = get_user_by('ID', $user_id);
       return $user->display_name;
   }
   ```

#### Integration with UI Module
1. In UI module's rendering callback:
   ```php
   $courses = Certificate_Data_Retriever::get_completed_courses(
       get_current_user_id()
   );
   ```

## Module 3: Certificate Generation Module

### Purpose
Generate the PDF document using TCPDF/FPDF.

### Tasks

#### PDF Library Integration
1. **Recommended**: Install via Composer
   ```bash
   composer require tecnickcom/tcpdf
   ```
2. **Alternative**: Manual inclusion
   ```php
   require_once plugin_dir_path(__FILE__) . 'vendor/tcpdf/tcpdf.php';
   ```

#### Define Certificate Generator Class
1. Create `class-certificate-generator.php`
2. Define method:
   ```php
   public function generate_pdf(
       $user_name,
       $course_details,
       $header_text,
       $footer_text,
       $background_image_path
   )
   ```

#### PDF Document Initialization
1. Inside `generate_pdf()`:
   ```php
   // Create TCPDF instance
   $pdf = new TCPDF();
   
   // Set document metadata
   $pdf->SetCreator('Your Plugin Name');
   $pdf->SetAuthor('Site Name');
   $pdf->SetTitle('Course Certificate');
   
   // Configure page
   $pdf->SetMargins(15, 15, 15);
   $pdf->SetAutoPageBreak(TRUE, 15);
   $pdf->AddPage();
   ```

#### Content Placement
1. Background Image:
   ```php
   $pdf->Image($background_image_path, 0, 0, $pdf->getPageWidth());
   ```

2. Header and Footer:
   ```php
   $pdf->SetFont('helvetica', 'B', 16);
   $pdf->SetTextColor(0, 0, 0);
   $pdf->Text(15, 15, $header_text);
   $pdf->Text(15, $pdf->getPageHeight() - 15, $footer_text);
   ```

3. Course Data:
   ```php
   $y = 50;
   foreach ($course_details as $course) {
       $pdf->SetY($y);
       $pdf->Cell(0, 10, $course['title'], 0, 1);
       $pdf->Cell(0, 10, $course['completion_date'], 0, 1);
       $y += 30;
   }
   ```

#### Output PDF Stream
```php
return $pdf->Output('', 'S');
```

## Module 4: Download Management Module

### Purpose
Deliver the generated PDF file to the user's browser.

### Tasks

#### Define Downloader Class
1. Create `class-certificate-downloader.php`
2. Define method:
   ```php
   public function download_pdf($pdf_output_stream, $filename) {
       ob_clean();
       
       header('Content-Type: application/pdf');
       header('Content-Disposition: attachment; filename="' . $filename . '"');
       header('Content-Length: ' . strlen($pdf_output_stream));
       header('Cache-Control: private, max-age=0, must-revalidate');
       header('Pragma: public');
       
       echo $pdf_output_stream;
       exit;
   }
   ```

## Module 5: Settings/Configuration Module

### Purpose
Manage storage and retrieval of global certificate customization options.

### Tasks

#### Define Settings Class
1. Create `class-certificate-settings.php`
2. Define save method:
   ```php
   public function save_settings($header_text, $footer_text, $background_image_id) {
       update_option('yourplugin_certificate_header_text', 
           sanitize_text_field($header_text)
       );
       update_option('yourplugin_certificate_footer_text',
           sanitize_text_field($footer_text)
       );
       update_option('yourplugin_certificate_bg_image_id',
           absint($background_image_id)
       );
   }
   ```

3. Define retrieve method:
   ```php
   public function get_settings() {
       return [
           'header_text' => get_option('yourplugin_certificate_header_text', ''),
           'footer_text' => get_option('yourplugin_certificate_footer_text', ''),
           'background_image_id' => get_option('yourplugin_certificate_bg_image_id', 0)
       ];
   }
   ```

#### Integration
1. UI Module:
   ```php
   // Pre-populate form
   $settings = Settings_Module::get_settings();
   
   // Save settings on form submission
   Settings_Module::save_settings(
       $_POST['custom_header_text'],
       $_POST['custom_footer_text'],
       $_POST['background_image_id']
   );
   ```

2. Certificate Generation:
   ```php
   $settings = Settings_Module::get_settings();
   $pdf = generate_pdf(
       $user_name,
       $course_details,
       $settings['header_text'],
       $settings['footer_text'],
       $settings['background_image_id']
   );
   ```