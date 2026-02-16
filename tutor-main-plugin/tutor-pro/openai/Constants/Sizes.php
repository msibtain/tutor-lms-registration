<?php
/**
 * Constants for keeping the image sizes
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI allowed image sizes for the DALL-E-2 & 3 models.
 *
 * @since 3.0.0
 */
final class Sizes {
	/**
	 * The portrait mode size of the images. This size is only allowed by dall-e-3.
	 *
	 * @since   3.0.0
	 *
	 * @var string
	 */
	const PORTRAIT = '1024x1792';

	/**
	 * The landscape mode size of the images. The size is only allowed by dall-e-3.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const LANDSCAPE = '1792x1024';

	/**
	 * The regular (default) size for our system. This size is allowed by the both dall-e-2 & 3.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const REGULAR = '1024x1024';

	/**
	 * The medium size of the images. This size is only allowed by dall-e-2.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const MEDIUM = '512x512';

	/**
	 * The small size of the images. This size is only allowed by dall-e-2.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const SMALL = '256x256';
}
