<?php
/**
 * Zoom API Reports
 *
 * @package TutorPro\Addons
 * @subpackage Zoom\Endpoint
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

/**
 * @copyright  https://github.com/UsabilityDynamics/zoom-api-php-client/blob/master/LICENSE
 */
namespace Zoom\Endpoint;

use Zoom\Interfaces\Request;

/**
 * Class Reports
 *
 * @package Zoom\Interfaces
 */
class Reports extends Request {

	/**
	 * Meetings constructor.
	 *
	 * @param $apiKey
	 * @param $apiSecret
	 */
	public function __construct( $apiKey, $apiSecret ) {
		parent::__construct( $apiKey, $apiSecret );
	}

	/**
	 * Meeting Participants
	 *
	 * @param $meetingUUID
	 * @param array       $query
	 * @return array|mixed
	 */
	public function meetingParticipants( string $meetingUUID, array $query = array() ) {
		return $this->get( "report/meetings/{$meetingUUID}/participants", $query );
	}

	public function dailyReports( array $query = array() ) {
		return $this->get( 'report/daily/', $query );
	}

}
