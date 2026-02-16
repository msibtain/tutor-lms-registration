<?php
/**
 * Zoom API Webinars
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
 * Class Webinars
 *
 * @package Zoom\Endpoint
 */
class Webinars extends Request {

	/**
	 * Webinars constructor.
	 *
	 * @param $apiKey
	 * @param $apiSecret
	 */
	public function __construct( $apiKey, $apiSecret ) {
		parent::__construct( $apiKey, $apiSecret );
	}

	/**
	 * List
	 *
	 * @param $userId
	 * @param array  $query
	 * @return array|mixed
	 */
	public function meetings_list( string $userId, array $query = array() ) {
		return $this->get( "users/{$userId}/webinars", $query );
	}

	/**
	 * Create
	 *
	 * @param $userId
	 * @param array  $data
	 * @return array|mixed
	 */
	public function create( string $userId, array $data = null ) {
		return $this->post( "users/{$userId}/webinars", $data );
	}

	/**
	 * Webinar
	 *
	 * @param $webinarId
	 * @return array|mixed
	 */
	public function meeting( string $webinarId ) {
		return $this->get( "webinars/{$webinarId}" );
	}

	/**
	 * Remove
	 *
	 * @param $webinarId
	 * @return array|mixed
	 */
	public function remove( string $webinarId ) {
		return $this->delete( "webinars/{$webinarId}" );
	}

	/**
	 * Update
	 *
	 * @param $webinarId
	 * @param array     $data
	 * @return array|mixed
	 */
	public function update( string $webinarId, array $data = array() ) {
		return $this->patch( "webinars/{$webinarId}", $data );
	}

	/**
	 * Status
	 *
	 * @param $webinarId
	 * @param array     $data
	 * @return mixed
	 */
	public function status( string $webinarId, array $data = array() ) {
		return $this->put( "webinars/{$webinarId}/status", $data );
	}

	/**
	 * List Registrants
	 *
	 * @param $webinarId
	 * @param array     $query
	 * @return array|mixed
	 */
	public function listRegistrants( string $webinarId, array $query = array() ) {
		return $this->get( "webinars/{$webinarId}/registrants", $query );
	}

	/**
	 * Add Registrant
	 *
	 * @param $webinarId
	 * @param array     $data
	 * @return array|mixed
	 */
	public function addRegistrant( string $webinarId, $data = array() ) {
		return $this->post( "webinars/{$webinarId}/registrants", $data );
	}

	/**
	 * Update Registrant Status
	 *
	 * @param $webinarId
	 * @param array     $data
	 * @return array|mixed
	 */
	public function updateRegistrantStatus( string $webinarId, array $data = array() ) {
		return $this->put( "webinars/{$webinarId}/registrants/status", $data );
	}

	/**
	 * Past Webinars
	 *
	 * @param $webinarId
	 * @return array|mixed
	 */
	public function pastMeeting( string $webinarId ) {
		return $this->get( "past_webinars/{$webinarId}" );
	}

}
