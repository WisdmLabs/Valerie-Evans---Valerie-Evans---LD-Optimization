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
$default_coordinates = array(
	'user_name'   => array(
		'x'              => 100,
		'y'              => 100,
		'font_size'      => 24,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'bacb_number' => array(
		'x'              => 100,
		'y'              => 150,
		'font_size'      => 14,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'qaba_number' => array(
		'x'              => 100,
		'y'              => 200,
		'font_size'      => 14,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'ibao_number' => array(
		'x'              => 100,
		'y'              => 250,
		'font_size'      => 14,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'total_hours' => array(
		'x'              => 100,
		'y'              => 300,
		'font_size'      => 14,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'course_list' => array(
		'x'              => 100,
		'y'              => 350,
		'font_size'      => 18,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
	'signature'   => array(
		'x' => 100,
		'y' => 400,
	),
	'page_number' => array(
		'x'              => 100,
		'y'              => 500,
		'font_size'      => 12,
		'font_family'    => 'Arial',
		'text_transform' => 'none',
	),
);

// Get coordinates for current background or use defaults.
$current_coordinates = isset( $coordinates[ $background_id ] ) ? $coordinates[ $background_id ] : $default_coordinates;

// Ensure font settings exist for all text elements.
$text_elements = array(
	'user_name'   => 24,
	'bacb_number' => 14,
	'qaba_number' => 14,
	'ibao_number' => 14,
	'total_hours' => 14,
	'course_list' => 18,
	'page_number' => 12,
);

foreach ( $text_elements as $element => $default_size ) {
	if ( ! isset( $current_coordinates[ $element ]['font_size'] ) ) {
		$current_coordinates[ $element ]['font_size'] = $default_size;
	}
	if ( ! isset( $current_coordinates[ $element ]['font_family'] ) ) {
		$current_coordinates[ $element ]['font_family'] = 'Arial';
	}
	if ( ! isset( $current_coordinates[ $element ]['text_transform'] ) ) {
		$current_coordinates[ $element ]['text_transform'] = 'none';
	}
}

// Only include the current background's coordinates in the form.
$form_coordinates = array();
if ( $background_id ) {
	$form_coordinates[ $background_id ] = $current_coordinates;
}

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Certificate Builder Settings', 'learndash-certificate-builder' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'lcb_settings' );
		wp_nonce_field( 'lcb_admin_nonce', 'lcb_nonce' );
		?>
		<!-- Hidden input to preserve coordinates. -->
		<input type="hidden" name="lcb_element_coordinates" value="<?php echo esc_attr( wp_json_encode( $form_coordinates ) ); ?>">
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
						'user_name'   => __( 'User Name', 'learndash-certificate-builder' ),
						'bacb_number' => __( 'BACB Certification Number', 'learndash-certificate-builder' ),
						'qaba_number' => __( 'QABA Certification Number', 'learndash-certificate-builder' ),
						'ibao_number' => __( 'IBAO Certification Number', 'learndash-certificate-builder' ),
						'total_hours' => __( 'Total Hours', 'learndash-certificate-builder' ),
						'course_list' => __( 'Course List', 'learndash-certificate-builder' ),
						'signature'   => __( 'Signature', 'learndash-certificate-builder' ),
						'page_number' => __( 'Page Number', 'learndash-certificate-builder' ),
					);

					foreach ( $elements as $element_id => $element_label ) :
						$pos = isset( $current_coordinates[ $element_id ] ) ? $current_coordinates[ $element_id ] : array(
							'x' => 0,
							'y' => 0,
						);
						?>
						<div class="lcb-draggable-element" id="element-<?php echo esc_attr( $element_id ); ?>" data-element="<?php echo esc_attr( $element_id ); ?>" style="left: <?php echo esc_attr( $pos['x'] ); ?>px; top: <?php echo esc_attr( $pos['y'] ); ?>px;">
							<div class="lcb-element-label"><?php echo esc_html( $element_label ); ?></div>
							<div class="lcb-element-coordinates">
								X: <input type="number" class="lcb-x-coordinate" value="<?php echo esc_attr( $pos['x'] ); ?>">
								Y: <input type="number" class="lcb-y-coordinate" value="<?php echo esc_attr( $pos['y'] ); ?>">
							</div>
							<?php if ( 'signature' !== $element_id ) : ?>
								<div class="lcb-element-styles">
									<div class="lcb-style-control">
										<label>
											<?php esc_html_e( 'Font Size:', 'learndash-certificate-builder' ); ?>
											<input type="number" class="lcb-style-input lcb-font-size" value="<?php echo esc_attr( isset( $pos['font_size'] ) ? $pos['font_size'] : $text_elements[ $element_id ] ); ?>">
										</label>
									</div>
									<div class="lcb-style-control">
										<label>
											<?php esc_html_e( 'Font:', 'learndash-certificate-builder' ); ?>
											<select class="lcb-style-input lcb-font-family">
												<?php
												$fonts         = array( 'Arial', 'Times New Roman', 'Helvetica', 'Georgia' );
												$selected_font = isset( $pos['font_family'] ) ? $pos['font_family'] : 'Arial';
												foreach ( $fonts as $font ) :
													?>
													<option value="<?php echo esc_attr( $font ); ?>"
														<?php selected( $selected_font, $font ); ?>>
														<?php echo esc_html( $font ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</label>
									</div>
									<div class="lcb-style-control">
										<label>
											<?php esc_html_e( 'Text Transform:', 'learndash-certificate-builder' ); ?>
											<select class="lcb-style-input lcb-text-transform">
												<?php
												$transforms         = array(
													'none' => __( 'None', 'learndash-certificate-builder' ),
													'uppercase' => __( 'UPPERCASE', 'learndash-certificate-builder' ),
													'lowercase' => __( 'lowercase', 'learndash-certificate-builder' ),
													'capitalize' => __( 'Capitalize', 'learndash-certificate-builder' ),
												);
												$selected_transform = isset( $pos['text_transform'] ) ? $pos['text_transform'] : 'none';
												foreach ( $transforms as $value => $label ) :
													?>
													<option value="<?php echo esc_attr( $value ); ?>"
														<?php selected( $selected_transform, $value ); ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</label>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
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
	z-index: 1;
	transition: z-index 0s, box-shadow 0.2s;
}

.lcb-draggable-element:hover {
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.lcb-element-label {
	font-weight: bold;
	margin-bottom: 5px;
}

.lcb-element-coordinates {
	margin-top: 5px;
	font-size: 12px;
}

.lcb-element-coordinates input {
	width: 60px;
	margin: 0 5px;
}

.lcb-element-styles {
	margin-top: 10px;
	padding: 8px;
	background: #fff;
	border: 1px solid #e5e5e5;
	border-radius: 3px;
}

.lcb-style-control {
	margin-bottom: 8px;
}

.lcb-style-control:last-child {
	margin-bottom: 0;
}

.lcb-style-control label {
	display: flex;
	align-items: center;
	gap: 8px;
}

.lcb-style-input {
	flex: 1;
	min-width: 0;
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
