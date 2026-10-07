<?php
/**
 * Manage Google Event operations
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\GoogleEvent
 */

namespace TutorPro\GoogleMeet\GoogleEvent;

use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use Tutor\Traits\JsonResponse;
use TUTOR_PRO\Dashboard;
use TutorPro\GoogleMeet\GoogleMeet;
use TutorPro\GoogleMeet\Utilities\Utilities;
use TutorPro\GoogleMeet\Validator\Validator;

/**
 * Manage google events
 */
class GoogleEvent {

	use JsonResponse;

	/**
	 * Credential filename.
	 *
	 * @var string
	 */
	private $credential_filename = null;

	/**
	 * API credential path
	 *
	 * @var string
	 */
	private $credential_path = null;

	/**
	 * Access token path
	 *
	 * @var string
	 */
	private $token_path = null;

	/**
	 * Tutor JSON directory.
	 *
	 * @var string
	 */
	private $tutor_json_dir;

	/**
	 * Access token path
	 *
	 * @var string
	 */
	public $upload_dir = null;

	/**
	 * Init google service
	 *
	 * @var mixed
	 */
	public $service;

	/**
	 * Authorized client
	 *
	 * @var mixed
	 */
	public $client;

	/**
	 * App name
	 *
	 * @var string
	 */
	private $app_name;

	/**
	 * Redirect URI
	 *
	 * @var array
	 */
	public $google_callback_url;

	/**
	 * Set current username
	 *
	 * @var string
	 */
	public $username;

	/**
	 * Scopes required to make API request
	 *
	 * @var array
	 */
	private $required_scopes = array(
		\Google_Service_Calendar::CALENDAR,
		\Google_Service_Calendar::CALENDAR_EVENTS,
	);

	/**
	 * Current calendar type
	 *
	 * @var string
	 */
	public $current_calendar;

	/**
	 * Init props & resolve dependencies
	 *
	 * @since v2.1.0
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_google_callback' ), 99 );

		$owner_id               = null;
		$this->current_calendar = 'primary';
		$this->tutor_json_dir   = 'tutor-json';
		$this->upload_dir       = trailingslashit( wp_upload_dir()['basedir'] ) . trailingslashit( $this->tutor_json_dir );

		wp_mkdir_p( $this->upload_dir );

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include ABSPATH . 'wp-includes/pluggable.php';
		}

		$this->username            = md5( \wp_get_current_user()->user_login );
		$this->credential_filename = "{$this->username}-credential.json";

		$credential_path = $this->upload_dir . $this->credential_filename;
		$token_path      = $this->upload_dir . "{$this->username}-token.json";

		// Create index.php file to restrict direct access.
		if ( ! file_exists( $this->upload_dir . 'index.php' ) ) {
			file_put_contents( $this->upload_dir . 'index.php', '<?php //silence is golden' );
		}

		if ( file_exists( $credential_path ) ) {
			$this->credential_path = $credential_path;
		}
		if ( file_exists( $token_path ) ) {
			$this->token_path = $token_path;
		}

		$this->google_callback_url = array( admin_url() . 'admin.php?page=google-meet&tab=set-api' );

		if ( ! is_admin() ) {
			global $wp_rewrite;

			if ( null === $wp_rewrite ) {
				$wp_rewrite = new \WP_Rewrite();
			}

			$this->google_callback_url = array(
				tutor_utils()->tutor_dashboard_url( 'google-meet/set-api' ),
				UrlHelper::add_query_params(
					tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU ),
					array(
						'nav' => Utilities::GOOGLE_MEET_TAB,
						'tab' => 'set-api',
					)
				),
			);
		}

		if ( $this->is_credential_loaded() ) {
			try {
				$this->validate_json_service_account_file( $credential_path );

				$data          = file_get_contents( $credential_path );
				$json          = json_decode( $data, true );
				$web           = $json['web'] ?? array();
				$redirect_uris = $web['redirect_uris'] ?? array();

				$has_redirect_uri = array_intersect( $this->google_callback_url, $redirect_uris );
				$has_redirect_uri = array_values( $has_redirect_uri );

				$this->client = new \Google_Client();
				$this->client->setApplicationName( $this->app_name );
				$this->client->setAuthConfig( $this->credential_path );
				$this->client->setRedirectUri( $has_redirect_uri[0] ?? $this->google_callback_url[0] );
				$this->client->addScope( $this->required_scopes );
				$this->client->setAccessType( 'offline' );
				$this->client->setApprovalPrompt( 'force' );
				$assigned = ! ( $this->assign_token_to_client() === false );

				if ( $assigned ) {
					// Create service if the token assigned.
					$this->service = new \Google_Service_Calendar( $this->client );
				}
			} catch ( \Throwable $th ) {
				if ( file_exists( $this->credential_path ) ) {
					unlink( $this->credential_path );
				}

				if ( is_admin() ) {
					add_action(
						'admin_notices',
						function () use ( $th ) {
							printf(
								'<div class="%1$s"><p>%2$s</p></div>',
								esc_attr( 'notice notice-error is-dismissible' ),
								esc_html( $th->getMessage() )
							);
						}
					);
				}
			}
		}

		add_action( 'wp_ajax_tutor_pro_google_meet_credential_upload', array( $this, 'upload_credentials' ) );
	}

	/**
	 * Handle google redirect uri after getting token.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function handle_google_callback() {
		$url       = get_pagenum_link( 1, false );
		$match_url = explode( '?', $url )[0];
		if ( ! in_array( rtrim( $match_url, '/' ), $this->google_callback_url, true ) ) {
			return;
		}
		$code = Input::get( 'code', '' );

		if ( empty( $code ) ) {
			return;
		}

		$call_back_url = UrlHelper::add_query_params(
			tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU ),
			array(
				'nav' => Utilities::GOOGLE_MEET_TAB,
				'tab' => 'set-api',
			)
		);

		$call_back_url = UrlHelper::add_query_params( $call_back_url, array( 'code' => $code ) );
		wp_safe_redirect( $call_back_url );

		exit;
	}

	/**
	 * Check valid service account JSON config file
	 *
	 * @param string $file_path file path.
	 * @return boolean
	 * @throws \Exception If invalid file or file does not exist.
	 *
	 * @since 2.1.3
	 */
	public function validate_json_service_account_file( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			throw new \Exception( __( 'File does not exist', 'tutor-pro' ) );
		}

		$data = file_get_contents( $file_path );
		$json = json_decode( $data );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception( __( 'Invalid JSON file', 'tutor-pro' ) );
		}

		if ( ! isset( $json->web ) ) {
			throw new \Exception( __( 'Invalid config file', 'tutor-pro' ) );
		}

		$required_key = array(
			'client_id',
			'client_secret',
			'project_id',
			'auth_uri',
			'token_uri',
		);

		$config_arr = (array) $json->web;

		foreach ( $required_key as $key ) {
			if ( ! array_key_exists( $key, $config_arr ) ) {
				throw new \Exception( $key . __( ' does not exist in your JSON file', 'tutor-pro' ) );
			}
		}

		return true;
	}

	/**
	 * Return consent screen url
	 *
	 * @since v2.1.0
	 *
	 * @return string  consent screen URL
	 */
	public function get_consent_screen_url() {
		return $this->client->createAuthUrl();
	}

	/**
	 * Filter upload directory.
	 *
	 * @since 3.0.0
	 *
	 * @param array $param param.
	 *
	 * @return array.
	 */
	public function filter_upload_dir( $param ) {
		$param['path'] = \trailingslashit( $param['basedir'] ) . $this->tutor_json_dir;
		$param['url']  = \trailingslashit( $param['baseurl'] ) . $this->tutor_json_dir;
		return $param;
	}

	/**
	 * Save JSON credentials
	 *
	 * @since 3.0.0 used wp_handle_upload function instead of move_uploaded_file.
	 * @since 2.1.0
	 *
	 * @param string $file  $_FILES.
	 *
	 * @return void
	 */
	public function upload_credentials( $file ) {
		if ( ! Validator::current_user_has_access() ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		$credential_path = $this->upload_dir . $this->credential_filename;

		try {
			if ( isset( $_FILES['file'] ) && isset( $_FILES['file']['tmp_name'] ) ) {
				if ( ! is_writable( $this->upload_dir ) ) {
					$this->response_bad_request( __( 'Upload directory is not writable', 'tutor-pro' ) );
				}

				$filetype = wp_check_filetype( $_FILES['file']['name'], array( 'json' => 'application/json' ) );
				if ( false === $filetype['ext'] ) {
					$this->response_bad_request( __( 'Invalid file type selected', 'tutor-pro' ) );
				}

				$overrides = array(
					'test_form'                => false,
					'test_type'                => false,
					'unique_filename_callback' => fn() => $this->credential_filename,
				);

				add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), PHP_INT_MAX );
				$upload = wp_handle_upload( $_FILES['file'], $overrides ); //phpcs:ignore
				remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), PHP_INT_MAX );

				if ( $upload && ! isset( $upload['error'] ) ) {
					$this->response_success( __( 'Credential uploaded successfully!', 'tutor-pro' ) );
				} else {
					$this->response_bad_request( $upload['error'] ?? __( 'Credential upload failed, please try again!', 'tutor-pro' ) );
				}
			}
		} catch ( \Throwable $th ) {

			if ( file_exists( $credential_path ) ) {
				unlink( $credential_path );
			}

			$this->response_bad_request( $th->getMessage() );
		}
	}

	/**
	 * Check if credentials available
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public function is_credential_loaded() {
		return file_exists( $this->upload_dir . "{$this->username}-credential.json" );
	}

	/**
	 * Assign the existing token, or try to refresh if expired
	 *
	 * @since v2.1.0
	 *
	 * @return mixed
	 */
	public function assign_token_to_client() {
		try {
			if ( isset( $this->token_path ) && file_exists( $this->token_path ) ) {
				$access_token = json_decode( file_get_contents( $this->token_path ), true );
				$this->client->setAccessToken( $access_token );
			}
			// Check if token expired.
			if ( $this->client->isAccessTokenExpired() ) {
				$refresh_token = $this->client->getRefreshToken();

				if ( ! $refresh_token ) {
					return false;
				}

				$new_token = null;

				try {
					$new_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
				} catch ( \Exception $e ) {
					if ( $e ) {
						return false;
					}
				}
				return $this->save_token( null, $new_token );
			}
		} catch ( \Throwable $th ) {
			return false;
		}
	}


	/**
	 * Save token provided by google
	 *
	 * @since v2.1.0
	 *
	 * @param mixed $code  google after authenticated.
	 * @param mixed $token access token.
	 */
	public function save_token( $code = null, $token = null ) {
		if ( Validator::current_user_has_access() ) {
			try {
				if ( ! $token ) {
					$token = $this->client->fetchAccessTokenWithAuthCode( $code );
					$this->client->setAccessToken( $token );
					$token = $this->client->getAccessToken();
				}
				file_put_contents( $this->upload_dir . "{$this->username}-token.json", json_encode( $token ) );
			} catch ( \Throwable $th ) {
				return false;
			}
		}
		return false;
	}


	/**
	 * Check if the app is permitted by user via consent screen
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public function is_app_permitted() {
		if ( is_null( $this->credential_path ) || is_null( $this->token_path ) ) {
			return false;
		}
		return $this->assign_token_to_client() === false ? false : true;
	}
}
