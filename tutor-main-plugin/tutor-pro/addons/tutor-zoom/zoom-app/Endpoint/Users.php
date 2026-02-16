<?php
/**
 * Zoom API Users
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Endpoint
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 * @copyright  https://github.com/UsabilityDynamics/zoom-api-php-client/blob/master/LICENSE
 */

namespace Zoom\Endpoint;

use Zoom\Interfaces\Request;

/**
 * Class Users
 */
class Users extends Request {

	/**
	 * Users constructor.
	 *
	 * @param $apiKey api key.
	 * @param $apiSecret api secret.
	 */
	public function __construct( $apiKey, $apiSecret ) {
		parent::__construct( $apiKey, $apiSecret );
	}

	/**
	 * List
	 *
	 * @param array $query query.
	 * @return array|mixed
	 */
	public function userlist( array $query = array() ) {
		return $this->get( 'users', $query );
	}

	/**
	 * Create
	 *
	 * @param array|null $data data.
	 * @return array|mixed
	 */
	public function create( array $data = null ) {
		return $this->post( 'users', $data );
	}

	/**
	 * Retrieve
	 *
	 * @param $userID
	 * @param array  $query query.
	 * @return array|mixed
	 */
	public function retrieve( string $userID, array $query = array() ) {
		return $this->get( "users/{$userID}", $query );
	}

	/**
	 * Remove
	 *
	 * @param $userId
	 * @return array|mixed
	 */
	public function remove( string $userId ) {
		return $this->delete( "users/{$userId}" );
	}

	/**
	 * Update
	 *
	 * @param $userId user id.
	 * @param array  $data data.
	 * @return array|mixed
	 */
	public function update( string $userId, array $data = array() ) {
		return $this->patch( "users/{$userId}", $data );
	}
}
