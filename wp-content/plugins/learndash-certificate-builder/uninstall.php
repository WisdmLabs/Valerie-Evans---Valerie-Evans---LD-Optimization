<?php
/**
 * Uninstall LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'lcb_version' );
delete_option( 'lcb_element_coordinates' );

// Delete certificate files.
$upload_dir = wp_upload_dir();
$cert_dir   = $upload_dir['basedir'] . '/certificates';

if ( file_exists( $cert_dir ) ) {
	// Get all files in directory.
	$files = glob( $cert_dir . '/*' );

	// Delete each file.
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			unlink( $file );
		}
	}

	// Remove directory.
	rmdir( $cert_dir );
}
