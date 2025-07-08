# Technical Specifications: Multi-Page Certificate Generation

## Overview
This document outlines the technical specifications for implementing multi-page support in the LearnDash Certificate Builder plugin. The system will handle course lists that exceed a single page by automatically creating additional pages while maintaining consistency in layout and design.

## Core Components

### 1. Page Dimension Handler
```php
class PageDimensionHandler {
    private $background_image;
    private $margins;
    private $coordinates;

    /**
     * Calculate usable page dimensions
     * 
     * @return array {
     *     @type int $width     Page width in mm
     *     @type int $height    Usable height in mm
     *     @type int $start_y   Starting Y coordinate for content
     * }
     */
    public function get_dimensions() {
        return array(
            'width'   => $this->get_page_width(),
            'height'  => $this->get_page_height() - $this->margins['bottom'],
            'start_y' => $this->coordinates['course_list']['y']
        );
    }

    /**
     * Convert pixel measurements to millimeters
     * 
     * @param int $pixels Value in pixels
     * @return float Value in millimeters
     */
    public function px_to_mm($pixels) {
        return $pixels * 25.4 / 96; // Standard conversion ratio
    }
}
```

### 2. Content Height Calculator
```php
class ContentHeightCalculator {
    private $font_metrics;
    private $margins;

    /**
     * Calculate height needed for a course entry
     * 
     * @param array $entry_data {
     *     @type int    $font_size Font size in points
     *     @type float  $line_height Line height multiplier
     *     @type string $font_family Font family name
     * }
     * @return float Height in millimeters
     */
    public function calculate_entry_height($entry_data) {
        $line_count = 3; // Title, Date, Instructor
        $line_height_mm = $this->calculate_line_height($entry_data);
        $margins_mm = $this->calculate_margins($line_count);
        
        return ($line_height_mm * $line_count) + $margins_mm;
    }

    /**
     * Calculate total margins for an entry
     * 
     * @param int $line_count Number of lines in entry
     * @return float Total margins in millimeters
     */
    private function calculate_margins($line_count) {
        return ($this->margins['field'] * ($line_count - 1)) + 
               $this->margins['course'];
    }
}
```

### 3. Page Content Manager
```php
class PageContentManager {
    private $dimension_handler;
    private $height_calculator;

    /**
     * Distribute content across pages
     * 
     * @param array $course_ids List of course IDs
     * @return array {
     *     @type array $page_number {
     *         @type array $course_data {
     *             @type int    $id Course ID
     *             @type float  $y_position Y coordinate for placement
     *             @type float  $height Height of entry
     *         }
     *     }
     * }
     */
    public function distribute_content($course_ids) {
        $pages = array();
        $dimensions = $this->dimension_handler->get_dimensions();
        $current = array(
            'page'   => 0,
            'height' => $dimensions['start_y']
        );

        foreach ($course_ids as $course_id) {
            $entry_data = $this->get_course_entry_data($course_id);
            $entry_height = $this->height_calculator
                               ->calculate_entry_height($entry_data);

            if (!$this->fits_on_current_page($current['height'], 
                                           $entry_height, 
                                           $dimensions['height'])) {
                $current = $this->start_new_page($current);
            }

            $pages[$current['page']][] = array(
                'id'         => $course_id,
                'y_position' => $current['height'],
                'height'     => $entry_height
            );

            $current['height'] += $entry_height;
        }

        return $pages;
    }

    /**
     * Check if entry fits on current page
     * 
     * @param float $current_height Current Y position
     * @param float $entry_height Entry height
     * @param float $max_height Maximum usable height
     * @return bool Whether entry fits
     */
    private function fits_on_current_page($current_height, 
                                        $entry_height, 
                                        $max_height) {
        return ($current_height + $entry_height) <= $max_height;
    }
}
```

### 4. Certificate Page Generator
```php
class CertificatePageGenerator {
    private $mpdf;
    private $background;
    private $content_manager;

    /**
     * Generate certificate pages
     * 
     * @param array $course_ids List of course IDs
     * @return bool Success status
     */
    public function generate($course_ids) {
        $pages = $this->content_manager->distribute_content($course_ids);
        
        foreach ($pages as $page_num => $entries) {
            if ($page_num > 0) {
                $this->add_new_page();
            }
            
            $this->render_page_entries($entries);
        }

        return true;
    }

    /**
     * Add new page with background
     */
    private function add_new_page() {
        $this->mpdf->AddPage();
        $this->apply_background();
    }

    /**
     * Render entries on current page
     * 
     * @param array $entries Page entries
     */
    private function render_page_entries($entries) {
        foreach ($entries as $entry) {
            $html = $this->generate_entry_html($entry);
            $this->place_entry($html, $entry['y_position']);
        }
    }
}
```

## Data Structures

### 1. Course Entry Data
```php
array(
    'id'          => int,     // Course ID
    'title'       => string,  // Course title
    'date'        => string,  // Completion date
    'instructor'  => string,  // Instructor name
    'font_size'   => int,     // Font size in points
    'font_family' => string,  // Font family name
    'y_position'  => float,   // Y coordinate in mm
    'height'      => float    // Entry height in mm
)
```

### 2. Page Configuration
```php
array(
    'margins' => array(
        'bottom' => float,  // Bottom margin in mm
        'field'  => float,  // Space between fields in mm
        'course' => float   // Space between courses in mm
    ),
    'dimensions' => array(
        'width'   => float, // Page width in mm
        'height'  => float, // Page height in mm
        'start_y' => float  // Initial Y coordinate in mm
    )
)
```

## Error Handling

1. **Page Overflow Detection**
   - Check if content exceeds maximum pages (e.g., 10 pages)
   - Validate entry heights against page dimensions
   - Handle oversized single entries

2. **Resource Management**
   - Monitor memory usage during generation
   - Implement cleanup for temporary resources
   - Handle background image loading failures

3. **Data Validation**
   - Validate course data completeness
   - Check font availability
   - Verify coordinate validity

## Performance Considerations

1. **Memory Optimization**
   - Process entries in chunks
   - Clean up generated HTML after use
   - Optimize image handling

2. **Processing Efficiency**
   - Cache calculated dimensions
   - Reuse font metrics
   - Batch process page generation

3. **File Size Management**
   - Optimize background image
   - Minimize HTML markup
   - Control font embedding

## Testing Requirements

1. **Unit Tests**
   - Test dimension calculations
   - Verify content distribution
   - Validate page generation

2. **Integration Tests**
   - Test with various course counts
   - Verify background consistency
   - Check coordinate accuracy

3. **Edge Cases**
   - Test with maximum content
   - Verify minimum content
   - Check various font sizes 