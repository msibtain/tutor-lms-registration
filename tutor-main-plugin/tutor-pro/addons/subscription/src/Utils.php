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

use TUTOR_PRO\AddonBaseHelper;

/**
 * Utils Class.
 *
 * @since 3.0.0
 */
class Utils extends AddonBaseHelper {
	/**
	 * Addon directory
	 *
	 * @var string
	 */
	protected static $addon_dir = TUTOR_SUBSCRIPTION_DIR;

	/**
	 * Addon file
	 *
	 * @var string
	 */
	protected static $addon_file = TUTOR_SUBSCRIPTION_FILE;

	/**
	 * Check if the current page is pricing page.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function is_pricing_page() {
		global $post;
		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, Shortcode::MEMBERSHIP_PRICING );
	}
}
