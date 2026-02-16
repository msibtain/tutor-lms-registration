<?php
/**
 * Utility helpers.
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription;

/**
 * Utils Class.
 *
 * @since 3.0.0
 */
class Utils {
	/**
	 * Get view path.
	 *
	 * @since 3.0.0
	 *
	 * @param string $path path.
	 *
	 * @return string
	 */
	public static function view_path( $path = null ) {
		$final_path = TUTOR_SUBSCRIPTION_DIR . 'views';
		if ( $path ) {
			$final_path .= '/' . $path;
		}
		return $final_path;
	}

	/**
	 * Get template path.
	 *
	 * @since 3.0.0
	 *
	 * @param string $path path.
	 *
	 * @return string
	 */
	public static function template_path( $path = null ) {
		$final_path = TUTOR_SUBSCRIPTION_DIR . 'templates';
		if ( $path ) {
			$final_path .= '/' . $path;
		}
		return $final_path;
	}

	/**
	 * Get asset URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url url of assets.
	 *
	 * @return string
	 */
	public static function asset_url( $url = null ) {
		$final_url = plugin_dir_url( TUTOR_SUBSCRIPTION_FILE ) . 'assets';
		if ( $url ) {
			$final_url .= '/' . $url;
		}
		return $final_url;
	}
}
