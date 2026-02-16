<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ClientContract {
	/**
	 * Create the resource for making http request.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options The options to send to openai endpoint.
	 *
	 * @return array
	 */
	public function create( array $options );
}
