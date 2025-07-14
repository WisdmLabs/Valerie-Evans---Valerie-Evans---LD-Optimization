# Valerie-Evans---Valerie-Evans---LD-Optimization
This is a new repository

## Generating Documentation

This repository uses Doxygen to generate documentation for two components:
1. LearnDash Certificate Builder
2. WDM CEU Customization

### Prerequisites

- Install Doxygen (version 1.9.8 or later recommended)
  ```bash
  sudo apt-get install doxygen  # For Ubuntu/Debian
  ```

### Generating Documentation

#### LearnDash Certificate Builder Documentation

1. Navigate to the wp-content directory:
   ```bash
   cd wp-content
   ```

2. Run Doxygen with the Certificate Builder configuration:
   ```bash
   doxygen LearndashCertificateBuilderDoxyfile
   ```

The documentation will be generated in `wp-content/Documentation/Learndash Certificate Builder/`.

#### WDM CEU Customization Documentation

1. Navigate to the wp-content directory:
   ```bash
   cd wp-content
   ```

2. Run Doxygen with the WDM CEU configuration:
   ```bash
   doxygen wdmCeueyLDCustomizationDoxyfile
   ```

The documentation will be generated in its respective output directory as specified in the Doxyfile.

### Viewing Documentation

After generation, open the generated `index.html` file in your preferred web browser to view the documentation.
