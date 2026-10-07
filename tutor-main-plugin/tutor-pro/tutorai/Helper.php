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
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

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
	 * Check if WordPress AI Connectors (WP 7.0+ or AI feature plugin) is supported.
	 *
	 * @since 4.0.1
	 *
	 * @return bool
	 */
	public static function is_wp_ai_supported(): bool {
		return function_exists( 'wp_ai_client_prompt' ) || class_exists( '\WordPress\AiClient\AiClient' ) || function_exists( 'wp_get_connectors' );
	}

	/**
	 * Check if any AI connector is configured in WordPress.
	 *
	 * Checks whether any registered AI provider has credentials configured via
	 * database option, PHP constant, or environment variable.
	 *
	 * @since 4.0.1
	 *
	 * @return bool
	 */
	public static function has_ai_connector(): bool {
		if ( function_exists( 'wp_get_connectors' ) ) {
			$connectors = wp_get_connectors();
			foreach ( $connectors as $connector_id => $connector_data ) {
				if ( 'ai_provider' !== ( $connector_data['type'] ?? '' ) ) {
					continue;
				}
				$auth = $connector_data['authentication'] ?? array();
				if ( 'api_key' === ( $auth['method'] ?? '' ) ) {
					$setting_name  = $auth['setting_name'] ?? '';
					$env_var_name  = $auth['env_var_name'] ?? '';
					$constant_name = $auth['constant_name'] ?? '';

					$source = function_exists( '_wp_connectors_get_api_key_source' )
						? _wp_connectors_get_api_key_source( $setting_name, $env_var_name, $constant_name )
						: ( function_exists( '\WordPress\AI\get_connector_api_key_source' )
							? \WordPress\AI\get_connector_api_key_source( $setting_name, $env_var_name, $constant_name )
							: 'none' );

					if ( 'none' !== $source ) {
						return true;
					}
				} elseif ( 'application_password' === ( $auth['method'] ?? '' ) ) {
					if ( function_exists( 'wp_connectors_get_application_password_credentials' ) ) {
						$creds = wp_connectors_get_application_password_credentials( $auth );
						if ( 'none' !== ( $creds['source'] ?? 'none' ) ) {
							return true;
						}
					}
				}
			}
			return false;
		}

		if ( function_exists( '\WordPress\AI\has_connector_authentication' ) && function_exists( '\WordPress\AI\get_ai_connectors' ) ) {
			$connectors = \WordPress\AI\get_ai_connectors();
			foreach ( array_keys( $connectors ) as $connector_id ) {
				if ( \WordPress\AI\has_connector_authentication( $connector_id ) ) {
					return true;
				}
			}
			return false;
		}

		return false;
	}

	/**
	 * Check if any configured connector supports image generation.
	 *
	 * @since 4.0.1
	 *
	 * @return bool
	 */
	public static function has_image_generation_support(): bool {
		if ( ! self::has_ai_connector() ) {
			return false;
		}

		if ( function_exists( '\WordPress\AI\has_image_generation_support' ) ) {
			return \WordPress\AI\has_image_generation_support( true );
		}
		return false;
	}

	/**
	 * Unified text generation using either WP AI Connectors or legacy OpenAI client.
	 *
	 * @since 4.0.1
	 *
	 * @param string|array $prompt_or_messages Either a prompt string or array of messages.
	 * @param array        $options Additional options (response_format, temperature, system_instruction, etc.).
	 *
	 * @return string
	 * @throws RuntimeException If generation fails or credentials missing.
	 */
	public static function generate_text( $prompt_or_messages, array $options = array() ) {
		// When WordPress AI Connectors is supported, strictly require configured AI Connectors.
		if ( self::is_wp_ai_supported() ) {
			if ( self::has_ai_connector() && function_exists( 'wp_ai_client_prompt' ) ) {
				$prompt_text        = '';
				$system_instruction = $options['system_instruction'] ?? '';

				if ( is_array( $prompt_or_messages ) ) {
					foreach ( $prompt_or_messages as $message ) {
						$role    = $message['role'] ?? 'user';
						$content = $message['content'] ?? '';
						if ( 'system' === $role ) {
							$system_instruction = empty( $system_instruction ) ? $content : $system_instruction . "\n" . $content;
						} else {
							$prompt_text = empty( $prompt_text ) ? $content : $prompt_text . "\n\n" . $content;
						}
					}
				} else {
					$prompt_text = (string) $prompt_or_messages;
				}

				// Add strict instruction for JSON object when requested.
				$is_json_request = ! empty( $options['response_format']['type'] ) && 'json_object' === $options['response_format']['type'];
				if ( $is_json_request ) {
					$json_instruction   = 'You must respond with valid, raw JSON object only. Do not wrap output in markdown code blocks like ```json or include conversational text.';
					$system_instruction = empty( $system_instruction ) ? $json_instruction : $system_instruction . "\n" . $json_instruction;
				}

				// Large course-generation prompts can take a while; use a 90s timeout.
				$request_options = new RequestOptions();
				$request_options->setTimeout( 90.0 );
				$builder = wp_ai_client_prompt( $prompt_text );
				$builder->usingRequestOptions( $request_options );
				if ( $is_json_request && is_callable( array( $builder, 'as_json_response' ) ) ) {
					$builder->as_json_response();
				}
				if ( ! empty( $system_instruction ) ) {
					if ( is_callable( array( $builder, 'using_system_instruction' ) ) ) {
						$builder->using_system_instruction( $system_instruction );
					} elseif ( is_callable( array( $builder, 'usingSystemInstruction' ) ) ) {
						$builder->usingSystemInstruction( $system_instruction );
					}
				}

				$result = $builder->generate_text();
				if ( is_wp_error( $result ) ) {
					$error_msg = $result->get_error_message();
					if ( false !== stripos( $error_msg, 'No models found' ) ) {
						throw new RuntimeException( __( 'No AI connector is configured in WordPress. Please configure an AI connector in WordPress Settings > Connectors to use AI features.', 'tutor-pro' ) );
					}
					throw new RuntimeException( $error_msg );
				}

				return (string) $result;
			}

			throw new RuntimeException( __( 'No AI connector is configured in WordPress. Please configure an AI connector in WordPress Settings > Connectors to use AI features.', 'tutor-pro' ) );
		}

		// Fallback to legacy OpenAI client (only for WP < 7.0).
		$client   = self::get_openai_client();
		$messages = is_array( $prompt_or_messages ) ? $prompt_or_messages : array(
			array(
				'role'    => 'user',
				'content' => (string) $prompt_or_messages,
			),
		);

		if ( ! empty( $options['system_instruction'] ) ) {
			array_unshift(
				$messages,
				array(
					'role'    => 'system',
					'content' => $options['system_instruction'],
				)
			);
		}

		if ( isset( $options['response_format'] ) && is_array( $options['response_format'] ) && 'json_object' === ( $options['response_format']['type'] ?? '' ) ) {
			$has_json = false;
			foreach ( $messages as $msg ) {
				if ( false !== stripos( $msg['content'] ?? '', 'json' ) ) {
					$has_json = true;
					break;
				}
			}
			if ( ! $has_json ) {
				$messages[] = array(
					'role'    => 'system',
					'content' => 'Please respond with a valid JSON object.',
				);
			}
		}

		$input    = self::create_openai_chat_input( $messages, $options );
		$response = $client->chat()->create( $input );
		$response = self::check_openai_response( $response );

		return $response->choices[0]->message->content ?? '';
	}

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
	 * @param string|null $content The content that will be converted to html.
	 *
	 * @return string
	 */
	public static function markdown_to_html( string $content = '' ) {
		if ( empty( $content ) ) {
			return '';
		}

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
	public static function is_valid_json( string $content = '' ) {
		if ( empty( $content ) ) {
			return false;
		}

		if ( function_exists( 'json_validate' ) ) {
			return json_validate( $content );
		}

		json_decode( $content );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Sanitize the json content by removing markdown code blocks and surrounding text.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content that will be sanitized.
	 *
	 * @return string
	 */
	public static function sanitize_json( string $content = '' ) {
		if ( empty( $content ) ) {
			return '';
		}

		$content = trim( $content );

		// Strip markdown code fences if present.
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)\s*```/i', $content, $matches ) ) {
			$content = trim( $matches[1] );
		} else {
			$content = ltrim( $content, '```json' );
			$content = rtrim( $content, '```' );
			$content = trim( $content );
		}

		if ( self::is_valid_json( $content ) ) {
			return $content;
		}

		// Fallback: If content has surrounding text, extract the outermost JSON object or array.
		if ( preg_match( '/(\[\s*[\s\S]*\s*\]|\{\s*[\s\S]*\s*\})/i', $content, $matches ) ) {
			$extracted = trim( $matches[1] );
			if ( self::is_valid_json( $extracted ) ) {
				return $extracted;
			}
			$content = $extracted;
		}

		// Repair trailing commas (e.g., [1, 2,] or {"a": 1,})
		$repaired = preg_replace( '/,\s*([\]\}])/', '$1', $content );
		if ( self::is_valid_json( $repaired ) ) {
			return $repaired;
		}

		// Repair single-quoted JSON strings/keys if applicable
		$single_quote_repaired = preg_replace( "/(?<=^|[\{\[\,]\s*)'([^'\\]*(?:\\.[^'\\]*)*)'\s*:/", '"$1":', $repaired );
		$single_quote_repaired = preg_replace( "/:\s*'([^'\\]*(?:\\.[^'\\]*)*)'(?=\s*[,\]\}])/", ':"$1"', $single_quote_repaired );
		if ( self::is_valid_json( $single_quote_repaired ) ) {
			return $single_quote_repaired;
		}

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

		return $response['data'] ?? null;
	}
}
