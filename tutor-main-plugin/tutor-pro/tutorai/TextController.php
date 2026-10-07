<?php
/**
 * Handle AI Generations
 *
 * @package TutorPro\TutorAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\TutorAI;

use Exception;
use InvalidArgumentException;
use Throwable;
use Tutor\Helpers\HttpHelper;
use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Text controller class.
 * This class is responsible for generating text content using openai.
 *
 * @since 3.0.0
 */
class TextController extends TutorAIBaseController {

	/**
	 * Constructor method for generating AI Content.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		/**
		 * Handle AJAX request for generating AI content
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_generate_text_content', array( $this, 'generate_text_content' ) );

		/**
		 * Handle AJAX request for modifying previously generated AI content
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_modify_text_content', array( $this, 'modify_text_content' ) );
	}

	/**
	 * Generating the text content from a user prompt, along with the tone, format, language, etc.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function generate_text_content() {
		$this->validate_ajax_request();

		$input = array(
			'prompt'     => Input::post( 'prompt', '' ),
			'tone'       => Input::post( 'tone', 'formal' ),
			'format'     => Input::post( 'format', 'essay' ),
			'language'   => Input::post( 'language', 'english' ),
			'characters' => Input::post( 'characters', 250 ),
			'is_html'    => Input::post( 'is_html', false, Input::TYPE_BOOL ),
		);

		try {
			$messages = Prompts::prepare_text_generation_messages( $input );
			$content  = Helper::generate_text( $messages );
			$content  = $input['is_html'] ? Helper::markdown_to_html( $content ) : $content;

			$this->json_response( __( 'Content generated', 'tutor-pro' ), $content );
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}

	/**
	 * Modify previously generated text content by using openai.
	 * You can modify the tone, language, even make the content shorter or longer and also rephrase the content.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If the provided payloads are not valid.
	 */
	public function modify_text_content() {
		$this->validate_ajax_request();

		$type            = Input::post( 'type' );
		$is_html         = Input::post( 'is_html', false, Input::TYPE_BOOL );
		$available_types = array( 'rephrase', 'make_shorter', 'change_tone', 'translation', 'write_as_bullets', 'make_longer', 'simplify_language' );

		try {
			if ( ! in_array( $type, $available_types, true ) ) {
				throw new InvalidArgumentException( sprintf( 'There is no such a type %s exists.', esc_html( $type ) ) );
			}

			$arguments = array(
				Input::post( 'content', '' ),
				Input::post( 'is_html', false, Input::TYPE_BOOL ),
			);

			switch ( $type ) {
				case 'change_tone':
					$arguments[] = Input::post( 'tone', '' );
					break;
				case 'translation':
					$arguments[] = Input::post( 'language', '' );
					break;
			}

			$method = 'prepare_' . $type . '_messages';

			if ( ! method_exists( Prompts::class, $method ) ) {
				throw new InvalidArgumentException(
					sprintf(
						'There is no such a method %s into the class %s.',
						esc_html( $method ),
						esc_html( Prompts::class )
					)
				);
			}

			$messages = call_user_func_array( array( Prompts::class, $method ), $arguments );
			$content  = Helper::generate_text( $messages );
			$content  = $is_html ? Helper::markdown_to_html( $content ) : $content;

			$this->json_response( __( 'Content updated', 'tutor-pro' ), $content );
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}
}
