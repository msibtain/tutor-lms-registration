<?php
/**
 * Zoom Addon
 *
 * @package TutorPro\Addons
 * @subpackage Zoom
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace TUTOR_ZOOM;

use WP_Post;
use TUTOR\Icon;
use TUTOR\Input;
use TUTOR\User;
use TUTOR\Tutor_Base;
use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Traits\JsonResponse;
use Tutor\Components\Button;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Constants\Size;
use Tutor\Helpers\QueryHelper;
use Tutor\Components\SvgIcon;
use Tutor\Helpers\UrlHelper;
use TUTOR_PRO\Dashboard;
use Zoom\Interfaces\Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Zoom
 */
class Zoom extends Tutor_Base {
	use JsonResponse;

	//phpcs:disable
	private $api_key;
	private $settings_key;
	private $zoom_meeting_post_type;
	private $zoom_meeting_base_slug;
	private $zoom_meeting_post_meta;
	//phpcs:enable

	/**
	 * Register hooks.
	 *
	 * @param boolean $register_hooks register hook or not.
	 */
	public function __construct( $register_hooks = true ) {
		parent::__construct();

		$this->api_key                = 'tutor_zoom_api';
		$this->settings_key           = 'tutor_zoom_settings';
		$this->zoom_meeting_post_type = 'tutor_zoom_meeting';
		$this->zoom_meeting_base_slug = 'tutor-zoom-meeting';
		$this->zoom_meeting_post_meta = '_tutor_zm_data';

		if ( ! $register_hooks ) {
			// This object is used for reusable method calls.
			return;
		}

		add_action( 'init', array( $this, 'register_zoom_post_types' ) );

		/**
		 * Register all admin scripts
		 *
		 * Use same admin scripts on the front end for zoom
		 *
		 * @since 1.9.4
		 */
		add_action( 'wp_loaded', array( $this, 'register_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'tutor_script_text_domain' ), 100 );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'tutor_admin_menu', array( $this, 'register_menu' ) );

		add_filter( 'tutor_course_contents_post_types', array( $this, 'tutor_course_contents_post_types' ) );

		// Saving zoom settings.
		add_action( 'wp_ajax_tutor_save_zoom_api', array( $this, 'tutor_save_zoom_api' ) );
		add_action( 'wp_ajax_tutor_save_zoom_settings', array( $this, 'tutor_save_zoom_settings' ) );

		add_action( 'tutor_course_builder_after_btn_group', array( $this, 'add_meeting_option_in_topic' ), 12, 2 );

		// Meeting modal form and save action.
		add_action( 'wp_ajax_tutor_zoom_save_meeting', array( $this, 'tutor_zoom_save_meeting' ) );
		add_action( 'wp_ajax_tutor_zoom_meeting_details', array( $this, 'ajax_zoom_meeting_details' ) );

		add_action( 'wp_ajax_tutor_zoom_delete_meeting', array( $this, 'tutor_zoom_delete_meeting' ) );

		add_action( 'tutor_course/single/before/topics', array( $this, 'tutor_zoom_course_meeting' ) );
		add_filter( 'tutor_single_content_template', array( $this, 'load_meeting_template' ), 99 );

		/**
		 * Apply filters on tutor nav items add zoom menu
		 *
		 * Load zoom template from zoom addons
		 *
		 * @since 1.9.4
		 */
		add_filter( 'load_dashboard_template_part_from_other_location', array( $this, 'load_zoom_template' ) );

		add_action( 'tutor/course/builder/content/tutor_zoom_meeting', array( $this, 'course_builder_row' ), 10, 4 );

		/**
		 * Is zoom lesson completed
		 *
		 * @since v2.0.0
		 */
		add_action( 'tutor_is_zoom_lesson_done', array( __CLASS__, 'is_zoom_lesson_done' ), 10, 3 );
		add_action( 'tutor/zoom/right_icon_area', array( $this, 'right_icon_area' ), 10, 2 );

		add_filter( 'post_type_link', array( $this, 'change_zoom_single_url' ), 1, 2 );

		/**
		 * Add zoom data to course details ajax response
		 *
		 * @since 3.0.0
		 */
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );

		// Add learning area nav item & single content loading.
		add_action( "tutor_learning_area_nav_item_{$this->zoom_meeting_post_type}", array( $this, 'render_nav_item' ), 10, 2 );
		add_action( "tutor_single_content_{$this->zoom_meeting_post_type}", array( $this, 'render_single_content' ) );
	}

	/**
	 * Change zoom meeting single URL
	 *
	 * @since 2.6.0
	 *
	 * @param string  $post_link post link.
	 * @param integer $id id.
	 *
	 * @return string
	 */
	public function change_zoom_single_url( $post_link, $id = 0 ) {
		$post = get_post( $id );

		if ( is_object( $post ) && 'tutor_zoom_meeting' === $post->post_type ) {
			$course_id = tutor_utils()->get_course_id_by( 'zoom_lesson', $post->ID );
			$course    = get_post( $course_id );

			if ( is_object( $course ) ) {
				return home_url( "/{$this->course_base_permalink}/{$course->post_name}/zoom-lessons/" . $post->post_name . '/' );
			} else {
				return home_url( "/{$this->course_base_permalink}/sample-course/zoom-lessons/" . $post->post_name . '/' );
			}
		}

		return $post_link;
	}

	/**
	 * Course builder row.
	 *
	 * @param object $meeting meeting.
	 * @param  object $topic topic.
	 * @param int    $course_id course id.
	 * @param mixed  $index index.
	 * @return void
	 */
	public function course_builder_row( $meeting, $topic, $course_id, $index = null ) {
		$row_id           = 'tutor-zoom-meeting-' . $meeting->ID;
		$id_string_delete = 'tutor-zoom-meet-del-modal-id' . $meeting->ID;
		?>
		<div data-course_content_id="<?php echo esc_attr( $meeting->ID ); ?>" id="<?php echo esc_attr( $row_id ); ?>" class="course-content-item tutor-zoom-meeting-item tutor-zoom-meeting-<?php echo esc_attr( $meeting->ID ); ?>">
			<div class="tutor-course-content-top tutor-d-flex tutor-align-center">
				<span class="tutor-icon-hamburger-menu tutor-cursor-move tutor-px-12"></span>
				<a href="javascript:;" class="<?php echo $topic->ID > 0 ? 'tutor-zoom-meeting-modal-open-btn' : ''; ?>" data-tutor-modal-target="<?php echo $topic->ID > 0 ? 'tutor-zoom-modal-cb-' . esc_attr( $meeting->ID ) : ''; ?>">
					<?php echo esc_html__( 'Zoom', 'tutor-pro' ) . ' ' . esc_html( $index ) . ': ' . esc_html( stripslashes( $meeting->post_title ) ); ?>
				</a>

				<div class="tutor-course-content-top-right-action">
					<?php if ( $topic->ID > 0 ) : ?>
						<a href="javascript:;" class="tutor-zoom-meeting-modal-open-btn tutor-iconic-btn" data-tutor-modal-target="tutor-zoom-modal-cb-<?php echo esc_attr( $meeting->ID ); ?>">
							<span class="tutor-icon-edit" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

					<a href="javascript:;" class="tutor-iconic-btn" data-tutor-modal-target="<?php echo esc_attr( $id_string_delete ); ?>">
						<span class="tutor-icon-trash-can-line" aria-hidden="true"></span>
					</a>
				</div>
			</div>
			<?php
				// Update Modal.
				$this->tutor_zoom_meeting_modal_content( $meeting->ID, $topic->ID, $course_id, 'course-builder', 'tutor-zoom-modal-cb-' . $meeting->ID );
				// Delete confirmation modal.
				tutor_load_template(
					'modal.confirm',
					array(
						'id'      => $id_string_delete,
						'image'   => 'icon-trash.svg',
						'title'   => __( 'Do You Want to Delete This Meeting?', 'tutor-pro' ),
						'content' => __( 'Are you sure you want to delete this meeting permanently? Please confirm your choice.', 'tutor-pro' ),
						'yes'     => array(
							'text'  => __( 'Yes, Delete This', 'tutor-pro' ),
							'class' => 'tutor-list-ajax-action',
							'attr'  => array(
								'data-request_data=\'{"meeting_id":"' . $meeting->ID . '", "action":"tutor_zoom_delete_meeting"}\'',
								'data-delete_element_id="' . $row_id . '"',
							),
						),
					)
				);
			?>
		</div>
		<?php
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register_zoom_post_types() {

		$labels = array(
			'name'               => _x( 'Meetings', 'post type general name', 'tutor-pro' ),
			'singular_name'      => _x( 'Meeting', 'post type singular name', 'tutor-pro' ),
			'menu_name'          => _x( 'Meetings', 'admin menu', 'tutor-pro' ),
			'name_admin_bar'     => _x( 'Meeting', 'add new on admin bar', 'tutor-pro' ),
			'add_new'            => _x( 'Add New', $this->zoom_meeting_post_type, 'tutor-pro' ),//phpcs:disable
			'add_new_item'       => __( 'Add New Meeting', 'tutor-pro' ),
			'new_item'           => __( 'New Meeting', 'tutor-pro' ),
			'edit_item'          => __( 'Edit Meeting', 'tutor-pro' ),
			'view_item'          => __( 'View Meeting', 'tutor-pro' ),
			'all_items'          => __( 'Meetings', 'tutor-pro' ),
			'search_items'       => __( 'Search Meetings', 'tutor-pro' ),
			'parent_item_colon'  => __( 'Parent Meetings:', 'tutor-pro' ),
			'not_found'          => __( 'No Meeting found.', 'tutor-pro' ),
			'not_found_in_trash' => __( 'No Meetings found in Trash.', 'tutor-pro' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Description.', 'tutor-pro' ),
			'public'              => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => $this->zoom_meeting_base_slug ),
			'menu_icon'           => 'dashicons-list-view',
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor' ),
			'exclude_from_search' => true,
		);

		register_post_type( $this->zoom_meeting_post_type, $args );
	}

	/**
	 * Register all admin scripts so that later
	 *
	 * we can enqueue on admin_scripts hook or wp_enqueue_scripts hook
	 *
	 * @since 1.9.4
	 */
	public function register_admin_scripts() {
		wp_register_script( 'tutor_zoom_timepicker_js', TUTOR_ZOOM()->url . 'assets/lib/jquery-ui-timepicker.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), TUTOR_PRO_VERSION, true );
		wp_register_script( 'tutor_zoom_admin_js', TUTOR_ZOOM()->url . 'assets/js/admin.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
		wp_register_script( 'tutor_zoom_common_js', TUTOR_ZOOM()->url . 'assets/js/common.js', array( 'jquery', 'jquery-ui-datepicker' ), TUTOR_PRO_VERSION, true );
		wp_register_style( 'tutor_zoom_timepicker_css', TUTOR_ZOOM()->url . 'assets/css/jquery-ui-timepicker.css', false, TUTOR_PRO_VERSION );
		wp_register_style( 'tutor_zoom_common_css', TUTOR_ZOOM()->url . 'assets/css/common.css', false, TUTOR_PRO_VERSION );
		wp_register_style( 'tutor_zoom_admin_css', TUTOR_ZOOM()->url . 'assets/css/admin.css', false, TUTOR_PRO_VERSION );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function admin_scripts() {
		wp_enqueue_script( 'tutor_zoom_timepicker_js' );
		wp_enqueue_script( 'tutor_zoom_admin_js' );
		wp_enqueue_script( 'tutor_zoom_common_js' );

		wp_enqueue_style( 'tutor_zoom_timepicker_css' );
		wp_enqueue_style( 'tutor_zoom_common_css' );
		wp_enqueue_style( 'tutor_zoom_admin_css' );
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function frontend_scripts() {
		global $wp_query;
		$is_single_zoom_page     = ( is_single() && ! empty( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] === 'tutor_zoom_meeting' );
		$is_legacy_learning_mode = tutor_utils()->is_legacy_learning_mode();

		if ( is_single_course() || $is_single_zoom_page ) {
			wp_enqueue_script( 'tutor_zoom_moment_js', TUTOR_ZOOM()->url . 'assets/lib/moment.min.js', array(), TUTOR_PRO_VERSION, true );
			wp_enqueue_script( 'tutor_zoom_moment_tz_js', TUTOR_ZOOM()->url . 'assets/lib/moment-timezone-with-data.min.js', array(), TUTOR_PRO_VERSION, true );
			wp_enqueue_script( 'tutor_zoom_countdown_js', TUTOR_ZOOM()->url . 'assets/lib/jquery.countdown.min.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
			
			wp_enqueue_script( 'tutor_zoom_frontend_js', TUTOR_ZOOM()->url . 'assets/js/frontend.js', array( 'jquery', 'wp-date' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_style( 'tutor_zoom_frontend_css', TUTOR_ZOOM()->url . 'assets/css/frontend.css', false, TUTOR_PRO_VERSION );

			if ( $is_single_zoom_page && ! $is_legacy_learning_mode ) {
				wp_enqueue_script( 'tutor_zoom_live_meeting_js', tutor_pro()->url . 'assets/js/live-meeting.js', array( 'tutor-core', 'wp-date' ), TUTOR_PRO_VERSION, true );
				wp_enqueue_style( 'tutor_zoom_live_meeting_css', tutor_pro()->url . 'assets/css/live-meeting.css', false, TUTOR_PRO_VERSION );
			}
		}

		if ( tutor_utils()->is_dashboard_page( Dashboard::LIVE_CLASSES_MENU ) ) {
			wp_enqueue_script( 'tutor_zoom_dashboard_js', TUTOR_ZOOM()->url . 'assets/js/dashboard.js', array( 'tutor-core' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_style( 'tutor_zoom_dashboard_css', TUTOR_ZOOM()->url . 'assets/css/dashboard.css', false, TUTOR_PRO_VERSION );
		}
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
		$menu['group_three']['zoom'] = array(
			'parent_slug' => 'tutor',
			'page_title'  => __( 'Zoom', 'tutor-pro' ),
			'menu_title'  => __( 'Zoom', 'tutor-pro' ),
			'capability'  => 'manage_tutor_instructor',
			'menu_slug'   => 'tutor_zoom',
			'callback'    => array( $this, 'tutor_zoom' ),
		);

		return $menu;
	}

	public function tutor_course_contents_post_types( $post_types ) {
		$post_types[] = $this->zoom_meeting_post_type;

		return $post_types;
	}

	public function add_meeting_option_in_topic( $topic_id, $course_id ) {
		$user_id    = get_current_user_id();
		$settings   = json_decode( get_user_meta( $user_id, $this->api_key, true ), true );
		$api_key    = ( ! empty( $settings['api_key'] ) ) ? $settings['api_key'] : '';
		$api_secret = ( ! empty( $settings['api_secret'] ) ) ? $settings['api_secret'] : '';
		$new_modal_ = 'tutor-zoom-new-lesson-' . $topic_id;

		if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
			?>
			<button class="tutor-btn tutor-btn-outline-primary tutor-btn-sm" data-tutor-modal-target="<?php echo $new_modal_; ?>">
				<i class="tutor-icon-brand-zoom tutor-mr-8" aria-hidden="true"></i>
				<?php _e( 'Zoom Live Lesson', 'tutor-pro' ); ?>
			</button>
			<?php

			$this->tutor_zoom_meeting_modal_content( 0, $topic_id, $course_id, 'course-builder', $new_modal_ );
		}
	}

	/**
	 * Get zoom meeting details
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_zoom_meeting_details() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$topic_id   = Input::post( 'topic_id', 0, Input::TYPE_INT );
		$meeting_id = Input::post( 'meeting_id', 0, Input::TYPE_INT );
		

		if ( ! tutor_utils()->can_user_manage( 'topic', $topic_id ) ) {
			$this->json_response(
				tutor_utils()->error_message(),
				null,
				HttpHelper::STATUS_FORBIDDEN
			);
		}
		
		$post = get_post( $meeting_id );
		if ( ! $post ) {
			$this->json_response(
				__( 'Invalid meeting id', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$meeting_data = get_post_meta( $meeting_id, $this->zoom_meeting_post_meta, true );
		$meeting_data = json_decode( $meeting_data, true );

		$duration_unit = get_post_meta( $meeting_id, '_tutor_zm_duration_unit', true );
		if ( false === $duration_unit ) {
			$duration_unit = 'min';
		}

		$meeting_data['duration_unit'] = $duration_unit;

		if ( 'hr' === $duration_unit ) {
			$meeting_data['duration'] = $meeting_data['duration'] / 60;
		}

		$start_datetime = get_post_meta( $meeting_id, '_tutor_zm_start_datetime', true );
		if ( false !== $start_datetime ) {
			$post->meeting_starts_at = $start_datetime;
		}

		$post->meeting_data = $meeting_data;

		$data = apply_filters( 'tutor_zoom_meeting_details_response', $post, $meeting_id );

		$this->json_response(
			__('Zoom meeting fetched successfully','tutor-pro'),
			$data
		);

	}

	public function tutor_zoom_meeting_modal_content( $meeting_id, $topic_id, $course_id, $click_form, $modal_id = null ) {

		$meeting_host     = $this->get_users_options();
		$timezone_options = require dirname( __DIR__ ) . '/includes/timezone.php';
		$modal_id         = $modal_id ? $modal_id : 'tutor-zoom-meeting-modal-' . $meeting_id;

		$post         = null;
		$meeting_data = null;
		if ( 0 != $meeting_id ) {
			$post          = get_post( $meeting_id );
			$meeting_start = get_post_meta( $meeting_id, '_tutor_zm_start_datetime', true );
			$meeting_data  = get_post_meta( $meeting_id, $this->zoom_meeting_post_meta, true );
			$meeting_data  = json_decode( $meeting_data, true );
		}

		! is_array( $meeting_data ) ? $meeting_data = null : 0;

		$start_date     = '';
		$start_time     = '';
		$host_id        = ( ! empty( $meeting_data ) && ! empty( $meeting_data['host_id'] ) ) ? $meeting_data['host_id'] : '';
		$title          = ( ! empty( $meeting_data ) && ! empty( $meeting_data['topic'] ) ) ? wp_strip_all_tags( $meeting_data['topic'] ) : '';
		$summary        = ( ! empty( $post ) && ! empty( $post->post_content ) ) ? $post->post_content : '';
		$timezone       = ( ! empty( $meeting_data ) && ! empty( $meeting_data['timezone'] ) ) ? $meeting_data['timezone'] : '';
		$duration       = ( ! empty( $meeting_data ) && ! empty( $meeting_data['duration'] ) ) ? $meeting_data['duration'] : 60;
		$duration_unit  = ! empty( $post ) ? get_post_meta( $meeting_id, '_tutor_zm_duration_unit', true ) : 'min';
		$password       = ( ! empty( $meeting_data ) && ! empty( $meeting_data['password'] ) ) ? $meeting_data['password'] : '';
		$auto_recording = ( ! empty( $meeting_data ) && ! empty( $meeting_data['settings'] ) ) ? $meeting_data['settings']['auto_recording'] : $this->get_settings( 'auto_recording' );

		// Fallback meeting title
		if ( empty( $title ) && ! empty( $post ) ) {
			$title = $post->post_title;
		}

		if ( ! empty( $meeting_data ) ) {
			$input_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $meeting_start );
			$start_date = $input_date->format( 'd/m/Y' );
			$start_time = $input_date->format( 'h:i A' );
			$duration   = ( $duration_unit == 'hr' ) ? $duration / 60 : $duration;
		}

		require TUTOR_ZOOM()->path . 'views/template/meeting-editor.php';
	}

	/**
	 * Save meeting
	 */
	public function tutor_zoom_save_meeting() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		// Check if API key updated.
		if ( ! $this->has_account_id() ) {
			$this->json_response(
				__( 'Invalid Api Credentials', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		// Prepare linking data.
		$meeting_id          = Input::post( 'meeting_id', 0, Input::TYPE_INT );
		$existing_meeting_id = $meeting_id;
		$topic_id            = Input::post( 'topic_id', 0, Input::TYPE_INT );
		$course_id           = Input::post( 'course_id', 0, Input::TYPE_INT );
		$click_form          = Input::post( 'click_form', '');

		// Prepare auth data.
		$current_user  = get_current_user_id();
		$zoom_settings = null;

		$course        = get_post( $course_id );
		$user_id       = $course->post_author;
		$user_settings = get_user_meta( $user_id, $this->api_key, true );
		if ( $user_settings ) {
			$zoom_settings = $user_settings;
		} else {
			$zoom_settings = get_user_meta( $current_user, $this->api_key, true );
		}

		$settings   = json_decode( $zoom_settings, true );
		$api_key    = ( ! empty( $settings['api_key'] ) ) ? $settings['api_key'] : '';
		$api_secret = ( ! empty( $settings['api_secret'] ) ) ? $settings['api_secret'] : '';


		// Respond error if api key or secret not set
		if ( empty( $api_key ) || empty( $api_secret ) ) {
			$this->json_response(
				__( 'Invalid Api Credentials', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		// Validate post value
		$post_keys = array(
			'meeting_host',
			'meeting_title',
			'meeting_timezone',
			'meeting_date',
			'meeting_time',
		);

		foreach ( $post_keys as $key ) {
			if ( empty( $_POST[ $key ] ) ) {
				$this->json_response(
					__( 'All fields required!', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_UNPROCESSABLE_ENTITY
				);
				return;
			}
		}

		// If set, then create the meeting
		$host_id    = sanitize_text_field( $_POST['meeting_host'] );
		$title      = sanitize_text_field( $_POST['meeting_title'] );
		$summary    = ! empty( $_POST['meeting_summary'] ) ? sanitize_text_field( $_POST['meeting_summary'] ) : '';
		$timezone   = sanitize_text_field( $_POST['meeting_timezone'] );
		$start_date = sanitize_text_field( $_POST['meeting_date'] );
		$start_time = sanitize_text_field( $_POST['meeting_time'] );

		$input_duration = ! empty( $_POST['meeting_duration'] ) ? intval( $_POST['meeting_duration'] ) : 60;
		$duration_unit  = ! empty( $_POST['meeting_duration_unit'] ) ? $_POST['meeting_duration_unit'] : 'min';
		$password       = ! empty( $_POST['meeting_password'] ) ? sanitize_text_field( $_POST['meeting_password'] ) : '';

		$join_before_host   = ( $this->get_settings( 'join_before_host' ) ) ? true : false;
		$host_video         = ( $this->get_settings( 'host_video' ) ) ? true : false;
		$participants_video = ( $this->get_settings( 'participants_video' ) ) ? true : false;
		$mute_participants  = ( $this->get_settings( 'mute_participants' ) ) ? true : false;
		$enforce_login      = ( $this->get_settings( 'enforce_login' ) ) ? true : false;
		$auto_recording     = ! empty( $_POST['auto_recording'] ) ? sanitize_text_field( $_POST['auto_recording'] ) : '';

		if ( false === date_create_from_format( 'd/m/Y', $start_date ) ) {
			$start_date = tutor_get_formated_date( 'd/m/Y', $start_date );
		}

		$input_date = \DateTime::createFromFormat( 'd/m/Y h:i A', $start_date . ' ' . $start_time );
		if ( ! $input_date ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Time Format', 'tutor-pro' ) ) );
			return;
		}

		$meeting_start = $input_date->format( 'Y-m-d\TH:i:s' );

		$duration = ( $duration_unit == 'hr' ) ? $input_duration * 60 : $input_duration;
		$data     = array(
			'topic'      => $title,
			'type'       => 2,
			'start_time' => $meeting_start,
			'timezone'   => $timezone,
			'duration'   => $duration,
			'password'   => $password,
			'settings'   => array(
				'join_before_host'  => $join_before_host,
				'host_video'        => $host_video,
				'participant_video' => $participants_video,
				'mute_upon_entry'   => $mute_participants,
				'auto_recording'    => $auto_recording,
				'enforce_login'     => $enforce_login,
			),
		);

		// save post
		$menu_order = tutor_utils()->get_next_course_content_order_id( $topic_id, $meeting_id );

		$post_content = array(
			'ID'           => $meeting_id ? $meeting_id : 0,
			'post_title'   => $title,
			'post_name'    => sanitize_title( $title ),
			'post_content' => $summary,
			'post_type'    => $this->zoom_meeting_post_type,
			'post_parent'  => $topic_id ? $topic_id : $course_id,
			'post_status'  => 'publish',
			'menu_order'   => $menu_order,
			'post_author'  => $user_id,
		);

		// save zoom meeting
		$post_id = wp_insert_post( $post_content );
		if ( ! $meeting_id ) {
			$meeting_id = $post_id;
		}

		$meeting_data = get_post_meta( $post_id, $this->zoom_meeting_post_meta, true );
		$meeting_data = json_decode( $meeting_data, true );

		// Interact with zoom
		$zoom_endpoint = tutor_utils()->get_package_object( true, '\Zoom\Endpoint\Meetings', $api_key, $api_secret );
		if ( ! empty( $meeting_data ) && isset( $meeting_data['id'] ) ) {
			// Update existing meeting id if id provided
			$zoom_endpoint->update( $meeting_data['id'], $data );
			$saved_meeting = $zoom_endpoint->meeting( $meeting_data['id'] );
			if ( isset( $saved_meeting['message'] ) ) {
				$this->response_bad_request( $saved_meeting['message'] );
			}
			do_action( 'tutor_zoom_after_update_meeting', $post_id );
		} else {
			// Or create new meeting
			$saved_meeting = $zoom_endpoint->create( $host_id, $data );
			if ( isset( $saved_meeting['message'] ) ) {
				$this->response_bad_request( $saved_meeting['message'] );
			}
			update_post_meta( $post_id, '_tutor_zm_for_course', $course_id );
			update_post_meta( $post_id, '_tutor_zm_for_topic', $topic_id );

			do_action( 'tutor_zoom_after_save_meeting', $post_id );
		}

		// Update meeting meta data
		update_post_meta( $post_id, '_tutor_zm_start_date', $input_date->format( 'Y-m-d' ) );
		update_post_meta( $post_id, '_tutor_zm_start_datetime', $input_date->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, '_tutor_zm_duration', $input_duration );
		update_post_meta( $post_id, '_tutor_zm_duration_unit', $duration_unit );
		update_post_meta( $post_id, $this->zoom_meeting_post_meta, json_encode( $saved_meeting ) );

		if ( $existing_meeting_id ) {
			$this->json_response( __( 'Meeting Successfully Updated', 'tutor-pro' ), $meeting_id );
		} else{
			$this->json_response( __( 'Meeting Successfully Added', 'tutor-pro' ), $meeting_id, HttpHelper::STATUS_CREATED );
		}
		
	}

	/**
	 * Delete meeting
	 */
	public function tutor_zoom_delete_meeting() {
		tutor_utils()->checking_nonce();

		$post_id = (int) sanitize_text_field( $_POST['meeting_id'] );
		$course_id = wp_get_post_parent_id( $post_id );
		if ( tutor()->topics_post_type === get_post_type( $course_id ) ) {
			$course_id = wp_get_post_parent_id( $course_id );
		}

		if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		// Check if API key updated.
		if ( ! $this->has_account_id() ) {
			$this->response_bad_request( __( 'Invalid Api Credentials', 'tutor-pro' ) );
		}

		$user_id    = get_current_user_id();
		$settings   = json_decode( get_user_meta( $user_id, $this->api_key, true ), true );
		$api_key    = ( ! empty( $settings['api_key'] ) ) ? $settings['api_key'] : '';
		$api_secret = ( ! empty( $settings['api_secret'] ) ) ? $settings['api_secret'] : '';

		if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
			$meeting_data = get_post_meta( $post_id, $this->zoom_meeting_post_meta, true );
			$meeting_data = json_decode( $meeting_data, true );

			$zoom_endpoint = tutor_utils()->get_package_object( true, '\Zoom\Endpoint\Meetings', $api_key, $api_secret );

			if ( isset( $meeting_data['id'] ) ) {
				$meeting_info = $zoom_endpoint->meeting( $meeting_data['id'] );

				if ( is_array( $meeting_info ) && isset( $meeting_info['id'] ) ) {
					$zoom_endpoint->remove( $meeting_data['id'] );
				}
			}

			wp_delete_post( $post_id, true );

			do_action( 'tutor_zoom_after_delete_meeting', $post_id );

			$this->response_success( __( 'Meeting Successfully Deleted', 'tutor-pro' ) );
		} else {
			$this->response_bad_request( __( 'Invalid Api Credentials', 'tutor-pro' ) );
		}
	}

	/**
	 * Get zoom meetings based on time context like expired, active, and currently running
	 *
	 * @since 4.0.0 $args[parent_ids] support added
	 *
	 * @return mixed Array or null
	 */
	public function get_meetings( $limit = 10, $page = 1, $context = '', $args = array(), $get_from_topic = true, $meeting_id = null ) {
		global $wpdb;

		$filter_clause = '';

		$limit_offset  = '';
		$limit_offset .= ' ORDER BY ' . ( ! empty( $args['order_by'] ) ? $args['order_by'] : '_meta_start.meta_value' );
		$limit_offset .= ' ' . ( ! empty( $args['order'] ) ? $args['order'] : 'DESC' );
		$limit_offset .= ! ( $limit === null ) ? ' LIMIT ' . $limit . ' OFFSET ' . ( ( $page - 1 ) * $limit ) : '';

		// Get meetings by id
		if ( $meeting_id ) {
			$meeting_id     = ! is_array( $meeting_id ) ? array( $meeting_id ) : 0;
			$meeting_ids    = implode( ',', $meeting_id );
			$filter_clause .= ' AND _meeting.ID IN(' . $meeting_ids . ')';
		}

		// Course ID filter
		if ( ! empty( $args['course_id'] ) ) {
			$topic_ids = array();

			if ( $get_from_topic ) {
				$topic_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_parent=%d",
						'topics',
						$args['course_id']
					)
				);
			}

			$parent_ids = array_merge( $topic_ids, array( $args['course_id'] ) );
			$parent_ids = implode( ',', $parent_ids );

			$filter_clause .= ' AND _meeting.post_parent IN (' . $parent_ids . ')';
		}

		// Author filter
		if ( ! empty( $args['author'] ) ) {
			$filter_clause .= ' AND _meeting.post_author=' . $args['author'];
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$filter_clause .= ' AND _meeting.post_title LIKE "%' . sanitize_text_field( $args['search'] ) . '%"';
		}

		// Date filter
		if ( ! empty( $args['date'] ) ) {
			$filter_clause .= ' AND DATE(_meta_start.meta_value)=\'' . $args['date'] . '\'';
		}

		$context_clause = '';
		if ( ! ( $context === null ) ) {
			$math_operator = $context == 'active' ? '>' : '<';

			$context_clause = ' AND ((
				_meta_unit.meta_value=\'min\'
				AND (JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, "$.start_time")) + INTERVAL _meta_duration.meta_value MINUTE)' . $math_operator . 'UTC_TIMESTAMP()
			) OR (
				_meta_unit.meta_value=\'hr\'
				AND (JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, "$.start_time")) + INTERVAL _meta_duration.meta_value HOUR)' . $math_operator . 'UTC_TIMESTAMP()
			))';
		}

		$meeting_parent_clause = ''; 
		// Get meeting that parent ids matched with args parent ids.
		if ( ! empty( $args['post_parent'] ) ) {
			$prepare_in_clause     = QueryHelper::prepare_in_clause( is_array( $args['post_parent'] ) ? $args['post_parent'] : array( $args['post_parent'] ) );
			$meeting_parent_clause = "AND _meeting.post_parent IN($prepare_in_clause)";
		}

		// Get the meetings from Database
		$meetings = $wpdb->get_results(
			"SELECT DISTINCT _meeting.*,
				_meta_start.meta_value AS meeting_starts_at,
				(JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.start_time')) + INTERVAL JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.duration')) MINUTE) < UTC_TIMESTAMP() AS is_expired,
				(UTC_TIMESTAMP() > JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.start_time')) AND UTC_TIMESTAMP() < JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.start_time')) + INTERVAL JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.duration')) MINUTE) AS is_running,
				(JSON_UNQUOTE(JSON_EXTRACT(_meta_zm_data.meta_value, '$.start_time')) > UTC_TIMESTAMP()) AS is_upcoming
			FROM {$wpdb->posts} _meeting
				INNER JOIN {$wpdb->postmeta} _meta_start ON _meeting.ID=_meta_start.post_id
				INNER JOIN {$wpdb->postmeta} _meta_duration ON _meeting.ID=_meta_duration.post_id
				INNER JOIN {$wpdb->postmeta} _meta_unit ON _meeting.ID=_meta_unit.post_id
				INNER JOIN {$wpdb->postmeta} _meta_zm_data ON _meeting.ID=_meta_zm_data.post_id
			WHERE _meeting.post_type='tutor_zoom_meeting'
				AND _meta_start.meta_key='_tutor_zm_start_datetime'
				AND _meta_unit.meta_key='_tutor_zm_duration_unit'
				AND _meta_duration.meta_key='_tutor_zm_duration'
				AND _meta_zm_data.meta_key='_tutor_zm_data'
				{$meeting_parent_clause}
				{$filter_clause}
				{$context_clause}
				{$limit_offset}"
		);

		return $meetings;
	}


	private function get_option_data( $key, $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}
		if ( ! $key ) {
			return $data;
		}
		if ( array_key_exists( $key, $data ) ) {
			return apply_filters( $key, $data[ $key ] );
		}
	}

	private function get_transient_key() {
		$user_id       = get_current_user_id();
		$transient_key = 'tutor_zoom_users_' . $user_id;
		return $transient_key;
	}

	public function get_api( $key = null ) {
		$user_id  = get_current_user_id();
		$api_data = json_decode( get_user_meta( $user_id, $this->api_key, true ), true );
		return $this->get_option_data( $key, $api_data );
	}

	public function get_settings( $key = null ) {
		$user_id       = get_current_user_id();
		$settings_data = json_decode( get_user_meta( $user_id, $this->settings_key, true ), true );
		return $this->get_option_data( $key, $settings_data );
	}

	public function tutor_zoom() {
		require TUTOR_ZOOM()->path . 'views/pages/main.php';
	}

	public function tutor_save_zoom_api() {
		tutor_utils()->checking_nonce();

		if ( ! User::has_any_role( array( User::ADMIN, User::INSTRUCTOR ) ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}
		$api_data = (array) isset( $_POST[ $this->api_key ] ) ? $_POST[ $this->api_key ] : array();
		$api_data = apply_filters( 'tutor_zoom_api_input', $api_data );

		if ( ! is_array( $api_data ) && ! empty( $api_data ) ) {
			$api_data = (array) json_decode( stripslashes( $api_data ) );
		}

		if ( empty( $api_data['api_key'] ) || empty( $api_data['api_secret'] ) || empty( $api_data['account_id'] ) ) {
			$this->response_bad_request(
				 __( 'Please fill up all the fields', 'tutor-pro' )
			);
		}

		do_action( 'tutor_save_zoom_api_before' );
		$user_id = get_current_user_id();
		update_user_meta( $user_id, $this->api_key, json_encode( $api_data ) );
		do_action( 'tutor_save_zoom_api_after' );

		$transient_key = Request::ACCESS_TOKEN_KEY . $user_id;
		$access_token = get_transient( $transient_key );
		if ( $access_token ) {
			// delete access token since we are updating the api key and secret
			delete_transient( $transient_key );
		}

		// Validate before saving.
		if ( ! $this->tutor_check_api_connection( $api_data ) ) {
			delete_user_meta( $user_id, $this->api_key );
			$this->response_bad_request( __( 'Please recheck your API Key and Secret Key', 'tutor-pro' ) );
		}

		$this->response_success( __( 'You can now add live classes to any course!', 'tutor-pro' ) );
	}

	public function tutor_save_zoom_settings() {
		tutor_utils()->checking_nonce();

		if ( ! User::has_any_role( array( User::ADMIN, User::INSTRUCTOR ) ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		do_action( 'tutor_save_zoom_settings_before' );
		$settings = (array) isset( $_POST[ $this->settings_key ] ) ? $_POST[ $this->settings_key ] : array();
		if ( ! is_array( $settings ) && ! empty( $settings ) ) {
			$settings = (array) json_decode( stripslashes( $settings ) );
		}
		$settings = apply_filters( 'tutor_zoom_settings_input', $settings );
		$user_id  = get_current_user_id();
		update_user_meta( $user_id, $this->settings_key, json_encode( $settings ) );
		do_action( 'tutor_save_zoom_settings_after' );
		$this->response_success(  __( 'Settings Updated', 'tutor-pro' ) );
	}

	private function tutor_check_api_connection( $settings ) {
		$transient_key = $this->get_transient_key();
		delete_transient( $transient_key ); // delete temporary cache

		$users = $this->tutor_zoom_get_users( $settings );
		return ! empty( $users );
	}

	/**
	 * Get Zoom Users from Zoom API
	 *
	 * @return array
	 */
	public function tutor_zoom_get_users( $settings = null ) {
		$user_id       = get_current_user_id();
		$transient_key = $this->get_transient_key();
		$users         = get_transient( $transient_key );
		$settings      = $settings ? $settings : json_decode( get_user_meta( $user_id, $this->api_key, true ), true );

		if ( empty( $users ) ) {
			$api_key    = ( ! empty( $settings['api_key'] ) ) ? $settings['api_key'] : '';
			$api_secret = ( ! empty( $settings['api_secret'] ) ) ? $settings['api_secret'] : '';
			if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
				$users      = array();
				$users_data = tutor_utils()->get_package_object( true, '\Zoom\Endpoint\Users', $api_key, $api_secret );
				$max_limit  = 2000;
				$users_list = $users_data->userlist(['page_size'=> $max_limit]);
				if ( ! empty( $users_list ) && ! empty( $users_list['users'] ) ) {
					$users = $users_list['users'];
					set_transient( $transient_key, $users, 36000 );
				}
			} else {
				$users = array();
			}
		}
		return $users;
	}

	/**
	 * Get Zoom Users
	 *
	 * @return array
	 */
	public function get_users_options() {
		$users = $this->tutor_zoom_get_users();
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				$first_name       = $user['first_name'];
				$last_name        = $user['last_name'] ?? '';
				$email            = $user['email'];
				$id               = $user['id'];
				$user_list[ $id ] = $first_name . ' ' . $last_name . ' (' . $email . ')';
			}
		} else {
			return array();
		}
		return $user_list;
	}

	/**
	 * Load zoom meeting template
	 *
	 * @return array
	 */
	public function tutor_zoom_course_meeting() {
		ob_start();
		tutor_load_template( 'single.course.zoom-meetings', null, true );
		$output = apply_filters( 'tutor_course/single/zoom_meetings', ob_get_clean() );
		echo $output;
	}

	/**
	 * Load zoom meeting template
	 *
	 * @return array
	 */
	public function load_meeting_template( $template ) {
		global $wp_query, $post;
		if ( $wp_query->is_single && ! empty( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] === $this->zoom_meeting_post_type ) {
			if ( is_user_logged_in() ) {
				$content_type       = ( get_post_type( $post->post_parent ) === tutor()->course_post_type ) ? 'topic' : 'lesson';
				$has_content_access = tutor_utils()->has_enrolled_content_access( $content_type, $post->ID );
				if ( $has_content_access ) {
					$template = tutor_get_template( 'single-zoom-meeting', true );
				} else {
					$template = tutor_get_template( 'single.lesson.required-enroll' ); // You need to enroll first
				}
			} else {
				$template = tutor_get_template( 'login' );
			}
			return $template;
		}
		return $template;
	}

	/**
	 * Add zoom menu on the tutor dashboard (frontend)
	 *
	 * @param array $nav_items nav items.
	 * @return array
	 *
	 * @since 1.9.4
	 */
	public function add_zoom_menu( $nav_items ) {
		do_action( 'before_zoom_menu_add_on_frontend' );
		$new_items = array(
			'zoom' => array(
				'title'    => __( 'Zoom', 'tutor-pro' ),
				'auth_cap' => tutor()->instructor_role,
				'icon'     => 'tutor-icon-brand-zoom',
			),
		);
		$nav_items = array_merge( $nav_items, $new_items );

		return apply_filters( 'after_zoom_menu_add_on_frontend', $nav_items );
	}

	/**
	 * If request is for zoom then load template from addons
	 *
	 * @param String
	 *
	 * @return String
	 *
	 * @since 1.9.4
	 */
	public function load_zoom_template( $location ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( tutor_utils()->is_dashboard_page( Dashboard::LIVE_CLASSES_MENU ) ) {
			$location = tutor_pro()->path . '/templates/dashboard/live-classes.php';
		}
		return $location;
	}

	/**
	 * Translation text domain load.
	 *
	 * @return void
	 */
	public function tutor_script_text_domain() {
		wp_set_script_translations( 'tutor_zoom_admin_js', 'tutor-pro', tutor_pro()->path . 'languages/' );
		wp_set_script_translations( 'tutor_zoom_frontend_js', 'tutor-pro', tutor_pro()->path . 'languages/' );
	}

	/**
	 * Check is zoom lesson mark as done or not
	 *
	 * @param string $value default value.
	 * @param string $lesson_id zoom lesson id.
	 * @param string $user_id id of student.
	 *
	 * @return bool | true on success | false on failure
	 */
	public static function is_zoom_lesson_done( $value, $lesson_id, $user_id ) : bool {
		$lesson_id = sanitize_text_field( tutor_utils()->get_post_id( $lesson_id ) );
		$user_id   = sanitize_text_field( tutor_utils()->get_user_id( $user_id ) );

		if ( $lesson_id && $user_id ) {
			$meta_key = '_tutor_completed_lesson_id_' . $lesson_id;
			$count    = get_user_meta( $user_id, $meta_key, true );
			return $count ? true : false;
		}
		return false;
	}

	/**
	 * Show icon on the right side of title on course spot light section
	 *
	 * @param int $post_id | zoom post id.
	 *
	 * @return void
	 */
	public function right_icon_area( int $post_id, $lock_icon = false ) : void {
		$post_id      = sanitize_text_field( $post_id );
		$user_id      = get_current_user_id();
		$is_completed = self::is_zoom_lesson_done( '', $post_id, $user_id );
		if ( $is_completed ) {
			echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle' disabled='disabled' readonly='readonly' checked='checked'/>";
		} else {
			if ( $lock_icon ) {
				echo '<i class="tutor-icon-lock-line tutor-fs-7 tutor-color-muted tutor-mr-4" aria-hidden="true"></i>';
			} else {
				echo "<input type='checkbox' class='tutor-form-check-input tutor-form-check-circle' disabled='disabled' readonly='readonly'/>";
			}
		}
	}

	/**
	 * Check whether user updated Zoom API or not
	 *
	 * @since 2.2.0
	 *
	 * @return boolean
	 */
	public function has_account_id() {
		$account_id = $this->get_api( 'account_id' );
		return $account_id ? true : false;
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( array $data ) {
		$course_id = $data['ID'] ?? 0;
		if ( ! $course_id ) {
			return $data;
		}
		
		$user_id = get_current_user_id();
		if ( current_user_can( 'administrator' ) ) {
			$post    = get_post( $course_id );
			$user_id = $post->post_author;
		}

		$meetings = $this->get_meetings(
			null,
			null,
			null,
			array(
				'author'    => $user_id,
				'course_id' => $course_id,
			),
			false
		);

		foreach( $meetings as $item ) {
			$meeting_data = get_post_meta( $item->ID, $this->zoom_meeting_post_meta, true );
			$meeting_data = json_decode( $meeting_data, true );

			$duration_unit = get_post_meta( $item->ID, '_tutor_zm_duration_unit', true );
			if ( false === $duration_unit ) {
				$duration_unit = 'min';
			}
			$meeting_data['duration_unit'] = $duration_unit;

			if ( 'hr' === $duration_unit ) {
				$meeting_data['duration'] = $meeting_data['duration'] / 60;
			}

			$item->meeting_data = $meeting_data;
		}

		$data[ 'zoom_users'] = $this->get_users_options();
		$data[ 'zoom_meetings'] = $meetings;

		return $data;
	}

	/**
	 * Render zoom meeting title as nav item to show on the learning area
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $zoom_meeting Zoom meeting post object.
	 * @param bool    $can_access Can user access this content.
	 *
	 * @return void
	 */
	public function render_nav_item( WP_Post $zoom_meeting, bool $can_access ): void {
		include TUTOR_ZOOM()->templates . 'learning-area/nav-item.php';
	}

	/**
	 * Render content for the a single zoom meeting
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $zoom_meeting Zoom meeting post object.
	 *
	 * @return void
	 */
	public function render_single_content( WP_Post $zoom_meeting ): void {
		$details = tutor_zoom_meeting_data( $zoom_meeting->ID );
		$data = $details->data ?? array();

		$join_url = $data['join_url'] ?? '';
		if ( (int) get_current_user_id() === (int) $zoom_meeting->post_author ) {
			$join_url = $data['start_url'] ?? $join_url;
		}

		$meeting_data = array(
			'id'           => $data['id'] ?? 0,
			'password'     => $data['password'] ?? '',
			'title'        => $zoom_meeting->post_title,
			'description'  => $zoom_meeting->post_content,
			'start_at'     => DateTimeHelper::create( $details->start_date ?? '', $details->timezone ?? '' )
				->set_timezone( User::get_user_timezone_string() )
				->format( 'c' ),
			'start_at_utc' => DateTimeHelper::create( $details->start_date ?? '', $details->timezone ?? '' )
				->set_timezone( 'UTC' )
				->format( 'Y-m-d H:i:s' ),
			'end_at'       => strtotime( $data['start_time'] ?? '' ) + ( $data['duration'] ?? 0 ) * 60,
			'duration'     => $data['duration'] ?? 0,
			'host_email'   => $data['host_email'] ?? '',
			'timezone'     => $details->timezone ?? '',
			'url'          => $join_url,
			'join_url'     => $data['join_url'] ?? '',
			'is_started'   => $details->is_started ?? false,
			'is_expired'   => $details->is_expired ?? false,
			'meeting_type' => $zoom_meeting->post_type,
		);

		tutor_load_template(
			'learning-area.subpages.live-meeting',
			array(
				'meeting_data' => $meeting_data,
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
		$is_upcoming   = ! $meeting_data['is_started'] && ! $meeting_data['is_expired'];

		// Meeting ID.
		if ( $is_upcoming ) {
			$table_content[] = array(
				'columns' => array(
					array(
						'content' => SvgIcon::make()->name( Icon::INFO_OCTAGON )->size( 20 )->get() . esc_html__( 'Meeting ID', 'tutor-pro' ),
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">' .
							'<span>' . $meeting_data['id'] . '</span>' .
							(string) Button::make()
								->variant( Variant::LINK_GRAY )
								->size( Size::X_SMALL )
								->icon_only()
								->icon( Icon::COPY_2 )
								->attrs(
									array(
										'class'  => 'tutor-copy-btn',
										'@click' => "copy('" . esc_js( $meeting_data['id'] ) . "')",
										'x-data' => 'tutorCopyToClipboard()',
										'type'   => 'button',
									)
								)->get() .
						'</div>',
					),
				),
			);
		}

		// Meeting Password.
		if ( ! empty( $meeting_data['password'] ) && $is_upcoming) {
			$table_content[] = array(
				'columns' => array(
					array(
						'content' => SvgIcon::make()->name( Icon::KEY )->size( 20 )->get() . esc_html__( 'Meeting Password', 'tutor-pro' ),
					),
					array(
						'content' => '<div class="tutor-flex tutor-items-center tutor-gap-4">' .
							'<span>' . $meeting_data['password'] . '</span>' .
							(string) Button::make()
								->variant( Variant::LINK_GRAY )
								->size( Size::X_SMALL )
								->icon_only()
								->icon( Icon::COPY_2 )
								->attrs(
									array(
										'class'  => 'tutor-copy-btn',
										'@click' => "copy('" . esc_js( $meeting_data['password'] ) . "')",
										'x-data' => 'tutorCopyToClipboard()',
										'type'   => 'button',
									)
								)->get() .
						'</div>',
					),
				),
			);
		}

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
	 * Get zoom meeting's content type info with start date
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $zoom_meeting Zoom meeting.
	 *
	 * @return string
	 */
	public static function get_content_type_info( WP_Post $zoom_meeting ) {
		$content_type   = __( 'Zoom', 'tutor-pro' );

		$details = json_decode( get_post_meta( $zoom_meeting->ID, '_tutor_zm_data', true ) );
		$start_datetime = get_post_meta( $zoom_meeting->ID, '_tutor_zm_start_datetime', true );
		if ( $start_datetime && ! empty( $start_datetime ) ) {
			$formatted_datetime = DateTimeHelper::create( $start_datetime, $details->timezone ?? '' )
				->set_timezone( 'UTC' )
				->format( 'Y-m-d H:i:s' );
			/* translators: %s: formatted date and time */
			$content_type = sprintf( __( 'Zoom - %s', 'tutor-pro' ), '<span class="tutor-utc-date-time">' . $formatted_datetime . '</span>' );
		}
		
		return $content_type;
	}
	
	/**
	 * Get the popover info card for zoom meetings.
	 *
	 * @since 4.0.0
	 *
	 * @param string $meet_link the meeting link.
	 * @param string $host_email the host email.
	 * @param string $password the meeting password.
	 *
	 * @return string
	 */
	public function get_zoom_info_card( string $meet_link, string $host_email, string $password ): string {
		$template_path = TUTOR_ZOOM()->path . 'views/template/zoom-info-popover.php';

		ob_start();
		tutor_load_template_from_custom_path(
			$template_path,
			array(
				'meet_link'  => $meet_link,
				'host_email' => $host_email,
				'password' => $password
			),
			false
		);

		return ob_get_clean();
	}

	/**
	 * Get subpage list for zoom.
	 *
	 * @since 4.0.0
	 *
	 * @param string $dashboard_url the dashboard page url.
	 * @param string $current_sub_page the current sub page.
	 *
	 * @return array
	 */
	public function get_subpage_list( string $dashboard_url, string $current_sub_page ): array {
		$sub_pages = array(
			'meetings' => array(
				'type'   => 'link',
				'label'  => __( 'Active Meetings', 'tutor-pro' ),
				'url'    => UrlHelper::add_query_params(
					$dashboard_url,
					array(
						'nav' => 'zoom',
						'tab' => 'meetings',
					)
				),
				'active' => 'meetings' === $current_sub_page || 'active' === $current_sub_page,
			),
			'expired'  => array(
				'type'   => 'link',
				'label'  => __( 'Expired', 'tutor-pro' ),
				'url'    => UrlHelper::add_query_params(
					$dashboard_url,
					array(
						'nav' => 'zoom',
						'tab' => 'expired',
					)
				),
				'active' => 'expired' === $current_sub_page,
			),
			'set-api'  => array(
				'type'   => 'link',
				'label'  => __( 'Set API', 'tutor-pro' ),
				'url'    => UrlHelper::add_query_params(
					$dashboard_url,
					array(
						'nav' => 'zoom',
						'tab' => 'set-api',
					)
				),
				'active' => 'set-api' === $current_sub_page,
			),
			'settings' => array(
				'type'   => 'link',
				'label'  => __( 'Settings', 'tutor-pro' ),
				'url'    => UrlHelper::add_query_params(
					$dashboard_url,
					array(
						'nav' => 'zoom',
						'tab' => 'settings',
					)
				),
				'active' => 'settings' === $current_sub_page,
			),
			'help'     => array(
				'type'   => 'link',
				'label'  => __( 'Help', 'tutor-pro' ),
				'url'    => UrlHelper::add_query_params(
					$dashboard_url,
					array(
						'nav' => 'zoom',
						'tab' => 'help',
					)
				),
				'active' => 'help' === $current_sub_page,
			),
		);

		return $sub_pages;
	}

	/**
	 * Get zoom settings options for frontend zoom settings.
	 *
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function get_zoom_settings_options(): array {
		$zoom_settings_options = apply_filters(
			'zoom_settings_options',
			array(
				'host_video'         => array(
					'type'  => 'switch',
					'label' => __( 'Host video', 'tutor-pro' ),
					'desc'  => __( 'Choose whether the instructor\'s camera is automatically turned on when the meeting starts.', 'tutor-pro' ),
				),
				'participants_video' => array(
					'type'  => 'switch',
					'label' => __( 'Participants video', 'tutor-pro' ),
					'desc'  => __( 'Choose whether students\' cameras are automatically turned on when they join the session.', 'tutor-pro' ),
				),
				'join_before_host'   => array(
					'type'  => 'switch',
					'label' => __( 'Join Before Host', 'tutor-pro' ),
					'desc'  => __( 'Allow students to enter the Zoom meeting room before the host starts.', 'tutor-pro' ),
				),
				'mute_participants'  => array(
					'type'  => 'switch',
					'label' => __( 'Mute Participants Upon Entry', 'tutor-pro' ),
					'desc'  => __( 'Automatically mute all students\' microphones when they join the session.', 'tutor-pro' ),
				),
				'auto_recording'     => array(
					'type'    => 'select',
					'label'   => __( 'Auto Recording', 'tutor-pro' ),
					'desc'    => __( 'Select where your recorded sessions should be saved.', 'tutor-pro' ),
					'options' => array(
						array(
							'label' => __( 'No Recordings', 'tutor-pro' ),
							'value' => 'none',
						),
						array(
							'label' => __( 'Local Drive', 'tutor-pro' ),
							'value' => 'local',
						),
						array(
							'label' => __( 'Zoom Cloud', 'tutor-pro' ),
							'value' => 'cloud',
						),
					),
				),
				'enforce_login'      => array(
					'type'  => 'switch',
					'label' => __( 'Meeting Authentication (enforce login)', 'tutor-pro' ),
					'desc'  => __( 'Require all participants to be actively signed into a Zoom account to join the meeting.', 'tutor-pro' ),
				),
			)
		);

		return $zoom_settings_options;
	}

	/**
	 * Get edit modal content for zoom meetings.
	 *
	 * @param integer $meeting_id
	 *
	 * @return array
	 */
	public function get_meeting_edit_content( int $meeting_id ): array {

		$post         	   = null;
		$zoom_meeting_data = array();
		$meeting_host      = $this->get_users_options();

		if ( ! $meeting_id ) {
			return $zoom_meeting_data;
		}

		$post = get_post( $meeting_id );

		if ( tutor()->zoom_post_type !== $post->post_type ) {
			return $zoom_meeting_data;
		}

		$meeting_start = get_post_meta( $meeting_id, '_tutor_zm_start_datetime', true );
		$meeting_data  = get_post_meta( $meeting_id, $this->zoom_meeting_post_meta, true );
		$meeting_data  = json_decode( $meeting_data, true );
		$zoom_meeting_data['meeting_id'] = $meeting_id;
		$parent_obj    = get_post_parent( $meeting_id );
		$object_id     = $parent_obj->ID ?? 0;
		if ( tutor()->topics_post_type === $parent_obj->post_type ) {
			$zoom_meeting_data['topic_id']  = $object_id;
			$course_id                      = get_post_parent( $object_id )->ID ?? 0;
			$zoom_meeting_data['course_id'] = $course_id;
		}
		if ( tutor()->course_post_type === $parent_obj->post_type ) {
			$zoom_meeting_data['course_id'] = $object_id;
		}

		if ( tutor_utils()->count( $meeting_data ) ) {
			$zoom_meeting_data['id'] = $meeting_data['id'] ?? '';
			$zoom_meeting_data['meeting_title']    = isset( $meeting_data['topic'] ) ? wp_strip_all_tags( $meeting_data['topic'] ) : '';
			$zoom_meeting_data['meeting_summary']  = $post->post_content ?? '';
			$zoom_meeting_data['meeting_password'] = $meeting_data['password'] ?? '';
			$zoom_meeting_data['meeting_timezone'] = $meeting_data['timezone'] ?? '';
			$zoom_meeting_data['meeting_duration_unit' ] = $post ? get_post_meta( $meeting_id, '_tutor_zm_duration_unit', true ) : 'min';
			$zoom_meeting_data['auto_recording'] = isset( $meeting_data['settings'] ) && isset( $meeting_data['settings']['auto_recording'] ) ? $meeting_data['settings']['auto_recording'] : 'none';
			$zoom_meeting_data['meeting_host'] = $meeting_data['host_id'] ?? '';
			$zoom_meeting_data['meeting_host_name'] = $meeting_host[ $meeting_data['host_id'] ] ?? '';
		}

		// Fallback meeting title
		if ( empty( $zoom_meeting_data['meeting_title'] ) ) {
			$zoom_meeting_data['meeting_title'] = $post->post_title ?? '';
		}

		if ( ! empty( $meeting_data ) ) {
			$input_date = DateTimeHelper::create( $meeting_start );
			$zoom_meeting_data['meeting_date'] = $input_date->format( 'Y-m-d' );
			$zoom_meeting_data['meeting_time'] = $input_date->format( 'h:i A' );
			$zoom_meeting_data['meeting_duration'] = ( 'hr' === $zoom_meeting_data['meeting_duration_unit'] ) ? $meeting_data[ 'duration' ] / 60 : $meeting_data[ 'duration' ];
		}

		return $zoom_meeting_data;
	}
}
