<?php
/**
 * Handle Course Duplicate
 *
 * @package TutorPro\Classes
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_PRO;

use TUTOR\Input;
use Tutor\Helpers\QueryHelper;
use Tutor\Models\CourseModel;
use TUTOR_PRO\Traits\QuizMaskDuplicator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course Duplicator
 */
class Course_Duplicator {
	use QuizMaskDuplicator;

	/**
	 * Question types that store instructor masks in answer_two_gap_match.
	 *
	 * @since 4.0.0
	 *
	 * @var string[]
	 */
	private const MASK_QUESTION_TYPES = array( 'draw_image', 'pin_image', 'puzzle' );
	/**
	 * Post columns
	 *
	 * @var array
	 */
	private $necessary_post_columns = array(
		'post_author',
		'post_content',
		'post_title',
		'post_excerpt',
		'post_status',
		'comment_status',
		'ping_status',
		'post_password',
		'post_name',
		'to_ping',
		'pinged',
		'post_content_filtered',
		'menu_order',
		'post_type',
		'post_mime_type',
	);

	/**
	 * Child types
	 *
	 * @var array
	 */
	private $necessary_child_types = array(
		'topics',
		'lesson',
		'tutor_quiz',
		// 'tutor_announcements', // Announcment is probably not necessary in duplicated course since there is no student yet in the new one
		'tutor_assignments',
	);

	/**
	 * Store duplicated IDs here to avoid accidental infinity recursion.
	 *
	 * @var array
	 */
	private $duplicated_post_ids = array();

	/**
	 * Map of source post ID => duplicated post ID.
	 *
	 * Used to rewrite internal references (content drip prerequisites,
	 * lesson/assignment course links) after the full course tree is copied.
	 *
	 * @since 4.1.0
	 *
	 * @var array<int,int>
	 */
	private $id_map = array();

	/**
	 * Register hooks
	 *
	 * @param boolean $register_hooks hook register or not.
	 */
	public function __construct( $register_hooks = true ) {
		if ( $register_hooks ) {
			add_action( 'wp_loaded', array( $this, 'init_duplicator' ) );
			add_filter( 'post_row_actions', array( $this, 'register_duplicate_button' ), 10, 2 );
			add_action( 'tutor_course_dashboard_actions_after', array( $this, 'duplicate_button_in_dashboard' ) );
		}
		add_action( 'tutor_admin_middle_course_list_action', array( $this, 'add_course_duplicate_menu' ) );
	}

	/**
	 * Build a nonce-protected duplicate URL.
	 *
	 * @since 4.1.0
	 *
	 * @param int  $course_id   Course ID.
	 * @param bool $is_wp_admin Whether the request originates from WP admin.
	 *
	 * @return string
	 */
	private function get_duplicate_url( $course_id, bool $is_wp_admin ): string {
		$url = add_query_arg(
			array(
				'tutor_action' => 'duplicate_course',
				'is_wp_admin'  => $is_wp_admin ? 'yes' : 'no',
				'course_id'    => (int) $course_id,
			)
		);

		return wp_nonce_url( $url, tutor()->nonce_action, tutor()->nonce );
	}

	/**
	 * Get duplicator HTML
	 *
	 * @param int     $course_id course id.
	 * @param boolean $is_wp_admin is wp admin.
	 * @param string  $class class.
	 *
	 * @return string
	 */
	private function get_duplicator_html( $course_id, bool $is_wp_admin, $class = '' ) {
		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $this->get_duplicate_url( $course_id, $is_wp_admin ) ) . '" aria-label="' . esc_attr__( 'Duplicate', 'tutor-pro' ) . '">
                ' . esc_html__( 'Duplicate', 'tutor-pro' ) . '
            </a>';
	}

	/**
	 * Get course edit link.
	 *
	 * @param int     $course_id course id.
	 * @param boolean $is_admin is admin or not.
	 *
	 * @return string
	 */
	private function get_course_edit_link( $course_id, bool $is_admin ) {
		return $is_admin ? get_edit_post_link( $course_id, null ) : tutor_utils()->get_tutor_dashboard_page_permalink( 'create-course/?course_ID=' . $course_id );
	}

	/**
	 * Duplicate button for dashboard.
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function duplicate_button_in_dashboard( $course_id ) {
		if ( ! tutor_utils()->can_user_manage( 'course', $course_id ) ) {
			return;
		}

		echo wp_kses_post( $this->get_duplicator_html( $course_id, false, 'tutor-mycourse-edit' ) );
	}

	/**
	 * Register duplicate button
	 *
	 * @param array  $actions action list.
	 * @param object $post post object.
	 *
	 * @return array
	 */
	public function register_duplicate_button( $actions, $post ) {
		if ( tutor()->course_post_type === $post->post_type && tutor_utils()->can_user_manage( 'course', $post->ID ) ) {
			$actions[] = $this->get_duplicator_html( $post->ID, true );
		}

		return $actions;
	}

	/**
	 * Handle Course duplicate for WP Admin and Frontend Dashboard
	 *
	 * Requires a valid Tutor nonce and course management permission.
	 *
	 * @since 2.0.0
	 * @since 4.1.0 Require nonce and can_user_manage ownership check.
	 *
	 * @return void
	 */
	public function init_duplicator() {
		$action = Input::get( 'tutor_action' );
		$id     = Input::get( 'course_id', 0, Input::TYPE_INT );
		if ( 'duplicate_course' !== $action || 0 === $id ) {
			return;
		}

		if ( ! tutor_utils()->is_nonce_verified( 'GET' ) ) {
			wp_die( esc_html( tutor_utils()->error_message( 'nonce' ) ) );
		}

		$course = get_post( $id );
		if ( ! $course || tutor()->course_post_type !== $course->post_type || ! tutor_utils()->can_user_manage( 'course', $id ) ) {
			wp_die( esc_html__( 'You are not allowed for this action.', 'tutor-pro' ) );
		}

		$new_post_id = $this->duplicate_post( $id );

		if ( $new_post_id ) {
			$is_wp_admin   = is_admin();
			$flash_message = __( 'Course Duplicated Successfully!', 'tutor-pro' );
			if ( $is_wp_admin ) {
				$link = admin_url( 'admin.php?page=tutor' );
			} else {
				$link = tutor_utils()->tutor_dashboard_url( 'my-courses/draft-courses' );
			}

			tutor_utils()->redirect_to( $link, $flash_message );
			exit;
		}

		wp_die( esc_html__( 'You are not allowed for this action.', 'tutor-pro' ) );
	}

	/**
	 * Duplicate post by using recursive mechanism
	 *
	 * @param int $post_id  post id that need to be duplicated.
	 * @param int $absolute_course_id optional.
	 * @param int $new_parent_id optional.
	 * @param int $new_id optional.
	 *
	 * @return int|false|void New post ID on success, false/void on failure.
	 */
	public function duplicate_post( $post_id, $absolute_course_id = null, $new_parent_id = 0, $new_id = null ) {

		if ( ! $post_id || ! is_numeric( $post_id ) ) {
			return;
		}

		$is_root_duplicate = ( 0 === (int) $new_parent_id && null === $absolute_course_id );

		if ( $is_root_duplicate ) {
			$this->id_map              = array();
			$this->duplicated_post_ids = array();
		}

		$post = get_post( $post_id );
		$post = is_object( $post ) ? (array) $post : null;

		if ( ! $post ) {
			// Return right from here.
			return false;
		}

		// Create new post using the old values.
		$post                = $this->strip_unnecessary_columns( $post );
		$post['post_author'] = get_current_user_id();
		$post['post_parent'] = $new_parent_id;
		$post['post_status'] = $new_parent_id > 0 ? 'publish' : 'draft';
		$post['post_name']   = tutor_utils()->get_unique_slug( sanitize_title( $post['post_name'], 'untitled-course' ) );
		! $new_id ? $new_id  = wp_insert_post( $post ) : 0;

		/**
		 * Add meta flag for duplicate number
		 * For ex: Copy 1 ,Copy 2, so that it can be identified how many
		 * times a course has been duplicated
		 *
		 * @since v2.0.0
		 */
		if ( $new_id ) {
			$this->id_map[ (int) $post_id ] = (int) $new_id;

			$has_duplicator_of_post = get_post_meta( $post_id, 'tutor-course-duplicate-' . $post_id, true );
			if ( $has_duplicator_of_post ) {
				update_post_meta( $post_id, 'tutor-course-duplicate-' . $post_id, (int) ++$has_duplicator_of_post );
			} else {
				update_post_meta( $post_id, 'tutor-course-duplicate-' . $post_id, 1 );
			}

			/**
			 * Show copy text only for course
			 *
			 * @since 2.1.7
			 */
			$copy_text = '';
			if ( tutor()->course_post_type === $post['post_type'] ) {
				$copy_text = ' (' . __( 'Copy ', 'tutor-pro' ) . get_post_meta( $post_id, 'tutor-course-duplicate-' . $post_id, 1 ) . ')';
			}

			self::update_course( array( 'ID' => $new_id ), array( 'post_title' => $post['post_title'] . $copy_text ) );
		}

		// Duplicate post meta.
		$this->duplicate_post_meta( $post_id, $new_id, $absolute_course_id );

		// Assign taxonomy.
		$this->assign_post_taxonomy( $post_id, $new_id, 'course-category' );
		$this->assign_post_taxonomy( $post_id, $new_id, 'course-tag' );

		// Duplicate quiz question if it is quiz post type.
		'tutor_quiz' === $post['post_type'] ? $this->duplicate_quiz_dependency( $post_id, $new_id, false ) : 0;

		// Set it as done.
		$this->duplicated_post_ids[] = (int) $post_id;

		// Now duplicate childs like topic, lesson, etc.
		$childs = $this->get_child_post_ids( $post_id );

		foreach ( $childs as $child_id ) {
			if ( in_array( (int) $child_id, $this->duplicated_post_ids, true ) ) {
				// Avoid accidental infinity recursion.
				continue;
			}

			$this->duplicate_post( $child_id, ( $absolute_course_id ? $absolute_course_id : $new_id ), $new_id );
		}

		/**
		 * After the full course tree is copied, rewrite internal IDs so the
		 * duplicate is self-contained (content drip prereqs, course links).
		 *
		 * @since 4.1.0
		 */
		if ( $is_root_duplicate && $new_id ) {
			$this->remap_internal_references( (int) $new_id );
			$this->copy_course_instructors( (int) $post_id, (int) $new_id );
		}

		return $new_id;
	}

	/**
	 * Attach source-course instructors to the duplicated course.
	 *
	 * Instructors are stored as user meta (`_tutor_instructor_course_id`),
	 * not post meta, so a plain meta copy never preserves co-instructors.
	 * The source author is included because the copy uses the current user
	 * as `post_author`.
	 *
	 * @since 4.1.0
	 *
	 * @param int $source_course_id Source course ID.
	 * @param int $new_course_id    Duplicated course ID.
	 *
	 * @return void
	 */
	private function copy_course_instructors( int $source_course_id, int $new_course_id ): void {
		if ( $source_course_id <= 0 || $new_course_id <= 0 ) {
			return;
		}

		$instructor_ids   = CourseModel::get_course_instructor_ids( $source_course_id );
		$source_author_id = (int) get_post_field( 'post_author', $source_course_id );
		$new_author_id    = (int) get_post_field( 'post_author', $new_course_id );

		if ( $source_author_id > 0 ) {
			$instructor_ids[] = $source_author_id;
		}

		if ( $new_author_id > 0 ) {
			$instructor_ids[] = $new_author_id;
		}

		$instructor_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $instructor_ids )
				)
			)
		);

		foreach ( $instructor_ids as $instructor_id ) {
			$existing_course_ids = get_user_meta( $instructor_id, '_tutor_instructor_course_id', false );
			$already_attached    = in_array( (string) $new_course_id, array_map( 'strval', (array) $existing_course_ids ), true );

			if ( $already_attached ) {
				continue;
			}

			add_user_meta( $instructor_id, '_tutor_instructor_course_id', $new_course_id );
		}
	}

	/**
	 * Duplicate post meta
	 *
	 * @param int $old_id  main post id.
	 * @param int $new_id  new duplicated post id.
	 * @param int $absolute_course_id course id.
	 *
	 * @return void
	 */
	private function duplicate_post_meta( $old_id, $new_id, $absolute_course_id ) {

		// Get existing meta from old post.
		$meta_array                             = get_post_meta( $old_id );
		! is_array( $meta_array ) ? $meta_array = array() : 0;

		// Add these meta to newly created post.
		foreach ( $meta_array as $name => $value ) {

			// Convert to singular value from second level array.
			$value = is_array( $value ) ? ( isset( $value[0] ) ? $value[0] : '' ) : '';
			$value = is_serialized( $value ) ? unserialize( $value ) : $value; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

			// Replace old course ID meta with new one.
			'_tutor_course_price_type' === $name ? $value = 'free' : 0;

			if ( '_tutor_course_product_id' === $name ) {
				continue;
			}

			/**
			 * Course assignment linked with new duplicated course id
			 *
			 * @since v2.0.7
			 */
			if ( '_tutor_course_id_for_assignments' === $name ) {
				$value = $absolute_course_id;
			}

			/**
			 * Lesson course link must point at the duplicated course.
			 *
			 * @since 4.1.0
			 */
			if ( '_tutor_course_id_for_lesson' === $name && $absolute_course_id ) {
				$value = $absolute_course_id;
			}

			update_post_meta( $new_id, $name, $value );
		}
	}

	/**
	 * Rewrite internal content IDs after a course tree has been duplicated.
	 *
	 * Remaps content-drip prerequisite IDs to their counterparts in the copy.
	 * IDs with no match in the map are dropped so the copy cannot lock
	 * students behind content that lives only on the source course.
	 *
	 * @since 4.1.0
	 *
	 * @param int $new_course_id Newly created (or target) course ID.
	 *
	 * @return void
	 */
	private function remap_internal_references( int $new_course_id ): void {
		if ( empty( $this->id_map ) ) {
			return;
		}

		foreach ( $this->id_map as $new_post_id ) {
			$new_post_id = (int) $new_post_id;

			$this->remap_content_drip_prerequisites( $new_post_id );
			$this->remap_quiz_option_drip_prerequisites( $new_post_id );

			if ( get_post_meta( $new_post_id, '_tutor_course_id_for_lesson', true ) ) {
				update_post_meta( $new_post_id, '_tutor_course_id_for_lesson', $new_course_id );
			}

			if ( get_post_meta( $new_post_id, '_tutor_course_id_for_assignments', true ) ) {
				update_post_meta( $new_post_id, '_tutor_course_id_for_assignments', $new_course_id );
			}
		}
	}

	/**
	 * Remap prerequisite IDs stored in `_content_drip_settings`.
	 *
	 * @since 4.1.0
	 *
	 * @param int $post_id Duplicated content post ID.
	 *
	 * @return void
	 */
	private function remap_content_drip_prerequisites( int $post_id ): void {
		$settings = get_post_meta( $post_id, '_content_drip_settings', true );
		if ( ! is_array( $settings ) || empty( $settings['prerequisites'] ) ) {
			return;
		}

		$settings['prerequisites'] = $this->remap_id_list( $settings['prerequisites'] );
		update_post_meta( $post_id, '_content_drip_settings', $settings );
	}

	/**
	 * Remap prerequisite IDs nested inside `tutor_quiz_option`.
	 *
	 * Quiz builder also stores drip settings under quiz options; keep both
	 * stores in sync after duplication.
	 *
	 * @since 4.1.0
	 *
	 * @param int $post_id Duplicated quiz post ID.
	 *
	 * @return void
	 */
	private function remap_quiz_option_drip_prerequisites( int $post_id ): void {
		$quiz_option = get_post_meta( $post_id, 'tutor_quiz_option', true );
		if ( ! is_array( $quiz_option ) ) {
			return;
		}

		if ( empty( $quiz_option['content_drip_settings'] ) || ! is_array( $quiz_option['content_drip_settings'] ) ) {
			return;
		}

		if ( empty( $quiz_option['content_drip_settings']['prerequisites'] ) ) {
			return;
		}

		$quiz_option['content_drip_settings']['prerequisites'] = $this->remap_id_list(
			$quiz_option['content_drip_settings']['prerequisites']
		);
		update_post_meta( $post_id, 'tutor_quiz_option', $quiz_option );
	}

	/**
	 * Map a list of source content IDs to duplicated IDs.
	 *
	 * Unmapped IDs are dropped.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $ids List of IDs (array) or a single ID.
	 *
	 * @return array<int>
	 */
	private function remap_id_list( $ids ): array {
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$mapped = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 ) {
				continue;
			}

			if ( isset( $this->id_map[ $id ] ) ) {
				$mapped[] = (int) $this->id_map[ $id ];
			}
		}

		return array_values( array_unique( $mapped ) );
	}

	/**
	 * Assign post taxonomy.
	 *
	 * @param int   $old_id old id.
	 * @param int   $new_id new id.
	 * @param mixed $taxonomy taxonomy.
	 *
	 * @return void
	 */
	private function assign_post_taxonomy( $old_id, $new_id, $taxonomy ) {
		$old_terms                            = get_the_terms( $old_id, $taxonomy );
		! is_array( $old_terms ) ? $old_terms = array() : 0;

		// Extract terms IDs.
		$term_ids = array();
		foreach ( $old_terms as $term ) {
			$term_ids[] = $term->term_id;
		}

		// Assign the terms.
		count( $term_ids ) > 0 ? wp_set_post_terms( $new_id, $term_ids, $taxonomy ) : 0;
	}

	/**
	 * Duplicate quiz dependency
	 *
	 * @param int     $old_context_id old context id.
	 * @param int     $new_context_id new context id.
	 * @param boolean $is_answer is answer or not.
	 *
	 * @return void
	 */
	private function duplicate_quiz_dependency( $old_context_id, $new_context_id, bool $is_answer ) {

		$table_name     = $is_answer ? 'tutor_quiz_question_answers' : 'tutor_quiz_questions';
		$rel_id_column  = $is_answer ? 'belongs_question_id' : 'quiz_id';
		$base_id_column = $is_answer ? 'answer_id' : 'question_id';

		global $wpdb, $table_prefix;
		$context_table = $table_prefix . $table_name;

		// Get quiz quesions by quiz post ID.
		$query  = 'SELECT * FROM ' . sanitize_text_field( $context_table ) . ' WHERE ' . sanitize_text_field( $rel_id_column ) . '=' . sanitize_text_field( $old_context_id );
		$result = $wpdb->get_results( $query );//phpcs:ignore

		if ( is_array( $result ) && ! empty( $result ) ) {

			// Loop through every question and duplicate.
			foreach ( $result as $context ) {
				$context = (array) $context;

				$old_stuff_id = $context[ $base_id_column ];

				unset( $context[ $base_id_column ] );
				$context[ $rel_id_column ] = $new_context_id;

				// Insert new row.
				$wpdb->insert( $context_table, $context );
				$new_row_id = (int) $wpdb->insert_id;
				if ( $is_answer && $new_row_id > 0 ) {
					$this->update_inserted_answer_mask_url( $new_row_id, $context, $context_table );
				}

				// Now copy quiz question answers.
				! $is_answer ? $this->duplicate_quiz_dependency( $old_stuff_id, $wpdb->insert_id, true ) : 0;
			}
		}
	}


	/**
	 * Get child post ids.
	 *
	 * @param int $parent_id parent id.
	 *
	 * @return array
	 */
	private function get_child_post_ids( $parent_id ) {

		$children = get_children(
			array(
				'post_parent'    => $parent_id,
				'post_type'      => $this->necessary_child_types,
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		! is_array( $children ) ? $children = array() : 0;

		$child_ids = array();
		foreach ( $children as $child_post ) {
			is_object( $child_post ) ? $child_ids[] = (int) $child_post->ID : 0;
		}

		return $child_ids;
	}

	/**
	 * Strip columns
	 *
	 * @param array $post post.
	 *
	 * @return array
	 */
	private function strip_unnecessary_columns( array $post ) {
		$new_array = array();

		foreach ( $post as $column => $value ) {
			if ( in_array( $column, $this->necessary_post_columns ) ) {
				// Keep only if it exist in ncessary column list.
				$new_array[ $column ] = $value;
			}
		}

		return $new_array;
	}

	/**
	 * Duplicate draw/pin mask file for copied quiz answers.
	 *
	 * Keeps non draw/pin question types unchanged.
	 *
	 * @since 4.0.0
	 *
	 * @param array $answer_row Raw quiz answer row data.
	 *
	 * @return array
	 */
	private function duplicate_mask_if_needed( array $answer_row ): array {
		$question_type = isset( $answer_row['belongs_question_type'] ) ? str_replace( '-', '_', (string) $answer_row['belongs_question_type'] ) : '';
		$mask = isset( $answer_row['answer_two_gap_match'] ) ? self::normalize_quiz_mask_value( (string) $answer_row['answer_two_gap_match'] ) : '';
		if ( '' === $mask ) {
			$answer_row['answer_two_gap_match'] = '';
			return $answer_row;
		}

		$is_supported_type = in_array( $question_type, self::MASK_QUESTION_TYPES, true );
		$is_quiz_mask_path = false !== strpos( $mask, '/tutor/quiz-images/' )
			|| '' !== QuizImageStorage::sanitize_quiz_image_filename( $mask );
		if ( ! $is_supported_type && ! $is_quiz_mask_path ) {
			return $answer_row;
		}

		// Default by source path if question type is unavailable/unexpected in source row.
		if ( ! $is_supported_type ) {
			if ( false !== strpos( $mask, '/puzzle-' ) || 0 === strpos( $mask, 'puzzle-' ) ) {
				$question_type = 'puzzle';
			} else {
				$question_type = false !== strpos( $mask, '/pin-mask-' ) || 0 === strpos( $mask, 'pin-mask-' ) ? 'pin_image' : 'draw_image';
			}
		}

		// Fast path: clone existing local quiz-images file to a new file.
		$cloned_mask_url = $this->clone_local_quiz_mask_file( $mask, $question_type );
		if ( '' !== $cloned_mask_url ) {
			$answer_row['answer_two_gap_match'] = $cloned_mask_url;
			return $answer_row;
		}

		$answer_row['answer_two_gap_match'] = $mask;
		return $answer_row;
	}

	/**
	 * Regenerate and update mask URL for an inserted duplicated answer row.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $answer_id     Newly inserted answer ID.
	 * @param array  $source_row    Source row used for insert.
	 * @param string $answers_table Answer table name.
	 *
	 * @return void
	 */
	private function update_inserted_answer_mask_url( int $answer_id, array $source_row, string $answers_table ): void {
		$updated = $this->duplicate_mask_if_needed( $source_row );
		$new_url = isset( $updated['answer_two_gap_match'] ) ? (string) $updated['answer_two_gap_match'] : '';
		$old_url = isset( $source_row['answer_two_gap_match'] ) ? (string) $source_row['answer_two_gap_match'] : '';

		$new_url = self::normalize_quiz_mask_value( $new_url );
		$old_url = self::normalize_quiz_mask_value( $old_url );

		if ( '' === $new_url || $new_url === $old_url ) {
			return;
		}

		QueryHelper::update(
			$answers_table,
			array( 'answer_two_gap_match' => $new_url ),
			array( 'answer_id' => $answer_id )
		);
	}

	/**
	 * Add course duplicate menu.
	 *
	 * @param int $id id.
	 *
	 * @return void
	 */
	public function add_course_duplicate_menu( $id ) {
		if ( ! tutor_utils()->can_user_manage( 'course', $id ) ) {
			return;
		}

		$duplicate = $this->get_duplicate_url( $id, is_admin() );
		?>
		<a class="tutor-dropdown-item" href="<?php echo esc_url( $duplicate ); ?>">
			<i class="tutor-icon-copy tutor-mr-8" aria-hidden="true"></i>
			<span><?php esc_html_e( 'Duplicate', 'tutor-pro' ); ?></span>
		</a>
		<?php
	}

	/**
	 * Update course
	 *
	 * @param array $where | where condition required.
	 * @param array $data | data that need to be updated required.
	 *
	 * @return bool | true on success, false on failure.
	 *
	 * @since v2.0.0
	 */
	public static function update_course( array $where, array $data ): bool {
		global $wpdb;
		$table  = $wpdb->posts;
		$update = $wpdb->update(
			$table,
			$data,
			$where
		);
		return $update ? true : false;
	}
}
