<?php
/**
 * Enqueue scripts and styles
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor H5P Assets class
 */
class Assets {

	/**
	 * Assets class constructor
	 */
	public function __construct() {
		/**
		 * Hook for scripts enqueue
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'h5p_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'h5p_alter_library_scripts', array( $this, 'add_h5p_iframe_script' ), 10, 3 );
		add_action( 'tutor_lesson/single/before/wrap', array( $this, 'add_lesson_script' ) );
		add_action( 'tutor_quiz/body/before', array( $this, 'add_quiz_script' ), 10, 1 );
	}

	/**
	 * Add script to handle h5p lesson xAPI statement.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function add_lesson_script() {
		wp_enqueue_script(
			'tutor_h5p_lesson',
			Utils::addon_config()->url . 'assets/js/lesson.js',
			array( 'jquery' ),
			filemtime( Utils::addon_config()->path . 'assets/js/lesson.js' ),
			true
		);
	}

	/**
	 * Add script to handle h5p quiz xAPI statement.
	 *
	 * @since 3.0.0
	 *
	 * @param int $quiz_id the quiz id.
	 * @return void
	 */
	public function add_quiz_script( $quiz_id ) {
		if ( tutor_utils()->is_started_quiz() ) {
			wp_enqueue_script(
				'tutor_h5p_quiz',
				Utils::addon_config()->url . 'assets/js/quiz.js',
				array( 'jquery' ),
				filemtime( Utils::addon_config()->path . 'assets/js/quiz.js' ),
				true
			);
		}
	}
	/**
	 * Enqueue frontend script for tutor H5P addon.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function h5p_scripts() {
		wp_enqueue_script(
			'tutor_h5p_modal',
			Utils::addon_config()->url . 'assets/js/modal.js',
			array( 'jquery' ),
			filemtime( Utils::addon_config()->path . 'assets/js/modal.js' ),
			true
		);

		if ( isset( $_GET['page'] ) && 'tutor_h5p' === $_GET['page'] ) {
			wp_enqueue_script(
				'tutor-pro-line-chart',
				Utils::addon_config()->url . 'assets/js/lib/Chart.bundle.min.js',
				array(),
				TUTOR_PRO_VERSION
			);

			wp_enqueue_script(
				'tutor-pro-analytics',
				Utils::addon_config()->url . 'assets/js/analytics.js',
				array(),
				TUTOR_PRO_VERSION
			);

			wp_add_inline_script(
				'tutor-pro-analytics',
				'const _tutor_h5p_analytics=' . json_encode( Utils::chart_data() ),
				'before'
			);
		}
	}

	/**
	 * Enqueue admin script for H5P admin panel.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function admin_scripts() {
		wp_enqueue_script(
			'tutor_h5p_modal',
			Utils::addon_config()->url . 'assets/js/modal.js',
			array( 'jquery' ),
			filemtime( Utils::addon_config()->path . 'assets/js/modal.js' ),
			true
		);

		if ( isset( $_GET['page'] ) && 'tutor_h5p' === $_GET['page'] ) {
			wp_enqueue_script(
				'tutor-pro-line-chart',
				Utils::addon_config()->url . 'assets/js/lib/Chart.bundle.min.js',
				array(),
				TUTOR_PRO_VERSION
			);

			wp_enqueue_script(
				'tutor-pro-analytics',
				Utils::addon_config()->url . 'assets/js/analytics.js',
				array(),
				TUTOR_PRO_VERSION
			);

			wp_add_inline_script(
				'tutor-pro-analytics',
				'const _tutor_h5p_analytics=' . json_encode( Utils::chart_data() ),
				'before'
			);

			wp_enqueue_style(
				'tutor-h5p-analytics-style',
				Utils::addon_config()->url . 'assets/css/analytics.css',
				array(),
				TUTOR_PRO_VERSION
			);

			wp_enqueue_style(
				'tutor-h5p-report-style',
				Utils::addon_config()->url . 'assets/css/report.css',
				array(),
				TUTOR_PRO_VERSION
			);
		}
	}

	/**
	 * Add iframe script on the H5P content iframe header
	 *
	 * @since 3.0.0
	 *
	 * @param array  $scripts the script list of H5P iframe to add to.
	 * @param mixed  $libraries the current library it depends on.
	 * @param string $embed_type the embed type.
	 * @return void
	 */
	public function add_h5p_iframe_script( &$scripts, $libraries, $embed_type ) {
		if ( 'iframe' === $embed_type ) {
			$scripts[] = (object) array(
				'path'    => Utils::addon_config()->url . 'assets/js/iframe.js',
				'version' => '?ver=1.0.0',
			);
		}
	}
}
