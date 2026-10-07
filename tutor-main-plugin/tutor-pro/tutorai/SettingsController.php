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
use TUTOR\User;

/**
 * SettingsController Class.
 *
 * @since 2.1.8
 */
class SettingsController extends TutorAIBaseController {

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
		add_filter( 'tutor_course_builder_settings', array( $this, 'extend_course_builder_settings' ) );
		add_filter( 'tutor_course_builder_localized_data', array( $this, 'extend_course_builder_localized_data' ) );
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
		$is_wp_ai_supported = Helper::is_wp_ai_supported();

		$fields = array(
			array(
				'key'     => self::CHATGPT_ENABLE,
				'type'    => 'toggle_switch',
				'label'   => __( 'Enable', 'tutor-pro' ),
				'default' => 'on',
				'desc'    => $is_wp_ai_supported
					? sprintf(
						/* translators: %s: Connectors admin URL */
						__( 'Enable AI features in Tutor LMS. AI connections and models are configured in WordPress <a href="%s" target="_blank">Settings &gt; Connectors</a>.', 'tutor-pro' ),
						esc_url( admin_url( 'options-connectors.php' ) )
					)
					: '',
			),
		);

		if ( ! $is_wp_ai_supported ) {
			$fields[] = array(
				'key'         => self::CHATGPT_API_KEY,
				'type'        => 'text',
				'label'       => __( 'Insert OpenAI API Key', 'tutor-pro' ),
				'default'     => '',
				'desc'        => __( 'Find your Secret API key in your <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI User settings</a> and paste it here.', 'tutor-pro' ),
				'placeholder' => __( 'API key', 'tutor-pro' ),
			);
		}

		$chatgpt_settings = array(
			'label'      => __( 'AI Studio', 'tutor-pro' ),
			'slug'       => 'options',
			'block_type' => 'uniform',
			'fields'     => $fields,
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
		$this->validate_ajax_request();

		if ( ! User::is_admin() ) {
			$this->json_response( tutor_utils()->error_message() );
		}

		$chatgpt_enable = Input::post( 'chatgpt_enable', true, Input::TYPE_BOOL );
		$api_key        = Input::post( 'chatgpt_api_key', '' );

		if ( ! Helper::is_wp_ai_supported() && $chatgpt_enable && empty( $api_key ) ) {
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

	/**
	 * Extend course builder settings with AI configurations.
	 *
	 * @since 4.0.1
	 *
	 * @param array $settings Course builder settings.
	 *
	 * @return array
	 */
	public function extend_course_builder_settings( array $settings ): array {
		if ( isset( $settings['is_wp_ai_supported'] ) ) {
			return $settings;
		}

		$is_wp_ai_supported = Helper::is_wp_ai_supported();
		$has_ai_connector   = $is_wp_ai_supported ? Helper::has_ai_connector() : false;

		$settings['is_wp_ai_supported']   = $is_wp_ai_supported;
		$settings['has_ai_connector']     = $has_ai_connector;
		$settings['connectors_admin_url'] = admin_url( 'options-connectors.php' );

		if ( $is_wp_ai_supported ) {
			$has_image_connector             = $has_ai_connector && Helper::has_image_generation_support();
			$settings['chatgpt_key_exist']   = $has_ai_connector;
			$settings['has_image_connector'] = $has_image_connector;
		} else {
			$full_settings                   = get_option( 'tutor_option', array() );
			$has_legacy_key                  = ! empty( $full_settings['chatgpt_api_key'] ?? '' );
			$settings['chatgpt_key_exist']   = $has_legacy_key;
			$settings['has_image_connector'] = $has_legacy_key;
		}

		return $settings;
	}

	/**
	 * Extend course builder localized data with AI configurations.
	 *
	 * @since 4.0.1
	 *
	 * @param array $data Localized data.
	 *
	 * @return array
	 */
	public function extend_course_builder_localized_data( array $data ): array {
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = $this->extend_course_builder_settings( $data['settings'] );
		}

		return $data;
	}
}
