<?php
/**
 * Enrollments class
 *
 * @author: themeum
 * @link https://themeum.com
 * @package TutorPro\Addons
 * @subpackage Enrollments
 * @since 1.4.0
 */

namespace TUTOR_ENROLLMENTS;

use TUTOR\Course;
use TUTOR\Earnings;
use Tutor\Ecommerce\OrderController;
use Tutor\Ecommerce\Ecommerce;
use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\QueryHelper;
use Tutor\Helpers\ValidationHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Models\UserModel;
use Tutor\Models\OrderActivitiesModel;
use Tutor\Models\OrderModel;
use Tutor\Traits\JsonResponse;
use TUTOR\User;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Models\BundleModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enrollments Class
 *
 * @since 2.0.6
 */
class Enrollments {

	use JsonResponse;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'tutor_admin_register', array( $this, 'register_menu' ) );

		add_action( 'wp_ajax_tutor_json_search_students', array( $this, 'tutor_json_search_students' ) );
		add_action( 'tutor_action_enrol_student', array( $this, 'enrol_student' ) );
		add_action( 'wp_ajax_tutor_enroll_bulk_student', array( $this, 'tutor_enroll_bulk_student' ) );
		add_action( 'tutor_after_enrollment', __CLASS__ . '::create_tutor_order', 10, 4 );

		add_action( 'wp_ajax_tutor_unenrolled_users', array( $this, 'ajax_get_unenrolled_users' ) );
		add_action( 'wp_ajax_tutor_course_bundle_list', array( $this, 'ajax_course_bundle_list' ) );
	}

	/**
	 * Register Enrollment Menu
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page( 'tutor', __( 'Enrollment', 'tutor-pro' ), __( 'Enrollment', 'tutor-pro' ), 'manage_tutor', 'enrollments', array( $this, 'enrollments' ) );
	}

	/**
	 * Manual Enrollment Page
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function enrollments() {
		$current_page = Input::get( 'page' );
		$action       = Input::get( 'action' );

		if ( 'enrollments' === $current_page && 'add_new' === $action ) {
			?>
				<div class="tutor-admin-wrap tutor-new-enrollment-wrapper">
					<div id="tutor-new-enrollment-root"></div>
				</div>
			<?php
			return;
		}

		include TUTOR_ENROLLMENTS()->path . 'views/enrollments.php';
	}

	/**
	 * Enroll multiple student by course ID
	 *
	 * @since 2.0.6
	 *
	 * @since 3.0.0
	 *
	 * Multiple course enrollment support added
	 *
	 * @return void
	 */
	public function tutor_enroll_bulk_student() {

		$required_fields = array(
			'student_ids',
			'object_ids',
			'payment_status',
			'order_type',
		);

		tutor_utils()->checking_nonce();

		if ( ! User::is_admin() ) {
			wp_send_json_error( tutor_utils()->error_message() );
		}

		$request = Input::sanitize_array( $_POST );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $request[ $field ] ) ) {
				$request[ $field ] = '';
			}
		}

		$validation = $this->validate( $request );
		if ( ! $validation->success ) {
			$this->json_response(
				tutor_utils()->error_message( HttpHelper::STATUS_BAD_REQUEST ),
				$validation->errors,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$request = (object) $request;

		$student_ids    = $request->student_ids;
		$payment_status = $request->payment_status;
		$order_type     = $request->order_type;
		$object_ids     = $request->object_ids;

		if ( empty( $student_ids ) ) {
			$this->json_response(
				__( 'Please select at least one student', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		if ( empty( $object_ids ) ) {
			$this->json_response(
				__( 'Please select a course or subscription plan', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		/**
		 * This can be course/bundle_id
		 *
		 * @var int $selected_id
		 *
		 * @since 3.0.0 field name changed to object_ids
		 * it could be course/bundle or both ids, value example: '1,2'
		 */
		if ( is_array( $object_ids ) && count( $object_ids ) ) {
			foreach ( $object_ids as $object_id ) {
				$post = get_post( $object_id );

				// Check all selected student are not enrolled before.
				$is_already_enrolled = false;
				foreach ( $student_ids as $student_id ) {
					$is_already_enrolled = tutor_utils()->is_enrolled( $object_id, $student_id, false );

					if ( $is_already_enrolled ) {
						// Skip already enrolled student.
						continue;
					}

					$is_paid_course   = tutor_utils()->is_course_purchasable( $object_id );
					$monetize_by      = tutor_utils()->get_option( 'monetize_by' );
					$generate_invoice = tutor_utils()->get_option( 'tutor_woocommerce_invoice' );

					// Now enroll each student for selected course/bundle.
					$order_id = 0;

					/**
					 * Check generate invoice settings along with monetize by
					 *
					 * @since 2.1.4
					 */
					if ( $is_paid_course && 'wc' === $monetize_by && $generate_invoice ) {
						// Make an manual order for student with this course.
						$product_id = tutor_utils()->get_course_product_id( $object_id );
						$order      = wc_create_order();

						$order->add_product( wc_get_product( $product_id ), 1 );
						$order->set_customer_id( $student_id );
						$order->calculate_totals();
						$order->update_status( 'Pending payment', __( 'Manual Enrollment Order', 'tutor-pro' ), true );

						$order_id = $order->get_id();

						/**
						 * Set transient for showing modal in view enrollment-success-modal.php
						 */
						$post->order_url = get_admin_url() . 'edit.php?post_type=shop_order';
						set_transient( 'tutor_manual_enrollment_success', $post );
					}

					/**
					 * If user disable generate invoice from tutor settings these will be happen.
					 * 1. Paid course enrollment will automatically completed without generate a WC order.
					 * 2. Earning data will not reflect on report.
					 *
					 * @since 2.1.4
					 */
					if ( ! $generate_invoice && $is_paid_course && 'wc' === $monetize_by ) {
						add_filter(
							'tutor_enroll_data',
							function( $data ) {
								$data['post_status'] = 'completed';
								return $data;
							}
						);
					}

					if ( $is_paid_course && tutor_utils()->is_monetize_by_tutor() && OrderModel::PAYMENT_PAID === $payment_status ) {
						add_filter(
							'tutor_enroll_data',
							function( $data ) {
								$data['post_status'] = 'completed';
								return $data;
							}
						);
					}

					// Enroll to course/bundle.
					tutor_utils()->do_enroll( $object_id, $order_id, $student_id );

					/**
					 * Enrol to bundle courses when WC order create disabled from tutor settings.
					 *
					 * @since 2.2.2
					 */
					if ( CourseBundle::POST_TYPE === $post->post_type && ! $generate_invoice && $is_paid_course && 'wc' === $monetize_by ) {
						BundleModel::enroll_to_bundle_courses( $object_id, $student_id );
					}

					if ( CourseBundle::POST_TYPE === $post->post_type && tutor_utils()->is_monetize_by_tutor() && OrderModel::PAYMENT_PAID === $payment_status ) {
						BundleModel::enroll_to_bundle_courses( $object_id, $student_id );
					}

					do_action( 'tutor_after_enrollment', $order_type, $object_id, $student_id, $payment_status );

					if ( OrderModel::PAYMENT_UNPAID === $payment_status && tutor_utils()->is_monetize_by_tutor() ) {
						$post->order_url = get_admin_url() . 'admin.php?page=tutor_orders';
						set_transient( 'tutor_manual_enrollment_success', $post );
					}
				}
			}
		}

		$this->json_response( __( 'Enrollment done for selected students', 'tutor-pro' ) );

	}

	/**
	 * Create tutor order
	 *
	 * @since 3.0.0
	 *
	 * @param string $order_type Order type single_order/subscription.
	 * @param int    $object_id Course or bundle id.
	 * @param int    $student_id Enroll student id.
	 * @param string $payment_status Order payment status.
	 *
	 * @return void
	 */
	public static function create_tutor_order( string $order_type, int $object_id, int $student_id, string $payment_status ) {
		// Check if monetize by tutor.
		if ( ! tutor_utils()->is_monetize_by_tutor() || ! tutor_utils()->is_course_purchasable( $object_id ) ) {
			return;
		}

		$order_controller = new OrderController();
		$order_model      = new OrderModel();
		$earnings         = Earnings::get_instance();

		$price = $order_model::TYPE_SINGLE_ORDER ? tutor_utils()->get_raw_course_price( $object_id ) : 0;

		if ( ! $price ) {
			return;
		}

		$item = array(
			'item_id'        => $object_id,
			'regular_price'  => tutor_get_locale_price( $price->regular_price ),
			'sale_price'     => $price->sale_price ? tutor_get_locale_price( $price->sale_price ) : null,
			'discount_price' => null,
		);

		try {
			$order_id = $order_controller->create_order( $student_id, $item, $payment_status, $order_type );

			// Store order activity.
			$data = (object) array(
				'order_id'   => $order_id,
				'meta_key'   => OrderActivitiesModel::META_KEY_HISTORY,
				'meta_value' => 'Order created for manual enrollment.',
			);

			( new OrderActivitiesModel() )->add_order_meta( $data );

			$earnings->prepare_order_earnings( $order_id );
			$earnings->remove_before_store_earnings();

			do_action( 'tutor_after_manual_enrollment_order', $data );
		} catch ( \Throwable $th ) {
			error_log( $th->getMessage() );
		}
	}

	/**
	 * Get unenrolled user list
	 *
	 * @since 3.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_get_unenrolled_users() {
		tutor_utils()->check_nonce();
		tutor_utils()->check_current_user_capability();

		$limit     = Input::post( 'limit', 10, Input::TYPE_INT );
		$offset    = Input::post( 'offset', 0, Input::TYPE_INT );
		$object_id = Input::post( 'object_id', 0, Input::TYPE_INT );

		$search_clause = array();
		$filter        = isset( $_POST['filter'] ) ? json_decode( wp_unslash( $_POST['filter'] ) ) : '';
		if ( ! empty( $filter ) && is_object( $filter ) && property_exists( $filter, 'search' ) ) {
			$search_term   = Input::sanitize( $filter->search );
			$search_clause = array(
				'u.ID'           => $search_term,
				'u.user_login'   => $search_term,
				'u.user_email'   => $search_term,
				'u.display_name' => $search_term,
			);
		}

		$response    = ( new UserModel() )->get_unenrolled_users( $object_id, $search_clause, $limit, $offset );
		$total_items = $response['total_count'];
		unset( $response['total_count'] );
		$response['total_items'] = $total_items;

		$this->json_response(
			__( 'User retrieved successfully!', 'tutor-pro' ),
			$response
		);
	}

	/**
	 * Get all course/bundle list
	 *
	 * Return paginated list of records
	 *
	 * @since 3.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_course_bundle_list() {
		tutor_utils()->check_nonce();
		tutor_utils()->check_current_user_capability();

		$response = array(
			'results'     => array(),
			'total_items' => 0,
		);

		$args = array(
			'post_type'      => tutor()->course_post_type,
			'post_status'    => array( CourseModel::STATUS_PUBLISH, CourseModel::STATUS_PRIVATE ),
			'posts_per_page' => Input::post( 'limit', 10, Input::TYPE_INT ),
			'offset'         => Input::post( 'offset', 0, Input::TYPE_INT ),
		);

		if ( tutor_utils()->is_addon_enabled( 'tutor-pro/addons/course-bundle/course-bundle.php' ) ) {
			$args['post_type'] = array( tutor()->course_post_type, 'course-bundle' );
		}

		$filter = isset( $_POST['filter'] ) ? json_decode( wp_unslash( $_POST['filter'] ) ) : '';
		if ( ! empty( $filter ) && is_object( $filter ) && property_exists( $filter, 'search' ) ) {
			$args['s'] = Input::sanitize( $filter->search );
		}

		try {
			if ( tutor_utils()->is_addon_enabled( 'tutor-pro/addons/subscription/subscription.php' ) ) {
				/**
				* Filter to exclude subscription course from course list
				*
				* @since 3.0.0
				*
				* @TODO manual subscription will implement later.
				*/
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => Course::COURSE_SELLING_OPTION_META,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => Course::COURSE_SELLING_OPTION_META,
						'value'   => Course::SELLING_OPTION_SUBSCRIPTION,
						'compare' => '!=',
					),
				);
			}

			$query = CourseModel::get_courses_by_args( $args );
			if ( is_a( $query, 'WP_Query' ) ) {
				foreach ( $query->get_posts() as $post ) {
					$response['results'][] = Course::get_card_data( $post );
				}

				$response['total_items'] = $query->found_posts;
			}

			$this->json_response(
				__( 'Course retrieved successfully!', 'tutor-pro' ),
				$response
			);
		} catch ( \Throwable $th ) {
			$this->json_response(
				tutor_utils()->error_message( 'server_error' ),
				$th->getMessage(),
				HttpHelper::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * Validate input data based on predefined rules.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data The data array to validate.
	 *
	 * @return object The validation result. It returns validation object.
	 */
	private function validate( array $data ) {
		$allowed_payment_status = implode( ',', array( OrderModel::PAYMENT_PAID, OrderModel::PAYMENT_UNPAID ) );

		$validation_rules = array(
			'student_ids'    => 'required',
			'object_ids'     => 'required',
			'order_type'     => 'required',
			'payment_status' => "required|match_string:{$allowed_payment_status}",
		);

		// Skip validation rules for not available fields in data.
		foreach ( $validation_rules as $key => $value ) {
			if ( ! array_key_exists( $key, $data ) ) {
				unset( $validation_rules[ $key ] );
			}
		}

		return ValidationHelper::validate( $validation_rules, $data );
	}

}
