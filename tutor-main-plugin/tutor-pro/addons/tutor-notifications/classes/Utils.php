<?php
/**
 * Utility helper for notification addon
 *
 * @package TutorPro\Addons
 * @subpackage Notification
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.9.10
 */

namespace TUTOR_NOTIFICATIONS;

defined( 'ABSPATH' ) || exit;

use Tutor\Helpers\QueryHelper;
use Tutor\Helpers\UrlHelper;

/**
 * Utils class
 */
class Utils {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( file_exists( 'pluggable.php' ) ) {
			include ABSPATH . 'wp-includes/pluggable.php';
		}
	}

	/**
	 * Save onsite notification data.
	 *
	 * @since 2.2.5
	 *
	 * @param array $data notification data.
	 *
	 * @return int
	 */
	public static function save_notification_data( array $data ) {
		/**
		 * Save GMT - datetime in mysql format 'Y-m-d H:i:s'
		 */
		$data['created_at'] = current_time( 'mysql', true );

		global $wpdb;

		$data            = apply_filters( 'tutor_before_insert_notification_data', $data );
		$notification_id = QueryHelper::insert( 'tutor_notifications', $data );

		do_action( 'tutor_after_insert_notification_data', $notification_id );

		return $notification_id;
	}

	/**
	 * Get all notifications of current user
	 *
	 * @return array $notifications
	 */
	public function get_all_notifications_by_current_user() {
		global $wpdb;
		$current_user_id = absint( get_current_user_id() );

		$notifications = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->tutor_notifications
				WHERE receiver_id = %d
				ORDER BY created_at DESC",
				$current_user_id
			)
		);

		$notifications = array_map(
			function ( $row ) {
				static $thumbnail_cache = array();

				$current_date_obj      = new \DateTime( current_time( 'mysql' ) );
				$notification_date_obj = new \DateTime( $row->created_at );
				$interval              = $current_date_obj->diff( $notification_date_obj );

				if ( $interval->days >= 1 ) {
					$row->created_at_readable = tutor_utils()->convert_date_into_wp_timezone( $row->created_at, get_option( 'date_format' ) );
				} else {
					/* translators: The placeholder is a human_time_diff */
					$row->created_at_readable = sprintf( __( '%s ago', 'tutor-pro' ), human_time_diff( strtotime( $row->created_at ) ) );
				}

				$post_id = isset( $row->post_id ) ? (int) $row->post_id : 0;

				if ( $post_id > 0 ) {
					if ( ! array_key_exists( $post_id, $thumbnail_cache ) ) {
						$thumbnail_cache[ $post_id ] = get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '';
					}

					$row->thumbnail_url = $thumbnail_cache[ $post_id ];
				} else {
					$row->thumbnail_url = '';
				}

				/**
				 * Added query param to the URL to ensure users are redirected to the announcements page after clicking on announcement type notification.
				 *
				 * @since 4.0.0
				 */
				$row->topic_url = $this->format_topic_url( $row );

				return $row;
			},
			$notifications
		);

		return $notifications;
	}

	/**
	 * Get latest notification of current user
	 */
	public function get_latest_notification_by_current_user() {
		global $wpdb;
		$current_user_id = get_current_user_id();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->tutor_notifications
				WHERE receiver_id = %d
				ORDER BY created_at DESC
				LIMIT 1",
				$current_user_id
			)
		);

		return $row;
	}

	/**
	 * Mark all notifications status as read
	 *
	 * @return bool
	 */
	public function mark_all_notifications_as_read() {
		global $wpdb;

		$current_user_id = absint( get_current_user_id() );
		$tablename       = $wpdb->tutor_notifications;
		$updated_status  = array(
			'status' => 'READ',
		);

		$where_clause = array(
			'receiver_id' => $current_user_id,
			'status'      => 'UNREAD',
		);

		$status_updated = $wpdb->update( $tablename, $updated_status, $where_clause );

		return $status_updated;
	}

	/**
	 * Mark all notifications status as unread
	 *
	 * @return bool
	 */
	public function mark_all_notifications_as_unread() {
		global $wpdb;

		$current_user_id = absint( get_current_user_id() );
		$tablename       = $wpdb->tutor_notifications;
		$updated_status  = array(
			'status' => 'UNREAD',
		);

		$where_clause = array(
			'receiver_id' => $current_user_id,
			'status'      => 'READ',
		);

		$status_updated = $wpdb->update( $tablename, $updated_status, $where_clause );

		return $status_updated;
	}

	/**
	 * Mark a single notification status as read
	 *
	 * @param int $notification_id notification id.
	 *
	 * @return bool
	 */
	public function mark_single_notification_as_read( $notification_id ) {
		global $wpdb;

		$current_user_id = absint( get_current_user_id() );
		$tablename       = $wpdb->tutor_notifications;
		$updated_status  = array(
			'status' => 'READ',
		);

		$where_clause = array(
			'ID'          => (int) $notification_id,
			'receiver_id' => $current_user_id,
			'status'      => 'UNREAD',
		);

		$status_updated = $wpdb->update( $tablename, $updated_status, $where_clause );

		return $status_updated;
	}

	/**
	 * Delete all notifications
	 *
	 * @return void
	 */
	public function delete_all_notifications_by_user() {
		global $wpdb;

		$current_user_id = absint( get_current_user_id() );
		$tablename       = $wpdb->tutor_notifications;

		$where_clause = array(
			'receiver_id' => $current_user_id,
		);

		$wpdb->delete( $tablename, $where_clause );
	}

	/**
	 * Format and resolve the topic URL based on notification type.
	 *
	 * @since 4.0.0
	 *
	 * @param object $notification Notification data object.
	 *
	 * @return string | null Formatted topic URL.
	 */
	private function format_topic_url( object $notification ): ?string {

		switch ( $notification->type ) {

			case 'Announcements':
				$topic_url = get_permalink( $notification->post_id );

				return tutor_utils()->is_legacy_learning_mode()
						? $topic_url
						: UrlHelper::add_query_params(
							$topic_url,
							array( 'subpage' => 'announcements' )
						);

			case 'Q&A':
				$query = wp_parse_url( $notification->topic_url, PHP_URL_QUERY );
				wp_parse_str( $query, $params );

				$question_id  = $params['question_id'] ?? 0;
				$previous_url = tutor_utils()->tutor_dashboard_url( 'question-answer?question_id=' . $question_id );

				if ( $previous_url === $notification->topic_url ) {
					return UrlHelper::add_query_params(
						tutor_utils()->get_tutor_dashboard_page_permalink( 'discussions' ),
						array(
							'tab' => 'qna',
							'id'  => $question_id,
						)
					);
				}

				return $notification->topic_url;

			case 'Quiz':
				$previous_url = tutor_utils()->tutor_dashboard_url( 'my-quiz-attempts' );

				return $previous_url === $notification->topic_url
						? tutor_utils()->tutor_dashboard_url( 'courses/my-quiz-attempts' )
						: $notification->topic_url;

			default:
				return $notification->topic_url;
		}
	}
}
