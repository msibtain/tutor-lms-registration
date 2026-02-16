<?php
/**
 * Buddypress Groups
 *
 * @package TutorPro\Addons
 * @subpackage Buddypress
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

namespace TUTOR_BP;

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BuddyPressGroups
 */
class BuddyPressGroups {

	/**
	 * Register hooks
	 */
	public function __construct() {
		add_filter( 'tutor_course_settings_tabs', array( $this, 'settings_attr' ) );
		add_filter( 'bp_get_activity_action', array( $this, 'tutor_bp_group_activities' ), 10, 3 );
		add_action( 'tutor_course/settings_tab_content/after/tutor_bp', array( $this, 'tutor_bp_settings' ) );
		add_action( 'tutor_save_course', array( $this, 'save_course_meta' ), 10, 2 );

		/**
		 * Setup BP Group Nav
		 */
		add_action( 'bp_init', array( $this, 'setup_group_nav' ), 100 );

		/**
		 * Events Hook
		 */
		add_action( 'tutor_course_complete_after', array( $this, 'tutor_course_complete_after' ) );
		add_action( 'tutor_after_enroll', array( $this, 'tutor_after_enroll' ) );
		add_action( 'tutor/course/started', array( $this, 'tutor_course_started' ) );
		add_action( 'tutor/lesson_update/after', array( $this, 'tutor_lesson_update_after' ) );
		add_action( 'tutor/lesson/created', array( $this, 'tutor_lesson_created' ) );
		add_action( 'tutor_quiz/start/before', array( $this, 'quiz_start_before' ), 10, 2 );
		add_action( 'tutor_quiz_finished', array( $this, 'tutor_quiz_finished' ), 10, 3 );
		add_action( 'tutor_quiz/attempt_ended', array( $this, 'tutor_quiz_attempt_ended' ) );

		/**
		 * Extend Course builder data
		 *
		 * @since 3.0.0
		 */
		add_filter( 'tutor_course_builder_localized_data', array( $this, 'extend_course_builder_localized_data' ) );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
	}

	/**
	 * Settings attributes.
	 *
	 * @param array $args args.
	 *
	 * @return array
	 */
	public function settings_attr( $args ) {
		$args['tutor_bp'] = array(
			'label'      => __( 'BuddyPress Groups', 'tutor-pro' ),
			'desc'       => __( 'Assign this course to a BuddyPress Group', 'tutor-pro' ),
			'icon_class' => 'dashicons dashicons-buddicons-buddypress-logo',
			'callback'   => '',
			'fields'     => array(
				'_tutor_course_settings[enable_tutor_bp]' => array(
					'type'    => 'checkbox',
					'label'   => '',
					'desc'    => __( 'Enable / Disable BuddyPress group activity feeds', 'tutor-pro' ),
					'options' => array(
						array(
							'label_title' => __( 'Enable', 'tutor-pro' ),
							'checked'     => (bool) tutor_utils()->get_course_settings( get_the_ID(), 'enable_tutor_bp' ),
							'value'       => '1',
						),
					),
				),
			),
		);
		return $args;
	}


	/**
	 * Group activities
	 *
	 * @param mixed $action action.
	 * @param mixed $activity activity.
	 * @param mixed $r unknown.
	 *
	 * @return mixed
	 */
	public function tutor_bp_group_activities( $action, $activity, $r ) {
		$time = bp_insert_activity_meta();
		return $action;
	}


	/**
	 * BP settings
	 *
	 * @return void
	 */
	public function tutor_bp_settings() {
		include TUTOR_BP()->path . 'views/bp-group-course.php';
	}

	/**
	 * Save BuddyPress group as course meta
	 *
	 * @param int    $post_id post id.
	 * @param object $post post object.
	 *
	 * @return void
	 */
	public function save_course_meta( $post_id, $post ) {
		global $wpdb;

		// Prepare data.
		$group_ids = Input::post( 'bp_attached_group_ids', array(), Input::TYPE_ARRAY );
		$group_ids = array_filter(
			$group_ids,
			function( $id ) {
				return is_numeric( $id );
			}
		);

		// Get existing group IDs for the course.
		$existing_group_ids = self::get_group_ids_by_course( $post_id );

		// Get group IDs to delete that's not in new groupd ID array.
		$delete_group_ids = array_diff( $existing_group_ids, $group_ids );

		// Exclude existing IDs from new selection.
		$new_group_ids = array_diff( $group_ids, $existing_group_ids );

		// Now firstly delete what should be deleted.
		if ( tutor_utils()->count( $delete_group_ids ) ) {
			foreach ( $delete_group_ids as $delete_group_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}bp_groups_groupmeta 
						WHERE group_id = %d 
							AND meta_key = %s 
							AND meta_value = %d",
						$delete_group_id,
						'_tutor_attached_course',
						$post_id
					)
				);
			}
		}

		// Insert new groups that doesn't exist yet.
		if ( tutor_utils()->count( $new_group_ids ) ) {
			foreach ( $new_group_ids as $group_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'bp_groups_groupmeta',
					array(
						'group_id'   => $group_id,
						'meta_key'   => '_tutor_attached_course',
						'meta_value' => $post_id,
					)
				);
			}
		}
	}

	/**
	 * Get BuddyPress Group ID by Tutor Course ID
	 *
	 * @param int $course_id course id.
	 *
	 * @return array
	 */
	public static function get_group_ids_by_course( $course_id = 0 ) {
		global $wpdb;

		if ( ! $course_id ) {
			return array();
		}

		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT group_id 
				FROM {$wpdb->prefix}bp_groups_groupmeta 
				WHERE 	meta_key = %s 
				AND meta_value = %d",
				'_tutor_attached_course',
				$course_id
			)
		);

		return (array) $group_ids;
	}


	/**
	 * Setup group nav
	 *
	 * @return void
	 */
	public function setup_group_nav() {
		global $bp;
		/* Add some group subnav items */
		$user_access = false;
		$group_link  = '';
		if ( bp_is_active( 'groups' ) && ! empty( $bp->groups->current_group ) ) {
			$group_link  = $bp->root_domain . '/' . bp_get_groups_root_slug() . '/' . $bp->groups->current_group->slug . '/';
			$user_access = $bp->groups->current_group->user_has_access;
			bp_core_new_subnav_item(
				array(
					'name'            => __( 'Courses', 'tutor-pro' ),
					'slug'            => tutor()->course_post_type,
					'parent_url'      => $group_link,
					'parent_slug'     => $bp->groups->current_group->slug,
					'screen_function' => array( $this, 'bp_group_courses' ),
					'position'        => 50,
					'user_has_access' => $user_access,
					'item_css_id'     => 'courses',
				)
			);
		}
	}


	/**
	 * BuddyPress Group Course Tab Page
	 *
	 * @since v.1.5.0
	 */
	public function bp_group_courses() {
		add_action( 'bp_template_content', array( $this, 'bp_courses_group_show_screen_content' ) );

		$templates = array( 'groups/single/plugins.php', 'plugin-template.php' );
		if ( strstr( locate_template( $templates ), 'groups/single/plugins.php' ) ) {
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/plugins' ) );
		} else {
			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );
		}
	}

	/**
	 * Courses group screen title
	 *
	 * @return void
	 */
	public function bp_courses_group_show_screen_title() {
		echo esc_html( 'New Tab Title' );
	}

	/**
	 * Courses group screen content
	 *
	 * @return void
	 */
	public function bp_courses_group_show_screen_content() {
		$group_id = bp_get_group_id();
		if ( ! $group_id ) {
			return;
		}

		global $wpdb;

		$course_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE  meta_key = '_tutor_attached_course' AND group_id = %d ", $group_id ) );

		if ( tutor_utils()->count( $course_ids ) ) {

			foreach ( $course_ids as $key => $course_id ) {
				$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
				if ( ! $is_enable ) {
					unset( $course_ids[ $key ] );
				}
			}

			$course_ids_string = implode( ',', $course_ids );
			echo do_shortcode( "[tutor_course id='{$course_ids_string}' ]" );
		}
	}




	/**
	 * After course complete.
	 *
	 * @since 1.5.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void.
	 */
	public function tutor_course_complete_after( $course_id ) {
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$student_id = get_current_user_id();
		$group_ids  = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {

				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_completed_course', $activities );

				if ( $checked_activity && groups_is_user_member( $student_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before' );

					$course_url    = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';
					$activity_args = apply_filters(
						'tutor_bp_course_completed_record_activity_args',
						array(
							'user_id'           => $student_id,
							'action'            => '_tutor_course_completed',
							'content'           => sprintf( __( 'I just completed learning %s. It was super insightful!', 'tutor-pro' ), $course_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);
					$activity_id   = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $activity_id );
				}
			}
		}
	}

	/**
	 * Course Enroll BuddyPress
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function tutor_after_enroll( $course_id ) {
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$student_id = get_current_user_id();
		$group_ids  = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {

				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_enrolled_course', $activities );

				if ( $checked_activity && groups_is_user_member( $student_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before' );

					$course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';

					$activity_args = apply_filters(
						'tutor_bp_course_enrolled_record_activity_args',
						array(
							'user_id'           => $student_id,
							'action'            => '_tutor_course_enrolled',
							'content'           => sprintf( __( 'Just got enrolled in %s, looks very promising! You should check it out as well. ', 'tutor-pro' ), $course_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);
					$activity_id   = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $activity_id );
				}
			}
		}

	}


	/**
	 * Course started
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function tutor_course_started( $course_id ) {
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$student_id = get_current_user_id();
		$group_ids  = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			$action_type = '_tutor_course_started';
			foreach ( $group_ids as $group_id ) {

				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_course_start', $activities );

				if ( $checked_activity && groups_is_user_member( $student_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before', $action_type );

					$course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';

					$activity_args = apply_filters(
						'tutor_bp_course_started_record_activity_args',
						array(
							'user_id'           => $student_id,
							'action'            => $action_type,
							'content'           => sprintf( __( 'Starting with %s from today. Wish me luck! ', 'tutor-pro' ), $course_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);

					$activity_id = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $action_type, $activity_id );
				}
			}
		}

	}

	/**
	 * Lesson created
	 *
	 * @param int $lesson_id lesson id.
	 *
	 * @return void
	 */
	public function tutor_lesson_created( $lesson_id ) {
		$course_id = tutor_utils()->get_course_id_by_content( $lesson_id );
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$instructor_id = get_current_user_id();
		$group_ids     = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			$action_type = '_tutor_lesson_creates';

			$course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';
			$lesson_url = "<a href='" . get_the_permalink( $lesson_id ) . "' target='_blank'>" . get_the_title( $lesson_id ) . '</a>';

			foreach ( $group_ids as $group_id ) {
				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_creates_lesson', $activities );

				if ( $checked_activity && groups_is_user_member( $instructor_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before', $action_type );

					$activity_args = apply_filters(
						'tutor_bp_course_started_record_activity_args',
						array(
							'user_id'           => $instructor_id,
							'action'            => $action_type,
							'content'           => sprintf(
								__( 'I have created a new lesson %1$s for my course %2$s. Go check it out!', 'tutor-pro' ),
								$lesson_url,
								$course_url
							),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);
					$activity_id   = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $action_type, $activity_id );
				}
			}
		}

	}

	/**
	 * After lesson update.
	 *
	 * @param int $lesson_id lesson id.
	 *
	 * @return void
	 */
	public function tutor_lesson_update_after( $lesson_id ) {
		$course_id = tutor_utils()->get_course_id_by_content( $lesson_id );
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$instructor_id = get_current_user_id();
		$group_ids     = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			$action_type = '_tutor_lesson_updated';

			// $course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>". get_the_title( $course_id ) ."</a>";
			$lesson_url = "<a href='" . get_the_permalink( $lesson_id ) . "' target='_blank'>" . get_the_title( $lesson_id ) . '</a>';

			foreach ( $group_ids as $group_id ) {

				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_updated_lesson', $activities );

				if ( $checked_activity && groups_is_user_member( $instructor_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before', $action_type );

					$activity_args = apply_filters(
						'tutor_bp_course_started_record_activity_args',
						array(
							'user_id'           => $instructor_id,
							'action'            => $action_type,
							'content'           => sprintf( __( 'I updated my lesson on %s to add more relevant content. See what’s new!', 'tutor-pro' ), $lesson_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);
					$activity_id   = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $action_type, $activity_id );
				}
			}
		}

	}

	/**
	 * Before quiz start.
	 *
	 * @param int $quiz_id quiz id.
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function quiz_start_before( $quiz_id, $user_id ) {

		$course_id = tutor_utils()->get_course_id_by_content( $quiz_id );
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$group_ids = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			$action_type = '_tutor_quiz_started';

			// $course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>". get_the_title( $course_id ) ."</a>";
			$lesson_url = "<a href='" . get_the_permalink( $quiz_id ) . "' target='_blank'>" . get_the_title( $quiz_id ) . '</a>';

			foreach ( $group_ids as $group_id ) {
				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_started_quiz', $activities );

				if ( $checked_activity && groups_is_user_member( $user_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before', $action_type );

					$activity_args = apply_filters(
						'tutor_bp_course_started_record_activity_args',
						array(
							'user_id'           => $user_id,
							'action'            => $action_type,
							'content'           => sprintf( __( 'I just started taking the quiz %s, come and take it with me.', 'tutor-pro' ), $lesson_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);

					$activity_id = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $action_type, $activity_id );
				}
			}
		}

	}

	/**
	 * When quiz is finished.
	 *
	 * @param int $attempt_id attempt id.
	 * @param int $quiz_id quiz id.
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function tutor_quiz_finished( $attempt_id, $quiz_id, $user_id ) {
		$this->quiz_finish_activity( $quiz_id, $user_id );
	}

	/**
	 * When quiz attempt ended.
	 *
	 * @param int $attempt_id attempt id.
	 *
	 * @return void
	 */
	public function tutor_quiz_attempt_ended( $attempt_id ) {
		$attempt = tutor_utils()->get_attempt( $attempt_id );
		if ( $attempt ) {
			$this->quiz_finish_activity( $attempt->quiz_id, $attempt->user_id );
		}
	}

	/**
	 * Finish Quiz Activity on BuddyPress group
	 *
	 * @since 1.5.0
	 *
	 * @param int $quiz_id quiz id.
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function quiz_finish_activity( $quiz_id, $user_id ) {

		$course_id = tutor_utils()->get_course_id_by_content( $quiz_id );
		$is_enable = (bool) tutor_utils()->get_course_settings( $course_id, 'enable_tutor_bp' );
		if ( ! $is_enable ) {
			return;
		}

		$group_ids = self::get_group_ids_by_course( $course_id );

		if ( tutor_utils()->count( $group_ids ) ) {
			$action_type = '_tutor_quiz_finished';

			// $course_url = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>". get_the_title( $course_id ) ."</a>";
			$lesson_url = "<a href='" . get_the_permalink( $quiz_id ) . "' target='_blank'>" . get_the_title( $quiz_id ) . '</a>';

			foreach ( $group_ids as $group_id ) {
				$activities       = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );
				$checked_activity = tutor_utils()->array_get( 'user_finished_quiz', $activities );

				if ( $checked_activity && groups_is_user_member( $user_id, $group_id ) ) {
					do_action( 'tutor_bp_record_activity_before', $action_type );

					$activity_args = apply_filters(
						'tutor_bp_course_started_record_activity_args',
						array(
							'user_id'           => $user_id,
							'action'            => $action_type,
							'content'           => sprintf( __( 'Done with %s, it was a challenging quiz.', 'tutor-pro' ), $lesson_url ),
							'type'              => 'activity_update',
							'item_id'           => $group_id,
							'secondary_item_id' => $course_id,
						)
					);
					$activity_id   = groups_record_activity( $activity_args );

					do_action( 'tutor_bp_record_activity_after', $action_type, $activity_id );
				}
			}
		}
	}

	/**
	 * Extend localized data of course builder
	 *
	 * @since 3.0.0
	 *
	 * @param array $data data.
	 *
	 * @return array
	 */
	public function extend_course_builder_localized_data( $data ) {
		$bp_groups         = groups_get_groups( array( 'show_hidden' => true ) );
		$data['bp_groups'] = $bp_groups['groups'] ?? array();

		return $data;
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

		$attached_groups = (array) self::get_group_ids_by_course( $course_id );

		$data['bp_attached_groups'] = $attached_groups;

		return $data;
	}

}
