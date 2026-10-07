<?php
/**
 * Backend Course Bundle Listing
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle\Backend;

use TUTOR\Backend_Page_Trait;
use Tutor\Components\SvgIcon;
use Tutor\Helpers\QueryHelper;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Models\EnrollmentModel;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Models\BundleModel;
use TutorPro\CourseBundle\Utils;

/**
 * BundleList Class.
 *
 * @since 2.2.0
 */
class BundleList {

	use Backend_Page_Trait;

	/**
	 * Register hooks.
	 *
	 * @since 2.2.0
	 *
	 * @param bool $register_hooks register hooks.
	 *
	 * @return void|null
	 */
	public function __construct( $register_hooks = true ) {
		if ( ! $register_hooks ) {
			return;
		}

		add_filter( 'tutor_admin_course_list', array( $this, 'add_bundle_list' ), 10, 4 );
		add_filter( 'tutor_course_list_meta', array( $this, 'add_bundle_meta' ), 10, 2 );
		add_filter( 'tutor_course_list_course_edit_link', array( $this, 'add_bundle_edit_link' ), 10, 2 );
		add_filter( 'tutor_course_list_before_filter_items', array( $this, 'add_bundle_filter' ) );
		add_action( 'tutor_data_list_navbar_button', array( $this, 'create_bundle_button' ) );
		add_filter( 'tutor_course_dropdown_options', array( $this, 'add_bundle_course_to_dropdown' ) );

		add_action( 'trashed_post', array( $this, 'redirect_to_bundle_list_page' ) );

		add_action( 'save_post_' . CourseModel::POST_TYPE, array( $this, 'assign_category_to_bundle' ), 100 );
		add_action( 'save_post_' . CourseBundle::POST_TYPE, array( $this, 'assign_bundle_category' ), 100 );

		add_filter( 'tutor_course_mini_info', array( $this, 'add_total_courses_number' ), 10, 2 );
		add_filter( 'tutor_course_card_data', array( $this, 'add_bundle_course_duration' ), 10, 2 );
		add_action( 'tutor_enrollment_row_bundle_info', array( $this, 'add_bundle_course_to_enrollment_list' ), 10, 2 );
		add_action( 'tutor_enrollment/after/completed', array( $this, 'update_bundle_course_completed_enrollment_status' ) );
		add_action( 'tutor_enrollment/after/cancel', array( $this, 'update_bundle_course_cancel_enrollment_status' ) );
		add_action( 'tutor_delete_course_enrollments', array( $this, 'delete_bundle_course_enrollments' ), 10, 3 );
		add_action( 'tutor_after_enrollment_extend', array( $this, 'handle_extend_enrollment' ), 10, 3 );
	}

	/**
	 * Enroll in bundle courses after enrollment extended.
	 *
	 * @since 4.0.0
	 *
	 * @param \WP_Post $enrollment the enrollment post object.
	 * @param int      $bundle_id the bundle id.
	 * @param int      $student_id the student id.
	 *
	 * @return void
	 */
	public function handle_extend_enrollment( $enrollment, $bundle_id, $student_id ) {
		if ( CourseBundle::POST_TYPE !== get_post_type( $bundle_id ) ) {
			return;
		}

		$enrolled_course_ids = BundleModel::get_bundle_enrollment_ids( $bundle_id, $student_id, $enrollment->ID );

		if ( count( $enrolled_course_ids ) ) {
			EnrollmentModel::update_enrollments( EnrollmentModel::STATUS_COMPLETED, $enrolled_course_ids, false );
		}
	}

	/**
	 * Delete course enrollments after bundle enrollments is deleted.
	 *
	 * @since 4.0.0
	 *
	 * @param int $enrollment_id the enrollment id.
	 * @param int $bundle_id the bundle id.
	 * @param int $student_id the student id.
	 *
	 * @return void
	 */
	public function delete_bundle_course_enrollments( $enrollment_id, $bundle_id, $student_id ) {
		if ( tutor()->bundle_post_type !== get_post_type( $bundle_id ) ) {
			return;
		}

		$enrollment_ids = BundleModel::get_bundle_enrollment_ids( $bundle_id, $student_id, $enrollment_id );

		if ( count( $enrollment_ids ) ) {
			foreach ( $enrollment_ids as $enroll_id ) {
				$course_enrollment = get_post( $enroll_id );

				if ( is_object( $course_enrollment ) ) {
					$course_id = $course_enrollment->post_parent;

					if ( $course_id ) {
						tutor_utils()->delete_course_progress( $course_id, $student_id );
					}

					// Delete only those enrollments that are cancelled.
					if ( in_array( $course_enrollment->post_status, array( 'cancel', 'cancelled', 'canceled' ), true ) ) {
						QueryHelper::delete( 'posts', array( 'ID' => $enroll_id ) );
					}
				}
			}
		}
	}

	/**
	 * Handle course enrollment after bundle enrollment status change.
	 *
	 * @since 4.0.0
	 *
	 * @param int $enrollment_id the enrollment id.
	 *
	 * @return void
	 */
	public function update_bundle_course_completed_enrollment_status( $enrollment_id ) {
		if ( ! $enrollment_id ) {
			return;
		}

		$enrollment = get_post( $enrollment_id ) ?? null;
		$bundle_id  = $enrollment ? $enrollment->post_parent : 0;
		if ( tutor()->bundle_post_type !== get_post_type( $bundle_id ) ) {
			return;
		}

		$is_set_enrollment_expiry  = (int) get_tutor_course_settings( $bundle_id, 'enrollment_expiry' );
		$enrollment_expiry_enabled = (bool) get_tutor_option( 'enrollment_expiry_enabled' );
		if ( $enrollment_expiry_enabled && $is_set_enrollment_expiry ) {
			global $wpdb;
			QueryHelper::update(
				$wpdb->posts,
				array(
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', true ),
				),
				array(
					'ID'        => $enrollment_id,
					'post_type' => tutor()->enrollment_post_type,
				)
			);
		}

		$enrolled_course_ids = BundleModel::get_bundle_enrollment_ids( $bundle_id, $enrollment->post_author, $enrollment_id );

		if ( count( $enrolled_course_ids ) ) {
			EnrollmentModel::update_enrollments( EnrollmentModel::STATUS_COMPLETED, $enrolled_course_ids, false );
		}
	}

	/**
	 * Handle course enrollment after bundle enrollment status change.
	 *
	 * @since 4.0.0
	 *
	 * @param int $enrollment_id the enrollment id.
	 *
	 * @return void
	 */
	public function update_bundle_course_cancel_enrollment_status( $enrollment_id ) {
		if ( ! $enrollment_id ) {
			return;
		}

		$enrollment = get_post( $enrollment_id ) ?? null;
		$bundle_id  = $enrollment ? $enrollment->post_parent : 0;
		if ( tutor()->bundle_post_type !== get_post_type( $bundle_id ) ) {
			return;
		}
		$enrolled_course_ids = BundleModel::get_bundle_enrollment_ids( $bundle_id, $enrollment->post_author, $enrollment_id );
		if ( count( $enrolled_course_ids ) ) {
			EnrollmentModel::update_enrollments( EnrollmentModel::STATUS_CANCEL, $enrolled_course_ids, false );
		}
	}

	/**
	 * Render bundle courses list as tooltip on enrollment list.
	 *
	 * @since 4.0.0
	 *
	 * @param string    $content the content to render.
	 * @param \stdClass $enrollment the enrollment object.
	 *
	 * @return void
	 */
	public function add_bundle_course_to_enrollment_list( $content, $enrollment ) {
		$course_id = $enrollment->course_id;
		if ( tutor()->bundle_post_type === get_post_type( $course_id ) ) {
			$bundle_courses = BundleModel::get_bundle_courses( $course_id );
			SvgIcon::make()->name( 'bundle' )->size( 20 )->attr( 'class', 'tutor-d-flex tutor-align-center' )->render();
			require Utils::template_path( 'course-list-card.php' );
		}
	}

	/**
	 * Add bundle courses to course dropdown.
	 *
	 * @since 4.0.0
	 *
	 * @param array $course_options existing course options.
	 *
	 * @return array
	 */
	public function add_bundle_course_to_dropdown( $course_options ) {
		$course_options[0] = array(
			'key'   => '',
			'title' => __( 'All Courses & Bundles', 'tutor-pro' ),
		);
		$bundle_list       = BundleModel::get_bundle_list();

		if ( ! count( $bundle_list->posts ) ) {
			return $course_options;
		}

		foreach ( $bundle_list->posts as $bundle ) {
			$course_options[] = array(
				'key'   => $bundle->ID,
				'title' => $bundle->post_title,
			);
		}
		return $course_options;
	}

	/**
	 * Add post type filter for course and bundles in course list.
	 *
	 * @since 3.5.0
	 *
	 * @param array $filters existing filters.
	 *
	 * @return array
	 */
	public function add_bundle_filter( $filters ) {
		if ( is_admin() && 'tutor' === Input::get( 'page', '' ) ) {
			$bundle_filter = array(
				'label'      => __( 'Type', 'tutor-pro' ),
				'field_type' => 'select',
				'field_name' => 'post-type',
				'options'    => array(
					array(
						'key'   => '',
						'title' => __( 'Courses & Bundles', 'tutor-pro' ),
					),
					array(
						'key'   => 'courses',
						'title' => __( 'Courses', 'tutor-pro' ),
					),
					array(
						'key'   => 'course-bundle',
						'title' => __( 'Bundles', 'tutor-pro' ),
					),
				),
				'value'      => Input::get( 'post-type', '' ),
			);

			return array_merge( array( $bundle_filter ), $filters );
		}
	}

	/**
	 * Add Create Bundle button on course list.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function create_bundle_button() {
		if ( is_admin() && 'tutor' === Input::get( 'page', '' ) ) {
			?>
			<button class="tutor-btn tutor-btn-outline-primary tutor-d-flex tutor-align-center tutor-gap-1 tutor-add-new-course-bundle">
				<i class="tutor-icon-bundle"></i>
				<span><?php esc_html_e( 'New Bundle', 'tutor-pro' ); ?></span>
			</button>
			<?php
		}
	}

	/**
	 * Set course bundle edit link.
	 *
	 * @since 3.5.0
	 *
	 * @param string   $edit_link the course edit link to update.
	 * @param \WP_POST $post the post object.
	 *
	 * @return string
	 */
	public function add_bundle_edit_link( $edit_link, $post ) {
		if ( ! isset( $post ) || CourseBundle::POST_TYPE !== $post->post_type ) {
			return $edit_link;
		}

		$edit_link = Utils::construct_page_url( 'edit', $post->ID );
		return $edit_link;
	}

	/**
	 * Filter meta value to show bundle info.
	 *
	 * @since 3.5.0
	 *
	 * @param string   $content the meta content to display.
	 * @param \WP_POST $post the post object.
	 *
	 * @return string
	 */
	public function add_bundle_meta( $content, $post ) {
		if ( CourseBundle::POST_TYPE === $post->post_type ) {
			$total_courses = count( BundleModel::get_bundle_course_ids( $post->ID ) );
			$content       = '<div class="tutor-bundle-list-meta">' . esc_html( $total_courses . ' ' . _n( 'Course', 'Courses', $total_courses, 'tutor-pro' ) ) . '</div>';
		}
		return $content;
	}

	/**
	 * Filter backend course list to add bundles.
	 *
	 * @since 3.5.0
	 *
	 * @param array  $args arguments for querying courses.
	 * @param int    $user_id the user id.
	 * @param string $status the post status.
	 * @param bool   $all_post_types should keep all post types.
	 *
	 * @return array
	 */
	public function add_bundle_list( $args, $user_id, $status, $all_post_types ) {
		$post_type = Input::get( 'post-type', '' );

		if ( tutor()->course_post_type === $post_type ) {
			return $args;
		}

		if ( isset( $args['post_type'] ) ) {
			if ( ! $all_post_types && CourseBundle::POST_TYPE === $post_type ) {
				$args['post_type'] = CourseBundle::POST_TYPE;
			} else {
				$args['post_type'] = array( $args['post_type'], CourseBundle::POST_TYPE );
			}
		}

		return $args;
	}

	/**
	 * On course update, assign course category to bundle category
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id post id.
	 *
	 * @return void
	 */
	public function assign_category_to_bundle( $post_id ) {
		if ( CourseModel::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$bundle_ids = BundleModel::get_bundle_ids_by_course( $post_id );
		if ( empty( $bundle_ids ) ) {
			return;
		}

		foreach ( $bundle_ids as $bundle_id ) {
			$this->assign_bundle_category( $bundle_id );
		}
	}

	/**
	 * Assign bundle category.
	 *
	 * @param int $post_id post id.
	 *
	 * @return void
	 */
	public static function assign_bundle_category( $post_id ) {
		if ( CourseBundle::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$categories = BundleModel::get_bundle_course_categories( $post_id );
		$cat_ids    = array_column( $categories, 'term_id' );

		wp_set_post_terms( $post_id, $cat_ids, CourseModel::COURSE_CATEGORY );
	}

	/**
	 * After trash a bundle direct to the bundle list page
	 *
	 * @since 2.2.4
	 *
	 * @param integer $post_id int bundle id.
	 *
	 * @return void
	 */
	public static function redirect_to_bundle_list_page( int $post_id ): void {
		$post = get_post( $post_id );
		if ( CourseBundle::POST_TYPE === $post->post_type ) {
			$is_gutenberg_enabled = tutor_utils()->get_option( 'enable_gutenberg_course_edit' );
			if ( ! $is_gutenberg_enabled ) {
				wp_safe_redirect( admin_url( 'admin.php?page=tutor' ) );
				exit;
			}
		}
	}

	/**
	 * Get bundle delete restriction message.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function get_delete_restriction_message() {
		return __( 'This bundle has enrolled student. It can not be deleted', 'tutor-pro' );
	}


	/**
	 * Update bundle status
	 *
	 * @param string $status for updating bundle status.
	 * @param string $bulk_ids comma separated ids.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public static function update_bundle_status( string $status, $bulk_ids ): bool {
		global $wpdb;
		$post_table = $wpdb->posts;
		$status     = sanitize_text_field( $status );
		$bulk_ids   = sanitize_text_field( $bulk_ids );

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$post_table} SET post_status = %s WHERE ID IN ($bulk_ids)", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$status
			)
		);

		return true;
	}

	/**
	 * Add total course to mini info
	 *
	 * @since 3.8.0
	 *
	 * @param array  $info info.
	 * @param object $post post data.
	 *
	 * @return array
	 */
	public static function add_total_courses_number( $info, $post ) {
		if ( tutor()->bundle_post_type === $post->post_type ) {
			$info['total_course'] = count( BundleModel::get_bundle_course_ids( $post->ID ) );
		}

		return $info;
	}

	/**
	 * Add course duration to card data
	 *
	 * @since 3.8.0
	 *
	 * @param array  $info info.
	 * @param object $post post data.
	 *
	 * @return array
	 */
	public static function add_bundle_course_duration( $info, $post ) {
		if ( tutor()->bundle_post_type === $post->post_type ) {
			$course_ids              = BundleModel::get_bundle_course_ids( $post->ID );
			$bundle_duration         = BundleModel::convert_seconds_into_human_readable_time( BundleModel::get_bundle_duration( $course_ids ), false );
			$info['course_duration'] = $bundle_duration;
		}

		return $info;
	}
}
