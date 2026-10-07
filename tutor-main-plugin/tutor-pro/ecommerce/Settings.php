<?php
/**
 * Pro settings for Tutor Monetization
 *
 * @package TutorPro\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Ecommerce;


/**
 * Class Settings
 *
 * @since 3.0.0
 */
class Settings {

	/**
	 * Enable guest checkout option
	 *
	 * @since 3.3.0
	 *
	 * @var string
	 */
	const ENABLE_GUEST_CHECKOUT_OPT = 'is_enable_guest_checkout';

	/**
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 * @since 3.3.0 Guest settings added
	 */
	public function __construct() {
		add_filter( 'tutor/options/extend/attr', array( $this, 'extend_settings' ) );
		add_filter( 'tutor_after_ecommerce_settings', array( $this, 'add_guest_checkout_settings' ) );
	}

	/**
	 * Extend tutor settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $fields settings.
	 *
	 * @return array
	 */
	public function extend_settings( $fields ) {

		$invoice_block = array(
			'label'      => __( 'Invoice', 'tutor-pro' ),
			'slug'       => 'ecommerce_invoice',
			'block_type' => 'uniform',
			'fields'     => array(
				array(
					'key'         => 'invoice_from_address',
					'type'        => 'textarea',
					'label'       => __( 'From Address', 'tutor-pro' ),
					'placeholder' => __( 'From Address', 'tutor-pro' ),
					'desc'        => __( 'Specify the "From Address" that will appear in the top-right corner of the order invoice.', 'tutor-pro' ),
					'maxlength'   => 200,
					'rows'        => 5,
					'default'     => '',
				),
			),
		);

		$fields['monetization']['blocks']['ecommerce_block_invoice'] = $invoice_block;

		return $fields;
	}

	/**
	 * Add guest checkout settings to the settings page
	 *
	 * @since 3.3.0
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function add_guest_checkout_settings( array $settings ): array {
		$setting_field = array(
			'key'     => self::ENABLE_GUEST_CHECKOUT_OPT,
			'type'    => 'toggle_switch',
			'label'   => __( 'Enable Guest Checkout', 'tutor-pro' ),
			'default' => 'off',
			'desc'    => __( 'Allow users to checkout as a guest user.', 'tutor-pro' ),
		);

		$settings['ecommerce_checkout']['blocks'][0]['fields'][] = $setting_field;

		return $settings;
	}
}
