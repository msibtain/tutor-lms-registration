<?php
/**
 * Buddypress Messages
 *
 * @package TutorPro\Addons
 * @subpackage Buddypress
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.3.5
 */

namespace TUTOR_BP;

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BuddyPressMessages
 */
class BuddyPressMessages {

	/**
	 * Register hooks
	 */
	public function __construct() {
		/**
		 * BuddyPress Message Header
		 */
		// This hook will be worked on later.
		// add_action( 'bp_before_message_thread_content', array( $this, 'bp_before_message_thread_content' ), 99 );.
		add_action( 'wp_ajax_tutor_bp_retrieve_user_records_for_thread', array( $this, 'tutor_bp_retrieve_user_records_for_thread' ) );
	}

	/**
	 * BuddyPress Message Thread Header
	 *
	 * @since v.1.5.0
	 */
	public function bp_before_message_thread_content() {
		$thread_id = (int) bp_action_variable( 0 );

		if ( $thread_id ) {
			echo '<div id="tutor-bp-thread-wrap">';
			echo wp_kses_post( $this->generate_before_message_thread( $thread_id ) );
			echo '</div>';
		}
	}

	/**
	 * Generate before message thread
	 *
	 * @param integer $message_thread_id message thread id.
	 *
	 * @return mixed
	 */
	public function generate_before_message_thread( $message_thread_id = 0 ) {
		if ( $message_thread_id ) {
			$recipients      = \BP_Messages_Thread::get_recipients_for_thread( $message_thread_id );
			$current_user_id = get_current_user_id();
			if ( isset( $recipients[ $current_user_id ] ) ) {
				unset( $recipients[ $current_user_id ] );
			}

			if ( tutor_utils()->count( $recipients ) ) {
				ob_start();
				tutor_load_template( 'buddypress.message_thread_recipients', compact( 'recipients' ), true );
				return ob_get_clean();
			}
		}
		return '';
	}

	/**
	 * Receive user records for thread.
	 *
	 * @return void
	 */
	public function tutor_bp_retrieve_user_records_for_thread() {
		tutor_utils()->checking_nonce();

		$thread_id = Input::post( 'thread_id', 0, Input::TYPE_INT );
		if ( $thread_id ) {
			wp_send_json_success( array( 'thread_head_html' => $this->generate_before_message_thread( $thread_id ) ) );
		}
		wp_send_json_error();
	}

}
