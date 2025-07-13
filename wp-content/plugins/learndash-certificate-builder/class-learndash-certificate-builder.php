<?php
/**
 * Plugin Name: WDM LearnDash Certificate Builder
 * Plugin URI: https://wisdmlabs.com/learndash-certificate-builder
 * Description: Custom certificate builder for LearnDash courses
 * Version: 1.0.1
 * Author: Wisdmlabs
 * Author URI: https://wisdmlabs.com
 * Text Domain: learndash-certificate-builder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package LearnDash_Certificate_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include Composer autoloader.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Define plugin constants.
define( 'LCB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LCB_VERSION', '1.0.0' );

// Include required files.
require_once plugin_dir_path( __FILE__ ) . 'includes/data/class-dataretriever.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/position/class-positionmanager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/generation/class-certificategenerator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/download/class-certificatedownloader.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-settings.php';

/**
 * Main plugin class
 */
class LearnDash_Certificate_Builder {
	/**
	 * Data retriever instance
	 *
	 * @var \LearnDash_Certificate_Builder\Data\DataRetriever
	 */
	private $data_retriever;

	/**
	 * Position manager instance
	 *
	 * @var \LearnDash_Certificate_Builder\Position\PositionManager
	 */
	private $position_manager;

	/**
	 * Certificate generator instance
	 *
	 * @var \LearnDash_Certificate_Builder\Generation\CertificateGenerator
	 */
	private $certificate_generator;

	/**
	 * Certificate downloader instance
	 *
	 * @var \LearnDash_Certificate_Builder\Download\CertificateDownloader
	 */
	private $certificate_downloader;

	/**
	 * Admin settings instance
	 *
	 * @var \LearnDash_Certificate_Builder\Admin\Settings
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize components.
		$this->init_components();

		// Admin hooks.
		$this->settings = new \LearnDash_Certificate_Builder\Admin\Settings();
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_lcb_get_image_data', array( $this, 'handle_get_image_data' ) );

		// Frontend hooks.
		add_action( 'wp_ajax_lcb_generate_certificate', array( $this, 'handle_generate_certificate' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_shortcode( 'learndash_custom_certificate', array( $this, 'render_certificate_form' ) );
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components() {
		// Initialize data retriever.
		$this->data_retriever = new \LearnDash_Certificate_Builder\Data\DataRetriever();

		// Initialize position manager.
		$this->position_manager = new \LearnDash_Certificate_Builder\Position\PositionManager();

		// Initialize certificate generator.
		$this->certificate_generator = new \LearnDash_Certificate_Builder\Generation\CertificateGenerator(
			$this->position_manager,
			$this->data_retriever
		);

		// Initialize certificate downloader.
		$this->certificate_downloader = new \LearnDash_Certificate_Builder\Download\CertificateDownloader();
	}

	/**
	 * Display notice if LearnDash is not active
	 */
	public function learndash_not_active_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'LearnDash Certificate Builder requires LearnDash LMS plugin to be installed and activated.', 'learndash-certificate-builder' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Certificate Builder', 'learndash-certificate-builder' ),
			__( 'Certificate Builder', 'learndash-certificate-builder' ),
			'manage_options',
			'learndash-certificate-builder',
			array( $this->settings, 'render_settings_page' ),
			'dashicons-awards',
			30
		);
	}


	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin's page.
		if ( 'toplevel_page_learndash-certificate-builder' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script(
			'lcb-admin',
			LCB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable' ),
			LCB_VERSION,
			true
		);

		// Add admin script data.
		wp_localize_script(
			'lcb-admin',
			'lcbAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lcb_admin_nonce' ),
				'i18n'    => array(
					'save_success' => __( 'Positions saved successfully.', 'learndash-certificate-builder' ),
					'save_error'   => __( 'Error saving positions.', 'learndash-certificate-builder' ),
				),
			)
		);

		wp_enqueue_style(
			'lcb-admin',
			LCB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LCB_VERSION
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue if shortcode is present.
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'learndash_custom_certificate' ) ) {
			return;
		}

		wp_enqueue_style(
			'lcb-frontend',
			LCB_PLUGIN_URL . 'assets/css/frontend-certificate.css',
			array(),
			LCB_VERSION
		);

		wp_enqueue_script(
			'lcb-frontend',
			LCB_PLUGIN_URL . 'assets/js/frontend-certificate.js',
			array( 'jquery' ),
			LCB_VERSION,
			true
		);

		wp_localize_script(
			'lcb-frontend',
			'lcb_frontend',
			array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'lcb_generate_nonce' ),
				'select_course'        => __( 'Please select at least one course.', 'learndash-certificate-builder' ),
				'generating'           => __( 'Generating...', 'learndash-certificate-builder' ),
				'popup_blocked'        => __( 'Pop-up blocked. Please allow pop-ups and try again.', 'learndash-certificate-builder' ),
				'success'              => __( 'Certificate generated successfully!', 'learndash-certificate-builder' ),
				'generation_failed'    => __( 'Failed to generate certificate. Please try again.', 'learndash-certificate-builder' ),
				'connection_failed'    => __( 'Failed to connect to server. Please check your network connection and try again.', 'learndash-certificate-builder' ),
				'generate_certificate' => __( 'Generate Certificate', 'learndash-certificate-builder' ),
			)
		);
	}

	/**
	 * Handle AJAX request to generate certificate
	 */
	public function handle_generate_certificate() {
		// Check nonce.
		if ( ! check_ajax_referer( 'lcb_generate_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}

		// Get and validate parameters.
		$course_ids    = isset( $_POST['course_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['course_ids'] ) ) : array();
		$background_id = isset( $_POST['background_id'] ) ? absint( wp_unslash( $_POST['background_id'] ) ) : 0;
		$stream_mode   = filter_var(
			isset( $_POST['stream_mode'] ) ? wp_unslash( $_POST['stream_mode'] ) : '',
			FILTER_VALIDATE_BOOLEAN
		);

		if ( empty( $course_ids ) || ! $background_id ) {
			wp_send_json_error( 'Missing required parameters.' );
		}

		// Check if user has completed the courses.
		$user_id = get_current_user_id();
		foreach ( $course_ids as $course_id ) {
			if ( ! learndash_course_completed( $user_id, $course_id ) ) {
				wp_send_json_error( 'You have not completed all selected courses.' );
			}
		}

		// Generate certificate.
		$pdf_content = $this->certificate_generator->generate_certificate( $user_id, $course_ids, $background_id );
		if ( false === $pdf_content ) {
			wp_send_json_error( 'Failed to generate certificate.' );
		}

		// Generate unique filename.
		$filename = sprintf(
			'certificate-%s-%s.pdf',
			$user_id,
			current_time( 'Y-m-d-H-i-s' )
		);

		// Save certificate for record keeping.
		$saved_path = $this->certificate_downloader->save_certificate( $pdf_content, $filename );
		if ( false === $saved_path ) {
			wp_send_json_error( 'Failed to save certificate.' );
		}

		// Download or stream the certificate based on request.
		$success = $stream_mode ?
			$this->certificate_downloader->stream_certificate( $pdf_content, $filename ) :
			$this->certificate_downloader->download_certificate( $pdf_content, $filename );

		if ( ! $success ) {
			wp_send_json_error( 'Failed to deliver certificate.' );
		}

		exit;
	}

	/**
	 * Render certificate generation form
	 *
	 * @return string Form HTML.
	 */
	public function render_certificate_form() {
		// Get completed courses for current user.
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return esc_html__( 'Please log in to generate certificates.', 'learndash-certificate-builder' );
		}

		$completed_courses = $this->data_retriever->get_completed_courses( $user_id );
		if ( empty( $completed_courses ) ) {
			return esc_html__( 'You have not completed any courses yet.', 'learndash-certificate-builder' );
		}

		// Start output buffering.
		ob_start();

		// Include the template.
		include LCB_PLUGIN_DIR . 'templates/frontend/certificate-form.php';

		// Return the buffered content.
		return ob_get_clean();
	}

	/**
	 * Handle AJAX request to get image data
	 */
	public function handle_get_image_data() {
		// Check nonce.
		if ( ! check_ajax_referer( 'lcb_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce.' );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		// Get image ID.
		$image_id = isset( $_POST['image_id'] ) ? absint( wp_unslash( $_POST['image_id'] ) ) : 0;
		if ( ! $image_id ) {
			wp_send_json_error( 'Invalid image ID.' );
		}

		// Get image data.
		$image_data = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! $image_data ) {
			wp_send_json_error( 'Failed to get image data.' );
		}

		// Return image data.
		wp_send_json_success(
			array(
				'url'    => $image_data[0],
				'width'  => $image_data[1],
				'height' => $image_data[2],
			)
		);
	}
}

// Initialize the plugin.
new LearnDash_Certificate_Builder();
