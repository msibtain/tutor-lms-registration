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
 * The base uri generation class.
 *
 * @since 3.0.0
 */
final class BaseUri {
	/**
	 * The uri string.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private $uri = null;

	/**
	 * The BaseUri constructor function.
	 *
	 * @since 3.0.0
	 *
	 * @param string $uri The uri string.
	 */
	private function __construct( string $uri ) {
		$this->uri = trim( $uri );
	}

	/**
	 * Create the base uri form the provided uri string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $uri The uri string.
	 *
	 * @return string
	 */
	public static function from( string $uri ) {
		$uri = trim( trim( $uri ), '/' );

		return ( new self( $uri ) )->to_string();
	}

	/**
	 * Prepare the base uri for the request.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	private function to_string() {
		foreach ( array( 'http://', 'https://' ) as $protocol ) {
			if ( str_starts_with( $this->uri, $protocol ) ) {
				return "{$this->uri}/";
			}
		}

		return "https://{$this->uri}/";
	}
}
