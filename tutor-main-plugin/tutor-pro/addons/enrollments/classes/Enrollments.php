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

defined( 'ABSPATH' ) || exit;

use Exception;
use TUTOR\Input;
use TUTOR\Course;
use TUTOR\Earnings;
use Tutor\Models\UserModel;
use Tutor\Models\OrderModel;
use Tutor\Helpers\HttpHelper;
use Tutor\Models\CourseModel;
use Tutor\Helpers\QueryHelper;
use Tutor\Traits\JsonResponse;
use Tutor\Helpers\ValidationHelper;
use Tutor\Ecommerce\OrderController;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Models\EnrollmentModel;
use Tutor\Models\OrderActivitiesModel;
use TutorPro\CourseBundle\Models\BundleModel;
use WpOrg\Requests\Exception\InvalidArgument;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;

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
	 * @since 3.5.0 param register_hooks added.
	 *
	 * @param bool $register_hooks whether to register hooks or not.
	 *
	 * @return void
	 */
	public function __construct( $register_hooks = true ) {

		if ( ! $register_hooks ) {
			return;
		}

		add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );

		add_action( 'wp_ajax_tutor_json_search_students', array( $this, 'tutor_json_search_students' ) );
		add_action( 'tutor_action_enrol_student', array( $this, 'enrol_student' ) );
		add_action( 'wp_ajax_tutor_enroll_bulk_student', array( $this, 'tutor_enroll_bulk_student' ) );
		add_action( 'tutor_after_enrollment', __CLASS__ . '::create_tutor_order', 10, 4 );

		add_action( 'wp_ajax_tutor_unenrolled_users', array( $this, 'ajax_get_unenrolled_users' ) );
		add_action( 'wp_ajax_tutor_course_bundle_list', array( $this, 'ajax_course_bundle_list' ) );

		// @since: 3.3.0
		add_action( 'tutor_course/single/entry/after', array( $this, 'show_enrollment_period_status' ) );
		add_action( 'tutor_course_loop_footer_bottom', array( $this, 'show_enrollment_period_status' ) );
		add_filter( 'tutor_guest_password_reset_email_heading_text', array( $this, 'set_password_reset_email_text' ) );

		add_filter( 'tutor_add_to_cart_btn', array( $this, 'restrict_enrollment' ), 10, 2 );
		add_filter( 'tutor_course_loop_add_to_cart_button', array( $this, 'restrict_enrollment' ), 10, 2 );
		add_filter( 'tutor_course_restrict_new_entry', array( $this, 'restrict_enrollment' ), 10, 2 );
		add_filter( 'tutor/course/single/entry-box/free', array( $this, 'restrict_enrollment' ), 10, 2 );
		add_filter( 'tutor_pro_subscription_enrollment', array( $this, 'restrict_enrollment' ), 10, 2 );
		add_filter( 'tutor_allow_guest_attempt_enrollment', array( $this, 'restrict_guest_attempt_enrollment' ), 10, 3 );
		add_filter( 'tutor_can_purchase_course', array( $this, 'validate_course_enrollment_settings' ), 10, 2 );
		add_filter( 'tutor_allow_course_enrollment', array( $this, 'validate_course_enrollment_settings' ), 10, 2 );
	}

	/**
	 * Validate course enrollment setting before enrollment.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $can_enroll whether user can enroll in course.
	 * @param int  $course_id the course id.
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_course_enrollment_settings( $can_enroll, $course_id ) {
		$course_settings = $this->get_course_enrollment_settings( $course_id );

		if ( ! tutor_utils()->count( $course_settings ) ) {
			return true;
		}

		if ( ! isset( $course_settings['course_enrollment_period'] ) || ! isset( $course_settings['pause_enrollment'] ) ) {
			return true;
		}

		$enrollment_period = $course_settings['course_enrollment_period'];
		$pause_enrollment  = $course_settings['pause_enrollment'];

		// translators: %s is the course title.
		$error_message = new \WP_Error( 'pause_enrollment_error', sprintf( __( 'Enrollment for %s is currently paused. Please remove it from your cart to proceed.', 'tutor-pro' ), get_the_title( $course_id ) ?? '' ) );

		if ( 'yes' === $pause_enrollment ) {
			return $error_message;
		}

		if ( 'yes' === $enrollment_period ) {
			$enrollment_start  = strtotime( $course_settings['enrollment_starts_at'] );
			$enrollment_end    = strtotime( $course_settings['enrollment_ends_at'] );
			$current_date_time = DateTimeHelper::now()->to_date_time_string();
			$current_date_time = strtotime( $current_date_time );

			if ( $enrollment_start && $enrollment_start > $current_date_time ) {
				return $error_message;
			}

			if ( $enrollment_end && $enrollment_end < $current_date_time ) {
				return $error_message;
			}
		}

		return true;
	}


	/**
	 * Prevent users from enrolling after registration.
	 *
	 * @since 3.4.0
	 *
	 * @param bool $can_enroll whether can user enroll.
	 * @param int  $course_id the course id.
	 * @param int  $user_id the user id.
	 *
	 * @return bool
	 */
	public function restrict_guest_attempt_enrollment( $can_enroll, $course_id, $user_id ) {
		list( $pause_enrollment, $course_enrollment_period, $enrollment_starts_at, $enrollment_ends_at ) = array_values( $this->get_course_enrollment_settings( $course_id ) );

		$current_time = time();

		$start_time = strtotime( $enrollment_starts_at );
		$end_time   = strtotime( $enrollment_ends_at );

		if ( $pause_enrollment ) {
			$can_enroll = false;
		} elseif ( 'yes' === $course_enrollment_period ) {
			if ( $start_time && $end_time ) {
				$not_started = $current_time < $start_time;
				$ended       = $current_time > $end_time;
				if ( $not_started || $ended ) {
					$can_enroll = false;
				}
			} elseif ( $enrollment_starts_at && ! $enrollment_ends_at ) {
				if ( $current_time < strtotime( $enrollment_starts_at ) ) {
					$can_enroll = false;
				}
			}
		}

		return $can_enroll;
	}

	/**
	 * Set reset password email heading text.
	 *
	 * @since 3.3.0
	 *
	 * @param string $email_text the reset email heading text.
	 *
	 * @return string
	 */
	public function set_password_reset_email_text( $email_text ) {
		if ( is_admin() ) {
			$email_text = __( "As part of your course enrollment, we've created an account for you to access all the learning resources and course content. Please set up your account password to access your courses.", 'tutor-pro' );
		}
		return $email_text;
	}

	/**
	 * Register Enrollment Menu
	 *
	 * @since 2.0.0
	 * @since 3.8.0 param $menu added.
	 *
	 * @param array $menu menu.
	 *
	 * @return array
	 */
	public function register_menu( $menu ) {
		$menu['group_two']['enrollments'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'Enrollment', 'tutor-pro' ),
			'menu_title'  => __( 'Enrollment', 'tutor-pro' ),
			'capability'  => 'manage_tutor',
			'menu_slug'   => 'enrollments',
			'callback'    => array( $this, 'enrollments' ),
		);

		return $menu;
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
	 * @since 3.0.0 Multiple course enrollment support added
	 * @since 3.3.0 Bulk enrollment with csv file support added.
	 * @since 3.4.0 Bulk subscription support added.
	 *
	 * @return void
	 */
	public function tutor_enroll_bulk_student() {

		$required_fields = array(
			'object_ids',
			'payment_status',
			'order_type',
		);

		tutor_utils()->check_nonce();

		tutor_utils()->check_current_user_capability();

		$request = Input::sanitize_array( $_POST ); //phpcs:ignore --safe data
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

		$student_ids    = $request->student_ids ?? null;
		$payment_status = $request->payment_status;
		$order_type     = $request->order_type;
		$object_ids     = $request->object_ids;
		$csv_students   = $request->csv_students ?? null;

		if ( empty( $student_ids ) && empty( $csv_students ) ) {
			$this->response_bad_request( __( 'Please select at least one student', 'tutor-pro' ) );
		}

		if ( empty( $object_ids ) ) {
			$this->response_bad_request( __( 'Please select a course or subscription plan', 'tutor-pro' ) );
		}

		$failed_enrollments = array();
		$total_enrollments  = 0;
		$response_message   = __( 'Enrollment done for selected students', 'tutor-pro' );
		$enrollment_data    = array(
			'failed_enrollment_list'  => $failed_enrollments,
			'total_enrolled_students' => $total_enrollments,
		);

		if ( ! empty( $csv_students ) ) {
			$csv_students = array_map( fn ( $val ) => json_decode( $val ), $csv_students );
			$result       = $this->get_student_ids_from_csv( $csv_students, $student_ids );

			if ( is_wp_error( $result ) ) {
				$this->response_bad_request( $result->get_error_message() );
			}

			$student_ids        = $result->student_ids;
			$failed_enrollments = array_merge( $failed_enrollments, $result->failed_enrollments );

			if ( 0 === count( $student_ids ) ) {
				$enrollment_data['failed_enrollment_list'] = $failed_enrollments;
				$this->json_response( $response_message, $enrollment_data );
			}
		}

		// Handle manual subscription enrollment.
		$enrollment_data = apply_filters( 'tutor_manual_enrollment', $enrollment_data, $object_ids, $student_ids, $payment_status );
		if ( isset( $enrollment_data['is_subscription_enrollment'] ) ) {
			$this->json_response( $enrollment_data['message'] ?? $response_message, $enrollment_data );
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
				if ( ! is_object( $post ) || ! in_array( $post->post_type, array( tutor()->course_post_type, tutor()->bundle_post_type ) ) ) {
					continue;
				}
				$is_set_enrollment_expiry = (int) get_tutor_course_settings( $object_id, 'enrollment_expiry' );
				$is_bundle                = tutor()->bundle_post_type === $post->post_type && tutor_utils()->is_addon_enabled( 'course-bundle' );

				// Check all selected student are not enrolled before.
				$is_already_enrolled = false;
				foreach ( $student_ids as $student_id ) {
					if ( $is_bundle ) {
						$is_already_enrolled = BundleModel::is_enrolled_to_bundle_courses( $post->ID, $student_id );
					} else {
						$is_already_enrolled = EnrollmentModel::is_enrolled( $object_id, $student_id, false );
					}

					$enrollment_record = EnrollmentModel::get_enrolled_data( $student_id, $object_id, null );

					if ( $enrollment_record && $is_set_enrollment_expiry ) {
						if ( $is_bundle ) {
							$course_ids = BundleModel::get_bundle_course_ids( $object_id );
							foreach ( $course_ids as $course_id ) {
								EnrollmentModel::delete_enrollment_record( $student_id, $course_id );
							}
						}

						// Delete user enrollment expiry meta if there is any.
						$has_student_enrollment_meta = get_user_meta( $student_id, 'tutor_course_enrollment_expiry_date_' . $object_id, true );

						if ( $has_student_enrollment_meta ) {
							delete_user_meta( $student_id, 'tutor_course_enrollment_expiry_date_' . $object_id );
						}

						EnrollmentModel::delete_enrollment_record( $student_id, $object_id );
						$is_already_enrolled = false;
					}

					if ( $is_already_enrolled ) {
						// Skip already enrolled student.
						$failed_enrollments[] = $this->get_failed_user_data( $student_id );
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
							function ( $data ) {
								$data['post_status'] = 'completed';
								return $data;
							}
						);
					}

					if ( $is_paid_course && tutor_utils()->is_monetize_by_tutor() && OrderModel::PAYMENT_PAID === $payment_status ) {
						add_filter(
							'tutor_enroll_data',
							function ( $data ) {
								$data['post_status'] = 'completed';
								return $data;
							}
						);
					}

					$allow_enrollment = fn() => true;

					add_filter( 'tutor_allow_course_enrollment', $allow_enrollment );

					// Enroll to course/bundle.
					$enrolled = EnrollmentModel::do_enroll( $object_id, $order_id, $student_id );

					remove_filter( 'tutor_allow_course_enrollment', $allow_enrollment );

					if ( $enrolled ) {
						++$total_enrollments;
					}

					if ( 0 === $enrolled ) {
						$failed_enrollments[] = $this->get_failed_user_data( $student_id );
					}

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

					// Enroll to free bundle courses.
					if ( CourseBundle::POST_TYPE === $post->post_type && ! $is_paid_course ) {
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

		$this->json_response(
			$response_message,
			array(
				'failed_enrollment_list'  => $failed_enrollments,
				'total_enrolled_students' => $total_enrollments,
			)
		);
	}


	/**
	 * Get failed user data for failed enrollments.
	 *
	 * @since 3.4.0
	 *
	 * @param int $student_id the student id.
	 *
	 * @return array
	 */
	public function get_failed_user_data( $student_id ) {
		$user        = get_userdata( $student_id );
		$failed_user = array(
			'first_name' => $user->user_login,
			'last_name'  => '',
			'email'      => $user->user_email,
		);

		return $failed_user;
	}

	/**
	 * Get student ids from csv file.
	 *
	 * @since 3.3.0
	 *
	 * @param array $students_from_csv the students array from csv file.
	 * @param array $student_ids the array of student ids.
	 *
	 * @return array|WP_Error
	 */
	public function get_student_ids_from_csv( $students_from_csv, $student_ids ) {
		$failed_users = array();

		if ( ! is_array( $student_ids ) ) {
			$student_ids = array();
		}

		if ( is_array( $students_from_csv ) && count( $students_from_csv ) ) {

			foreach ( $students_from_csv as $student ) {
				if ( ! $student ) {
					continue;
				}

				if ( ! array_intersect_key( (array) $student, array_flip( array( 'first_name', 'last_name', 'email' ) ) ) ) {
					return new \WP_Error( 'invalid_data', __( 'Invalid data found in csv file', 'tutor-pro' ) );
				}

				// Check if user exists.
				if ( email_exists( $student->email ) ) {
					$user = get_user_by( 'email', $student->email );
					if ( ! in_array( $user->ID, $student_ids ) ) {
						$student_ids[] = $user->ID;
					} else {
						$failed_user    = array(
							'first_name' => $user->user_login,
							'last_name'  => '',
							'email'      => $user->user_email,
						);
						$failed_users[] = $failed_user;
					}
				} else {
					$user_login = tutor_utils()->create_unique_username( $student->email );
					$userdata   = array(
						'user_login' => $user_login,
						'first_name' => $student->first_name,
						'last_name'  => $student->last_name,
						'user_email' => $student->email,
						'user_pass'  => wp_generate_password(),
					);

					$student_id = wp_insert_user( $userdata );

					if ( is_wp_error( $student_id ) ) {
						$failed_users[] = $student;
						continue;
					}

					$user_data = get_userdata( $student_id );

					if ( $user_data ) {
						( new \TUTOR_PRO\GuestEmail() )->send_password_reset_email( $user_data );
					}

					$student_ids[] = $student_id;
				}
			}
		}

		return (object) array(
			'student_ids'        => $student_ids,
			'failed_enrollments' => $failed_users,
		);
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

		$order_controller = new OrderController( false );
		$order_model      = new OrderModel();
		$earnings         = Earnings::get_instance();
		$args             = array();

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

		/**
		 * Discount amount added for sale product
		 *
		 * @since 4.0.0
		 */
		if ( $price->sale_price ) {
			$args['discount_amount'] = $price->regular_price - $price->sale_price;
		}

		try {
			$order_id = $order_controller->create_order( $student_id, $item, $payment_status, $order_type, null, $args );

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

		$limit      = Input::post( 'limit', 10, Input::TYPE_INT );
		$offset     = Input::post( 'offset', 0, Input::TYPE_INT );
		$object_id  = Input::post( 'object_id', 0, Input::TYPE_INT );
		$order_type = Input::post( 'order_type' );

		$user_model    = new UserModel();
		$search_clause = array();
		$filter        = isset( $_POST['filter'] ) ? json_decode( wp_unslash( $_POST['filter'] ) ) : ''; //phpcs:ignore --safe data
		if ( ! empty( $filter ) && is_object( $filter ) && property_exists( $filter, 'search' ) ) {
			$search_term   = Input::sanitize( $filter->search );
			$search_clause = array(
				'u.ID'           => $search_term,
				'u.user_login'   => $search_term,
				'u.user_email'   => $search_term,
				'u.display_name' => $search_term,
			);
		}

		$enrollment_object_id = $object_id;
		if ( OrderModel::TYPE_SUBSCRIPTION === $order_type ) {
			$plan = apply_filters( 'tutor_get_plan_info', null, $object_id );
			if ( ! $plan ) {
				$this->response_bad_request( __( 'Invalid plan selected', 'tutor-pro' ) );
				return;
			}

			$enrollment_object_id = $plan->is_membership_plan
									? -1
									: Input::post( 'course_id', 0, Input::TYPE_INT );
		}

		$response    = $user_model->get_unenrolled_users( $enrollment_object_id, $search_clause, $limit, $offset );
		$total_items = $response['total_count'];
		unset( $response['total_count'] );
		$response['total_items'] = $total_items;

		// Check if user is enrolled in bundle.
		if ( OrderModel::TYPE_SINGLE_ORDER === $order_type && CourseBundle::POST_TYPE === get_post_type( $object_id ) ) {
			$response['results'] = array_map(
				function ( $user ) use ( $object_id ) {
					$is_enrolled       = BundleModel::is_enrolled_to_bundle_courses( $object_id, $user->ID );
					$user->is_enrolled = $is_enrolled;
					return $user;
				},
				$response['results']
			);
		}

		$response = apply_filters( 'tutor_unenrolled_users_response', $response, $object_id );

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

		if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
			$args['post_type'] = array( tutor()->course_post_type, 'course-bundle' );
		}

		$filter = isset( $_POST['filter'] ) ? json_decode( wp_unslash( $_POST['filter'] ) ) : ''; //phpcs:ignore --safe data
		if ( ! empty( $filter ) && is_object( $filter ) && property_exists( $filter, 'search' ) ) {
			$args['s'] = Input::sanitize( $filter->search );
		}

		try {

			$query = CourseModel::get_courses_by_args( $args );
			if ( is_a( $query, 'WP_Query' ) ) {
				foreach ( $query->get_posts() as $post ) {
					$maximum_students         = tutor_utils()->get_course_settings( $post->ID, 'maximum_students' );
					$course_enrollment_period = tutor_utils()->get_course_settings( $post->ID, 'course_enrollment_period' );
					$enrollment_ends_at       = tutor_utils()->get_course_settings( $post->ID, 'enrollment_ends_at' );
					$pause_enrollment         = tutor_utils()->get_course_settings( $post->ID, 'pause_enrollment' );
					$selling_option           = Course::get_selling_option( $post->ID );

					$course_data = array_merge(
						Course::get_card_data( $post ),
						array(
							'selling_option'           => $selling_option,
							'maximum_students'         => $maximum_students,
							'enrollment_ends_at'       => $enrollment_ends_at,
							'pause_enrollment'         => $pause_enrollment,
							'course_enrollment_period' => $course_enrollment_period,
						)
					);

					$response['results'][] = $course_data;
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
			'csv_students'   => 'required',
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

	/**
	 * Alter tutor enroll box to show enrollment period status
	 *
	 * @since 3.3.0
	 *
	 * @param string $course_id  current course id.
	 *
	 * @return void
	 */
	public function show_enrollment_period_status( $course_id ) {
		$user_id = get_current_user_id();
		if ( EnrollmentModel::is_enrolled( $course_id, $user_id ) || ! in_array( get_post_type( $course_id ), array( tutor()->course_post_type, tutor()->bundle_post_type ), true ) || 'yes' === get_post_meta( $course_id, '_tutor_is_public_course', true ) ) {
			return;
		}

		$content = '';

		list( $pause_enrollment, $course_enrollment_period, $enrollment_starts_at, $enrollment_ends_at ) = array_values( $this->get_course_enrollment_settings( $course_id ) );

		if ( 'yes' === $pause_enrollment ) {
			ob_start();
			?>
			<div class="tutor-enrollment-status-wrapper tutor-enrollment-status-paused">
				<i class="tutor-icon-warning"></i>
				<?php esc_attr_e( 'Enrollment is now paused', 'tutor-pro' ); ?>
			</div>
			<?php
			$content = ob_get_clean();
		} elseif ( 'yes' === $course_enrollment_period && $enrollment_starts_at ) {
			$current_time = time();

			if ( ! $enrollment_ends_at ) {
				if ( $current_time < strtotime( $enrollment_starts_at ) ) {
					ob_start();
					?>
					<div class="tutor-enrollment-status-wrapper">
						<i class="tutor-icon-book-open-line"></i>
						<span>
							<?php
							printf(
								/* translators: %s: from date */
								esc_html__( 'Enrollment opens on %1$s', 'tutor-pro' ),
								'<span class="tutor-utc-date-time tutor-color-success">' . esc_html( $enrollment_starts_at ) . '</span>'
							);
							?>
						</span>
					</div>
					<?php
					$content = ob_get_clean();
				}
			} elseif ( $current_time < strtotime( $enrollment_starts_at ) ) {
					ob_start();
				?>
					<div class="tutor-enrollment-status-wrapper">
						<i class="tutor-icon-book-open-line"></i>
						<span>
							<?php
							printf(
								/* translators: %s: from date %s: to date */
								esc_html__( 'Enrollment Period %1$s - %2$s', 'tutor-pro' ),
								'<span class="tutor-utc-date-time tutor-color-success">' . esc_html( $enrollment_starts_at ) . '</span>',
								'<span class="tutor-utc-date-time tutor-color-success">' . esc_html( $enrollment_ends_at ) . '</span>'
							);
							?>
						</span>
					</div>
					<?php
					$content = ob_get_clean();
			} elseif ( $current_time > strtotime( $enrollment_starts_at ) && $current_time < strtotime( $enrollment_ends_at ) ) {
				ob_start();
				?>
					<div class="tutor-enrollment-status-wrapper">
						<i class="tutor-icon-book-open-line"></i>
						<span>
							<?php
							printf(
								/* translators: %s: from date */
								esc_html__( 'Enrollment closes on %1$s', 'tutor-pro' ),
								'<span class="tutor-utc-date-time tutor-color-primary">' . esc_html( $enrollment_ends_at ) . '</span>'
							);
							?>
						</span>
					</div>
					<?php
					$content = ob_get_clean();
			} elseif ( $current_time > strtotime( $enrollment_ends_at ) ) {
				ob_start();
				?>
					<div class="tutor-enrollment-status-wrapper tutor-enrollment-status-closed">
						<i class="tutor-icon-circle-info-o"></i>
					<?php esc_html_e( 'Enrollment is now closed', 'tutor-pro' ); ?>
					</div>
					<?php
					$content = ob_get_clean();
			}
		}
		echo wp_kses_post( $content );
	}

	/**
	 * Remove add to cart button based on enrollment period settings
	 *
	 * @since 3.3.0
	 *
	 * @param string $btn Button HTML.
	 * @param int    $course_id Course ID.
	 *
	 * @return string Modified button HTML
	 */
	public function restrict_enrollment( $btn, $course_id ) {
		$user_id = get_current_user_id();
		if ( EnrollmentModel::is_enrolled( $course_id, $user_id ) || ! in_array( get_post_type( $course_id ), array( tutor()->course_post_type, tutor()->bundle_post_type ), true ) ) {
			return $btn;
		}

		list( $pause_enrollment, $course_enrollment_period, $enrollment_starts_at, $enrollment_ends_at ) = array_values( $this->get_course_enrollment_settings( $course_id ) );

		$current_time = time();

		$start_time = strtotime( $enrollment_starts_at );
		$end_time   = strtotime( $enrollment_ends_at );

		if ( 'yes' === $pause_enrollment ) {
			$btn = '';
		} elseif ( 'yes' === $course_enrollment_period ) {
			if ( $start_time && $end_time ) {
				$not_started = $current_time < $start_time;
				$ended       = $current_time > $end_time;
				if ( $not_started || $ended ) {
					$btn = '';
				}
			} elseif ( $enrollment_starts_at && ! $enrollment_ends_at ) {

				if ( $current_time < strtotime( $enrollment_starts_at ) ) {
					$btn = '';
				}
			}
		}

		return $btn;
	}

	/**
	 * Get course enrollment settings data
	 *
	 * @since 3.3.0
	 *
	 * @param integer $course_id Course id.
	 *
	 * @return array
	 */
	public function get_course_enrollment_settings( int $course_id ): array {
		$pause_enrollment         = get_tutor_course_settings( $course_id, 'pause_enrollment' );
		$course_enrollment_period = get_tutor_course_settings( $course_id, 'course_enrollment_period' );
		$enrollment_starts_at     = get_tutor_course_settings( $course_id, 'enrollment_starts_at' );
		$enrollment_ends_at       = get_tutor_course_settings( $course_id, 'enrollment_ends_at' );

		return array(
			'pause_enrollment'         => $pause_enrollment,
			'course_enrollment_period' => $course_enrollment_period,
			'enrollment_starts_at'     => $enrollment_starts_at,
			'enrollment_ends_at'       => $enrollment_ends_at,
		);
	}

	/**
	 * Check if the enrollment is active
	 *
	 * @since 3.3.0
	 *
	 * @param int|WP_Post $enrollment Enrollment ID or WP_Post object.
	 *
	 * @throws InvalidArgument If the argument is in invalid.
	 * @throws Exception If the enrollment is invalid.
	 *
	 * @return bool
	 */
	public static function is_active( $enrollment ) {
		if ( ! $enrollment ) {
			throw new InvalidArgument( __( 'Invalid argument passed', 'tutor-pro' ) );
		}

		$enrollment = is_int( $enrollment ) ? get_post( $enrollment ) : $enrollment;
		if ( ! $enrollment ) {
			throw new Exception( __( 'Invalid enrollment', 'tutor-pro' ) );
		}

		return in_array( $enrollment->post_status, array( 'complete', 'completed', 'approved' ), true );
	}

	/**
	 * Retrieve all enrollments for a specific course.
	 *
	 * @since 3.8.1
	 *
	 * @param int $id  The ID of the course/bundle. Defaults to 0.
	 *
	 * @return array Array of enrollments as associative arrays, or an empty array if an error occurs.
	 */
	public function get_all_enrollments( int $id = 0 ): array {

		global $wpdb;

		$where = array(
			'post_type'   => tutor()->enrollment_post_type,
			'post_parent' => $id,
		);

		return QueryHelper::get_all( $wpdb->posts, $where, 'ID' );
	}
}
