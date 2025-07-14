<?php
/**
 * Template for certificate generation form
 *
 * @package LearnDash_Certificate_Builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$background_id = get_option( 'lcb_background_image' );
if ( ! $background_id ) {
	echo '<p>' . esc_html__( 'Certificate background not configured. Please contact the administrator.', 'learndash-certificate-builder' ) . '</p>';
	return;
}
?>

<div class="lcb-certificate-form">
	<h2><?php esc_html_e( 'Generate Certificate', 'learndash-certificate-builder' ); ?></h2>
	<form id="lcb-generate-form" method="post">
		<div class="lcb-course-list">
			<h3><?php esc_html_e( 'Select Completed Courses', 'learndash-certificate-builder' ); ?></h3>
			<?php foreach ( $completed_courses as $course ) : ?>
				<div class="lcb-course-item">
					<label>
						<input type="checkbox" name="course_ids[]" value="<?php echo esc_attr( $course['id'] ); ?>">
						<?php echo esc_html( $course['title'] ); ?>
						<span class="lcb-completion-date">
							<?php echo esc_html( $course['completion_date'] ); ?>
						</span>
					</label>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="lcb-delivery-options">
			<h3><?php esc_html_e( 'Delivery Options', 'learndash-certificate-builder' ); ?></h3>
			<div class="lcb-delivery-toggle">
				<label>
					<input type="radio" name="stream_mode" value="0" checked>
					<?php esc_html_e( 'Download Certificate', 'learndash-certificate-builder' ); ?>
				</label>
				<label>
					<input type="radio" name="stream_mode" value="1">
					<?php esc_html_e( 'View in Browser', 'learndash-certificate-builder' ); ?>
				</label>
			</div>
		</div>

		<input type="hidden" name="background_id" value="<?php echo esc_attr( $background_id ); ?>">
		<input type="hidden" name="action" value="lcb_generate_certificate">
		<?php wp_nonce_field( 'lcb_generate_nonce' ); ?>

		<div class="lcb-form-actions">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Generate Certificate', 'learndash-certificate-builder' ); ?>
			</button>
		</div>

		<div id="lcb-form-messages"></div>
	</form>
</div> 

