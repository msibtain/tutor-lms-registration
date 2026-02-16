<?php
/**
 * WC Subscription Addon
 *
 * @package TutorPro\Addons
 * @subpackage WCSubscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defined the tutor main file
 */
define( 'TUTOR_WCS_VERSION', '1.0.0' );
define( 'TUTOR_WCS_FILE', __FILE__ );

add_filter( 'tutor_addons_lists_config', 'tutor_wcs_config' );

/**
 * Showing config for addons central lists
 *
 * @param array $config config.
 *
 * @return array
 */
function tutor_wcs_config( $config ) {
	$new_config = array(
		'name'           => __( 'WooCommerce Subscriptions', 'tutor-pro' ),
		'description'    => __( 'Capture Residual Revenue with Recurring Payments.', 'tutor-pro' ),
		'depend_plugins' => array(
			'woocommerce/woocommerce.php' => 'WooCommerce',
			'woocommerce-subscriptions/woocommerce-subscriptions.php' => 'WooCommerce Subscriptions',
		),
	);

	$basic_config = (array) TUTOR_WCS();
	$new_config   = array_merge( $new_config, $basic_config );

	$config[ plugin_basename( TUTOR_WCS_FILE ) ] = $new_config;
	return $config;
}

if ( ! function_exists( 'TUTOR_WCS' ) ) {
	/**
	 * Addon helper
	 *
	 * @return object
	 */
	//phpcs:ignore
	function TUTOR_WCS() {
		$info = array(
			'path'         => plugin_dir_path( TUTOR_WCS_FILE ),
			'url'          => plugin_dir_url( TUTOR_WCS_FILE ),
			'basename'     => plugin_basename( TUTOR_WCS_FILE ),
			'version'      => TUTOR_WCS_VERSION,
			'nonce_action' => 'tutor_nonce_action',
			'nonce'        => '_wpnonce',
		);

		return (object) $info;
	}
}

require 'classes/init.php';
new \TUTOR_WCS\Init();
