<?php
/**
 * Handle Course Preview Addon Logic
 *
 * @package TutorPro/Addons
 * @subpackage CoursePreview
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_CP;

use TUTOR\Input;
use TUTOR\Tutor_Base;

/**
 * Class CoursePreview
 */
class CoursePreview extends Tutor_Base {

	/**
	 * Register hooks
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'tutor_lesson_edit_modal_form_after', array( $this, 'additional_data_metabox_before' ), 10, 0 );
		add_action( 'save_post_' . $this->lesson_post_type, array( $this, 'save_lesson_meta' ) );

		add_filter( 'tutor_course/contents/lesson/title', array( $this, 'mark_lesson_title_preview' ), 10, 2 );

		add_filter( 'tutor_lesson_template', array( $this, 'tutor_lesson_template' ) );
		add_filter( 'tutor_video_stream_is_public', array( $this, 'video_stream_is_public' ), 10, 2 );
		add_filter( 'tutor_lesson_details_response', array( $this, 'extend_lesson_details_response' ) );
	}

	/**
	 * Save lesson meta.
	 *
	 * @param int $post_ID post id.
	 * @return void
	 */
	public function save_lesson_meta( $post_ID ) {
		$_is_preview = Input::post( '_is_preview' );
		if ( $_is_preview ) {
			update_post_meta( $post_ID, '_is_preview', 1 );
		} else {
			delete_post_meta( $post_ID, '_is_preview' );
		}
	}

	/**
	 * Mark lesson title as preview.
	 *
	 * @param string $title title.
	 * @param int    $post_id post id.
	 *
	 * @return mixed
	 */
	public function mark_lesson_title_preview( $title, $post_id ) {

		$user     = wp_get_current_user();
		$is_admin = in_array( 'administrator', $user->roles );

		$course_id  = tutor_utils()->get_course_id_by( 'lesson', $post_id );
		$is_preview = (bool) get_post_meta( $post_id, '_is_preview', true );

		if ( $is_preview || $is_admin || tutor_utils()->is_instructor_of_this_course( $user->ID, $course_id ) ) {
			$new_title = '<a href="' . get_the_permalink( $post_id ) . '"><span class="lesson-preview-title">' . $title . '</span></a>';
			return $new_title;
		}

		$modified_title = $title;

		return $modified_title;
	}

	/**
	 * Required login to view lesson.
	 *
	 * @param bool $bool bool.
	 * @param int  $post_id post id.
	 *
	 * @return bool
	 */
	public function required_login_to_view_lesson( $bool, $post_id ) {
		return ! (bool) get_post_meta( $post_id, '_is_preview', true );
	}

	/**
	 * Lesson template.
	 *
	 * @param string $template template.
	 *
	 * @return string
	 */
	public function tutor_lesson_template( $template ) {
		$is_course_enrolled = tutor_utils()->is_course_enrolled_by_lesson();

		if ( ! $is_course_enrolled ) {
			$is_preview = (bool) get_post_meta( get_the_ID(), '_is_preview', true );
			if ( $is_preview ) {
				$template = tutor_get_template( 'single-preview-lesson' );
			}
		}
		return $template;
	}

	/**
	 * View stream is public.
	 *
	 * @param bool $bool bool.
	 * @param int  $post_id post id.
	 * @return bool
	 */
	public function video_stream_is_public( $bool, $post_id ) {
		return (bool) get_post_meta( $post_id, '_is_preview', true );

	}

	/**
	 * Extend lesson details response.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data data.
	 *
	 * @return array
	 */
	public function extend_lesson_details_response( $data ) {
		$lesson_id = $data['ID'] ?? 0;
		if ( ! $lesson_id ) {
			return $data;
		}

		$is_preview         = (bool) get_post_meta( $lesson_id, '_is_preview', true );
		$data['is_preview'] = $is_preview;

		return $data;
	}

}
