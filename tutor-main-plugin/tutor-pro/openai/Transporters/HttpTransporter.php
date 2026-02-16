<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Transporters;

use TutorPro\OpenAI\Contracts\TransporterContract;
use TutorPro\OpenAI\Http\Response;
use TutorPro\OpenAI\Support\Header;
use TutorPro\OpenAI\Support\Payload;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The HTTP request transporter. Use the WP transporter by default.
 *
 * @since 3.0.0
 */
class HttpTransporter implements TransporterContract {
	/**
	 * The base request uri.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private $base_uri = null;

	/**
	 * The request headers
	 *
	 * @since 3.0.0
	 *
	 * @var Header
	 */
	private $headers = null;

	/**
	 * The constructor method for the HttpTransporter
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_uri The base uri.
	 * @param Header $headers The request headers.
	 */
	public function __construct( string $base_uri, Header $headers ) {
		$this->base_uri = $base_uri;
		$this->headers  = $headers;
	}

	/**
	 * Send the request to the requested endpoint
	 *
	 * @since 3.0.0
	 *
	 * @param Payload $payload The route instance.
	 *
	 * @return Response
	 */
	public function request( Payload $payload ) {
		return Response::create(
			$payload->build( $this->base_uri, $this->headers )
		);
	}
}
