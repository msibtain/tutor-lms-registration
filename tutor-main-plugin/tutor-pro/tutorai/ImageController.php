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
use RuntimeException;
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
 * Image controller class.
 * This class is responsible for generating image using openai.
 *
 * @since 3.0.0
 */
class ImageController extends TutorAIBaseController {

	/**
	 * Constructor method for generating AI Images.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		/**
		 * Handle AJAX request for generating AI images
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_generate_image', array( $this, 'generate_image' ) );

		/**
		 * Handle AJAX request for editing AI image
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_magic_fill_image', array( $this, 'magic_fill_image' ) );

		/**
		 * Handle AJAX request for using the AI generated image to the WP system.
		 *
		 * @since 3.0.0
		 */
		add_action( 'wp_ajax_tutor_pro_use_magic_image', array( $this, 'use_magic_image' ) );
	}

	/**
	 * Generate the prompt for the specific styles
	 *
	 * @since 3.0.0
	 *
	 * @param string $prompt The user prompt for generating image.
	 * @param string $style The style of the output image.
	 *
	 * @return string
	 */
	private static function generate_prompt( $prompt, $style ) {
		$style_prompts = array(
			'filmic'          => 'Create an image of {user_prompt} with a cinematic quality, incorporating deep contrasts, rich colors, and dramatic lighting. The scene should evoke the feeling of a classic film.',
			'photo'           => 'Generate a high-resolution photograph {user_prompt} of with realistic lighting, shadows, and textures. The image should have a natural, lifelike quality as if captured by a professional camera.',
			'neon'            => 'Create an image of {user_prompt} with vibrant neon colors and glowing elements. The design should feature bright, fluorescent lights and a modern, urban aesthetic reminiscent of neon signs and cityscapes.',
			'dreamy'          => 'Design an image of {user_prompt} with a dreamy, ethereal quality, using soft focus, pastel colors, and gentle lighting. The scene should evoke a sense of whimsy and surreal beauty, like something out of a fantasy.',
			'black_and_white' => 'Generate a black and white image of {user_prompt} with high contrast and a wide range of grays. The absence of color should emphasize the shapes, textures, and lighting to create a dramatic and timeless look.',
			'retrowave'       => 'Design an image of {user_prompt} with a retro 80s aesthetic, featuring neon colors, grid patterns, and futuristic elements that evoke the style of synthwave music and retro video games.',
			'3d'              => 'Create an image of {user_prompt} 3D low poly, featuring game style, clean edges, and vibrant colors. The render should emphasize the crafted nature of 3D, focusing on expressive forms and controlled lighting.',
			'concept_art'     => 'Produce a piece of concept art of {user_prompt} that showcases a creative and imaginative design. Use detailed textures, dynamic compositions, and a strong visual narrative to convey the concept effectively.',
			'sketch'          => 'Create a sketch-style image of {user_prompt} with clean, hand-drawn lines and minimal shading. The design should look like a detailed pencil or ink drawing, capturing the essence of the subject with simplicity and elegance.',
			'illustration'    => "Create an illustration of {user_prompt} with vibrant colors, clear outlines, and stylized elements. The design should have a playful and imaginative quality, with detailed characters and scenes that capture the viewer's attention and convey a strong visual story.",
			'painting'        => 'Design an image of {user_prompt} with the texture and style of a traditional painting. Use brushstroke effects, rich colors, and painterly techniques to create a piece that looks like it was painted by hand on canvas.',
		);

		if ( empty( $style ) || 'none' === $style ) {
			return $prompt;
		}

		if ( empty( $style_prompts[ $style ] ) ) {
			return $prompt;
		}

		$style_prompt = 'You are an intelligent assistant tasked with generating banner images for an e-learning application. ' . $style_prompts[ $style ];

		return str_replace( '{user_prompt}', $prompt, $style_prompt );
	}

	/**
	 * Generate image using the user prompt and the styles
	 *
	 * @since 3.0.0
	 *
	 * @throws Exception If error occur.
	 *
	 * @return void
	 */
	public function generate_image() {
		$this->validate_ajax_request();

		$prompt = Input::post( 'prompt' );
		$style  = Input::post( 'style' );

		if ( empty( $prompt ) ) {
			$this->json_response(
				__( 'Prompt is required to generating images', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		$prompt = self::generate_prompt( $prompt, $style );

		// 1. If WordPress AI Connectors is supported, strictly use WP AI Connectors.
		if ( Helper::is_wp_ai_supported() ) {
			if ( Helper::has_image_generation_support() && function_exists( 'wp_ai_client_prompt' ) ) {
				try {
					// Image generation (e.g. DALL·E 3) can take 45–90s.
					// Extend PHP execution time and set a 120s HTTP timeout
					// so the request is not killed by the 30s default.
					@set_time_limit( 180 );
					$request_options = new RequestOptions();
					$request_options->setTimeout( 120.0 );
					$builder = wp_ai_client_prompt( $prompt );
					$image   = $builder->usingRequestOptions( $request_options )->generate_image();
					if ( is_wp_error( $image ) ) {
						throw new Exception( $image->get_error_message() );
					}
					if ( ! empty( $image ) ) {
						$b64 = '';
						$url = '';
						if ( is_object( $image ) ) {
							if ( method_exists( $image, 'getDataUri' ) && $image->getDataUri() ) {
								$b64 = $image->getDataUri();
							} elseif ( method_exists( $image, 'getBase64Data' ) && $image->getBase64Data() ) {
								$mime = method_exists( $image, 'getMimeType' ) ? $image->getMimeType() : 'image/png';
								$b64  = 'data:' . $mime . ';base64,' . $image->getBase64Data();
							}
							if ( method_exists( $image, 'getUrl' ) && $image->getUrl() ) {
								$url = $image->getUrl();
							}
						} elseif ( is_string( $image ) ) {
							if ( preg_match( '/^https?:\/\//i', $image ) ) {
								$url = $image;
							} elseif ( 0 === strpos( $image, 'data:' ) ) {
								$b64 = $image;
							} else {
								$b64 = 'data:image/png;base64,' . $image;
							}
						}

						// If we have a remote URL but no data URI, download and convert to data URI.
						if ( empty( $b64 ) && ! empty( $url ) ) {
							$remote_img = wp_remote_get( $url );
							if ( ! is_wp_error( $remote_img ) ) {
								$body = wp_remote_retrieve_body( $remote_img );
								$mime = wp_remote_retrieve_header( $remote_img, 'content-type' ) ?: 'image/png';
								if ( ! empty( $body ) ) {
									$b64 = 'data:' . $mime . ';base64,' . base64_encode( $body );
								}
							}
						}

						// Ensure b64 has data URI format so browser <img> and upload_base64_image can process it.
						if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
							$b64 = 'data:image/png;base64,' . $b64;
						}

						$response = (object) array(
							'created' => time(),
							'data'    => array(
								(object) array(
									'b64_json' => $b64,
									'url'      => $url,
								),
							),
						);
						$this->json_response( __( 'Image created', 'tutor-pro' ), $response );
						return;
					}
				} catch ( Throwable $error ) {
					$error_msg = $error->getMessage();
					if ( false !== stripos( $error_msg, 'No models found' ) ) {
						$error_msg = __( 'The configured AI connector does not support image generation. Please configure an image-capable connector in WordPress Settings > Connectors.', 'tutor-pro' );
					}
					$this->json_response( $error_msg, null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
					return;
				}
			}

			if ( ! Helper::has_ai_connector() ) {
				$this->json_response(
					__( 'No AI connector is configured in WordPress. Please configure an AI connector in WordPress Settings > Connectors.', 'tutor-pro' ),
					null,
					HttpHelper::STATUS_BAD_REQUEST
				);
				return;
			}

			$this->json_response(
				__( 'The configured AI connector does not support image generation. Please configure an image-capable connector in WordPress Settings > Connectors.', 'tutor-pro' ),
				null,
				HttpHelper::STATUS_BAD_REQUEST
			);
			return;
		}

		// 2. Fall back to legacy OpenAI key (only for WP < 7.0).
		$chatgpt_api_key = tutor_utils()->get_option( 'chatgpt_api_key' );
		if ( ! empty( $chatgpt_api_key ) ) {
			$input = array(
				'model'  => Models::GPT_IMAGE_2_5_FLARE,
				'prompt' => $prompt,
				'n'      => 1,
				'size'   => Sizes::LANDSCAPE,
			);

			try {
				$client   = Helper::get_openai_client();
				$response = $client->images()->create( $input );
				$response = Helper::check_openai_response( $response );

				// Ensure legacy OpenAI b64_json and url are synced and in data URI format.
				if ( is_object( $response ) && ! empty( $response->data ) && is_array( $response->data ) ) {
					foreach ( $response->data as &$item ) {
						$b64 = is_object( $item ) && ! empty( $item->b64_json ) ? (string) $item->b64_json : '';
						$url = is_object( $item ) && ! empty( $item->url ) ? (string) $item->url : '';

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
						}

						if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
							$b64 = 'data:image/png;base64,' . $b64;
						}

						if ( is_object( $item ) ) {
							$item->b64_json = $b64;
							$item->url      = ! empty( $url ) ? $url : $b64;
						}
					}
					unset( $item );
				} elseif ( is_array( $response ) && ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
					foreach ( $response['data'] as &$item ) {
						$b64 = is_array( $item ) && ! empty( $item['b64_json'] ) ? (string) $item['b64_json'] : '';
						$url = is_array( $item ) && ! empty( $item['url'] ) ? (string) $item['url'] : '';

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
						}

						if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
							$b64 = 'data:image/png;base64,' . $b64;
						}

						if ( is_array( $item ) ) {
							$item['b64_json'] = $b64;
							$item['url']      = ! empty( $url ) ? $url : $b64;
						}
					}
					unset( $item );
				}

				$this->json_response( __( 'Image created', 'tutor-pro' ), $response );
				return;
			} catch ( Throwable $error ) {
				$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
				return;
			}
		}

		$this->json_response(
			__( 'No OpenAI API key found. Please add the API key in Tutor Settings > Advanced.', 'tutor-pro' ),
			null,
			HttpHelper::STATUS_BAD_REQUEST
		);
	}

	/**
	 * Edit image by selecting an area.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function magic_fill_image() {
		$this->validate_ajax_request();

		$prompt         = Input::post( 'prompt' );
		$image          = Input::post( 'image' );
		$revised_prompt = 'Fill the image and replace the selected area by {prompt}';

		$input = array(
			'model'  => Models::DALL_E_2,
			'image'  => $image,
			'prompt' => str_replace( '{prompt}', $prompt, $revised_prompt ),
			'n'      => 1,
			'size'   => Sizes::REGULAR,
		);

		try {
			$client   = Helper::get_openai_client();
			$response = $client->edits()->create( $input );
			$response = Helper::check_openai_response( $response );

			if ( is_object( $response ) && ! empty( $response->data ) && is_array( $response->data ) ) {
				foreach ( $response->data as &$item ) {
					$b64 = is_object( $item ) && ! empty( $item->b64_json ) ? (string) $item->b64_json : '';
					$url = is_object( $item ) && ! empty( $item->url ) ? (string) $item->url : '';

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
					}

					if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
						$b64 = 'data:image/png;base64,' . $b64;
					}

					if ( is_object( $item ) ) {
						$item->b64_json = $b64;
						$item->url      = ! empty( $url ) ? $url : $b64;
					}
				}
				unset( $item );
			} elseif ( is_array( $response ) && ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
				foreach ( $response['data'] as &$item ) {
					$b64 = is_array( $item ) && ! empty( $item['b64_json'] ) ? (string) $item['b64_json'] : '';
					$url = is_array( $item ) && ! empty( $item['url'] ) ? (string) $item['url'] : '';

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
					}

					if ( ! empty( $b64 ) && 0 !== strpos( $b64, 'data:' ) && 0 !== strpos( $b64, 'http' ) ) {
						$b64 = 'data:image/png;base64,' . $b64;
					}

					if ( is_array( $item ) ) {
						$item['b64_json'] = $b64;
						$item['url']      = ! empty( $url ) ? $url : $b64;
					}
				}
				unset( $item );
			}

			$this->json_response( __( 'Mask applied successfully.', 'tutor-pro' ), $response );
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}

	/**
	 * Use the image generated by the AI, upload this image to the media.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 *
	 * @throws RuntimeException Throws an exception if any error happens while uploading the bits.
	 */
	public function use_magic_image() {
		$this->validate_ajax_request();

		$image = Input::post( 'image' );

		if ( empty( $image ) ) {
			$this->json_response( __( 'Image is missing to use', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		try {
			$response = tutor_utils()->upload_base64_image( $image );
			$this->json_response( __( 'Image stored', 'tutor-pro' ), $response );
		} catch ( Throwable $error ) {
			$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_INTERNAL_SERVER_ERROR );
		}
	}
}
