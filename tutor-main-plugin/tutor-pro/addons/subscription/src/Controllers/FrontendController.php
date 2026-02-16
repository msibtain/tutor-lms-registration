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
use Tutor\Helpers\DateTimeHelper;
use TUTOR\Input;
use Tutor\Models\OrderModel;
use TutorPro\Subscription\Models\PlanModel;
use TutorPro\Subscription\Models\SubscriptionModel;
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

		add_filter( 'get_tutor_course_price', array( $this, 'set_loop_price' ), 10, 2 );
		add_filter( 'tutor_course_loop_add_to_cart_button', array( $this, 'course_loop_add_to_cart_button' ), 10, 2 );

		add_filter( 'tutor/course/single/entry-box/purchasable', array( $this, 'show_course_subscription_plans' ), 12, 2 );
		add_action( 'tutor_course/single/entry/after', array( $this, 'subscription_expire_info' ), 9 );

		add_filter( 'tutor_after_order_history_menu', array( $this, 'register_subscription_menu' ) );
		add_action( 'load_dashboard_template_part_from_other_location', array( $this, 'subscription_view' ) );

		// Course archive.
		add_filter( 'tutor_pro_certificate_access', array( $this, 'certificate_access_for_plan' ), 10, 2 );

		add_filter( 'tutor_pro_check_course_expiry', array( $this, 'bypass_course_expiry_check' ), 11, 2 );
	}

	/**
	 * Set loop price for course subscription plan.
	 *
	 * @since 3.0.0
	 *
	 * @param string $price price.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function set_loop_price( $price, $course_id ) {
		$selling_option = Course::get_selling_option( $course_id );

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true ) ) {
			return $price;
		}

		$plan_model        = new PlanModel();
		$course_plans      = $plan_model->get_course_plans( $course_id );
		$lowest_price_plan = $plan_model->get_lowest_price_plan( $course_plans );

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

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true ) ) {
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
	 * Show course subscription plans.
	 *
	 * @since 3.0.0
	 *
	 * @param string $html price html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function show_course_subscription_plans( $html, $course_id ) {
		$selling_option = Course::get_selling_option( $course_id );

		if ( ! in_array( $selling_option, array( Course::SELLING_OPTION_BOTH, Course::SELLING_OPTION_SUBSCRIPTION ), true ) ) {
			return $html;
		}

		$plan_model   = new PlanModel();
		$course_plans = $plan_model->get_course_plans( $course_id );
		if ( ! is_array( $course_plans ) || 0 === count( $course_plans ) ) {
			return $html;
		}

		ob_start();
		$template = Utils::template_path( 'single/course-plans.php' );
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
		$price_type = tutor_utils()->price_type( $course_id );
		if ( Course::PRICE_TYPE_PAID === $price_type ) {
			$subscription = $this->subscription_model->is_any_course_plan_subscribed( $course_id );
			if ( $subscription && SubscriptionModel::STATUS_ACTIVE === $subscription->status ) {
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
			}

			return;
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
	public function register_subscription_menu( $nav_items ) {
		$nav_items = apply_filters( 'tutor_pro_before_subscription_menu', $nav_items );

		$nav_items['subscriptions'] = array(
			'title' => __( 'Subscriptions', 'tutor-pro' ),
			'icon'  => 'tutor-icon-subscription',
		);

		return apply_filters( 'tutor_pro_after_subscription_menu', $nav_items );
	}

	/**
	 * Show subscription view.
	 *
	 * @since 3.0.0
	 *
	 * @param string $template template.
	 *
	 * @return string
	 */
	public function subscription_view( $template ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( isset( $query_vars['tutor_dashboard_page'] ) && 'subscriptions' === $query_vars['tutor_dashboard_page'] ) {
			if ( Input::get( 'id', 0, Input::TYPE_INT ) ) {
				$template = Utils::template_path( 'dashboard/subscription-details.php' );
				return $template;
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

		$course_enrolled = tutor_utils()->is_enrolled( $course_id, $user_id );

		if ( $course_enrolled ) {
			$order_id = (int) get_post_meta( $course_enrolled->ID, '_tutor_enrolled_by_order_id', true );
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
}
