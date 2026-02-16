<?php
/**
 * Plugin Name: Tutor LMS Registration
 * Plugin URI: https://github.com/your-repo/tutor-lms-registration
 * Description: Custom user registration shortcode that creates WordPress users with Subscriber role and Tutor LMS Instructor capabilities.
 * Version: 1.0.0
 * Author: Sib
 * Author URI: https://innovisionlab.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tutor-lms-registration
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: tutor
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TLR_VERSION', '1.0.0' );
define( 'TLR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TLR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
class Tutor_LMS_Registration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'tutor_registration', array( $this, 'render_registration_form' ) );
		add_shortcode( 'student_registration', array( $this, 'render_student_registration_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Check if Tutor LMS is active.
	 *
	 * @return bool
	 */
	public function is_tutor_active() {
		return function_exists( 'tutor' ) && defined( 'TUTOR_VERSION' );
	}

	/**
	 * Enqueue CSS for the registration form.
	 */
	public function enqueue_assets() {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}
		if ( ! has_shortcode( $post->post_content, 'tutor_registration' ) && ! has_shortcode( $post->post_content, 'student_registration' ) ) {
			return;
		}

		wp_enqueue_style(
			'tlr-registration-form',
			TLR_PLUGIN_URL . 'assets/css/registration-form.css',
			array(),
			TLR_VERSION
		);
	}

	/**
	 * Render the registration form shortcode.
	 *
	 * @return string
	 */
	public function render_registration_form() {
		if ( is_user_logged_in() ) {
			return sprintf(
				'<p class="tlr-message tlr-info">%s</p>',
				esc_html__( 'You are already logged in.', 'tutor-lms-registration' )
			);
		}

		if ( ! $this->is_tutor_active() ) {
			return sprintf(
				'<p class="tlr-message tlr-error">%s</p>',
				esc_html__( 'Tutor LMS plugin is required for this registration form.', 'tutor-lms-registration' )
			);
		}

		$output = '';

		if ( isset( $_GET['tlr_registered'] ) && $_GET['tlr_registered'] === '1' ) {
			$output .= sprintf(
				'<p class="tlr-message tlr-success">%s</p>',
				esc_html__( 'Registration successful! You can now log in.', 'tutor-lms-registration' )
			);
		}

		if ( isset( $_GET['tlr_error'] ) ) {
			$error_code = sanitize_text_field( wp_unslash( $_GET['tlr_error'] ) );
			$messages   = $this->get_error_messages();
			$message    = isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : $messages['generic'];
			$output    .= sprintf(
				'<p class="tlr-message tlr-error">%s</p>',
				esc_html( $message )
			);
		}

		$output .= $this->get_registration_form_html( 'instructor' );

		return $output;
	}

	/**
	 * Render the student registration form shortcode.
	 *
	 * @return string
	 */
	public function render_student_registration_form() {
		if ( is_user_logged_in() ) {
			return sprintf(
				'<p class="tlr-message tlr-info">%s</p>',
				esc_html__( 'You are already logged in.', 'tutor-lms-registration' )
			);
		}

		if ( ! $this->is_tutor_active() ) {
			return sprintf(
				'<p class="tlr-message tlr-error">%s</p>',
				esc_html__( 'Tutor LMS plugin is required for this registration form.', 'tutor-lms-registration' )
			);
		}

		$output = '';

		if ( isset( $_GET['tlr_registered'] ) && $_GET['tlr_registered'] === '1' ) {
			$output .= sprintf(
				'<p class="tlr-message tlr-success">%s</p>',
				esc_html__( 'Registration successful! You can now log in.', 'tutor-lms-registration' )
			);
		}

		if ( isset( $_GET['tlr_error'] ) ) {
			$error_code = sanitize_text_field( wp_unslash( $_GET['tlr_error'] ) );
			$messages   = $this->get_error_messages();
			$message    = isset( $messages[ $error_code ] ) ? $messages[ $error_code ] : $messages['generic'];
			$output    .= sprintf(
				'<p class="tlr-message tlr-error">%s</p>',
				esc_html( $message )
			);
		}

		$output .= $this->get_registration_form_html( 'student' );

		return $output;
	}

	/**
	 * Get registration form HTML (shared by instructor and student shortcodes).
	 *
	 * @param string $type Either 'instructor' or 'student'.
	 * @return string
	 */
	private function get_registration_form_html( $type ) {
		$is_student = ( $type === 'student' );
		$form_id    = $is_student ? 'tlr-student-registration-form' : 'tlr-registration-form';
		$nonce      = $is_student ? 'tlr_register_student' : 'tlr_register';
		$nonce_name = $is_student ? 'tlr_register_student_nonce' : 'tlr_register_nonce';
		$submit_name = $is_student ? 'tlr_register_student' : 'tlr_register';

		return '
		<form method="post" action="" class="tlr-registration-form" id="' . esc_attr( $form_id ) . '">
			' . wp_nonce_field( $nonce, $nonce_name, true, false ) . '
			<p class="tlr-form-row">
				<label for="tlr_first_name">' . esc_html__( 'First Name', 'tutor-lms-registration' ) . ' <span class="required">*</span></label>
				<input type="text" name="tlr_first_name" id="tlr_first_name" required value="' . esc_attr( $this->get_posted_value( 'tlr_first_name' ) ) . '" />
			</p>
			<p class="tlr-form-row">
				<label for="tlr_last_name">' . esc_html__( 'Last Name', 'tutor-lms-registration' ) . ' <span class="required">*</span></label>
				<input type="text" name="tlr_last_name" id="tlr_last_name" required value="' . esc_attr( $this->get_posted_value( 'tlr_last_name' ) ) . '" />
			</p>
			<p class="tlr-form-row">
				<label for="tlr_username">' . esc_html__( 'Username', 'tutor-lms-registration' ) . ' <span class="required">*</span></label>
				<input type="text" name="tlr_username" id="tlr_username" required value="' . esc_attr( $this->get_posted_value( 'tlr_username' ) ) . '" autocomplete="username" />
			</p>
			<p class="tlr-form-row">
				<label for="tlr_email">' . esc_html__( 'Email', 'tutor-lms-registration' ) . ' <span class="required">*</span></label>
				<input type="email" name="tlr_email" id="tlr_email" required value="' . esc_attr( $this->get_posted_value( 'tlr_email' ) ) . '" autocomplete="email" />
			</p>
			<p class="tlr-form-row">
				<label for="tlr_password">' . esc_html__( 'Password', 'tutor-lms-registration' ) . ' <span class="required">*</span></label>
				<input type="password" name="tlr_password" id="tlr_password" required autocomplete="new-password" />
			</p>
			<p class="tlr-form-row tlr-submit-row">
				<button type="submit" name="' . esc_attr( $submit_name ) . '" class="tlr-submit-btn">' . esc_html__( 'Register', 'tutor-lms-registration' ) . '</button>
			</p>
		</form>';
	}

	/**
	 * Get sanitized value from POST data (for repopulating on validation error).
	 *
	 * @param string $key POST key.
	 * @return string
	 */
	private function get_posted_value( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Get error messages.
	 *
	 * @return array
	 */
	private function get_error_messages() {
		return array(
			'nonce'         => __( 'Security check failed. Please try again.', 'tutor-lms-registration' ),
			'username'      => __( 'Invalid username. Please choose another.', 'tutor-lms-registration' ),
			'email'         => __( 'Invalid or duplicate email address.', 'tutor-lms-registration' ),
			'password'      => __( 'Password is too short. Please use at least 6 characters.', 'tutor-lms-registration' ),
			'generic'       => __( 'Registration failed. Please try again.', 'tutor-lms-registration' ),
			'tutor_inactive' => __( 'Tutor LMS is not active. Registration is unavailable.', 'tutor-lms-registration' ),
		);
	}

	/**
	 * Handle form submission (instructor and student).
	 */
	public function handle_form_submission() {
		$is_student = isset( $_POST['tlr_register_student'] ) && isset( $_POST['tlr_register_student_nonce'] );
		$is_instructor = isset( $_POST['tlr_register'] ) && isset( $_POST['tlr_register_nonce'] );

		if ( ! $is_student && ! $is_instructor ) {
			return;
		}

		if ( $is_student ) {
			$nonce_value = isset( $_POST['tlr_register_student_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tlr_register_student_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce_value, 'tlr_register_student' ) ) {
				wp_safe_redirect( add_query_arg( 'tlr_error', 'nonce', wp_get_referer() ?: home_url( '/' ) ) );
				exit;
			}
		} else {
			$nonce_value = isset( $_POST['tlr_register_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tlr_register_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce_value, 'tlr_register' ) ) {
				wp_safe_redirect( add_query_arg( 'tlr_error', 'nonce', wp_get_referer() ?: home_url( '/' ) ) );
				exit;
			}
		}

		if ( ! $this->is_tutor_active() ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'tutor_inactive', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		$first_name = isset( $_POST['tlr_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tlr_first_name'] ) ) : '';
		$last_name  = isset( $_POST['tlr_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tlr_last_name'] ) ) : '';
		$username   = isset( $_POST['tlr_username'] ) ? sanitize_user( wp_unslash( $_POST['tlr_username'] ), true ) : '';
		$email      = isset( $_POST['tlr_email'] ) ? sanitize_email( wp_unslash( $_POST['tlr_email'] ) ) : '';
		$password   = isset( $_POST['tlr_password'] ) ? $_POST['tlr_password'] : '';

		// Validation.
		if ( empty( $first_name ) || empty( $last_name ) || empty( $username ) || empty( $email ) || empty( $password ) ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'generic', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		if ( ! validate_username( $username ) || username_exists( $username ) ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'username', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		if ( ! is_email( $email ) || email_exists( $email ) ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'email', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		if ( strlen( $password ) < 6 ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'password', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => trim( $first_name . ' ' . $last_name ),
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'tlr_error', 'generic', wp_get_referer() ?: home_url( '/' ) ) );
			exit;
		}

		if ( $is_instructor ) {
			// Mark user as pending instructor so they appear in wp-admin → Tutor LMS → Instructor list (Pending tab).
			// Admin must approve to grant the instructor role and _tutor_instructor_approved.
			update_user_meta( $user_id, '_tutor_instructor_status', 'pending' );
			update_user_meta( $user_id, '_is_tutor_instructor', tutor_time() );
			do_action( 'tlr_after_instructor_registration', $user_id );
		} else {
			// Student registration: subscriber only, no instructor meta.
			do_action( 'tlr_after_student_registration', $user_id );
		}

		// Log the user in after successful registration.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		$redirect_url = remove_query_arg( array( 'tlr_error', 'tlr_registered' ), wp_get_referer() ?: get_permalink( 394 ) );
		wp_safe_redirect( add_query_arg( 'tlr_registered', '1', $redirect_url ) );
		exit;
	}
}

new Tutor_LMS_Registration();
