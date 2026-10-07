<?php
/**
 * Pusher
 *
 * @package TutorPro\Addons
 * @subpackage Notification
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.10
 */

namespace TUTOR_NOTIFICATIONS;

use \Minishlink\WebPush\WebPush;
use \Minishlink\WebPush\Subscription;
use Tutor\Helpers\UrlHelper;
use TUTOR\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Pusher class
 */
class Pusher extends Push_Notification {

	/**
	 * Whether push notifications can be sent in this environment.
	 *
	 * @since 4.0.0
	 *
	 * @var bool
	 */
	private $is_available = false;

	/**
	 * Failure reasons for admin notice.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	private $failure_reasons = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->check_php_extensions();
		$this->check_php_version();

		if ( ! empty( $this->failure_reasons ) && $this->is_any_push_notification_enabled() ) {
			add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
			return;
		}

		parent::__construct();

		$this->is_available = true;
	}

	/**
	 * Get all push notification option keys.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	private function get_push_notification_options() {
		return array(
			'tutor_pn_to_instructors.instructor_application_accepted',
			'tutor_pn_to_instructors.instructor_application_rejected',
			'tutor_pn_to_admin.instructor_application_received',
			'tutor_pn_to_students.new_announcement_posted',
			'tutor_pn_to_students.after_question_answered',
			'tutor_pn_to_students.feedback_submitted_for_quiz',
			'tutor_pn_to_students.enrollment_expired',
			'tutor_pn_to_students.course_enrolled',
			'tutor_pn_to_students.remove_from_course',
			'tutor_pn_to_students.assignment_graded',
		);
	}

	/**
	 * Check if any push notification setting is enabled.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private function is_any_push_notification_enabled() {
		$push_notification_options = $this->get_push_notification_options();

		foreach ( $push_notification_options as $option ) {
			if ( tutor_utils()->get_option( $option ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if required PHP extensions are loaded.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function check_php_extensions() {
		$required_extensions = array( 'curl', 'gmp', 'mbstring', 'openssl' );
		$missing_extensions  = array();

		foreach ( $required_extensions as $ext ) {
			if ( ! extension_loaded( $ext ) ) {
				$missing_extensions[] = $ext;
			}
		}

		if ( ! empty( $missing_extensions ) ) {
			$this->failure_reasons['missing_extensions'] = $missing_extensions;
		}
	}

	/**
	 * Check if minimum PHP version is installed.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function check_php_version() {
		$min_version = '7.2.5';

		if ( ! version_compare( PHP_VERSION, $min_version, '>=' ) ) {
			$this->failure_reasons['php_version'] = array(
				'current'  => PHP_VERSION,
				'required' => $min_version,
			);
		}
	}

	/**
	 * Show admin notice for missing requirements.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function show_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$reasons = array();

		if ( ! empty( $this->failure_reasons['missing_extensions'] ) ) {
			$reasons[] = sprintf(
				/* translators: %s: missing extensions list */
				__( 'Push notifications require the following PHP extensions: %s', 'tutor-pro' ),
				implode( ', ', $this->failure_reasons['missing_extensions'] )
			);
		}

		if ( ! empty( $this->failure_reasons['php_version'] ) ) {
			$reasons[] = sprintf(
				/* translators: %1$s: current PHP version, %2$s: required PHP version */
				__( 'Push notifications require PHP version %2$s or higher. Current version: %1$s', 'tutor-pro' ),
				$this->failure_reasons['php_version']['current'],
				$this->failure_reasons['php_version']['required']
			);
		}
		?>
		<div class="notice notice-error is-dismissible">
			<p><strong><?php esc_html_e( 'Tutor LMS Push Notifications', 'tutor-pro' ); ?></strong></p>
			<ul>
				<?php foreach ( $reasons as $reason ) : ?>
					<li><?php echo esc_html( $reason ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Get auth
	 *
	 * @return array
	 */
	private function get_auth() {

		$vapid = $this->get_vapid_keys();

		if ( $vapid ) {

			$vapid['subject'] = get_home_url();

			return array(
				'VAPID' => $vapid,
			);
		}
	}

	/**
	 * Broadcast
	 *
	 * @param array       $user_ids user ids.
	 * @param string      $title title.
	 * @param string      $message message.
	 * @param string|bool $url url.
	 * @param array       $notification_ids notification IDs keyed by user ID.
	 */
	private function broadcast( $user_ids = array(), $title = '', $message = '', $url = null, $notification_ids = array() ) {

		if ( ! $this->is_available ) {
			return;
		}

		$this->load_web_push();

		$user_ids         = is_array( $user_ids ) ? $user_ids : array( $user_ids );
		$user_ids         = array_unique( $user_ids );
		$notification_ids = is_array( $notification_ids ) ? $notification_ids : array();

		$auth = $this->get_auth();
		if ( ! $auth ) {
			return;
		}

		try {
			$web_push = new WebPush( $auth );
		} catch ( \Exception $e ) {
			return;
		}

		$payload = array(
			'title'     => $title,
			'body'      => $message,
			'data'      => array( 'url' => $url ),

			'badge'     => get_site_icon_url( 96 ),
			'icon'      => get_site_icon_url( 256 ),
			'dir'       => is_rtl() ? 'rtl' : 'ltr',
			'timestamp' => time() * 1000,
		);

		foreach ( $user_ids as $user_id ) {

			$notification_id = isset( $notification_ids[ $user_id ] ) ? (int) $notification_ids[ $user_id ] : 0;

			$payload['id']              = $notification_id;
			$payload['notification_id'] = $notification_id;

			// Assign the recipient user id to match before showing notification.
			$payload['client_id'] = $user_id;

			foreach ( $this->get_subscriptions( $user_id ) as $browser_key => $sub ) {
				$payload['browser_key'] = $browser_key;
				$web_push->queueNotification( Subscription::create( $sub ), json_encode( $payload ) );
			}
		}

		foreach ( $web_push->flush() as $report ) {
			$report->isSuccess();
		}
	}

	/**
	 * Instructor approval
	 *
	 * @param int   $instructor_id instructor id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function instructor_approval( $instructor_id, $notification_ids = array() ) {

		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_instructors.instructor_application_accepted' );
		if ( ! $send_notification ) {
			return;
		}

		if ( ! tutor_utils()->is_instructor( $instructor_id ) ) {
			return;
		}

		$user = get_userdata( $instructor_id );
		$name = $user->display_name;

		$message = sprintf(
			/* translators: %s: instructor name. */
			_x( 'Congratulations %s, your application to be an instructor has been approved.', 'instructorship-approved-text', 'tutor-pro' ),
			ucfirst( $name )
		);

		$this->broadcast( $instructor_id, __( 'Instructorship', 'tutor-pro' ), $message, tutor_utils()->tutor_dashboard_url(), $notification_ids );
	}

	/**
	 * Instructor rejected
	 *
	 * @param int   $instructor_id instructor id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function instructor_rejected( $instructor_id, $notification_ids = array() ) {

		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_instructors.instructor_application_rejected' );
		if ( ! $send_notification ) {
			return;
		}

		$user = get_userdata( $instructor_id );
		$name = $user->display_name;

		$message = sprintf(
			/* translators: %s: instructor name. */
			_x( '%s, your instructorship application has been declined.', 'instructorship-rejected-text', 'tutor-pro' ),
			ucfirst( $name )
		);

		$this->broadcast( $instructor_id, __( 'Instructorship', 'tutor-pro' ), $message, tutor_utils()->tutor_dashboard_url(), $notification_ids );
	}

	/**
	 * New instructor application
	 *
	 * @param int   $instructor_id instructor id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function new_instructor_application( $instructor_id, $notification_ids = array() ) {

		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_admin.instructor_application_received' );
		if ( ! $send_notification ) {
			return;
		}

		$admin_users         = get_users( array( 'role__in' => array( 'administrator' ) ) );
		$instructor_page_url = admin_url( 'admin.php?page=' . \TUTOR\Instructors_List::INSTRUCTOR_LIST_PAGE );

		foreach ( $admin_users as $admin ) {
			$message = sprintf(
				/* translators: %1$s: admin name, %2$s: instructor name. */
				_x( '%1$s, you have received a new application from %2$s for Instructorship.', 'instructor-application-received', 'tutor-pro' ),
				tutor_utils()->display_name( $admin->ID ),
				tutor_utils()->display_name( $instructor_id )
			);

			$this->broadcast( $admin->ID, __( 'Instructorship', 'tutor-pro' ), $message, $instructor_page_url, $notification_ids );
		}
	}

	/**
	 * Announcement notifications
	 *
	 * @param int          $announcement_id announcement id.
	 * @param array|object $announcement announcement.
	 * @param string       $action_type action type.
	 * @param array        $notification_ids notification IDs keyed by user ID.
	 */
	public function tutor_announcements_notify_students( $announcement_id, $announcement, $action_type, $notification_ids = array() ) {
		$tutor_push_notify_students = Input::post( 'tutor_push_notify_students' );
		$send_notification          = tutor_utils()->get_option( 'tutor_pn_to_students.new_announcement_posted' );

		if ( ! $tutor_push_notify_students || ! $send_notification ) {
			return;
		}

		$student_ids = tutor_utils()->get_students_data_by_course_id( $announcement->post_parent, 'ID' );
		$course_name = get_the_title( $announcement->post_parent );
		$author      = get_userdata( $announcement->post_author );
		$author_name = $author->display_name;

		$message = sprintf(
			'create' === $action_type
				/* translators: %1$s: author name, %2$s: course name. */
				? _x( 'A new announcement has been posted by %1$s of %2$s', 'announcement-text', 'tutor-pro' )
				/* translators: %1$s: author name, %2$s: course name. */
				: _x( 'An announcement has been updated by %1$s of %2$s', 'announcement-text', 'tutor-pro' ),
			ucfirst( $author_name ),
			$course_name
		);
		$assignment_url = tutor_utils()->is_legacy_learning_mode()
							? get_permalink( $announcement->post_parent ) . 'announcements/'
							: UrlHelper::add_query_params( get_permalink( $announcement->post_parent ), array( 'subpage' => 'announcements' ) );

		$this->broadcast( $student_ids, __( 'Announcements', 'tutor-pro' ), $message, $assignment_url, $notification_ids );
	}

	/**
	 * After answering questions
	 *
	 * @param int   $answer_id answer id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 * @return void
	 */
	public function tutor_after_answer_to_question( $answer_id, $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.after_question_answered' );

		if ( ! $send_notification ) {
			return;
		}

		$answer = tutor_utils()->get_qa_answer_by_answer_id( $answer_id );

		if ( ! $answer ) {
			return;
		}

		$course_name    = get_the_title( $answer->comment_post_ID );
		$comment_author = 'tutor_q_and_a' === get_comment_type( $answer_id ) ? get_comment_author( $answer_id ) : 0;
		$qa_url         = UrlHelper::add_query_params(
			tutor_utils()->get_tutor_dashboard_page_permalink( 'discussions' ),
			array(
				'tab' => 'qna',
				'id'  => $answer->question_id,
			)
		);

		$message = sprintf(
			/* translators: %1$s: answer author, %2$s: course name. */
			_x( 'A new answer has been posted by %1$s in %2$s\'s Q&A.', 'qa-answer-posted', 'tutor-pro' ),
			ucfirst( $comment_author ),
			$course_name
		);

		$this->broadcast( $answer->question_by, __( 'Q&A', 'tutor-pro' ), $message, $qa_url, $notification_ids );
	}

	/**
	 * Feedback submitted for quiz
	 *
	 * @param int   $attempt_id attempt id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function feedback_submitted_for_quiz_attempt( $attempt_id, $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.feedback_submitted_for_quiz' );

		if ( ! $send_notification ) {
			return;
		}

		$attempt    = tutor_utils()->get_attempt( $attempt_id );
		$quiz_title = get_post_field( 'post_title', $attempt->quiz_id );
		$course     = get_post( $attempt->course_id );
		$message    = sprintf(
			/* translators: %1$s: Quiz title, %2$s: Course title. */
			_x( 'Your quiz result for %1$s of %2$s has been published.', 'quiz-attempt-text', 'tutor-pro' ),
			$quiz_title,
			$course->post_title
		);
		$quiz_url = UrlHelper::add_query_params(
			tutor_utils()->get_tutor_dashboard_page_permalink( 'courses/my-quiz-attempts' ),
			array( 'attempt_id' => $attempt_id )
		);

		$this->broadcast( $attempt->user_id, __( 'Quiz', 'tutor-pro' ), $message, $quiz_url, $notification_ids );
	}

	/**
	 * Enrolment Expired
	 *
	 * @param int   $enrol_id enroll id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function tutor_enrollment_after_expired( $enrol_id, $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.enrollment_expired' );

		if ( ! $send_notification ) {
			return;
		}

		$enrolment = tutor_utils()->get_enrolment_by_enrol_id( $enrol_id );
		if ( ! $enrolment ) {
			return;
		}

		$course_name = $enrolment->course_title;
		$course_url  = get_the_permalink( $enrolment->course_id );

		$title = __( 'Your Enrollment Has Expired.', 'tutor-pro' );

		$this->broadcast( $enrolment->student_id, $title, $course_name, $course_url, $notification_ids );
	}

	/**
	 * Email to student when enrolled in course
	 *
	 * @param int    $course_id course id.
	 * @param int    $student_id student id.
	 * @param int    $enrol_id enrol id.
	 * @param string $status_to status to.
	 * @param array  $notification_ids notification IDs keyed by user ID.
	 */
	public function course_enroll_email_to_student( $course_id, $student_id, $enrol_id, $status_to = 'completed', $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.course_enrolled' );

		if ( ! $send_notification || 'completed' !== $status_to ) {
			return;
		}

		$course     = tutor_utils()->get_course_by_enrol_id( $enrol_id );
		$course_url = tutor_utils()->get_course_first_lesson( $course_id );

		$message = sprintf(
			/* translators: %s: course title. */
			_x( 'Congratulations, you have been successfully enrolled in %s', 'got-enrolled-text', 'tutor-pro' ),
			$course->post_title
		);

		$this->broadcast( $student_id, __( 'Enrollment', 'tutor-pro' ), $message, $course_url, $notification_ids );
	}

	/**
	 * Student removed from course
	 *
	 * @param int   $enrol_id enrol id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function tutor_student_remove_from_course( $enrol_id, $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.remove_from_course' );

		if ( ! $send_notification ) {
			return;
		}

		$enrolment = tutor_utils()->get_enrolment_by_enrol_id( $enrol_id );
		if ( ! $enrolment ) {
			return;
		}

		$display_name = $enrolment->display_name;
		$course_name  = $enrolment->course_title;
		$course_url   = get_the_permalink( $enrolment->course_id );

		$message = sprintf(
			/* translators: %1$s: user name, %2$s: course title. */
			_x( '%1$s, your enrollment request for %2$s has been declined.', 'enrollment-cancelled-text', 'tutor-pro' ),
			ucfirst( $display_name ),
			$course_name
		);

		$this->broadcast( $enrolment->student_id, __( 'Enrollment', 'tutor-pro' ), $message, $course_url, $notification_ids );
	}

	/**
	 * Assignment graded
	 *
	 * @param int   $assignment_submit_id assignment submit id.
	 * @param array $notification_ids notification IDs keyed by user ID.
	 */
	public function tutor_after_assignment_evaluate( $assignment_submit_id, $notification_ids = array() ) {
		$send_notification = tutor_utils()->get_option( 'tutor_pn_to_students.assignment_graded' );

		if ( ! $send_notification ) {
			return;
		}

		$submitted_assignment = tutor_utils()->get_assignment_submit_info( $assignment_submit_id );
		$assignment_name      = get_the_title( $submitted_assignment->comment_post_ID );
		$assignment_url       = get_permalink( $submitted_assignment->comment_post_ID );
		$user_data            = get_userdata( $submitted_assignment->user_id );
		$display_name         = $user_data->display_name;

		$message = sprintf(
			/* translators: %1$s: user name, %2$s: assignment title. */
			_x( 'Hi %1$s, your %2$s has been graded. Check it out.', 'grades-submitted-text', 'tutor-pro' ),
			ucfirst( $display_name ),
			$assignment_name
		);

		$this->broadcast( $submitted_assignment->user_id, __( 'Assignments', 'tutor-pro' ), $message, $assignment_url, $notification_ids );
	}
}
