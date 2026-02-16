<?php
/**
 * Helper class for handling magic ai functionalities
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
 * Class for keeping the content types.
 *
 * @since 3.0.0
 */
final class ContentTypes {
	/**
	 * The application/json content type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const JSON = 'application/json';

	/**
	 * The multipart/form-data content type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const MULTIPART = 'multipart/form-data';

	/**
	 * The text/plain content type
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const PLAIN_TEXT = 'text/plain';
}
