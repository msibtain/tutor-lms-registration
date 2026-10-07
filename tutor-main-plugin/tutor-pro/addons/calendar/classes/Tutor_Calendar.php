<?php
/**
 * Tutor_Calendar for handle calendar logics
 *
 * @since 1.9.10
 *
 * @package Tutor Calendar
 */

namespace TUTOR_PRO_C;

use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\QueryHelper;
use TUTOR\Icon;
use TUTOR\Input;
use TUTOR\Course;
use Tutor\Models\CourseModel;
use Tutor\Models\EnrollmentModel;
use TutorPro\GoogleMeet\Models\EventsModel;
use TutorPro\GoogleMeet\Utilities\Utilities;
use TutorPro\GoogleMeet\Validator\Validator;

/**
 * Handle Tutor Calendar logics
 */
class Tutor_Calendar {

	/**
	 * Handle Dependencies Register Hooks
	 *
	 * @since 2.7.0 $register_hooks param added.
	 *
	 * @param bool $register_hooks register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		if ( ! $register_hooks ) {
			return;
		}

		add_filter( 'tutor_dashboard/nav_items', array( $this, 'register_calendar_menu' ) );
		add_action( 'load_dashboard_template_part_from_other_location', array( $this, 'load_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_get_calendar_materials', array( $this, 'get_calendar_materials' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
	}

	/**
	 * Register calendar menu item in dashboard navigation
	 *
	 * @since 1.9.10
	 *
	 * @param array $nav_items existing navigation items.
	 *
	 * @return array modified navigation items with calendar menu added.
	 */
	public function register_calendar_menu( $nav_items ) {
		do_action( 'tutor_pro_before_calendar_menu_add', $nav_items );

		$nav_items['calendar'] = array(
			'title'       => __( 'Calendar', 'tutor-pro' ),
			'icon'        => Icon::CALENDAR_2,
			'active_icon' => Icon::CALENDAR_2_FILL,
		);
		return apply_filters( 'tutor_pro_after_calendar_menu', $nav_items );
	}

	/**
	 * Load calendar template for dashboard
	 *
	 * @since 1.9.10
	 *
	 * @param string $template current template path.
	 *
	 * @return string template path for calendar or original template.
	 */
	public function load_template( $template ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;
		if ( isset( $query_vars['tutor_dashboard_page'] ) && 'calendar' === $query_vars['tutor_dashboard_page'] ) {
			$calendar_template = tutor_pro_calendar()->path . 'templates/calendar.php';
			if ( file_exists( $calendar_template ) ) {
				return apply_filters( 'tutor_pro_calendar', $calendar_template );
			}
		}
		return $template;
	}

	// @TODO: Need to remove when move to v4 frontend dashboard.
	/**
	 * Load admin assets
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function admin_script() {
		$page     = Input::get( 'page' );
		$sub_page = Input::get( 'subpage' );
		$params   = Input::get( 'dashboard-page' );
		if ( 'playground' === $page && 'dashboard' === $sub_page && 'calendar' === $params ) {
			wp_enqueue_script(
				'tutor-pro-calendar',
				tutor_pro_calendar()->url . 'assets/js/calendar/index.js',
				array( 'wp-i18n', 'wp-date', 'wp-element' ),
				TUTOR_PRO_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue calendar scripts and styles
	 *
	 * @since 1.9.10
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( tutor_utils()->is_dashboard_page( 'calendar' ) ) {
			wp_enqueue_script(
				'tutor-pro-calendar',
				tutor_pro_calendar()->url . 'assets/js/calendar/index.js',
				array( 'tutor-core', 'wp-i18n', 'wp-element', 'wp-date' ),
				TUTOR_PRO_VERSION,
				true
			);
		}
	}

	/**
	 * Check assignment expired or not
	 *
	 * @since 1.9.10
	 * @since 3.4.0 param $user_id added.
	 *
	 * @param int $assignment_id assignment id.
	 * @param int $user_id the user id.
	 *
	 * @return mixed array | false
	 */
	public static function get_assignment_info_by_user( int $assignment_id, int $user_id ) {
		$assignment_id = sanitize_text_field( $assignment_id );
		$time_duration = tutor_utils()->get_assignment_option(
			$assignment_id,
			'time_duration',
			array(
				'time'  => '',
				'value' => 0,
			)
		);

		$unlock_date = tutor_utils()->get_item_content_drip_settings( $assignment_id, 'unlock_date' );

		$post          = get_post( $assignment_id );
		$course_id     = tutor_utils()->get_course_id_by( 'assignment', $assignment_id );
		$enrolled_info = EnrollmentModel::is_enrolled( $course_id, $user_id );
		$enrolled_time = apply_filters( 'tutor_content_drip_assignment_deadline', strtotime( $enrolled_info->post_date_gmt ), $course_id, $assignment_id );
		if ( $post && ! is_null( $post ) ) {
			$assignment_created_time = strtotime( $post->post_date_gmt );
			$deadline_time           = $enrolled_time < $assignment_created_time ? $assignment_created_time : $enrolled_time;
			$time_duration_in_sec    = 0;
			if ( isset( $time_duration['value'] ) && isset( $time_duration['time'] ) ) {
				switch ( $time_duration['time'] ) {
					case 'hours':
						$time_duration_in_sec = 3600;
						break;
					case 'days':
						$time_duration_in_sec = 86400;
						break;
					case 'weeks':
						$time_duration_in_sec = 7 * 86400;
						break;
					default:
						$time_duration_in_sec = 0;
						break;
				}
			}

			$time_duration_in_sec = $time_duration_in_sec * (int) $time_duration['value'];
			if ( empty( $unlock_date ) ) {
				$remaining_time = $deadline_time + $time_duration_in_sec;
			} else {
				$remaining_time = ( strtotime( $unlock_date ) < $enrolled_time ? $deadline_time : strtotime( $unlock_date ) ) + $time_duration_in_sec;
			}

			$now                 = time();
			$time_duration_value = (int) $time_duration['value'];
			$week_values         = array(
				'days'  => _n( 'Day', 'Days', $time_duration_value, 'tutor-pro' ),
				'hours' => _n( 'Hour', 'Hours', $time_duration_value, 'tutor-pro' ),
				'weeks' => _n( 'Week', 'Weeks', $time_duration_value, 'tutor-pro' ),
			);

			$custom_expire_date    = '';
			$start_assignment_date = null;
			$deadline_from_start   = (bool) tutor_utils()->get_assignment_option( $assignment_id, 'deadline_from_start' );
			if ( $deadline_from_start ) {
				$assignment_comment = tutor_utils()->get_single_comment_user_post_id( $assignment_id, $user_id );
				if ( $assignment_comment && isset( $assignment_comment->comment_date_gmt ) ) {
					$start_assignment_date = $assignment_comment->comment_date_gmt;
					$remaining_time        = strtotime( $assignment_comment->comment_date_gmt ) + $time_duration_in_sec;
				}

				if ( ! $assignment_comment ) {
					$custom_expire_date = sprintf(
						// translators: %1$s is the number value (e.g., 3), %2$s is the time unit (e.g., days).
						esc_html__( '%1$s %2$s after you start the assignment', 'tutor-pro' ),
						esc_html( $time_duration_value ),
						esc_html( strtolower( $week_values[ $time_duration['time'] ] ) )
					);
				}
			}

			return array(
				'duration'     => 0 === $time_duration_value ? __( 'No Limit', 'tutor-pro' ) : $time_duration_value . ' ' . $week_values[ $time_duration['time'] ],
				'is_expired'   => 0 === $time_duration_value ? false : ( ( ! $deadline_from_start || $start_assignment_date ) && $now > $remaining_time ),
				'expire_date'  => 0 === $time_duration_value ? __( 'No Limit', 'tutor-pro' ) : ( $custom_expire_date ? $custom_expire_date : gmdate( 'Y-m-d', $remaining_time ) ),
				'expire_month' => 0 === $time_duration_value ? __( 'No Limit', 'tutor-pro' ) : ( $custom_expire_date ? '' : gmdate( 'n', $remaining_time ) ),
				'unlock_date'  => $unlock_date,
			);
		}
		return false;
	}

	/**
	 * Get course info for calendar event.
	 *
	 * @since 4.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return array|null
	 */
	private function get_course_info( int $course_id ) {
		$course_post = get_post( $course_id );

		if ( ! $course_post ) {
			return null;
		}

		$course_info = Course::get_mini_info( $course_post );
		$author_id   = $course_post->post_author;
		$thumbnail   = $course_info['image'] ?? '';

		if ( empty( $thumbnail ) ) {
			$thumbnail = tutor()->url . 'assets/images/placeholder.svg';
		}

		return array(
			'ID'         => (string) $course_id,
			'title'      => $course_info['title'] ?? $course_post->post_title,
			'guid'       => $course_post->guid,
			'author'     => get_the_author_meta( 'display_name', $author_id ),
			'author_url' => tutor_utils()->profile_url( $author_id, true ),
			'thumbnail'  => $thumbnail,
			'post_link'  => get_permalink( $course_id ),
		);
	}

	/**
	 * Resolve course id from a calendar event post.
	 *
	 * @since 4.0.0
	 *
	 * @param object $event calendar event post.
	 *
	 * @return int
	 */
	private function resolve_course_id_from_event( $event ) {
		$course_id = (int) $event->post_parent;

		if ( tutor()->topics_post_type === get_post_type( $course_id ) ) {
			$course_id = (int) wp_get_post_parent_id( $course_id );
		}

		return $course_id;
	}

	/**
	 * Quiz info time_limit|remaining_attempt|is_attempt_available
	 *
	 * @since  1.9.10
	 *
	 * @param  int $quiz_id quiz id.
	 * @return array
	 */
	public static function quiz_info( int $quiz_id ): array {
		$quiz_id           = sanitize_text_field( $quiz_id );
		$time_limit        = tutor_utils()->get_quiz_option( $quiz_id, 'time_limit.time_value' );
		$time_type         = tutor_utils()->get_quiz_option( $quiz_id, 'time_limit.time_type' );
		$previous_attempts = tutor_utils()->quiz_attempts( $quiz_id );
		$attempted_count   = is_array( $previous_attempts ) ? count( $previous_attempts ) : 0;

		$attempts_allowed     = tutor_utils()->get_quiz_option( get_the_ID(), 'attempts_allowed', 0 );
		$attempt_remaining    = $attempts_allowed - $attempted_count;
		$is_attempt_available = false;

		if ( 0 == $attempts_allowed ) {
			$is_attempt_available = true;
		} elseif ( $attempt_remaining ) {
				$is_attempt_available = true;
		} else {
			$is_attempt_available = false;
		}

		$available_time_types = array(
			'seconds' => __( 'Seconds', 'tutor-pro' ),
			'minutes' => __( 'Minutes', 'tutor-pro' ),
			'weeks'   => __( 'Weeks', 'tutor-pro' ),
			'days'    => __( 'Days', 'tutor-pro' ),
			'hours'   => __( 'Hours', 'tutor-pro' ),
		);

		return array(
			'time_limit'           => $time_limit . ' ' . $available_time_types[ $time_type ],
			'is_attempt_available' => $is_attempt_available,
			'attempt_remaining'    => 0 == $attempts_allowed ? __( 'No Limit', 'tutor-pro' ) : $attempt_remaining,
		);
	}

	/**
	 * Get zoom meeting list by course ids, year, month
	 *
	 * @since 2.0.7
	 *
	 * @param array  $course_ids course ids.
	 * @param string $year year.
	 * @param string $month month.
	 *
	 * @return array|object|null
	 */
	public function get_zoom_meeting_list( array $course_ids, $year, $month ) {
		global $wpdb;

		$ids_str = QueryHelper::prepare_in_clause( $course_ids );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM (
					SELECT 
						(SELECT meta_value FROM {$wpdb->postmeta}
							WHERE post_id = p.ID AND meta_key = '_tutor_zm_start_datetime'
						) AS zoom_meeting_at,
						
						(SELECT meta_value FROM {$wpdb->postmeta}
							WHERE post_id = p.ID AND meta_key = '_tutor_zm_for_topic'
						) AS topic_id,
						
						(SELECT post_title FROM {$wpdb->postmeta}
							LEFT JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = meta_value
							WHERE post_id = p.ID AND meta_key = '_tutor_zm_for_topic'
						) AS topic_title,
						
						(SELECT CASE 
							WHEN meta_value > 0 THEN (SELECT post_parent FROM {$wpdb->posts} WHERE ID = meta_value)
							ELSE post_parent
						END FROM {$wpdb->postmeta}
							WHERE post_id = p.ID AND meta_key = '_tutor_zm_for_topic'
						) AS course_id,
						
						p.ID, p.post_title, p.post_date, p.post_type, p.guid, p.post_content

					FROM {$wpdb->posts} p
					WHERE p.post_type = 'tutor_zoom_meeting'
				) AS A
				WHERE 
					YEAR(zoom_meeting_at) = %d
					AND MONTH(zoom_meeting_at) = %d
					AND course_id IN ({$ids_str})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$year,
				$month
			)
		);

		foreach ( $results as $meeting ) {
			$zoom_meeting_data = tutor_zoom_meeting_data( $meeting->ID );
			// Format date.
			$meeting->zm_start_date = gmdate( 'Y-m-d', strtotime( $meeting->zoom_meeting_at ) );
			$meeting->is_expired    = $zoom_meeting_data->is_expired;

			$zoom_data = json_decode( get_post_meta( $meeting->ID, '_tutor_zm_data', true ) );
			$timezone  = is_object( $zoom_data ) && isset( $zoom_data->timezone ) ? $zoom_data->timezone : wp_timezone_string();
			$start_at  = $zoom_meeting_data->start_date;

			$meeting->meta_info = array(
				'start_datetime' => $start_at ? DateTimeHelper::create( $start_at, $timezone )->set_timezone( 'UTC' )->format( 'Y-m-d H:i:s' ) : '',
				'expire_date'    => $zoom_meeting_data->end_date,
				'is_expired'     => $zoom_meeting_data->is_expired,
			);

			$meeting->course_info = $this->get_course_info( (int) $meeting->course_id );
		}

		return $results;
	}

	/**
	 * Handle ajax post request
	 *
	 * Merge assignment info with assignment post data
	 *
	 * @param int $month month in number.
	 * @param int $year the year.
	 * @param int $user_id user id.
	 *
	 * @return void|array
	 *
	 * @since 1.9.10
	 * @since 2.6.2 Added params $month, $year & user_id.
	 */
	public function get_calendar_materials( $month = 0, $year = 0, $user_id = 0 ) {
		if ( ! tutor_is_rest() ) {
			tutor_utils()->checking_nonce();
			$year  = Input::post( 'year', 0, Input::TYPE_INT );
			$month = Input::post( 'month', 0, Input::TYPE_INT );
			$month = 1 + $month;
		}

		$course_cache = array();
		$response     = '';
		$user_id      = tutor_utils()->get_user_id( $user_id );
		global $wpdb;

		$enrolled_courses    = CourseModel::get_enrolled_courses_by_user( $user_id );
		$enrolled_course_ids = tutor_utils()->get_enrolled_courses_ids_by_user( $user_id );

		if ( ! $enrolled_courses ) {
			$data = array(
				'response' => array(),
				'overdue'  => 0,
				'upcoming' => 0,
			);

			if ( ! tutor_is_rest() ) {
				wp_send_json_success( $data );
				exit;
			} else {
				return $data;
			}
		}

		$post_types = array(
			tutor()->assignment_post_type,
		);

		// If google meet addon is enabled then include meet post type.
		$is_enabled_gm = false;
		if ( class_exists( 'TutorPro\GoogleMeet\Validator\Validator' ) && Validator::is_addon_enabled() ) {
			$is_enabled_gm = true;
			$post_types[]  = tutor()->meet_post_type;
		}

		// Check content drip addon enabled or not.
		// If enabled then include lesson and quiz post type.
		$is_enabled_cd = tutor_utils()->is_addon_enabled( TUTOR_CONTENT_DRIP()->basename );
		if ( $is_enabled_cd ) {
			$post_types[] = tutor()->lesson_post_type;
			$post_types[] = tutor()->quiz_post_type;
		}

		$in_clause = QueryHelper::prepare_in_clause( $post_types );

		$data = array();

		foreach ( $enrolled_courses->posts as $key => $course ) {
			$topics = tutor_utils()->get_topics( $course->ID );
			foreach ( $topics->posts as $topic ) {
				$data[] = $topic->ID;
			}
		}

		// If google meet enabled then merge course ids with topic ids.
		// To get meeting that is under course.
		if ( $is_enabled_gm ) {
			$data = array_merge( $data, $enrolled_course_ids );
		}

		$data = QueryHelper::prepare_in_clause( $data );

		$query = "SELECT 
						ID,
						DATE (post_date) AS post_date, 
						MONTH(post_date) AS month, 
						DATE(post_date) AS created_at, 
						post_title, 
						post_content,
						post_parent, 
						guid, 
						post_type 
											FROM {$wpdb->posts} 
											WHERE post_parent IN  ({$data})
													AND post_type IN ($in_clause)
													AND post_status = %s
													
													AND YEAR(post_date) = %d
											GROUP BY post_date
											ORDER BY post_date ASC
									";

		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,//phpcs:ignore
				'publish',
				$year
			)
		);

		$response = array();

		foreach ( $results as $key => $result ) {
			$course_id = 0;

			if ( tutor()->assignment_post_type === $result->post_type ) {
				$meta_info = self::get_assignment_info_by_user( $result->ID, $user_id );
				$course_id = $this->resolve_course_id_from_event( $result );

				if ( $meta_info['unlock_date'] ) {
					$result->month = gmdate( 'm', strtotime( $meta_info['unlock_date'] ) );
				} elseif ( isset( $meta_info['expire_month'] ) && is_numeric( $meta_info['expire_month'] ) ) {
					$result->month = $meta_info['expire_month'];
				}

				$result->meta_info = $meta_info;
			} elseif ( in_array( $result->post_type, array( tutor()->lesson_post_type, tutor()->quiz_post_type ), true ) ) {
				$course_id = $this->resolve_course_id_from_event( $result );

				$unlock_date = self::get_unlock_date( $result->ID, $course_id );
				if ( $unlock_date['unlock_date'] ) {
					$unlock_timestamp           = strtotime( $unlock_date['unlock_date'] );
					$is_unlocked                = time() > $unlock_timestamp;
					$unlock_date['is_unlocked'] = $is_unlocked;
					$result->month              = gmdate( 'm', $unlock_timestamp );
					$result->meta_info          = $unlock_date;
				} else {
					// Remove from events if there is no unlock date.
					unset( $results[ $key ] );
				}
			} elseif ( tutor()->meet_post_type === $result->post_type ) {
				if ( class_exists( 'TutorPro\GoogleMeet\Models\EventsModel' ) ) {
					$course_id      = $this->resolve_course_id_from_event( $result );
					$start_datetime = get_post_meta( $result->ID, EventsModel::POST_META_KEYS[0], true );
					$end_datetime   = get_post_meta( $result->ID, EventsModel::POST_META_KEYS[1], true );
					$meeting_data   = json_decode( get_post_meta( $result->ID, EventsModel::POST_META_KEYS[2], true ) );
					$timezone       = $meeting_data->timezone ?? wp_timezone_string();
					$end_timestamp  = strtotime( Utilities::get_gmt_date_from_timezone_date( $end_datetime, $timezone ) );

					$result->month     = gmdate( 'm', strtotime( $start_datetime ) );
					$result->meta_info = array(
						'gm_start_date'  => gmdate( 'Y-m-d', strtotime( $start_datetime ) ),
						'start_datetime' => $start_datetime ? DateTimeHelper::create( $start_datetime, $timezone )->set_timezone( 'UTC' )->format( 'Y-m-d H:i:s' ) : '',
						'expire_date'    => $end_datetime,
						'is_expired'     => time() > $end_timestamp,
					);
				} else {
					// Remove meet.
					unset( $results[ $key ] );
				}
			}

			if ( $course_id ) {
				if ( ! isset( $course_cache[ $course_id ] ) ) {
					$course_cache[ $course_id ] = $this->get_course_info( $course_id );
				}

				if ( $course_cache[ $course_id ] ) {
					$result->course_info = $course_cache[ $course_id ];
				}
			}
		}

		$overdue  = 0;
		$upcoming = 0;
		foreach ( $results as $r ) {
			( isset( $r->meta_info['is_expired'] ) && $r->meta_info['is_expired'] ) ? $overdue++ : $upcoming++;
			if ( (int) $r->month === $month ) {
				array_push( $response, $r );
			}
		}

		// Check zoom addon enabled or not and get zoom meetings.
		$is_enabled_zm = tutor_utils()->is_addon_enabled( TUTOR_ZOOM()->basename );
		$meeting_list  = $is_enabled_zm ? $this->get_zoom_meeting_list( $enrolled_course_ids, $year, $month ) : array();

		$response = array_merge( $response, $meeting_list );

		$data = array(
			'response' => $response,
			'overdue'  => $overdue,
			'upcoming' => $upcoming,
		);

		if ( ! tutor_is_rest() ) {
			wp_send_json_success( $data );
			exit;
		} else {
			return $data;
		}
	}

	/**
	 * Get post unlock date
	 *
	 * Basically the unlock date of lesson, quiz & assignment
	 *
	 * @since 2.4.0
	 *
	 * @param integer $post_id post id.
	 * @param integer $course_id course id.
	 *
	 * @return array
	 */
	public static function get_unlock_date( int $post_id, int $course_id ) {
		$res = array(
			'unlock_date' => null,
			'is_unlocked' => false,
		);

		$enable = (bool) get_tutor_course_settings( $course_id, 'enable_content_drip' );

		// If content drip not enabled then return null date.
		if ( ! $enable ) {
			return $res;
		}

		$drip_type = get_tutor_course_settings( $course_id, 'content_drip_type', 'unlock_by_date' );

		if ( 'unlock_by_date' === $drip_type || 'specific_days' === $drip_type ) {

			$unlock_date = null;

			if ( 'unlock_by_date' === $drip_type ) {
				$unlock_date = get_item_content_drip_settings( $post_id, 'unlock_date' );
			}

			if ( 'specific_days' === $drip_type ) {
				$days = (int) get_item_content_drip_settings( $post_id, 'after_xdays_of_enroll' );

				if ( $days > 0 ) {
					$enroll       = tutor_utils()->is_course_enrolled_by_lesson( $post_id );
					$enroll_date  = tutor_utils()->array_get( 'post_date', $enroll );
					$enroll_date  = gmdate( 'Y-m-d', strtotime( $enroll_date ) );
					$days_in_time = 60 * 60 * 24 * $days;

					$unlock_timestamp = strtotime( $enroll_date ) + $days_in_time;

					$unlock_date = date_i18n( get_option( 'date_format' ), $unlock_timestamp );
				}
			}

			$res['unlock_date'] = $unlock_date;
		}

		return $res;
	}
}
