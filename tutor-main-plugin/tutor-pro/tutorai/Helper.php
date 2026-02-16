<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\TutorAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\TutorAI;

use Parsedown;
use RuntimeException;
use TutorPro\OpenAI\OpenAI;
use TutorPro\OpenAI\Client;
use Tutor\Traits\JsonResponse;
use TutorPro\OpenAI\Constants\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for openai related functionalities.
 *
 * @since 3.0.0
 */
final class Helper {
	use JsonResponse;

	/**
	 * Tutor OpenAI Client instance
	 *
	 * @since 3.0.0
	 *
	 * @var Client | null
	 */
	private static $client = null;

	/**
	 * Get the instance of the OpenAI\Client
	 *
	 * @since 3.0.0
	 *
	 * @return Client
	 *
	 * @throws RuntimeException If openai api key is not found.
	 */
	public static function get_openai_client() {
		if ( is_null( self::$client ) ) {
			$api_key = tutor_utils()->get_option( 'chatgpt_api_key' );

			if ( empty( $api_key ) ) {
				throw new RuntimeException( 'Missing openai api key, please add the api key into the settings.' );
			}

			self::$client = OpenAI::client( $api_key );
		}

		return self::$client;
	}

	/**
	 * Convert markdown text to html
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content that will be converted to html.
	 *
	 * @return string
	 */
	public static function markdown_to_html( string $content ) {
		$markdown = new Parsedown();
		$markdown->setSafeMode( true );

		return $markdown->text( $content );
	}

	/**
	 * Create the openai chat input options.
	 *
	 * @since 3.0.0
	 *
	 * @param array $messages The chat messages.
	 * @param array $options Optional options for overwriting the model, temperature etc.
	 *
	 * @return array
	 */
	public static function create_openai_chat_input( array $messages, array $options = array() ) {
		$default_options = array(
			'model'       => Models::GPT_4O,
			'temperature' => 0.7,
		);

		$options             = array_merge( $default_options, $options );
		$options['messages'] = $messages;

		return $options;
	}

	/**
	 * Check if a content is a valid JSON string or not.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The string content to check.
	 *
	 * @return boolean
	 */
	public static function is_valid_json( string $content ) {
		json_decode( $content );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Sanitize the json content by removing the markdown code block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content that will be sanitized.
	 *
	 * @return string
	 */
	public static function sanitize_json( string $content ) {
		$content = ltrim( $content, '```json' );
		$content = rtrim( $content, '```' );

		return $content;
	}

	/**
	 * Check if the openai response has any error or not.
	 * If there any error then send the error response, otherwise continue.
	 *
	 * @since   3.0.0
	 *
	 * @param array $response The openai response.
	 *
	 * @return mixed
	 */
	public static function check_openai_response( array $response ) {
		$status_code = $response['status_code'] ?? 200;

		if ( $status_code >= 400 ) {
			$error_message = $response['error_message'] ?? '';
			wp_send_json(
				array(
					'status_code' => $status_code,
					'message'     => $error_message,
					'data'        => null,
				),
				$status_code
			);
		}

		return $response['data'];
	}
}
