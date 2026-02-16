<?php
/**
 * Buddypress Group Settings
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
 * Class BuddyPressGroupSettings
 */
class BuddyPressGroupSettings extends \BP_Group_Extension {

	/**
	 * Constructor
	 */
	public function __construct() {
		$args = array(
			'slug'            => 'group-course-settings',
			'name'            => __( 'Course Settings', 'tutor-pro' ),
			'enable_nav_item' => false,
		);

		parent::init( $args );
	}

	/**
	 * Display
	 *
	 * @param int $group_id group id.
	 *
	 * @return void
	 */
	public function display( $group_id = null ) {

	}

	/**
	 * Settings screen.
	 *
	 * @param int $group_id group id.
	 *
	 * @return void
	 */
	public function settings_screen( $group_id = null ) {
		$group_status = groups_get_groupmeta( $group_id, 'bp_course_attached', true );
		$activities   = maybe_unserialize( groups_get_groupmeta( $group_id, '_tutor_bp_group_activities', true ) );

		if ( ! empty( $courses ) ) { ?>
			<div class="bp-learndash-group-course">
				<h4>Group Course</h4>


			</div><br><br/><br/>
			<?php
		}

		?>
		<div class="bp-learndash-course-activity-checkbox">

			<h4>Course Activities</h4>

			<p> <?php esc_html_e( 'Which Tutor LMS activity should be displayed in this group?', 'tutor-pro' ); ?></p>

			<div class="tutor-bp-group-activities">

				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_enrolled_course]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_enrolled_course', $activities ) )
					?>
					> <?php esc_html_e( 'User Enrolled a course', 'tutor-pro' ); ?>
				</label>

				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_course_start]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_course_start', $activities ) )
					?>
					> <?php esc_html_e( 'User Starts a course', 'tutor-pro' ); ?>
				</label>

				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_completed_course]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_completed_course', $activities ) )
					?>
					> <?php esc_html_e( 'User completes a course', 'tutor-pro' ); ?>
				</label>

				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_creates_lesson]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_creates_lesson', $activities ) )
					?>
					> <?php esc_html_e( 'User creates a lesson', 'tutor-pro' ); ?>
				</label>
				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_updated_lesson]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_updated_lesson', $activities ) )
					?>
					> <?php esc_html_e( 'User updated a lesson', 'tutor-pro' ); ?>
				</label>


				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_started_quiz]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_started_quiz', $activities ) )
					?>
					> <?php esc_html_e( 'User started quiz', 'tutor-pro' ); ?>
				</label>
				<label>
					<input type="checkbox" name="tutor_bp_group_activities[user_finished_quiz]" value="1" 
					<?php
					echo esc_html( $this->is_checked( 'user_finished_quiz', $activities ) )
					?>
					> <?php esc_html_e( 'User finished quiz', 'tutor-pro' ); ?>
				</label>

			</div>
		</div><br/>
		<?php

	}

	/**
	 * Settings screen save.
	 *
	 * @param int $group_id group id.
	 *
	 * @return void
	 */
	public function settings_screen_save( $group_id = null ) {
		$tutor_bp_course_activities = Input::post( 'tutor_bp_group_activities', '', Input::TYPE_KSES_POST );
		groups_update_groupmeta( $group_id, '_tutor_bp_group_activities', $tutor_bp_course_activities );
	}

	/**
	 * Checked based on given value
	 *
	 * @param mixed $value value.
	 * @param array $array array.
	 *
	 * @return string
	 */
	public function is_checked( $value, $array ) {
		$checked = '';
		if ( is_array( $array ) && array_key_exists( $value, $array ) ) {
			$checked = 'checked';
		}
		return $checked;
	}
}
