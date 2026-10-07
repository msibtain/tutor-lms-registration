<?php
/**
 * Manage Assets.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

use Tutor\Ecommerce\Tax;
use TUTOR\Input;
use TutorPro\Subscription\Models\PlanModel;

/**
 * Assets Class.
 *
 * @since 3.0.0
 */
class Assets {
	/**
	 * Register hooks and dependency
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_script' ) );
		add_filter( 'tutor_localize_data', array( $this, 'extend_localize_data' ) );
		add_filter( 'tutor_course_builder_localized_data', array( $this, 'extend_localize_data' ) );
	}

	/**
	 * Load admin assets
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function admin_script() {
		$is_report_page = Input::get( 'page' ) === 'tutor_report' && Input::get( 'sub_page' ) === 'subscriptions';

		if ( in_array( Input::get( 'page' ), array( 'tutor-subscriptions', 'tutor_settings' ), true ) || $is_report_page ) {
			wp_enqueue_script( 'tutor-subscription-backend', Utils::asset_url( 'js/backend.js' ), array(), TUTOR_PRO_VERSION, true );
		}

		if ( 'tutor_settings' === Input::get( 'page' ) && ! Input::has( 'edit' ) ) {
			wp_enqueue_script( 'tutor-membership-settings', Utils::asset_url( 'js/membership-settings/index.js' ), array( 'wp-i18n', 'wp-element', 'wp-date' ), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Load frontend assets
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function frontend_script() {
		global $post;

		$course_details_page   = is_single() && tutor()->course_post_type === $post->post_type;
		$bundle_details_page   = is_single() && tutor()->bundle_post_type === $post->post_type;
		$is_subscriptions_page = tutor_utils()->is_tutor_frontend_dashboard( 'subscriptions' );

		if ( $is_subscriptions_page || Utils::is_pricing_page() || $course_details_page || $bundle_details_page ) {
			wp_enqueue_style( 'tutor-subscription-frontend', Utils::asset_url( 'css/frontend.css' ), array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-subscription-frontend', Utils::asset_url( 'js/frontend.js' ), array(), TUTOR_PRO_VERSION, true );
		}

		if ( tutor_utils()->is_dashboard_page( 'account' ) && 'subscriptions' === Input::get( 'tab' ) ) {
			wp_enqueue_style( 'tutor-subscription-frontend', Utils::asset_url( 'css/dashboard.css' ), array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-subscription-dashboard', Utils::asset_url( 'js/dashboard.js' ), array(), TUTOR_PRO_VERSION, true );
		}
	}

	/**
	 * Extend localize data
	 *
	 * @since 3.3.0
	 *
	 * @param array $data data.
	 *
	 * @return array
	 */
	public function extend_localize_data( $data ) {
		$data['settings']['membership_only_mode']          = Settings::membership_only_mode_enabled();
		$data['settings']['has_active_membership_plans']   = ( new PlanModel() )->has_active_membership_plans();
		$data['settings']['enable_tax']                    = Tax::get_setting( 'enable_tax', true );
		$data['settings']['enable_individual_tax_control'] = Tax::get_setting( 'enable_individual_tax_control' );
		$data['settings']['is_tax_included_in_price']      = Tax::is_tax_included_in_price();

		return $data;
	}
}
