<?php
/**
 * Handle H5P logic
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

use TUTOR\Addons;
use TUTOR\Input;
use TUTOR\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * H5P addon class.
 */
final class H5P extends Singleton {

	/**
	 * H5P plugin addon constructor.
	 */
	public function __construct() {

		if ( ! function_exists( 'tutor' ) ) {
			return;
		}

		$has_h5p      = tutor_utils()->is_plugin_active( 'h5p/h5p.php' );
		$addon_config = tutor_utils()->get_addon_config( Utils::addon_config()->basename );
		$is_enable    = (bool) tutor_utils()->array_get( 'is_enable', $addon_config );

		/**
		 * If h5p plugin is not activated or does not exist.
		 * Disable the h5p addon.
		 */
		if ( ! $has_h5p && $is_enable ) {
			Addons::update_addon_status( Utils::addon_config()->basename, 0 );
		}

		// Need to call before checking addon is enable.
		new Database();
		new AddonRegister();

		if ( ! $is_enable || ! $has_h5p ) {
			return;
		}
		new Quiz();
		new Lesson();
		new Analytics();
		new Assets();
		new Settings();

		/**
		 * Hook for addon enable disable
		 */
		add_action( 'tutor_addon_after_disable_' . Utils::addon_config()->basename, array( $this, 'remove_h5p' ) );

		/**
		 * Register H5P admin menu
		 */
		add_action( 'tutor_admin_register', array( $this, 'tutor_h5p_register_menu' ) );
	}


	/**
	 * Register tutor H5P admin menu.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function tutor_h5p_register_menu() {
		add_submenu_page( 'tutor', __( 'H5P', 'tutor-pro' ), __( 'H5P', 'tutor-pro' ), 'manage_tutor_instructor', 'tutor_h5p', array( $this, 'h5p_analytics_menu' ) );
	}

	/**
	 * Provide the view for H5P analytics menu
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function h5p_analytics_menu() {

		$current_sub_page = 'overview';
		$current_name     = __( 'Overview', 'tutor-pro' );
		$sub_pages        = array(
			'overview'      => __( 'Overview', 'tutor-pro' ),
			'verbs'         => __( 'Verbs', 'tutor-pro' ),
			'activities'    => __( 'Activities', 'tutor-pro' ),
			'learners'      => __( 'Learners', 'tutor-pro' ),
			'lesson-report' => __( 'Lesson Report', 'tutor-pro' ),
		);

		if ( Input::has( 'sub_page' ) ) {
			$current_sub_page = Input::get( 'sub_page' );
			$current_name     = isset( $sub_pages[ $current_sub_page ] ) ? $sub_pages[ $current_sub_page ] : '';
		}

		/**
		* Pagination data
		*/
		$paged_filter = Input::get( 'paged', 1, Input::TYPE_INT );
		$limit        = tutor_utils()->get_option( 'pagination_per_page' );
		$offset       = ( $limit * $paged_filter ) - $limit;

		/**
		* Bulk action & filters
		*/
		$filters = array(
			'bulk_action'     => false,
			'filters'         => true,
			'category_filter' => false,
			'course_filter'   => true,
		);

		/**
		 * Order filter
		 */
		$h5p_analytics_order = Input::get( 'order', 'DESC' );

		/**
		 * Search filter
		 */
		$h5p_analytics_search = Input::get( 'search', '' );

		/**
		 * Course filter
		 */
		$course_id = Input::get( 'course-id', '' );

		/**
		 * Date filter
		 */
		$date = Input::get( 'date', '' );

		$total_statements            = Analytics::get_all_statements_count();
		$total_monthly_statements    = Analytics::get_all_monthly_statements_count();
		$all_verb_statements         = Analytics::get_h5p_total_statement_count( 'verb', $limit, $offset, $h5p_analytics_order, $h5p_analytics_search, $date );
		$all_activity_statements     = Analytics::get_h5p_total_statement_count( 'activity_name', $limit, $offset, $h5p_analytics_order, $h5p_analytics_search, $date );
		$all_learners_statements     = Analytics::get_h5p_total_statement_count( 'user_id', $limit, $offset, $h5p_analytics_order, $h5p_analytics_search, $date );
		$all_verb_count              = count( Analytics::get_h5p_total_statement_count( 'verb', '', '', '', $h5p_analytics_search, $date ) );
		$all_activities_count        = count( Analytics::get_h5p_total_statement_count( 'activity_name', '', '', '', $h5p_analytics_search, $date ) );
		$all_learners_count          = count( Analytics::get_h5p_total_statement_count( 'user_id', '', '', '', $h5p_analytics_search, $date ) );
		$all_lesson_statements       = Lesson::get_h5p_lesson_statements( $limit, $offset, $h5p_analytics_order, $h5p_analytics_search, $date, $course_id );
		$all_lesson_statements_count = Lesson::count_h5p_lesson_statements( $h5p_analytics_search, $date, $course_id );
		$all_quiz_statements         = Quiz::get_h5p_quiz_statements( $limit, $offset, $h5p_analytics_order, $h5p_analytics_search, $date, $course_id );
		$all_quiz_statements_count   = Quiz::count_h5p_quiz_statements( $h5p_analytics_search, $date, $course_id );
		include Utils::addon_config()->path . 'views/analytics/h5p-analytics.php';
	}

	/**
	 * Check if addon is enabled
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$basename       = plugin_basename( TUTOR_H5P_FILE );
		$is_enabled     = tutor_utils()->is_addon_enabled( $basename );
		$has_h5p        = tutor_utils()->is_plugin_active( 'h5p/h5p.php' );
		$plugin_enabled = $is_enabled && $has_h5p;
		return $plugin_enabled;
	}


	/**
	 * Handle tutor H5P addon disable.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function remove_h5p() {
		global $wpdb;

		$wpdb->query(
			"DROP TABLE IF EXISTS {$wpdb->prefix}tutor_h5p_quiz_result, {$wpdb->prefix}tutor_h5p_quiz_statement, {$wpdb->prefix}tutor_h5p_lesson_statement, {$wpdb->prefix}tutor_h5p_statement "
		);
	}
}
