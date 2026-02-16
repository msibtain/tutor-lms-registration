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
	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'tutor_before_ecommerce_payment_settings', array( $this, 'add_subscription_settings' ) );
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
		$arr['ecommerce_subscription'] = array(
			'label'    => __( 'Subscriptions', 'tutor-pro' ),
			'slug'     => 'ecommerce_subscription',
			'desc'     => __( 'Subscription Settings', 'tutor-pro' ),
			'template' => 'basic',
			'icon'     => 'tutor-icon-subscription',
			'blocks'   => array(
				array(
					'label'      => false,
					'block_type' => 'uniform',
					'slug'       => 'basic',
					'fields'     => array(
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
					),
				),

			),
		);

		return $arr;
	}
}
