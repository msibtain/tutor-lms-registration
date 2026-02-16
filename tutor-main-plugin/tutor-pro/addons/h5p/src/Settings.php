<?php

/**
 * Handle Settings for Tutor H5P
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Tutor H5P Setting class
 *
 * @since 3.0.0
 */
class Settings {

	/**
	 * Register hooks and dependencies
	 */
	public function __construct() {

		add_filter( 'tutor/options/extend/attr', array( $this, 'add_h5p_settings' ) );
	}

	/**
	 * Add H5P lesson settings on Tutor Settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attr array of tutor setting options.
	 *
	 * @return array
	 */
	public function add_h5p_settings( $attr ) {

		array_push( 
			$attr['course']['blocks']['block_lesson']['fields'], 
			array(
				'key'         => 'disable_complete_lesson_button',
				'type'        => 'toggle_switch',
				'label'       => __( 'Disable "Mark as Complete" Button Until H5P Completion', 'tutor-pro' ),
				'label_title' => '',
				'default'     => 'on',
				'desc'        => __( 'If enabled, students must complete all H5P content before they can mark lessons as complete.', 'tutor-pro' ),
			)
		);

		return $attr;
	}
}
