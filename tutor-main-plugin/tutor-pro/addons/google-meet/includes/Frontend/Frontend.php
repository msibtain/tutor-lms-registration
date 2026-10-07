<?php
/**
 * Manage frontend things for google meet
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Frontend
 */

namespace TutorPro\GoogleMeet\Frontend;

use TutorPro\GoogleMeet\GoogleMeet;
use TutorPro\GoogleMeet\Models\EventsModel;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Components\SvgIcon;
use TUTOR\Icon;
use TUTOR\User;
use TUTOR_PRO\Dashboard;
use TutorPro\GoogleMeet\Utilities\Utilities;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage frontend functions, hooks & other stuff
 */
class Frontend {

	/**
	 * Google meeting post type
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	private $meet_post_type;

	/**
	 * Register hooks
	 *
	 * @since v2.1.0
	 */
	public function __construct() {
		$this->meet_post_type = EventsModel::POST_TYPE;

		add_action( 'load_dashboard_template_part_from_other_location', __CLASS__ . '::load_template' );
		add_filter( 'tutor_single_content_template', __CLASS__ . '::load_meeting_template', 100 );
		add_filter( 'tutor_google_meet_lesson_done', __CLASS__ . '::is_lesson_completed', 99, 3 );
		add_action( 'tutor_course/single/before/topics', __CLASS__ . '::show_meeting_on_course_info_tab' );
		add_action( 'tutor/google_meet/right_icon_area', __CLASS__ . '::right_icon_area', 10, 2 );

		// Add learning area nav item & single content loading.
		add_action( "tutor_learning_area_nav_item_{$this->meet_post_type}", array( $this, 'render_nav_item' ), 10, 2 );
		add_action( "tutor_single_content_{$this->meet_post_type}", array( $this, 'render_single_content' ) );
	}

	/**
	 * Register menu on frontend dashboard
	 *
	 * @param array $nav_items tutor available nav items.
	 *
	 * @return array
	 */
	public static function register_dashboard_menu( array $nav_items ): array {
		do_action( 'tutor_pro_before_google_meet_frontend_menu_add', $nav_items );

		$nav_items['google-meet'] = array(
			'title'    => __( 'Google Meet', 'tutor-pro' ),
			'auth_cap' => tutor()->instructor_role,
			'icon'     => 'tutor-icon-brand-google-meet',
		);

		$nav_items = apply_filters( 'tutor_pro_after_google_meet_menu', $nav_items );
		do_action( 'tutor_pro_google_meet_after_frontend_menu_register' );
		return $nav_items;
	}

	/**
	 * Load Dashboard template
	 *
	 * @param string $template template that is going to be loaded.
	 *
	 * @return string  template path to load
	 */
	public static function load_template( $template ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( tutor_utils()->is_dashboard_page( Dashboard::LIVE_CLASSES_MENU ) ) {
			$template = tutor_pro()->path . '/templates/dashboard/live-classes.php';
			if ( file_exists( $template ) ) {
				return $template;
			}
		}
		return $template;
	}

	/**
	 * Load lesson template on the course spotlight
	 * section
	 *
	 * @since v2.1.0
	 *
	 * @param string $template  template path.
	 *
	 * @return bool|string
	 */
	public static function load_meeting_template( $template ) {
		global $wp_query, $post;
		$plugin_data = GoogleMeet::meta_data();

		if ( $wp_query->is_single && ! empty( $wp_query->query_vars['post_type'] ) && EventsModel::POST_TYPE === $wp_query->query_vars['post_type'] ) {
			if ( is_user_logged_in() ) {
				$content_type = ( get_post_type( $post->post_parent ) === tutor()->course_post_type ) ? 'topic' : 'lesson';

				$has_content_access = tutor_utils()->has_enrolled_content_access( $content_type, $post->ID );
				if ( $has_content_access ) {
					$template = $plugin_data['templates'] . 'single-meeting.php';
				} else {
					// You need to enroll first.
					$template = tutor_get_template( 'single.lesson.required-enroll' );
				}
			} else {
				$template = tutor_get_template( 'login' );
			}
			return $template;
		}
		return $template;
	}

	/**
	 * Filter hook for google meet lesson progress count
	 *
	 * If particular lesson return true then on the course progress
	 * it will increase the number.
	 *
	 * @since v2.1.0
	 *
	 * @param bool $value initial complete status, false.
	 * @param int  $lesson_id google meet lesson id.
	 * @param int  $user_id current user id.
	 *
	 * @return boolean
	 */
	public static function is_lesson_completed( bool $value, $lesson_id, $user_id ): bool {
		$lesson_id = sanitize_text_field( tutor_utils()->get_post_id( $lesson_id ) );
		$user_id   = sanitize_text_field( tutor_utils()->get_user_id( $user_id ) );

		if ( $lesson_id && $user_id ) {
			$meta_key = '_tutor_completed_lesson_id_' . $lesson_id;
			$value    = get_user_meta( $user_id, $meta_key, true );
			$value    = $value ? true : false;
		}
		return $value;
	}

	/**
	 * Render ongoing meetings on the course info tab
	 * frontend course details page.
	 *
	 * @since v2.1.0
	 *
	 * @return void
	 */
	public static function show_meeting_on_course_info_tab() {
		$plugin_data = GoogleMeet::meta_data();
		$template    = $plugin_data['templates'] . 'meeting-parts/meeting-collapsible.php';
		if ( file_exists( $template ) ) {
			tutor_load_template_from_custom_path(
				$template
			);
		} else {
			echo esc_html( $template . ' not exists' );
		}
	}

	/**
	 * Manage Lesson right lock icon
	 *
	 * @since v2.1.0
	 *
	 * @param integer $post_id   content post id.
	 * @param boolean $lock_icon should show lock icon or not.
	 *
	 * @return void
	 */
	public static function right_icon_area( int $post_id, $lock_icon = false ): void {
		$post_id      = sanitize_text_field( $post_id );
		$user_id      = get_current_user_id();
		$is_completed = self::is_lesson_completed( false, $post_id, $user_id );
		if ( $is_completed ) {
			echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle' disabled='disabled' readonly='readonly' checked='checked'/>";
		} elseif ( $lock_icon ) {
				echo '<i class="tutor-icon-lock-line tutor-fs-7 tutor-color-muted tutor-mr-4" aria-hidden="true"></i>';
		} else {
			echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle' disabled='disabled' readonly='readonly'/>";
		}
	}

	/**
	 * Render google meeting title as nav item to show on the learning area
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $google_meeting Google meeting post object.
	 * @param bool    $can_access Can user access this content.
	 *
	 * @return void
	 */
	public function render_nav_item( WP_Post $google_meeting, bool $can_access ): void {
		include GoogleMeet::meta_data()['templates'] . 'learning-area/nav-item.php';
	}

	/**
	 * Render content for the a single google meeting
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $google_meeting Google meeting post object.
	 *
	 * @return void
	 */
	public function render_single_content( WP_Post $google_meeting ): void {
		$details = json_decode( get_post_meta( $google_meeting->ID, EventsModel::POST_META_KEYS[2], true ) );

		$start_datetime = $details->start_datetime ?? '';
		$end_datetime   = $details->end_datetime ?? '';
		$timezone       = $details->timezone ?? '';

		$start_ts = DateTimeHelper::create(
			$start_datetime,
			$timezone
		)->to_timestamp();

		$end_ts = DateTimeHelper::create(
			$end_datetime,
			$timezone
		)->to_timestamp();

		$duration_minutes = (int) round( ( $end_ts - $start_ts ) / 60 );

		$meeting_data = array(
			'id'           => $details->id ?? 0,
			'title'        => $google_meeting->post_title,
			'description'  => $google_meeting->post_content,
			'start_at'     => DateTimeHelper::create( $start_datetime, $timezone )
				->set_timezone( User::get_user_timezone_string() )
				->format( 'c' ),
			'start_at_utc' => DateTimeHelper::create( $start_datetime, $timezone )
				->set_timezone( 'UTC' )
				->format( 'Y-m-d H:i:s' ),
			'end_at'       => DateTimeHelper::create( $end_datetime, $timezone )
				->set_timezone( User::get_user_timezone_string() )
				->format( 'c' ),
			'end_at_utc'   => DateTimeHelper::create( $end_datetime, $timezone )
				->set_timezone( 'UTC' )
				->format( 'Y-m-d H:i:s' ),
			'duration'     => $duration_minutes,
			'host_email'   => $details->organizer->email ?? '',
			'timezone'     => $timezone,
			'url'          => $details->meet_link ?? '',
			'is_started'   => ( $details->start_datetime ?? 0 ) < time(),
			'is_expired'   => ( $details->end_datetime ?? 0 ) < time(),
			'meeting_type' => $google_meeting->post_type,
		);

		tutor_load_template(
			'learning-area.subpages.live-meeting',
			array(
				'meeting_data'  => $meeting_data,
				'table_content' => $this->get_table_content( $meeting_data ),
			),
			true
		);
	}

	/**
	 * Get table content
	 *
	 * @since 4.0.0
	 *
	 * @param array $meeting_data meeting data.
	 *
	 * @return array
	 */
	private function get_table_content( array $meeting_data ): array {
		$table_content = array();

		// Meeting Start Date.
		$table_content[] = array(
			'columns' => array(
				array(
					'content' => SvgIcon::make()->name( Icon::VIDEO_CAMERA_2 )->size( 20 )->get() . esc_html__( 'Meeting Start Date', 'tutor-pro' ),
				),
				array(
					'content' => '<span class="tutor-utc-date-time">' . esc_html( $meeting_data['start_at_utc'] ) . '</span>',
				),
			),
		);

		$table_content[] = array(
			'columns' => array(
				array(
					'content' => SvgIcon::make()->name( Icon::CALL_END )->size( 20 )->get() . esc_html__( 'Meeting End Date', 'tutor-pro' ),
				),
				array(
					'content' => '<span class="tutor-utc-date-time">' . esc_html( $meeting_data['end_at_utc'] ) . '</span>',
				),
			),
		);

		// Meeting Duration.
		$table_content[] = array(
			'columns' => array(
				array(
					'content' => SvgIcon::make()->name( Icon::CLOCK_2 )->size( 20 )->get() . esc_html__( 'Meeting Duration', 'tutor-pro' ),
				),
				array(
					'content' => sprintf(
						/* translators: %d: meeting duration in minutes */
						_n( '%d minute', '%d minutes', $meeting_data['duration'], 'tutor-pro' ),
						$meeting_data['duration']
					),
				),
			),
		);

		// Host Email.
		$table_content[] = array(
			'columns' => array(
				array(
					'content' => SvgIcon::make()->name( Icon::INSTRUCTOR )->size( 20 )->get() . esc_html__( 'Host Email', 'tutor-pro' ),
				),
				array(
					'content' => $meeting_data['host_email'],
				),
			),
		);

		return $table_content;
	}

	/**
	 * Get google meeting's content type info with start date
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $google_meeting Google meeting.
	 *
	 * @return string
	 */
	public static function get_content_type_info( WP_Post $google_meeting ) {
		$content_type = __( 'Google Meet', 'tutor-pro' );

		$details = json_decode( get_post_meta( $google_meeting->ID, EventsModel::POST_META_KEYS[2], true ) );

		$start_datetime = $details->start_datetime ?? '';
		if ( $start_datetime && ! empty( $start_datetime ) ) {
			$formatted_datetime = DateTimeHelper::create( $start_datetime, $details->timezone ?? '' )
				->set_timezone( 'UTC' )
				->format( 'Y-m-d H:i:s' );
			$content_type       = sprintf(
				/* translators: %s: formatted date and time */
				__( 'Google Meet - %s', 'tutor-pro' ),
				'<span class="tutor-utc-date-time">' . $formatted_datetime . '</span>'
			);
		}

		return $content_type;
	}
}
