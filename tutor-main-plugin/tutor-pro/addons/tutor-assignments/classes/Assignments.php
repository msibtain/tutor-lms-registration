<?php
/**
 * Tutor Assignments
 *
 * @package TutorPro
 * @subpackage Addons\TutorAssignments
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_ASSIGNMENTS;

use Tutor\Cache\TutorCache;
use Tutor\Components\Badge;
use Tutor\Components\Table;
use Tutor\Components\SvgIcon;
use TUTOR\Input;
use TUTOR\Course;
use TUTOR\Tutor_Base;
use Tutor\Helpers\HttpHelper;
use Tutor\Models\CourseModel;
use Tutor\Helpers\QueryHelper;
use TUTOR\Icon;
use Tutor\Models\EnrollmentModel;
use Tutor\Options_V2;
use Tutor\Traits\JsonResponse;
use WP_Post;

/**
 * Class Assignments
 *
 * @since 1.0.0
 */
class Assignments extends Tutor_Base {
	use JsonResponse;

	/**
	 * Assignment statuses
	 *
	 * @since 4.0.0
	 */
	const PASSED  = 'passed';
	const FAILED  = 'failed';
	const PENDING = 'pending';

	/**
	 * Assignment user action
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const ACTION = 'submit';

	/**
	 * Assign post type
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $post_type;

	/**
	 * Assignment submission comment type.
	 *
	 * @var string
	 */
	const SUBMISSION_COMMENT_TYPE = 'tutor_assignment';

	/**
	 * Assignment addon path.
	 *
	 * @var string
	 */
	private $assignment_path;

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @since 2.6.2 added $allow_hooks param.
	 *
	 * @param bool   $allow_hooks default true.
	 * @param string $assignment_path the assignment addon path.
	 */
	public function __construct( $allow_hooks = true, $assignment_path = '' ) {
		parent::__construct();

		$this->assignment_path = $assignment_path;
		$this->post_type       = tutor()->assignment_post_type;

		if ( $allow_hooks ) {
			add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );
			add_filter( 'tutor_course_contents_post_types', array( $this, 'tutor_course_contents_post_types' ) );
			add_filter( 'post_type_link', array( $this, 'change_assignment_single_url' ), 1, 2 );
			add_filter( 'tutor/options/extend/attr', array( $this, 'extend_settings_option' ) );
			add_filter( 'tutor_assignment/single/content', array( $this, 'load_content_template' ), 9 );
			add_filter( 'tutor_instructor_dashboard_nav', array( $this, 'frontend_dashboard_nav_items' ) );
			add_filter( 'load_dashboard_template_part_from_other_location', array( $this, 'load_dashboard_template' ) );
			add_filter( 'tutor/options/extend/attr', array( $this, 'extend_settings_option' ) );
			add_filter( 'tutor_assignment/single/content', array( $this, 'load_content_template' ), 9 );

			add_action( 'wp_ajax_tutor_assignment_save', array( $this, 'ajax_assignment_save' ) );
			add_action( 'wp_ajax_tutor_assignment_details', array( $this, 'ajax_assignment_details' ) );
			add_action( 'wp_ajax_tutor_start_assignment', array( $this, 'tutor_start_assignment' ) );// Handle assignment submit form.
			add_action( 'wp_ajax_delete_tutor_course_assignment_submission', array( $this, 'delete_tutor_course_assignment_submission' ) );
			add_action( 'wp_ajax_tutor_remove_assignment_attachment', array( $this, 'remove_assignment_attachment' ) );
			add_action( 'wp_ajax_tutor_assignment_submit', array( $this, 'ajax_assignment_submit' ) );
			add_action( 'wp_ajax_tutor_remove_assignment_attempt', array( $this, 'ajax_remove_assignment_attempt' ) );

			add_action( 'tutor_action_tutor_assignment_submit', array( $this, 'tutor_assignment_submit' ) );
			add_action( 'tutor_action_tutor_evaluate_assignment_submission', array( $this, 'tutor_evaluate_assignment_submission' ) );
			add_action( 'delete_tutor_course_progress', array( $this, 'delete_tutor_course_progress' ), 10, 2 );
			add_action( 'tutor_assignment/evaluate/after', array( $this, 'do_auto_course_complete' ), 10, 3 );
			add_action( 'tutor/assignment/right_icon_area', array( $this, 'show_assignment_submitted_icon' ), 10, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ) );
			add_filter( 'tutor_localize_data', array( $this, 'localize_assignment_data' ) );

			// Add assignment title as nav item & render single content on the learning area.
			add_action( "tutor_learning_area_nav_item_{$this->post_type}", array( $this, 'render_nav_item' ), 10, 2 );
			add_action( "tutor_single_content_{$this->post_type}", array( $this, 'render_single_content' ) );

			// Legacy support.
			add_filter( 'tutor_single_content_template', array( $this, 'load_assignment_template' ), 99, 2 );
			add_filter( 'tutor_localize_data', array( $this, 'add_assignment_slug' ) );
		}
	}

	/**
	 * Add assignment data in tutorObj.
	 *
	 * @since 4.0.0
	 *
	 * @param array $data the default localized data.
	 *
	 * @return array
	 */
	public function localize_assignment_data( $data ) {

		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );

		if ( tutor()->assignment_post_type !== $post_type ) {
			return $data;
		}

		$data['assignment_max_file_allowed']   = (int) tutor_utils()->get_assignment_option( $post_id, 'upload_files_limit', 0 );
		$data['assignment_allowed_file_types'] = array(
			...wp_get_ext_types()['image'],
			'pdf',
			'doc',
			'docx',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'txt',
		);

		return $data;
	}

	/**
	 * Check whether to show assignment expiration message.
	 *
	 * @since 3.8.2
	 *
	 * @param boolean $is_expired If assignment is expired.
	 * @param boolean $is_submitting If assignment is submitting.
	 * @param array   $submitted_assignment the assignment submission.
	 *
	 * @return boolean
	 */
	public static function show_expiration_message( bool $is_expired = false, bool $is_submitting = false, array $submitted_assignment = array() ) {
		return $is_expired && ! $is_submitting && ( tutor_utils()->count( $submitted_assignment ) ? 1 >= count( $submitted_assignment ) : true );
	}

	/**
	 * Check if resubmission is allowed for assignment
	 *
	 * @since 3.8.2
	 *
	 * @param integer $assignment_id the assignment id.
	 * @param integer $user_id the user id.
	 * @param array   $submitted_assignment the assignment submission.
	 *
	 * @return boolean
	 */
	public static function is_resubmission_allowed(
		int $assignment_id = 0,
		int $user_id = 0,
		array $submitted_assignment = array()
	) {
		$is_reviewed_by_instructor = false;
		if ( $submitted_assignment ) {
			$is_reviewed_by_instructor = get_comment_meta( $submitted_assignment[0]->comment_ID, 'evaluate_time', true );
		}

		$assignment_attempt = self::get_assignment_attempt( $assignment_id, $user_id );
		$pass_mark          = number_format( floatval( tutor_utils()->get_assignment_option( get_the_ID(), 'pass_mark' ) ), 2 );
		$earned_marks       = number_format( floatval( $assignment_attempt->earned_marks ?? 0 ), 2 ) ?? 0;

		$is_retry_allowed = (int) tutor_utils()->get_assignment_option( $assignment_id, 'is_retry_allowed', 1 );
		$attempts_allowed = (int) tutor_utils()->get_assignment_option( $assignment_id, 'attempts_allowed', 5 );
		return 0 < $attempts_allowed && $is_retry_allowed && $earned_marks < $pass_mark && $is_reviewed_by_instructor;
	}

	/**
	 * Load assignment content template
	 *
	 * @since 3.8.2
	 *
	 * @param string $template the template content to show.
	 *
	 * @return string
	 */
	public function load_content_template( $template ) {
		ob_start();

		tutor_load_template_from_custom_path( $this->assignment_path . 'templates/content.php', array( 'assignment_path' => $this->assignment_path ) );
		$template = ob_get_clean();

		return $template;
	}

	/**
	 * Extend settings options.
	 *
	 * @since 2.6.0
	 *
	 * @param array $attr settings options.
	 *
	 * @return array
	 */
	public function extend_settings_option( $attr ) {
		$assignment_url = site_url() . '/' . $this->course_base_permalink . '/sample-course/<code>' . $this->assignment_base_permalink . '</code>/sample-assignment/';

		$video_option = array_pop( $attr['course']['blocks'] );

		$attr['advanced']['blocks'][2]['fields'][] = array(
			'key'     => 'assignment_permalink_base',
			'type'    => 'text',
			'label'   => __( 'Assignment Permalink', 'tutor-pro' ),
			'default' => 'assignments',
			'desc'    => $assignment_url,
		);

		$attr['course']['blocks']['block_assignment'] = array(
			'label'      => __( 'Assignment', 'tutor-pro' ),
			'slug'       => 'assignment',
			'block_type' => 'uniform',
			'fields'     => array(
				array(
					'key'     => 'assignment_grade_method',
					'type'    => 'radio_horizontal_full',
					'label'   => __( 'Final Grade Calculation', 'tutor-pro' ),
					'desc'    => __( 'When multiple attempts are allowed, select which method should be used to calculate a student\'s final grade for the assignment.', 'tutor-pro' ),
					'default' => 'assignment_highest_grade',
					'options' => array(
						'assignment_highest_grade' => __( 'Highest Grade', 'tutor-pro' ),
						'assignment_average_grade' => __( 'Average Grade', 'tutor-pro' ),
						'assignment_first_attempt' => __( 'First Attempt', 'tutor-pro' ),
						'assignment_last_attempt'  => __( 'Last Attempt', 'tutor-pro' ),
					),
				),
			),
		);

		$attr['course']['blocks'][] = $video_option;

		return $attr;
	}

	/**
	 * Load scripts
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_admin_scripts() {
		if ( 'tutor-assignments' === Input::get( 'page' ) ) {
			// @since 1.8.0
			wp_enqueue_style( 'assignments-css', TUTOR_ASSIGNMENTS()->url . 'assets/css/assignments.css', array(), TUTOR_PRO_VERSION );
		}
	}

	/**
	 * Load frontend scripts
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function load_frontend_scripts() {
		if ( tutor_utils()->is_dashboard_page( 'assignments' ) ) {
			wp_enqueue_style( 'tutor-dashboard-assignments', TUTOR_ASSIGNMENTS()->url . 'assets/css/dashboard-assignments.css', array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-dashboard-assignments', TUTOR_ASSIGNMENTS()->url . 'assets/js/dashboard-assignments.js', array( 'tutor-core' ), TUTOR_PRO_VERSION, true );
		}

		$current_post_type  = get_post_type();
		$is_legacy_learning = Options_V2::LEARNING_MODE_LEGACY === tutor_utils()->get_option( 'learning_mode' );
		if ( tutor_utils()->is_learning_area() && tutor()->assignment_post_type === $current_post_type && ! $is_legacy_learning ) {
			wp_enqueue_style( 'tutor-assignment-submission', TUTOR_ASSIGNMENTS()->url . 'assets/css/_assignment.css', array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-assignment-submission', TUTOR_ASSIGNMENTS()->url . 'assets/js/assignment-submission.js', array( 'tutor-core' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Remove assignment attempt for student.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function ajax_remove_assignment_attempt() {
		tutor_utils()->checking_nonce();

		$comment_id    = Input::post( 'comment_id', 0, Input::TYPE_INT );
		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );

		if ( ! $comment_id || ! $assignment_id ) {
			$this->response_bad_request( __( 'Invalid data', 'tutor-pro' ) );
		}

		$course_id = $assignment_id ? tutor_utils()->get_course_id_by( 'assignment', $assignment_id ) : 0;

		if ( $comment_id && $course_id ) {
			if ( ! EnrollmentModel::is_enrolled( $course_id, get_current_user_id() ) ) {
				$this->response_bad_request( __( 'Access Denied', 'tutor-pro' ) );
			}

			$this->delete_assignment_submission( $comment_id );
			$this->json_response( __( 'Assignment attempt removed', 'tutor-pro' ), get_permalink( $assignment_id ) );
		}
	}

	/**
	 * Delete single assignment ajax callback
	 */
	public function delete_tutor_course_assignment_submission() {

		// Check the request authenticity.
		tutor_utils()->checking_nonce();

		// All good, let's proceed.
		$submitted_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );

		if ( $submitted_id ) {
			if ( ! tutor_utils()->can_user_manage( 'assignment_submission', $submitted_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Access Denied', 'tutor-pro' ) ) );
				return;
			}

			$this->delete_assignment_submission( $submitted_id );
			wp_send_json_success( array( 'message' => __( 'Assignment deleted', 'tutor-pro' ) ) );
		}
	}

	/**
	 * Add sub-menu.
	 *
	 * @since 3.8.0
	 *
	 * @param array $menu menu.
	 *
	 * @return array
	 */
	public function register_menu( $menu ) {
		$menu['group_two']['assignments'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'Assignments', 'tutor-pro' ),
			'menu_title'  => __( 'Assignments', 'tutor-pro' ),
			'capability'  => 'manage_tutor_instructor',
			'menu_slug'   => 'tutor-assignments',
			'callback'    => array( $this, 'tutor_assignments_page' ),
		);

		return $menu;
	}

	/**
	 * Frontend dashboard nav item for assignments.
	 *
	 * @param array $nav_items nav items.
	 *
	 * @return array
	 */
	public function frontend_dashboard_nav_items( $nav_items ) {
		$new_items = array();

		foreach ( $nav_items as $key => $item ) {
			$new_items[ $key ] = $item;

			// Insert after quiz attempts.
			if ( 'quiz-attempts' === $key ) {
				$new_items['assignments'] = array(
					'title'       => __( 'Assignments', 'tutor-pro' ),
					'icon'        => Icon::BOOK_2,
					'active_icon' => Icon::BOOK_2_FILL,
					'auth_cap'    => tutor()->instructor_role,
				);
			}
		}

		return $new_items;
	}

	/**
	 * Load assignments template for dashboard
	 *
	 * @since 4.0.0
	 *
	 * @param string $template Current template path.
	 *
	 * @return string
	 */
	public function load_dashboard_template( $template ) {
		$base_path = TUTOR_ASSIGNMENTS()->path . 'templates/dashboard/';

		// Note: The map order is important.
		$map = array(
			'assignments/review' => 'submission-review.php',
			'assignments'        => 'assignments.php',
		);

		foreach ( $map as $page => $file_name ) {
			if ( tutor_utils()->is_dashboard_page( $page ) ) {
				$file = $base_path . $file_name;
				return file_exists( $file ) ? $file : $template;
			}
		}

		return $template;
	}

	/**
	 * Assignment page.
	 *
	 * @return void
	 */
	public function tutor_assignments_page() {
		$view_assignment = Input::get( 'view_assignment', 0, Input::TYPE_INT );
		if ( $view_assignment ) {
			$assignment_submitted_id = $view_assignment;
			include TUTOR_ASSIGNMENTS()->path . '/views/pages/submitted_assignment.php';
		} else {
			include TUTOR_ASSIGNMENTS()->path . '/views/pages/assignments.php';
		}
	}

	/**
	 * Create or update assignment
	 *
	 * @return void
	 */
	public function ajax_assignment_save() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$user_id       = get_current_user_id();
		$topic_id      = Input::post( 'topic_id', 0, Input::TYPE_INT );
		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );

		$assignment_action = 0 === $assignment_id ? 'create' : 'update';

		$course_id = tutor_utils()->get_course_id_by( 'topic', $topic_id );

		if ( ! tutor_utils()->can_user_edit_course( $user_id, $course_id ) ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_FORBIDDEN
			);
		}

		$title             = Input::post( 'title', '' );
		$lesson_content    = Input::post( 'summary', '', Input::TYPE_KSES_POST );
		$assignment_option = tutor_utils()->avalue_dot( 'assignment_option', $_POST ); //phpcs:ignore
		$attachments       = tutor_utils()->avalue_dot( 'attachments', $_POST ); //phpcs:ignore

		if ( isset( $assignment_option['time_duration'], $assignment_option['time_duration']['value'] ) ) {
			$assignment_option['time_duration']['value'] = (int) $assignment_option['time_duration']['value'];
		}

		$assignment_data = array(
			'post_type'    => 'tutor_assignments',
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_parent'  => $topic_id,
			'post_title'   => wp_slash( $title ), // Note: Added wp_slash to support latex syntaxes.
			'post_name'    => sanitize_title( $title ),
			'post_content' => wp_slash( $lesson_content ),
			'menu_order'   => tutor_utils()->get_next_course_content_order_id( $topic_id, $assignment_id ),
		);

		// @since v2.0.0
		$total_mark = isset( $assignment_option['total_mark'] ) ? sanitize_text_field( $assignment_option['total_mark'] ) : 0;
		$pass_mark  = isset( $assignment_option['pass_mark'] ) ? sanitize_text_field( $assignment_option['pass_mark'] ) : 0;

		// Create or update assignment post.
		if ( 'create' === $assignment_action ) {
			$assignment_id = wp_insert_post( $assignment_data );

			if ( is_wp_error( $assignment_id ) ) {
				$this->json_response(
					$assignment_id->get_error_message(),
					null,
					HttpHelper::STATUS_INTERNAL_SERVER_ERROR
				);
			}

			update_post_meta( $assignment_id, '_tutor_course_id_for_assignments', $course_id );
		} else {
			if ( ! tutor_utils()->can_user_manage( 'assignment', $assignment_id ) ) {
				wp_send_json_error( tutor_utils()->error_message() );
			}
			$assignment_data['ID'] = $assignment_id;
			wp_update_post( $assignment_data );
		}

		// Update assignment options as meta.
		update_post_meta( $assignment_id, 'assignment_option', $assignment_option );

		// Update assignment total_mark & pass_mark meta @since v2.0.0.
		update_post_meta( $assignment_id, '_tutor_assignment_total_mark', $total_mark );
		update_post_meta( $assignment_id, '_tutor_assignment_pass_mark', $pass_mark );

		// Update assignment attachments.
		if ( tutor_utils()->count( $attachments ) ) {
			update_post_meta( $assignment_id, '_tutor_assignment_attachments', $attachments );
		} else {
			delete_post_meta( $assignment_id, '_tutor_assignment_attachments' );
		}

		// Run the create/update hook.
		$hook_name = 'create' === $assignment_action ? 'tutor_assignment_created' : 'tutor_assignment_updated';
		do_action( $hook_name, $assignment_id );

		if ( 'create' === $assignment_action ) {
			$this->json_response(
				__( 'Assignment created successfully', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_CREATED
			);
		} else {
			$this->json_response(
				__( 'Assignment updated successfully', 'tutor-pro' )
			);
		}
	}

	/**
	 * Get assignment details by ID
	 *
	 * @since 3.7.0
	 *
	 * @param int $assignment_id assignment id.
	 *
	 * @return mixed
	 */
	public static function get_assignment_details( $assignment_id ) {
		$post = get_post( $assignment_id, ARRAY_A );
		if ( $post ) {
			$post['attachments']       = tutor_utils()->get_attachments( $assignment_id, '_tutor_assignment_attachments' );
			$post['assignment_option'] = tutor_utils()->get_assignment_option( $assignment_id );

			$is_retry_allowed = $post['assignment_option']['is_retry_allowed'] ?? '1';
			$attempts_allowed = $post['assignment_option']['attempts_allowed'] ?? '5';

			$post['assignment_option']['is_retry_allowed'] = $is_retry_allowed;
			$post['assignment_option']['attempts_allowed'] = $attempts_allowed;
		} else {
			$post = array();
		}

		$data = apply_filters( 'tutor_assignment_details_response', $post, $assignment_id );

		return $data;
	}
	/**
	 * Get assignment details
	 *
	 * @return void
	 */
	public function ajax_assignment_details() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->response_bad_request( tutor_utils()->error_message( 'nonce' ) );
		}

		$topic_id      = Input::post( 'topic_id', 0, Input::TYPE_INT );
		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_manage( 'topic', $topic_id ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		$data = self::get_assignment_details( $assignment_id );

		$this->json_response(
			__( 'Assignment data fetched successfully', 'tutor-pro' ),
			$data
		);
	}

	/**
	 * Course contents post types
	 *
	 * @param array $post_types post types.
	 *
	 * @return array
	 */
	public function tutor_course_contents_post_types( $post_types ) {
		$post_types[] = 'tutor_assignments';

		return $post_types;
	}

	/**
	 * Change Assignment single URL
	 *
	 * @since 1.3.3
	 *
	 * @param string  $post_link post link.
	 * @param integer $id id.
	 *
	 * @return string
	 */
	public function change_assignment_single_url( $post_link, $id = 0 ) {
		$post = get_post( $id );

		if ( is_object( $post ) && 'tutor_assignments' === $post->post_type ) {
			$course_id = tutor_utils()->get_course_id_by( 'assignment', $post->ID );
			$course    = get_post( $course_id );

			if ( is_object( $course ) ) {
				return home_url( "/{$this->course_base_permalink}/{$course->post_name}/{$this->assignment_base_permalink}/" . $post->post_name . '/' );
			} else {
				return home_url( "/{$this->course_base_permalink}/sample-course/{$this->assignment_base_permalink}/" . $post->post_name . '/' );
			}
		}

		return $post_link;
	}

	/**
	 * Start assignment.
	 *
	 * @return void
	 */
	public function tutor_start_assignment() {
		tutor_utils()->checking_nonce();
		global $wpdb;

		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );
		$course_id     = tutor_utils()->get_course_id_by( 'assignment', $assignment_id );
		$user_id       = get_current_user_id();
		$user          = get_userdata( $user_id );
		$gmdate        = gmdate( 'Y-m-d H:i:s' );
		$site_date     = wp_date( 'Y-m-d H:i:s' );

		$is_enrolled = EnrollmentModel::is_enrolled( $course_id, $user_id );
		if ( ! $is_enrolled ) {
			wp_send_json_error( tutor_utils()->error_message() );
		}

		$is_retry_allowed    = (int) tutor_utils()->get_assignment_option( $assignment_id, 'is_retry_allowed', 1 );
		$attempts_allowed    = (int) tutor_utils()->get_assignment_option( $assignment_id, 'attempts_allowed', 5 );
		$assignment_attempts = (int) QueryHelper::get_count(
			$wpdb->comments,
			array(
				'comment_post_ID' => $assignment_id,
				'user_id'         => $user_id,
				'comment_type'    => self::SUBMISSION_COMMENT_TYPE,
			),
			array(),
			'comment_ID'
		);

		if ( $is_retry_allowed && $assignment_attempts > $attempts_allowed ) {
			wp_send_json_error( __( 'Maximum assignment submission attempt limit reached', 'tutor-pro' ) );
		}

		if ( ! $is_retry_allowed && $assignment_attempts ) {
			wp_send_json_error( __( 'An assignment is submitting currently, please submit previous one first', 'tutor-pro' ) );
		}

		do_action( 'tutor_before_assignment_submit_start' );
		$data = apply_filters(
			'tutor_assignment_start_submitting_data',
			array(
				'comment_post_ID'  => $assignment_id,
				'comment_author'   => $user->user_login,
				'comment_date'     => $site_date, // Submit Finished.
				'comment_date_gmt' => $gmdate, // Submit Started.
				'comment_approved' => 'submitting', // submitting, submitted.
				'comment_agent'    => 'TutorLMSPlugin',
				'comment_type'     => self::SUBMISSION_COMMENT_TYPE,
				'comment_parent'   => $course_id,
				'user_id'          => $user_id,
			)
		);

		$insert     = (int) $wpdb->insert( $wpdb->comments, $data );
		$comment_id = 0;
		if ( $insert ) {
			$comment_id = $wpdb->insert_id;
		}

		do_action( 'tutor_after_assignment_submit_start', $comment_id );

		$is_legacy_learning = Options_V2::LEARNING_MODE_LEGACY === tutor_utils()->get_option( 'learning_mode' );
		if ( ! $is_legacy_learning ) {
			$query_params = array(
				'action' => self::ACTION,
				'id'     => $comment_id,
			);
			wp_send_json_success( add_query_arg( $query_params, get_permalink( $assignment_id ) ) );
		}

		wp_send_json_success( __( 'Answer has been added successfully', 'tutor-pro' ) );
	}

	/**
	 * Check if the user can update the assignment
	 *
	 * @since 3.9.0
	 *
	 * @param int $assignment_id assignment id.
	 * @param int $user_id user id optional, default is current user id.
	 *
	 * @return boolean
	 */
	public static function can_update_assignment( $assignment_id, $user_id = 0 ) {
		$user_id = tutor_utils()->get_user_id( $user_id );
		$content = get_comment( $assignment_id );
		return $content && self::SUBMISSION_COMMENT_TYPE === $content->comment_type && $user_id === (int) $content->user_id;
	}

	/**
	 * Submit assignment.
	 *
	 * @return void
	 */
	public function tutor_assignment_submit() {
		tutor_utils()->checking_nonce();

		$update_id     = Input::get( 'update-assignment', 0, Input::TYPE_INT );
		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );
		if ( $update_id ) {
			$can_update = self::can_update_assignment( $update_id );
			if ( ! $can_update ) {
				tutor_utils()->redirect_to( get_permalink( $assignment_id ), tutor_utils()->error_message(), 'error' );
			}
		}

		$store_data                       = new \stdClass();
		$store_data->update_id            = $update_id;
		$store_data->assignment_id        = $assignment_id;
		$store_data->assignment_answer    = Input::post( 'tutor-global-text-editor', '', Input::TYPE_KSES_POST );
		$store_data->allowed_upload_files = (int) tutor_utils()->get_assignment_option( $store_data->assignment_id, 'upload_files_limit' );
		$store_data->assignment_submit_id = tutor_utils()->is_assignment_submitting( $store_data->assignment_id );
		$store_data->course_id            = tutor_utils()->get_course_id_by( 'assignment', $store_data->assignment_id );
		$store_data->student_id           = get_current_user_id();

		$assignment_id = $this->update_assignment_submit( $store_data );

		wp_safe_redirect( get_permalink( $assignment_id ) );
		exit;
	}

	/**
	 * Ajax call for assignment submission.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function ajax_assignment_submit() {
		tutor_utils()->checking_nonce();

		$update_id     = Input::post( 'update-assignment', 0, Input::TYPE_INT );
		$assignment_id = Input::post( 'assignment_id', 0, Input::TYPE_INT );
		$course_id     = tutor_utils()->get_course_id_by( 'assignment', $assignment_id );

		if ( ! EnrollmentModel::is_enrolled( $course_id, get_current_user_id() ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		if ( $update_id ) {
			$can_update = self::can_update_assignment( $update_id );
			if ( ! $can_update ) {
				$this->response_bad_request( tutor_utils()->error_message() );
			}
		}

		$store_data                       = new \stdClass();
		$store_data->update_id            = $update_id;
		$store_data->assignment_id        = $assignment_id;
		$store_data->assignment_answer    = Input::post( 'assignment_content', '', Input::TYPE_KSES_POST );
		$store_data->allowed_upload_files = (int) tutor_utils()->get_assignment_option( $store_data->assignment_id, 'upload_files_limit' );
		$store_data->assignment_submit_id = tutor_utils()->is_assignment_submitting( $store_data->assignment_id );
		$store_data->course_id            = tutor_utils()->get_course_id_by( 'assignment', $store_data->assignment_id );
		$store_data->student_id           = get_current_user_id();

		$assignment_id = $this->update_assignment_submit( $store_data );

		$this->json_response( __( 'Assignment Submitted Successfully', 'tutor-pro' ), get_permalink( $assignment_id ) );
	}

	/**
	 * Store the data to submit the assignment
	 *
	 * @since 2.6.2
	 *
	 * @param object $store_data date object.
	 *
	 * @return int
	 */
	public function insert_assignment_submit( $store_data ) {
		global $wpdb;

		$assignment_id        = $store_data->assignment_id;
		$assignment_answer    = $store_data->assignment_answer;
		$allowed_upload_files = $store_data->allowed_upload_files;
		$assignment_submit_id = $store_data->assignment_submit_id;
		$course_id            = $store_data->course_id;
		$student_id           = $store_data->student_id;

		$user = get_userdata( $student_id );

		if ( in_array( $assignment_answer, array( '', '<p>&nbsp;</p>', '<p><br data-mce-bogus="1"></p>' ), true ) ) {
			tutor_utils()->redirect_to( get_permalink( $assignment_id ), __( 'Assignment answer is required', 'tutor-pro' ), 'error' );
			exit;
		}

		do_action( 'tutor_assignment/before/submit', $assignment_submit_id );

		$date = gmdate( 'Y-m-d H:i:s' );

		$data_array = array(
			'comment_post_ID'  => $assignment_id,
			'comment_author'   => $user->user_login,
			'comment_date'     => wp_date( 'Y-m-d H:i:s' ), // Submit Finished.
			'comment_date_gmt' => $date, // Submit Started.
			'comment_agent'    => 'TutorLMSPlugin',
			'comment_type'     => self::SUBMISSION_COMMENT_TYPE,
			'comment_parent'   => $course_id,
			'user_id'          => $student_id,
			'comment_content'  => $assignment_answer,
			'comment_approved' => 'submitted', // submitting, submitted.
		);

		$data = apply_filters(
			'tutor_assignment_submit_updating_data',
			$data_array
		);

		$wpdb->insert(
			$wpdb->comments,
			$data
		);

		$assignment_submit_id = (int) $wpdb->insert_id;

		$upload_attachment = $this->handle_assignment_attachment_uploads( $assignment_id );

		if ( $allowed_upload_files && is_array( $upload_attachment ) && count( $upload_attachment ) ) {
			// Insert attachments.
			if ( tutor_utils()->count( $upload_attachment ) ) {
				update_comment_meta( $assignment_submit_id, 'uploaded_attachments', json_encode( $upload_attachment, JSON_UNESCAPED_UNICODE ) );
			}
		}

		if ( 0 !== $assignment_submit_id ) {
			do_action( 'tutor_assignment/after/submitted', $assignment_submit_id );
		}

		return $assignment_id;
	}

	/**
	 * Update the data to submit the assignment
	 *
	 * @since 2.6.2
	 *
	 * @param object $store_data date object.
	 *
	 * @return int
	 */
	public function update_assignment_submit( $store_data ) {
		global $wpdb;

		$update_id            = $store_data->update_id;
		$assignment_id        = $store_data->assignment_id;
		$assignment_answer    = $store_data->assignment_answer;
		$allowed_upload_files = $store_data->allowed_upload_files;
		$assignment_submit_id = $store_data->assignment_submit_id;

		if ( in_array( $assignment_answer, array( '', '<p>&nbsp;</p>', '<p><br data-mce-bogus="1"></p>' ), true ) ) {
			tutor_utils()->redirect_to( get_permalink( $assignment_id ), __( 'Assignment answer is required', 'tutor-pro' ), 'error' );
			exit;
		}

		if ( self::is_assignment_expired( $assignment_id, $store_data->student_id, $store_data->course_id ) ) {
			tutor_utils()->redirect_to( get_permalink( $assignment_id ), __( 'Assignment is expired', 'tutor-pro' ), 'error' );
			exit;
		}

		do_action( 'tutor_assignment/before/submit', $assignment_submit_id );

		$date = wp_date( 'Y-m-d H:i:s' );

		$data_array = array(
			'comment_content'  => $assignment_answer,
			'comment_date'     => $date, // Submit Finished.
			'comment_approved' => 'submitted', // submitting, submitted.
		);

		$data = apply_filters(
			'tutor_assignment_submit_updating_data',
			$data_array
		);

		$upload_attachment = $this->handle_assignment_attachment_uploads( $assignment_id );

		if ( $allowed_upload_files && is_array( $upload_attachment ) && count( $upload_attachment ) ) {
			if ( $update_id ) {
				// Update attachments.
				$existing_attachments = get_comment_meta( $update_id, 'uploaded_attachments', true );
				$existing_attachments = json_decode( $existing_attachments );

				if ( is_array( $existing_attachments ) && count( $existing_attachments ) ) {
					$attachments = $this->prepare_attachment( $existing_attachments, $upload_attachment );
				} else {
					$attachments = $upload_attachment;
				}
				update_comment_meta( $update_id, 'uploaded_attachments', json_encode( $attachments, JSON_UNESCAPED_UNICODE ) );
			} elseif ( tutor_utils()->count( $upload_attachment ) ) {
				update_comment_meta( $assignment_submit_id, 'uploaded_attachments', json_encode( $upload_attachment, JSON_UNESCAPED_UNICODE ) );
			}
		}

		$wpdb->update(
			$wpdb->comments,
			$data,
			array(
				'comment_ID' => $update_id ? $update_id : $assignment_submit_id,
			)
		);

		if ( 0 !== $assignment_submit_id ) {
			do_action( 'tutor_assignment/after/submitted', $assignment_submit_id );
		}

		return $assignment_id;
	}

	/**
	 * Handle file upload during assignment submit
	 *
	 * @since 1.0.0
	 *
	 * @param integer $assignment_id assignment id.
	 *
	 * @return array
	 */
	public function handle_assignment_attachment_uploads( $assignment_id = 0 ) {
		if ( ! $assignment_id ) {
			return array();
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$attached_files  = array();
		$allow_to_upload = (int) tutor_utils()->get_assignment_option( $assignment_id, 'upload_files_limit' );

		if ( ! empty( $_FILES['attached_assignment_files'] ) ) { //phpcs:ignore
			$files       = $_FILES['attached_assignment_files']; //phpcs:ignore
			$max_size_mb = (int) tutor_utils()->get_assignment_option( $assignment_id, 'upload_file_size_limit', 2 );

			foreach ( $files['name'] as $key  => $value ) {
				$file_size  = $files['size'][ $key ];
				$size_in_mb = round( $file_size / ( 1024 * 1024 ) );

				if ( $size_in_mb > $max_size_mb ) {
					exit(
						esc_html(
							sprintf(
							// translators: %d: Max file size allowed.
						__( 'Maximum attachment upload size allowed is %d MB', 'tutor-pro' ), $max_size_mb ) ) ); //phpcs:ignore
				}
			}

			foreach ( $files['name'] as $key => $value ) {
				if ( $files['name'][ $key ] ) {
					$file = array(
						'name'     => $files['name'][ $key ],
						'type'     => $files['type'][ $key ],
						'tmp_name' => $files['tmp_name'][ $key ],
						'error'    => $files['error'][ $key ],
						'size'     => $files['size'][ $key ],
					);

					$upload_overrides = array(
						'test_form' => false,
					);
					$movefile         = wp_handle_upload( $file, $upload_overrides );

					if ( $movefile && ! isset( $movefile['error'] ) ) {
						$file_path = $movefile['file'];
						unset( $movefile['file'] );
						$upload_dir = wp_get_upload_dir();

						$file_sub_path = str_replace( trailingslashit( $upload_dir['basedir'] ), '', $file_path );
						$file_name     = str_replace( trailingslashit( $upload_dir['path'] ), '', $file_path );

						$movefile['uploaded_path'] = $file_sub_path;
						$movefile['name']          = $file_name;

						$attached_files[] = $movefile;
					} else {
						/**
						 * Error generated by _wp_handle_upload()
						 *
						 * @see _wp_handle_upload() in wp-admin/includes/file.php
						 */
						echo $movefile['error']; //phpcs:ignore
					}
				}
			}
		}

		return $attached_files;
	}

	/**
	 * Evaluate assignment submission
	 */
	public function tutor_evaluate_assignment_submission() {
		try {
			tutor_utils()->checking_nonce();
			$date = gmdate( 'Y-m-d H:i:s' );

			do_action( 'tutor_assignment/evaluate/before' );

			// Get data from request.
			$submitted_id    = Input::post( 'assignment_submitted_id', 0, Input::TYPE_INT );
			$evaluate_fields = tutor_utils()->array_get( 'evaluate_assignment', $_POST ); //phpcs:ignore

			// Get assignment info.
			$submitted_assignment = tutor_utils()->get_assignment_submit_info( $submitted_id );
			$course_id            = $submitted_assignment->comment_parent;
			$student_id           = $submitted_assignment->user_id;
			$assignment_info      = get_post( $submitted_assignment->comment_post_ID );
			$total_mark           = (int) get_post_meta( $assignment_info->ID, '_tutor_assignment_total_mark', true );

			if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
				wp_send_json_error( tutor_utils()->error_message() );
			}

			foreach ( $evaluate_fields as $field_key => $field_value ) {
				if ( 'assignment_mark' === $field_key ) {
					$assignment_mark = (int) $field_value;
					if ( $assignment_mark > $total_mark ) {
						wp_send_json_error( __( 'Evaluation mark must be less than total mark', 'tutor-pro' ) );
					}

					if ( $assignment_mark < 0 ) {
						wp_send_json_error( __( 'Evaluation mark cannot be less than zero', 'tutor-pro' ) );
					}
				}

				update_comment_meta( $submitted_id, $field_key, $field_value );
			}

			update_comment_meta( $submitted_id, 'evaluate_time', $date );

			// Assignment mark meta update @since v2.0.0.
			$assignment_post_id = Input::post( 'assignment_post_id', 0, Input::TYPE_INT );
			$assignment_mark    = isset( $evaluate_fields['assignment_mark'] ) ? Input::sanitize( $evaluate_fields['assignment_mark'], 0, INPUT::TYPE_INT ) : 0;
			if ( $assignment_mark > $total_mark ) {
				wp_send_json_error( __( 'Evaluation mark must be less than total mark', 'tutor-pro' ) );
			}

			if ( $assignment_mark < 0 ) {
				wp_send_json_error( __( 'Evaluation mark cannot be less than zero', 'tutor-pro' ) );
			}
			update_post_meta( $assignment_post_id, '_tutor_assignment_evaluate_mark', $assignment_mark );

			do_action( 'tutor_assignment/evaluate/after', $submitted_id, $course_id, $student_id );

			wp_send_json_success( __( 'Assignment Evaluated', 'tutor-pro' ) );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	/**
	 * Do auto course complete after evaluate an assignment.
	 *
	 * @since 2.4.0
	 *
	 * @param int $submitted_id submission id.
	 * @param int $course_id course id.
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function do_auto_course_complete( $submitted_id, $course_id, $user_id ) {
		if ( CourseModel::can_autocomplete_course( $course_id, $user_id ) ) {
			CourseModel::mark_course_as_completed( $course_id, $user_id );
			Course::set_review_popup_data( $user_id, $course_id );
		}
	}

	/**
	 * Show check icon for assignment.
	 *
	 * @param object  $post post.
	 * @param boolean $lock_icon lock icon.
	 *
	 * @return void
	 */
	public function show_assignment_submitted_icon( $post, $lock_icon = false ) {
		if ( 'tutor_assignments' === $post->post_type ) {
			$is_submitted = tutor_utils()->is_assignment_submitted( $post->ID );

			if ( $is_submitted && 'submitted' === $is_submitted[0]->comment_approved ) {
				$result       = self::get_assignment_result( $post->ID, get_current_user_id() );
				$result_class = '';
				if ( 'pending' === $result ) {
					$result_class = 'tutor-check-pending';
				}
				if ( 'fail' === $result ) {
					$result_class = 'tutor-check-fail';
				}
				echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle " . esc_attr( $result_class ) . "' disabled='disabled' readonly='readonly' checked='checked' />";
			} elseif ( $lock_icon ) {
					echo '<i class="tutor-icon-lock-line tutor-fs-7 tutor-color-muted tutor-mr-4" aria-hidden="true"></i>';
			} else {
				echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle' disabled='disabled' readonly='readonly' />";
			}
		}
	}

	/**
	 * Delete assignment by ID
	 *
	 * @since 1.9.5
	 *
	 * @param int $submitted_id submitted id.
	 *
	 * @return void
	 */
	private function delete_assignment_submission( $submitted_id ) {

		// Delete assignment attachments.
		$root_dir    = wp_get_upload_dir()['basedir'];
		$attachments = get_comment_meta( $submitted_id, 'uploaded_attachments', true );
		$attachments = @json_decode( $attachments, true );
		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				if ( isset( $attachment['uploaded_path'] ) ) {
					$path = $root_dir . DIRECTORY_SEPARATOR . $attachment['uploaded_path'];
					file_exists( $path ) ? unlink( $path ) : 0;
				}
			}
		}

		// Delete assignment rows.
		global $wpdb;
		$wpdb->delete( $wpdb->comments, array( 'comment_ID' => $submitted_id ) );
		$wpdb->delete( $wpdb->commentmeta, array( 'comment_id' => $submitted_id ) );
	}

	/**
	 * Delete course progress.
	 *
	 * @param int $course_id course id.
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function delete_tutor_course_progress( $course_id, $user_id ) {
		global $wpdb;
		$submission_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID
				FROM {$wpdb->comments}
				WHERE comment_type=%s AND comment_parent=%d AND user_id=%d",
				self::SUBMISSION_COMMENT_TYPE,
				$course_id,
				$user_id
			)
		);

		// Loop through IDs and delete.
		foreach ( $submission_ids as $id ) {
			$this->delete_assignment_submission( $id );
		}
	}

	/**
	 * Provide attachment files after merge existing and new attachments
	 *
	 * @param array $existing_attachments | existing assignment attachments.
	 * @param array $upload_attachments | new uploaded attachments.
	 *
	 * @return array merged attachments files
	 *
	 * @since v2.0.0
	 */
	protected function prepare_attachment( array $existing_attachments, array $upload_attachments ): array {
			$merge_all_files = array_merge( $existing_attachments, $upload_attachments );
			return is_array( $merge_all_files ) ? $merge_all_files : array();
	}

	/**
	 * Delete attachment handle ajax request
	 *
	 * @return void
	 *
	 * @since v2.0.0
	 */
	public function remove_assignment_attachment(): void {
		tutor_utils()->checking_nonce();
		$assignment_comment_id = Input::post( 'assignment_comment_id' );
		$file_name             = Input::post( 'file_name' );

		$assignment = tutor_utils()->get_assignment_submit_info( $assignment_comment_id );

		if ( ! $assignment ) {
			$this->response_bad_request();
		}

		$course_id = $assignment->comment_parent;
		$user_id   = get_current_user_id();

		$is_enrolled = EnrollmentModel::is_enrolled( $course_id, $user_id );
		if ( ! $is_enrolled ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		$update = self::delete_attachment( $assignment_comment_id, $file_name );

		if ( $update ) {
			$this->response_success( __( 'Attachment deleted successfully', 'tutor-pro' ) );
		} else {
			$this->response_bad_request();
		}
	}

	/**
	 * Functionality for the delete attachment
	 *
	 * @param int    $assignment_comment_id comment id.
	 * @param string $file_name name of the file to delete.
	 *
	 * @return int|bool
	 */
	public static function delete_attachment( $assignment_comment_id, $file_name ) {
		$submitted_attachments = get_comment_meta( $assignment_comment_id, 'uploaded_attachments' );
		$updated_files         = array();
		$upload_dir            = wp_upload_dir();
		$file_path             = null;

		if ( is_array( $submitted_attachments ) && count( $submitted_attachments ) ) {
			foreach ( $submitted_attachments as $key => $attach ) {
				$attachments = json_decode( $attach );
				foreach ( $attachments as $attach ) {
					if ( $file_name == $attach->name ) {
						$file_path = trailingslashit( $upload_dir['basedir'] ) . $attach->uploaded_path ?? '';
						continue;
					}
					array_push( $updated_files, $attach );
				}
			}
		}

		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			unlink( $file_path );
		}

		return update_comment_meta( $assignment_comment_id, 'uploaded_attachments', json_encode( $updated_files ) );
	}

	/**
	 * Check weather assignment evaluated or not
	 *
	 * @param int $assignment_id | assignment id to check.
	 *
	 * @return int comment id on success 0 on failure
	 *
	 * @since v2.0.0
	 */
	public static function is_evaluated( int $assignment_id ): int {
		global $wpdb;
		$assignment_id = sanitize_text_field( $assignment_id );
		$id            = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT comment_ID
					FROM $wpdb->comments
					WHERE comment_post_ID = %d
						AND comment_type = %s
				",
				$assignment_id,
				self::SUBMISSION_COMMENT_TYPE
			)
		);
		$evaluate      = get_comment_meta( $id, 'evaluate_time', true );
		return $evaluate ? (int) $evaluate : 0;
	}

	/**
	 * Get assignment result.
	 *
	 * @since 2.4.0
	 *
	 * @param int $assignment_id assignment id.
	 * @param int $user_id user id.
	 *
	 * @return string pending, pass, fail.
	 */
	public static function get_assignment_result( $assignment_id, $user_id ) {
		$assignment_submissions = tutor_utils()->is_assignment_submitted( $assignment_id, $user_id );
		if ( ! tutor_utils()->count( $assignment_submissions ) ) {
			return 'fail';
		}

		$submit_id   = $assignment_submissions[0]->comment_ID ?? 0;
		$is_reviewed = get_comment_meta( $submit_id, 'evaluate_time', true );
		if ( ! $is_reviewed ) {
			return 'pending';
		}

		$pass_mark = intval( tutor_utils()->get_assignment_option( $assignment_id ? $assignment_id : 0, 'pass_mark' ) );

		// Make assignment pass if any one attempt is passed.
		foreach ( $assignment_submissions as $submission ) {
			$given_mark = (int) get_comment_meta( $submission->comment_ID, 'assignment_mark', true );
			if ( $given_mark >= $pass_mark ) {
				return 'pass';
			}
		}
		return 'fail';
	}

	/**
	 * Get total assignments count
	 *
	 * @since 3.6.0
	 * @since 3.7.1 Course ids param added
	 *
	 * @param array $course_ids Array of course ids.
	 *
	 * @return int
	 */
	public function get_total_assignment( array $course_ids = array() ) {
		global $wpdb;

		$assignment_type = tutor()->assignment_post_type;

		$primary_table = "{$wpdb->posts} AS a";
		$join_tables   = array(
			array(
				'type'  => 'INNER',
				'table' => "$wpdb->posts t",
				'on'    => 'a.post_parent=t.ID',
			),
			array(
				'type'  => 'INNER',
				'table' => "{$wpdb->posts} c",
				'on'    => 'c.ID=t.post_parent',
			),
		);

		$where = array(
			'a.post_type' => $assignment_type,
		);

		if ( count( $course_ids ) ) {
			$where['c.ID'] = array( 'IN', $course_ids );
		}

		$where['c.post_status'] = array(
			'IN',
			array(
				'publish',
				'future',
				'draft',
				'private',
				'pending',
			),
		);

		$search = array();

		try {
			$count = QueryHelper::get_joined_count(
				$primary_table,
				$join_tables,
				$where,
				$search,
				'a.ID'
			);
		} catch ( \Throwable $th ) {
			return 0;
		}

		return $count;
	}

	/**
	 * Get all assignments submitted by a user for a specific course.
	 *
	 * @since 3.8.1
	 *
	 * @param int $course_id The ID of the course.
	 *
	 * @return array Returns an array of assignment comment objects with meta, empty array if none found.
	 */
	public function get_assignments_by_course_id( $course_id ): array {
		global $wpdb;

		$where  = array(
			'comment_type'   => self::SUBMISSION_COMMENT_TYPE,
			'comment_parent' => $course_id,
		);
		$result = QueryHelper::get_all( $wpdb->comments, $where, 'comment_parent', -1 );

		if ( empty( $result ) ) {
			return array();
		}

		return array_map(
			function ( $item ) {
				$item->assignment_meta = get_comment_meta( $item->comment_ID );
				return $item;
			},
			$result
		);
	}


	/**
	 * Get assignment attempt.
	 *
	 * @since 3.8.2
	 *
	 * @param integer $assignment_id the assignment id.
	 * @param integer $user_id the user id.
	 *
	 * @return \stdClass|int
	 */
	public static function get_assignment_attempt( int $assignment_id = 0, int $user_id = 0 ) {
		$assignment_submission = tutor_utils()->is_assignment_submitted( $assignment_id, $user_id );

		if ( tutor_utils()->count( $assignment_submission ) ) {
			$submit_id        = $assignment_submission[0]->comment_ID ?? 0;
			$is_reviewed      = get_comment_meta( $submit_id, 'evaluate_time', true );
			$submission_count = (int) count( $assignment_submission );

			if ( ! $is_reviewed && 1 === $submission_count ) {
				return 0;
			}

			return self::get_assignment_earned_marks( $assignment_id, $user_id );
		}

		return 0;
	}

	/**
	 * Get assignment earned marks.
	 *
	 * @since 3.8.2
	 *
	 * @param int $assignment_id the assignment id.
	 * @param int $user_id the user id.
	 *
	 * @return \stdClass|int
	 */
	public static function get_assignment_earned_marks( int $assignment_id, int $user_id ) {
		$grade_method = get_tutor_option( 'assignment_grade_method', 'assignment_last_attempt' );

		$primary_tables = 'commentmeta as meta';

		$joining_tables = array(
			array(
				'type'  => 'INNER',
				'table' => 'comments as comments',
				'on'    => 'meta.comment_id = comments.comment_ID',
			),
			array(
				'type'  => 'INNER',
				'table' => 'posts as assignment',
				'on'    => 'comments.comment_post_ID = assignment.ID',
			),
		);

		$cache_key = '';
		$columns   = array( 'meta.meta_value as earned_marks' );
		$order_by  = 'meta.meta_id';
		$sort_by   = 'DESC';

		switch ( $grade_method ) {
			case 'assignment_highest_grade':
				$cache_key = "tutor_assignment_attempt_highest_grade_{$assignment_id}_{$user_id}";
				$columns   = array( 'MAX(CONVERT(meta.meta_value, DECIMAL(9,2))) as earned_marks' );
				break;
			case 'assignment_average_grade':
				$cache_key = "tutor_assignment_attempt_average_grade_{$assignment_id}_{$user_id}";
				$columns   = array( 'AVG(CONVERT(meta.meta_value, DECIMAL(9,2))) as earned_marks' );
				break;
			case 'assignment_first_attempt':
				$cache_key = "tutor_assignment_attempt_first_attempt_{$assignment_id}_{$user_id}";
				$sort_by   = 'ASC';
				break;
			case 'assignment_last_attempt':
				$cache_key = "tutor_assignment_attempt_last_attempt_{$assignment_id}_{$user_id}";
				break;
			default:
				return 0;
		}

		$result = TutorCache::get( $cache_key );
		if ( ! $result ) {
			$result = QueryHelper::get_joined_data(
				$primary_tables,
				$joining_tables,
				$columns,
				array(
					'meta.meta_key'            => 'assignment_mark',
					'comments.comment_post_ID' => $assignment_id,
					'comments.user_id'         => $user_id,
				),
				array(),
				$order_by,
				1,
				0,
				$sort_by,
				'OBJECT',
				true
			);

			TutorCache::set( $cache_key, $result );
		}
		return $result;
	}


	/**
	 * Convert assignment time.
	 *
	 * @since 3.8.2
	 *
	 * @param integer $seconds time in seconds.
	 *
	 * @return string
	 */
	public static function tutor_assignment_convert_seconds( int $seconds ) {
		$dt1 = new \DateTime( '@0' );
		$dt2 = new \DateTime( "@$seconds" );

		$diff    = $dt1->diff( $dt2 );
		$days    = $diff->days;
		$hours   = $diff->h;
		$minutes = $diff->i;

		return $days . ' ' . _n( 'Day', 'Days', $days, 'tutor-pro' ) . ', ' . $hours . ' ' . _n( 'Hour', 'Hours', $hours, 'tutor-pro' ) . ', ' . $minutes . ' ' . _n( 'Minute', 'Minutes', $minutes, 'tutor-pro' );
	}

	/**
	 * Get time map for duration/deadline labels (singular/plural).
	 *
	 * @since 4.0.0
	 *
	 * @param int $time_value Time value for _n() context.
	 *
	 * @return array<string, string>
	 */
	public static function get_time_map( int $time_value = 1 ): array {
		return array(
			'hours' => _n( 'Hour', 'Hours', $time_value, 'tutor-pro' ),
			'days'  => _n( 'Day', 'Days', $time_value, 'tutor-pro' ),
			'weeks' => _n( 'Week', 'Weeks', $time_value, 'tutor-pro' ),
		);
	}

	/**
	 * Get assignment deadline context (deadline_time, remaining_time, duration, etc.) for reuse.
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID.
	 * @param int $course_id     Course ID (0 to resolve from assignment).
	 *
	 * @return array{time_value: int, time_unit: string, deadline_time: int, remaining_time: int, deadline_from_start: bool, start_assignment_date: string|null, time_map: array}
	 */
	private static function get_assignment_deadline_context( int $assignment_id, int $user_id = 0, int $course_id = 0 ): array {
		$user_id   = $user_id ? $user_id : get_current_user_id();
		$course_id = $course_id ? $course_id : (int) tutor_utils()->get_course_id_by( 'assignment', $assignment_id );

		$time_duration = tutor_utils()->get_assignment_option(
			$assignment_id,
			'time_duration',
			array(
				'time'  => '',
				'value' => 0,
			)
		);
		if ( ! is_array( $time_duration ) ) {
			$time_duration = array();
		}

		$time_unit  = $time_duration['time'] ?? '';
		$time_value = isset( $time_duration['value'] ) ? (int) $time_duration['value'] : 0;
		$time_map   = self::get_time_map( $time_value );

		$deadline_from_start   = (bool) tutor_utils()->get_assignment_option( $assignment_id, 'deadline_from_start' );
		$assignment_comment    = tutor_utils()->get_single_comment_user_post_id( $assignment_id, $user_id );
		$start_assignment_date = null;
		if ( $assignment_comment && isset( $assignment_comment->comment_date_gmt ) ) {
			$start_assignment_date = $assignment_comment->comment_date_gmt;
		}

		$enrollment_time         = null;
		$assignment_created_time = strtotime( get_post_field( 'post_date_gmt', $assignment_id ) );
		if ( $course_id && $user_id ) {
			$enrolled_info = EnrollmentModel::is_enrolled( $course_id, $user_id );
			if ( is_object( $enrolled_info ) ) {
				$enrollment_time = apply_filters( 'tutor_content_drip_assignment_deadline', strtotime( $enrolled_info->post_date_gmt ), $course_id, $assignment_id );
			}
		}
		$enrollment_time = $enrollment_time ?? $assignment_created_time;

		$deadline_time = $enrollment_time < $assignment_created_time ? $assignment_created_time : $enrollment_time;
		if ( $deadline_from_start && $start_assignment_date ) {
			$deadline_time = strtotime( $start_assignment_date );
		}

		$time_duration_in_sec = 0;
		if ( $time_value && ! empty( $time_unit ) ) {
			switch ( $time_unit ) {
				case 'hours':
					$time_duration_in_sec = HOUR_IN_SECONDS;
					break;
				case 'days':
					$time_duration_in_sec = DAY_IN_SECONDS;
					break;
				case 'weeks':
					$time_duration_in_sec = WEEK_IN_SECONDS;
					break;
			}
		}
		$time_duration_in_sec = $time_duration_in_sec * $time_value;
		$remaining_time       = $deadline_time + $time_duration_in_sec;

		return array(
			'time_value'            => $time_value,
			'time_unit'             => $time_unit,
			'deadline_time'         => $deadline_time,
			'remaining_time'        => $remaining_time,
			'deadline_from_start'   => $deadline_from_start,
			'start_assignment_date' => $start_assignment_date,
			'time_map'              => $time_map,
		);
	}

	/**
	 * Get human-readable duration string from assignment time_duration option.
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 *
	 * @return string
	 */
	public static function get_assignment_duration_string( int $assignment_id ): string {
		$time_duration = tutor_utils()->get_assignment_option(
			$assignment_id,
			'time_duration',
			array(
				'time'  => '',
				'value' => 0,
			)
		);
		if ( ! is_array( $time_duration ) ) {
			$time_duration = array();
		}
		$time_unit  = $time_duration['time'] ?? '';
		$time_value = isset( $time_duration['value'] ) ? (int) $time_duration['value'] : 0;
		if ( ! $time_value || empty( $time_unit ) ) {
			return __( 'No limit', 'tutor-pro' );
		}
		$time_map = self::get_time_map( $time_value );
		return $time_value . ' ' . ( $time_map[ $time_unit ] ?? $time_unit );
	}

	/**
	 * Get assignment deadline display text (time left, "Expired", "No limit", etc.).
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID (0 = current user).
	 * @param int $course_id     Course ID (0 = resolve from assignment).
	 *
	 * @return array|string
	 */
	public static function get_deadline_display_string( int $assignment_id, int $user_id = 0, int $course_id = 0 ) {
		$ctx                   = self::get_assignment_deadline_context( $assignment_id, $user_id, $course_id );
		$time_value            = $ctx['time_value'];
		$time_unit             = $ctx['time_unit'];
		$time_map              = $ctx['time_map'];
		$remaining_time        = $ctx['remaining_time'];
		$deadline_from_start   = $ctx['deadline_from_start'];
		$start_assignment_date = $ctx['start_assignment_date'];
		$now                   = time();

		if ( ! $time_value ) {
			return __( 'N/A', 'tutor-pro' );
		}

		if ( $deadline_from_start && ! $start_assignment_date ) {
			return sprintf(
				/* translators: %1$s is the number value (e.g., 3), %2$s is the time unit (e.g., days). */
				__( '%1$s %2$s after you start the assignment', 'tutor-pro' ),
				$time_value,
				strtolower( $time_map[ $time_unit ] ?? $time_unit )
			);
		}

		if ( $now > $remaining_time ) {
			return __( 'Expired', 'tutor-pro' );
		}

		$deadline_date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $remaining_time );
		$remaining     = human_time_diff( $now, $remaining_time );
		return array( $deadline_date, $remaining );
	}

	/**
	 * Check if the assignment submission deadline has passed.
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID (0 = current user).
	 * @param int $course_id     Course ID (0 = resolve from assignment).
	 *
	 * @return bool True if deadline exists and has passed, false otherwise (no limit or not yet expired).
	 */
	public static function is_assignment_expired( int $assignment_id, int $user_id = 0, int $course_id = 0 ): bool {
		$ctx = self::get_assignment_deadline_context( $assignment_id, $user_id, $course_id );
		if ( ! $ctx['time_value'] || ( $ctx['deadline_from_start'] && ! $ctx['start_assignment_date'] ) ) {
			return false;
		}
		return time() > $ctx['remaining_time'];
	}

	/**
	 * Check if assignment is expired (alias for is_assignment_expired).
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID.
	 * @param int $course_id     Course ID.
	 *
	 * @return bool
	 */
	public static function is_expired( $assignment_id, $user_id = 0, $course_id = 0 ): bool {
		return self::is_assignment_expired( (int) $assignment_id, (int) $user_id, (int) $course_id );
	}

	/**
	 * Render assignment summary table (Total Marks, Passing Marks, Duration, Deadline).
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID (for deadline calculation).
	 *
	 * @return void
	 */
	public static function render_assignment_summary( int $assignment_id, int $user_id = 0 ): void {
		$user_id        = $user_id ? $user_id : get_current_user_id();
		$course_id      = (int) tutor_utils()->get_course_id_by( 'assignment', $assignment_id );
		$total_mark     = (int) tutor_utils()->get_assignment_option( $assignment_id, 'total_mark', 0 );
		$pass_mark      = (int) tutor_utils()->get_assignment_option( $assignment_id, 'pass_mark', 0 );
		$duration       = self::get_assignment_duration_string( $assignment_id );
		$deadline       = self::get_deadline_display_string( $assignment_id, $user_id, $course_id );
		$remaining_time = '';
		if ( is_array( $deadline ) ) {
			$remaining_time = $deadline[1] ?? '';
			$deadline       = $deadline[0] ?? '';
		}
		$remaining_time   = $remaining_time ? '<span class="tutor-text-subdued"> (' . esc_html( $remaining_time ) . __( ' remaining', 'tutor-pro' ) . ')</span>' : '';
		$is_retry_allowed = (int) tutor_utils()->get_assignment_option( $assignment_id, 'is_retry_allowed', 1 );
		$attempts_allowed = $is_retry_allowed ? (int) tutor_utils()->get_assignment_option( $assignment_id, 'attempts_allowed', 5 ) + 1 : 1;
		$earned_marks     = self::get_assignment_earned_marks( $assignment_id, $user_id );
		$earned_marks     = (float) is_object( $earned_marks ) ? self::get_assignment_earned_marks( $assignment_id, $user_id )->earned_marks ?? 0 : $earned_marks;

		$summary = array(
			array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::PRIME_CHECK_CIRCLE )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Total Marks', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( (string) $total_mark ) . '</span></div>',
					),
				),
			),
			array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::PASSED )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Passing Marks', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( (string) $pass_mark ) . '</span></div>',
					),
				),
			),
			array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::TARGET )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Total Attempts', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( (string) $attempts_allowed ) . '</span></div>',
					),
				),
			),
			array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::CLOCK )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Duration', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( $duration ) . '</span></div>',
					),
				),
			),
			array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::CALENDAR_2 )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Deadline', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( $deadline ) . $remaining_time . '</span></div>',
					),
				),
			),
		);

		if ( $earned_marks ) {
			$earned_marks_row = array(
				'columns' => array(
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">'
							. SvgIcon::make()->name( Icon::STAR )->size( 20 )->get()
							. '<span class="tutor-small tutor-text-secondary">' . esc_html__( 'Earned Marks', 'tutor-pro' ) . '</span></div>',
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center"><span class="tutor-small tutor-font-medium tutor-text-secondary">' . esc_html( (string) number_format( $earned_marks, 2 ) ) . '</span></div>',
					),
				),
			);
			array_splice( $summary, 2, 0, array( $earned_marks_row ) );
		}

		Table::make()->contents( $summary )->render();
	}

	/**
	 * Get assignment status label for overview (Not Started, In Progress, Pending, Passed, Failed).
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment post ID.
	 * @param int $user_id       User ID.
	 *
	 * @return array{label: string, class: string}
	 */
	public static function get_assignment_status_badge( int $assignment_id, int $user_id = 0 ): array {
		$user_id       = $user_id ? $user_id : get_current_user_id();
		$submitted     = tutor_utils()->is_assignment_submitted( $assignment_id, $user_id );
		$is_submitting = tutor_utils()->is_assignment_submitting( $assignment_id );
		if ( $is_submitting ) {
			return array(
				'label' => __( 'In Progress', 'tutor-pro' ),
				'class' => 'tutor-badge-info',
			);
		}

		if ( ! tutor_utils()->count( $submitted ) ) {
			return array(
				'label' => __( 'Not Started', 'tutor-pro' ),
				'class' => 'tutor-badge-secondary',
			);
		}

		$is_retry_allowed = (int) tutor_utils()->get_assignment_option( $assignment_id, 'is_retry_allowed', 1 );
		$is_expired       = self::is_expired( $assignment_id, $user_id );
		if ( $is_expired ) {
			return array(
				'label' => $is_retry_allowed ? __( 'Overdue', 'tutor-pro' ) : __( 'Expired', 'tutor-pro' ),
				'class' => 'tutor-badge-danger',
			);
		}

		$result = self::get_assignment_result( $assignment_id, $user_id );
		switch ( $result ) {
			case 'pass':
				return array(
					'label' => __( 'Passed', 'tutor-pro' ),
					'class' => 'tutor-badge-success',
				);
			case 'fail':
				return array(
					'label' => __( 'Failed', 'tutor-pro' ),
					'class' => 'tutor-badge-danger',
				);
			case 'pending':
			default:
				return array(
					'label' => __( 'Pending', 'tutor-pro' ),
					'class' => 'tutor-badge-warning',
				);
		}
	}

	/**
	 * Render assignment title as nav item to show on the learning area
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $assignment Quiz post object.
	 * @param bool    $can_access Can user access this content.
	 *
	 * @return void
	 */
	public function render_nav_item( WP_Post $assignment, bool $can_access ): void {
		include TUTOR_ASSIGNMENTS()->templates . 'learning-area/nav-item.php';
	}

	/**
	 * Render content for the a single assignment
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $assignment Assignment post object.
	 *
	 * @return void
	 */
	public function render_single_content( WP_Post $assignment ): void {
		include TUTOR_ASSIGNMENTS()->templates . 'learning-area/content.php';
	}

	/**
	 * Load assignment template
	 *
	 * @since 4.0.0
	 *
	 * @param string $template template file to load.
	 * @param string $post_type Post type.
	 *
	 * @return string template path
	 */
	public function load_assignment_template( $template, $post_type ) {
		if ( tutor()->assignment_post_type !== $post_type ) {
			return $template;
		}

		if ( is_user_logged_in() ) {
			$has_content_access = tutor_utils()->has_enrolled_content_access( 'assignment' );
			if ( $has_content_access ) {
				$template = tutor_get_template( 'single-assignment' );
			} else {
				$template = tutor_get_template( 'single.lesson.required-enroll' ); // You need to enroll first.
			}
		} else {
			$template = tutor_get_template( 'login' );
		}

		return $template;
	}

	/**
	 * Renders an assignment result badge (Passed, Failed, or Pending) based on evaluation status and marks.
	 *
	 * @since 4.0.0
	 *
	 * @param boolean $is_evaluated Whether the assignment has been evaluated.
	 * @param float   $pass_mark   The minimum score required to pass.
	 * @param float   $given_mark  The mark given to student.
	 *
	 * @return void
	 */
	public static function print_assignment_result_badge( bool $is_evaluated, float $pass_mark, float $given_mark ): void {
		$status  = 'pending';
		$variant = Badge::WARNING;

		if ( $is_evaluated ) {
			if ( $given_mark >= $pass_mark ) {
				$status  = 'passed';
				$variant = Badge::SUCCESS;
			} else {
				$status  = 'failed';
				$variant = Badge::ERROR;
			}
		}

		Badge::make()
			->label( ucfirst( $status ) )
			->variant( $variant )
			->rounded()
			->render();
	}

	/**
	 * Add assignment slug to localized data array for front-end use.
	 *
	 * @since 4.0.0
	 *
	 * @param array $data Localized data array to be extended.
	 *
	 * @return array Modified data array including assignment slug.
	 */
	public function add_assignment_slug( array $data ): array {
		$slug = tutor_utils()->get_option( 'assignment_permalink_base', 'assignments' );

		$data['assignment_slug'] = $slug;
		return $data;
	}

	/**
	 * Renders the assignment status icon for the learning area navigation.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $assignment The assignment post object.
	 * @param int     $course_id  The ID of the associated course.
	 * @param bool    $can_access Whether the current user can access the assignment.
	 * @param int     $tutor_current_content_id Current content id.
	 *
	 * @return void
	 */
	public static function render_sidebar_nav( WP_Post $assignment, $course_id, $can_access, $tutor_current_content_id ) {

		$user_id           = get_current_user_id();
		$is_submmited      = tutor_utils()->is_assignment_submitted( $assignment->ID, $user_id );
		$assignment_status = '';
		$is_expired        = self::is_expired( $assignment->ID, $user_id, $course_id );

		$icon_status_map = array(
			'pending' => Icon::INFO_COLORIZE,
			'pass'    => Icon::COMPLETED_COLORIZE,
			'fail'    => Icon::CROSS_COLORIZE,
		);

		$icon_name = Icon::BOOK_2;
		if ( ! $can_access ) {
			$icon_name = Icon::LOCK_STROKE_2;
		} elseif ( $is_submmited ) {
			$assignment_status = self::get_assignment_result( $assignment->ID, $user_id );
			$icon_name         = $icon_status_map[ $assignment_status ] ?? $icon_name;
		} elseif ( $is_expired ) {
			$icon_name         = Icon::CROSS_COLORIZE;
			$assignment_status = 'fail';
		}

		tutor_load_template(
			'learning-area.components.sidebar-nav-item',
			array(
				'item'         => $assignment,
				'active'       => $tutor_current_content_id === $assignment->ID,
				'can_access'   => $can_access,
				'is_completed' => false,
				'type_label'   => __( 'Assignment', 'tutor-pro' ),
				'icon'         => $icon_name,
				'status_class' => $assignment_status,
			)
		);
	}

	/**
	 * Determine whether a user has passed an assignment.
	 *
	 * @since 4.0.0
	 *
	 * @param int $assignment_id Assignment id.
	 * @param int $user_id User id.
	 *
	 * @return bool
	 */
	public static function is_assignment_passed( $assignment_id, $user_id ) {
		$result = self::get_assignment_result( $assignment_id, $user_id );

		return 'pass' === $result;
	}
}
