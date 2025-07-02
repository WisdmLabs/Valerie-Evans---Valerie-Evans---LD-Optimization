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

<script>
/* Add ajaxurl for frontend */
var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

jQuery(document).ready(function($) {
	$('#lcb-generate-form').on('submit', function(e) {
		e.preventDefault();
		
		var $form = $(this);
		var $button = $form.find('button[type="submit"]');
		var $messages = $('#lcb-form-messages');
		var streamMode = $form.find('input[name="stream_mode"]:checked').val() === '1';
		
		// Check if at least one course is selected
		if (!$form.find('input[name="course_ids[]"]:checked').length) {
			$messages.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Please select at least one course.', 'learndash-certificate-builder' ) ); ?></p></div>');
			return;
		}
		
		// Disable button and show loading state
		$button.prop('disabled', true).text('<?php echo esc_js( __( 'Generating...', 'learndash-certificate-builder' ) ); ?>');
		
		// Submit form data
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: $form.serialize(),
			xhrFields: {
				responseType: streamMode ? 'blob' : 'blob'  // Always use blob for PDF
			},
			success: function(response, status, xhr) {
				var contentType = xhr.getResponseHeader('content-type');
				
				if (contentType && contentType.indexOf('application/pdf') !== -1) {
					// Handle PDF response
					var blob = new Blob([response], { type: 'application/pdf' });
					var url = window.URL.createObjectURL(blob);
					
					if (streamMode) {
						// Open PDF in new tab
						var newTab = window.open('', '_blank');
						if (newTab) {
							newTab.location.href = url;
						} else {
							$messages.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Pop-up blocked. Please allow pop-ups and try again.', 'learndash-certificate-builder' ) ); ?></p></div>');
						}
					} else {
						// Download PDF
						var a = document.createElement('a');
						var contentDisposition = xhr.getResponseHeader('content-disposition');
						var filename = 'certificate.pdf';
						
						if (contentDisposition) {
							var filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
							if (filenameMatch && filenameMatch[1]) {
								filename = filenameMatch[1].replace(/['"]/g, '');
							}
						}
						
						a.href = url;
						a.download = filename;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						window.URL.revokeObjectURL(url);
					}
					
					$messages.html('<div class="notice notice-success"><p><?php echo esc_js( __( 'Certificate generated successfully!', 'learndash-certificate-builder' ) ); ?></p></div>');
				} else {
					try {
						// Try to parse error response
						var jsonResponse = JSON.parse(new TextDecoder().decode(response));
						$messages.html('<div class="notice notice-error"><p>' + (jsonResponse.data || '<?php echo esc_js( __( 'Failed to generate certificate. Please try again.', 'learndash-certificate-builder' ) ); ?>') + '</p></div>');
					} catch (e) {
						$messages.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Failed to generate certificate. Please try again.', 'learndash-certificate-builder' ) ); ?></p></div>');
					}
				}
			},
			error: function(xhr, status, error) {
				$messages.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'Failed to connect to server. Please check your network connection and try again.', 'learndash-certificate-builder' ) ); ?></p></div>');
			},
			complete: function() {
				// Reset button state
				$button.prop('disabled', false).text('<?php echo esc_js( __( 'Generate Certificate', 'learndash-certificate-builder' ) ); ?>');
			}
		});
	});
});
</script>

<style>
.lcb-certificate-form {
	max-width: 800px;
	margin: 2em auto;
	padding: 2em;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.lcb-course-list {
	margin: 2em 0;
}

.lcb-course-item {
	padding: 1em;
	margin-bottom: 1em;
	background: #f9f9f9;
	border: 1px solid #eee;
	border-radius: 3px;
}

.lcb-course-item label {
	display: flex;
	align-items: center;
	gap: 1em;
}

.lcb-completion-date {
	margin-left: auto;
	color: #666;
	font-size: 0.9em;
}

.lcb-delivery-options {
	margin: 2em 0;
	padding: 1em;
	background: #f9f9f9;
	border: 1px solid #eee;
	border-radius: 3px;
}

.lcb-delivery-toggle {
	display: flex;
	gap: 2em;
	margin-top: 1em;
}

.lcb-delivery-toggle label {
	display: flex;
	align-items: center;
	gap: 0.5em;
	cursor: pointer;
}

.lcb-form-actions {
	margin-top: 2em;
	text-align: center;
}

#lcb-form-messages {
	margin-top: 2em;
}

#lcb-form-messages .notice {
	margin: 0;
}
</style> 
