<?php
/**
 * Helper class for creating the AI prompts for text generation.
 *
 * @package TutorPro\TutorAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\TutorAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for generating AI prompts used by openai.
 *
 * @since 3.0.0
 */
final class Prompts {
	/**
	 * Create the system message for generating text content using tone, format, language, etc.
	 *
	 * @since   3.0.0
	 *
	 * @param array $input The request payload inputs for generating the prompt.
	 *
	 * @return string
	 */
	private static function create_system_message( array $input ) {
		$system_content = 'You are a friendly and helpful assistant. You will be given a prompt, and your task is to generate a {format} for an online course. The content should be in {language} and have a {tone} tone. Ensure the content does not exceed {characters} characters.';

		if ( ! $input['is_html'] ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown, quotation marks, or HTML.';
		}

		foreach ( $input as $key => $value ) {
			$system_content = str_replace( '{' . $key . '}', $value, $system_content );
		}

		return $system_content;
	}

	/**
	 * Prepare the input array for generating text content from the request prompt.
	 *
	 * @since 3.0.0
	 *
	 * @param array $input The request payload inputs for generating the prompt.
	 *
	 * @return array
	 */
	public static function prepare_text_generation_messages( array $input ) {
		return array(
			array(
				'role'    => 'system',
				'content' => self::create_system_message( $input ),
			),
			array(
				'role'    => 'user',
				'content' => $input['prompt'],
			),
		);
	}

	/**
	 * Prepare the input array for translating content to a specific language.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 * @param string $language The target language.
	 *
	 * @return array
	 */
	public static function prepare_translation_messages( string $content, bool $is_html, string $language ) {
		$system_content = 'Your task is to translate the provided text into {language}. Identify the original language if needed and ensure the translation accurately conveys the meaning of the original content.';
		$system_content = str_replace( '{language}', $language, $system_content );

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for rephrasing content
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 *
	 * @return array
	 */
	public static function prepare_rephrase_messages( string $content, bool $is_html ) {
		$system_content = 'Your task is to rephrase any text content provided to you, ensuring that the original meaning is preserved while expressing it differently.';

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for making the content shorten.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 *
	 * @return array
	 */
	public static function prepare_make_shorter_messages( string $content, bool $is_html ) {
		$system_content = 'Your task is to shorten the provided text, retaining the key points and meaning while making it as concise as possible.';

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for changing the tone of the content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 * @param string $tone  The target content tone.
	 *
	 * @return array
	 */
	public static function prepare_change_tone_messages( string $content, bool $is_html, string $tone ) {
		$system_content = "Your task is to change the tone of the provided text to match the specified style, which is {tone}. Ensure that the content's meaning remains consistent while reflecting this new tone.";
		$system_content = str_replace( '{tone}', $tone, $system_content );

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for converting the content into bullet points.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 *
	 * @return array
	 */
	public static function prepare_write_as_bullets_messages( string $content, bool $is_html ) {
		$system_content = 'Your task is to rewrite the provided text as bullet points. Ensure that each point is clear and concise while preserving the original meaning.';

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for making the content larger.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 *
	 * @return array
	 */
	public static function prepare_make_longer_messages( string $content, bool $is_html ) {
		$system_content = 'Your task is to expand the provided text, adding more detail and depth while maintaining the original meaning and intent.';

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for simplifying the language of the generated content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content to modify.
	 * @param bool   $is_html If the should come in HTML or plain text.
	 *
	 * @return array
	 */
	public static function prepare_simplify_language_messages( string $content, bool $is_html ) {
		$system_content = 'Your task is to simplify the language of the provided text, making it easier to understand while preserving the original meaning.';

		if ( ! $is_html ) {
			$system_content .= ' Please respond with plain text only. Do not use markdown or HTML.';
		}

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $content,
			),
		);
	}

	/**
	 * Prepare the input array for creating the course title.
	 *
	 * @since 3.0.0
	 *
	 * @param string $prompt The input prompt for generating course title.
	 *
	 * @return array
	 */
	public static function prepare_course_title_messages( string $prompt ) {
		return array(
			array(
				'role'    => 'system',
				'content' => 'You are a highly skilled assistant specialized in generating course titles for an e-learning platform. When provided with a prompt describing a course, your task is to create a concise, compelling, and marketable course title. The title should be clear, engaging, and appropriate for the specified audience, which could range from beginners to advanced learners. Ensure that the title reflects the course content accurately and consider the use of impactful language that highlights the value of the course. Do not use markdown or HTML, do not wrap the content with quotes, and the title should not exceed 100 characters.',
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);
	}

	/**
	 * Prepare the course description with the help of the course title.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The course title.
	 *
	 * @return array
	 */
	public static function prepare_course_description_messages( string $title ) {
		return array(
			array(
				'role'    => 'system',
				'content' => 'You are an AI assistant that specializes in generating detailed course descriptions for an e-learning platform. Based on the provided course title, your task is to create a compelling and informative course description that includes the following elements: an overview of the course content, key learning outcomes, and a clear identification of the target audience. The description should be engaging, informative, and accurately reflect the skills and knowledge students will gain. Ensure that the language is accessible, with a tone that is both motivating and professional, and tailored to the specified audience, whether they are beginners, intermediate learners, or advanced professionals. Please respond with plain text only.',
			),
			array(
				'role'    => 'user',
				'content' => $title,
			),
		);
	}

	/**
	 * Prepare the messages for openai for generating topic names.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The course title.
	 *
	 * @return array
	 */
	public static function prepare_course_topic_names_messages( string $title ) {
		$system_content = 'You are an AI assistant specialized in generating course module names. You are tasked with generating course module names for a given course title. Based on this course title, create at most 5 modules names that follow a logical progression, starting with introductory topics and moving toward more advanced concepts. Ensure that the module names include standard course elements like an introduction, a course outline, and a conclusion. The names should be clear, concise, and directly related to the content of the course. Return the module names as a JSON array in the format: [{title: "Module title"}].';

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => $title,
			),
		);
	}

	/**
	 * Prepare the messages for openai for generating topic contents.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The course title.
	 * @param string $topic_name The topic name.
	 *
	 * @return array
	 */
	public static function prepare_course_topic_content_messages( string $title, string $topic_name ) {
		$is_assignment_addon_enabled = tutor_utils()->is_addon_enabled( TUTOR_ASSIGNMENTS()->basename );

		$content_types = array( 'lesson', 'quiz' );
		if ( $is_assignment_addon_enabled ) {
				$content_types[] = 'assignment';
		}

		$content_types_string = implode( "', '", $content_types );

		$system_content = 'You are an AI assistant specialized in generating course contents. Generate at most 5 content items based on the provided course title and module name. The content can include any of the following types: \'' . $content_types_string . '\'. For each content item, provide a title and a description that accurately reflects the content. Return the generated content as a JSON array with the structure: [{type: "' . implode( '|', $content_types ) . '", title: "the content title", description: "the content description"}].';

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => 'The course title is: ' . $title,
			),
			array(
				'role'    => 'user',
				'content' => 'The module name is: ' . $topic_name,
			),
		);
	}

	/**
	 * Prepare the messages for the openai chat API for generating quiz content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The course title.
	 * @param string $topic_name The course module/topic name.
	 * @param string $quiz_title The quiz title.
	 *
	 * @return array
	 */
	public static function prepare_quiz_questions_messages( string $title, string $topic_name, string $quiz_title ) {
		$system_content = "You are an intelligent assistant tasked with creating quiz questions for a course. You are provided a course title, a course module name, and a quiz title.
    Please generate at most 3 quiz questions of the following types:
    - True/False
    - Multiple Choice
    - Open-Ended

    Each question must have:
    - A clear question title.
    - A brief description that adds context to the question.
    - For true/false questions: two options - 'true' and 'false'.
    - For multiple-choice questions: several answer options with one correct answer.
    - For open-ended questions: no options (only the question and description).

		Special reminder, **please generate the true/false questions as less as possible, prioritize multiple-choice questions more**.
		Additionally, please ensure that some of the questions have a question mark ('?') as the value of the title.

		The response should be in **valid JSON** format as follows, and make sure not to use any suffix or prefix with the response:

    [
      {
        'title': 'the question title?',
				'type': 'true_false|open_ended|multiple_choice',
        'options': [
          {
            'name': 'option name',
            'is_correct': true
          },
          {
            'name': 'option name',
            'is_correct': false
          }
        ]
      }
    ]

    Please ensure that the provided JSON is valid and properly structured. Include a variety of question types (true/false, multiple-choice, and open-ended), and make sure the content relates to the course and module.
    Make sure the number of questions falls between 3 to 5, with some having '?' as the title.
		";

		return array(
			array(
				'role'    => 'system',
				'content' => $system_content,
			),
			array(
				'role'    => 'user',
				'content' => 'The course title: ' . $title,
			),
			array(
				'role'    => 'user',
				'content' => 'The module name: ' . $topic_name,
			),
			array(
				'role'    => 'user',
				'content' => 'The quiz title: ' . $quiz_title,
			),
		);
	}
}
