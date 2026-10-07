<?php
/**
 * Zoom Addon Helper
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Includes
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

/**
 * Check API connection
 *
 * @return mixed
 */
function tutor_zoom_check_api_connection() {
	$user_id    = get_current_user_id();
	$settings   = json_decode( get_user_meta( $user_id, 'tutor_zoom_api', true ), true );
	$api_key    = ( ! empty( $settings['api_key'] ) ) ? $settings['api_key'] : '';
	$api_secret = ( ! empty( $settings['api_secret'] ) ) ? $settings['api_secret'] : '';

	return ( $api_key && $api_secret );
}

/**
 * Get zoom meeting data by meeting post id
 *
 * @param int $meeting_id meeting post id.
 *
 * @return object
 */
function tutor_zoom_meeting_data( $meeting_id ) {
	$meeting_data = get_post_meta( $meeting_id, '_tutor_zm_data', true );
	$meeting_data = json_decode( stripslashes( $meeting_data ), true );
	$meeting_date = isset( $meeting_data['start_time'] ) ? new DateTime( $meeting_data['start_time'], new DateTimeZone( 'UTC' ) ) : new DateTime();
	$timezone     = isset( $meeting_data['timezone'] ) ? $meeting_data['timezone'] : 'UTC';
	$meeting_date->setTimezone( new DateTimeZone( $timezone ) );
	$countdown_date = $meeting_date->format( 'Y-m-d H:i:s' );
	$start_date     = $meeting_date->format( 'Y-m-d H:i:s' );
	$meeting_unix   = $meeting_date->format( 'U' );
	$is_started     = ( $meeting_unix > time() ) ? false : true;
	$is_expired     = true;
	$end_date       = $start_date;
	if ( isset( $meeting_data['duration'] ) ) {
		$end_time   = $meeting_unix + ( $meeting_data['duration'] * MINUTE_IN_SECONDS );
		$end_date   = wp_date( 'Y-m-d H:i:s', $end_time, new DateTimeZone( $timezone ) );
		$is_expired = time() > $end_time;
	}

	return (object) array(
		'data'           => $meeting_data,
		'timezone'       => $timezone,
		'start_date'     => $start_date,
		'countdown_date' => $countdown_date,
		'is_started'     => $is_started,
		'is_expired'     => $is_expired,
		'end_date'       => $end_date,
	);
}
