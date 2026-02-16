<?php
/**
 * Zoom API Recordings
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Endpoint
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace Zoom\Endpoint;

use Zoom\Interfaces\Request;

/**
 * Class Recordings
 *
 * @package Zoom\Endpoint
 */
class Recordings extends Request {

	/**
	 * Recordings constructor.
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
	public function listAll( string $userId, array $query = array() ) {
		return $this->get( "users/{$userId}/recordings", $query );
	}

	/**
	 * Meeting
	 *
	 * @param $meetingId
	 * @return array|mixed
	 */
	public function meeting( string $meetingId ) {
		return $this->get( "meetings/{$meetingId}/recordings" );
	}
	public function download( string $meetingId ) {

		return $this->get( $meetingId );
	}

	/**
	 * Remove All
	 *
	 * @param $meetingId
	 * @param array     $query
	 * @return array|mixed
	 */
	public function removeAll( string $meetingId, array $query = array( 'action' => 'trash' ) ) {
		return $this->delete( "meetings/{$meetingId}/recordings", $query );
	}

	/**
	 * Remove
	 *
	 * @param $meetingId
	 * @param $recordingId
	 * @param array       $query
	 * @return array|mixed
	 */
	public function remove( string $meetingId, string $recordingId, array $query = array( 'action' => 'trash' ) ) {
		return $this->delete( "meetings/{$meetingId}/recordings/{$recordingId}", $query );
	}

	/**
	 * Recover All
	 *
	 * @param $meetingId
	 * @param array     $data
	 * @return array|mixed
	 */
	public function recoverAll( string $meetingId, array $data = array( 'action' => 'recover' ) ) {
		return $this->put( "meetings/{$meetingId}/recordings/status", $data );
	}

	/**
	 * Recover
	 *
	 * @param $meetingId
	 * @param $recordingId
	 * @param array       $data
	 * @return array|mixed
	 */
	public function recover( string $meetingId, string $recordingId, array $data = array( 'action' => 'recover' ) ) {
		return $this->put( "meetings/{$meetingId}/recordings/{$recordingId}/status", $data );
	}

}
