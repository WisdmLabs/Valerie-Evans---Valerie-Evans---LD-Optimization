# Implementation Tasks - Version 2

## Module 1: User Interface (UI) Module

### Purpose
Provide the user interface for course selection and the rich HTML/CSS template editor.

### Tasks

#### Plugin Main File Setup
1. Create the main plugin file (`your-plugin-name.php`)
2. Define plugin header information
3. Include necessary files for each module

#### Admin Page/Shortcode Creation
##### Option A (Frontend Shortcode)
1. Hook into `init` action
2. Use `add_shortcode()` to register a new shortcode
3. Define the callback function for rendering the shortcode content

#### HTML Form Structure
1. In the UI rendering callback function, create an HTML form
2. Add a hidden input field for WordPress nonce (`wp_nonce_field()`)
3. For each completed LearnDash course:
   - Display course title and completion date
   - Add a checkbox input (`<input type="checkbox">`) with:
     - `name="selected_course_ids[]"`
     - `value="[course_id]"`
4. Add template editor:
   - Large textarea (`<textarea>`) for `certificate_html_template`
   - Implement WordPress TinyMCE editor (`wp_editor()`)
   - Provide clear instructions for placeholders:
     - `{{user_name}}`
     - `{{course_list}}`
     - `{{completion_date}}`
5. Add a "Generate Certificate" submit button

#### Form Submission Handling
1. In the UI rendering callback, check for form submission (`$_POST` data)
2. Security:
   - Verify nonce using `check_admin_referer()` (admin page) or `wp_verify_nonce()` (front-end)
   - If nonce check fails, use `wp_die()` or return error message
3. Validate and sanitize inputs:
   - Use `wp_kses_post()` for HTML/CSS template
   - Consider custom whitelist for broader HTML tags
   - Sanitize selected course IDs
4. Pass collected and sanitized data to Certificate Generation Module

## Module 2: Data Retrieval Module

### Purpose
Fetch necessary dynamic data from LearnDash and WordPress.

### Tasks

#### Define Data Retrieval Class/Functions
1. Create `class-certificate-data-retriever.php`
2. Define method `get_completed_courses($user_id)`:
   ```php
   public function get_completed_courses($user_id) {
       // Use LearnDash functions:
       // - ld_get_user_courses_progress()
       // - learndash_course_get_last_step_date_completed()
       
       return [
           [
               'title' => 'Course A',
               'date' => 'YYYY-MM-DD'
           ],
           // ...
       ];
   }
   ```

3. Define method `get_user_details($user_id)`:
   ```php
   public function get_user_details($user_id) {
       $user = get_user_by('ID', $user_id);
       return [
           'display_name' => $user->display_name,
           // Add other relevant user info
       ];
   }
   ```

#### Integration with UI Module
1. In UI module's rendering callback:
   ```php
   $courses = Certificate_Data_Retriever::get_completed_courses(
       get_current_user_id()
   );
   ```
2. In form submission handler:
   ```php
   $user_data = Certificate_Data_Retriever::get_user_details(
       get_current_user_id()
   );
   ```

## Module 3: Template Management Module

### Purpose
Store, retrieve, and manage the editable HTML/CSS certificate template.

### Tasks

#### Define Template Manager Class/Functions
1. Create `class-certificate-template-manager.php`
2. Define save method:
   ```php
   public function save_template($template_content) {
       // Option 1: WordPress Options API
       return update_option(
           'certificate_template_html',
           wp_kses_post($template_content)
       );
       
       // Option 2: Custom Post Type
       return wp_insert_post([
           'post_type' => 'certificate_template',
           'post_content' => wp_kses_post($template_content),
           'post_status' => 'publish'
       ]);
   }
   ```

3. Define retrieve method:
   ```php
   public function get_template() {
       // Option 1: WordPress Options API
       $template = get_option('certificate_template_html');
       
       // Option 2: Custom Post Type
       $template = get_post(...);
       
       return $template ?: $this->get_default_template();
   }
   ```

#### Integration
1. UI Module:
   ```php
   // Pre-populate editor
   $template = Template_Manager::get_template();
   
   // Save updated template
   Template_Manager::save_template($_POST['certificate_html_template']);
   ```

## Module 4: Certificate Generation Module

### Purpose
Populate the HTML template with dynamic data and convert it into a PDF.

### Tasks

#### PDF Library Integration
1. **Recommended**: Install via Composer
   ```bash
   composer require mpdf/mpdf
   # or
   composer require dompdf/dompdf
   ```

#### Define Certificate Generator Class
1. Create `class-certificate-generator.php`
2. Define method:
   ```php
   public function generate_pdf(
       $user_details,
       $selected_course_details,
       $html_template
   )
   ```

#### Template Processing
1. Placeholder Replacement:
   ```php
   private function replace_placeholders($template, $data) {
       $template = str_replace(
           '{{user_name}}',
           $data['user_details']['display_name'],
           $template
       );
       
       $course_list = $this->generate_course_list(
           $data['selected_course_details']
       );
       $template = str_replace(
           '{{course_list}}',
           $course_list,
           $template
       );
       
       return $template;
   }
   ```

2. Course List Generation:
   ```php
   private function generate_course_list($courses) {
       $html = '<ul class="course-list">';
       foreach ($courses as $course) {
           $html .= sprintf(
               '<li>%s - Completed: %s</li>',
               esc_html($course['title']),
               esc_html($course['date'])
           );
       }
       $html .= '</ul>';
       return $html;
   }
   ```

#### PDF Generation
```php
public function create_pdf($populated_html) {
    // Using mPDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($populated_html);
    return $mpdf->Output('', 'S');
    
    // Or using Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($populated_html);
    $dompdf->render();
    return $dompdf->output();
}
```

## Module 5: Download Management Module

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