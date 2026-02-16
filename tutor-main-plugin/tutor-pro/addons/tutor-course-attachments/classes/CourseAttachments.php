<?php
/**
 * Handle Course Attachments
 *
 * @package TutorPro/Addons
 * @subpackage CourseAttachment
 * @author: themeum
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_CA;

use TUTOR\Input;
use TUTOR\Tutor_Base;

/**
 * Class CourseAttachments
 */
class CourseAttachments extends Tutor_Base {

	/**
	 * Open mode.
	 *
	 * @var string
	 */
	private $open_mode = 'tutor_pro_attachment_open_type';

	/**
	 * Register hook
	 */
	public function __construct() {
		parent::__construct();

		add_filter( 'tutor_course/single/nav_items', array( $this, 'add_course_nav_item' ), 10, 2 );

		/**
		 * Listen only save_post will hook for every post type
		 * course / lesson / quizz etc
		 * removed save_post_courses hook to avoid redundancy
		 *
		 * @since 1.8.9
		*/
		add_action( 'save_post', array( $this, 'save_course_meta' ) );
		add_action( 'save_tutor_course', array( $this, 'save_course_meta' ) );

		add_filter( 'tutor/options/extend/attr', array( $this, 'add_option' ) );
		add_filter( 'tutor_pro_attachment_open_mode', array( $this, 'set_open_open_mode' ) );

		// @since 3.0.0
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
	}

	/**
	 * Set open mode.
	 *
	 * @return mixed
	 */
	public function set_open_open_mode() {
		return tutor_utils()->get_option( $this->open_mode );
	}

	/**
	 * Add option to tutor settings.
	 *
	 * @param array $attr attributes.
	 *
	 * @return array
	 */
	public function add_option( $attr ) {

		$attr['course']['blocks']['block_course']['fields'][] = array(
			'key'     => $this->open_mode,
			'type'    => 'radio_horizontal_full',
			'label'   => __( 'Attachment Open Mode', 'tutor-pro' ),
			'default' => 'download',
			'options' => array(
				'download' => __( 'Download', 'tutor-pro' ),
				'view'     => __( 'View in new tab', 'tutor-pro' ),
			),
			'desc'    => __( 'How you want users to view attached files.', 'tutor-pro' ),
		);

		return $attr;
	}

	/**
	 * Merge resources tab with course nav items
	 *
	 * @param array $items items.
	 * @param int   $course_id course id.
	 *
	 * @return array course nav items
	 */
	public function add_course_nav_item( $items, $course_id ) {
		/**
		 * Check settings if admin & instructor as course access and
		 * current user has permission to edit course then user should
		 * access course attachments without enrollment.
		 *
		 * @since v2.0.5
		 */
		$is_enabled           = tutor_utils()->get_option( 'course_content_access_for_ia' );
		$can_user_edit_course = tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id );
		$require_enrolment    = ! ( $is_enabled && $can_user_edit_course ); // Admin and instructor of the course can see resource tab.

		if ( is_single() && $course_id ) {
			$items['resources'] = array(
				'title'             => __( 'Resources', 'tutor-pro' ),
				'method'            => array( $this, 'load_resource_tab_content' ),
				'require_enrolment' => $require_enrolment,
			);
		}
		return $items;
	}

	/**
	 * Load resource tab content.
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function load_resource_tab_content( $course_id ) {
		get_tutor_posts_attachments();
	}

	/**
	 * Upload attachment only if $_POST[tutor_attachments]
	 * is not empty else delete
	 * it will remove empty data in db
	 *
	 * @since 1.8.9
	 *
	 * @param init $post_id post id.
	 */
	public function save_course_meta( $post_id ) {
		// Attachments.
		$attachments           = array();
		$attachments_main_edit = tutor_utils()->avalue_dot( '_tutor_attachments_main_edit', $_POST );//phpcs:ignore

		// Make sure it is post editor.
		if ( ! $attachments_main_edit ) {
			return;
		}

		// Get unique attachment ID. User might add single media multiple times.
		if ( Input::has( 'tutor_attachments' ) ) {
			$attachments = Input::post( 'tutor_attachments', array(), Input::TYPE_ARRAY );
			$attachments = array_filter( $attachments, 'is_numeric' );
			$attachments = array_unique( $attachments );
		}

		// Update assignment meta if at least one exist.
		// Otherwise delete the meta.
		if ( ! empty( $attachments ) ) {
			update_post_meta( $post_id, '_tutor_attachments', $attachments );
		} else {
			delete_post_meta( $post_id, '_tutor_attachments' );
		}
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( array $data ) {
		$course_id = $data['ID'] ?? 0;
		if ( ! $course_id ) {
			return $data;
		}

		$attachments = tutor_utils()->get_attachments( $course_id );

		$data['course_attachments'] = $attachments;

		return $data;
	}
}
