<?php
/**
 * Addon Register Handler
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

/**
 * AddonRegister Class.
 *
 * @since 3.0.0
 */
class AddonRegister {
	/**
	 * Register hooks and dependencies
	 */
	public function __construct() {
		add_filter( 'tutor_addons_lists_config', array( $this, 'register_addon' ) );
	}

	/**
	 * Register course bundle addon
	 *
	 * @since 2.2.0
	 *
	 * @param array $addons array of addons.
	 *
	 * @return array
	 */
	public static function register_addon( $addons ) {

		$required_settings = array(
			'has'     => tutor_utils()->is_monetize_by_tutor() ? false : true,
			'title'   => __( 'Requires Native Payment to be enabled.', 'tutor-pro' ),
			'message' => __( 'Choose “Native Payment” from the eCommerce engine option in the settings', 'tutor-pro' ),
		);

		$new_addon = array(
			'name'              => __( 'Subscriptions', 'tutor-pro' ),
			'description'       => __( 'Enable the native subscriptions feature for recurring revenue.', 'tutor-pro' ),
			'path'              => TUTOR_SUBSCRIPTION_DIR,
			'basename'          => plugin_basename( TUTOR_SUBSCRIPTION_FILE ),
			'url'               => plugin_dir_url( TUTOR_SUBSCRIPTION_FILE ),
			'required_settings' => $required_settings['has'],
			'required_title'    => $required_settings['title'],
			'required_message'  => $required_settings['message'],
		);

		$addons[ plugin_basename( $new_addon['basename'] ) ] = $new_addon;

		return $addons;
	}
}
