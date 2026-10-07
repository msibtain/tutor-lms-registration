<?php
/**
 * Instructor Signature
 *
 * @package TutorPro/Addons
 * @subpackage Certificate
 * @author: themeum
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_CERT;

use TUTOR\Input;
use TUTOR\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Instructor_Signature
 *
 * @since 1.0.0
 */
class Instructor_Signature {
	/**
	 * File name
	 *
	 * @var string
	 */
	private $file_name_string = 'tutor_pro_custom_signature_file';

	/**
	 * File ID
	 *
	 * @var string
	 */
	private $file_id_string = 'tutor_pro_custom_signature_id';

	/**
	 * Image meta
	 *
	 * @var string
	 */
	private $image_meta = 'tutor_pro_custom_signature_image_id';

	/**
	 * Image post identifier
	 *
	 * @var string
	 */
	private $image_post_identifier = 'tutor_pro_custom_signature_image';

	/**
	 * Constructor
	 *
	 * @param boolean $register_hooks register hooks or not.
	 */
	public function __construct( $register_hooks = true ) {
		if ( $register_hooks ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'tutor_profile_edit_input_after', array( $this, 'custom_signature_field' ) );
			add_action( 'tutor_profile_update_before', array( $this, 'save_custom_signature' ) );
			add_filter( 'tutor_profile_default_values', array( $this, 'add_signature_to_default_values' ), 10, 2 );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Enqueue media scripts only for account settings page.
		if ( tutor_utils()->is_dashboard_page( 'account/settings' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Custom signature field.
	 *
	 * @param object $user user.
	 *
	 * @return void
	 */
	public function custom_signature_field( $user ) {

		if ( ! User::is_instructor_view() ) {
			// It is non instructor user.
			return;
		}

		$signature             = $this->get_instructor_signature( $user->ID );
		$placeholder_signature = tutor_pro()->url . 'addons/tutor-certificate/assets/images/instructor-signature.svg';

		include TUTOR_CERT()->path . '/views/signature-field.php';
	}

	/**
	 * Save custom signature.
	 *
	 * @param int $user_id user id.
	 *
	 * @return void
	 */
	public function save_custom_signature( $user_id ) {
		$media_id = Input::post( $this->file_id_string, '' );

		if ( ! is_numeric( $media_id ) ) {
			// Unlink signature from user meta.
			delete_user_meta( $user_id, $this->image_meta );
		} else {
			update_user_meta( $user_id, $this->image_meta, $media_id );
		}
	}

	/**
	 * Get instructor signature.
	 *
	 * @param int $user_id user id.
	 *
	 * @return array
	 */
	public function get_instructor_signature( $user_id ) {
		// Get personal signature image from user meta.
		$id    = get_user_meta( $user_id, $this->image_meta, true );
		$valid = is_numeric( $id );

		return array(
			'id'  => $valid ? $id : null,
			'url' => $valid ? wp_get_attachment_url( $id ) : null,
		);
	}

	/**
	 * Add signature to default values.
	 *
	 * @since 4.0.0
	 *
	 * @param array    $default_values default values.
	 * @param \WP_User $user user.
	 *
	 * @return array
	 */
	public function add_signature_to_default_values( $default_values, $user ) {
		if ( $user && tutor_utils()->is_instructor( $user->ID, true ) ) {
			$signature = $this->get_instructor_signature( $user->ID );

			if ( ! empty( $signature['id'] ) ) {
				$default_values[ $this->file_id_string ] = array(
					'id'    => $signature['id'],
					'url'   => $signature['url'],
					'title' => __( 'Instructor Signature', 'tutor-pro' ),
				);
			} else {
				$default_values[ $this->file_id_string ] = '';
			}
		}

		return $default_values;
	}
}
