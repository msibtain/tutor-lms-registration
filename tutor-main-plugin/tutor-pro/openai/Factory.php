<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI;

use TutorPro\OpenAI\Client;
use TutorPro\OpenAI\Constants\ContentTypes;
use TutorPro\OpenAI\Support\BaseUri;
use TutorPro\OpenAI\Support\Header;
use TutorPro\OpenAI\Transporters\HttpTransporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The factory class for making a openai client.
 *
 * @since 3.0.0
 */
final class Factory {
	/**
	 * The openai API key.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private $api_key = null;

	/**
	 * The openai organization for the request.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private $organization = null;

	/**
	 * The request headers
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, string>
	 */
	private array $headers = array();

	/**
	 * The openai request base uri.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private $base_uri = null;

	/**
	 * Set the API key for the openai requests.
	 *
	 * @since 3.0.0
	 *
	 * @param string $api_key The openai api key.
	 *
	 * @return self
	 */
	public function with_api_key( string $api_key ) {
		$this->api_key = trim( $api_key );

		return $this;
	}

	/**
	 * Undocumented function
	 *
	 * @since 3.0.0
	 *
	 * @param string|null $organization The request organization.
	 *
	 * @return self
	 */
	public function with_organization( $organization ) {
		$this->organization = $organization;

		return $this;
	}

	/**
	 * Set the base uri of the openai request.
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_uri The base uri.
	 *
	 * @return self
	 */
	public function with_base_uri( string $base_uri ) {
		$this->base_uri = $base_uri;

		return $this;
	}

	/**
	 * Set HTTP header.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The header name.
	 * @param string $value The header value.
	 *
	 * @return self
	 */
	public function with_http_header( string $name, string $value ) {
		$this->headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Make the openai client instance
	 *
	 * @since 3.0.0
	 *
	 * @return Client
	 */
	public function make() {
		$base_uri = BaseUri::from( $this->base_uri ?? 'api.openai.com/v1' );
		$headers  = Header::create();

		$headers->with_content_type( ContentTypes::JSON );

		if ( ! is_null( $this->api_key ) ) {
			$headers->with_authorization( $this->api_key );
		}

		if ( ! is_null( $this->organization ) ) {
			$headers->with_organization( $this->organization );
		}

		if ( ! empty( $this->headers ) ) {
			foreach ( $this->headers as $name => $value ) {
				$headers->with_custom_header( $name, $value );
			}
		}

		return new Client(
			new HttpTransporter( $base_uri, $headers )
		);
	}
}
