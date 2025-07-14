<?php
/**
 * Uninstall LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit( 'Direct access denied.' );
}

// Verify user capabilities.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit( 'Insufficient permissions.' );
}

// Delete the plugin version number option used for managing updates and migrations.
delete_option( 'lcb_version' );

// Delete the stored coordinates for certificate elements (text positions, image placements, etc.).
// These coordinates were used to determine where different elements should be placed on the certificate.
delete_option( 'lcb_element_coordinates' );

// Delete certificate files.
$upload_dir = wp_upload_dir();
if ( ! is_array( $upload_dir ) || empty( $upload_dir['basedir'] ) ) {
	return;
}

// Sanitize and build the certificates directory path.
$cert_dir = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . 'certificates' );

// Initialize files array.
$files = array();

// Check if directory exists and is accessible.
if ( file_exists( $cert_dir ) && is_dir( $cert_dir ) && is_readable( $cert_dir ) ) {
	// Get all PDF files in directory with error handling.
	$glob_result = glob( $cert_dir . '/*.pdf' );
	if ( false !== $glob_result ) {
		$files = array_filter( $glob_result, 'is_file' );
	}

	// Process files if any exist.
	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			// Verify file is within certificates directory.
			if ( 0 !== strpos( wp_normalize_path( $file ), $cert_dir ) ) {
				continue;
			}

			// Check file permissions and delete.
			if ( is_readable( $file ) && is_writable( $file ) ) {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Silencing warning is acceptable here.
				@unlink( $file );
			}
		}
	}

	// Check if directory is empty before removing.
	$remaining_files = array_diff( scandir( $cert_dir ), array( '.', '..' ) );
	if ( empty( $remaining_files ) && is_writable( $cert_dir ) ) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Silencing warning is acceptable here.
		@rmdir( $cert_dir );
	}
}
