<?php
/**
 * Manage settings related to subscriptions.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

/**
 * Settings Class.
 *
 * @since 3.0.0
 */
class Settings {
	const PRICING_PAGE_SLUG        = 'membership-pricing';
	const PRICING_PAGE_OPTION_NAME = 'membership_pricing_page_id';

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'tutor_before_ecommerce_payment_settings', array( $this, 'add_subscription_settings' ) );
		add_action( 'tutor_pages', array( $this, 'add_pricing_page' ), 10, 1 );
		add_action( 'tutor_after_option_sub_section', array( $this, 'add_membership_only_mode_consent_modal' ) );
	}

	/**
	 * Get membership pricing page ID
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public static function get_pricing_page_id() {
		return (int) tutor_utils()->get_option( self::PRICING_PAGE_OPTION_NAME );
	}

	/**
	 * Get membership pricing page URL
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public static function get_pricing_page_url() {
		$page_id = self::get_pricing_page_id();
		return $page_id ? get_permalink( $page_id ) : '';
	}

	/**
	 * Add pricing page
	 *
	 * @since 2.1.0
	 *
	 * @param array $pages page list.
	 *
	 * @return array
	 */
	public function add_pricing_page( array $pages ) {
		return $pages + array( self::PRICING_PAGE_OPTION_NAME => __( 'Membership Pricing', 'tutor-pro' ) );
	}

	/**
	 * Check membership-only mode enabled.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	public static function membership_only_mode_enabled() {
		return (bool) tutor_utils()->get_option( 'membership_only_mode', false );
	}

	/**
	 * Add subscription settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $arr array.
	 *
	 * @return array
	 */
	public function add_subscription_settings( $arr ) {
		$pages = tutor_utils()->get_pages();

		$arr['ecommerce_subscription'] = array(
			'label'    => __( 'Subscriptions', 'tutor-pro' ),
			'slug'     => 'ecommerce_subscription',
			'desc'     => __( 'Subscription Settings', 'tutor-pro' ),
			'template' => 'basic',
			'icon'     => 'tutor-icon-subscription',
			'blocks'   => array(
				array(
					'label'         => __( 'Membership Plans', 'tutor-pro' ),
					'block_type'    => 'custom',
					'slug'          => 'memberships',
					'template_path' => Utils::template_path( 'membership-settings-block.php' ),
				),
				array(
					'label'      => __( 'Options', 'tutor-pro' ),
					'block_type' => 'uniform',
					'slug'       => 'options',
					'fields'     => array(
						array(
							'key'        => self::PRICING_PAGE_OPTION_NAME,
							'type'       => 'select',
							'label'      => __( 'Pricing Page', 'tutor-pro' ),
							'default'    => '0',
							'options'    => $pages,
							'desc'       => __( 'Select the Membership pricing page.', 'tutor-pro' ),
							'searchable' => true,
						),
						array(
							'key'         => 'subscription_cancel_anytime',
							'type'        => 'toggle_switch',
							'label'       => __( 'Cancel Anytime', 'tutor-pro' ),
							'label_title' => '',
							'default'     => 'on',
							'desc'        => __( 'Allow students to cancel their subscriptions whenever they want.', 'tutor-pro' ),
						),
						array(
							'key'         => 'subscription_early_renewal',
							'type'        => 'toggle_switch',
							'label'       => __( 'Early Renewal', 'tutor-pro' ),
							'label_title' => '',
							'default'     => 'off',
							'desc'        => __( 'Allow students to renew their subscriptions before next payment date.', 'tutor-pro' ),
						),
						array(
							'key'         => 'membership_only_mode',
							'type'        => 'toggle_switch',
							'label'       => __( 'Membership-Only Mode', 'tutor-pro' ),
							'label_title' => '',
							'default'     => 'off',
							'desc'        => __( 'Enable this to sell courses exclusively through membership plans. Individual course sales will be disabled.', 'tutor-pro' ),
						),
						array(
							'key'         => 'allow_trial_checkout_without_payment',
							'type'        => 'toggle_switch',
							'label'       => __( 'Skip Payment for Free Trials', 'tutor-pro' ),
							'label_title' => '',
							'default'     => 'off',
							'desc'        => __( 'Allow students to skip the payment gateway when the trial price is set to zero.', 'tutor-pro' ),
						),
					),
				),
			),
		);

		return $arr;
	}

	/**
	 * Membership only mode consent modal content.
	 *
	 * @since 3.3.0
	 *
	 * @param string $key key.
	 *
	 * @return void
	 */
	public function add_membership_only_mode_consent_modal( $key ) {
		if ( 'ecommerce_subscription' === $key ) {
			tutor_load_template_from_custom_path( Utils::template_path( 'membership-only-mode-modal.php' ) );
		}
	}
}
