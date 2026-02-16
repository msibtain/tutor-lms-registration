<?php
/**
 * Manage Settings.
 *
 * @package TutorPro\TutorAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.8
 */

namespace TutorPro\TutorAI;

use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Traits\JsonResponse;
use TUTOR\User;

/**
 * SettingsController Class.
 *
 * @since 2.1.8
 */
class SettingsController {
	use JsonResponse;

	const CHATGPT_API_KEY = 'chatgpt_api_key';
	const CHATGPT_ENABLE  = 'chatgpt_enable';

	/**
	 * Register hooks.
	 *
	 * @since 2.1.8
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'tutor/options/extend/attr', array( $this, 'add_chatgpt_settings_option' ) );
		add_action( 'wp_ajax_tutor_pro_chatgpt_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Add ChatGPT settings to Tutor Settings > Advance section.
	 *
	 * @since 2.1.8
	 *
	 * @param array $attr existing settings attributes.
	 *
	 * @return array
	 */
	public function add_chatgpt_settings_option( $attr ) {
		$chatgpt_settings = array(
			'label'      => __( 'AI Studio', 'tutor-pro' ),
			'slug'       => 'options',
			'block_type' => 'uniform',
			'fields'     => array(
				array(
					'key'     => self::CHATGPT_ENABLE,
					'type'    => 'toggle_switch',
					'label'   => __( 'Enable', 'tutor-pro' ),
					'default' => 'on',
					'desc'    => '',
				),
				array(
					'key'         => self::CHATGPT_API_KEY,
					'type'        => 'text',
					'label'       => __( 'Insert OpenAI API Key', 'tutor-pro' ),
					'default'     => '',
					'desc'        => __( 'Find your Secret API key in your <a href="https://platform.openai.com/account/api-keys" target="blank">OpenAI User settings</a> and paste it here.', 'tutor-pro' ),
					'placeholder' => __( 'API key', 'tutor-pro' ),
				),
			),
		);

		array_push( $attr['advanced']['blocks'], $chatgpt_settings );

		return $attr;
	}

	/**
	 * API for saving ChatGPT API.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function save_settings() {
		tutor_utils()->check_nonce();

		if ( ! User::is_admin() ) {
			$this->json_response( tutor_utils()->error_message() );
		}

		$chatgpt_enable = Input::post( 'chatgpt_enable', true, Input::TYPE_BOOL );
		$api_key        = Input::post( 'chatgpt_api_key', '' );

		if ( $chatgpt_enable && empty( $api_key ) ) {
			$this->json_response( __( 'API key required', 'tutor-pro' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$options        = get_option( 'tutor_option' );
		$chatgpt_enable = $chatgpt_enable ? 'on' : 'off';
		if ( false === $options ) {
			$options = array(
				self::CHATGPT_API_KEY => $api_key,
				self::CHATGPT_ENABLE  => $chatgpt_enable,
			);
		}

		$options[ self::CHATGPT_API_KEY ] = $api_key;
		$options[ self::CHATGPT_ENABLE ]  = $chatgpt_enable;

		update_option( 'tutor_option', $options );

		$this->json_response( __( 'API key saved successfully!', 'tutor-pro' ) );
	}
}
