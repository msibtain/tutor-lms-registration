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
use Tutor\Traits\JsonResponse;
use TutorPro\OpenAI\Constants\Models;
use TutorPro\OpenAI\Constants\Sizes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller class for generating course with content using openai.
 *
 * @since 3.0.0
 */
class CourseGenerationController {
	/**
	 * Use the trait JsonResponse for sending response in application/json content type
	 *
	 * @var JsonResponse
	 */
	use JsonResponse;

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
	 * @return string|null
	 *
	 * @throws Throwable Catch if there any exceptions then throw it.
	 */
	private function generate_course_title() {
		$prompt = Input::post( 'prompt', '' );

		try {
			$client   = Helper::get_openai_client();
			$response = $client->chat()->create(
				Helper::create_openai_chat_input(
					Prompts::prepare_course_title_messages( $prompt )
				)
			);

			$response = Helper::check_openai_response( $response );

			if ( ! empty( $response->choices ) ) {
					return $response->choices[0]->message->content;
			}

			return null;
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
			$client   = Helper::get_openai_client();
			$response = $client->chat()->create(
				Helper::create_openai_chat_input(
					Prompts::prepare_course_description_messages( $title )
				)
			);

			$response = Helper::check_openai_response( $response );

			if ( ! empty( $response->choices ) ) {
					$content = $response->choices[0]->message->content;
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
			$client = Helper::get_openai_client();
			$prompt = "Design a modern and professional e-learning course banner image that visually conveys the theme of a course titled '{title}'. The image should be clean and minimalistic, using realistic visuals, colors, and icons related to the course topic. Incorporate subtle, relevant graphics or symbols that represent the course subject while keeping the layout simple and focused. Avoid clutter and unnecessary details. Use a visually appealing color scheme that aligns with the course theme, ensuring the design remains sleek and engaging. Do not include any text in the image, focusing solely on the graphics and design to convey the course's theme in a minimal, realistic manner.";
			$prompt = str_replace( '{title}', $title, $prompt );

			$response = $client->images()->create(
				array(
					'model'           => Models::DALL_E_3,
					'prompt'          => $prompt,
					'size'            => Sizes::LANDSCAPE,
					'n'               => 1,
					'response_format' => 'b64_json',
				)
			);

			$response = Helper::check_openai_response( $response );

			return $response->data[0]->b64_json;
		} catch ( Throwable $error ) {
			throw $error;
		}
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
			$client = Helper::get_openai_client();
			$input  = Helper::create_openai_chat_input(
				Prompts::prepare_course_topic_names_messages( $title ),
				array( 'response_format' => array( 'type' => 'json_object' ) )
			);

			$response = $client->chat()->create( $input );
			$response = Helper::check_openai_response( $response );
			$content  = $response->choices[0]->message->content;
			$content  = Helper::sanitize_json( $content );
			$content  = Helper::is_valid_json( $content ) ? json_decode( $content ) : array();
			$modules  = ! empty( $content->modules ) ? $content->modules : array();

			return $modules;
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
		tutor_utils()->check_nonce();

		$title      = Input::post( 'title' );
		$topic_name = Input::post( 'topic_name' );
		$index      = Input::post( 'index', 0, Input::TYPE_INT );

		try {
			$client   = Helper::get_openai_client();
			$input    = Helper::create_openai_chat_input(
				Prompts::prepare_course_topic_content_messages( $title, $topic_name )
			);
			$response = $client->chat()->create( $input );
			$response = Helper::check_openai_response( $response );
			$content  = $response->choices[0]->message->content;
			$content  = Helper::sanitize_json( $content );
			$content  = Helper::is_valid_json( $content ) ? json_decode( $content ) : array();

			$this->json_response(
				__( 'Content generated', 'tutor-pro' ),
				array(
					'topic_contents' => $content,
					'index'          => $index,
				)
			);
		} catch ( Exception $error ) {
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
		tutor_utils()->check_nonce();

		$type  = Input::post( 'type' );
		$title = Input::post( 'title' );
		try {
			$method    = 'generate_course_' . $type;
			$arguments = 'title' === $type ? array() : array( $title );

			if ( ! method_exists( $this, $method ) ) {
				$this->json_response( __( 'Invalid type provided', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
			}

			$content = call_user_func_array( array( $this, $method ), $arguments );

			$this->json_response(
				__( 'Content generated', 'tutor' ),
				$content
			);
		} catch ( Exception $error ) {
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
		tutor_utils()->check_nonce();

		$title      = Input::post( 'title' );
		$topic_name = Input::post( 'topic_name' );
		$quiz_title = Input::post( 'quiz_title' );

		if ( empty( $title ) || empty( $topic_name ) || empty( $quiz_title ) ) {
			$this->json_response( __( 'Missing required payloads.', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		try {
			$client = Helper::get_openai_client();
			$input  = Helper::create_openai_chat_input(
				Prompts::prepare_quiz_questions_messages( $title, $topic_name, $quiz_title )
			);

			$response = $client->chat()->create( $input );
			$response = Helper::check_openai_response( $response );
			$content  = $response->choices[0]->message->content;
			$content  = Helper::sanitize_json( $content );
			$content  = Helper::is_valid_json( $content ) ? json_decode( $content ) : array();

			$this->json_response( __( 'Quiz generated', 'tutor-pro' ), $content );
		} catch ( Exception $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}
}
