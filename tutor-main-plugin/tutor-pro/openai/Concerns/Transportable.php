<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Concerns;

use TutorPro\OpenAI\Contracts\TransporterContract;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Transportable {
	/**
	 * The transporter instance.
	 *
	 * @since 3.0.0
	 *
	 * @var TransporterContract The transporter contract.
	 */
	protected $transporter = null;

	/**
	 * The constructor method for storing the transporter instance.
	 *
	 * @since 3.0.0
	 *
	 * @param TransporterContract $transporter The transporter instance.
	 */
	public function __construct( TransporterContract $transporter ) {
		$this->transporter = $transporter;
	}
}
