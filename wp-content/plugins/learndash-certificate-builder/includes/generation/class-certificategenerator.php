<?php
/**
 * Certificate Generator class for LearnDash Certificate Builder
 *
 * @package LearnDash_Certificate_Builder
 */

namespace LearnDash_Certificate_Builder\Generation;

use \Mpdf\Mpdf;
use \Mpdf\MpdfException;
use LearnDash_Certificate_Builder\Position\PositionManager;
use LearnDash_Certificate_Builder\Data\DataRetriever;

/**
 * Class CertificateGenerator
 * Handles PDF certificate generation using mPDF
 */
class CertificateGenerator {
	/**
	 * Position manager instance
	 *
	 * @var PositionManager
	 */
	private $position_manager;

	/**
	 * Data retriever instance
	 *
	 * @var DataRetriever
	 */
	private $data_retriever;

	/**
	 * Constructor
	 *
	 * @param PositionManager $position_manager Position manager instance.
	 * @param DataRetriever   $data_retriever Data retriever instance.
	 */
	public function __construct( PositionManager $position_manager, DataRetriever $data_retriever ) {
		$this->position_manager = $position_manager;
		$this->data_retriever   = $data_retriever;
	}

	/**
	 * Generate certificate PDF
	 *
	 * @param int   $user_id User ID.
	 * @param array $course_ids Array of course IDs.
	 * @param int   $background_id Background image ID.
	 * @return string|false PDF content as string or false on failure.
	 */
	public function generate_certificate( $user_id, $course_ids, $background_id ) {
		try {
			$mpdf = $this->init_mpdf();

			// Get element coordinates.
			$coordinates = $this->position_manager->get_coordinates( $background_id );

			// Set background image.
			$background_url = wp_get_attachment_url( $background_id );
			if ( $background_url ) {
				$mpdf->SetDefaultBodyCSS( 'background', "url($background_url)" );
				$mpdf->SetDefaultBodyCSS( 'background-image-resize', 6 );
				$mpdf->SetDefaultBodyCSS( 'background-position', 'center top' );
				$mpdf->SetDefaultBodyCSS( 'background-repeat', 'no-repeat' );
				$mpdf->SetDefaultBodyCSS( 'background-size', 'contain' );
			}

			// Add user name with larger font.
			$user_name = $this->data_retriever->get_user_display_name( $user_id );
			if ( $user_name && isset( $coordinates['user_name'] ) ) {
				$user_name_html = sprintf( '<div style="font-size: 24pt; font-weight: bold;">%s</div>', esc_html( $user_name ) );
				$this->add_element( $mpdf, $user_name_html, $coordinates['user_name'] );
			}

			// Add completion date.
			$completion_date = current_time( get_option( 'date_format' ) );
			if ( isset( $coordinates['completion_date'] ) ) {
				$date_html = sprintf( '<div style="font-size: 14pt;">%s</div>', esc_html( $completion_date ) );
				$this->add_element( $mpdf, $date_html, $coordinates['completion_date'] );
			}

			// Add course list.
			if ( isset( $coordinates['course_list'] ) ) {
				$course_list = $this->get_formatted_course_list( $user_id, $course_ids );
				$this->add_element( $mpdf, $course_list, $coordinates['course_list'] );
			}

			// Add signature if available.
			$signature_id = get_option( 'lcb_signature_image' );
			if ( $signature_id && isset( $coordinates['signature'] ) ) {
				$signature_url = wp_get_attachment_url( $signature_id );
				if ( $signature_url ) {
					$this->add_image( $mpdf, $signature_url, $coordinates['signature'] );
				}
			}

			return $mpdf->Output( '', 'S' );
		} catch ( MpdfException $e ) {
			error_log( 'LearnDash Certificate Builder - PDF Generation Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Initialize mPDF with custom configuration
	 *
	 * @return Mpdf mPDF instance.
	 * @throws MpdfException When mPDF initialization fails.
	 */
	private function init_mpdf() {
		// Get background image dimensions.
		$background_id = get_option( 'lcb_background_image' );
		$image_data    = wp_get_attachment_image_src( $background_id, 'full' );

		// Default to A4 landscape if no image.
		$format     = 'A4-L';
		$img_width  = 297; // A4 landscape width in mm.
		$img_height = 210; // A4 landscape height in mm.

		// If we have image dimensions, use them.
		if ( $image_data ) {
			// Convert pixels to mm (assuming 96 DPI).
			$px_to_mm   = 25.4 / 96;
			$img_width  = round( $image_data[1] * $px_to_mm );
			$img_height = round( $image_data[2] * $px_to_mm );
			$format     = array( $img_width, $img_height );
		}

		$config = array(
			'mode'          => 'utf-8',
			'format'        => $format,
			'margin_left'   => 0,
			'margin_right'  => 0,
			'margin_top'    => 0,
			'margin_bottom' => 0,
			'tempDir'       => wp_upload_dir()['basedir'] . '/mpdf',
			'dpi'           => 96,
			'img_dpi'       => 96,
			'pdf_unit'      => 'mm',
		);

		// Create temp directory if it doesn't exist.
		if ( ! file_exists( $config['tempDir'] ) ) {
			wp_mkdir_p( $config['tempDir'] );
		}

		$mpdf = new Mpdf( $config );

		// Set default CSS.
		$mpdf->WriteHTML(
			'
			<style>
				body { margin: 0; padding: 0; }
				.certificate-element { font-family: Arial, sans-serif; }
			</style>
		'
		);

		return $mpdf;
	}

	/**
	 * Add text element to PDF
	 *
	 * @param Mpdf   $mpdf mPDF instance.
	 * @param string $content Element content.
	 * @param array  $position Element position.
	 */
	private function add_element( $mpdf, $content, $position ) {
		// Convert position from pixels to mm
		$x = isset( $position['x'] ) ? round( $position['x'] * 25.4 / 96 ) : 0;
		$y = isset( $position['y'] ) ? round( $position['y'] * 25.4 / 96 ) : 0;

		$html = sprintf(
			'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm;">%s</div>',
			$x,
			$y,
			wp_kses_post( $content )
		);
		$mpdf->WriteHTML( $html );
	}

	/**
	 * Add image element to PDF
	 *
	 * @param Mpdf   $mpdf mPDF instance.
	 * @param string $image_url Image URL.
	 * @param array  $position Image position.
	 */
	private function add_image( $mpdf, $image_url, $position ) {
		// Convert position from pixels to mm
		$x = isset( $position['x'] ) ? round( $position['x'] * 25.4 / 96 ) : 0;
		$y = isset( $position['y'] ) ? round( $position['y'] * 25.4 / 96 ) : 0;

		$html = sprintf(
			'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm;"><img src="%s" style="max-width: 50mm;"></div>',
			$x,
			$y,
			esc_url( $image_url )
		);
		$mpdf->WriteHTML( $html );
	}

	/**
	 * Get formatted course list
	 *
	 * @param int   $user_id User ID.
	 * @param array $course_ids Array of course IDs.
	 * @return string Formatted course list HTML.
	 */
	private function get_formatted_course_list( $user_id, $course_ids ) {
		$html = '<div class="certificate-element" style="font-size: 12pt; line-height: 1.4;">';

		foreach ( $course_ids as $course_id ) {
			$completion_date = $this->data_retriever->get_course_completion_date( $user_id, $course_id );
			$course_title    = get_the_title( $course_id );

			$html .= sprintf(
				'<div style="margin-bottom: 3mm;">%s<br><span style="font-size: 10pt; color: #666;">%s</span></div>',
				esc_html( $course_title ),
				esc_html( $completion_date )
			);
		}

		$html .= '</div>';
		return $html;
	}
}
