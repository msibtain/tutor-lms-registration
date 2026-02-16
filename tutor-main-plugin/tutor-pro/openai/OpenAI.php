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

use TutorPro\OpenAI\Factory;
use TutorPro\OpenAI\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The root class for making openai client
 */
final class OpenAI {
	/**
	 * Create the openai client for making request.
	 *
	 * @since 3.0.0
	 *
	 * @param string $api_key The api key for the openai.
	 * @param string $organization The organization value.
	 *
	 * @return Client
	 */
	public static function client( string $api_key, $organization = null ) {
		return self::factory()
			->with_api_key( $api_key )
			->with_organization( $organization )
			->with_base_uri( 'api.openai.com/v1' )
			->with_http_header( 'OpenAI-Beta', 'assistants=v2' )
			->make();
	}

	/**
	 * The application factory class for instantiating the client.
	 *
	 * @since 3.0.0
	 *
	 * @return Factory
	 */
	private static function factory() {
		return new Factory();
	}
}
