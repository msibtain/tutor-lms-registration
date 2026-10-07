<?php //phpcs:ignore
/**
 * Manage License Key & Update tutor pro plugin
 *
 * @package Tutor\Updater
 * @subpackage Tutor\Updater\Update
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

		/**
		 *  Plugin meta data
		 *
		 * @var array
		 */
		private $meta;

		/**
		 * Product slug
		 *
		 * @var string
		 */
		private $product_slug;

		/**
		 * Api end point
		 *
		 * @var string
		 */
		private $api_end_point = 'https://tutorlms.com/wp-json/themeum-products/v1/';

		/**
		 * Error message key
		 *
		 * @var string
		 */
		private $error_message_key;

		/**
		 * Themeum response data
		 *
		 * @var array
		 */
		private $themeum_response_data = array();
		/**
		 * License transient key
		 *
		 * @since 4.0.2
		 *
		 * @var string
		 */
		const LICENSE_TRANSIENT_KEY = 'tutor_license_transient_key';

		const TOKEN_REFRESH_ENDPOINT = 'oauth/tokens';
		const GRANT_TYPE_REFRESH     = 'refresh';

		/**
		 * Secret key to pass platform API header
		 *
		 * @since 4.0.2
		 */
		const API_SECRET_KEY = 't344d5d71sae7dcb546b8cf55e594808';

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->meta = array(
				'product_title'      => 'Tutor Pro',
				'product_slug'       => 'tutor-pro',
				'product_basename'   => tutor_pro()->basename,
				'product_type'       => 'plugin',
				'current_version'    => TUTOR_PRO_VERSION,
				'menu_title'         => __( 'License', 'tutor-pro' ),
				'parent_menu'        => 'tutor',
				'menu_capability'    => 'manage_tutor',
				'license_option_key' => 'tutor_license_info',
				'updater_url'        => tutor_pro()->url . 'updater/',
				'header_content'     => '<svg width="116" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><defs/><path d="M27.496 19.704V6.36h-4c-.452 0-.62-.288-.62-.988v-.62c0-.704.168-.992.62-.992H34.56c.456 0 .62.288.62.992v.62c0 .7-.208.988-.62.988h-3.964v13.344c0 .456-.372.704-1.24.704h-.62c-.868-.008-1.24-.248-1.24-.704zM53.148 14.292c0-3.924 1.944-6.072 5.6-6.072 3.656 0 5.576 2.148 5.576 6.072s-1.924 6.072-5.556 6.072-5.62-2.148-5.62-6.072zm8.18 0c0-2.52-.8-3.8-2.56-3.8s-2.604 1.28-2.604 3.8c0 2.52.8 3.8 2.604 3.8s2.56-1.292 2.56-3.8zM66 8.22h1.94a.324.324 0 01.332.332l.124 1.732c.576-.948 1.404-1.776 2.6-1.776 1.076 0 1.364.4 1.364 1.116 0 .456-.248 1.736-.66 1.736a8.735 8.735 0 00-1.156-.208c-1.076 0-1.82.952-2 1.28v7.272c0 .456-.372.704-1.28.704h-.372c-.868 0-1.24-.248-1.24-.704V8.552A.326.326 0 0166 8.22zM78.1 19.664V4.256c0-.332.288-.496.908-.496h.192c.66 0 .908.204.908.496v14.332h6.4c.332 0 .456.208.456.704v.372c0 .496-.124.7-.456.7H78.8a.611.611 0 01-.7-.7zM102.225 3.716h-.8a1.027 1.027 0 00-.988.4l-4.988 8.564-5-8.548c-.12-.248-.4-.4-.988-.4h-.828c-.66 0-.908.208-.908.496v15.64c0 .332.288.496.908.496h.168c.664 0 .912-.204.912-.496v-11.6c.007-.427-.008-.854-.044-1.28l.084-.04c.152.365.345.71.576 1.032L94.5 15.2a.448.448 0 00.492.292h.912a.576.576 0 00.496-.292l4.088-7.064c.208-.328.372-.66.58-1.032l.08.044c-.04.4-.04.864-.04 1.28v11.44c0 .332.288.496.908.496h.164c.664 0 .912-.204.912-.496v-15.6c.08-.344-.208-.552-.868-.552zM105.488 19.248c-.292-.204-.456-.4-.456-.576 0-.372.576-1.28.824-1.28.181.047.35.132.496.248a7.162 7.162 0 003.556.948c1.776 0 3.056-1.116 3.056-2.768 0-1.9-1.736-2.476-3.552-3.18-2-.8-4.092-1.528-4.092-4.628 0-2.52 2.068-4.336 5.2-4.336.992 0 2.52.288 3.308.828a.794.794 0 01.452.62c0 .368-.496 1.236-.744 1.236a1.438 1.438 0 01-.62-.288 5.09 5.09 0 00-2.436-.62c-1.776 0-3.016.952-3.016 2.644 0 1.692 1.488 2.148 3.18 2.8 2.108.8 4.464 1.736 4.464 4.96 0 2.8-2.068 4.544-5.2 4.544-2.028 0-3.68-.572-4.42-1.152zM51.828 17.888a5.25 5.25 0 01-.8.08c-.62 0-.992-.288-.992-1.28v-6.156h1.236a.632.632 0 00.62-.62V8.756a.636.636 0 00-.62-.62h-1.236V5.6a.636.636 0 00-.62-.616H47.68a.632.632 0 00-.604.616v2.52h-.8a.636.636 0 00-.62.62v1.156a.632.632 0 00.62.62h.8v6.212c0 2.644 1.448 3.6 3.304 3.6.704 0 2.068-.084 2.068-.992.04-.168-.208-1.448-.62-1.448zM43.856 8.18a.636.636 0 01.62.62v10.944a.636.636 0 01-.62.62h-1.572a.636.636 0 01-.62-.62v-1.156a4.798 4.798 0 01-3.6 1.776c-2.4 0-3.6-1.28-3.6-3.8V8.8a.636.636 0 01.62-.62h1.736a.632.632 0 01.632.62v7.352c0 1.116.576 1.736 1.776 1.736.992 0 1.816-.744 2.272-1.448V8.8a.631.631 0 01.62-.62h1.736z" fill="#092844"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.824 15.284a1.2 1.2 0 01-1.156-1.156v-2.644a1.156 1.156 0 112.312 0v2.644a1.128 1.128 0 01-1.156 1.156zM13.168 15.284A1.154 1.154 0 0112 14.128v-2.644a1.156 1.156 0 012.312 0v2.644a1.153 1.153 0 01-1.156 1.156" fill="#0049F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M2.512 9.5a2.928 2.928 0 012.56-1.692 2.948 2.948 0 012.852 3.016v5.412a1.084 1.084 0 002.148 0v-5.412a2.944 2.944 0 012.848-3.016 2.672 2.672 0 012.48 1.568A7.2 7.2 0 112.512 9.5zm4.544-7.1h4.048v1.564a8.905 8.905 0 00-2.064-.248c-.672.007-1.341.076-2 .208L7.056 2.4zm10.988 11.068c0-.248.04-.456.04-.704a9.077 9.077 0 00-4.624-7.888V2.4h1.74a1.2 1.2 0 000-2.4H2.924a1.264 1.264 0 00-1.2 1.24 1.2 1.2 0 001.2 1.2H4.7v2.44a9.028 9.028 0 00-3.264 12.8C4.8 23.38 13.912 23.96 17.012 24a1.2 1.2 0 00.744-.288 1.008 1.008 0 00.288-.744v-9.5z" fill="#0049F8"/></svg>',
			);

			$this->product_slug      = strtolower( $this->meta['product_slug'] );
			$this->error_message_key = 'themeum_update_error_' . $this->meta['product_basename'];

			$license = $this->get_license();

			$force_check        = isset( $this->meta['force_update_check'] ) && true === $this->meta['force_update_check'];
			$update_hook_prefix = $force_check ? '' : 'pre_set_';

			if ( 'plugin' === $this->meta['product_type'] ) {
				add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
				add_filter( $update_hook_prefix . 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
				add_action( 'in_plugin_update_message-' . $this->meta['product_basename'], array( $this, 'custom_update_message' ), 10, 2 );
				add_action( 'wp_ajax_delete_tutor_license', array( $this, 'delete_tutor_license' ) );
				add_action( 'wp_ajax_update_tutor_license', array( $this, 'update_tutor_license' ) );
				add_action( 'wp_ajax_tutor_oauth_check', array( $this, 'tutor_oauth_check' ) );
				add_filter( 'upgrader_pre_download', array( $this, 'check_plugin_license_before_update' ), 10, 3 );
			} elseif ( 'theme' === $this->meta['product_type'] ) {
				add_filter( $update_hook_prefix . 'site_transient_update_themes', array( $this, 'check_for_update' ) );
			}

			add_action(
				'current_screen',
				function ( $screen ) {
					if ( 'plugins' === $screen->id ) {
						delete_site_transient( 'update_plugins' );
						wp_update_plugins();
					}
				}
			);

			add_filter( 'tutor/options/extend/attr', array( $this, 'add_license_settings_tab' ), PHP_INT_MAX );
			add_filter( 'tutor_option_template_path', array( $this, 'include_license_template' ), PHP_INT_MAX, 2 );
		}

		/**
		 * Tutor oauth check
		 *
		 * @return void
		 */
		public function tutor_oauth_check() {
			tutor_utils()->check_nonce();
			tutor_utils()->check_current_user_capability();
			$license_key = Input::post( 'license_key' );
			$site_url    = get_site_url();
			try {
				$params = array(
					'body'    => array(
						'license_key' => $license_key,
						'website_url' => $site_url,
						'version'     => TUTOR_PRO_VERSION,
					),
					'headers' => $this->get_api_header(),
				);

				$is_authorize_response = wp_remote_post(
					$this->api_end_point . 'oauth/authorize',
					$params
				);

				if ( ! is_wp_error( $is_authorize_response ) ) {
					$is_authorize_response_body = $is_authorize_response['body'];
					$is_authorize               = json_decode( $is_authorize_response_body );
					wp_send_json( $is_authorize );
				}
			} catch ( \Throwable $th ) {
				wp_send_json_error( __( 'Something went wrong!', 'tutor-pro' ), 400 );
			}
		}

		/**
		 * Delete tutor license
		 *
		 * @return void
		 */
		public function delete_tutor_license() {
			tutor_utils()->check_nonce();
			try {
				$license_deleted = delete_option( $this->meta['license_option_key'] );
				if ( $license_deleted ) {
					delete_transient( self::LICENSE_TRANSIENT_KEY );
					wp_send_json(
						array(
							'status_code' => 200,
							'response'    => __( 'License removed.', 'tutor-pro' ),
							'url'         => admin_url( 'admin.php?page=tutor_settings&tab_page=license' ),
						),
						200
					);
				} else {
					wp_send_json_error( false, 400 );
				}
			} catch ( \Throwable $th ) {
				wp_send_json_error( false, 400 );
			}
		}


		/**
		 * Update tutor license
		 *
		 * @return void
		 */
		public function update_tutor_license() {
			tutor_utils()->check_nonce();
			tutor_utils()->check_current_user_capability();
			$updated_license_key = Input::post( 'updated_license_key' );
			try {
				$license_updated = $this->update_license( $updated_license_key );
				wp_send_json( $license_updated );
			} catch ( \Throwable $th ) {
				wp_send_json_error( false, 400 );
			}
		}


		/**
		 * Update license key
		 *
		 * @param string $key license key.
		 *
		 * @return  mixed
		 */
		public function update_license( $key ) {
			$license_option = get_option( $this->meta['license_option_key'], null );
			$license        = maybe_unserialize( $license_option );
			$license        = is_array( $license ) ? $license : array();
			if ( ! empty( $license ) ) {
				$license['license_key'] = $key;
				$verify                 = $this->verify_license( $license );
				return $verify;
			} else {
				return false;
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
		 * Get update information
		 *
		 * @return array|bool|mixed|object
		 */
		public function check_for_update_api() {
			if ( $this->themeum_response_data ) {
				// Use runtime cache.
				return $this->themeum_response_data;
			}

			$license_info     = get_option( $this->meta['license_option_key'], null );
			$license_key      = $license_info ? $license_info['license_key'] ?? '' : '';
			$access_token     = $license_info ? $license_info['access_token'] ?? '' : '';
			$refresh_token    = $license_info ? $license_info['refresh_token'] ?? '' : '';
			$token_expires_at = $license_info ? $license_info['tokens_expires_at'] ?? '' : '';
			$is_token_expired = time() >= $token_expires_at;

			$site_url = get_site_url();

			if ( ! empty( $access_token ) ) {
				$end_point = 'plugin-update-status';
			} else {
				$end_point = 'check-update';
			}

			// Get new access token if it expired.
			if ( $refresh_token && $token_expires_at && $is_token_expired ) {
				$refreshed_tokens = $this->get_refreshed_tokens( $refresh_token );
				if ( tutor_utils()->count( $refreshed_tokens ) ) {
					$this->update_license_info_by_refreshed_tokens( $refreshed_tokens, $license_info );
					$access_token = $refreshed_tokens['access_token'];
				}
			}

			$params = array(
				'body'    => array(
					'license_key'  => $license_key,
					'product_slug' => $this->product_slug,
					'website_url'  => $site_url,
				),
				'headers' => $this->get_api_header( $access_token ),
			);

			// Make the POST request.
			$request = wp_remote_post( $this->api_end_point . '' . $end_point, $params );

			$response_data = array();

			// Check if response is valid.
			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$response_data = json_decode( $request['body'] );
				if ( is_object( $response_data ) && property_exists( $response_data, 'code' ) && 'expired_token' === $response_data->code ) {
					$refreshed_tokens = $this->get_refreshed_tokens( $refresh_token );
					if ( tutor_utils()->count( $refreshed_tokens ) ) {
						$this->update_license_info_by_refreshed_tokens( $refreshed_tokens, $license_info );

						$access_token = $refreshed_tokens['access_token'];

						$params['headers'] = $this->get_api_header( $access_token );
						$response          = wp_remote_post( $this->api_end_point . '' . $end_point, $params );
						if ( ! is_wp_error( $request ) ) {
							$body          = wp_remote_retrieve_body( $response );
							$response_data = json_decode( $body );
						}
					}
				}
			}

			$this->themeum_response_data = $response_data;
			return $this->themeum_response_data;
		}

		/**
		 * Check license key.
		 *
		 * @return void
		 */
		public function check_license_key() {
			if ( isset( $_GET['authorize_token'] ) ) {
				$authorize_token = Input::get( 'authorize_token' );
				$redirect_url    = home_url() . '/wp-admin/admin.php?page=tutor_settings&tab_page=license';
				try {
					$api_call     = wp_remote_post(
						$this->api_end_point . 'oauth/tokens',
						array(
							'body'    => array(
								'grant_type' => 'authorize',
								'token'      => $authorize_token,
							),
							'headers' => $this->get_api_header(),
						)
					);
					$license_info = array(
						'access_token'      => '',
						'refresh_token'     => '',
						'tokens_expires_at' => '',
						'activated'         => false,
						'license_key'       => '',
						'customer_name'     => '',
						'expires_at'        => '',
						'activated_at'      => '',
						'license_type'      => '',
					);
					if ( ! is_wp_error( $api_call ) ) {
						$response_body = $api_call['body'];
						$response      = json_decode( $response_body );
						if ( 200 === $response->status ) {
							$license_info = array_combine( array_keys( $license_info ), array_values( (array) $response->body_response ) );
							update_option( $this->meta['license_option_key'], $license_info, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_info, DAY_IN_SECONDS );
							$this->redirect( $redirect_url, __( 'License key activated successfully', 'tutor-pro' ), 'success' );
						}
						if ( 400 === $response->status ) {
							$this->redirect( $redirect_url, __( 'License key is invalid', 'tutor-pro' ), 'error' );
						}
					} else {
						$this->redirect( $redirect_url, __( 'Something went wrong!!', 'tutor-pro' ), 'error' );
					}
				} catch ( \Throwable $th ) {
					$this->redirect( $redirect_url, __( 'Something went wrong!!', 'tutor-pro' ), 'error' );
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
				$res->name         = $remote->body_response->plugin_name;
				$res->slug         = $this->product_slug;
				$res->version      = $remote->body_response->version;
				$res->last_updated = $remote->body_response->updated_at;
				$res->sections     = array(
					'changelog' => $remote->body_response->change_log,
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
			$base_name    = $this->meta['product_basename'];
			$request_body = $this->check_for_update_api();
			if ( is_object( $request_body ) && property_exists( $request_body, 'status' ) && 200 === $request_body->status ) {
				/**
				 * Prevent Pro updates until the required Free version is installed.
				 *
				 * WordPress.org releases may be rolled out gradually, causing the Pro
				 * update to become available before the corresponding Free update.
				 * Only offer the Pro update when the installed Free version matches
				 * or exceeds the target Pro version.
				 */
				$update_info = array(
					'new_version' => $request_body->body_response->version,
					'package'     => $request_body->body_response->download_url,
					'tested'      => $request_body->body_response->tested_wp_version,
					'slug'        => $base_name,
					'url'         => $request_body->body_response->download_url,
				);

				if ( version_compare( $this->meta['current_version'], $request_body->body_response->version, '<' ) ) {
					$transient->response[ $base_name ] = 'plugin' === $this->meta['product_type'] ? (object) $update_info : $update_info;
				}
			}

			return $transient;
		}

		/**
		 * Get license.
		 *
		 * @param string|null $option_key option key.
		 *
		 * @return mixed
		 */
		public function get_license( $option_key = null ) {
			! $option_key ? $option_key = $this->meta['license_option_key'] : 0;
			$license_option             = get_option( $option_key, null );

			$license = maybe_unserialize( $license_option );
			$license = is_array( $license ) ? $license : array();

			$site_url     = get_site_url();
			$license_info = array();

			$page     = Input::get( 'page' );
			$tab_page = Input::get( 'tab_page' );

			$is_license_page = ( 'tutor_settings' === $page && 'license' === $tab_page ) ? true : false;

			$authorize_token = Input::get( 'authorize_token' );

			if ( $authorize_token ) {
				$this->check_license_key();
			}

			if ( $is_license_page ) {
				if ( ! empty( $license['access_token'] ) ) {
					$end_point = 'verify-license';
				} else {
					$end_point = 'check-license';
				}
				$license_info = get_transient( self::LICENSE_TRANSIENT_KEY );
				if ( false === $license_info ) {
					$api_call    = wp_remote_post(
						$this->api_end_point . $end_point,
						array(
							'body'    => array(
								'license_key' => $license['license_key'] ?? '',
								'website_url' => $site_url,
							),
							'headers' => $this->get_api_header( $license['access_token'] ?? '' ),
						)
					);
					$status_code = wp_remote_retrieve_response_code( $api_call );
					if ( ! is_wp_error( $api_call ) ) {
						$response_body        = $api_call['body'];
						$response             = json_decode( $response_body );
						$license_options_data = get_option( $this->meta['license_option_key'] );
						if ( 200 === $status_code ) {
							if ( $license_options_data ) {
								$license_options_data['activated']     = true;
								$license_options_data['customer_name'] = $response->body_response->customer_name;
								$license_options_data['expires_at']    = $response->body_response->expires_at;
								$license_options_data['activated_at']  = $response->body_response->activated_at;
								$license_options_data['license_type']  = $response->body_response->license_type;
							}
							update_option( $this->meta['license_option_key'], $license_options_data, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
						} elseif ( 401 === $status_code ) {
							if ( property_exists( $response, 'code' ) && 'expired_token' === $response->code ) {
								$refresh_token = $license['refresh_token'] ?? '';
								if ( $refresh_token ) {
									$refreshed_tokens = $this->get_refreshed_tokens( $refresh_token );
									if ( tutor_utils()->count( $refreshed_tokens ) ) {
										$this->update_license_info_by_refreshed_tokens( $refreshed_tokens, $license_info );
										delete_transient( self::LICENSE_TRANSIENT_KEY );
									}
								}
							} else {
								delete_option( $this->meta['license_option_key'] );
								delete_transient( self::LICENSE_TRANSIENT_KEY );
							}
						} else {
							if ( ! empty( $license_options_data['activated'] ) ) {
								$license_options_data['activated'] = false;
							}
							update_option( $this->meta['license_option_key'], $license_options_data, false );
							set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
						}
					}
				}
				$data = get_option( $this->meta['license_option_key'] );
				if ( $data && ! empty( $license_info['activated'] ) ) {
					$data['activated'] = $license_info['activated'];
				}
				update_option( $this->meta['license_option_key'], $data, false );
			}

			$license_option_key = get_option( $this->meta['license_option_key'] );
			if ( $license_option_key ) {
				return get_option( $this->meta['license_option_key'] );
			} else {
				return null;
			}
			return get_option( $this->meta['license_option_key'] );
		}

		/**
		 * Api header description
		 *
		 * @param string $token token description.
		 */
		public function get_api_header( $token = '' ) {
			$headers = array();
			if ( ! empty( $token ) ) {
				$headers = array(
					'Authorization' => 'Bearer ' . $token,
				);
			} else {
				$headers = array(
					'Secret-Key' => self::API_SECRET_KEY,
				);
			}
			return $headers;
		}

		/**
		 * Verify license description
		 *
		 * @param array $license license description.
		 *
		 * @return mixed
		 */
		public function verify_license( $license ) {
			$site_url  = get_site_url();
			$end_point = ! empty( $license['access_token'] ) ? 'verify-license' : 'check-license';

			$api_call = wp_remote_post(
				$this->api_end_point . $end_point,
				array(
					'body'    => array(
						'license_key' => $license['license_key'] ?? '',
						'website_url' => $site_url,
					),
					'headers' => $this->get_api_header( $license['access_token'] ?? '' ),
				)
			);

			$status_code = wp_remote_retrieve_response_code( $api_call );

			if ( ! is_wp_error( $api_call ) ) {
				$response_body = $api_call['body'];
				$response      = json_decode( $response_body );

				if ( 200 === $status_code ) {
					$license_options_data = get_option( $this->meta['license_option_key'] );
					if ( $license_options_data ) {
						$license_options_data['activated']     = true;
						$license_options_data['license_key']   = $license['license_key'] ?? '';
						$license_options_data['customer_name'] = $response->body_response->customer_name;
						$license_options_data['expires_at']    = $response->body_response->expires_at;
						$license_options_data['activated_at']  = $response->body_response->activated_at;
						$license_options_data['license_type']  = $response->body_response->license_type;
					}

					update_option( $this->meta['license_option_key'], $license_options_data, false );
					set_transient( self::LICENSE_TRANSIENT_KEY, $license_options_data, DAY_IN_SECONDS );
				} elseif ( 401 === $status_code ) {
					if ( property_exists( $response, 'code' ) && 'expired_token' === $response->code ) {
						$refresh_token = $license['refresh_token'] ?? '';
						if ( $refresh_token ) {
							$refreshed_tokens = $this->get_refreshed_tokens( $refresh_token );
							if ( tutor_utils()->count( $refreshed_tokens ) ) {
								$this->update_license_info_by_refreshed_tokens( $refreshed_tokens, $license );
								delete_transient( self::LICENSE_TRANSIENT_KEY );
							}
						}
					}
				}

				return $response;
			}
		}

		/**
		 * Check plugin license before update description
		 *
		 * @param bool   $reply    Reply.
		 * @param string $package  Download uri.
		 * @param object $upgrader The WP_Upgrader instance.
		 *
		 * @return  mixed
		 */
		public function check_plugin_license_before_update( $reply, $package, $upgrader ) {
			if ( is_object( $upgrader ) && property_exists( $upgrader->skin, 'plugin_info' ) ) {
				if ( false === strpos( $upgrader->skin->plugin_info['TextDomain'] ?? '', $this->product_slug ) ) {
					return $reply;
				}

				$license_option = get_option( $this->meta['license_option_key'], null );
				if ( ! $license_option ) {
					return new \WP_Error(
						'license_required',
						__( 'A valid license key is required to update Tutor LMS Pro. Please enter your license key in the plugin settings.', 'tutor-pro' )
					);
				}

				$is_active   = $license_option['activated'] ?? '';
				$license_key = $license_option['license_key'] ?? '';
				if ( ! $is_active || ! $license_key ) {
					return new \WP_Error(
						'license_required',
						__( 'A valid license key is required to update Tutor LMS Pro. Please enter your license key in the plugin settings.', 'tutor-pro' )
					);
				}

				$response = $this->check_for_update_api();
				if ( 200 !== $response->status ) {
					return new \WP_Error(
						'license_required',
						__( 'A valid license key is required to update Tutor LMS Pro. Please enter your license key in the plugin settings.', 'tutor-pro' )
					);
				}
			}
			return $reply;
		}

		/**
		 * Adding license tab in the settings page
		 *
		 * @since 3.8.0
		 *
		 * @param array $settings Settings array.
		 *
		 * @return array
		 */
		public function add_license_settings_tab( $settings ) {
			$settings['license'] = array(
				'label'    => __( 'License', 'tutor-pro' ),
				'slug'     => 'license',
				'desc'     => __( 'Setup License', 'tutor-pro' ),
				'template' => 'license',
				'icon'     => 'tutor-icon-key',
				'blocks'   => array(),
			);

			return $settings;
		}

		/**
		 * Include license template
		 *
		 * If the template name is license then load license template
		 *
		 * @since 3.8.0
		 *
		 * @param string $template_path Template path.
		 * @param string $template Template name.
		 *
		 * @return string
		 */
		public function include_license_template( $template_path, $template ) {
			if ( 'license' === $template ) {
				$template_path = __DIR__ . '/license-form.php';
			}

			return $template_path;
		}

		/**
		 * Redirect to URL
		 *
		 * @since 3.8.0
		 *
		 * @param string $url URL.
		 * @param string $flash_message flash message.
		 * @param string $flash_type flash type.
		 *
		 * @return void
		 */
		public function redirect( string $url, $flash_message = null, $flash_type = 'success' ) {
			$url = trim( $url );

			$available_types = array( 'success', 'error' );
			if ( ! empty( $flash_message ) && in_array( $flash_type, $available_types ) ) {
				set_transient( 'tutor_flash_type', $flash_type );
				set_transient( 'tutor_flash_message', $flash_message );
			}

			if ( ! headers_sent() ) {
				wp_safe_redirect( $url );
			} else {
				echo '<script>window.location.href = ' . "'" . esc_url( $url ) . "';" . '</script>';
			}

			exit;
		}

		/**
		 * Get refreshed tokens
		 *
		 * When a access token expires, it needs to renew using the previous refresh token
		 *
		 * @since 4.0.2
		 *
		 * @param string $refresh_token Refresh token.
		 *
		 * @return mixed Array of token [access_token => '', refresh_token => '', tokens_expires_at => '']
		 * returns null if something went wrong.
		 */
		private function get_refreshed_tokens( string $refresh_token = '' ) {
			if ( empty( $refresh_token ) ) {
				return null;
			}

			$post = wp_remote_post(
				$this->api_end_point . self::TOKEN_REFRESH_ENDPOINT,
				array(
					'body'    => array(
						'token'      => $refresh_token,
						'grant_type' => self::GRANT_TYPE_REFRESH,
					),
					'headers' => $this->get_api_header(),
				)
			);

			if ( is_wp_error( $post ) ) {
				return null;
			}

			$body = wp_remote_retrieve_body( $post );
			$body = json_decode( $body );
			if ( json_last_error() ) {
				return null;
			}

			$body_response = $body->body_response;
			if ( 200 === $body->status ) {
				$res = array(
					'access_token'      => $body_response->access_token,
					'refresh_token'     => $body_response->refresh_token,
					'tokens_expires_at' => $body_response->tokens_expires_at,
				);

				return $res;
			}

			return null;
		}

		/**
		 * Update license info using refreshed tokens
		 *
		 * @since 4.0.2
		 *
		 * @param array $refreshed_tokens Refresh tokens.
		 * @param array $license_info Existing license info.
		 *
		 * @return void
		 */
		private function update_license_info_by_refreshed_tokens( $refreshed_tokens, $license_info ): void {
			if ( is_array( $refreshed_tokens ) && $license_info ) {
				$license_info['access_token']      = $refreshed_tokens['access_token'];
				$license_info['refresh_token']     = $refreshed_tokens['refresh_token'];
				$license_info['tokens_expires_at'] = $refreshed_tokens['tokens_expires_at'];

				// Update license info.
				update_option( $this->meta['license_option_key'], $license_info, false );
			}
		}
	}
}
