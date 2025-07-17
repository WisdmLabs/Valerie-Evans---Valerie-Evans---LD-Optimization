# LearnDash Certificate Builder

## Overview
The LearnDash Certificate Builder is a WordPress plugin that enhances LearnDash LMS by providing a certificate generation system. It allows course creators to design custom certificates for their courses using a builder interface.

**Version:** 1.0.1  
**Requires at least:** WordPress 5.8  
**Requires PHP:** 7.4  
**Requires:** LearnDash LMS  

## Features
- Certificate builder interface
- PDF certificate generation using mPDF
- Support for custom images
- Dynamic data insertion from course completion
- Basic shortcode for certificate display
- Secure certificate download handling
- Administrative settings interface

## Installation

1. Upload the plugin files to `/wp-content/plugins/learndash-certificate-builder/` directory
2. Ensure you have LearnDash LMS installed and activated
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Certificate Builder in the WordPress admin menu to configure settings

## Configuration

### Accessing the Builder
1. Log in to your WordPress admin panel
2. Navigate to Certificate Builder in the main menu
3. Use the builder interface to design your certificates

### Available Elements

#### Text Elements
- Student Name
- Course Title
- Completion Date
- Custom Text

#### Image Elements
- Background Template
- Logo
- Signature

### Using the Certificate Builder

1. **Configure Settings**
   - Upload background image
   - Set certificate dimensions
   - Configure text elements

2. **Position Elements**
   - Use the interface to position elements
   - Save element positions

### Shortcode Usage

Display certificate generation form:
```php
[learndash_custom_certificate]
```

The shortcode displays:
- A form for completed courses (if user is logged in and has completed courses)
- Login message (if user is not logged in)
- Completion message (if user has no completed courses)

## PDF Generation

### Supported Features
- Custom page sizes
- Image handling
- Text positioning
- Dynamic data insertion

### Best Practices

1. **Image Optimization**
   - Use web-optimized images
   - Recommended formats: JPG, PNG
   - Keep file sizes reasonable
   - Use appropriate resolution

2. **Layout Design**
   - Test with different content lengths
   - Consider printer margins
   - Design for both digital and print use

## Troubleshooting

### Common Issues

1. **PDF Generation Fails**
   - Check PHP memory limit (64MB minimum recommended)
   - Verify file permissions
   - Ensure all assets are accessible
   - Check error logs for specific issues

2. **Image Problems**
   - Check image file permissions
   - Verify image path is correct
   - Ensure image format is supported
   - Optimize large images

### Support
For technical support, please:
1. Check the plugin documentation
2. Review WordPress error logs
3. Contact support with:
   - WordPress version
   - LearnDash version
   - Error messages
   - Steps to reproduce issues

## Technical Details

### System Requirements
- PHP 7.4 or higher
- WordPress 5.8 or higher
- LearnDash LMS
- mPDF 8.2.1 or higher
- Minimum 64MB PHP memory limit

### File Structure
```
learndash-certificate-builder/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── admin/
│   ├── data/
│   ├── generation/
│   ├── position/
│   └── download/
├── templates/
├── languages/
└── vendor/
```

## Changelog

### 1.0.1
- Initial public release
- Basic certificate builder interface
- PDF generation with mPDF
- LearnDash course completion integration

## License
GPL-2.0-or-later 
