<?php
/**
 * Contains utilities helper methods
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\Utilities
 */

namespace TutorPro\GoogleMeet\Utilities;

use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use TutorPro\GoogleMeet\GoogleMeet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility methods
 */
class Utilities {

	const GOOGLE_MEET_TAB = 'google-meet';

	/**
	 * Available sub page keys
	 *
	 * @since v2.1.0
	 *
	 * @return array
	 */
	public static function sub_pages() {
		return apply_filters(
			'tutor_pro_google_meet_sub_pages',
			array(
				'active-meeting' => __( 'Active Meeting', 'tutor-pro' ),
				'expired'        => __( 'Expired Meeting', 'tutor-pro' ),
				'set-api'        => __( 'Set API', 'tutor-pro' ),
				'settings'       => __( 'Settings', 'tutor-pro' ),
				'help'           => __( 'Help', 'tutor-pro' ),
			)
		);
	}

	/**
	 * Sub pages
	 *
	 * @return array  available sub-pages
	 */
	public static function tabs_key_value() {
		$sub_pages     = self::sub_pages();
		$tab_key_value = array();

		foreach ( $sub_pages as $key => $sub_page ) {
			$url  = add_query_arg(
				array(
					'page' => 'google-meet',
					'tab'  => $key,
				)
			);
			$page = array(
				'key'   => $key,
				'title' => $sub_page,
				'url'   => $url,
			);
			array_push( $tab_key_value, $page );
		}

		return apply_filters(
			'tutor_pro_google_meet_sub_page_tabs',
			$tab_key_value
		);
	}

	/**
	 * Tabs for google meet frontend subpage.
	 *
	 * @since 4.0.0
	 *
	 * @param string $url the base url.
	 * @param string $active_tab the current active tab.
	 *
	 * @return array
	 */
	public static function frontend_tabs_key_value( $url = '', $active_tab = '' ) {
		$sub_pages     = self::sub_pages();
		$tab_key_value = array();

		foreach ( $sub_pages as $key => $sub_page ) {
			$page = array(
				'type'   => 'link',
				'label'  => $sub_page,
				'url'    => UrlHelper::add_query_params(
					$url,
					array(
						'nav' => self::GOOGLE_MEET_TAB,
						'tab' => $key,
					)
				),
				'active' => $key === $active_tab,
			);
			array_push( $tab_key_value, $page );
		}

		return apply_filters(
			'tutor_pro_google_meet_sub_page_tabs',
			$tab_key_value
		);
	}

	/**
	 * Get active tab key
	 *
	 * @return string
	 */
	public static function active_tab(): string {
		$default_tab = 'active-meeting';
		$tab         = Input::get( 'tab', $default_tab );

		return array_key_exists( $tab, self::sub_pages() ) ? $tab : $default_tab;
	}

	/**
	 * Load modal template
	 *
	 * A wrapper method to load any page template inside modal
	 *
	 * @param string $modal_id  modal unique id.
	 *
	 * @param string $template_path  template full path to load.
	 *
	 * @param string $header_title   modal header  title.
	 *
	 * @param array  $footer_buttons  action buttons that will visible
	 *  on the modal footer section. Ex:
	 *  [
	 *      ['label' => '', 'class' => '', 'id' => '', 'type' => 'button'],
	 *      ['label' => '', 'class' => '', 'id' => '', 'type' => 'button'],
	 * ].
	 *
	 * @param array  $hidden_args  hidden input fields.
	 * @param string $form_id  form unique id.
	 * @param string $modal_class extra class to identify which modal it is.
	 * For ex: create modal, update modal.
	 *
	 * @see tutor-pro/addons/google-meet/views/metabox/index.php for details
	 * implementation
	 *
	 * @return void
	 */
	public static function load_template_as_modal( string $modal_id, string $template_path, string $header_title, array $footer_buttons, array $hidden_args = array(), string $form_id = '', string $modal_class = '' ) {
		$plugin_data = GoogleMeet::meta_data();
		$modal       = $plugin_data['views'] . 'modal/modal.php';

		if ( file_exists( $modal ) ) {
			tutor_load_template_from_custom_path(
				$modal,
				array(
					'modal_id'       => $modal_id,
					'form_id'        => $form_id,
					'form_action'    => 'tutor_google_meet_new_meeting',
					'header_title'   => $header_title,
					'body'           => $template_path,
					'footer_buttons' => $footer_buttons,
					'hidden_args'    => $hidden_args,
					'modal_class'    => $modal_class,
				),
				false
			);
		} else {
			echo esc_html( $modal . ' not exists' );
		}
	}

	/**
	 * Get formatted event start & end date time separately
	 *
	 * @param string $start_datetime  event start datetime.
	 * @param string $end_datetime  event end datetime.
	 *
	 * @return object
	 */
	public static function get_formatted_start_end_datetime( $start_datetime, $end_datetime ) {
		$start_date = tutor_get_formated_date( get_option( 'date_format' ), $start_datetime );
		$start_time = tutor_get_formated_date( get_option( 'time_format' ), $start_datetime );
		$end_date   = tutor_get_formated_date( get_option( 'date_format' ), $end_datetime );
		$end_time   = tutor_get_formated_date( get_option( 'time_format' ), $end_datetime );
		$response   = array(
			'start_date' => $start_date,
			'start_time' => $start_time,
			'end_date'   => $end_date,
			'end_time'   => $end_time,
		);
		return (object) $response;
	}

	/**
	 * Get available meeting status
	 *
	 * @since v2.1.0
	 *
	 * @return array
	 */
	public static function meeting_status(): array {
		return array(
			'ongoing'       => __( 'Ongoing', 'tutor-pro' ),
			'expired'       => __( 'Expired', 'tutor-pro' ),
			'start_meeting' => __( 'Start Meeting', 'tutor-pro' ),
		);
	}

	/**
	 * Get UTC time from any specific timezone date
	 *
	 * @param string $datetime  string date time.
	 * @param string $timezone  timezone.
	 * @param string $format    optional date format to get formatted date.
	 *
	 * @return string date time.
	 */
	public static function get_gmt_date_from_timezone_date( $datetime, $timezone, $format = 'Y-m-d H:i:s' ) {
		$datetime = date_create( $datetime, new \DateTimeZone( $timezone ) );
		if ( false === $datetime ) {
			return gmdate( $format, 0 );
		}
		return $datetime->setTimezone( new \DateTimeZone( 'UTC' ) )->format( $format );
	}

	/**
	 * Google meeting date & time html markup
	 *
	 * @since v2.1.0
	 *
	 * @return string
	 */
	public static function date_time_markup(): string {
		ob_start();
		?>
			<div>
				<div class="tutor-v2-date-picker tutor-v2-date-picker-fd tutor-google-meet-start-date" style="width: 100%;" data-prevent_redirect="1" data-input_name="meeting_start_date" data-input_value="" data-disable_past_date="1"></div>
				<div class="tutor-form-wrap">
					<span class="tutor-icon-clock-line tutor-form-icon tutor-form-icon-reverse tutor-google-meet-start-time"></span>
					<input type="text" name="meeting_start_time" class="tutor-form-control tutor-google-meet-timepicker" data-name="meeting_start_time" autocomplete="off" placeholder="HH:MM PM" value="" >
				</div>
			</div>
			<span class="tutor-icon-minus-o tutor-icon-minus-o tutor-fs-6"></span>
			<div>
				<div class="tutor-v2-date-picker tutor-v2-date-picker-fd tutor-google-meet-end-date" style="width: 100%;" data-prevent_redirect="1" data-input_name="meeting_end_date" data-input_value="" data-disable_past_date="1"></div>
				<div class="tutor-form-wrap">
					<span class="tutor-icon-clock-line tutor-form-icon tutor-form-icon-reverse tutor-google-meet-end-time"></span>
					<input type="text" name="meeting_end_time" data-name="meeting_end_time" class="tutor-form-control tutor-google-meet-timepicker" value="" autocomplete="off" placeholder="HH:MM PM">
				</div>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Filter sub pages if App is not permitted
	 *
	 * @since v2.1.0
	 *
	 * @return void
	 */
	public static function not_permitted_sub_pages() {
		add_filter(
			'tutor_pro_google_meet_sub_pages',
			function () {
				return array(
					'set-api'  => __( 'Set API', 'tutor-pro' ),
					'settings' => __( 'Settings', 'tutor-pro' ),
					'help'     => __( 'Help', 'tutor-pro' ),
				);
			}
		);
	}

	/**
	 * Get the popover info card for google meetings.
	 *
	 * @since 4.0.0
	 *
	 * @param string $meet_link the meeting link.
	 * @param string $host_email the host email.
	 *
	 * @return string
	 */
	public static function get_google_meeting_info_card( string $meet_link, string $host_email ): string {
		$meta_data     = GoogleMeet::meta_data();
		$template_path = $meta_data['templates'] . 'google-meet-info-popover.php';

		ob_start();
		tutor_load_template_from_custom_path(
			$template_path,
			array(
				'meet_link'  => $meet_link,
				'host_email' => $host_email,
			),
			false
		);

		return ob_get_clean();
	}

	/**
	 * Get allowed tags for meeting info popover.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public static function allowed_info_popover_tags() {
		return array(
			'div'    => array(
				'class' => true,
			),
			'p'      => array(
				'class' => true,
			),
			'button' => array(
				'type'       => true,
				'class'      => true,
				'x-data'     => true,
				'x-on:click' => true,
			),
		);
	}
}
