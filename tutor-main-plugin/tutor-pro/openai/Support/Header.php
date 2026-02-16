<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Make the openai request header class.
 *
 * @since   3.0.0
 */
final class Header {

	/**
	 * The headers
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, string>
	 */
	private $headers = array();

	/**
	 * The constructor method for the request headers.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, string> $headers The request headers.
	 */
	public function __construct( array $headers ) {
		$this->headers = $headers;
	}

	/**
	 * Create the instance of the request Header.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, string> $headers The default headers.
	 *
	 * @return Header
	 */
	public static function create( array $headers = array() ) {
		return new self( $headers );
	}

	/**
	 * With authorization request header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $api_key The openai api key.
	 *
	 * @return Header
	 */
	public function with_authorization( string $api_key ) {
		$this->headers['Authorization'] = "Bearer {$api_key}";

		return $this;
	}

	/**
	 * With organization request header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $organization The openai organization.
	 *
	 * @return Header
	 */
	public function with_organization( string $organization ) {
		$this->headers['OpenAI-Organization'] = $organization;

		return $this;
	}

	/**
	 * With content type header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content_type The content type value.
	 * @param string $prefix The content type prefix if any.
	 *
	 * @return Header
	 */
	public function with_content_type( string $content_type, string $prefix = '' ) {
		$this->headers['Content-Type'] = $content_type . $prefix;

		return $this;
	}

	/**
	 * With custom header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The header name.
	 * @param string $value The header value.
	 *
	 * @return Header
	 */
	public function with_custom_header( string $name, string $value ) {
		$this->headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Get the headers array.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, string>
	 */
	public function to_array() {
		return $this->headers;
	}
}
