<?php
/**
 * Handler for Frontend Subscription.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Controllers;

use TUTOR\Course;
use TUTOR\Course_List;
use TUTOR\Dashboard;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\UrlHelper;
use TUTOR\Icon;
use TUTOR\Input;
use Tutor\Models\EnrollmentModel;
use Tutor\Models\OrderModel;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Models\BundleModel;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;
use TutorPro\Subscription\Settings;
use TutorPro\Subscription\Utils;

/**
 * FrontendController Class.
 *
 * @since 3.0.0
 */
class FrontendController {
	/**
	 * Plan model
	 *
	 * @var PlanModel
	 */
	private $plan_model;

	/**
	 * Subscription model.
	 *
	 * @var SubscriptionModel
	 */
	private $subscription_model;

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->plan_model         = new PlanModel();
		$this->subscription_model = new SubscriptionModel();

		add_filter( 'get_tutor_course_price', array( $this, 'set_course_price' ), 10, 2 );
		add_filter( 'tutor_course_loop_add_to_cart_button', array( $this, 'course_loop_add_to_cart_button' ), 10, 2 );

		add_filter( 'tutor/course/single/entry-box/purchasable', array( $this, 'show_subscription_plans' ), 12, 2 );
		add_action( 'tutor_course/single/entry/after', array( $this, 'subscription_expire_info' ), 9 );

		add_filter( 'tutor_dashboard_account_billing_page_nav_items', array( $this, 'add_subscription_tab' ) );
		add_action( 'tutor_dashboard_account_billing_tab_template', array( $this, 'subscription_view' ), 10, 2 );

		// Course archive.
		add_filter( 'tutor_pro_certificate_access', array( $this, 'certificate_access_for_plan' ), 10, 2 );

		add_filter( 'tutor_pro_check_course_expiry', array( $this, 'bypass_course_expiry_check' ), 11, 2 );

		add_filter( 'is_course_purchasable', array( $this, 'update_is_course_purchaseable' ), 10, 2 );
		add_filter( 'tutor_enroll_now_link_attrs', array( $this, 'add_subscription_attr_for_enrollment' ), 10, 2 );
		add_filter( 'tutor/course/single/entry-box/free', array( $this, 'course_free_access_control' ), 10, 2 );
		add_filter( 'tutor/course/single/entry-box/is_enrolled', array( $this, 'handle_enrolled_state' ), 10, 2 );
		add_action( 'tutor_after_enrolled', array( $this, 'handle_after_enrollment_completed' ), 10, 3 );
		add_action( 'tutor/course/single/content/before/all', array( $this, 'handle_course_content_access' ), 100, 2 );

		add_filter( 'tutor_membership_only_mode', fn() => Settings::membership_only_mode_enabled() );
		add_filter( 'tutor_course_loop_price', array( $this, 'course_loop_for_membership_only_mode' ), 10, 2 );
		add_filter( 'tutor_has_bundle_course_subscription_access', array( $this, 'has_bundle_course_subscription_access' ), 10, 3 );
		add_filter( 'tutor_allow_guest_attempt_enrollment', array( $this, 'filter_allow_guest_attempt_enrollment' ), 11, 3 );
	}


	/**
	 * Update is course purchaseable.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $is_purchaseable is purchaseable.
	 * @param int  $course_id course id.
	 *
	 * @return bool
	 */
	public function update_is_course_purchaseable( $is_purchaseable, $course_id ) {
		if ( $this->subscription_model->has_course_access( $course_id ) ) {
			return false;
		}

		return $is_purchaseable;
	}

	/**
	 * Handle enrolled state
	 *
	 * @since 3.2.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function handle_enrolled_state( $html, $course_id ) {
		$user_id = get_current_user_id();

		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id, $user_id )
			&& ! $this->subscription_model->has_course_access( $course_id, $user_id )
		) {
			// Cancel the enrollment.
			$enrollment = EnrollmentModel::is_enrolled( $course_id, $user_id );
			if ( $enrollment ) {
				EnrollmentModel::update_enrollments( 'cancelled', array( $enrollment->ID ), false );
				tutor_utils()->redirect_to( get_the_permalink( $course_id ) );
				exit;
			}
		}

		return $html;
	}

	/**
	 * Handle subscription enrollment
	 *
	 * @since 3.2.0
	 *
	 * @param int $course_id course id.
	 * @param int $user_id user id.
	 * @param int $enrolled_id enrollment id.
	 *
	 * @return void
	 */
	public function handle_after_enrollment_completed( $course_id, $user_id, $enrolled_id ) {
		$subscription_enrollment_flag_required = false;

		// For hybrid mode.
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id ) && Input::has( 'tutor_subscription_enrollment' ) ) {
			$subscription_enrollment_flag_required = true;
		}

		// For membership only mode.
		if ( Settings::membership_only_mode_enabled() && Input::has( 'tutor_subscription_enrollment' ) ) {
			$subscription_enrollment_flag_required = true;
		}

		if ( $subscription_enrollment_flag_required ) {
			$user_active_subscriptions = $this->subscription_model->get_user_active_subscriptions( get_current_user_id() );
			if ( count( $user_active_subscriptions ) ) {
				$user_latest_subscription_id = (int) $user_active_subscriptions[0]->id;

				if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) && tutor()->bundle_post_type === get_post_type( $course_id ) ) {
					// Mark the bundle enrollment as subscription enrollment.
					$this->subscription_model->mark_as_subscription_enrollment( $enrolled_id, $user_latest_subscription_id );

					$bundle_course_ids = BundleModel::get_bundle_course_ids( $course_id );
					foreach ( $bundle_course_ids as $bundle_course_id ) {
						/**
						 * Auto bundle course enrollment by subscription.
						 * Course should be free with hybrid mode or need subscription access required.
						 */
						if ( ( ! Settings::membership_only_mode_enabled() && Course::PRICE_TYPE_FREE === tutor_utils()->price_type( $bundle_course_id ) )
							|| $this->subscription_model->has_course_access( $bundle_course_id, $user_id ) ) {

							add_filter( 'tutor_enroll_data', fn( $enroll_data ) => array_merge( $enroll_data, array( 'post_status' => 'completed' ) ) );
							$course_enrolled_id = tutor_utils()->do_enroll( $bundle_course_id, 0, $user_id, false );
							$this->subscription_model->mark_as_subscription_enrollment( $course_enrolled_id, $user_latest_subscription_id );
							update_post_meta( $course_enrolled_id, CourseBundle::BUNDLE_ENROLLMENT_META, $course_id );
						}
					}
				} else {
					$this->subscription_model->mark_as_subscription_enrollment( $enrolled_id, $user_latest_subscription_id );
				}
			}
		}
	}

	/**
	 * Check active subscription before course content access.
	 *
	 * @since 3.2.0
	 *
	 * @param int $course_id  current course id.
	 * @param int $content_id course content like lesson, quiz etc.
	 *
	 * @return void
	 */
	public function handle_course_content_access( $course_id, $content_id ) {

		$user_id                  = get_current_user_id();
		$has_course_access        = tutor_utils()->has_user_course_content_access( $user_id, $course_id );
		$is_enrolled              = EnrollmentModel::is_enrolled( $course_id, $user_id );
		$enrolled_by_subscription = $this->subscription_model->is_enrolled_by_subscription( $course_id );
		$is_preview_enabled       = tutor()->lesson_post_type === get_post_type( $content_id ) ? (bool) get_post_meta( $content_id, '_is_preview', true ) : false;

		if ( $has_course_access || ( $is_enrolled && ! $enrolled_by_subscription ) || $is_preview_enabled ) {
			return;
		}

		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id )
		&& ! $this->subscription_model->has_course_access( $course_id ) ) {
			wp_safe_redirect( get_permalink( $course_id ) );
			exit;
		}
	}

	/**
	 * Free enrol to course when subscription is active.
	 *
	 * @since 3.2.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_free_access_control( $html, $course_id ) {
		$free_enrollment_access = false;

		// For hybrid mode.
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id ) && $this->subscription_model->has_course_access( $course_id ) ) {
			$free_enrollment_access = true;
		}

		// For membership only mode.
		if ( Settings::membership_only_mode_enabled() && $this->subscription_model->has_course_access( $course_id ) ) {
			$free_enrollment_access = true;
		}

		if ( $free_enrollment_access ) {
			ob_start();
			include Utils::template_path( 'single/entry-box-free.php' );
			$html = ob_get_clean();
		} else {
			if ( Settings::membership_only_mode_enabled() ) {
				$selling_option = Course::SELLING_OPTION_MEMBERSHIP;
				ob_start();
				$template = Utils::template_path( 'single/subscription-plans.php' );
				include_once $template;
				$html = ob_get_clean();
			}
		}

		return apply_filters( 'tutor_pro_subscription_entry_box', $html, $course_id );
	}

	/**
	 * Add subscription enrollment attribute to enroll now link.
	 *
	 * @since 3.2.0
	 *
	 * @param array $attr associate array with key value.
	 * @param int   $course_id course id.
	 *
	 * @return string
	 */
	public function add_subscription_attr_for_enrollment( $attr, $course_id ) {
		// For hybrid mode.
		if ( Course::PRICE_TYPE_PAID === tutor_utils()->price_type( $course_id )
			&& $this->subscription_model->has_course_access( $course_id ) ) {
			$attr['data-subscription-enrollment'] = true;
		}

		// For membership only mode.
		if ( Settings::membership_only_mode_enabled() && $this->subscription_model->has_course_access( $course_id ) ) {
			$attr['data-subscription-enrollment'] = true;
		}

		return $attr;
	}

	/**
	 * Set price for course subscription plan.
	 *
	 * @since 3.0.0
	 *
	 * @param string $price price.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function set_course_price( $price, $course_id ) {
		/**
		 * For membership only mode.
		 * Show membership plans for courses and bundles on backend and frontend course and bundle data list pages.
		 *
		 * @since 3.3.0
		 */
		if ( Settings::membership_only_mode_enabled() ) {
			$post_type = get_post_type( $course_id );
			if ( tutor()->bundle_post_type === $post_type ) {
				$course_ids = BundleModel::get_bundle_course_ids( $course_id );
			} else {
				$course_ids = array( $course_id );
			}

			$plans_str = '...';
			$plans     = $this->plan_model->get_membership_plans_for_course_ids( $course_ids );
			if ( count( $plans ) ) {
				$plans_str = implode( ', ', wp_list_pluck( $plans, 'plan_name' ) );
			}

			ob_start();
			?>
			<span class="tutor-font-normal tutor-text-small tutor-truncate" title="<?php echo esc_attr( $plans_str ); ?>"><?php echo esc_html( $plans_str ); ?></span>
			<?php
			return ob_get_clean();
		}

		/**
		 * Whitelisted pages
		 * 1. admin dashboard course list.
		 * 2. frontend dashboard my courses page.
		 */
		$is_whitelisted_page = ( is_admin() && 'tutor' === Input::get( 'page' ) ) || tutor_utils()->is_tutor_frontend_dashboard( 'my-courses' );

		if ( ! $is_whitelisted_page && $this->subscription_model->has_course_access( $course_id ) ) {
			// Set the course as free for enrollment.
			return null;
		}

		$selling_option = Course::get_selling_option( $course_id );

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION, Course::SELLING_OPTION_MEMBERSHIP ), true ) ) {
			return $price;
		}

		$plan_model        = new PlanModel();
		$course_plans      = $plan_model->get_subscription_plans( $course_id );
		$lowest_price_plan = $plan_model->get_lowest_price_plan( $course_plans );

		if ( Course::SELLING_OPTION_MEMBERSHIP === $selling_option ) {
			$active_membership_plans = $this->plan_model->get_membership_plans( PlanModel::STATUS_ACTIVE );
			$lowest_price_plan       = $this->plan_model->get_lowest_price_plan( $active_membership_plans );
		}

		if ( ! $lowest_price_plan ) {
			return $price;
		} else {
			ob_start();
			include Utils::template_path( 'loop/subscription-price.php' );
			return ob_get_clean();
		}
	}

	/**
	 * Change add to cart button in course loop
	 *
	 * @since 3.0.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_loop_add_to_cart_button( $html, $course_id ) {
		$selling_option = Course::get_selling_option( $course_id );

		if ( empty( $selling_option ) || Course::SELLING_OPTION_ONE_TIME === $selling_option ) {
			return $html;
		}

		ob_start();
		$url = get_the_permalink( $course_id );

		?>
		<a href="<?php echo esc_url( $url ); ?>" class="tutor-btn tutor-btn-outline-primary tutor-btn-md tutor-btn-block">
			<?php esc_html_e( 'View Details', 'tutor-pro' ); ?>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Show subscription plans.
	 *
	 * @since 3.0.0
	 *
	 * @param string $html price html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function show_subscription_plans( $html, $course_id ) {
		$selling_option = Settings::membership_only_mode_enabled()
							? Course::SELLING_OPTION_MEMBERSHIP
							: Course::get_selling_option( $course_id );

		if ( Course::SELLING_OPTION_ONE_TIME === $selling_option ) {
			return $html;
		}

		ob_start();
		$template = Utils::template_path( 'single/subscription-plans.php' );
		include_once $template;
		return ob_get_clean();
	}

	/**
	 * Show subscription expire info.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void|null
	 */
	public function subscription_expire_info( $course_id ) {
		if ( $this->subscription_model->is_enrolled_by_subscription( $course_id )
			&& $this->subscription_model->has_course_access( $course_id )
		) {
			$user_id                   = get_current_user_id();
			$user_active_subscriptions = $this->subscription_model->get_user_active_subscriptions( $user_id );
			if ( count( $user_active_subscriptions ) ) {
				$subscription = $user_active_subscriptions[0];
				remove_all_filters( 'tutor_course/single/entry/after' );

				$plan     = $this->plan_model->get_plan( $subscription->plan_id );
				$validity = '';
				if ( PlanModel::PAYMENT_ONETIME === $plan->payment_type ) {
					$validity = __( 'Lifetime', 'tutor-pro' );
				} else {
					if ( ! empty( $subscription->next_payment_date_gmt ) ) {
						$validity = DateTimeHelper::get_gmt_to_user_timezone_date( $subscription->next_payment_date_gmt, 'd M, Y' );
					}
				}

				echo '<div class="enrolment-expire-info tutor-fs-7 tutor-color-muted tutor-d-flex tutor-align-center tutor-mt-12">
					<i class="tutor-icon-calender-line tutor-mr-8"></i> ' .
						wp_kses_post(
							sprintf(
								/* translators: %1$s: opening tag, %2$s: date, %3$s: closing tag */
								__( 'Subscription validity: %1$s%2$s%3$s', 'tutor-pro' ),
								'<span class="tutor-ml-4">',
								$validity,
								'</span>'
							)
						)
					. '</div >';
				return;
			}
		} else {
			if ( $this->subscription_model->is_enrolled_by_subscription( $course_id ) ) {
				remove_all_filters( 'tutor_course/single/entry/after' );
				echo '';
				return;
			}
		}
	}

	/**
	 * Register frontend subscription menu.
	 *
	 * @since 3.0.0
	 *
	 * @param array $nav_items nav items.
	 *
	 * @return array
	 */
	public function add_subscription_tab( $nav_items ) {
		$billing_url = Dashboard::get_account_page_url( 'billing' );
		$nav_items[] = array(
			'type'   => 'link',
			'label'  => __( 'Subscriptions', 'tutor-pro' ),
			'icon'   => Icon::BILLING,
			'url'    => UrlHelper::add_query_params( $billing_url, array( 'tab' => 'subscriptions' ) ),
			'active' => 'subscriptions' === Input::get( 'tab' ),
		);

		return $nav_items;
	}

	/**
	 * Show subscription view.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template template.
	 * @param string $current_tab current tab.
	 *
	 * @return string
	 */
	public function subscription_view( $template, $current_tab ) {
		if ( 'subscriptions' === $current_tab ) {
			if ( Input::has( 'id' ) ) {
				add_filter( 'tutor_dashboard_account_billing_show_tab_nav', '__return_false' );
			}

			$template = Utils::template_path( 'dashboard/subscriptions.php' );
			if ( file_exists( $template ) ) {
				return $template;
			}
		}

		return $template;
	}

	/**
	 * Certificate access for plan.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $has_access has access.
	 * @param object $completed_record course complete record object.
	 *
	 * @return bool
	 */
	public function certificate_access_for_plan( $has_access, $completed_record ) {
		if ( isset( $completed_record->course_id, $completed_record->completed_user_id ) ) {
			$course_id    = $completed_record->course_id;
			$user_id      = $completed_record->completed_user_id;
			$subscription = $this->subscription_model->is_any_course_plan_subscribed( $course_id, $user_id );
			if ( $subscription ) {
				$plan = $this->plan_model->get_plan( $subscription->plan_id );
				if ( $plan && ! $plan->provide_certificate ) {
					$has_access = false;
				}
			}
		}

		return $has_access;
	}

	/**
	 * Bypass course expiry check for enrolled by subscription plan.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $bool true or false.
	 * @param int  $course_id course id.
	 *
	 * @return bool
	 */
	public function bypass_course_expiry_check( $bool, $course_id ) {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! tutor_utils()->is_monetize_by_tutor() ) {
			return $bool;
		}

		$course_enrolled = EnrollmentModel::is_enrolled( $course_id, $user_id );

		if ( $course_enrolled ) {
			$order_id = (int) get_post_meta( $course_enrolled->ID, EnrollmentModel::ENROLLMENT_ORDER_ID_META, true );
			if ( $order_id ) {
				$order_details = ( new OrderModel() )->get_order_by_id( $order_id );
				if ( $order_details && OrderModel::TYPE_SUBSCRIPTION === $order_details->order_type ) {
					/**
					 * If user enrolled by subscription plan, then bypass course expiry check.
					 *
					 * @since 3.0.0
					 */
					return false;
				}
			}
		}

		return $bool;
	}

	/**
	 * Course loop for membership only mode.
	 *
	 * @since 3.3.0
	 *
	 * @param string $html price html content.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_loop_for_membership_only_mode( $html, $course_id ) {
		if ( Settings::membership_only_mode_enabled() ) {
			if ( Course_List::is_public( $course_id ) ) {
				return $html;
			}
			// Keep the enrollment if user already purchased this with single course purchase.
			if ( EnrollmentModel::is_enrolled( $course_id ) && ! $this->subscription_model->is_enrolled_by_subscription( $course_id ) ) {
				return $html;
			}

			if ( ! $this->subscription_model->has_course_access( $course_id ) ) {
				ob_start();
				include Utils::template_path( 'loop/membership-price.php' );
				return ob_get_clean();
			}
		}

		return $html;
	}

	/**
	 * Check has bundle course subscription access.
	 *
	 * @since 3.3.0
	 *
	 * @param bool $has_access has access.
	 * @param int  $bundle_id bundle id.
	 * @param int  $course_id course id.
	 *
	 * @return boolean
	 */
	public function has_bundle_course_subscription_access( $has_access, $bundle_id, $course_id ) {
		if ( ! EnrollmentModel::is_enrolled( $bundle_id ) ) {
			return $has_access;
		}

		/**
		 * If user is not enrolled this bundle by subscription plan, then return.
		 */
		if ( ! $this->subscription_model->is_enrolled_by_subscription( $bundle_id ) ) {
			return $has_access;
		}

		if ( ! $this->subscription_model->has_course_access( $course_id ) ) {
			$has_access = false;
		}

		return $has_access;
	}

	/**
	 * Filter allow guest attempt enrollment for membership only mode.
	 *
	 * @since 3.3.1
	 *
	 * @param bool $allowed allow for enrollment.
	 * @param int  $course_id course id.
	 * @param int  $user_id user id.
	 *
	 * @return bool
	 */
	public function filter_allow_guest_attempt_enrollment( $allowed, $course_id, $user_id ) {
		if ( Settings::membership_only_mode_enabled() ) {
			return false;
		}

		return $allowed;
	}
}
