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
	 * Register hooks and dependencies
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'tutor/options/extend/attr', array( $this, 'extend_settings' ) );
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
}
