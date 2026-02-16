<?php
/**
 * Tutor pro ecommerce gateway config
 *
 * Extends Tutor settings for payment configuration
 *
 * @package TutorPro\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPro\Ecommerce;

use Tutor\Ecommerce\OptionKeys;
use Tutor\Helpers\HttpHelper;
use Tutor\Traits\JsonResponse;

/**
 * Init payment gateway init class
 */
class Config {

	use JsonResponse;

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'tutor_payment_gateways', array( $this, 'extend_tutor_gateways' ) );
	}

	/**
	 * Get available payment gateways
	 *
	 * @since 1.0.0
	 *
	 * @throws \Throwable Throw throwable if exception occur.
	 *
	 * @return array
	 */
	public static function get_payment_gateways(): array {
		try {
			$gateways = self::fetch_available_gateways();
			$gateways = (array) $gateways;

			$pro_gateways = array();

			foreach ( $gateways as $key => $gateway ) {
				$gateway_arr = (array) $gateway;
				$basename    = "tutor-$key/tutor-{$key}.php";

				$gateway_arr['name']           = $key;
				$gateway_arr['is_installed']   = self::is_installed( $basename );
				$gateway_arr['is_active']      = self::is_active( $key );
				$gateway_arr['icon']           = $gateway->icon;
				$gateway_arr['latest_version'] = $gateway->latest_version;

				// Override.
				$gateway_arr['support_subscription'] = 'Yes' === $gateway->support_subscription;
				$gateway_arr['is_installable']       = 'Yes' === $gateway->is_installable;
				$gateway_arr['fields']               = self::get_config_fields( $key );

				$pro_gateways[ $key ] = $gateway_arr + self::get_plugin_info( $basename, $gateway->latest_version );
			}

			return apply_filters( 'tutor_pro_payment_gateways', $pro_gateways );
		} catch ( \Throwable $th ) {
			throw $th;
		}
	}

	/**
	 * Fetch available payment gateways
	 *
	 * @since 3.0.0
	 *
	 * @throws \Exception Throw exception if any error found.
	 *
	 * @return array
	 */
	private static function fetch_available_gateways() {
		$domain       = site_url();
		$license_info = get_option( 'tutor_license_info' );
		$license_key  = $license_info ? $license_info['license_key'] : '';

		$data = array(
			'domain'      => $domain,
			'license_key' => $license_key,
		);

		if ( tutor_is_dev_mode() ) {
			$api_url = 'https://tutor.prismbuilder.com/wp-json/themeum-products/v1/tutor/payment-gateways';
		} else {
			$api_url = 'https://tutorlms.com/wp-json/themeum-products/v1/tutor/payment-gateways';
		}

		$remote_post = HttpHelper::post( $api_url, $data );
		if ( is_wp_error( $remote_post ) ) {
			throw new \Exception( $remote_post->get_error_message() );
		} else {
			$status_code = $remote_post->get_status_code();
			$res_body    = json_decode( stripslashes( $remote_post->get_body() ) );
			if ( 200 === $status_code ) {
				return $res_body->body_response;
			} else {
				throw new \Exception( $res_body->response ?? 'Failed to fetch payment gateways' );
			}
		}
	}

	/**
	 * Check whether a payment gateway is active
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway Gateway key name.
	 *
	 * @see get_payment_gateways method
	 *
	 * @return boolean
	 */
	public static function is_active( string $gateway ) : bool {
		$payments = tutor_utils()->get_option( OptionKeys::PAYMENT_SETTINGS );
		$payments = json_decode( stripslashes( $payments ) );

		if ( $payments ) {
			foreach ( $payments->payment_methods as $method ) {
				if ( $method->name === $gateway ) {
					return (bool) $method->is_active;
				}
			}
		}

		return false;
	}

	/**
	 * Check whether a payment gateway is connected
	 *
	 * This method will return true if package available
	 * in the source codebase: ecommerce/PaymentGateways/Package/Package.php
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway Gateway label name. This name should
	 * be same as package dir & main file name. For ex: AliPay, Eway etc.
	 *
	 * @see get_payment_gateways method
	 *
	 * @return boolean
	 */
	public static function is_installed( string $plugin_basename ) : bool {
		return file_exists( trailingslashit( WP_PLUGIN_DIR ) . $plugin_basename );
	}

	/**
	 * Construct config fields based on gateway
	 *
	 * This method will return an array of config fields based on the provided gateway key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $gateway Gateway name.
	 *
	 * @return array
	 */
	public static function get_config_fields( string $gateway ): array {
		$config_keys_method = 'get_' . $gateway . '_config_keys';

		if ( ! method_exists( __CLASS__, $config_keys_method ) ) {
			return array();
		}

		$config_keys   = self::$config_keys_method();
		$config_fields = array();

		foreach ( $config_keys as $key => $type ) {
			if ( 'environment' === $type ) {
				$config_fields[] = array(
					'name'    => $key,
					'type'    => 'select',
					'options' => self::get_payment_environments(),
					'label'   => __( ucfirst( str_replace( '_', ' ', $key ) ), 'tutor-pro' ),
					'value'   => 'test',
				);
			} elseif ( 'alipay_region' === $type ) {
				$config_fields[] = array(
					'name'    => $key,
					'type'    => 'select',
					'options' => array(
						'na'   => __( 'North America', 'tutor-pro' ),
						'asia' => __( 'Asia', 'tutor-pro' ),
					),
					'label'   => __( ucfirst( str_replace( '_', ' ', $key ) ), 'tutor-pro' ),
					'value'   => 'na',
				);
			} else {
				$config_fields[] = array(
					'name'  => $key,
					'type'  => $type,
					'label' => __( ucfirst( str_replace( '_', ' ', $key ) ), 'tutor-pro' ),
					'value' => '',
				);
			}
		}

		return $config_fields;
	}

	/**
	 * Get payment environments
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_payment_environments() {
		return array(
			'test' => __( 'Test', 'tutor' ),
			'live' => __( 'Live', 'tutor' ),
		);
	}

	/**
	 * Get Razorpay config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_razorpay_config_keys() {
		return array(
			'environment'    => 'environment',
			'key_id'         => 'secret_key',
			'key_secret'     => 'secret_key',
			'webhook_secret' => 'secret_key',
		);
	}

	/**
	 * Get AmazonPay config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_amazonpay_config_keys() {
		return array(
			'environment'   => 'environment',
			'store_id'      => 'secret_key',
			'merchant_id'   => 'secret_key',
			'public_key_id' => 'secret_key',
		);
	}

	/**
	 * Get Paddle config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_paddle_config_keys() {
		return array(
			'environment' => 'environment',
			'vendor_id'   => 'secret_key',
			'auth_code'   => 'secret_key',
			'public_key'  => 'textarea',
		);
	}


	/**
	 * Get PayStack config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_paystack_config_keys() {
		return array(
			'environment' => 'environment',
			'secret_key'  => 'secret_key',
		);
	}

	/**
	 * Get Redsys config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_redsys_config_keys() {
		return array(
			'environment'   => 'environment',
			'merchant_code' => 'secret_key',
			'trade_key'     => 'secret_key',
			'terminal'      => 'secret_key',
		);
	}

	/**
	 * Get Authorize.Net config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_authorize_net_config_keys() {
		return array(
			'environment'     => 'environment',
			'login_id'        => 'secret_key',
			'transaction_key' => 'secret_key',
			'signature_key'   => 'secret_key',
		);
	}

	/**
	 * Get Moneris config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_moneris_config_keys() {
		return array(
			'environment' => 'environment',
			'store_id'    => 'secret_key',
			'api_token'   => 'secret_key',
			'checkout_id' => 'secret_key',
		);
	}

	/**
	 * Get PayFast config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_payfast_config_keys() {
		return array(
			'environment'  => 'environment',
			'merchant_id'  => 'secret_key',
			'merchant_key' => 'secret_key',
			'pass_phrase'  => 'secret_key',
		);
	}

	/**
	 * Get Mollie config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_mollie_config_keys() {
		return array(
			'environment' => 'environment',
			'api_key'     => 'secret_key',
		);
	}

	/**
	 * Get Klarna config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_klarna_config_keys() {
		return array(
			'environment' => 'environment',
			'username'    => 'secret_key',
			'password'    => 'secret_key',
		);
	}

	/**
	 * Get QuickPay config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_quickpay_config_keys() {
		return array(
			'environment' => 'environment',
			'api_key'     => 'secret_key',
			'private_key' => 'secret_key',
		);
	}

	/**
	 * Get AliPay config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_alipay_config_keys() {
		return array(
			'environment' => 'environment',
			'client_id'   => 'secret_key',
			'private_key' => 'secret_key',
			'public_key'  => 'secret_key',
			'region'      => 'alipay_region',
		);
	}

	/**
	 * Get Eway config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_eway_config_keys() {
		return array(
			'environment'  => 'environment',
			'api_key'      => 'secret_key',
			'api_password' => 'secret_key',
		);
	}

	/**
	 * Get stripe config keys
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_stripe_config_keys() {
		return array(
			'environment'           => 'environment',
			'public_key'            => 'secret_key',
			'secret_key'            => 'secret_key',
			'webhook_signature_key' => 'secret_key',
			'webhook_url'           => 'webhook_url',
		);
	}

	/**
	 * Get plugin info
	 *
	 * @since 3.0.0
	 *
	 * @param string $gateway_basename Gateway slug.
	 * @param mixed  $latest_version Latest version.
	 *
	 * @return array
	 */
	public static function get_plugin_info( string $gateway_basename, $latest_version ) {
		// Ensure the WordPress functions are loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$plugin_info = array(
			'active_version'   => null,
			'update_available' => false,
		);

		if ( in_array( $gateway_basename, array_keys( $all_plugins ) ) ) {
			$details = $all_plugins[ $gateway_basename ];

			$plugin_info['active_version'] = $details['Version'];

			// Compare the active version with the latest version.
			if ( $latest_version && version_compare( $details['Version'], $latest_version, '<' ) ) {
				$plugin_info['update_available'] = true;
			}
		}

		return $plugin_info;
	}

	/**
	 * Add pro payment gateways with default
	 *
	 * @since 3.0.0
	 *
	 * @param array $payment_gateways Tutor default payment gateways.
	 *
	 * @return array
	 */
	public function extend_tutor_gateways( $payment_gateways ) {
		try {
			$gateways = self::get_payment_gateways();
			foreach ( $gateways as $gateway ) {
				$payment_gateways[] = $gateway;
			}
		} catch ( \Throwable $th ) {
			tutor_log( $th );
		}

		return $payment_gateways;
	}
}

