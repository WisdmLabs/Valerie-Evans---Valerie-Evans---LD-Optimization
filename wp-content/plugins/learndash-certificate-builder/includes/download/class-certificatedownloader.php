<?php
/**
 * Handles PDF certificate download operations.
 *
 * @file
 * Certificate Downloader functionality for LearnDash Certificate Builder.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */

namespace LearnDash_Certificate_Builder\Download;

/**
 * Manages PDF certificate operations.
 *
 * @brief Certificate downloader class for handling PDF certificate operations.
 *
 * @details This class provides functionality to handle PDF certificate operations including:
 * - Downloading certificates as attachments
 * - Streaming certificates for browser viewing
 * - Saving certificates to server
 * - Deleting saved certificates
 *
 * All operations include security measures and proper file handling to ensure
 * safe certificate delivery and storage.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */
class CertificateDownloader {
	/**
	 * Defines size limit for certificate files.
	 *
	 * @brief Maximum allowed file size for certificates.
	 *
	 * @var int MAX_FILE_SIZE
	 * @details Defines the maximum allowed size for certificate files (50MB).
	 * @access private
	 */
	private const MAX_FILE_SIZE = 52428800;

	/**
	 * Downloads certificate as attachment.
	 *
	 * @brief Downloads a certificate as an attachment.
	 *
	 * @details Handles the secure delivery of PDF certificates as downloadable files.
	 * Sets appropriate headers for file download, performs security checks, and
	 * ensures proper content delivery.
	 *
	 * @param string $pdf_content The PDF content as binary string.
	 * @param string $filename The desired filename for download.
	 * @return bool True on successful download, false on failure.
	 * @throws \Exception When file handling errors occur.
	 * @access public
	 * @since 1.0.0
	 *
	 * @see stream_certificate() For in-browser viewing alternative.
	 */
	public function download_certificate( $pdf_content, $filename ) {
		return $this->deliver_certificate( $pdf_content, $filename, 'attachment' );
	}

	/**
	 * Stream certificate PDF to browser
	 *
	 * @param string $pdf_content PDF content as string.
	 * @param string $filename Desired filename.
	 * @return bool Whether the streaming was successful.
	 */
	public function stream_certificate( $pdf_content, $filename ) {
		return $this->deliver_certificate( $pdf_content, $filename, 'inline' );
	}

	/**
	 * Handle certificate delivery to browser
	 *
	 * @param string $pdf_content PDF content as string.
	 * @param string $filename Desired filename.
	 * @param string $disposition Content disposition type ('attachment' or 'inline').
	 * @return bool Whether the delivery was successful.
	 */
	private function deliver_certificate( $pdf_content, $filename, $disposition ) {
		$success = false;

		try {
			// Validate conditions before proceeding.
			$can_proceed = ! headers_sent( $filename, $line ) &&
				strlen( $pdf_content ) <= self::MAX_FILE_SIZE;

			if ( $can_proceed ) {
				// Clean output buffer.
				while ( ob_get_level() ) {
					ob_end_clean();
				}

				// Prevent any extra output.
				if ( ob_get_length() ) {
					ob_clean();
				}

				// Set delivery headers.
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: application/pdf; charset=binary' );
				header( 'Content-Disposition: ' . $disposition . '; filename="' . sanitize_file_name( $filename ) . '"' );
				header( 'Content-Length: ' . strlen( $pdf_content ) );
				header( 'Content-Transfer-Encoding: binary' );
				if ( 'inline' === $disposition ) {
					header( 'Accept-Ranges: bytes' );
				}
				header( 'Cache-Control: private, no-transform, no-store, must-revalidate, max-age=0' );
				header( 'Pragma: public' );
				header( 'Expires: 0' );
				header( 'X-Content-Type-Options: nosniff' );

				// Disable compression.
				if ( ini_get( 'zlib.output_compression' ) ) {
					ini_set( 'zlib.output_compression', 'Off' );
				}

				// Output file content.
				flush();
				echo $pdf_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				flush();
				$success = true;
			}
		} catch ( \Exception $e ) {
			$success = false;
		}

		return $success;
	}

	/**
	 * Save certificate to server
	 *
	 * @param string $pdf_content PDF content as string.
	 * @param string $filename Desired filename.
	 * @return string|false Path to saved file or false on failure.
	 */
	public function save_certificate( $pdf_content, $filename ) {
		try {
			// Get upload directory.
			$upload_dir = wp_upload_dir();
			$cert_dir   = $upload_dir['basedir'] . '/certificates';

			// Create certificates directory if it doesn't exist.
			if ( ! file_exists( $cert_dir ) ) {
				wp_mkdir_p( $cert_dir );

				// Create .htaccess to prevent direct access.
				$htaccess = $cert_dir . '/.htaccess';
				if ( ! file_exists( $htaccess ) ) {
					$htaccess_content = "Order deny,allow\nDeny from all";
					file_put_contents( $htaccess, $htaccess_content );
				}

				// Create index.php to prevent directory listing.
				$index = $cert_dir . '/index.php';
				if ( ! file_exists( $index ) ) {
					file_put_contents( $index, '<?php // Silence is golden' );
				}
			}

			// Generate unique filename.
			$safe_filename   = sanitize_file_name( $filename );
			$unique_filename = wp_unique_filename( $cert_dir, $safe_filename );
			$file_path       = $cert_dir . '/' . $unique_filename;

			// Save file.
			$result = file_put_contents( $file_path, $pdf_content );
			if ( false === $result ) {
				return false;
			}

			return $file_path;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Delete saved certificate
	 *
	 * @param string $file_path Path to certificate file.
	 * @return bool Whether the deletion was successful.
	 */
	public function delete_certificate( $file_path ) {
		try {
			// Verify file is in certificates directory.
			$upload_dir = wp_upload_dir();
			$cert_dir   = $upload_dir['basedir'] . '/certificates';

			// Return true for non-existent files or successful deletion.
			$result = true;

			if ( strpos( $file_path, $cert_dir ) === 0 && file_exists( $file_path ) ) {
				$result = unlink( $file_path );
			}

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
