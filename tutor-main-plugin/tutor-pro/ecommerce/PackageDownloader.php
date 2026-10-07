<?php
/**
 * Package Downloader
 *
 * @package TutorPro\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPro\Ecommerce;

use TUTOR\Input;
use Tutor\Helpers\HttpHelper;
use Tutor\Traits\JsonResponse;
use Tutor\Helpers\PluginInstaller;

/**
 * Handle payment gateway install/remove
 */
class PackageDownloader {

	use JsonResponse;

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_tutor_install_payment_gateway', array( $this, 'ajax_install_payment_gateway' ) );
		add_action( 'wp_ajax_tutor_remove_payment_gateway', array( $this, 'ajax_remove_payment_gateway' ) );
	}

	/**
	 * Handle ajax request for downloading a gateway
	 *
	 * @since 1.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_install_payment_gateway() {
		tutor_utils()->checking_nonce();
		tutor_utils()->check_current_user_capability();

		$message         = '';
		$success         = true;
		$default_err_msg = __( 'Payment gateway download failed', 'tutor-pro' );

		$slug        = Input::post( 'slug' );
		$action_type = Input::post( 'action_type' );
		$domain      = site_url();
		$license_key = get_option( 'tutor_license_info' );
		$license_key = $license_key ? $license_key['license_key'] : '';

		if ( tutor_is_dev_mode() ) {
			$api_url = 'https://tutor.prismbuilder.com/wp-json/themeum-products/v1/tutor/payment-gateways/install';
		} else {
			$api_url = 'https://tutorlms.com/wp-json/themeum-products/v1/tutor/payment-gateways/install';
		}

		if ( ! empty( $slug ) ) {
			try {
				$basename = "tutor-{$slug}/tutor-{$slug}.php";

				if ( file_exists( WP_PLUGIN_DIR . '/' . $basename && 'upgrade' !== $action_type ) ) {
					if ( ! is_plugin_active( $basename ) ) {
						$response = activate_plugin( $basename );
						if ( is_wp_error( $response ) ) {
							$success = false;
							$message = $response->get_error_message();
						} else {
							$message = __( 'Payment gateway activated successfully', 'tutor-pro' );
						}
					} else {
						$message = __( 'Payment gateway already activated', 'tutor-pro' );
					}
				} else {
					$args = array(
						'slug'        => $slug,
						'domain'      => $domain,
						'license_key' => $license_key,
					);

					$remote_post = HttpHelper::post( $api_url, $args );
					if ( ! is_wp_error( $remote_post ) ) {
						$status_code = $remote_post->get_status_code();
						$res_body    = json_decode( stripslashes( $remote_post->get_body() ) );
						if ( 200 === $status_code ) {
							$url = $res_body->body_response;
							try {
								$install = PluginInstaller::install_or_upgrade_plugin( $url, $action_type ? $basename : null );
								if ( $install ) {
									$message = __( 'Payment gateway installed successfully', 'tutor-pro' );
								} else {
									$success = false;
									$message = $default_err_msg;
								}
							} catch ( \Throwable $th ) {
								$success = false;
								$message = $th->getMessage();
							}
						} else {
							$success = false;
							$message = $res_body->response ?? $default_err_msg;
						}
					} else {
						$success = false;
						$message = $remote_post->get_error_message();
					}
				}
			} catch ( \Throwable $th ) {
				$success = false;
				$message = $th->getMessage();
			}
		} else {
			$success = false;
			$message = __( 'Payment gateway slug is required.', 'tutor-pro' );
		}

		if ( $success ) {
			$this->json_response(
				$message
			);
		} else {
			$this->json_response(
				$message,
				'',
				HttpHelper::STATUS_BAD_REQUEST
			);
		}
	}


	/**
	 * Ajax handler to remove a installed payment gateway
	 *
	 * @since 3.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_remove_payment_gateway() {
		tutor_utils()->checking_nonce();
		tutor_utils()->check_current_user_capability();

		$slug = Input::post( 'slug' );
		if ( ! $slug ) {
			$this->json_response(
				__( 'Payment gateway slug is required.', 'tutor-pro' ),
				'',
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		// Ensure the necessary WordPress functions are loaded.
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Get the plugin path using the slug.
		$plugin_path = "tutor-$slug/tutor-$slug.php";

		deactivate_plugins( $plugin_path );

		$result = delete_plugins( array( $plugin_path ) );
		if ( is_wp_error( $result ) ) {
			$this->json_response(
				__( 'Failed', 'tutor-pro' ),
				$result->get_error_message(),
				HttpHelper::STATUS_INTERNAL_SERVER_ERROR
			);
		} else {
			$this->json_response(
				__( 'Payment gateway successfully removed!', 'tutor-pro' )
			);
		}
	}
}
