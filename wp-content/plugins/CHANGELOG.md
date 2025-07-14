# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Added comprehensive documentation generation instructions to README.md
  - Instructions for LearnDash Certificate Builder documentation
  - Instructions for WDM CEU Customization documentation
  - Prerequisites and viewing instructions

### Fixed
- Font settings (size, family, text transform) for username marker now save correctly.
- Updated PHP handler to preserve font settings when saving coordinates.
- Updated JavaScript save handler to include font settings in AJAX request.
- Fixed saving coordinates when no background image is selected.
- Added better error handling for AJAX requests.
- Updated PHP handler to properly handle 'default' background ID.

### Changed
- Removed completion date marker from default coordinates.
- Added font settings for username marker including font size, font family, and text transform options.
- Removed requirement for background image when saving coordinates.
- Refactored get_course_completion_date method to reduce return statements and improve maintainability
- Refactored certificate download methods to reduce return statements and improve error handling
  - Simplified download_certificate method
  - Simplified stream_certificate method

### Added
- Font settings for username marker
  - Added font size control
  - Added font family selection (Arial, Times New Roman, Helvetica, Georgia)
  - Added text transform options (None, UPPERCASE, lowercase, Capitalize)
- Added support for saving default coordinates without background image.

### Removed
- Completion date marker from certificate builder
  - Removed from default coordinates array
  - Removed from elements array in settings page
  - Previous working state had: completion_date with default x:100, y:200 coordinates

### Notes
- Working state backup (if needed to restore):
  ```php
  // Default coordinates
  'completion_date' => array(
      'x' => 100,
      'y' => 200,
  ),
  
  // Elements array
  'completion_date' => __('Completion Date', 'learndash-certificate-builder'),
  ```

- Font settings backup (if needed to restore):
  ```php
  // Default coordinates structure before font settings
  'user_name' => array(
      'x' => 100,
      'y' => 100,
  ),
  ``` 

[Unreleased]: https://github.com/username/learndash-certificate-builder/compare/v1.0.0...HEAD 