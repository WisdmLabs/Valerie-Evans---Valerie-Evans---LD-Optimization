<?php
/**
 * Certificate Generator for LearnDash.
 *
 * @file
 * Handles PDF certificate generation using mPDF library.
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */

namespace LearnDash_Certificate_Builder\Generation;

use \Mpdf\Mpdf;
use \Mpdf\MpdfException;
use LearnDash_Certificate_Builder\Position\PositionManager;
use LearnDash_Certificate_Builder\Data\DataRetriever;

/**
 * PDF Certificate Generator.
 *
 * @brief Handles generation of PDF certificates.
 *
 * @details This class is responsible for generating PDF certificates using mPDF library.
 * It handles:
 * - PDF initialization with custom configuration
 * - Background image placement
 * - Text and image element positioning
 * - Course list formatting and pagination
 * - User name and signature placement
 *
 * @package LearnDash_Certificate_Builder
 * @since 1.0.0
 */
class CertificateGenerator {
	/**
	 * Position manager instance.
	 *
	 * @brief Position manager for element coordinates.
	 *
	 * @var PositionManager
	 * @access private
	 * @since 1.0.0
	 */
	private $position_manager;

	/**
	 * Data retriever instance.
	 *
	 * @brief Data retriever for user and course information.
	 *
	 * @var DataRetriever
	 * @access private
	 * @since 1.0.0
	 */
	private $data_retriever;

	/**
	 * Constructor for CertificateGenerator.
	 *
	 * @brief Initializes the certificate generator.
	 *
	 * @details Creates a new instance with position management and data retrieval capabilities.
	 *
	 * @param PositionManager $position_manager Position manager instance.
	 * @param DataRetriever   $data_retriever Data retriever instance.
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( PositionManager $position_manager, DataRetriever $data_retriever ) {
		$this->position_manager = $position_manager;
		$this->data_retriever   = $data_retriever;
	}

	/**
	 * Generate PDF certificate.
	 *
	 * @brief Generates a PDF certificate.
	 *
	 * @details Creates a PDF certificate with background image, user information,
	 * course list, and signature. Handles positioning and pagination of elements.
	 *
	 * @param int   $user_id User ID.
	 * @param array $course_ids Array of course IDs.
	 * @param int   $background_id Background image ID.
	 * @return string|false PDF content as string or false on failure.
	 * @throws MpdfException When PDF generation fails.
	 * @access public
	 * @since 1.0.0
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

			// Add formatted course list to certificate.
			if ( isset( $coordinates['course_list'] ) ) {
				$course_entries = $this->get_formatted_course_list( $user_id, $course_ids );
				$this->add_course_list( $mpdf, $course_entries, $coordinates['course_list'], $user_id );
			}

			return $mpdf->Output( '', 'S' );
		} catch ( MpdfException $e ) {
			return false;
		}
	}

	/**
	 * Initialize mPDF configuration.
	 *
	 * @brief Initializes mPDF with custom configuration.
	 *
	 * @details Sets up mPDF instance with:
	 * - Custom page dimensions based on background image
	 * - Zero margins for precise element positioning
	 * - DPI settings for consistent rendering
	 * - Multi-page support configuration
	 * - Default CSS styles
	 *
	 * @return Mpdf mPDF instance.
	 * @throws MpdfException When mPDF initialization fails.
	 * @access private
	 * @since 1.0.0
	 */
	private function init_mpdf() {
		// Get background image dimensions.
		$background_id = get_option( 'lcb_background_image' );
		$image_data    = wp_get_attachment_image_src( $background_id, 'full' );

		// Default to A4 landscape if no image.
		$format = 'A4-L';

		// If we have image dimensions, use them.
		if ( $image_data ) {
			// Convert pixels to mm (assuming 96 DPI).
			$px_to_mm   = 25.4 / 96;
			$img_width  = round( $image_data[1] * $px_to_mm );
			$img_height = round( $image_data[2] * $px_to_mm );
			$format     = array( $img_width, $img_height );
		}

		$config = array(
			'mode'                     => 'utf-8',
			'format'                   => $format,
			'margin_left'              => 0,
			'margin_right'             => 0,
			'margin_top'               => 0,
			'margin_bottom'            => 0,
			'tempDir'                  => wp_upload_dir()['basedir'] . '/mpdf',
			'dpi'                      => 96,
			'img_dpi'                  => 96,
			'pdf_unit'                 => 'mm',
			// Add settings for multi-page support.
			'autoPageBreak'            => true,
			'setAutoTopMargin'         => 'stretch',
			'setAutoBottomMargin'      => 'stretch',
			'useFixedNormalLineHeight' => true,
			'adjustFontDescLineheight' => 1.5,
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
				body {
					margin: 0;
					padding: 0;
				}
				.certificate-element {
					font-family: Arial, sans-serif;
				}
				.course-list {
					page-break-inside: avoid;
					margin-bottom: 10mm;
				}
			</style>
		'
		);

		// Set page margins after initialization for more control.
		$mpdf->SetMargins( 0, 0, 0, 0 );

		// Enable automatic page breaks.
		$mpdf->SetAutoPageBreak( true, 0 );

		return $mpdf;
	}

	/**
	 * Add text element to certificate.
	 *
	 * @brief Adds a text element to the PDF.
	 *
	 * @details Positions and adds a text element to the PDF with proper formatting.
	 * Converts pixel coordinates to millimeters for accurate placement.
	 *
	 * @param Mpdf   $mpdf mPDF instance.
	 * @param string $content Element content.
	 * @param array  $position Element position coordinates.
	 * @access private
	 * @since 1.0.0
	 */
	private function add_element( $mpdf, $content, $position ) {
		// Convert position from pixels to mm.
		$x = isset( $position['x'] ) ? round( $position['x'] * 25.4 / 96 ) : 0;
		$y = isset( $position['y'] ) ? round( $position['y'] * 25.4 / 96 ) : 0;

		// If this is a username element (identified by the font-weight: bold style).
		if ( strpos( $content, 'font-weight: bold' ) !== false ) {
			// Extract text content.
			$text_content = strip_tags( $content );

			// Get font settings from position.
			$font_size   = isset( $position['font_size'] ) ? $position['font_size'] : 24;
			$font_family = isset( $position['font_family'] ) ? $position['font_family'] : 'Arial';

			// Set font before measuring.
			$mpdf->SetFont( $font_family, 'B', $font_size );

			// Get text width in mm.
			$width_mm = $mpdf->GetStringWidth( $text_content );
			$x_offset = $width_mm / 2;

			$html = sprintf(
				'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm;">%s</div>',
				$x - $x_offset,
				$y,
				$content
			);
		} else {
			$html = sprintf(
				'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm;">%s</div>',
				$x,
				$y,
				$content
			);
		}

		$mpdf->WriteHTML( $html );
	}

	/**
	 * Add image element to certificate.
	 *
	 * @brief Adds an image element to the PDF.
	 *
	 * @details Positions and adds an image element to the PDF.
	 * Converts pixel coordinates to millimeters and applies size constraints.
	 *
	 * @param Mpdf   $mpdf mPDF instance.
	 * @param string $image_url Image URL.
	 * @param array  $position Image position coordinates.
	 * @access private
	 * @since 1.0.0
	 */
	private function add_image( $mpdf, $image_url, $position ) {
		// Convert position from pixels to mm.
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
	 * Format course list entries.
	 *
	 * @brief Formats the course list with styling.
	 *
	 * @details Creates formatted course entries with:
	 * - Custom font settings from coordinates
	 * - Course title, completion date, and instructor
	 * - Proper spacing and margins
	 * - Height calculations for pagination
	 *
	 * @param int   $user_id User ID.
	 * @param array $course_ids Array of course IDs.
	 * @return array Array of formatted course entries with height information.
	 * @access private
	 * @since 1.0.0
	 */
	private function get_formatted_course_list( $user_id, $course_ids ) {
		// Get font settings from coordinates.
		$coordinates   = get_option( 'lcb_element_coordinates', array() );
		$background_id = get_option( 'lcb_background_image', 'default' );
		$font_settings = isset( $coordinates[ $background_id ]['course_list'] ) ? $coordinates[ $background_id ]['course_list'] : array();

		$font_size      = isset( $font_settings['font_size'] ) ? $font_settings['font_size'] : 18;
		$font_family    = isset( $font_settings['font_family'] ) ? $font_settings['font_family'] : 'Arial';
		$text_transform = isset( $font_settings['text_transform'] ) ? $font_settings['text_transform'] : 'none';

		// Convert margins from pixels to mm using the same ratio as positions.
		$course_margin = round( 100 * 25.4 / 96 ); // 100px converted to mm
		$field_margin  = round( 10 * 25.4 / 96 );   // 10px converted to mm

		$style = sprintf(
			'font-family: %s; font-size: %spt; text-transform: %s; line-height: 1.6;',
			esc_attr( $font_family ),
			esc_attr( $font_size ),
			esc_attr( $text_transform )
		);

		// Return array of course entries instead of single HTML string.
		$course_entries = array();

		foreach ( $course_ids as $course_id ) {
			$completion_date = $this->data_retriever->get_course_completion_date( $user_id, $course_id );
			$course_title    = get_the_title( $course_id );
			$instructor      = 'Valerie Evans, BCBA-D';

			$course_entries[] = array(
				'html'   => sprintf(
					'<div class="certificate-element" style="%s">
						<div style="margin-bottom: %dmm;">
							<div style="margin-bottom: %dmm;"><strong>Title:</strong> %s</div>
							<div style="margin-bottom: %dmm;"><strong>Completion Date:</strong> %s</div>
							<div style="margin-bottom: %dmm;"><strong>Instructor:</strong> %s</div>
						</div>
					</div>',
					$style,
					$course_margin,
					$field_margin,
					esc_html( $course_title ),
					$field_margin,
					esc_html( $completion_date ),
					$field_margin,
					esc_html( $instructor )
				),
				'height' => $course_margin + ( $field_margin * 3 ) + ( round( $font_size * 1.6 * 25.4 / 96 ) * 3 ), // Account for line-height and add extra padding.
			);
		}

		return $course_entries;
	}

	/**
	 * Add paginated course list.
	 *
	 * @brief Adds paginated course list to the PDF.
	 *
	 * @details Handles course list placement with:
	 * - Automatic pagination
	 * - Fixed elements on each page
	 * - Proper spacing and margins
	 * - Position calculations
	 *
	 * @param Mpdf  $mpdf mPDF instance.
	 * @param array $course_entries Array of course entries.
	 * @param array $initial_position Initial position for course list.
	 * @param int   $user_id User ID.
	 * @access private
	 * @since 1.0.0
	 */
	private function add_course_list( $mpdf, $course_entries, $initial_position, $user_id ) {
		// Convert initial position from pixels to mm.
		$start_x = isset( $initial_position['x'] ) ? round( $initial_position['x'] * 25.4 / 96 ) : 0;
		$start_y = isset( $initial_position['y'] ) ? round( $initial_position['y'] * 25.4 / 96 ) : 0;

		$current_y     = $start_y;
		$page_height   = $mpdf->h; // Get page height in mm.
		$margin_bottom = 40; // Bottom margin in mm.

		// Store coordinates for fixed elements.
		$coordinates = $this->position_manager->get_coordinates( get_option( 'lcb_background_image' ) );
		$user_name   = $this->data_retriever->get_user_display_name( $user_id );

		// Add page number if coordinates exist.
		if ( isset( $coordinates['page_number'] ) ) {
			$page_number_html = sprintf(
				'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm; font-family: %s; font-size: %spt;">Page {PAGENO} of {nbpg}</div>',
				round( $coordinates['page_number']['x'] * 25.4 / 96 ),
				round( $coordinates['page_number']['y'] * 25.4 / 96 ),
				isset( $coordinates['page_number']['font_family'] ) ? $coordinates['page_number']['font_family'] : 'Arial',
				isset( $coordinates['page_number']['font_size'] ) ? $coordinates['page_number']['font_size'] : '12'
			);
			$mpdf->WriteHTML( $page_number_html );
		}

		// Get signature information.
		$signature_id  = get_option( 'lcb_signature_image' );
		$signature_url = $signature_id ? wp_get_attachment_url( $signature_id ) : false;

		// Add fixed elements function.
		$add_fixed_elements = function() use ( $mpdf, $coordinates, $user_name, $signature_url ) {
			// Add username if coordinates exist.
			if ( $user_name && isset( $coordinates['user_name'] ) ) {
				$this->add_element( $mpdf, $this->format_user_name( $user_name, $coordinates['user_name'] ), $coordinates['user_name'] );
			}

			// Add signature if available and coordinates exist.
			if ( $signature_url && isset( $coordinates['signature'] ) ) {
				$this->add_image( $mpdf, $signature_url, $coordinates['signature'] );
			}

			// Add page number if coordinates exist.
			if ( isset( $coordinates['page_number'] ) ) {
				$page_number_html = sprintf(
					'<div class="certificate-element" style="position: absolute; left: %dmm; top: %dmm; font-family: %s; font-size: %spt;">Page {PAGENO} of {nbpg}</div>',
					round( $coordinates['page_number']['x'] * 25.4 / 96 ),
					round( $coordinates['page_number']['y'] * 25.4 / 96 ),
					isset( $coordinates['page_number']['font_family'] ) ? $coordinates['page_number']['font_family'] : 'Arial',
					isset( $coordinates['page_number']['font_size'] ) ? $coordinates['page_number']['font_size'] : '12'
				);
				$mpdf->WriteHTML( $page_number_html );
			}
		};

		// Add fixed elements to the first page before adding course entries.
		$add_fixed_elements();

		foreach ( $course_entries as $entry ) {
			// Check if entry will fit on current page.
			if ( ( $current_y + $entry['height'] ) > ( $page_height - $margin_bottom ) ) {
				// Add new page.
				$mpdf->AddPage();

				// Add fixed elements to new page.
				$add_fixed_elements();

				// Reset Y position to starting position for new page.
				$current_y = $start_y;
			}

			// Add the course entry at current position.
			$html = sprintf(
				'<div style="position: absolute; left: %dmm; top: %dmm;">%s</div>',
				$start_x,
				$current_y,
				$entry['html']
			);
			$mpdf->WriteHTML( $html );

			// Update Y position for next entry.
			$current_y += $entry['height'];
		}
	}

	/**
	 * Format user name with styles.
	 *
	 * @brief Formats user name with custom styling.
	 *
	 * @details Applies custom formatting to user name:
	 * - Font family and size from settings
	 * - Text transformation options
	 * - Bold weight styling
	 *
	 * @param string $user_name User name.
	 * @param array  $position User name position and style settings.
	 * @return string Formatted user name HTML.
	 * @access private
	 * @since 1.0.0
	 */
	private function format_user_name( $user_name, $position ) {
		// Get font settings for username element.
		$font_size      = isset( $position['font_size'] ) ? $position['font_size'] : 24;
		$font_family    = isset( $position['font_family'] ) ? $position['font_family'] : 'Arial';
		$text_transform = isset( $position['text_transform'] ) ? $position['text_transform'] : 'none';

		$style = sprintf(
			'font-family: %s; font-size: %spt; text-transform: %s; font-weight: bold;',
			esc_attr( $font_family ),
			esc_attr( $font_size ),
			esc_attr( $text_transform )
		);

		return sprintf( '<div style="%s">%s</div>', $style, esc_html( $user_name ) );
	}
}
