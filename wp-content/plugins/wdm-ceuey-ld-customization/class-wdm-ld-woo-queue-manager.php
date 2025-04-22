<?php
/**
 * Plugin Name: Wisdmlabs LearnDash WooCommerce Queue Manager
 * Plugin URI:
 * Description: Manages LearnDash WooCommerce course enrollment queue processing limits
 * Version: 1.0.1
 * Author: Wisdmlabs
 * Author URI: https://wisdmlabs.com
 * Text Domain: wdm-ld-woo-queue-manager
 * Domain Path: /languages
 *
 * @package WDM_LD_Woo_Queue_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WDM_LD_WOO_QUEUE_MANAGER_VERSION', '1.0.0' );
define( 'WDM_LD_WOO_QUEUE_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WDM_LD_WOO_QUEUE_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


/**
 * LearnDash WooCommerce Queue Manager
 *
 * Manages the processing of LearnDash course enrollments when purchased through WooCommerce.
 * This class addresses performance issues that occur when processing large numbers of course
 * enrollments simultaneously by implementing controlled queue processing and asynchronous
 * enrollment handling.
 *
 * Features:
 * - Configurable limits for simultaneous course/group enrollments
 * - Asynchronous processing of large orders via REST API
 * - User notifications for large orders with potential delays
 * - Administrative settings for queue and product processing limits
 * - Support for both regular orders and subscription-based enrollments
 *
 * @since 1.0.0
 * @package WDM_LD_Woo_Queue_Manager
 */
class WDM_LD_Woo_Queue_Manager {

	/**
	 * Singleton instance of the WDM_LD_Woo_Queue_Manager class.
	 *
	 * @var $instance Instance of WDM_LD_Woo_Queue_Manager class
	 */
	private static $instance = null;
	/**
	 * Option name for queue processing limit
	 *
	 * @var String $option_name
	 */
	private $option_name = 'ld_woo_queue_processing_limit';
	/**
	 * Option name for product queue processing limit
	 *
	 * @var String $product_option_name
	 */
	private $product_option_name = 'ld_woo_queue_processing_product_limit';

	/**
	 * Gets the instance of the WDM_LD_Woo_Queue_Manager class.
	 *
	 * @since 1.0.0
	 * @return WDM_LD_Woo_Queue_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WDM_LD_Woo_Queue_Manager constructor.
	 *
	 * Initializes the plugin by adding actions and filters for admin menu,
	 * settings registration, modifying queue limits, and displaying cart
	 * course limit notices. Also registers an activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'learndash_woocommerce_process_silent_course_enrollment_queue_count', array( $this, 'modify_queue_limit' ) );
		add_filter( 'learndash_woocommerce_products_count_for_silent_course_enrollment', array( $this, 'modify_product_queue_limit' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'display_cart_course_limit_notice' ) );
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
	}

	/**
	 * Adds a submenu page to the LearnDash menu for queue processing settings
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'learndash-lms',
			__( 'Queue Processing Settings', 'wdm-ld-woo-queue-manager' ),
			__( 'Queue Processing', 'wdm-ld-woo-queue-manager' ),
			'manage_options',
			'wdm-ld-woo-queue-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers settings for the queue processing limit and product count.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'ld_woo_queue_settings',
			$this->option_name,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_queue_limit' ),
				'default'           => 10,
			)
		);
		register_setting(
			'ld_woo_queue_settings',
			$this->product_option_name,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_queue_limit' ),
				'default'           => 10,
			)
		);
	}

	/**
	 * Sanitizes the queue processing limit to ensure it is a positive integer
	 * value.
	 *
	 * @param int $value The value to sanitize.
	 * @return int The sanitized value, which is the absolute value of the
	 * provided value, or 1 if the provided value is less than 1.
	 */
	public function sanitize_queue_limit( $value ) {
		$value = absint( $value );
		return max( 1, $value );
	}

	/**
	 * Renders the settings page for the LearnDash WooCommerce Queue Manager plugin.
	 *
	 * This function checks if the current user has the capability to manage
	 * options and, if so, displays the settings page. The page includes a form
	 * to configure the queue processing limit and queue processing product limit
	 * with input fields for each setting. The settings are registered under the
	 * 'ld_woo_queue_settings' group, and the form can be submitted to update the
	 * options.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'ld_woo_queue_settings' );
				do_settings_sections( 'ld_woo_queue_settings' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $this->option_name ); ?>">
								<?php esc_html_e( 'Queue Processing Limit', 'wdm-ld-woo-queue-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								id="<?php echo esc_attr( $this->option_name ); ?>"
								name="<?php echo esc_attr( $this->option_name ); ?>"
								value="<?php echo esc_attr( get_option( $this->option_name, 10 ) ); ?>"
								min="1"
								class="regular-text"
							/>
							<p class="description">
									<?php esc_html_e( 'Set the maximum number of queue items to process at once.', 'wdm-ld-woo-queue-manager' ); ?>
							</p>
						</td>
					</tr>
					<tr>
					<th scope="row">
							<label for="<?php echo esc_attr( $this->option_name ); ?>">
								<?php esc_html_e( 'Queue Processing Product Limit', 'wdm-ld-woo-queue-manager' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								id="<?php echo esc_attr( $this->product_option_name ); ?>"
								name="<?php echo esc_attr( $this->product_option_name ); ?>"
								value="<?php echo esc_attr( get_option( $this->product_option_name, 10 ) ); ?>"
								min="1"
								class="regular-text"
							/>
							<p class="description">
									<?php esc_html_e( 'Set the minimum number of courses in the order after which the order will be added to the processing queue', 'wdm-ld-woo-queue-manager' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Modifies the default queue processing limit by returning the custom limit.
	 *
	 * @param int $default_limit The default limit.
	 * @return int The custom limit.
	 */
	public function modify_queue_limit( $default_limit ) {
		$custom_limit = get_option( $this->option_name, 10 );
		return absint( $custom_limit );
	}

	/**
	 * Modifies the default queue processing product limit by returning the custom limit.
	 *
	 * @param int $default_limit The default limit.
	 * @return int The custom limit.
	 */
	public function modify_product_queue_limit( $default_limit ) {
		$custom_limit = get_option( $this->product_option_name, 10 );
		return absint( $custom_limit );
	}

	/**
	 * Displays a notice on the WooCommerce checkout page if the total number of
	 * courses and groups in the cart exceeds the configured limit.
	 *
	 * This function checks if the current page is the checkout page and retrieves
	 * the cart contents. It calculates the total number of related courses and
	 * groups for all products in the cart. If the sum exceeds the predefined
	 * limit, a notice is added to inform users that there may be a delay in
	 * courses appearing in their account.
	 *
	 * The limit is retrieved from the plugin's settings and the notice is
	 * displayed using WooCommerce's notice system.
	 *
	 * @since 1.0.0
	 */
	public function display_cart_course_limit_notice() {
		if ( ! is_checkout() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return;
		}

		$courses_count = 0;
		$groups_count  = 0;

		foreach ( $cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			if ( ! empty( $product->get_variation_id() ) ) {
				$courses = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
				$groups  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
			} else {
				$courses = (array) get_post_meta( $product_id, '_related_course', true );
				$groups  = (array) get_post_meta( $product_id, '_related_group', true );
			}

			$courses = array_filter(
				$courses,
				function( $course_id ) {
					return ! empty( $course_id ) && is_numeric( $course_id );
				}
			);

			$groups = array_filter(
				$groups,
				function( $group_id ) {
					return ! empty( $group_id ) && is_numeric( $group_id );
				}
			);

			$courses_count += count( $courses );
			$groups_count  += count( $groups );
		}

		$limit = get_option( $this->option_name, 10 );

		if ( $courses_count + $groups_count > $limit ) {
			wc_add_notice(
				sprintf(
					__( 'Notice: The total number of courses and groups across all your bundles (%1$d courses and groups) exceeds the recommended limit of %2$d courses/groups. You might experience a short delay before all courses appear in your account.', 'wdm-ld-woo-queue-manager' ),
					$courses_count + $groups_count,
					$limit
				),
				'notice'
			);
		}
	}

	/**
	 * Activates the plugin by processing the silent course enrollment queue.
	 *
	 * This method is triggered on plugin activation and processes any pending
	 * course enrollments in the queue. It ensures that users gain access to
	 * the courses or subscriptions they have purchased.
	 *
	 * @since 1.0.0
	 */
	public function activate_plugin() {
		$this->process_silent_course_enrollment();
	}

	/**
	 * Processes the silent course enrollment queue.
	 *
	 * This function retrieves the queue of pending course enrollments from
	 * the options table and iterates over each entry. For each entry, it
	 * either adds course access based on order ID or subscription access
	 * based on subscription ID. Once processed, the entry is removed from
	 * the queue, and the updated queue is saved back to the options table.
	 *
	 * This ensures that users are granted access to courses or subscriptions
	 * they have purchased, processing each queued enrollment silently.
	 *
	 * @since 1.0.0
	 */
	public function process_silent_course_enrollment() {
		$queue = get_option( 'learndash_woocommerce_silent_course_enrollment_queue', array() );

		foreach ( $queue as $id => $args ) {
			if ( ! empty( $args['order_id'] ) ) {
				$this->add_course_access( $args['order_id'] );
			} elseif ( ! empty( $args['subscription_id'] ) ) {
				$this->add_subscription_course_access( wcs_get_subscription( $args['subscription_id'] ) );
			}

			unset( $queue[ $id ] );
		}

		update_option( 'learndash_woocommerce_silent_course_enrollment_queue', $queue, false );
	}

	/**
	 * Adds course and group access for a given order.
	 *
	 * This function processes the WooCommerce order to retrieve associated courses
	 * and groups, then grants access to the customer for each course and group.
	 * It checks if the order and customer are valid and uses reflection to invoke
	 * methods from the Learndash_WooCommerce class to update access.
	 *
	 * @param int      $order_id The ID of the WooCommerce order.
	 * @param int|null $customer_id The ID of the customer. If not provided, the
	 *                              order's user ID is used.
	 */
	public function add_course_access( $order_id, $customer_id = null ) {
		$order = wc_get_order( $order_id );

		if ( false !== $order && is_a( $order, 'WC_Order' ) ) {

			global $learndash_woocommerce_get_items_filter_out_subscriptions;
			$learndash_woocommerce_get_items_filter_out_subscriptions = true;
			$products = $order->get_items();

			$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $order->get_user_id();

			$courses_count = 0;
			$groups_count  = 0;

			foreach ( $products as $product ) {
				if ( ! empty( $product->get_variation_id() ) ) {
					$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
					$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
				} else {
					$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
					$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
				}

				if ( $courses_id && is_array( $courses_id ) ) {
					foreach ( $courses_id as $course_id ) {
						if ( class_exists( 'Learndash_WooCommerce' ) ) {
							try {
								$ref = new ReflectionClass( 'Learndash_WooCommerce' );
								if ( $ref->hasMethod( 'update_add_course_access' ) ) {
									$method = $ref->getMethod( 'update_add_course_access' );
									$method->setAccessible( true );
									$method->invoke( null, $course_id, $customer_id, $order_id );
								}
							} catch ( ReflectionException $e ) {
								error_log( 'Reflection error: ' . $e->getMessage() );
							}
						}
					}
				}

				if ( $groups_id && is_array( $groups_id ) ) {
					foreach ( $groups_id as $group_id ) {
						if ( class_exists( 'Learndash_WooCommerce' ) ) {
							try {
								$ref = new ReflectionClass( 'Learndash_WooCommerce' );
								if ( $ref->hasMethod( 'update_add_group_access' ) ) {
									$method = $ref->getMethod( 'update_add_group_access' );
									$method->setAccessible( true );
									$method->invoke( null, $group_id, $customer_id, $order_id );
								}
							} catch ( ReflectionException $e ) {
								error_log( 'Reflection error: ' . $e->getMessage() );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Adds course and group access for a subscription.
	 *
	 * This function grants access to related courses and groups for a given
	 * WooCommerce subscription. It checks the validity of the subscription
	 * and applies necessary filters before processing the products. Course
	 * and group IDs are retrieved from product metadata, and access is added
	 * using the LearnDash WooCommerce integration. The course and group access
	 * start dates are updated based on the subscription's creation date.
	 *
	 * @param WC_Subscription $subscription The subscription object.
	 * @param array           $products     Optional. Array of subscription products.
	 *                                      Defaults to empty array.
	 * @param int|null        $customer_id  Optional. The customer ID. Defaults to null.
	 */
	public function add_subscription_course_access( $subscription, $products = array(), $customer_id = null ) {
		if ( false === $subscription || ! is_a( $subscription, 'WC_Subscription' ) ) {
			return;
		}

		if ( ! apply_filters( 'ld_woocommerce_add_subscription_course_access', true, $subscription, current_filter() ) ) {
			return;
		}

		if ( empty( $products ) ) {
			$products = $subscription->get_items();
		}

		$customer_id = ! empty( $customer_id ) && is_numeric( $customer_id ) ? $customer_id : $subscription->get_user_id();

		$start_date = $subscription->get_date( 'date_created' );

		foreach ( $products as $product ) {
			if ( ! empty( $product->get_variation_id() ) ) {
				$courses_id = (array) get_post_meta( $product->get_variation_id(), '_related_course', true );
				$groups_id  = (array) get_post_meta( $product->get_variation_id(), '_related_group', true );
			} else {
				$courses_id = (array) get_post_meta( $product['product_id'], '_related_course', true );
				$groups_id  = (array) get_post_meta( $product['product_id'], '_related_group', true );
			}

			if ( $courses_id && is_array( $courses_id ) ) {
				foreach ( $courses_id as $course_id ) {
					if ( class_exists( 'Learndash_WooCommerce' ) ) {
						try {
							$ref = new ReflectionClass( 'Learndash_WooCommerce' );
							if ( $ref->hasMethod( 'update_add_course_access' ) ) {
								$method = $ref->getMethod( 'update_add_course_access' );
								$method->setAccessible( true );
								$method->invoke( null, $course_id, $customer_id, $subscription->get_id() );
							}
						} catch ( ReflectionException $e ) {
							error_log( 'Reflection error: ' . $e->getMessage() );
						}
					}
					if ( apply_filters( 'learndash_woocommerce_reset_subscription_course_access_from', true, $course_id, $subscription ) ) {
						update_user_meta( $customer_id, 'course_' . $course_id . '_access_from', strtotime( $start_date ) );
					}
				}
			}

			if ( $groups_id && is_array( $groups_id ) ) {
				foreach ( $groups_id as $group_id ) {
					if ( class_exists( 'Learndash_WooCommerce' ) ) {
						try {
							$ref = new ReflectionClass( 'Learndash_WooCommerce' );
							if ( $ref->hasMethod( 'update_add_group_access' ) ) {
								$method = $ref->getMethod( 'update_add_group_access' );
								$method->setAccessible( true );
								$method->invoke( null, $course_id, $customer_id, $subscription->get_id() );
							}
						} catch ( ReflectionException $e ) {
							error_log( 'Reflection error: ' . $e->getMessage() );
						}
					}
					if ( apply_filters( 'learndash_woocommerce_reset_subscription_group_access_from', true, $group_id, $subscription ) ) {
						update_user_meta( $customer_id, 'learndash_group_enrolled_' . $group_id, strtotime( $start_date ) );
					}
				}
			}
		}

	}

}

WDM_LD_Woo_Queue_Manager::get_instance();
