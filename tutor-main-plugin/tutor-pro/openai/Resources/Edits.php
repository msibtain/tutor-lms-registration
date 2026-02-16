<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Resources;

use TutorPro\OpenAI\Concerns\Transportable;
use TutorPro\OpenAI\Contracts\ClientContract;
use TutorPro\OpenAI\Support\Payload;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The chat resource for making chat completion requests to openai.
 *
 * @since 3.0.0
 */
final class Edits implements ClientContract {
	use Transportable;

	/**
	 * Create the resource for making http request.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options The options to send to openai endpoint.
	 *
	 * @return array
	 */
	public function create( array $options ) {
		$payload = Payload::multipart( 'images/edits', $options );
		return $this->transporter->request( $payload )->as_base64_image();
	}
}
