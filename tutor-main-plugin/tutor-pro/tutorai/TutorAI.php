<?php
/**
 * Main class to handle tutor native e-commerce.
 *
 * @package Tutor\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\TutorAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base class for instantiating the controllers.
 * The controllers are responsible for generating text, image, course contents by using openai.
 *
 * @since 3.0.0
 */
final class TutorAI {

	/**
	 * The constructor method for instantiating the AI Controllers.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init the tutorai classes on after plugin loaded.
	 *
	 * @return void
	 */
	public function init() {
		require_once tutor_pro()->path . 'vendor/autoload.php';
		$this->load_controllers();
	}

	/**
	 * Load the tutorai controllers.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function load_controllers() {
		$controllers = array(
			SettingsController::class,
			ImageController::class,
			TextController::class,
			CourseGenerationController::class,
			CourseCreatorController::class,
		);

		foreach ( $controllers as $controller ) {
			new $controller();
		}
	}
}

// Instantiate the TutorAI class for loading the controllers.
new TutorAI();
