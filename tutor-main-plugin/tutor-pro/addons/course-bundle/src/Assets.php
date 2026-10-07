<?php
/**
 * Manage Assets.
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle;

use Tutor\Ecommerce\Tax;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;

/**
 * Assets Class.
 *
 * @since 2.2.0
 */
class Assets {
	/**
	 * Register hooks.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_backend_assets' ) );

		// Common assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'load_common_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_common_assets' ) );
		add_filter( 'tutor_localize_data', array( $this, 'localize_course_bundle_builder_data' ) );

		add_filter( 'tutor_check_course_post_type', array( $this, 'add_bundle_post_type_check' ), 10, 2 );
	}

	/**
	 * Check post type for course and bundle.
	 *
	 * @since 3.5.0
	 * @since 3.8.0 param $check_post_type added.
	 *
	 * @param bool   $check_post_type check whether it has correct post type.
	 * @param string $post_type the post type to check.
	 *
	 * @return bool
	 */
	public function add_bundle_post_type_check( $check_post_type, $post_type ) {
		return in_array( $post_type, array( CourseModel::POST_TYPE, CourseBundle::POST_TYPE ) );
	}


	/**
	 * Localize custom course bundle builder data for _tutorobject.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data the localized data.
	 *
	 * @return array
	 */
	public function localize_course_bundle_builder_data( $data ) {
		if ( Utils::is_bundle_editor() ) {
			$full_settings                             = get_option( 'tutor_option', array() );
			$settings['chatgpt_enable']                = $full_settings['chatgpt_enable'] ?? '';
			$settings['chatgpt_key_exist']             = ! empty( $full_settings['chatgpt_api_key'] ?? '' );
			$settings['instructor_can_delete_course']  = $full_settings['instructor_can_delete_course'] ?? '';
			$settings['instructor_can_publish_course'] = $full_settings['instructor_can_publish_course'] ?? '';
			$settings['hide_admin_bar_for_users']      = $full_settings['hide_admin_bar_for_users'] ?? '';
			$settings['brand_logo_light']              = wp_get_attachment_image_url( $full_settings['brand_logo_light'] ?? $full_settings['tutor_frontend_course_page_logo_id'] ?? 0, 'full' );
			$settings['enrollment_expiry_enabled']     = get_tutor_option( 'enrollment_expiry_enabled' ) ? 'on' : 'off';

			$settings['enable_tax']                    = Tax::get_setting( 'enable_tax', true );
			$settings['is_tax_included_in_price']      = Tax::is_tax_included_in_price();
			$settings['enable_individual_tax_control'] = Tax::get_setting( 'enable_individual_tax_control' );

			$data['backend_bundle_list_url']  = get_admin_url( null, 'admin.php?page=tutor' );
			$data['frontend_bundle_list_url'] = tutor_utils()->tutor_dashboard_url( 'my-courses' );
			$data['settings']                 = array_merge( $settings, $data['settings'] );
			$data['max_upload_size']          = size_format( wp_max_upload_size() );
		}

		return $data;
	}

	/**
	 * Load assets for frontend.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_frontend_assets() {
		wp_enqueue_style( 'tutor-course-bundle-frontend', Utils::asset_url( 'css/frontend.css' ), array(), TUTOR_VERSION );
	}

	/**
	 * Load assets for backend.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_backend_assets() {
		if ( is_admin() && ( 'tutor' === Input::get( 'page', '' ) || 'enrollments' === Input::get( 'page' ) || CourseBundle::POST_TYPE === get_post_type( get_the_ID() ) ) ) {
			wp_enqueue_style( 'tutor-course-bundle-backend', Utils::asset_url( 'css/backend.css' ), array(), TUTOR_VERSION );
			wp_enqueue_script( 'tutor-course-bundle-backend', Utils::asset_url( 'js/backend.js' ), array( 'jquery' ), TUTOR_VERSION, true );
		}
	}

	/**
	 * Load common assets.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function load_common_assets() {
		wp_enqueue_script( 'tutor-course-bundle-common', Utils::asset_url( 'js/common.js' ), array(), TUTOR_VERSION, true );

		wp_add_inline_script(
			'tutor-course-bundle-common',
			'var tutorProCourseBundle = ' . wp_json_encode( self::inline_script_data() ),
			'high'
		);

		if ( Utils::is_bundle_editor() ) {
			// Fix: function print_emoji_styles and wp_admin_bar_header are deprecated since WP version 6.4.0!
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'wp_head', 'wp_admin_bar_header' );
			add_action( 'wp_head', 'wp_enqueue_admin_bar_header_styles' );

			wp_enqueue_script( 'wp-tinymce' );
			wp_enqueue_script( 'mce-view' );
			wp_enqueue_editor();

			wp_enqueue_media();

			$builder_js_url  = Utils::asset_url( 'js/bundle-builder/index.js' );
			$builder_js_path = Utils::asset_path( 'js/bundle-builder/index.js' );

			wp_enqueue_script(
				'tutor-course-bundle-builder',
				$builder_js_url,
				array( 'wp-i18n', 'wp-element', 'wp-date' ),
				filemtime( $builder_js_path ),
				true
			);
		}
	}

	/**
	 * Inline script data to use in js
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public static function inline_script_data(): array {
		$is_bundle_editor = false;

		// For frontend bundle editor.
		if ( tutils()->is_tutor_frontend_dashboard() && Input::get( 'bundle-id', 0, Input::TYPE_INT ) ) {
			$is_bundle_editor = true;
		}

		// For backend bundle editor.
		if ( is_admin() && ( CourseBundle::POST_TYPE === Input::get( 'post_type', '' ) || CourseBundle::POST_TYPE === get_post_type( get_the_ID() ) ) ) {
				$is_bundle_editor = true;
		}

		$data = array(
			'is_course_bundle_editor'     => $is_bundle_editor,
			'course_bundle_list_page_url' => admin_url( 'admin.php?page=tutor' ),
			'course_bundle_post_type'     => CourseBundle::POST_TYPE,
		);

		return apply_filters( 'tutor_pro_course_bundle_inline_data', $data );
	}


}
