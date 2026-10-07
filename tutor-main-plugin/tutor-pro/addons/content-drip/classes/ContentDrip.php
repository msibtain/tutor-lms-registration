<?php
/**
 * ContentDrip class
 *
 * @author themeum
 * @link https://themeum.com
 * @package TutorPro\ContentDrip
 * @since 1.4.1
 */

namespace TUTOR_CONTENT_DRIP;

use stdClass;
use Tutor\Components\Badge;
use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use TUTOR\Input;
use TUTOR\Lesson;
use Tutor\Models\CourseModel;
use Tutor\Models\QuizModel;
use TUTOR\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Drip class
 */
class ContentDrip {

	/**
	 * The Unlock Date.
	 *
	 * @var string
	 */
	private $unlock_date = '';

	/**
	 * The unlock message
	 *
	 * @var string
	 */
	private $unlock_message = null;

	/**
	 * The content drip type.
	 *
	 * @var string
	 */
	private $drip_type = null;

	/**
	 * The email log meta key.
	 *
	 * @var string
	 */
	private $mail_log_meta_key = '_tutor_pro_content_drip_mail_log';

	/**
	 * Save sent mail logs.
	 *
	 * @var array
	 */
	private $sent_mail_log = array();

	/**
	 * Whether quiz pass is required.
	 *
	 * @var boolean
	 */
	private $quiz_pass_req = false;

	/**
	 * Exclude Zoom, Meet meeting when sequential mode enabled.
	 *
	 * @var array
	 */
	private $exclude_type = array( 'tutor_zoom_meeting', 'tutor-google-meet' );

	/**
	 * Whether quiz needs manual review.
	 *
	 * @var boolean
	 */
	private $quiz_manual_review_required = false;

	/**
	 * Get the post type of single post.
	 *
	 * @var string
	 */
	private $singular_post_type;

	/**
	 * Contend drip prerequisite contents.
	 *
	 * @var array
	 */
	private $prerequisites = array();

	/**
	 * Content drip class constructor.
	 */
	public function __construct() {

		/**
		 * Enqueue scripts.
		 *
		 * @since 4.0.0
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ) );

		add_action( 'tutor/lesson_update/after', array( $this, 'lesson_updated' ) );
		add_action( 'tutor_quiz_settings_updated', array( $this, 'lesson_updated' ) );
		add_action( 'tutor_assignment_updated', array( $this, 'lesson_updated' ) );
		add_action( 'tutor_assignment_created', array( $this, 'lesson_updated' ) );

		/**
		 * On save lesson update content drip meta
		 *
		 * @since 1.8.9
		*/
		add_action( 'save_post_' . tutor()->lesson_post_type, array( $this, 'lesson_updated' ), 10, 1 );

		add_action( 'tutor/lesson_list/right_icon_area', array( $this, 'show_content_drip_icon' ) );
		add_filter( 'tutor_course/single/content/show_permalink', array( $this, 'alter_lqaz_show_permalink' ), 10, 2 );

		add_filter( 'tutor_lesson/single/content', array( $this, 'drip_content_protection' ) );
		add_filter( 'tutor_quiz/single/wrapper', array( $this, 'drip_content_protection' ) );
		add_filter( 'tutor_assignment/single/content', array( $this, 'drip_content_protection' ) );
		add_filter( 'tutor_learning_area_content', array( $this, 'content_drip_protection' ) );
		add_filter( 'tutor_learning_area_lesson_mark_as_complete', array( $this, 'remove_mark_as_complete' ) );

		// Lesson-Quiz-Assignment onPublish Mailing.
		add_action( 'init', array( $this, 'execute_content_drip_publish_hook' ) );
		add_filter( 'tutor_emails/dashboard/list', array( $this, 'register_email_list' ), 11 );

		add_action( 'wp_ajax_tutor_content_drip_state_update', array( $this, 'tutor_content_drip_state_update' ) );
		/**
		 * On mobile view mark as complete disable if lesson is locked
		 *
		 * @since 2.1.9
		 */
		add_filter( 'tutor_lesson_show_mark_as_complete', array( $this, 'check_if_lesson_is_locked' ) );

		add_filter( 'tutor_assignment_details_response', array( $this, 'extend_assignment_details_response' ), 10, 2 );
		add_filter( 'tutor_lesson_details_response', array( $this, 'extend_lesson_details_response' ), 10, 2 );
		add_filter( 'tutor_content_drip_assignment_deadline', array( $this, 'set_assignment_deadline' ), 10, 3 );
	}

	/**
	 * Load frontend scripts
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function load_frontend_scripts() {
		if ( tutor_utils()->is_learning_area() ) {
			wp_enqueue_style( 'tutor-content-drip', TUTOR_CONTENT_DRIP()->url . 'assets/css/content-drip.css', array(), TUTOR_PRO_VERSION );
		}
	}

	/**
	 * Set assignment deadline based on content drip date and days settings.
	 *
	 * @since 3.5.0
	 *
	 * @param int $deadline the deadline time to compare.
	 * @param int $course_id the course id.
	 * @param int $assignment_id the assignment id.
	 *
	 * @return int
	 */
	public function set_assignment_deadline( $deadline, $course_id, $assignment_id ) {
		$enable = (bool) get_tutor_course_settings( $course_id, 'enable_content_drip' );

		if ( ! $enable ) {
			return $deadline;
		}

		$drip_type = get_tutor_course_settings( $course_id, 'content_drip_type', 'unlock_by_date' );

		if ( 'specific_days' === $drip_type ) {
			$days            = (int) get_item_content_drip_settings( $assignment_id, 'after_xdays_of_enroll' );
			$days_in_seconds = DAY_IN_SECONDS * $days;
			$deadline       += $days_in_seconds;
		}

		if ( 'unlock_by_date' === $drip_type ) {
			$unlock_timestamp = strtotime( get_item_content_drip_settings( $assignment_id, 'unlock_date' ) );

			if ( $unlock_timestamp ) {
				$deadline = $unlock_timestamp < $deadline ? $deadline : $unlock_timestamp;
			}
		}

		return $deadline;
	}

	/**
	 * Check if lesson is locked.
	 *
	 * @since 2.2.0
	 *
	 * @param string $status the status.
	 *
	 * @return string|bool
	 */
	public function check_if_lesson_is_locked( $status ) {
		return $this->is_lock_lesson( get_the_ID() ) ? false : $status;
	}

	/**
	 * Update content drip state.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function tutor_content_drip_state_update() {
		tutor_utils()->checking_nonce();
		$course_id = Input::post( 'course_id', 0, Input::TYPE_INT );
		if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
			wp_send_json_error();
		}

		$course = get_post( $course_id );

		do_action( 'tutor_save_course_settings', $course_id, $course );
		wp_send_json_success();
	}

	/**
	 * Save content drip settings.
	 *
	 * @since 1.4.0
	 *
	 * @param int $lesson_id the lesson id.
	 *
	 * @return void
	 */
	public function lesson_updated( $lesson_id ) {
		$content_drip_settings = tutor_utils()->array_get( 'content_drip_settings', $_POST );//phpcs:ignore
		if ( tutor_utils()->count( $content_drip_settings ) ) {
			update_post_meta( $lesson_id, '_content_drip_settings', $content_drip_settings );
		}
	}

	/**
	 * Show lock icon based on condition.
	 *
	 * @param \WP_Post $post the post object.
	 *
	 * @return void
	 */
	public function show_content_drip_icon( $post ) {
		$is_lock = $this->is_lock_lesson( $post->ID );

		if ( $is_lock ) {
			echo '<i class="tutor-icon-lock-line tutor-fs-7 tutor-color-muted tutor-mr-4" aria-hidden="true"></i>';
		}
	}

	/**
	 * Alter show permalink.
	 *
	 * @since 2.2.0
	 *
	 * @param bool $boolean the boolean.
	 * @param int  $id the id.
	 *
	 * @return bool
	 */
	public function alter_lqaz_show_permalink( $boolean, $id ) {
		if ( ! $boolean ) {
			return $boolean;
		}

		return $this->is_lock_lesson( $id ) ? false : $boolean;
	}

	/**
	 * Check if lesson is locked.
	 *
	 * @since 1.4.0
	 *
	 * @param int $content_id the content id.
	 *
	 * @return bool
	 */
	public function is_lock_lesson( $content_id ) {
		$content_type     = get_post_field( 'post_type', $content_id );
		$lesson_post_type = tutor()->lesson_post_type;
		$course_id        = tutor_utils()->get_course_id_by_content( $content_id );

		$enable = (bool) get_tutor_course_settings( $course_id, 'enable_content_drip' );
		if ( ! $enable ) {
			return false;
		}

		$drip_type       = get_tutor_course_settings( $course_id, 'content_drip_type', 'unlock_by_date' );
		$this->drip_type = $drip_type;

		$course_obj               = get_post_type_object( $content_type );
		$this->singular_post_type = empty( $course_obj->labels->singular_name ) ? '' : $course_obj->labels->singular_name;

		if ( 'unlock_by_date' === $drip_type ) {
			$unlock_timestamp = strtotime( get_item_content_drip_settings( $content_id, 'unlock_date' ) );
			if ( $unlock_timestamp ) {
				$this->unlock_date = date_i18n( get_option( 'date_format' ), $unlock_timestamp );
				// Translators: %1s post type %2s unlock date.
				$this->unlock_message = sprintf( __( 'This %1$s will be available from %2$s', 'tutor-pro' ), $this->singular_post_type, $this->unlock_date );

				return $unlock_timestamp > current_time( 'timestamp' );
			}
		} elseif ( 'specific_days' === $drip_type ) {
			$days = (int) get_item_content_drip_settings( $content_id, 'after_xdays_of_enroll' );

			if ( $days > 0 ) {
				$enroll       = tutor_utils()->is_course_enrolled_by_lesson( $content_id );
				$enroll_date  = tutor_utils()->array_get( 'post_date', $enroll );
				$enroll_date  = gmdate( 'Y-m-d', strtotime( $enroll_date ) );
				$days_in_time = 60 * 60 * 24 * $days;

				$unlock_timestamp = strtotime( $enroll_date ) + $days_in_time;

				$this->unlock_date = date_i18n( get_option( 'date_format' ), $unlock_timestamp );
				// Translators: %1s post type %2s unlock date.
				$this->unlock_message = sprintf( __( 'This %1$s will be available for you from %2$s', 'tutor-pro' ), $this->singular_post_type, $this->unlock_date );

				return $unlock_timestamp > current_time( 'timestamp' );
			}
		}

		if ( 'unlock_sequentially' === $drip_type && ! tutor_utils()->has_user_course_content_access( get_current_user_id(), $course_id ) ) {
			/**
			 * Lock exclude Zoom, Meet meeting when sequential mode enabled
			 */
			$previous_id = tutor_utils()->get_course_previous_content_id( $content_id, $this->exclude_type );

			if ( $previous_id ) {
				$previous_content = get_post( $previous_id );

				$obj = get_post_type_object( $previous_content->post_type );

				// Lesson.
				if ( $previous_content->post_type === $lesson_post_type ) {
					$is_lesson_complete = tutor_utils()->is_completed_lesson( $previous_id );
					if ( ! $is_lesson_complete ) {
						// Translators: %s name.
						$this->unlock_message = sprintf( __( 'Please complete previous %s first', 'tutor-pro' ), $obj->labels->singular_name );
						return true;
					}
				}

				// Assignment.
				if ( 'tutor_assignments' === $previous_content->post_type ) {
					$is_submitted = tutor_utils()->is_assignment_submitted( $previous_id );
					if ( ! $is_submitted ) {
						// Translators: %s name.
						$this->unlock_message = sprintf( __( 'Please submit previous %s first', 'tutor-pro' ), $obj->labels->singular_name );
						return true;
					}
				}

				// Quiz.
				if ( 'tutor_quiz' === $previous_content->post_type ) {
					$attempts = tutor_utils()->quiz_ended_attempts( $previous_id );

					// Need to reset it first.
					$this->quiz_manual_review_required = false;
					$this->quiz_pass_req               = false;

					if ( ! $attempts ) {
						// Translators: %s name.
						$this->unlock_message = sprintf( __( 'Please complete previous %s first', 'tutor-pro' ), $obj->labels->singular_name );
						return true;
					}

					/**
					 * Previous quiz pass required to access next course content
					 *
					 * @since 2.1.0
					 */
					$previous_pass_required = tutor_utils()->get_quiz_option( $previous_id, 'pass_is_required' );
					$passed_previous_quiz   = QuizModel::is_quiz_passed( $previous_id, get_current_user_id() );
					$limit_attempts_allowed = '1' === (string) tutor_utils()->get_quiz_option( $previous_id, 'limit_attempts_allowed', '0' );
					$attempts_allowed       = (int) tutor_utils()->get_quiz_option( $previous_id, 'attempts_allowed', 0 );
					$can_retry              = $limit_attempts_allowed && ( 0 === $attempts_allowed || $attempts_allowed > 1 );

					if ( $can_retry && $previous_pass_required && ! $passed_previous_quiz ) {
						// Translators: %s name.
						$this->unlock_message              = sprintf( __( 'To access this %s you have to pass the quiz', 'tutor-pro' ), $course_obj->labels->singular_name ) . '<a href="' . esc_url( get_permalink( $previous_id ) ) . '" style="color:#3E64DE" > ' . esc_html( $previous_content->post_title ) . ' </a>';
						$this->quiz_pass_req               = true;
						$this->quiz_manual_review_required = QuizModel::is_manual_review_required( $previous_id );

						return true;
					}
				}
			}
		} elseif ( 'after_finishing_prerequisites' === $drip_type ) {
			/**
			 * Whitelisted pre-requisites lesson restriction.
			 *
			 * @since 3.0.0
			 */
			if ( tutor_utils()->has_user_course_content_access( get_current_user_id(), $course_id ) ) {
				return false;
			}

			$this->prerequisites = (array) get_item_content_drip_settings( $content_id, 'prerequisites' );
			$this->prerequisites = array_filter( $this->prerequisites );

			if ( tutor_utils()->count( $this->prerequisites ) ) {
				$required_finish = array();

				foreach ( $this->prerequisites as $id ) {
					$item = get_post( $id );

					if ( ! $item || ! is_object( $item ) ) {
						continue;
					}

					if ( $item->post_type === $lesson_post_type ) {
						$is_lesson_complete = tutor_utils()->is_completed_lesson( $id );
						if ( ! $is_lesson_complete ) {
							$required_finish[] = "<a href='" . get_permalink( $item ) . "' target='_blank'>{$item->post_title}</a>";
						}
					}
					if ( 'tutor_assignments' === $item->post_type ) {
						$is_submitted = tutor_utils()->is_assignment_submitted( $id );
						if ( ! $is_submitted ) {
							$required_finish[] = "<a href='" . get_permalink( $item ) . "' target='_blank'>{$item->post_title}</a>";
						}
					}
					if ( 'tutor_quiz' === $item->post_type ) {
						$attempts = tutor_utils()->quiz_ended_attempts( $id );
						if ( ! $attempts ) {
							$required_finish[] = "<a href='" . get_permalink( $item ) . "' target='_blank'>{$item->post_title}</a>";
						}
					}
				}

				if ( tutor_utils()->count( $required_finish ) ) {
					$output = the_title( '<div class="tutor-assignment-title tutor-fs-4 tutor-fw-medium tutor-color-black">', '</div>', false );
					// Translators: %s post type.
					$output .= '<h4>' . sprintf( __( 'You can take this %s after finishing the following prerequisites:', 'tutor-pro' ), $this->singular_post_type ) . '</h4>';
					$output .= '<ul>';
					foreach ( $required_finish as $required_finish_item ) {
						$output .= "<li>{$required_finish_item}</li>";
					}
					$output .= '</ul>';

					$this->unlock_message = $output;
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Content drip protection for the new learning area.
	 *
	 * @since 1.9.10
	 *
	 * @param string $html the html content.
	 *
	 * @return string
	 */
	public function drip_content_protection( $html ) {
		$content_id = get_the_ID();

		if ( $this->is_lock_lesson( $content_id ) ) {
			$course_id   = tutor_utils()->get_course_id_by_subcontent( $content_id );
			$previous_id = tutor_utils()->get_course_previous_content_id( $content_id, $this->exclude_type );

			$header = '';
			if ( get_post_field( 'post_type', $content_id ) !== 'tutor_quiz' ) {
				ob_start();
				tutor_load_template(
					'single.common.header',
					array(
						'course_id'        => $course_id,
						'mark_as_complete' => false,
					)
				);
				$header = ob_get_clean();
			}

			if ( 'after_finishing_prerequisites' === $this->drip_type ) {
				$img_url = trailingslashit( TUTOR_CONTENT_DRIP()->url ) . 'assets/images/traffic-light.svg';

				$output = "<div class='content-drip-message-wrap content-drip-wrap-flex'> <div class='content-drip-left'><img src='{$img_url}' alt='' /> </div> <div class='content-drip-right'>{$this->unlock_message}</div> </div>";

				$output = apply_filters( 'tutor/content_drip/unlock_message', $output );
				return $header . "<div class='tutor-lesson-content-drip-wrap'> {$output} </div>";

			} elseif ( 'unlock_sequentially' === $this->drip_type && $previous_id ) {

				$post_types = array(
					'lesson'             => __( 'Lesson', 'tutor-pro' ),
					'tutor_quiz'         => __( 'Quiz', 'tutor-pro' ),
					'tutor_assignments'  => __( 'Assignment', 'tutor-pro' ),
					'tutor_zoom_meeting' => __( 'Meeting', 'tutor-pro' ),
					'tutor-google-meet'  => __( 'Meeting', 'tutor-pro' ),
				);

				$previous_title        = get_the_title( $previous_id );
				$previous_permalink    = get_permalink( $previous_id );
				$previous_content_type = get_post_type( $previous_id );
				$previous_content_type = isset( $post_types[ $previous_content_type ] ) ? $post_types[ $previous_content_type ] : $previous_content_type;

				ob_start();
				require dirname( __DIR__ ) . '/views/restrict-sequence.php';
				return $header . apply_filters( 'tutor/content_drip/unlock_message', ob_get_clean() );

			} else {
				$output = apply_filters( 'tutor/content_drip/unlock_message', "<div class='content-drip-message-wrap tutor-alert'> {$this->unlock_message}</div>" );
				return $header . "<div class='tutor-lesson-content-drip-wrap'> {$output} </div>";
			}
		}

		return $html;
	}

	/**
	 * Render prerequisite list.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function render_prerequisite_list() {

		$content = '';

		if ( ! tutor_utils()->count( $this->prerequisites ) ) {
			return $content;
		}

		foreach ( $this->prerequisites as $id ) {
			$item = get_post( $id );

			if ( ! $item || ! is_object( $item ) ) {
				continue;
			}

			$link = get_post_permalink( $id );
			$type = '';
			$icon = '';

			if ( tutor()->lesson_post_type === $item->post_type ) {
				$type = Lesson::get_content_type_info( $item );
				$icon = 'Reading' === $type ? Icon::BOOK_2 : Icon::VIDEO_CAMERA_2;
			}

			if ( tutor()->quiz_post_type === $item->post_type ) {
				$type = __( 'Quiz', 'tutor-pro' );
				$icon = Icon::QUIZ_2;
			}

			if ( tutor()->assignment_post_type === $item->post_type ) {
				$type = __( 'Assignment', 'tutor-pro' );
				$icon = Icon::ASSIGNMENT;
			}

			$title_html = sprintf(
				'<div>
					<a class="tutor-brand" href="%s">%s</a>
					<div class="tutor-tiny tutor-text-subdued">%s</div>
				</div>',
				esc_url( $link ),
				esc_html( $item->post_title ),
				esc_html( $type )
			);

			$footer_item_html = sprintf(
				'<div class="tutor-content-drip-card-footer-item">
					<div class="tutor-icon-brand">%s</div>
					%s
				</div>',
				SvgIcon::make()->name( $icon )->size( 20 )->get(),
				$title_html
			);

			$content .= $footer_item_html;
		}

		return $content;
	}

	/**
	 * Content drip protection for the new learning area.
	 *
	 * @since 4.0.0
	 *
	 * @param string $html the html content.
	 *
	 * @return string
	 */
	public function content_drip_protection( $html = '' ) {
		$content_id      = get_the_ID();
		$contents        = tutor_utils()->get_course_prev_next_contents_by_id( $content_id );
		$image_base_path = 'images/illustrations/';
		$button_label    = '';
		$image_path      = '';

		if ( ! $this->is_lock_lesson( $content_id ) ) {
			return $html;
		}

		$item           = get_post( $content_id );
		$title          = '';
		$footer_label   = '';
		$badge          = '';
		$footer_content = '';

		$post_types = array(
			'lesson'             => __( 'lesson', 'tutor-pro' ),
			'tutor_quiz'         => __( 'quiz', 'tutor-pro' ),
			'tutor_assignments'  => __( 'assignment', 'tutor-pro' ),
			'tutor_zoom_meeting' => __( 'meeting', 'tutor-pro' ),
			'tutor-google-meet'  => __( 'meeting', 'tutor-pro' ),
		);

		if ( 'unlock_sequentially' === $this->drip_type && $contents->previous_id ) {
			$title         = __( "You're Almost There", 'tutor-pro' );
			$image_path    = $image_base_path . 'continue-previous-content.svg';
			$previous_item = get_post( $contents->previous_id );

			/* translators: %s item type */
			$button_label = sprintf( __( 'Continue Previous %s', 'tutor-pro' ), ucfirst( $post_types[ $previous_item->post_type ] ?? '' ) );
			/* translators: %s item type */
			$footer_label = sprintf( __( '1 step remaining to unlock this %s', 'tutor-pro' ), $post_types[ $item->post_type ] ?? '' );
			$content_id   = $contents->previous_id ?? 0;
			/* translators: %1$s item type %2$s item name */
			if ( empty( $this->unlock_message ) ) {
				$this->unlock_message = sprintf(
					/* translators: %1$s item type %2$s item name */
					__( 'Complete the previous %1$s to unlock this content and continue to <b>%2$s</b>', 'tutor-pro' ),
					$post_types[ $previous_item->post_type ] ?? '',
					$item->post_title
				);
			}
		} elseif ( 'after_finishing_prerequisites' === $this->drip_type ) {
			$title = sprintf(
				/* translators: %s item type */
				_n(
					'Complete the following prerequisite to unlock this %s',
					'Complete the following prerequisites to unlock this %s',
					count( $this->prerequisites ),
					'tutor-pro'
				),
				ucfirst( $post_types[ $item->post_type ] ?? '' )
			);
			$image_path = $image_base_path . 'prerequisites.svg';
			$badge      = Badge::make()
							->label( __( 'Prerequisites Required', 'tutor-pro' ) )
							->icon( Icon::DOT, 12 )
							->variant( Badge::WARNING )
							->rounded()
							->get();

			$footer_content = $this->render_prerequisite_list();

			$this->unlock_message = '';

		} else {
			/* translators: %s item type */
			$title        = sprintf( __( 'This %s is locked', 'tutor-pro' ), ucfirst( $post_types[ $item->post_type ] ?? '' ) );
			$button_label = __( 'Go To Next', 'tutor-pro' );
			$image_path   = $image_base_path . 'not-available-yet.svg';
			$content_id   = $contents->next_id ?? 0;

			if ( 'unlock_by_date' === $this->drip_type ) {
				/* translators: %1$s item type %2$s unlock date */
				$this->unlock_message = sprintf( __( 'This %1$s will be available from <b>%2$s</b>. Please return after the release date to begin.', 'tutor-pro' ), $post_types[ $item->post_type ] ?? '', $this->unlock_date );
			}
		}

		$drip_title   = apply_filters( 'tutor/content_drip/title', sprintf( '<h3 class="tutor-h3 tutor-font-semibold tutor-pb-4">%s</h3>', esc_html( $title ) ) );
		$drip_message = apply_filters( 'tutor/content_drip/unlock_message', sprintf( '<p class="tutor-text-secondary">%s</p>', $this->unlock_message ) );

		ob_start();
		require TUTOR_CONTENT_DRIP()->path . '/views/content-drip-card.php';
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Remove mark as complete button from lesson
	 * if content drip enabled.
	 *
	 * @since 4.0.0
	 *
	 * @param string $mark_as_complete_btn the button html.
	 *
	 * @return string
	 */
	public function remove_mark_as_complete( $mark_as_complete_btn ) {
		$content_id = get_the_ID();

		if ( ! $this->is_lock_lesson( $content_id ) ) {
			return $mark_as_complete_btn;
		}

		return '';
	}

	/**
	 * Register email list for dashboard.
	 *
	 * @since 1.7.4
	 *
	 * @param array $emails the emails.
	 *
	 * @return array
	 */
	public function register_email_list( $emails ) {

		$lqa = '{site_url}, {site_name}, {student_username}, {lqa_type}, {course_title}, ';

		$emails['email_to_students.new_lesson_published']     = array( __( 'Content Drip: New Lesson Published', 'tutor-pro' ), $lqa . '{lesson_title}' );
		$emails['email_to_students.new_quiz_published']       = array( __( 'Content Drip: New Quiz Published', 'tutor-pro' ), $lqa . '{quiz_title}' );
		$emails['email_to_students.new_assignment_published'] = array( __( 'Content Drip: New Assignment Published', 'tutor-pro' ), $lqa . '{assignment_title}' );

		return $emails;
	}

	/**
	 * List all the courses where content drip enabled.
	 *
	 * @since 1.9.8
	 *
	 * @return array
	 */
	private function get_mailing_courses() {

		global $wpdb;

		$drip_enabled = esc_sql( 's:19:"enable_content_drip";s:1:"1";' );

		// Get courses that is published and content drip enabled.
		$courses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_title, {$wpdb->postmeta}.meta_value
			FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta}
			ON {$wpdb->posts}.ID={$wpdb->postmeta}.post_id
			WHERE {$wpdb->posts}.post_status= %s
				AND {$wpdb->postmeta}.meta_key= %s
				AND {$wpdb->postmeta}.meta_value LIKE %s",
				'publish',
				'_tutor_course_settings',
				'%' . $drip_enabled . '%'
			)
		);

		$courses = array_map(
			function ( $element ) {
				$element->meta_value = unserialize( $element->meta_value );
				return $element;
			},
			$courses
		);

		return $courses;
	}

	/**
	 * Get all the lesson, quizzes and assignments by course ID.
	 *
	 * @since 1.9.8
	 *
	 * @param int $course_id the course id.
	 *
	 * @return array
	 */
	private function get_mailing_course_children( $course_id ) {

		global $wpdb;
		$topic_ids     = "SELECT ID FROM {$wpdb->posts} WHERE post_parent={$course_id} AND post_type='topics'";
		$content_types = "'lesson', 'tutor_quiz', 'tutor_assignments'";

		//phpcs:disable
		return $wpdb->get_results(
			"SELECT ID, post_title, post_type
			FROM {$wpdb->posts}
			WHERE post_parent IN ({$topic_ids})
				AND post_type IN ({$content_types})"
		);
		//phpcs:enable
	}

	/**
	 * Check if mail sent to specific user for specific lesson-quiz-assignment publish.
	 *
	 * @since 1.7.2
	 *
	 * @param int $student_id the student id.
	 * @param int $content_id the content id.
	 * @param int $time_stamp the time stamp.
	 *
	 * @return bool
	 */
	private function is_mail_sent( $student_id, $content_id, $time_stamp ) {

		if ( ! isset( $this->sent_mail_log[ $student_id ] ) ) {
			$log                                = get_user_meta( $student_id, $this->mail_log_meta_key, true );
			$this->sent_mail_log[ $student_id ] = is_array( $log ) ? $log : array();
		}

		$log = array_key_exists( $content_id, $this->sent_mail_log[ $student_id ] ) ? $this->sent_mail_log[ $student_id ][ $content_id ] : array();

		if ( in_array( $time_stamp, $log ) ) {
			return true;
		}

		$log[] = $time_stamp;
		$this->sent_mail_log[ $student_id ][ $content_id ] = $log;
	}

	/**
	 * Get the timestamp when the LQA should be considered as published.
	 *
	 * @since 1.7.2
	 *
	 * @param int    $content_id the content id.
	 * @param string $enroll_date the enroll date.
	 * @param bool   $unlock_by_date the unlock by date.
	 *
	 * @return int
	 */
	private function get_content_publish_timestamp( $content_id, $enroll_date, bool $unlock_by_date ) {
		$timestamp = null;

		if ( $unlock_by_date ) {
			$timestamp = (int) strtotime( get_item_content_drip_settings( $content_id, 'unlock_date' ) );
		} else {
			$days = (int) get_item_content_drip_settings( $content_id, 'after_xdays_of_enroll' );

			if ( $days > 0 ) {
				$enroll_date  = gmdate( 'Y-m-d', strtotime( $enroll_date ) );
				$days_in_time = 60 * 60 * 24 * $days;

				$timestamp = strtotime( $enroll_date ) + $days_in_time;
			}
		}

		return $timestamp;
	}

	/**
	 * Get enrollment list of published course by course ID.
	 *
	 * @since 1.9.10
	 *
	 * @param int $course_id the course id.
	 *
	 * @return array
	 */
	private function get_mailing_enrollments( $course_id ) {

		global $wpdb;

		$enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$wpdb->posts}.ID as enrolment_id,
				{$wpdb->posts}.post_date AS enroll_date,
				{$wpdb->users}.ID as student_id,
				{$wpdb->users}.user_email,
				{$wpdb->users}.display_name
			FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->users} ON {$wpdb->posts}.post_author={$wpdb->users}.ID
			WHERE {$wpdb->posts}.post_parent=%d
				AND {$wpdb->posts}.post_type='tutor_enrolled'
				AND {$wpdb->posts}.post_status='completed'",
				$course_id
			)
		);

		return $enrollments;
	}

	/**
	 * Execute content drip publish hook.
	 *
	 * @since 1.9.10
	 *
	 * @return void
	 */
	public function execute_content_drip_publish_hook() {

		$last_call = get_option( 'tutor_cd_last_call_time', null );

		if ( ! $last_call || $last_call < ( time() - 3600 ) ) {
			update_option( 'tutor_cd_last_call_time', time(), true );
		} else {
			return;
		}

		$mail_enable_status = array();

		// Loop through published courses.
		$courses = $this->get_mailing_courses();
		foreach ( $courses as $course ) {

			$drip_type = isset( $course->meta_value['content_drip_type'] ) ? $course->meta_value['content_drip_type'] : null;

			if ( ! $drip_type || ( 'unlock_by_date' !== $drip_type && 'specific_days' !== $drip_type ) ) {
				// No need to send mail for other drip types. Or no drip defined.
				continue;
			}

			$students = $this->get_mailing_enrollments( $course->ID );
			$contents = $this->get_mailing_course_children( $course->ID );

			// Loop through lesson, quiz and assignments.
			foreach ( $contents as $content ) {

				$event = trim( $content->post_type, 'tutor_' ); // lesson, quiz or assignments.
				$event = trim( $event, 's' ); // lesson, quiz or assignment.

				if ( ! array_key_exists( $event, $mail_enable_status ) ) {
					$mail_enable_status[ $event ] = tutor_utils()->get_option( 'email_to_students.new_' . $event . '_published' );
				}

				if ( ! $mail_enable_status[ $event ] ) {
					continue;
				}

				foreach ( $students as $student ) {

					$unlock_timestamp = $this->get_content_publish_timestamp( $content->ID, $student->enroll_date, 'unlock_by_date' === $drip_type );

					if ( ! $unlock_timestamp || $unlock_timestamp > time() ) {
						// Check if publish time passed.
						continue;
					}

					if ( ! $this->is_mail_sent( $student->student_id, $content->ID, $unlock_timestamp ) ) {

						$arg = array(
							'student'  => $student,
							'lqa'      => $content,
							'course'   => $course,
							'lqa_type' => ucfirst( $event ),
						);

						do_action( 'tutor-pro/content-drip/new_' . $event . '_published', $arg );
					}
				}
			}
		}

		foreach ( $this->sent_mail_log as $user_id => $log ) {
			update_user_meta( $user_id, $this->mail_log_meta_key, $log );
		}
	}

	/**
	 * Get content drip settings of a lesson.
	 *
	 * @since 3.0.0
	 *
	 * @param int $lesson_id lesson id.
	 *
	 * @return array
	 */
	public static function get_content_drip_settings( $lesson_id ) {
		$settings_meta = get_post_meta( $lesson_id, '_content_drip_settings', true );
		$settings      = (array) maybe_unserialize( $settings_meta );
		return $settings;
	}

	/**
	 * Get content drip prerequisite courses
	 *
	 * @since 3.0.0
	 *
	 * @param array $post_id post id.
	 *
	 * @return array
	 */
	public static function get_content_drip_prerequisite_courses( $post_id ) {
		$items = array();

		$content_drip_settings = self::get_content_drip_settings( $post_id );
		$prerequisite_ids      = $content_drip_settings['prerequisites'] ?? array();

		if ( is_array( $prerequisite_ids ) && count( $prerequisite_ids ) ) {
			$courses = get_posts(
				array(
					'post_type'      => tutor()->course_post_type,
					'post__in'       => $prerequisite_ids,
					'posts_per_page' => -1,
				)
			);

			foreach ( $courses as $course ) {
				$tmp                 = new stdClass();
				$tmp->id             = $course->ID;
				$tmp->post_title     = $course->post_title;
				$tmp->featured_image = get_the_post_thumbnail_url( $course->ID );

				if ( ! $tmp->featured_image ) {
					$tmp->featured_image = CourseModel::get_course_preview_image_placeholder();
				}

				$items[] = $tmp;
			}
		}

		return $items;
	}

	/**
	 * Extend lesson details response.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data data.
	 * @param int   $lesson_id lesson id.
	 *
	 * @return array
	 */
	public function extend_lesson_details_response( $data, $lesson_id ) {
		if ( $lesson_id ) {
			$content_drip_settings = self::get_content_drip_settings( $lesson_id );
			$courses               = self::get_content_drip_prerequisite_courses( $lesson_id );

			if ( is_array( $courses ) && count( $courses ) ) {
				$content_drip_settings['course_prerequisites'] = $courses;
			}

			$data['content_drip_settings'] = $content_drip_settings;
		}

		return $data;
	}

	/**
	 * Extend assignment details response.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data data.
	 * @param int   $assignment_id assignment id.
	 *
	 * @return array
	 */
	public function extend_assignment_details_response( $data, $assignment_id ) {
		if ( $assignment_id ) {
			$content_drip_settings = self::get_content_drip_settings( $assignment_id );
			$courses               = self::get_content_drip_prerequisite_courses( $assignment_id );

			if ( is_array( $courses ) && count( $courses ) ) {
				$content_drip_settings['course_prerequisites'] = $courses;
			}

			$data['content_drip_settings'] = $content_drip_settings;
		}

		return $data;
	}
}
