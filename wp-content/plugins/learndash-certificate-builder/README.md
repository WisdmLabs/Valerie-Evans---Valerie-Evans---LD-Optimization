# LearnDash Certificate Builder

A WordPress plugin that extends LearnDash LMS to provide custom certificate generation capabilities with precise element positioning.

## Features

- Visual drag-and-drop interface for positioning certificate elements
- Custom background image support
- Signature image upload
- Course completion verification
- PDF certificate generation with precise element positioning
- Multiple delivery options (download or view in browser)
- Secure certificate storage and delivery

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- LearnDash LMS plugin
- Composer (for installation)

## Installation

1. Download the plugin
2. Upload to your WordPress plugins directory
3. Run `composer install` in the plugin directory to install dependencies
4. Activate the plugin through WordPress admin

## Usage

### Admin Settings

1. Navigate to LearnDash > Certificate Builder
2. Upload or select a background image
3. Upload a signature image
4. Use the drag-and-drop interface to position elements:
   - User name
   - Completion date
   - Course list
   - Signature

### Frontend

1. Add the shortcode `[learndash_custom_certificate]` to any page
2. Users will see their completed courses
3. They can select courses and generate a certificate
4. Choose to download or view the certificate in browser

## Development

### Directory Structure

```
learndash-certificate-builder/
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── Admin/
│   ├── Data/
│   ├── Download/
│   ├── Generation/
│   └── Position/
├── templates/
│   ├── admin/
│   └── frontend/
├── languages/
├── composer.json
├── class-learndash-certificate-builder.php
└── learndash-certificate-builder.php
```

### Modules

1. **UI Module**: Handles admin settings and frontend interface
2. **Data Retrieval**: Manages course completion data
3. **Position Management**: Handles element coordinate storage
4. **Certificate Generation**: Creates PDF certificates using mPDF
5. **Download Management**: Handles secure certificate delivery

## Support

For support, please contact [support@example.com](mailto:support@example.com)

## License

GPL v2 or later 