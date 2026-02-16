<?php
/**
 * Manage Update
 *
 * @package TutorPro\Updater
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPRO\ThemeumUpdater;

use TUTOR\Input;

if ( ! class_exists( 'TutorPRO\ThemeumUpdater\Update' ) ) {
	/**
	 * Class Update
	 */
	class Update {
        //phpcs:disable
		private $meta;
		private $product_slug;
		private $url_slug;
		private $license_field_name;
		private $nonce_field_name;
		private $api_end_point = 'https://www.themeum.com/wp-json/themeum-license/v2/';
		private $error_message_key;
		private $themeum_response_data;
		public $is_valid;
        //phpcs:enable

		/**
		 * Constructor
		 *
		 * @param array $meta meta.
		 */
		public function __construct( $meta ) {

			$this->meta               = $meta;
			$this->product_slug       = strtolower( $this->meta['product_slug'] );
			$this->url_slug           = $this->product_slug . '-license';
			$this->license_field_name = $this->url_slug . '-key';
			$this->nonce_field_name   = $this->url_slug . '-nonce';
			$this->error_message_key  = 'themeum_update_error_' . $this->meta['product_basename'];

			$license        = $this->get_license();
			$this->is_valid = $license && $license['activated'];

			if ( ! isset( $this->meta['is_product_free'] ) || true !== $this->meta['is_product_free'] ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'license_page_asset_enqueue' ) );
				add_action( 'tutor_after_settings_menu', array( $this, 'add_license_page' ) );
				add_action( 'admin_init', array( $this, 'check_license_key' ) );
				add_action( 'admin_notices', array( $this, 'show_invalid_license_notice' ) );
			}

			$force_check        = isset( $this->meta['force_update_check'] ) && true === $this->meta['force_update_check'];
			$update_hook_prefix = $force_check ? '' : 'pre_set_';

			if ( 'plugin' === $this->meta['product_type'] ) {
				add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
				add_filter( $update_hook_prefix . 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
				add_action( 'in_plugin_update_message-' . $this->meta['product_basename'], array( $this, 'custom_update_message' ), 10, 2 );
			} elseif ( 'theme' === $this->meta['product_type'] ) {
				add_filter( $update_hook_prefix . 'site_transient_update_themes', array( $this, 'check_for_update' ) );
			}
		}

		/**
		 * Custom update message.
		 *
		 * @param mixed $plugin_data plugin data.
		 * @param mixed $response response.
		 *
		 * @return void
		 */
		public function custom_update_message( $plugin_data, $response ) {

			if ( ! $response->package ) {
				$error_message = get_option( $this->error_message_key );
				echo $error_message ? ' ' . wp_kses_post( $error_message ) . '' : '';
			}
		}

		/**
		 * Asset load
		 *
		 * @return void
		 */
		public function license_page_asset_enqueue() {
			$css_url = $this->meta['updater_url'] . 'license-form.css';

			//phpcs:ignore
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->url_slug ) {
				wp_enqueue_style( $this->url_slug . '-css', $css_url, array(), TUTOR_VERSION );
			}
		}

		/**
		 * Add page.
		 *
		 * @return void
		 */
		public function add_license_page() {
			add_submenu_page( $this->meta['parent_menu'], $this->meta['menu_title'], $this->meta['menu_title'], $this->meta['menu_capability'], $this->url_slug, array( $this, 'license_form' ) );
		}

		/**
		 * Form
		 *
		 * @return void
		 */
		public function license_form() {

			$license          = $this->get_license();
			$field_name       = $this->license_field_name;
			$nonce_field_name = $this->nonce_field_name;
			$product_title    = $this->meta['product_title'];
			$header_content   = $this->meta['header_content'];

			include __DIR__ . '/license-form.php';
		}

		/**
		 * Get update information
		 *
		 * @return array|bool|mixed|object
		 */
		public function check_for_update_api() {
			if ( $this->themeum_response_data ) {
				// Use runtime cache.
				return $this->themeum_response_data;
			}

			$license_info = $this->get_license();
			$license_key  = $license_info ? $license_info['license_key'] : '';

			$params = array(
				'body' => array(
					'license_key'  => $license_key,
					'product_slug' => $this->product_slug,
				),
			);

			// Make the POST request.
			$is_free      = isset( $this->meta['is_product_free'] ) && true === $this->meta['is_product_free'];
			$access_slug  = $is_free ? 'check-update-free' : 'check-update';
			$request      = wp_remote_post( $this->api_end_point . $access_slug, $params );
			$request_body = false;

			// Check if response is valid.
			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$request_body  = json_decode( $request['body'] );
				$response_data = $request_body->data;

				if ( is_object( $response_data ) && property_exists( $response_data, 'dependency_products' ) && property_exists( $response_data, 'option_keymap' ) ) {
					$dependency_products = array();
					$option_keymap       = (array) $response_data->option_keymap;

					// Loop through all the dependencies and prepare license keys.
					foreach ( (array) $response_data->dependency_products as $dependency ) {
						$dependency = (array) $dependency;
						$option_key = isset( $option_keymap[ $dependency['slug'] ] ) ? $option_keymap[ $dependency['slug'] ] : null;

						if ( $option_key ) {
							$license                          = $this->get_license( $option_key );
							! is_array( $license ) ? $license = array() : 0;

							$license['blog_url']                        = get_home_url();
							$dependency_products[ $dependency['slug'] ] = $license;
						}
					}

					// Call again with dependency product data.
					$params['body']['dependency_products'] = $dependency_products;
					$request                               = wp_remote_post( $this->api_end_point . $access_slug, $params );

					if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
						$request_body = json_decode( $request['body'] );
					}
				}
			}

			$this->themeum_response_data = $request_body;
			return $this->themeum_response_data;
		}

		/**
		 * Check license key.
		 *
		 * @return void
		 */
		public function check_license_key() {

			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->url_slug && ! empty( $_POST[ $this->license_field_name ] ) ) {
				if ( ! check_admin_referer( $this->nonce_field_name ) ) {
					return;
				}

				$key       = Input::post( $this->license_field_name, '' );
				$unique_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
				$blog      = get_home_url();

				$api_call = wp_remote_post(
					$this->api_end_point . 'validator',
					array(
						'body' => array(
							'blog_url'     => $blog,
							'license_key'  => $key,
							'action'       => 'check_license_key_api',
							'blog_ip'      => $unique_ip,
							'product_info' => array( 'unique_id' => $this->product_slug ),
						),
					)
				);

				if ( ! is_wp_error( $api_call ) ) {
					$response_body = $api_call['body'];
					$response      = json_decode( $response_body );

					$response_msg = '';
					if ( ! empty( $response->data->msg ) ) {
						$response_msg = $response->data->msg;
					}

					if ( $response->success ) {
						$license_info = array(
							'activated'    => true,
							'license_key'  => $key,
							'license_to'   => $response->data->license_info->customer_name,
							'expires_at'   => $response->data->license_info->expires_at,
							'activated_at' => $response->data->license_info->activated_at,
							'license_type' => $response->data->license_info->license_type,
							'msg'          => $response_msg,
						);
					} else {
						// License is invalid.
						$license_info = array(
							'activated'    => false,
							'license_key'  => $key,
							'license_to'   => '',
							'expires_at'   => '',
							'license_type' => '',
							'msg'          => $response_msg,
						);
					}

					update_option( $this->meta['license_option_key'], $license_info );
				} else {
					$error_string = $api_call->get_error_message();
					echo '<div id="message" class="error"><p>' . esc_html( $error_string ) . '</p></div>';
				}
			}
		}

		/**
		 * Get plugin info from server.
		 *
		 * @param mixed  $res response.
		 * @param string $action action name.
		 * @param mixed  $args args.
		 *
		 * @return bool|\stdClass
		 */
		public function plugin_info( $res, $action, $args ) {

			// do nothing if this is not about getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return false;
			}

			// do nothing if it is not our plugin.
			if ( $this->product_slug !== $args->slug && $this->meta['product_basename'] !== $args->slug ) {
				return $res;
			}

			$remote = $this->check_for_update_api();

			if ( ! is_wp_error( $remote ) ) {

				$res               = new \stdClass();
				$res->name         = $remote->data->plugin_name;
				$res->slug         = $this->product_slug;
				$res->version      = $remote->data->version;
				$res->last_updated = $remote->data->updated_at;
				$res->sections     = array(
					'changelog' => $remote->data->change_log,
				);

				return $res;
			}

			return false;
		}

		/**
		 * Check for update.
		 *
		 * @param mixed $transient transient.
		 *
		 * @return mixed
		 */
		public function check_for_update( $transient ) {

			$base_name = $this->meta['product_basename'];

			$request_body = $this->check_for_update_api();

			if ( ! empty( $request_body->success ) && $request_body->success ) {
				if ( version_compare( $this->meta['current_version'], $request_body->data->version, '<' ) ) {

					$update_info = array(
						'new_version' => $request_body->data->version,
						'package'     => $request_body->data->download_url,
						'tested'      => $request_body->data->tested_wp_version,
						'slug'        => $base_name,
						'url'         => $request_body->data->url,
					);

					$transient->response[ $base_name ] = 'plugin' === $this->meta['product_type'] ? (object) $update_info : $update_info;

					$error_mesage = empty( $request_body->data->error_message ) ? null : $request_body->data->error_message;
					update_option( $this->error_message_key, $error_mesage );
				}
			}
			return $transient;
		}

		/**
		 * Show invalid license notice
		 *
		 * @return void
		 */
		public function show_invalid_license_notice() {
			if ( ! $this->is_valid ) {
				$class   = 'notice notice-error';
				$message = sprintf(
					__( 'There is an error with your %1$s License. Automatic update has been turned off, %2$s Please check license %3$s', $this->url_slug ),//phpcs:ignore
					$this->meta['product_title'],
					" <a href='" . admin_url( 'admin.php?page=' . $this->url_slug ) . "'>",
					'</a>'
				);

				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
			}
		}

		/**
		 * Get license.
		 *
		 * @param string|null $option_key option key.
		 *
		 * @return mixed
		 */
		private function get_license( $option_key = null ) {
			! $option_key ? $option_key = $this->meta['license_option_key'] : 0;
			$license_option             = get_option( $option_key, null );

			if ( ! $license_option ) {
				// Not submitted yet.
				return null;
			}

			$license = maybe_unserialize( $license_option );
			$license = is_array( $license ) ? $license : array();

			$keys = array( 'activated', 'license_key', 'license_to', 'expires_at', 'license_type', 'msg' );
			foreach ( $keys as $key ) {
				$license[ $key ] = ! empty( $license[ $key ] ) ? $license[ $key ] : null;
			}

			return $license;
		}
	}
}

