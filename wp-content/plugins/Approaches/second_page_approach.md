# Multi-Page Certificate Generation Approach

## Problem Statement
When generating certificates with a large course list, we need to handle content overflow by creating additional pages while maintaining:
- Same background image
- Consistent starting coordinates
- Proper spacing and formatting
- No split entries across pages

## Technical Approach

### 1. Page Dimension Calculations
```php
// Get page dimensions from background image
$img_width = $background_image_width;
$img_height = $background_image_height;

// Define usable area
$usable_height = $img_height - $bottom_margin; // Leave margin at bottom
$start_y = $coordinates['course_list']['y']; // Starting Y coordinate
```

### 2. Entry Height Calculation
```php
function calculate_entry_height($font_size, $line_height, $margins) {
    // Each entry has 3 lines (Title, Date, Instructor)
    $lines = 3;
    $line_height_mm = $font_size * $line_height * 25.4 / 96; // Convert to mm
    $total_margins = $field_margin * ($lines - 1) + $course_margin;
    
    return ($line_height_mm * $lines) + $total_margins;
}
```

### 3. Content Distribution Logic
```php
function distribute_content($course_ids, $usable_height, $start_y) {
    $pages = array();
    $current_page = 0;
    $current_height = $start_y;
    
    foreach($course_ids as $course_id) {
        $entry_height = calculate_entry_height(...);
        
        // Check if entry fits on current page
        if(($current_height + $entry_height) > $usable_height) {
            // Start new page
            $current_page++;
            $current_height = $start_y;
        }
        
        $pages[$current_page][] = $course_id;
        $current_height += $entry_height;
    }
    
    return $pages;
}
```

### 4. mPDF Page Generation
```php
function generate_certificate_pages($mpdf, $pages, $background_image) {
    foreach($pages as $page_num => $page_courses) {
        if($page_num > 0) {
            // Add new page with same background
            $mpdf->AddPage();
            $mpdf->Image($background_image, 0, 0, $img_width, $img_height);
        }
        
        // Generate course list for this page
        $course_list_html = generate_course_list_html($page_courses);
        
        // Add to page at original coordinates
        $this->add_element($mpdf, $course_list_html, $coordinates['course_list']);
    }
}
```

## Implementation Steps

1. **Update CertificateGenerator Class**
   - Add methods for height calculations
   - Implement content distribution logic
   - Handle multi-page generation

2. **Modify get_formatted_course_list()**
   - Split content into pages
   - Return array of HTML for each page
   - Maintain consistent styling

3. **Update generate_certificate()**
   - Handle multiple pages
   - Apply background to each page
   - Maintain coordinates

## Testing Considerations

1. **Edge Cases**
   - Single entry spanning most of page
   - Many small entries
   - Various font sizes
   - Different page orientations

2. **Visual Consistency**
   - Background alignment
   - Entry spacing
   - Font rendering
   - Margins and padding

3. **Performance**
   - Memory usage with many pages
   - Processing time
   - File size optimization

## Future Enhancements

1. **Page Numbers**
   - Add optional page numbers
   - Configurable position
   - Customizable format

2. **Progress Tracking**
   - Add generation progress indicators
   - Status updates for large documents

3. **Error Handling**
   - Graceful handling of oversized entries
   - Memory limit management
   - Error reporting and logging 