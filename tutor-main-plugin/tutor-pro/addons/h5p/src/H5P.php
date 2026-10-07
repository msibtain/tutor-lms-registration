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
use TUTOR\Quiz as TUTORQuiz;
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

		add_filter( 'tutor_filter_course_content', array( $this, 'filter_h5p_quiz_content' ), 10, 1 );
		add_filter( 'tutor_filter_lesson_sidebar', array( $this, 'filter_h5p_sidebar_contents' ), 10, 2 );
		add_filter( 'tutor_filter_attempt_answers', array( $this, 'filter_h5p_attempt_answers' ), 10, 1 );
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
		 * Register H5P admin menu
		 */
		add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Filter H5P quiz attempt answers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $answers the attempt answers to filter.
	 * @return array
	 */
	public function filter_h5p_attempt_answers( $answers ) {
		if ( ! self::is_enabled() ) {
			$answers = array_filter(
				$answers,
				function ( $answer ) {
					return 'h5p' !== $answer->question_type;
				}
			);
		}
		return $answers;
	}

	/**
	 * Filter lesson sidebar for H5P content.
	 *
	 * @since 3.0.0
	 *
	 * @param object $query the content query object.
	 * @param int    $topic_id the topic id.
	 *
	 * @return \WP_Query
	 */
	public function filter_h5p_sidebar_contents( $query, $topic_id ) {
		if ( ! self::is_enabled() ) {
			$topics_id        = tutor_utils()->get_post_id( $topic_id );
			$lesson_post_type = tutor()->lesson_post_type;
			$post_type        = array_unique( apply_filters( 'tutor_course_contents_post_types', array( $lesson_post_type, 'tutor_quiz' ) ) );

			$args = array(
				'post_type'      => $post_type,
				'post_parent'    => $topics_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => TUTORQuiz::META_QUIZ_OPTION,
						'value'   => 's:9:"quiz_type";s:14:"tutor_h5p_quiz";',
						'compare' => 'NOT LIKE',
					),
					array(
						'key'     => TUTORQuiz::META_QUIZ_OPTION,
						'compare' => 'NOT EXISTS',
					),

				),
			);

			$query = new \WP_Query( $args );
		}

		return $query;
	}

	/**
	 * Filter H5P quiz contents.
	 *
	 * @since 3.0.0
	 *
	 * @param array $current_topic the topic array.
	 * @return array
	 */
	public function filter_h5p_quiz_content( $current_topic ) {
		$contents = $current_topic['contents'];
		if ( is_array( $contents ) && count( $contents ) ) {
			$topic_contents = array();
			foreach ( $contents as $post ) {
				$quiz_option = get_post_meta( $post->ID, TUTORQuiz::META_QUIZ_OPTION, true );
				if ( isset( $quiz_option['quiz_type'] ) && 'tutor_h5p_quiz' === $quiz_option['quiz_type'] ) {
					$post->quiz_type = 'tutor_h5p_quiz';

					if ( ! self::is_enabled() ) {
						continue;
					}
				}
				array_push( $topic_contents, $post );
			}

			if ( count( $topic_contents ) ) {
				$current_topic['contents'] = $topic_contents;
			}
		}
		return $current_topic;
	}


	/**
	 * Add sub-menu.
	 *
	 * @since 3.8.0
	 *
	 * @param array $menu menu.
	 *
	 * @return array
	 */
	public function register_menu( $menu ) {
		$menu['group_three']['h5p'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'H5P', 'tutor-pro' ),
			'menu_title'  => __( 'H5P', 'tutor-pro' ),
			'capability'  => 'manage_tutor_instructor',
			'menu_slug'   => 'tutor_h5p',
			'callback'    => array( $this, 'h5p_analytics_menu' ),
		);

		return $menu;
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
}
