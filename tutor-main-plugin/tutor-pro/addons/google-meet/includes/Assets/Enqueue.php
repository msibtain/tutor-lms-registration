<?php
/**
 * Enqueue Assets, styles & scripts
 *
 * @since    v2.1.0
 *
 * @package TutorPro\GoogleMeet\Assets
 */

namespace TutorPro\GoogleMeet\Assets;

use TUTOR\Input;
use TutorPro\GoogleMeet\GoogleMeet;
use TutorPro\GoogleMeet\Models\EventsModel;

/**
 * Enqueue styles & scripts
 */
class Enqueue {

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_front_end_scripts' ) );
		add_filter( 'tutor_should_enqueue_countdown_scripts', __CLASS__ . '::load_countdown_scripts' );
	}

	/**
	 * Load admin styles & scripts
	 *
	 * @since v2.1.0
	 *
	 * @return void
	 */
	public static function load_admin_scripts(): void {
		self::enqueue_common_scripts();
	}

	/**
	 * Load front end scripts
	 *
	 * @since v2.1.0
	 *
	 * @return void
	 */
	public static function load_front_end_scripts() {
		self::enqueue_common_scripts();

		$is_single_meet_page     = ( is_single() && get_post_type() === EventsModel::POST_TYPE );
		$is_legacy_learning_mode = tutor_utils()->is_legacy_learning_mode();

		if ( $is_single_meet_page && ! $is_legacy_learning_mode ) {
			wp_enqueue_script( 'tutor_meeting_js', tutor_pro()->url . 'assets/js/live-meeting.js', array( 'tutor-core', 'wp-date' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_style( 'tutor_meeting_css', tutor_pro()->url . 'assets/css/live-meeting.css', false, TUTOR_PRO_VERSION );
		}
	}

	/**
	 * Enqueue common styles & scripts
	 *
	 * Will be used inside hooked method to reuse on both wp script & admin scripts
	 *
	 * @since v2.1.0
	 *
	 * @return void
	 */
	private static function enqueue_common_scripts() {
		global $wp_query;
		$query_vars  = $wp_query->query_vars;
		$plugin_data = GoogleMeet::meta_data();
		$page        = isset( $query_vars['tutor_dashboard_page'] ) ? $query_vars['tutor_dashboard_page'] : '';
		if ( '' === $page ) {
			$page = Input::get( 'page', '' );
		}

		$post_type = get_post_type();
		// load styles & scripts only required page.
		$has_page = in_array( $page, array( 'create-course', 'google-meet', 'live-classes' ), true );
		$has_post = in_array( $post_type, array( tutor()->course_post_type, EventsModel::POST_TYPE ), true );
		if ( $has_page || $has_post ) {
			wp_enqueue_script(
				'tutor-pro-google-meet-ui-timepicker',
				$plugin_data['assets'] . 'lib/jquery-ui-timepicker.js',
				array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
				TUTOR_PRO_VERSION,
				true
			);

			wp_enqueue_script(
				'tutor-pro-google-meet',
				$plugin_data['assets'] . 'js/scripts.js',
				array( 'jquery', 'wp-i18n' ),
				filemtime( $plugin_data['path'] . 'assets/js/scripts.js' ),
				true
			);

			wp_enqueue_script(
				'tutor-pro-google-meet-live-class',
				$plugin_data['assets'] . 'js/meet.js',
				array( 'jquery', 'wp-i18n' ),
				filemtime( $plugin_data['path'] . 'assets/js/meet.js' ),
				true
			);

			wp_enqueue_style(
				'tutor-pro-google-meet',
				$plugin_data['assets'] . 'css/google-meet.css',
				array(),
				filemtime( $plugin_data['path'] . 'assets/css/google-meet.css' ),
			);

			wp_enqueue_style(
				'tutor-pro-google-meet-jquery-ui-timepicker',
				$plugin_data['assets'] . 'css/jquery-ui-timepicker.css',
				array(),
				filemtime( $plugin_data['path'] . 'assets/css/google-meet.css' ),
			);

			wp_localize_script(
				'tutor-pro-google-meet-ui-timepicker',
				'_tutor_google_meet',
				array(
					'tutor_pro_now'         => __( 'Now', 'tutor-pro' ),
					'tutor_pro_done'        => __( 'Done', 'tutor-pro' ),
					'tutor_pro_choose_time' => __( 'Choose Time', 'tutor-pro' ),
					'tutor_pro_time'        => __( 'Time', 'tutor-pro' ),
					'tutor_pro_hour'        => __( 'Hour', 'tutor-pro' ),
					'tutor_pro_minute'      => __( 'Minute', 'tutor-pro' ),
					'tutor_pro_second'      => __( 'Second', 'tutor-pro' ),
					'tutor_pro_millisecond' => __( 'Millisecond', 'tutor-pro' ),
					'tutor_pro_microsecond' => __( 'Microsecond', 'tutor-pro' ),
					'tutor_pro_time_zone'   => __( 'Time Zone', 'tutor-pro' ),
				)
			);
		}

		// Rotate arrow icon on the frontend.
		$css = '
		.tutor-google-meet-meeting.tutor-active .tutor-icon-angle-right {
			display: inline-block;
			transform: rotate(90deg);
		}
		.tutor-google-meet-meeting .tutor-icon-angle-right {
			color: #3e64de;
			font-size: 16px;
		}';
		wp_add_inline_style( 'tutor', $css );
	}

	/**
	 * Enqueue countdown scripts by altering value
	 *
	 * @since v2.1.0
	 *
	 * @param bool $should_enqueue true if already filtered otherwise false.
	 *
	 * @return bool
	 */
	public static function load_countdown_scripts( bool $should_enqueue ) {
		global $wp_query;
		$is_single_meet_page = (
			is_single() && ! empty( $wp_query->query['post_type'] ) &&
			EventsModel::POST_TYPE === $wp_query->query['post_type']
		);
		if ( is_single_course() || $is_single_meet_page ) {
			$should_enqueue = true;
		}
		return $should_enqueue;
	}
}
