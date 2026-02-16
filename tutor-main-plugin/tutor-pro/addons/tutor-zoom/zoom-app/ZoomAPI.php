<?php
/**
 * Zoom API
 *
 * @package TutorPro\Addons
 * @subpackage Zoom
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace Zoom;

use Zoom\Endpoint\Users;

/**
 * Class ZoomAPI
 */
class ZoomAPI {

	/**
	 * API key
	 *
	 * @var null
	 */
	private $apiKey = null;

	/**
	 * API secret
	 *
	 * @var null
	 */
	private $apiSecret = null;

	/**
	 * Users
	 *
	 * @var null
	 */
	private $users = null;


	/**
	 * Get instance
	 *
	 * @return Users|null
	 */
	public function getInstance() {
		static $users = null;
		if ( null === $users ) {
			$this->users = new Users( $this->apiKey, $this->apiSecret );
		}

		return $users;
	}

	/**
	 * Zoom constructor.
	 *
	 * @param $apiKey api key.
	 * @param $apiSecret apiSecret.
	 *
	 * @return void
	 */
	public function __construct( $apiKey, $apiSecret ) {
		$this->apiKey = $apiKey;

		$this->apiSecret = $apiSecret;

		$this->getInstance();
	}

	/**
	 * Create user.
	 *
	 * @param array $user_data user data.
	 *
	 * @return mixed
	 */
	public function createUser( $user_data = array() ) {
		$createAUserArray['action']    = 'create';
		$createAUserArray['email']     = sanitize_text_field( wp_unslash( $_POST['email'] ) );//phpcs:ignore
		$createAUserArray['user_info'] = $user_data;

		return $this->users->create( $createAUserArray );
	}
}



