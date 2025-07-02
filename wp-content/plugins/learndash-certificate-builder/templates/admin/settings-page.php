<?php
/**
 * Admin settings page template for certificate builder.
 *
 * @package LearnDash Certificate Builder
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get default coordinates if not set.
if ( ! isset( $coordinates['default'] ) ) {
	$coordinates['default'] = array(
		'user_name'       => array(
			'x' => 100,
			'y' => 100,
		),
		'completion_date' => array(
			'x' => 100,
			'y' => 200,
		),
		'course_list'     => array(
			'x' => 100,
			'y' => 300,
		),
		'signature'       => array(
			'x' => 100,
			'y' => 400,
		),
	);
}

// Get coordinates for current background or use defaults.
$current_coordinates = isset( $coordinates[ $background_id ] ) ? $coordinates[ $background_id ] : $coordinates['default'];

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Certificate Builder Settings', 'learndash-certificate-builder' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'lcb_settings' );
		wp_nonce_field( 'lcb_admin_nonce', 'lcb_nonce' );
		?>
		
		<!-- Hidden input to preserve coordinates -->
		<input type="hidden" name="lcb_element_coordinates" value="<?php echo esc_attr( wp_json_encode( $coordinates ) ); ?>">
		
		<div class="lcb-settings-section">
			<h2><?php esc_html_e( 'Background Image', 'learndash-certificate-builder' ); ?></h2>
			<div class="lcb-image-upload">
				<input type="hidden" name="lcb_background_image" id="lcb_background_image" value="<?php echo esc_attr( $background_id ); ?>">
				<button type="button" class="button lcb-upload-image" data-target="lcb_background_image">
					<?php esc_html_e( 'Upload Image', 'learndash-certificate-builder' ); ?>
				</button>
				<button type="button" class="button lcb-remove-image <?php echo empty( $background_id ) ? 'lcb-hidden' : ''; ?>" data-target="lcb_background_image">
					<?php esc_html_e( 'Remove Image', 'learndash-certificate-builder' ); ?>
				</button>
				<div class="lcb-preview-image">
					<?php if ( $background_id ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_url( $background_id ) ); ?>" alt="">
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="lcb-settings-section">
			<h2><?php esc_html_e( 'Signature Image', 'learndash-certificate-builder' ); ?></h2>
			<div class="lcb-image-upload">
				<input type="hidden" name="lcb_signature_image" id="lcb_signature_image" value="<?php echo esc_attr( $signature_id ); ?>">
				<button type="button" class="button lcb-upload-image" data-target="lcb_signature_image">
					<?php esc_html_e( 'Upload Image', 'learndash-certificate-builder' ); ?>
				</button>
				<button type="button" class="button lcb-remove-image <?php echo empty( $signature_id ) ? 'lcb-hidden' : ''; ?>" data-target="lcb_signature_image">
					<?php esc_html_e( 'Remove Image', 'learndash-certificate-builder' ); ?>
				</button>
				<div class="lcb-preview-image">
					<?php if ( $signature_id ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_url( $signature_id ) ); ?>" alt="">
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="lcb-settings-section">
			<h2><?php esc_html_e( 'Element Positions', 'learndash-certificate-builder' ); ?></h2>
			<div id="lcb-position-editor" class="lcb-position-editor">
				<div class="lcb-canvas" style="background-image: url(<?php echo esc_url( wp_get_attachment_url( $background_id ) ); ?>)">
					<?php
					$elements = array(
						'user_name'       => __( 'User Name', 'learndash-certificate-builder' ),
						'completion_date' => __( 'Completion Date', 'learndash-certificate-builder' ),
						'course_list'     => __( 'Course List', 'learndash-certificate-builder' ),
						'signature'       => __( 'Signature', 'learndash-certificate-builder' ),
					);

					foreach ( $elements as $element_id => $element_label ) :
						$pos = isset( $current_coordinates[ $element_id ] ) ? $current_coordinates[ $element_id ] : array(
							'x' => 0,
							'y' => 0,
						);
						?>
						<div class="lcb-draggable-element" 
							 id="element-<?php echo esc_attr( $element_id ); ?>"
							 data-element="<?php echo esc_attr( $element_id ); ?>"
							 style="left: <?php echo esc_attr( $pos['x'] ); ?>px; top: <?php echo esc_attr( $pos['y'] ); ?>px;">
							<div class="lcb-element-label"><?php echo esc_html( $element_label ); ?></div>
							<div class="lcb-element-coordinates">
								X: <input type="number" class="lcb-x-coordinate" value="<?php echo esc_attr( $pos['x'] ); ?>">
								Y: <input type="number" class="lcb-y-coordinate" value="<?php echo esc_attr( $pos['y'] ); ?>">
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-primary lcb-save-positions">
					<?php esc_html_e( 'Save Positions', 'learndash-certificate-builder' ); ?>
				</button>
			</div>
		</div>

		<?php submit_button( __( 'Save Changes', 'learndash-certificate-builder' ) ); ?>
	</form>
</div>

<script>
// Initialize admin object with necessary data.
var lcb_admin = {
	ajaxurl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
	nonce: '<?php echo esc_js( wp_create_nonce( 'lcb_admin_nonce' ) ); ?>'
};
</script>

<style>
.lcb-position-editor {
	margin: 20px 0;
}

.lcb-canvas {
	position: relative;
	width: 800px;
	height: 600px;
	border: 1px solid #ccc;
	background-size: contain;
	background-repeat: no-repeat;
	background-position: center;
}

.lcb-draggable-element {
	position: absolute;
	padding: 10px;
	background: rgba(255, 255, 255, 0.9);
	border: 1px solid #999;
	cursor: move;
	min-width: 100px;
}

.lcb-element-coordinates {
	margin-top: 5px;
	font-size: 12px;
}

.lcb-element-coordinates input {
	width: 60px;
	margin: 0 5px;
}

.lcb-preview-image {
	margin: 10px 0;
	max-width: 300px;
}

.lcb-preview-image img {
	max-width: 100%;
	height: auto;
}

.lcb-settings-section {
	margin: 30px 0;
	padding: 20px;
	background: #fff;
	border: 1px solid #ccc;
}
</style> 
