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

use TutorPro\OpenAI\Contracts\TransporterContract;
use TutorPro\OpenAI\Resources\Chat;
use TutorPro\OpenAI\Contracts\ClientContract;
use TutorPro\OpenAI\Resources\Edits;
use TutorPro\OpenAI\Resources\Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The openai client
 *
 * @since 3.0.0
 */
class Client {
	/**
	 * The transporter instance with required metadata.
	 *
	 * @since 3.0.0
	 *
	 * @var TransporterContract
	 */
	private $transporter = null;

	/**
	 * The constructor function of the client class.
	 *
	 * @since 3.0.0
	 *
	 * @param TransporterContract $transporter The transporter instance.
	 */
	public function __construct( TransporterContract $transporter ) {
		$this->transporter = $transporter;
	}

	/**
	 * The image generation client instance
	 *
	 * @since 3.0.0
	 *
	 * @return ClientContract
	 */
	public function images() {
		return new Images( $this->transporter );
	}

	/**
	 * The chat completion client instance.
	 *
	 * @since 3.0.0
	 *
	 * @return ClientContract
	 */
	public function chat() {
		return new Chat( $this->transporter );
	}

	/**
	 * The chat completion client instance.
	 *
	 * @since 3.0.0
	 *
	 * @return ClientContract
	 */
	public function edits() {
		return new Edits( $this->transporter );
	}
}
