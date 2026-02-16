<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Contracts;

use TutorPro\OpenAI\Support\Payload;
use TutorPro\OpenAI\Http\Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The transporter interface
 *
 * @since 3.0.0
 */
interface TransporterContract {
	/**
	 * Send the request to the requested endpoint
	 *
	 * @since 3.0.0
	 *
	 * @param Payload $route A route instance.
	 *
	 * @return Response
	 */
	public function request( Payload $route );
}
