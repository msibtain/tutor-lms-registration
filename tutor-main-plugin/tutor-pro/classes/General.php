<?php
/**
 * General class for hook logic
 *
 * @package TutorPro
 *
 * @since 2.0.0
 */

namespace TUTOR_PRO;

defined( 'ABSPATH' ) || exit;

use Tutor\Components\Button;
use Tutor\Components\Constants\Color;
use Tutor\Components\Constants\Positions;
use Tutor\Components\Constants\Size;
use Tutor\Components\Constants\Variant;
use Tutor\Components\Modal;
use Tutor\Components\Popover;
use Tutor\Components\SvgIcon;
use Tutor\Helpers\PluginInstaller;
use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\DateTimeHelper;
use TUTOR\Icon;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Models\EnrollmentModel;
use Tutor\Traits\JsonResponse;
use TUTOR\Tutor_Base;
use TUTOR\User;
use TUTOR_ZOOM\Zoom;
use TutorPro\GoogleMeet\Models\EventsModel;
use WP_Error;
use WP_Post;

/**
 * Class General
 *
 * @since 2.0.0
 */
class General extends Tutor_Base {

	use JsonResponse;

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'tutor_action_tutor_add_course_builder', array( $this, 'tutor_add_course_builder' ) );
		add_filter( 'frontend_course_create_url', array( $this, 'frontend_course_create_url' ) );
		add_filter( 'template_include', array( $this, 'fs_course_builder' ), 99 );

		add_filter( 'tutor/options/extend/attr', array( $this, 'extend_settings_option' ) );
		add_filter( 'tutor_course_builder_logo_src', array( $this, 'tutor_course_builder_logo_src' ) );
		add_filter( 'tutor_email_logo_src', array( $this, 'tutor_email_logo_src' ) );
		add_filter( 'tutor_pages', array( $this, 'add_login_page' ), 10, 1 );

		add_action( 'tutor_after_lesson_completion_button', array( $this, 'add_course_completion_button' ), 10, 4 );

		add_action( 'tutor_is_error_before_course_update', array( $this, 'tutor_is_error_before_course_update' ), 10, 2 );
		add_action( 'wp_ajax_tutor_install_plugin', array( $this, 'ajax_tutor_install_plugin' ) );

		add_filter( 'tutor_addons_lists_config', array( $this, 'manage_addon_depend_plugins' ), PHP_INT_MAX );

		add_filter( 'init', array( $this, 'disable_author_support_for_instructors' ) );
		add_action( 'tutor_after_continue_learning_section', array( $this, 'render_upcoming_live_classes' ) );

		add_action( 'tutor_enrollment_actions', array( $this, 'render_enrollment_actions' ), 10, 5 );
		add_action( 'wp_ajax_tutor_enrolled_course_complete', array( $this, 'ajax_enrolled_course_complete' ) );
	}

	/**
	 * Show course completion button after all course content done.
	 *
	 * @since 2.4.0
	 *
	 * @param int   $course_id course id.
	 * @param int   $user_id user id.
	 * @param bool  $is_course_completed course completed or not.
	 * @param array $course_stats course progress stats.
	 *
	 * @return void
	 */
	public function add_course_completion_button( $course_id, $user_id, $is_course_completed, $course_stats ) {
		if ( false === $is_course_completed
			&& $course_stats['completed_count'] === $course_stats['total_count']
			&& CourseModel::can_complete_course( $course_id, $user_id ) ) {
			?>
			<div class="tutor-topbar-complete-btn tutor-mr-20">
				<form method="post">
					<?php wp_nonce_field( tutor()->nonce_action, tutor()->nonce, false ); ?>
					<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>"/>
					<input type="hidden" name="tutor_action" value="tutor_complete_course"/>
					<button type="submit" class="tutor-topbar-mark-btn tutor-btn tutor-btn-primary tutor-ws-nowrap" name="complete_course" value="complete_course">
						<span class="tutor-icon-circle-mark-line tutor-mr-8" aria-hidden="true"></span>
						<span><?php esc_html_e( 'Complete Course', 'tutor-pro' ); ?></span>
					</button>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Add login page
	 *
	 * @since 2.1.0
	 *
	 * @param array $pages page list.
	 *
	 * @return array
	 */
	public function add_login_page( array $pages ) {
		return array( 'tutor_login_page' => __( 'Tutor Login', 'tutor-pro' ) ) + $pages;
	}

	/**
	 * Process course submission from frontend course builder
	 *
	 * @since v.1.3.4
	 *
	 * @return void
	 */
	public function tutor_add_course_builder() {
		tutor_utils()->checking_nonce();

		$user_id          = get_current_user_id();
		$course_post_type = tutor()->course_post_type;

		//phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$course_ID = Input::post( 'course_ID', 0, Input::TYPE_INT );
		$post_ID   = Input::post( 'post_ID', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_edit_course( $user_id, $post_ID ) ) {
			wp_send_json_error( array( 'message' => __( 'Access Denied', 'tutor-pro' ) ) );
		}

		$post   = get_post( $post_ID );
		$update = true;

		/**
		 * Update the post
		 */

		$content   = Input::post( 'content', '', Input::TYPE_KSES_POST );
		$title     = Input::post( 'title', '' );
		$tax_input = tutor_utils()->array_get( 'tax_input', $_POST ); //phpcs:ignore
		$slug      = Input::post( 'post_name', '' );

		$post_data = array(
			'ID'           => $post_ID,
			'post_title'   => $title,
			'post_content' => $content,
			'post_name'    => $slug,
		);

		// Publish or Pending...
		$message       = null;
		$show_modal    = true;
		$submit_action = Input::post( 'course_submit_btn', '' );

		if ( 'save_course_as_draft' === $submit_action ) {
			$post_data['post_status'] = 'draft';
			$message                  = __( 'Course has been saved as a draft.', 'tutor-pro' );
			$show_modal               = false;
		} elseif ( 'submit_for_review' === $submit_action ) {
			$post_data['post_status'] = 'pending';
			$message                  = __( 'Course has been submitted for review.', 'tutor-pro' );
		} elseif ( 'publish_course' === $submit_action ) {
			$can_publish_course = (bool) tutor_utils()->get_option( 'instructor_can_publish_course' );
			if ( $can_publish_course || current_user_can( 'administrator' ) ) {
				$post_data['post_status'] = 'publish';
				$message                  = __( 'Course has been published.', 'tutor-pro' );
			} else {
				$post_data['post_status'] = 'pending';
				$message                  = __( 'Course has been submitted for review.', 'tutor-pro' );
			}
		}

		if ( $message ) {
			update_user_meta( $user_id, 'tutor_frontend_course_message_expires', time() + 5 );
			update_user_meta(
				$user_id,
				'tutor_frontend_course_action_message',
				array(
					'message'    => $message,
					'show_modal' => $show_modal,
				)
			);
		}
		wp_update_post( $post_data );

		/**
		 * Setting Thumbnail
		 */
		$_thumbnail_id = Input::post( 'tutor_course_thumbnail_id', 0, Input::TYPE_INT );
		self::update_post_thumbnail( $post_ID, $_thumbnail_id );

		/**
		 * Adding taxonomy
		 */
		if ( tutor_utils()->count( $tax_input ) ) {
			foreach ( $tax_input as $taxonomy => $tags ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( ! $taxonomy_obj ) {
					/* translators: %s: taxonomy name */
					_doing_it_wrong( __FUNCTION__, sprintf( __( 'Invalid taxonomy: %s.' ), $taxonomy ), '4.4.0' );//phpcs:ignore
					continue;
				}

				// array = hierarchical, string = non-hierarchical.
				if ( is_array( $tags ) ) {
					$tags = array_filter( $tags );
				}
				wp_set_post_terms( $post_ID, $tags, $taxonomy );
			}
		}

		do_action( 'save_tutor_course', $post_ID, $post_data );

		if ( wp_doing_ajax() ) {
			wp_send_json_success();
		} else {

			/**
			 * If update request not comes from edit page, redirect it to edit page
			 */
			$edit_mode = Input::get( 'course_ID', 0, Input::TYPE_INT );
			if ( ! $edit_mode ) {
				$edit_page_url = add_query_arg( array( 'course_ID' => $post_ID ) );
				wp_redirect( $edit_page_url );
				die();
			}

			/**
			 * Finally redirect it to previous page to avoid multiple post request
			 */
			$redirect_option_enabled = (bool) tutor_utils()->get_option( 'enable_redirect_on_course_publish_from_frontend' );
			if ( 'publish_course' === $submit_action && true === $redirect_option_enabled ) {
				$target_url = tutor_utils()->tutor_dashboard_url( 'my-courses' );
				wp_safe_redirect( $target_url );
			} else {
				wp_safe_redirect( tutor_utils()->referer() );
			}

			die();
		}
		die();
	}


	/**
	 * Frontend Course builder url
	 *
	 * @return string
	 */
	public function frontend_course_create_url() {
		return tutor_utils()->get_tutor_dashboard_page_permalink( 'create-course' );
	}


	/**
	 * Include Dashboard
	 *
	 * @param string $template template.
	 *
	 * @return bool|string
	 */
	public function fs_course_builder( $template ) {
		global $wp_query;

		if ( $wp_query->is_page ) {
			$student_dashboard_page_id = (int) tutor_utils()->get_option( 'tutor_dashboard_page_id' );
			if ( get_the_ID() === $student_dashboard_page_id ) {
				if ( tutor_utils()->array_get( 'tutor_dashboard_page', $wp_query->query_vars ) === 'create-course' ) {
					if ( is_user_logged_in() ) {
						$template = tutor_get_template( 'dashboard.create-course' );
					} else {
						$template = tutor_get_template( 'login' );
					}
				}
			}
		}

		return $template;
	}


	/**
	 * Extend settings options.
	 *
	 * @param array $attr settings options.
	 *
	 * @return array
	 */
	public function extend_settings_option( $attr ) {

		$pages = tutor_utils()->get_pages();

		$attr['advanced']['blocks'][0]['fields'][] = array(
			'key'         => 'hide_admin_bar_for_users',
			'type'        => 'toggle_switch',
			'label'       => __( 'Hide Admin Bar and Restrict Access to WP Admin for Instructors', 'tutor-pro' ),
			'label_title' => '',
			'default'     => 'off',
			'desc'        => __( 'Enable to hide WordPress Admin Bar from frontend and prevent instructors from accessing WP Admin panel.', 'tutor-pro' ),
		);

		/**
		 * Settings option added in Course > Course section.
		 *
		 * @since 2.0.6
		 */
		$attr['course']['blocks']['block_course']['fields'][] = array(
			'key'         => 'enable_redirect_on_course_publish_from_frontend',
			'type'        => 'toggle_switch',
			'label'       => __( 'Redirect Instructor to "Courses" once Publish button is Clicked', 'tutor-pro' ),
			'default'     => 'off',
			'label_title' => '',
			'desc'        => __( 'Enable to Redirect an Instructor to the "Courses" Page once he clicks on the "Publish" button', 'tutor-pro' ),
		);

		/**
		 * Hide quiz attempts details from frontend
		 *
		 * @since 2.0.7
		 */
		$attr['course']['blocks']['block_quiz']['fields'][] = array(
			'key'     => 'hide_quiz_details',
			'type'    => 'toggle_switch',
			'label'   => __( 'Hide Quiz Details From Students', 'tutor-pro' ),
			'default' => 'off',
			'desc'    => __( 'If enabled, the students will not be able to see their quiz attempts details', 'tutor-pro' ),
		);

		/**
		 * Show enrollment box on top of page when mobile view
		 *
		 * @since 2.0.9
		 */
		$attr['design']['blocks']['course-details']['fields'][] = array(
			'key'     => 'enrollment_box_position_in_mobile',
			'type'    => 'select',
			'label'   => __( 'Position of the Enrollment Box in Mobile View', 'tutor-pro' ),
			'default' => 'bottom',
			'options' => array(
				'top'    => __( 'On Page Top', 'tutor-pro' ),
				'bottom' => __( 'On Page Bottom', 'tutor-pro' ),
			),
			'desc'    => __( 'You can decide where you want to show Enrollment Box on your Course Details page by selecting an option from here', 'tutor-pro' ),
		);

		/**
		 * Login page option
		 *
		 * @since 2.1.0
		 */
		$login_option = array(
			'key'        => 'tutor_login_page',
			'type'       => 'select',
			'label'      => __( 'Login Page', 'tutor-pro' ),
			'default'    => '0',
			'options'    => $pages,
			'desc'       => __( 'This page will be used as the login page for both the students and the instructors.', 'tutor-pro' ),
			'searchable' => true,
		);
		/**
		 * Invoice generate settings for manual enrollment
		 *
		 * @since 2.1.4
		 */
		$invoice_options = array(
			'key'         => 'tutor_woocommerce_invoice',
			'type'        => 'toggle_switch',
			'label'       => __( 'Generate WooCommerce Order', 'tutor-pro' ),
			'label_title' => '',
			'default'     => 'off',
			'desc'        => __( 'If you want to create an WooCommerce Order to keep Track of your Sales Report for Manual Enrolment', 'tutor-pro' ),
		);

		tutor_utils()->add_option_after(
			'enable_tutor_native_login',
			$attr['advanced']['blocks'][1]['fields'],
			$login_option
		);
		tutor_utils()->add_option_after(
			'tutor_woocommerce_order_auto_complete',
			$attr['monetization']['blocks']['block_woocommerce']['fields'],
			$invoice_options
		);

		$attr['design']['blocks']['course-details']['fields'][0]['group_options'][] = array(
			'key'         => 'enable_sticky_sidebar',
			'type'        => 'toggle_single',
			'label'       => __( 'Sticky Sidebar', 'tutor-pro' ),
			'label_title' => __( 'Disable', 'tutor-pro' ),
			'default'     => 'off',
			'desc'        => __( 'Enable sticky sidebar on course details on scroll', 'tutor-pro' ),
		);

		// TODO implement later.
		/**
		* $attr['design']['blocks']['course-details']['fields'][0]['group_options'][] = array(
		* 'key'         => 'enable_sticky_topbar',
		* 'type'        => 'toggle_single',
		* 'label'       => __( 'Sticky Topbar', 'tutor-pro' ),
		* 'label_title' => __( 'Disable', 'tutor-pro' ),
		* 'default'     => 'off',
		* 'desc'        => __( 'Enable sticky topbar on course details on scroll', 'tutor-pro' ),
		* );
		*/

		/**
		 * Lesson video completion control.
		 *
		 * @since 2.2.4
		 */
		tutor_utils()->add_option_after(
			'enable_lesson_classic_editor',
			$attr['course']['blocks']['block_lesson']['fields'],
			array(
				'key'           => 'control_video_lesson_completion',
				'type'          => 'toggle_switch',
				'label'         => __( 'Video Lesson Completion Control', 'tutor-pro' ),
				'default'       => 'off',
				'desc'          => __( 'Enable to set the minimum video watch % for lesson completion, only works with Tutor Player.', 'tutor-pro' ),
				'toggle_fields' => 'required_percentage_to_complete_video_lesson',
			)
		);

		tutor_utils()->add_option_after(
			'control_video_lesson_completion',
			$attr['course']['blocks']['block_lesson']['fields'],
			array(
				'key'         => 'required_percentage_to_complete_video_lesson',
				'type'        => 'number',
				'number_type' => 'integer',
				'label'       => __( 'Set Required Percentage', 'tutor-pro' ),
				'default'     => 80,
				'max'         => 100,
				'desc'        => __( 'Specify the minimum video watch % learners must watch to mark the lesson as complete.', 'tutor-pro' ),
			)
		);

		/**
		 * New option added to restrict instructors from changing author
		 *
		 * @since 3.2.0
		 */
		$attr['general']['blocks'][3]['fields'][] = array(
			'key'         => 'instructor_can_change_course_author',
			'type'        => 'toggle_switch',
			'label'       => __( 'Allow Instructors to Change Course Author', 'tutor-pro' ),
			'label_title' => '',
			'default'     => 'on',
			'desc'        => __( 'If enabled, instructors can change the course author for their courses.', 'tutor-pro' ),
		);

		return $attr;
	}

	/**
	 * Course builder logo src
	 *
	 * @param string $url url.
	 *
	 * @return string
	 */
	public function tutor_course_builder_logo_src( $url ) {
		$media_id = (int) get_tutor_option( 'tutor_frontend_course_page_logo_id' );
		if ( $media_id ) {
			return wp_get_attachment_url( $media_id );
		}
		return $url;
	}

	/**
	 * Email logo src
	 *
	 * @param string $url url.
	 * @param mixed  $size size.
	 *
	 * @return string
	 */
	public function tutor_email_logo_src( $url = null, $size = null ) {
		$hotlink_protection = (bool) get_tutor_option( ContentSecurity::HOTLINKING_OPTION );
		$media_id_or_url    = get_tutor_option( 'tutor_email_template_logo_id' );

		if ( $hotlink_protection ) {
			return esc_url( $media_id_or_url );
		}

		if ( (int) $media_id_or_url ) {
			return wp_get_attachment_image_url( $media_id_or_url, 'full' );
		}

		return $url;
	}

	/**
	 * Update post thumbnail
	 *
	 * It will update post thumbnail meta if thumbnail_id is set
	 * otherwise it will remove existing meta
	 *
	 * Used from frontend course & bundle builder
	 *
	 * @since 2.2.0
	 *
	 * @param int $post_id required post id.
	 * @param int $thumbnail_id thumbnail id.
	 *
	 * @return void
	 */
	public static function update_post_thumbnail( int $post_id, int $thumbnail_id = 0 ) {
		$thumbnail_id = (int) sanitize_text_field( $thumbnail_id );
		$product_id   = tutor_utils()->get_course_product_id( $post_id );

		if ( $thumbnail_id ) {
			update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );

			// Update product thumbnail.
			set_post_thumbnail( $product_id, $thumbnail_id );
		} else {
			delete_post_meta( $post_id, '_thumbnail_id' );
		}
	}

	/**
	 * Restriction added for instructors to change author
	 *
	 * @param mixed $is_error error.
	 *
	 * @param array $params course params.
	 *
	 * @since 3.2.0
	 */
	public function tutor_is_error_before_course_update( $is_error, $params ) {
		if ( ! isset( $params['course_id'] ) ) {
			return $is_error;
		}

		$course_id                            = (int) $params['course_id'];
		$course                               = get_post( $course_id );
		$is_course_author_modified            = $course->post_author !== $params['post_author'];
		$can_instructors_change_course_author = (bool) tutor_utils()->get_option( 'instructor_can_change_course_author', true );
		if ( $is_course_author_modified && User::is_only_instructor() && ! $can_instructors_change_course_author ) {
			$is_error = new WP_Error( '401', __( 'You don\'t have permission to change author.', 'tutor-pro' ) );
		}
		return $is_error;
	}

	/**
	 * Checks if a plugin is available, installed, and active.
	 *
	 * @return void
	 *
	 * @since 3.2.0
	 */
	public function ajax_tutor_install_plugin() {

		tutor_utils()->checking_nonce();
		tutor_utils()->check_current_user_capability();

		$plugin_slug = sanitize_text_field( Input::post( 'plugin_slug', '' ) );

		if ( empty( $plugin_slug ) ) {
			$this->json_response( __( 'Plugin slug is required', 'tutor-pro' ), '', HttpHelper::STATUS_BAD_REQUEST );
		}

		// If Plugin doesn't exist, install it.
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {

			list( $is_successful, $response ) = array_values( PluginInstaller::get_downloadable_url( $plugin_slug ) );

			if ( ! $is_successful ) {
				$this->json_response( $response, '', HttpHelper::STATUS_BAD_REQUEST );
			}

			if ( PluginInstaller::install_or_upgrade_plugin( $response ) ) {
				$this->json_response( __( 'Plugin installed successfully', 'tutor-pro' ), '', HttpHelper::STATUS_OK );
			}

			$this->json_response( __( 'Plugin installation failed', 'tutor-pro' ), '', HttpHelper::STATUS_BAD_REQUEST );
		}

		// If plugin exists but not activated, activate it.
		if ( ! is_plugin_active( $plugin_slug ) ) {

			$activate = activate_plugin( $plugin_slug, '', false, true );

			if ( is_wp_error( $activate ) ) {
				$this->json_response( $activate->get_error_message(), '', HttpHelper::STATUS_BAD_REQUEST );
			}

			$this->json_response( __( 'Plugin activated successfully', 'tutor-pro' ), '', HttpHelper::STATUS_OK );
		}

		$this->json_response( __( 'Plugin is already activated', 'tutor-pro' ), '', HttpHelper::STATUS_OK );
	}

	/**
	 * Set depend_plugins empty if required plugin already active
	 *
	 * @since 3.2.0
	 *
	 * @param array $addons Addon config array.
	 *
	 * @return array Addon config array
	 */
	public function manage_addon_depend_plugins( $addons ) {
		$addons = array_map(
			function ( $addon ) {
				if ( ! empty( $addon['depend_plugins'] ) ) {
					$addon['is_dependents_installed'] = true;

					$dependent_plugins = $addon['depend_plugins'];
					foreach ( $dependent_plugins as $basename => $value ) {
						$depended_plugin_dir = WP_PLUGIN_DIR . '/' . $basename;
						if ( ! file_exists( $depended_plugin_dir ) ) {
							$addon['is_dependents_installed'] = false;
						}

						if ( is_plugin_active( $basename ) ) {
							unset( $addon['depend_plugins'][ $basename ] );
						}
					}
				}

				return $addon;
			},
			$addons
		);

		return $addons;
	}

	/**
	 * Restriction added for instructors to change course author,
	 *
	 * @since 3.2.0
	 */
	public function disable_author_support_for_instructors() {
		$can_instructors_change_course_author = (bool) tutor_utils()->get_option( 'instructor_can_change_course_author', true );
		if ( User::is_only_instructor() && ! $can_instructors_change_course_author ) {
			remove_post_type_support( 'courses', 'author' );
		}
	}

	/**
	 * Render upcoming lessons for the dashboard.
	 *
	 * This method displays upcoming live lessons integrated with Zoom and Google Meet,
	 * showing meeting info, start time, and links if available. Utilizes the Tutor template
	 * at `dashboard/upcoming-lessons.php`.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function render_upcoming_live_classes() {
		$zoom_enabled = tutor_utils()->is_addon_enabled( 'tutor-zoom' );
		$meet_enabled = tutor_utils()->is_addon_enabled( 'google-meet' );

		if ( ! $zoom_enabled && ! $meet_enabled ) {
			return;
		}

		$upcoming_meetings = array();

		$enrolled_course_ids = tutor_utils()->get_enrolled_courses_ids_by_user();
		if ( ! $enrolled_course_ids ) {
			return;
		}

		$enrolled_course_ids = array_map( 'intval', $enrolled_course_ids );

		$args = array(
			'post_parent__in' => $enrolled_course_ids,
			'fields'          => 'ids',
		);

		$course_topic_ids = array();
		foreach ( $enrolled_course_ids as $course_id ) {
			$topic_ids = tutor_utils()->get_topics( $course_id, $args );
			$topic_ids = is_a( $topic_ids, 'WP_Query' ) ? $topic_ids->get_posts() : array();
			if ( tutor_utils()->count( $topic_ids ) ) {
				foreach ( $topic_ids as $topic_id ) {
					array_push( $course_topic_ids, $topic_id );
				}
			}
		}

		$meeting_parent_ids = array_merge( $enrolled_course_ids, $course_topic_ids );

		// Collect upcoming Zoom meetings.
		if ( $zoom_enabled && class_exists( '\TUTOR_ZOOM\Zoom' ) ) {
			$zoom          = new Zoom( false );
			$zoom_meetings = $zoom->get_meetings(
				20,
				1,
				null,
				array(
					'post_parent' => $meeting_parent_ids,
				),
				true
			);

			if ( ! empty( $zoom_meetings ) ) {
				foreach ( $zoom_meetings as $meeting ) {
					if ( ! is_object( $meeting ) ) {
						continue;
					}

					if ( empty( $meeting->meeting_starts_at ) ) {
						continue;
					}

					// Skip if meeting already expired (no longer available).
					if ( isset( $meeting->is_expired ) && $meeting->is_expired ) {
						continue;
					}

					$meeting_parent = get_post( $meeting->post_parent );
					$course_id      = (int) get_post_meta( $meeting->ID, '_tutor_zm_for_course', true );
					$status         = ( isset( $meeting->is_running ) && $meeting->is_running ) ? 'open' : 'live session';

					if ( ! $meeting_parent ) {
						continue;
					}

					$meeting_url = $this->construct_meeting_url( $meeting_parent, $meeting );

					$zoom_data        = json_decode( get_post_meta( $meeting->ID, '_tutor_zm_data', true ) );
					$meeting_timezone = is_object( $zoom_data ) && isset( $zoom_data->timezone ) ? $zoom_data->timezone : wp_timezone_string();
					$start_time       = DateTimeHelper::create( $meeting->meeting_starts_at ?? '', $meeting_timezone )->set_timezone( 'UTC' )->format( 'Y-m-d H:i:s' );

					$upcoming_meetings[] = array(
						'source'    => 'zoom',
						'title'     => $meeting->post_title,
						'course_id' => $course_id,
						'start_at'  => $start_time,
						'status'    => $status,
						'url'       => $meeting_url,
					);
				}
			}
		}

		// Collect upcoming Google Meet meetings.
		if ( $meet_enabled && class_exists( '\TutorPro\GoogleMeet\Models\EventsModel' ) ) {
			$sorting_args = array(
				'course_id'   => 0,
				'search_term' => '',
				'author_id'   => '',
				'date'        => '',
				'order'       => 'DESC',
				'post_parent' => $meeting_parent_ids,
			);

			$paging_args = array(
				'limit'  => 20,
				'offset' => 0,
			);

			$result = EventsModel::get( 'active', $sorting_args, $paging_args );

			if ( ! empty( $result['meetings'] ) ) {
				foreach ( $result['meetings'] as $meeting ) {
					if ( ! is_object( $meeting ) ) {
						continue;
					}

					// If no start time skip it.
					$start = get_post_meta( $meeting->ID, EventsModel::POST_META_KEYS[0], true );
					if ( empty( $start ) ) {
						continue;
					}

					$post = get_post( (int) $meeting->post_parent );
					if ( ! $post ) {
						continue;
					}

					$course_id = 0;
					if ( is_a( $post, 'WP_Post' ) ) {
						$course_id = tutor()->topics_post_type === $post->post_type ? wp_get_post_parent_id( $post->ID ) : $post->ID;
					}

					if ( ! $course_id ) {
						continue;
					}

					$url = $this->construct_meeting_url( $post, $meeting );

					// meeting_status can be 'ongoing', 'expired', or 'start_meeting'.
					$status = ( isset( $meeting->meeting_status ) && 'ongoing' === $meeting->meeting_status ) ? 'open' : 'live session';

					$meet_data = json_decode( get_post_meta( $meeting->ID, EventsModel::POST_META_KEYS[2], true ) );
					$timezone  = is_object( $meet_data ) && isset( $meet_data->timezone ) ? $meet_data->timezone : wp_timezone_string();
					$start     = DateTimeHelper::create( $start, $timezone )->set_timezone( 'UTC' )->format( 'Y-m-d H:i:s' );

					$upcoming_meetings[] = array(
						'source'    => 'meet',
						'title'     => $meeting->post_title,
						'course_id' => $course_id,
						'start_at'  => $start,
						'status'    => $status,
						'url'       => $url,
					);
				}
			}
		}

		if ( empty( $upcoming_meetings ) ) {
			return;
		}

		// Sort by meeting start date in descending order.
		usort(
			$upcoming_meetings,
			function ( $a, $b ) {
				$a_ts = strtotime( $a['start_at'] );
				$b_ts = strtotime( $b['start_at'] );

				if ( $a_ts === $b_ts ) {
					return 0;
				}

				return ( $a_ts < $b_ts ) ? 1 : -1;
			}
		);

		// Limit to 4 meetings.
		$upcoming_meetings = array_slice( $upcoming_meetings, 0, 4 );

		tutor_load_template(
			'dashboard.upcoming-live-classes',
			array(
				'upcoming_meetings' => $upcoming_meetings,
			),
			true
		);
	}

	/**
	 * Render enrollment actions.
	 *
	 * @since 4.0.0
	 *
	 * @param int    $enrollment_id enrollment id.
	 * @param int    $course_id course id.
	 * @param int    $student_id student id.
	 * @param int    $progress course progress.
	 * @param string $context context.
	 *
	 * @return void
	 */
	public function render_enrollment_actions( $enrollment_id, $course_id, $student_id, $progress, $context ) {
		$actions      = array();
		$is_completed = tutor_utils()->is_completed_course( $course_id, $student_id );

		if ( ! $is_completed ) {
			$modal_id = 'tutor-enrolled-course-complete-' . $enrollment_id;
			$data     = array(
				'modal_id'      => $modal_id,
				'enrollment_id' => $enrollment_id,
				'course_id'     => $course_id,
				'student_id'    => $student_id,
				'progress'      => $progress,
			);

			$modal_template = 'dashboard' === $context
								? TUTOR_ENROLLMENTS()->path . 'views/enrolled-course-complete-dashboard-modal.php'
								: TUTOR_ENROLLMENTS()->path . 'views/enrolled-course-complete-modal.php';

			$actions['mark_as_complete'] = array(
				'label' => __( 'Mark as Complete', 'tutor-pro' ),
				'icon'  => 'dashboard' === $context ? Icon::CHECK_2 : 'tutor-icon-mark-light',
				'modal' => array(
					'id'       => $modal_id,
					'template' => $modal_template,
					'data'     => $data,
				),
			);
		}

		$actions = apply_filters( 'tutor_enrollment_action_dropdown_items', $actions, $enrollment_id, $course_id, $student_id, $progress, $context );
		if ( ! tutor_utils()->count( $actions ) ) {
			return;
		}

		'dashboard' === $context
			? $this->render_actions_for_dashboard( $actions )
			: $this->render_actions_for_admin( $actions );
	}

	/**
	 * Render enrollment actions for dashboard
	 *
	 * @since 4.0.0
	 *
	 * @param array $actions actions.
	 *
	 * @return void
	 */
	private function render_actions_for_dashboard( array $actions ) {
		$trigger_button = Button::make()
				->label( __( 'Actions', 'tutor-pro' ) )
				->variant( Variant::SECONDARY )
				->size( Size::X_SMALL )
				->icon( Icon::ELLIPSES )
				->icon_only()
				->attr( 'x-ref', 'trigger' )
				->attr( '@click', 'toggle()' )
				->get();

		$options = Popover::make()->placement( Positions::BOTTOM_END )->trigger( $trigger_button );
		foreach ( $actions as $action ) {
			$modal_id = $action['modal']['id'] ?? '';
			$options->menu_item(
				array(
					'tag'     => 'a',
					'icon'    => SvgIcon::make()->name( $action['icon'] ?? '' )->size( 20 )->get(),
					'content' => $action['label'] ?? '',
					'attr'    => array(
						'href'   => '#',
						'@click' => "hide(); TutorCore.modal.showModal('{$modal_id}');",
					),
				)
			);
		}

		$options->render();

		foreach ( $actions as $action ) {
			if ( ! empty( $action['modal']['template'] ) ) {
				tutor_load_template_from_custom_path(
					$action['modal']['template'],
					$action['modal']['data'] ?? array(),
					false
				);
			}
		}
	}

	/**
	 * Render enrollment actions for admin
	 *
	 * @since 4.0.0
	 *
	 * @param array $actions actions.
	 *
	 * @return void
	 */
	private function render_actions_for_admin( array $actions ) {
		?>
		<div class="tutor-dropdown-parent">
			<button type="button" class="tutor-iconic-btn" action-tutor-dropdown="toggle">
				<span class="tutor-icon-kebab-menu" aria-hidden="true"></span>
			</button>
			<div class="tutor-dropdown tutor-dropdown-dark tutor-text-left">
				<?php
				foreach ( $actions as $action ) {
					?>
					<a	class="tutor-dropdown-item" 
						href="#"
						<?php
						if ( isset( $action['modal'] ) ) {
							?>
							data-tutor-modal-target="<?php echo esc_attr( $action['modal']['id'] ?? '' ); ?>"
						<?php } ?>
					>
						<i class="<?php echo esc_attr( $action['icon'] ?? '' ); ?> tutor-mr-8" aria-hidden="true"></i>
						<span><?php echo esc_html( $action['label'] ?? '' ); ?></span>
					</a>
					<?php
				}
				?>
			</div>
		</div>
			<?php
			// Load modal templates.
			foreach ( $actions as $action ) {
				if ( isset( $action['modal'] ) ) {
					tutor_load_template_from_custom_path(
						$action['modal']['template'] ?? '',
						$action['modal']['data'] ?? array(),
						false
					);
				}
			}
	}

	/**
	 * Complete enrolled course
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function ajax_enrolled_course_complete() {
		tutor_utils()->check_nonce();

		$course_id  = Input::post( 'course_id', 0, Input::TYPE_INT );
		$student_id = Input::post( 'student_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		if ( ! $course_id || ! $student_id ) {
			$this->response_bad_request( tutor_utils()->error_message( 'invalid_req' ) );
		}

		$is_enrolled = EnrollmentModel::is_enrolled( $course_id, $student_id );
		if ( ! $is_enrolled ) {
			$this->response_bad_request( tutor_utils()->error_message( 'invalid_req' ) );
		}

		CourseModel::mark_course_as_completed( $course_id, $student_id );

		$this->response_success( __( 'Course completed successfully', 'tutor-pro' ) );
	}

	/**
	 * Constructs a URL for a meeting based on the parent and meeting post.
	 *
	 * Determines the appropriate meeting URL based on the meeting's parent post type,
	 * current learning mode, and lesson type.
	 *
	 * @since 4.0.0
	 *
	 * @param WP_Post $meeting_parent The parent post object, typically a course or topic.
	 * @param Object  $meeting        The meeting post object.
	 *
	 * @return string The constructed meeting URL.
	 */
	public function construct_meeting_url( WP_Post $meeting_parent, object $meeting ): string {
		$meeting_url = '';
		if ( tutor()->course_post_type === $meeting_parent->post_type ) {
			if ( tutor_utils()->is_legacy_learning_mode() ) {
				$meeting_url = sprintf( get_permalink( $meeting_parent->ID ) . '#%s', tutor()->meet_post_type === $meeting->post_type ? 'tutor-google-meetings' : 'tutor-zoom-meetings' );
			} else {
				$meeting_url = sprintf( home_url( "{$this->course_base_permalink}/sample-course/%s/" . $meeting->post_name . '/' ), tutor()->meet_post_type === $meeting->post_type ? 'meet-lessons' : 'zoom-lessons' );
			}
		} else {
			// For the meeting inside topic.
			$meeting_url = get_permalink( $meeting->ID );
		}

		return $meeting_url;
	}
}
