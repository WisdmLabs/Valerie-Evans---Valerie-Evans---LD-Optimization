# Implementation Tasks - Version 2

## Module 1: User Interface (UI) Module

### Purpose
Provide the user interface for course selection and element positioning on certificates.

### Tasks

#### Plugin Main File Setup
1. Create the main plugin file (`learndash-certificate-builder.php`)
2. Define plugin header information
3. Include necessary files for each module
4. Enqueue required scripts and styles:
   ```php
   wp_enqueue_script('jquery-ui-draggable');
   wp_enqueue_script('jquery-ui-droppable');
   ```

#### Admin Settings Page
1. Create admin menu under LearnDash
2. Create settings sections:
   - Background Image Management
   - Signature Image Upload
   - Element Position Editor
   - Default Coordinate Settings

#### Element Position Editor Interface
1. Create the position editor template:
   - Canvas area with background image
   - Draggable elements for:
     - User name
     - Completion date
     - Course list
     - Signature
   - Coordinate input fields for fine-tuning
2. Implement drag-and-drop functionality:
   ```javascript
   $('.draggable-element').draggable({
       containment: 'parent',
       stop: function(event, ui) {
           updateCoordinates($(this), ui.position);
       }
   });
   ```
3. Add coordinate save/update handlers:
   ```javascript
   function updateCoordinates(element, position) {
       $.ajax({
           url: ajaxurl,
           method: 'POST',
           data: {
               action: 'save_element_coordinates',
               element: element.data('type'),
               x: position.left,
               y: position.top,
               background_id: currentBackgroundId
           }
       });
   }
   ```

#### Frontend Certificate Selection
1. Create shortcode `[learndash_custom_certificate]`
2. Implement course selection form:
   - List completed courses with checkboxes
   - Generate button
   - Security nonce
3. Handle form submission

## Module 2: Data Retrieval Module

### Purpose
Fetch course completion and user data from LearnDash.

### Tasks

#### Define Data Retrieval Class
1. Create `class-certificate-data-retriever.php`
2. Implement course retrieval:
   ```php
   public function get_completed_courses($user_id) {
       $courses = [];
       $user_courses = learndash_user_get_enrolled_courses($user_id);
       
       foreach ($user_courses as $course_id) {
           if (learndash_course_completed($user_id, $course_id)) {
               $courses[] = [
                   'id' => $course_id,
                   'title' => get_the_title($course_id),
                   'completion_date' => learndash_course_get_completed_date($user_id, $course_id)
               ];
           }
       }
       return $courses;
   }
   ```

## Module 3: Position Management Module

### Purpose
Manage and store element coordinates for certificate generation.

### Tasks

#### Define Position Manager Class
1. Create `class-certificate-position-manager.php`
2. Implement coordinate storage:
   ```php
   public function save_coordinates($background_id, $coordinates) {
       $all_coordinates = get_option('lcb_element_coordinates', []);
       $all_coordinates[$background_id] = $coordinates;
       return update_option('lcb_element_coordinates', $all_coordinates);
   }
   ```
3. Implement coordinate retrieval:
   ```php
   public function get_coordinates($background_id) {
       $all_coordinates = get_option('lcb_element_coordinates', []);
       return isset($all_coordinates[$background_id]) 
           ? $all_coordinates[$background_id] 
           : $this->get_default_coordinates();
   }
   ```
4. Define default coordinates:
   ```php
   private function get_default_coordinates() {
       return [
           'user_name' => ['x' => 300, 'y' => 200],
           'completion_date' => ['x' => 300, 'y' => 400],
           'course_list' => ['x' => 300, 'y' => 600],
           'signature' => ['x' => 500, 'y' => 800]
       ];
   }
   ```

## Module 4: Certificate Generation Module

### Purpose
Generate PDF certificates using mPDF with precise element positioning.

### Tasks

#### PDF Library Integration
1. Install mPDF via Composer:
   ```bash
   composer require mpdf/mpdf
   ```

#### Define Certificate Generator Class
1. Create `class-certificate-generator.php`
2. Initialize mPDF with custom configuration:
   ```php
   private function init_mpdf() {
       $config = [
           'mode' => 'utf-8',
           'format' => 'A4-L',
           'margin_left' => 0,
           'margin_right' => 0,
           'margin_top' => 0,
           'margin_bottom' => 0
       ];
       return new \Mpdf\Mpdf($config);
   }
   ```
3. Implement certificate generation:
   ```php
   public function generate_certificate($user_id, $course_ids, $background_id) {
       $mpdf = $this->init_mpdf();
       $coordinates = $this->position_manager->get_coordinates($background_id);
       
       // Add background
       $background_url = wp_get_attachment_url($background_id);
       $mpdf->SetDefaultBodyCSS('background', "url($background_url)");
       $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
       
       // Add elements using coordinates
       foreach ($coordinates as $element => $pos) {
           $content = $this->get_element_content($element, $user_id, $course_ids);
           $mpdf->WriteFixedPosHTML($content, $pos['x'], $pos['y'], 50, 50);
       }
       
       return $mpdf->Output('', 'S');
   }
   ```

## Module 5: Download Management Module

### Purpose
Deliver the generated PDF certificate to the user's browser.

### Tasks

#### Define Download Handler Class
1. Create `class-certificate-downloader.php`
2. Implement download method:
   ```php
   public function download_certificate($pdf_output, $filename) {
       if (headers_sent()) {
           return false;
       }
       
       header('Content-Type: application/pdf');
       header('Content-Disposition: attachment; filename="' . $filename . '"');
       header('Cache-Control: private, max-age=0, must-revalidate');
       
       echo $pdf_output;
       exit;
   }
   ```

#### Error Handling
1. Implement error checks:
   - Verify PDF generation success
   - Check file size limits
   - Handle timeout scenarios
2. Add user feedback for download status