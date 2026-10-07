<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\TutorAI
 * @author  Themeum <support@themeum.com>
 * @link    https://themeum.com
 * @since   3.0.0
 */

namespace TutorPro\TutorAI;

use Exception;
use Throwable;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use TutorPro\OpenAI\Constants\Models;
use TutorPro\OpenAI\Constants\Sizes;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller class for generating course with content using openai.
 *
 * @since 3.0.0
 */
class CourseGenerationController extends TutorAIBaseController {

	/**
	 * Constructor method for the course generation controller
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		/**
		 * Handle AJAX request for generating AI images
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_generate_course_content', array( $this, 'course_content_generation' ) );

		/**
		 * Handle AJAX request for generating course content for a topic
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_generate_course_topic_content', array( $this, 'generate_course_topic_content' ) );

		/**
		 * Handle AJAX request for generating quiz question by using openai.
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_generate_quiz_questions', array( $this, 'generate_quiz_questions' ) );
	}

	/**
	 * Generate the course title from the user prompt.
	 *
	 * @since 3.0.0
	 *
	 * @param string $prompt Prompt string.
	 *
	 * @return string|null
	 *
	 * @throws Throwable Catch if there any exceptions then throw it.
	 */
	private function generate_course_title( string $prompt = '' ) {
		if ( empty( $prompt ) ) {
			$prompt = Input::post( 'prompt', '' );
		}
		if ( empty( $prompt ) ) {
			$prompt = Input::post( 'title', '' );
		}

		try {
			$messages = Prompts::prepare_course_title_messages( $prompt );
			$content  = Helper::generate_text( $messages );

			if ( ! empty( $content ) ) {
				$content = trim( $content, " \t\n\r\0\x0B\"'#" );
				// If response has multiple lines, take the first non-empty line as title.
				$lines   = array_filter( array_map( 'trim', explode( "\n", $content ) ) );
				$content = ! empty( $lines ) ? reset( $lines ) : $content;
				$content = trim( $content, " \t\n\r\0\x0B\"'#" );
			}

			return ! empty( $content ) ? $content : null;
		} catch ( Throwable $error ) {
			throw $error;
		}
	}

	/**
	 * Generate the course description from the user prompt.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $title Generated course title.
	 *
	 * @return string|null
	 *
	 * @throws Throwable Catch if there any exceptions then throw it.
	 */
	private function generate_course_description( string $title ) {
		try {
			$messages = Prompts::prepare_course_description_messages( $title );
			$content  = Helper::generate_text( $messages );

			if ( ! empty( $content ) ) {
				return Helper::markdown_to_html( $content );
			}

			return null;
		} catch ( Throwable $error ) {
			throw $error;
		}
	}

	/**
	 * Generate course image using the course title.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $title The course title.
	 *
	 * @return string
	 *
	 * @throws Throwable If any exception happens, then throw it.
	 */
	private function generate_course_image( string $title ) {
		try {
			$prompt = "Design a modern and professional e-learning course banner image that visually conveys the theme of a course titled '{title}'. The image should be clean and minimalistic, using realistic visuals, colors, and icons related to the course topic. Incorporate subtle, relevant graphics or symbols that represent the course subject while keeping the layout simple and focused. Avoid clutter and unnecessary details. Use a visually appealing color scheme that aligns with the course theme, ensuring the design remains sleek and engaging. Do not include any text in the image, focusing solely on the graphics and design to convey the course's theme in a minimal, realistic manner.";
			$prompt = str_replace( '{title}', $title, $prompt );

			if ( Helper::is_wp_ai_supported() ) {
				if ( Helper::has_image_generation_support() && function_exists( 'wp_ai_client_prompt' ) ) {
					try {
						// Image generation can take 45–90s; extend limits accordingly.
						@set_time_limit( 180 );
						$request_options = new RequestOptions();
						$request_options->setTimeout( 120.0 );
						$builder = wp_ai_client_prompt( $prompt );
						$image   = $builder->usingRequestOptions( $request_options )->generate_image();
						if ( is_wp_error( $image ) ) {
							return null;
						}
						if ( ! empty( $image ) ) {
							if ( is_object( $image ) ) {
								if ( method_exists( $image, 'getDataUri' ) && $image->getDataUri() ) {
									return $image->getDataUri();
								}
								if ( method_exists( $image, 'getBase64Data' ) && $image->getBase64Data() ) {
									$mime = method_exists( $image, 'getMimeType' ) ? $image->getMimeType() : 'image/png';
									return 'data:' . $mime . ';base64,' . $image->getBase64Data();
								}
								if ( method_exists( $image, 'getUrl' ) && $image->getUrl() ) {
									$url        = $image->getUrl();
									$remote_img = wp_remote_get( $url, array( 'timeout' => 60 ) );
									if ( ! is_wp_error( $remote_img ) ) {
										$body = wp_remote_retrieve_body( $remote_img );
										$mime = wp_remote_retrieve_header( $remote_img, 'content-type' ) ?: 'image/png';
										if ( ! empty( $body ) ) {
											return 'data:' . $mime . ';base64,' . base64_encode( $body );
										}
									}
									return $url;
								}
							}
							if ( is_string( $image ) ) {
								if ( 0 !== strpos( $image, 'data:' ) && 0 !== strpos( $image, 'http' ) ) {
									return 'data:image/png;base64,' . $image;
								}
								return $image;
							}
							return null;
						}
					} catch ( Throwable $error ) {
						return null;
					}
				}

				// If no image model / connector is supported, skip image generation gracefully.
				return null;
			}

			// Fallback to legacy OpenAI key (only for WP < 7.0).
			$chatgpt_api_key = tutor_utils()->get_option( 'chatgpt_api_key' );
			if ( ! empty( $chatgpt_api_key ) ) {
				try {
					$client   = Helper::get_openai_client();
					$response = $client->images()->create(
						array(
							'model'  => Models::GPT_IMAGE_2_5_FLARE,
							'prompt' => $prompt,
							'size'   => Sizes::LANDSCAPE,
							'n'      => 1,
						)
					);

					$response = Helper::check_openai_response( $response );

					$b64 = '';
					$url = '';

					if ( is_object( $response ) && ! empty( $response->data ) && is_array( $response->data ) ) {
						$first_item = $response->data[0] ?? null;
						if ( is_object( $first_item ) ) {
							$b64 = ! empty( $first_item->b64_json ) ? (string) $first_item->b64_json : '';
							$url = ! empty( $first_item->url ) ? (string) $first_item->url : '';
						} elseif ( is_array( $first_item ) ) {
							$b64 = ! empty( $first_item['b64_json'] ) ? (string) $first_item['b64_json'] : '';
							$url = ! empty( $first_item['url'] ) ? (string) $first_item['url'] : '';
						}
					}

					if ( 'data:image/png;base64,' === $b64 ) {
						$b64 = '';
					}

					if ( empty( $b64 ) && ! empty( $url ) && preg_match( '/^https?:\/\//i', $url ) ) {
						$remote_img = wp_remote_get( $url, array( 'timeout' => 60 ) );
						if ( ! is_wp_error( $remote_img ) ) {
							$body = wp_remote_retrieve_body( $remote_img );
							$mime = wp_remote_retrieve_header( $remote_img, 'content-type' ) ?: 'image/png';
							if ( ! empty( $body ) ) {
								$b64 = 'data:' . $mime . ';base64,' . base64_encode( $body );
							}
						}
						if ( empty( $b64 ) ) {
							$b64 = $url;
						}
					}

					if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
						$b64 = 'data:image/png;base64,' . $b64;
					}

					return ! empty( $b64 ) && 'data:image/png;base64,' !== $b64 ? $b64 : null;
				} catch ( Throwable $error ) {
					return null;
				}
			}

			// If no OpenAI key found, skip image generation gracefully.
			return null;
		} catch ( Throwable $error ) {
			return null;
		}
	}

	/**
	 * Extract topic names from decoded response.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $data Decoded JSON data.
	 *
	 * @return array
	 */
	private function extract_topic_names( $data ) {
		if ( empty( $data ) ) {
			return array();
		}

		if ( is_object( $data ) ) {
			$data = json_decode( wp_json_encode( $data ), true );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$is_list_of_topics = function ( array $items ) {
			if ( empty( $items ) ) {
				return false;
			}
			$found = 0;
			foreach ( $items as $item ) {
				if ( is_string( $item ) || ( is_array( $item ) && ( isset( $item['title'] ) || isset( $item['name'] ) ) ) ) {
					++$found;
				}
			}
			return $found > 0 && $found >= ( count( $items ) / 2 );
		};

		if ( $is_list_of_topics( $data ) ) {
			$normalized = array();
			foreach ( $data as $item ) {
				if ( is_string( $item ) ) {
					$normalized[] = array( 'title' => $item );
				} elseif ( is_array( $item ) ) {
					if ( ! isset( $item['title'] ) && isset( $item['name'] ) ) {
						$item['title'] = $item['name'];
					}
					$normalized[] = $item;
				}
			}
			return $normalized;
		}

		$priority_keys = array( 'modules', 'topics', 'topic_names', 'data', 'items' );
		foreach ( $priority_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$extracted = $this->extract_topic_names( $data[ $key ] );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		foreach ( $data as $val ) {
			if ( is_array( $val ) ) {
				$extracted = $this->extract_topic_names( $val );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		return array();
	}

	/**
	 * Extract topic contents from decoded response.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $data Decoded JSON data.
	 *
	 * @return array
	 */
	private function extract_topic_contents( $data ) {
		if ( empty( $data ) ) {
			return array();
		}

		if ( is_object( $data ) ) {
			$data = json_decode( wp_json_encode( $data ), true );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$is_list_of_contents = function ( array $items ) {
			if ( empty( $items ) ) {
				return false;
			}
			$found = 0;
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['title'] ) ) {
					++$found;
				}
			}
			return $found > 0 && $found >= ( count( $items ) / 2 );
		};

		if ( $is_list_of_contents( $data ) ) {
			return array_values( $data );
		}

		$priority_keys = array( 'contents', 'topic_contents', 'items', 'data', 'lessons_and_quizzes' );
		foreach ( $priority_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$extracted = $this->extract_topic_contents( $data[ $key ] );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		$combined = array();
		if ( isset( $data['lessons'] ) && is_array( $data['lessons'] ) ) {
			foreach ( $data['lessons'] as $lesson ) {
				if ( is_array( $lesson ) ) {
					$lesson['type'] = $lesson['type'] ?? 'lesson';
					$combined[]     = $lesson;
				}
			}
		}
		if ( isset( $data['quizzes'] ) && is_array( $data['quizzes'] ) ) {
			foreach ( $data['quizzes'] as $quiz ) {
				if ( is_array( $quiz ) ) {
					$quiz['type'] = $quiz['type'] ?? 'quiz';
					$combined[]   = $quiz;
				}
			}
		}
		if ( ! empty( $combined ) ) {
			return $combined;
		}

		foreach ( $data as $val ) {
			if ( is_array( $val ) ) {
				$extracted = $this->extract_topic_contents( $val );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		return array();
	}

	/**
	 * Extract quiz questions from decoded response.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $data Decoded JSON data.
	 *
	 * @return array
	 */
	private function extract_quiz_questions( $data ) {
		if ( empty( $data ) ) {
			return array();
		}

		if ( is_object( $data ) ) {
			$data = json_decode( wp_json_encode( $data ), true );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$is_list_of_questions = function ( array $items ) {
			if ( empty( $items ) ) {
				return false;
			}
			$found = 0;
			foreach ( $items as $item ) {
				if ( is_array( $item ) && ( isset( $item['title'] ) || isset( $item['question'] ) ) ) {
					++$found;
				}
			}
			return $found > 0 && $found >= ( count( $items ) / 2 );
		};

		if ( $is_list_of_questions( $data ) ) {
			return array_values( $data );
		}

		$priority_keys = array( 'questions', 'quiz_questions', 'quiz', 'data', 'items' );
		foreach ( $priority_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$extracted = $this->extract_quiz_questions( $data[ $key ] );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		foreach ( $data as $val ) {
			if ( is_array( $val ) ) {
				$extracted = $this->extract_quiz_questions( $val );
				if ( ! empty( $extracted ) ) {
					return $extracted;
				}
			}
		}

		return array();
	}

	/**
	 * Generate the course topic names from the title
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The course title.
	 *
	 * @return array
	 *
	 * @throws Throwable If any exception happens then throws it.
	 */
	private function generate_course_topic_names( string $title ) {
		try {
			$messages = Prompts::prepare_course_topic_names_messages( $title );
			$content  = Helper::generate_text(
				$messages,
				array( 'response_format' => array( 'type' => 'json_object' ) )
			);
			$content  = Helper::sanitize_json( $content );
			$decoded  = Helper::is_valid_json( $content ) ? json_decode( $content, true ) : array();

			return $this->extract_topic_names( $decoded );
		} catch ( Throwable $error ) {
			throw $error;
		}
	}

	/**
	 * API endpoint for generate course content for a topic by the course title and the topic name.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function generate_course_topic_content() {
		$this->validate_ajax_request();

		$title      = Input::post( 'title' );
		$topic_name = Input::post( 'topic_name' );
		$index      = Input::post( 'index', 0, Input::TYPE_INT );

		try {
			$messages = Prompts::prepare_course_topic_content_messages( $title, $topic_name );
			$content  = Helper::generate_text(
				$messages,
				array( 'response_format' => array( 'type' => 'json_object' ) )
			);
			$content  = Helper::sanitize_json( $content );
			$decoded  = Helper::is_valid_json( $content ) ? json_decode( $content, true ) : array();
			$contents = $this->extract_topic_contents( $decoded );

			$this->json_response(
				__( 'Content generated', 'tutor-pro' ),
				array(
					'topic_contents' => $contents,
					'index'          => $index,
				)
			);
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}

	/**
	 * API endpoint for generating course contents using the prompt.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function course_content_generation() {
		$this->validate_ajax_request();

		$type   = Input::post( 'type' );
		$prompt = Input::post( 'prompt' );
		$title  = Input::post( 'title' );

		// Fallbacks: if title was passed for prompt or prompt was passed for title.
		if ( empty( $prompt ) && ! empty( $title ) ) {
			$prompt = $title;
		}
		if ( empty( $title ) && ! empty( $prompt ) ) {
			$title = $prompt;
		}

		try {
			$method    = 'generate_course_' . $type;
			$arguments = 'title' === $type ? array( $prompt ) : array( $title );

			if ( empty( $type ) || ! method_exists( $this, $method ) ) {
				$this->json_response( __( 'Invalid type provided', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
				return;
			}

			$content = call_user_func_array( array( $this, $method ), $arguments );

			$this->json_response(
				__( 'Content generated', 'tutor-pro' ),
				$content
			);
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}

	/**
	 * Generate quiz questions by the help of course title, topic, and quiz title.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function generate_quiz_questions() {
		$this->validate_ajax_request();

		$title      = Input::post( 'title' );
		$topic_name = Input::post( 'topic_name' );
		$quiz_title = Input::post( 'quiz_title' );

		if ( empty( $title ) || empty( $topic_name ) || empty( $quiz_title ) ) {
			$this->json_response( __( 'Missing required payloads.', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
			return;
		}

		try {
			$messages  = Prompts::prepare_quiz_questions_messages( $title, $topic_name, $quiz_title );
			$content   = Helper::generate_text(
				$messages,
				array( 'response_format' => array( 'type' => 'json_object' ) )
			);
			$content   = Helper::sanitize_json( $content );
			$decoded   = Helper::is_valid_json( $content ) ? json_decode( $content, true ) : array();
			$questions = $this->extract_quiz_questions( $decoded );

			$this->json_response( __( 'Quiz generated', 'tutor-pro' ), $questions );
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}
}
