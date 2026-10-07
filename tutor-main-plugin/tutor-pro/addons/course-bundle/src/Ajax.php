<?php
/**
 * Handle Ajax Request.
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle;

use TUTOR\Course;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Traits\JsonResponse;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\CustomPosts\ManagePostMeta;
use TutorPro\CourseBundle\BundleEnrollmentBatchProcessor;
use TutorPro\CourseBundle\Models\BundleModel;

/**
 * Ajax Class.
 *
 * @since 2.2.0
 */
class Ajax {

	use JsonResponse;

	/**
	 * Register hooks.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_tutor_get_bundle_list', array( $this, 'ajax_get_bundle_list' ) );
		add_action( 'wp_ajax_tutor_add_remove_course_to_bundle', array( $this, 'add_remove_course_to_bundle' ) );
		add_action( 'wp_ajax_tutor_create_course_bundle', array( $this, 'ajax_create_course_bundle' ) );
		add_action( 'wp_ajax_tutor_get_course_bundle_data', array( $this, 'ajax_get_bundle_data' ) );
	}

	/**
	 * Get course list
	 *
	 * @since 3.6.0
	 *
	 *
	 * Refactor the arguments & response as per new design
	 *
	 * @return void
	 */
	public function ajax_get_bundle_list() {
		// Validate nonce.
		tutor_utils()->checking_nonce();

		// Check user permission.
		if ( ! Utils::current_user_can_create_bundle() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		$limit       = Input::post( 'limit', 10, Input::TYPE_INT );
		$offset      = Input::post( 'offset', 0, Input::TYPE_INT );
		$post_status = Input::post( 'post_status', null );
		$search_term = '';

		$filter = json_decode( wp_unslash( $_POST['filter'] ) ); //phpcs:ignore --sanitized already
		if ( ! empty( $filter ) && property_exists( $filter, 'search' ) ) {
			$search_term = Input::sanitize( $filter->search );
		}

		$args = array(
			'post_status'    => is_null( $post_status ) ? 'publish' : $post_status,
			'posts_per_page' => $limit,
			'offset'         => $offset,
			's'              => $search_term,
		);

		$exclude = Input::post( 'exclude', array(), Input::TYPE_ARRAY );
		if ( count( $exclude ) ) {
			$exclude              = array_filter(
				$exclude,
				function ( $id ) {
					return is_numeric( $id );
				}
			);
			$args['post__not_in'] = $exclude;
		}

		$bundles = BundleModel::get_bundle_list( $args );

		$response = array(
			'results'     => array(),
			'total_items' => 0,
		);

		$response['total_items'] = is_a( $bundles, 'WP_Query' ) ? $bundles->found_posts : 0;

		if ( is_a( $bundles, 'WP_Query' ) && $bundles->have_posts() ) {
			$bundles = $bundles->get_posts();
			foreach ( $bundles as $bundle ) {
				$response['results'][] = array(
					'id'            => $bundle->ID,
					'title'         => $bundle->post_title,
					'image'         => get_tutor_course_thumbnail_src( 'post-thumbnail', $bundle->ID ),
					'total_courses' => BundleModel::get_total_courses_in_bundle( $bundle->ID ),
				);
			}
		}

		$this->json_response(
			__( 'Bundle list retrieved successfully!', 'tutor-pro' ),
			$response
		);
	}

	/**
	 * Get course bundle data
	 *
	 * All the course bundle related data will be returned.
	 *
	 * @since 3.7.1 Allow admin to add/remove course from bundle
	 * regardless of enrollments
	 *
	 * @since 2.2.0
	 *
	 * @return void send wp_json response
	 */
	public function add_remove_course_to_bundle() {
		// Validate nonce.
		tutor_utils()->checking_nonce();

		// Post data.
		$bundle_id                = Input::post( 'ID', 0, Input::TYPE_INT );
		$course_ids               = Input::post( 'course_ids', array(), Input::TYPE_ARRAY );
		$user_action              = Input::post( 'user_action', '', Input::TYPE_STRING );
		$enroll_existing_students = Input::post( 'enroll_existing_students', '', Input::TYPE_STRING );

		// Check user permission.
		if ( ! Utils::current_user_can_update_bundle( $bundle_id ) ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		if ( ! $bundle_id || CourseBundle::POST_TYPE !== get_post_type( $bundle_id ) ) {
			$this->json_response(
				tutor_utils()->error_message( 'validation_error' ),
				__( 'Invalid bundle id or post type', 'tutor-pro' ),
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		if ( ! empty( $course_ids ) ) {
			// Remove course from bundle if user action is remove.
			if ( 'remove_course' === $user_action ) {
				BundleModel::remove_course_from_bundle( $course_ids[0], $bundle_id );
			} else {
				// Update bundle course_settings if enroll_existing_students is passed.
				if ( '' !== $enroll_existing_students ) {
					$course_settings                             = get_post_meta( $bundle_id, Course::COURSE_SETTINGS_META, true );
					$course_settings                             = is_array( $course_settings ) ? $course_settings : array();
					$course_settings['enroll_existing_students'] = $enroll_existing_students;
					update_post_meta( $bundle_id, Course::COURSE_SETTINGS_META, $course_settings );
				} else {
					$course_settings          = get_post_meta( $bundle_id, Course::COURSE_SETTINGS_META, true );
					$enroll_existing_students = is_array( $course_settings ) ? ( $course_settings['enroll_existing_students'] ?? 'no' ) : 'no';
				}

				// Otherwise add courses to the bundle.
				$existing_course_ids = BundleModel::get_bundle_course_ids( $bundle_id );
				$course_ids_to_add   = array_diff( $course_ids, $existing_course_ids );
				if ( ! empty( $course_ids_to_add ) ) {
					$updated_course_ids = array_merge( $existing_course_ids, $course_ids_to_add );
					$update             = BundleModel::update_bundle_course_ids( $bundle_id, $updated_course_ids );

					// If bundle course update failed.
					if ( ! $update ) {
						$this->json_response(
							__( 'Course could not added to the bundle.', 'tutor-pro' ),
							null,
							HttpHelper::STATUS_INTERNAL_SERVER_ERROR
						);
					}

					// Enroll existing students in newly added courses if enabled.
					if ( 'yes' === $enroll_existing_students ) {
						BundleEnrollmentBatchProcessor::instance()->add_to_queue( $bundle_id, array_values( $course_ids_to_add ) );
					}

					// Do action.
					do_action( 'tutor_course_bundle_course_added', $bundle_id, $course_ids );
				} else {
					$this->json_response(
						__( 'Course already added to the bundle.', 'tutor-pro' ),
						null,
						HttpHelper::STATUS_BAD_REQUEST
					);
				}
			}
		} else {
			$this->json_response(
				__( 'No courses found to add to the bundle.', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$bundle_data = BundleModel::get_bundle_data( $bundle_id );

		$regular_price = BundleModel::get_bundle_regular_price( $bundle_id );
		$sale_price    = tutor_utils()->get_raw_course_price( $bundle_id )->sale_price;

		if ( $sale_price > $regular_price ) {

			update_post_meta( $bundle_id, Course::COURSE_SALE_PRICE_META, 0 );
			$bundle_data['subtotal_sale_price']     = '';
			$bundle_data['subtotal_raw_sale_price'] = '';

		}

		$this->json_response(
			'remove_course' === $user_action ?
			__( 'Course removed from the bundle.', 'tutor-pro' ) :
			__( 'Course added to the bundle.', 'tutor-pro' ),
			$bundle_data
		);
	}

	/**
	 * Create course bundle.
	 *
	 * @since 3.2.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_create_course_bundle() {

		tutor_utils()->check_nonce();
		if ( ! Utils::current_user_can_create_bundle() ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		$post = Input::sanitize_array(
			$_POST,//phpcs:ignore
			array(
				'post_content'    => 'wp_kses_post',
				'course_benefits' => 'sanitize_textarea_field',
			)
		);

		$post['post_type']  = CourseBundle::POST_TYPE;
		$post['post_title'] = Input::post( 'post_title', __( 'New Bundle', 'tutor-pro' ), Input::TYPE_STRING );
		$post['post_name']  = Input::post( 'post_name', 'new-bundle', Input::TYPE_STRING );
		$sale_price         = Input::post( 'sale_price', 0, Input::TYPE_NUMERIC );

		if ( isset( $post['ID'] ) ) {

			if ( CourseBundle::POST_TYPE !== get_post_type( $post['ID'] ) ) {
				$this->json_response(
					__( 'Invalid bundle id or post type', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
			}

			$bundle_id     = $post['ID'];
			$regular_price = BundleModel::get_bundle_regular_price( $bundle_id );
			if ( $sale_price > $regular_price ) {
				$this->json_response(
					__( 'Sale price can not be greater than regular price', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
			}

			$post_author = get_post_field( 'post_author', $bundle_id );

			$post['post_author'] = $post_author;
		}

		$insert = wp_insert_post( $post );
		if ( is_wp_error( $insert ) ) {
			$this->json_response(
				$insert->get_error_message(),
				null,
				HttpHelper::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		if ( isset( $post['course_ids'] ) ) {
			BundleModel::update_bundle_course_ids( $insert, $post['course_ids'] );
		}

		if ( isset( $post['thumbnail_id'] ) ) {
			set_post_thumbnail( $insert, $post['thumbnail_id'] );
		}

		if ( isset( $post['tax_on_single'] ) ) {
			update_post_meta( $insert, Course::TAX_ON_SINGLE_META, $post['tax_on_single'] );
		}

		if ( isset( $post['tax_on_subscription'] ) ) {
			update_post_meta( $insert, Course::TAX_ON_SUBSCRIPTION_META, $post['tax_on_subscription'] );
		}

		if ( isset( $post['course_settings'] ) ) {
			update_post_meta( $insert, Course::COURSE_SETTINGS_META, $post['course_settings'] );
		}

		if ( isset( $post['source'] ) && 'frontend' === $post['source'] ) {
			$edit_url = Utils::construct_front_url( 'edit', $insert );
		} else {
			$edit_url = Utils::construct_page_url( 'edit', $insert );
		}

		do_action( 'tutor_save_bundle', $insert, $post );

		$this->json_response(
			__( 'Course Bundle updated successfully', 'tutor-pro' ),
			$edit_url,
		);
	}

	/**
	 * Get course bundle data.
	 *
	 * @since 3.2.0
	 * @since 4.0.6 Return unauthorized response if user doesn't have the bundle update permission.
	 *
	 * @return void send wp_json response
	 */
	public function ajax_get_bundle_data() {
		tutor_utils()->checking_nonce();
		$bundle_id = Input::post( 'bundle_id', 0, Input::TYPE_INT );
		if ( ! $bundle_id ) {
			$this->json_response(
				__( 'Invalid bundle id', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$bundle_course = get_post( $bundle_id );
		if ( ! Utils::current_user_can_update_bundle( $bundle_id ) ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}
		$bundle_data = BundleModel::get_bundle_data( $bundle_id );
		if ( is_a( $bundle_course, 'WP_Post' ) ) {
			$bundle_course->post_name = urldecode( $bundle_course->post_name );
			$bundle_course->details   = $bundle_data;
		} else {
			$this->json_response(
				__( 'Invalid bundle id', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		! empty( get_post_meta( $bundle_id, ManagePostMeta::BUNDLE_RIBBON_META_KEY, true ) ) ? $bundle_course->ribbon_type       = get_post_meta( $bundle_id, ManagePostMeta::BUNDLE_RIBBON_META_KEY, true ) : null;
		! empty( get_post_meta( $bundle_id, Course::COURSE_SELLING_OPTION_META, true ) ) ? $bundle_course->course_selling_option = get_post_meta( $bundle_id, Course::COURSE_SELLING_OPTION_META, true ) : null;

		$editors = tutor_utils()->get_editor_list( $bundle_id );

		$bundle_course->course_benefits = get_post_meta( $bundle_id, CourseModel::BENEFITS_META_KEY, true );
		$bundle_course->preview_link    = get_preview_post_link( $bundle_id );
		$bundle_course->thumbnail_id    = get_post_meta( $bundle_id, '_thumbnail_id', true );
		$bundle_course->thumbnail       = get_the_post_thumbnail_url( $bundle_id );
		$bundle_course->total_enrolled  = BundleModel::get_total_bundle_sold( $bundle_id );
		$bundle_course->editors         = array_values( $editors );
		$bundle_course->editor_used     = tutor_utils()->get_editor_used( $bundle_id );

		$tax_on_single       = get_post_meta( $bundle_id, Course::TAX_ON_SINGLE_META, true );
		$tax_on_subscription = get_post_meta( $bundle_id, Course::TAX_ON_SUBSCRIPTION_META, true );

		$bundle_course->tax_collection  = array(
			'tax_on_single'       => '' === $tax_on_single ? '1' : $tax_on_single,
			'tax_on_subscription' => '' === $tax_on_subscription ? '1' : $tax_on_subscription,
		);
		$bundle_course->course_settings = get_post_meta( $bundle_id, Course::COURSE_SETTINGS_META, true );

		$bundle_course = apply_filters( 'tutor_bundle_details_response', $bundle_course );

		$this->json_response(
			__( 'Success', 'tutor-pro' ),
			$bundle_course
		);
	}
}
