<?php
/**
 * LearnDash Course Credit Settings.
 *
 * @package LearnDash\Settings\Metaboxes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the course credit settings.
 */
function wdm_init_course_credit_settings() {
	// Check if LearnDash is active.
	if ( ! defined( 'LEARNDASH_VERSION' ) ) {
		return;
	}

	if ( ! class_exists( 'LearnDash_Settings_Metabox' ) ) {
		require_once ABSPATH . 'wp-content/plugins/sfwd-lms/includes/settings/settings-metaboxes/class-ld-settings-metabox.php';
	}

	if ( class_exists( 'LearnDash_Settings_Metabox' ) && ! class_exists( 'WDM_Course_Credit_Settings' ) ) {
		/**
		 * Class to handle course credit settings in LearnDash.
		 *
		 * @since 1.0.0
		 */
		class WDM_Course_Credit_Settings extends LearnDash_Settings_Metabox {

			/**
			 * Public constructor for class.
			 */
			public function __construct() {
				$this->settings_screen_id     = 'sfwd-courses';
				$this->settings_metabox_key   = 'learndash-course-credit-settings';
				$this->settings_section_label = esc_html__( 'Credit Settings', 'learndash' );
				parent::__construct();
			}

			/**
			 * Initialize the metabox settings fields.
			 */
			public function load_settings_fields() {
				$this->setting_option_fields = array(
					'wdm_credit_type'   => array(
						'name'      => 'wdm_credit_type',
						'label'     => 'Credit Type',
						'type'      => 'select',
						'default'   => '',
						'options'   => array(
							''            => 'Select Credit Type',
							'Ethics'      => 'Ethics',
							'General'     => 'General',
							'Supervision' => 'Supervision',
						),
						'help_text' => 'Select the type of credit this course offers.',
						'value'     => '',
					),
					'wdm_credit_amount' => array(
						'name'      => 'wdm_credit_amount',
						'label'     => 'Credit Amount',
						'type'      => 'number',
						'class'     => 'small-text',
						'default'   => '',
						'help_text' => 'Enter the amount of credit (e.g., 1.5).',
						'value'     => '',
					),
				);

				parent::load_settings_fields();
			}

			/**
			 * Show Settings Section meta box.
			 *
			 * @param WP_Post $post Post.
			 * @param object  $metabox Metabox.
			 */
			public function show_meta_box( $post = null, $metabox = null ) {
				if ( $post ) {
					$this->init( $post );

					// Load current values into fields.
					foreach ( $this->setting_option_fields as $key => &$field ) {
						$field['value'] = get_post_meta( $post->ID, $key, true );
					}

					$this->show_metabox_nonce_field();

					// Custom field rendering.
					?>
					<div class="sfwd_options">
						<div class="sfwd_input">
							<span class="sfwd_option_label">
								<label for="wdm_credit_type">Credit Type</label>
							</span>
							<span class="sfwd_option_input">
								<select name="<?php echo esc_attr( $this->settings_metabox_key ); ?>[wdm_credit_type]" id="wdm_credit_type">
									<option value="">Select Credit Type</option>
									<option value="Ethics" <?php selected( get_post_meta( $post->ID, 'wdm_credit_type', true ), 'Ethics' ); ?>>Ethics</option>
									<option value="General" <?php selected( get_post_meta( $post->ID, 'wdm_credit_type', true ), 'General' ); ?>>General</option>
									<option value="Supervision" <?php selected( get_post_meta( $post->ID, 'wdm_credit_type', true ), 'Supervision' ); ?>>Supervision</option>
								</select>
								<p class="ld-metabox-description">Select the type of credit this course offers.</p>
							</span>
						</div>

						<div class="sfwd_input">
							<span class="sfwd_option_label">
								<label for="wdm_credit_amount">Credit Amount</label>
							</span>
							<span class="sfwd_option_input">
								<input type="number" 
									   name="<?php echo esc_attr( $this->settings_metabox_key ); ?>[wdm_credit_amount]" 
									   id="wdm_credit_amount" 
									   value="<?php echo esc_attr( get_post_meta( $post->ID, 'wdm_credit_amount', true ) ); ?>" 
									   class="small-text">
								<p class="ld-metabox-description">Enter the amount of credit (e.g., 1.5).</p>
							</span>
						</div>
					</div>
					<?php
				}
			}

			/**
			 * Override parent verify_metabox_nonce_field to add logging
			 */
			public function verify_metabox_nonce_field() {
				$nonce_key = $this->settings_metabox_key . '[nonce]';

				if ( ! isset( $_POST[ $this->settings_metabox_key ]['nonce'] ) ) {
					return false;
				}

				return wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST[ $this->settings_metabox_key ]['nonce'] ) ),
					$this->settings_metabox_key
				);
			}

			/**
			 * Get Settings Field Updates
			 *
			 * This function is called by the save_post_meta_box() function to get the fields to update.
			 *
			 * @since 1.0.0
			 *
			 * @param int     $post_id Post ID being saved.
			 * @param WP_Post $post WP_Post object being saved.
			 * @param bool    $update If update true, else false.
			 *
			 * @return array Array of settings fields to update.
			 */
			protected function get_settings_field_updates( $post_id = 0, $post = null, $update = false ) {
				$settings_field_updates = array();

				if ( ! $this->verify_metabox_nonce_field() ) {
					return $settings_field_updates;
				}

				// Load the fields configuration.
				$this->load_settings_fields();

				// Get and sanitize the settings fields.
				$post_values = array();
				if ( isset( $_POST[ $this->settings_metabox_key ] ) && is_array( $_POST[ $this->settings_metabox_key ] ) ) {
					$post_values = wp_unslash( $_POST[ $this->settings_metabox_key ] );
				}

				// Process each field.
				foreach ( $this->setting_option_fields as $field_key => $field_settings ) {
					if ( isset( $post_values[ $field_key ] ) ) {
						$settings_field_updates[ $field_key ] = sanitize_text_field( $post_values[ $field_key ] );
					}
				}

				return $settings_field_updates;
			}

			/**
			 * Save Settings Metabox
			 *
			 * @param int          $post_id                The post ID.
			 * @param WP_Post|null $saved_post             WP_Post object being saved.
			 * @param boolean      $update                 If update true, otherwise false.
			 * @param array        $settings_field_updates Array of settings fields to update.
			 */
			public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
				if ( ! $post_id || ! $saved_post ) {
					return;
				}

				$settings_field_updates = $this->get_settings_field_updates( $post_id, $saved_post, $update );

				if ( ( ! empty( $settings_field_updates ) ) && ( is_array( $settings_field_updates ) ) ) {
					foreach ( $settings_field_updates as $setting_option_key => $setting_option_value ) {
						update_post_meta( $post_id, $setting_option_key, $setting_option_value );
					}
				}
			}
		}

		// Initialize the metabox.
		add_filter(
			'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
			function( $metaboxes = array() ) {
				if ( ! isset( $metaboxes['WDM_Course_Credit_Settings'] ) ) {
					$metaboxes['WDM_Course_Credit_Settings'] = WDM_Course_Credit_Settings::add_metabox_instance();
				}
				return $metaboxes;
			},
			50,
			1
		);
	}
}
add_action( 'plugins_loaded', 'wdm_init_course_credit_settings', 20 );
