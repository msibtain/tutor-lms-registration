<?php
/**
 * Handles All Notifications
 *
 * @package TutorPro\Addons
 * @subpackage Notification
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.10
 */

namespace TUTOR_NOTIFICATIONS;

defined( 'ABSPATH' ) || exit;

use Tutor\Components\InputField;
use Tutor\Components\Constants\InputType;

/**
 * Tutor Notifications class
 */
class Tutor_Notifications {

	/**
	 * Instance of utils class.
	 *
	 * @var Utils
	 */
	public $utils;

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'tutor/options/attr', array( $this, 'add_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scrips' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scrips' ) );
		add_action( 'tutor_dashboard/before_header_button', array( $this, 'load_notification_template' ) );
		add_action( 'tutor_announcement_editor/after', array( $this, 'notification_checkbox_for_announcement' ) );

		$this->utils = new \TUTOR_NOTIFICATIONS\Utils();
	}

	/**
	 * Load frontend scripts
	 */
	public function load_scrips() {
		// Service worker should always be registered regardless of login state.
		wp_enqueue_script( 'tutor-pn-register', TUTOR_NOTIFICATIONS()->url . 'assets/js/register.js', array( 'wp-i18n' ), TUTOR_PRO_VERSION, true );
		wp_enqueue_style( 'tutor-pn-permission-css', TUTOR_NOTIFICATIONS()->url . 'assets/css/permission.css', array(), TUTOR_PRO_VERSION );

		if ( is_user_logged_in() && tutor_utils()->is_dashboard_page() ) {
			wp_enqueue_style( 'tutor-notifications', TUTOR_NOTIFICATIONS()->url . 'assets/css/tutor-notifications.css', array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-notifications', TUTOR_NOTIFICATIONS()->url . 'assets/js/tutor-notifications.js', array( 'tutor-core', 'wp-i18n' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Add options
	 *
	 * @param array $attr options array.
	 *
	 * @return array
	 */
	public function add_options( $attr ) {

		$attr['tutor_notifications'] = array(
			'label'    => __( 'Notifications', 'tutor-pro' ),
			'slug'     => 'tutor_notifications',
			'desc'     => __( 'Notifications Settings', 'tutor-pro' ),
			'template' => 'notifications',
			'icon'     => 'tutor-icon-bell-bold',
			'blocks'   => array(
				array(
					'label'        => __( 'Student Notification', 'tutor-pro' ),
					'tooltip'      => __( 'Notifications for Students', 'tutor-pro' ),
					'status_label' => __( 'Notification Status', 'tutor-pro' ),
					'block_type'   => 'notification',
					'fields'       => array(
						array(
							'label'          => __( 'Course Enrolled', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][course_enrolled]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][course_enrolled]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Notification when a student enrolls in a course.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Cancel Enrollment', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][remove_from_course]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][remove_from_course]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Notification when a student\'s enrollment is cancelled.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Assignment Graded', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][assignment_graded]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][assignment_graded]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'When an instructor grades a submitted assignment of the student.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'New Announcement Posted', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][new_announcement_posted]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][new_announcement_posted]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Notification for new announcements posted by the instructor.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Q&A Message Answered', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][after_question_answered]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][after_question_answered]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'When someone answers one of the student’s Q&A.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Feedback Submitted for Quiz Attempt', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_students][feedback_submitted_for_quiz]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_students][feedback_submitted_for_quiz]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Student receives feedback for a quiz attempt.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Removed From Course', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_pn_to_students][delete_from_course]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'An instructor/admin deletes a student from the enrollment list.', 'tutor-pro' ),
						),
					),
				), // End of Student Notifications.
				array(
					'label'        => __( 'Instructor Notification', 'tutor-pro' ),
					'tooltip'      => __( 'Notifications for Instructors', 'tutor-pro' ),
					'status_label' => __( 'Notification Status', 'tutor-pro' ),
					'block_type'   => 'notification',
					'fields'       => array(
						array(
							'label'          => __( 'Instructor Application Accepted', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_instructors][instructor_application_accepted]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_instructors][instructor_application_accepted]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Submitted instructor registration application is accepted by the admin.', 'tutor-pro' ),
						),
						array(
							'label'          => __( 'Instructor Application Rejected', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_instructors][instructor_application_rejected]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_instructors][instructor_application_rejected]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'Submitted instructor registration application is rejected by the admin.', 'tutor-pro' ),
						),
					),
				), // End of Instructor Notifications.
				array(
					'label'        => __( 'Admin Notification', 'tutor-pro' ),
					'tooltip'      => __( 'Notifications for Admin', 'tutor-pro' ),
					'status_label' => __( 'Notification Status', 'tutor-pro' ),
					'block_type'   => 'notification',
					'fields'       => array(
						array(
							'label'          => __( 'Instructor Application Received', 'tutor-pro' ),
							'type'           => 'checkbox_notification',
							'select_options' => false,
							'options'        => array(
								'[tutor_notifications_to_admin][instructor_application_received]' => __( 'On Site', 'tutor-pro' ),
								'[tutor_pn_to_admin][instructor_application_received]' => __( 'Push', 'tutor-pro' ),
							),
							'desc'           => __( 'When you receive an application from someone wanting to register as an instructor', 'tutor-pro' ),
						),
					),
				),
			),
		);

		foreach ( $attr['tutor_notifications']['blocks'] as $i1 => $block ) {
			foreach ( $block['fields'] as $i2 => $field ) {
				foreach ( $field['options'] as $key => $option ) {
					$option_key = str_replace( '][', '.', $key );
					$option_key = str_replace( '[', '', $option_key );
					$option_key = str_replace( ']', '', $option_key );

					$ref = &$attr['tutor_notifications']['blocks'][ $i1 ]['fields'][ $i2 ]['options'][ $key ];
					$ref = array(
						'label' => $ref,
						'value' => tutor_utils()->get_option( $option_key ),
					);
				}
			}
		}

		return $attr;
	}

	/**
	 * Load notification template
	 */
	public function load_notification_template() {
		tutor_load_template_from_custom_path( tutor_notifications()->path . 'templates/dashboard-notifications.php' );
	}

	/**
	 * Add notification checkbox in announcement editor
	 */
	public function notification_checkbox_for_announcement() {
		$notify_all_students = tutor_utils()->get_option( 'tutor_notifications_to_students.new_announcement_posted' );

		if ( ! $notify_all_students ) {
			return;
		}

		$is_admin_dashboard = is_admin();

		if ( $is_admin_dashboard ) : ?>
			<div class="tutor-option-field-row">
				<div class="tutor-form-check tutor-mb-4">
					<input id="tutor_announcement-notification-onsite" type="checkbox" class="tutor-form-check-input tutor-form-check-20" name="tutor_notify_all_students" checked="checked"/>
					<label for="tutor_announcement-notification-onsite">
						<?php esc_html_e( 'Send on-site notification to all students of this course.', 'tutor-pro' ); ?>
					</label>
				</div>
			</div>
			<?php
		else :
			InputField::make()
				->type( InputType::CHECKBOX )
				->name( 'tutor_notify_all_students' )
				->label( __( 'Send on-site notification to all students of this course.', 'tutor-pro' ) )
				->attr( 'x-bind', "register('tutor_notify_all_students')" )
				->checked()
				->render();
		endif;
	}
}
