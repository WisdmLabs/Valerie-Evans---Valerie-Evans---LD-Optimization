# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Created new LearnDash Simple Certificate Generator plugin following Approach 1
  - Implemented main plugin file with necessary headers and initialization
  - Created certificate form template with course selection and customization options
  - Added Data Handler class for fetching course and user data
  - Added Certificate Generator class for PDF generation using TCPDF
  - Added Certificate UI class for handling form submissions and AJAX requests
  - Added JavaScript functionality for form handling and PDF downloads
  - Added CSS styles for a clean and professional form layout
  - Implemented proper security measures and error handling
  - Added support for custom header and footer text
  - Added option to include completion dates
  - Added proper PDF file naming and delivery
- Implemented Module 1: User Interface (UI) Module
  - Created main plugin file with necessary headers and initialization
  - Added Certificate UI class for handling certificate customization
  - Created certificate selection form template with course selection and customization options
  - Added CSS styles for a clean and professional form layout
  - Implemented JavaScript functionality for form handling and AJAX requests
  - Added preview functionality for certificates
  - Implemented responsive design for mobile devices
- Feature: Added a new admin setting to allow customization of the checkout notice message shown when the course/group limit is exceeded. The message is now plain text (no counts/variables) and can be set from the plugin settings page. (See: `class-wdm-ld-woo-queue-manager.php`)
- Feature: Added a new shortcode [ld_woo_queue_notice] to display the custom queue notice anywhere (e.g., after [uo_show]) if the course/group limit is exceeded. (See: `class-wdm-ld-woo-queue-manager.php`)

### Changed
- Updated file naming to follow WordPress coding standards
- Fixed linter errors in PHP files
- Improved code documentation and comments

### Security
- Added nonce verification for form submissions
- Added proper data sanitization and validation
- Added proper PDF content type headers
- Added user authentication checks 