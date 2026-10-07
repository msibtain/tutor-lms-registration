<?php
/**
 * Create placeholder for social authentication buttons
 *
 * @package TutorPro\SocialLogin\Authentication
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.9
 */

namespace TutorPro\SocialLogin\Authentication;

use Tutor\Components\SvgIcon;
use TUTOR\Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook up & create placeholder
 *
 * @since 2.1.9
 */
class Placeholder {

	/**
	 * Register hooks
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'tutor_after_login_form_wrapper', __CLASS__ . '::create_placeholder' );
		add_action( 'tutor_after_registration_form_wrap', __CLASS__ . '::create_placeholder' );
		add_action( 'tutor_before_instructor_reg_form', __CLASS__ . '::set_instructor' );
		add_action( 'tutor_before_student_reg_form', __CLASS__ . '::unset_instructor' );
		add_action( 'tutor_before_login_form', __CLASS__ . '::unset_instructor' );
	}

	/**
	 * Function to set twitter_login_is_instructor for Twitter Login
	 *
	 * @since 2.1.10
	 *
	 * @return void
	 */
	public static function set_instructor() {
		set_transient( 'twitter_login_is_instructor', true );
	}

	/**
	 * Function to unset twitter_login_is_instructor for Twitter Login
	 *
	 * @since 2.1.10
	 *
	 * @return void
	 */
	public static function unset_instructor() {
		delete_transient( 'twitter_login_is_instructor' );
	}

	/**
	 * Create placeholder wrapper so that
	 * social authentication button can be placed here
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public static function create_placeholder(): void {
		// Return if user already logged-in.
		if ( get_current_user_id() ) {
			return;
		}

		$authentications = self::authentication_info();
		$enabled_auths   = array_filter(
			$authentications,
			function( $auth ) {
				return (bool) tutor_utils()->get_option( $auth['key'] );
			}
		);

		if ( empty( $enabled_auths ) ) {
			return;
		}

		$twitter_btn_url = rtrim( tutor_utils()->tutor_dashboard_url(), '/' ) . '?twitter_oauth_verify=true';
		?>
		<style>
			.tutor-social-authentication {
				display: flex;
				flex-direction: column;
				gap: 8px;
				align-items: center;
				padding-top: 24px;
				border-top: 1px solid var(--tutor-border-idle, var(--tutor-border-color, #e0e2ea));
			}
			#tutor-pro-twitter-login {
				background-color: #000;
				border-color: #000;
				font-weight: bold;
			}
		</style>
		<div id="tutor-pro-social-authentication" class="tutor-social-authentication">
			<?php
			foreach ( $enabled_auths as $authentication ) {
				?>
				<div class="tutor-flex tutor-d-flex tutor-justify-center" id="<?php echo esc_html( $authentication['placeholder_id'] ); ?>">
					<?php if ( 'facebook' === $authentication['auth'] ) : ?>
						<div class="fb-login-button" data-size="large" data-button-type="" data-layout="" data-auto-logout-link="false" data-use-continue-as="true" scope="public_profile,email" show-faces="false" onlogin="checkLoginState();"></div>
					<?php endif; ?>

					<?php if ( 'twitter' === $authentication['auth'] ) : ?>
						<a href="<?php echo esc_url( $twitter_btn_url ); ?>" class="tutor-btn tutor-btn-primary tutor-justify-center" id="tutor-pro-twitter-login">
							<?php SvgIcon::make()->name( Icon::X )->render(); ?>
							<span>&nbsp;<?php esc_html_e( 'Sign In with X', 'tutor-pro' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get available authentication info
	 *
	 * @since 2.1.9
	 *
	 * @return array
	 */
	public static function authentication_info() {
		return array(
			array(
				'auth'           => 'google',
				'placeholder_id' => 'tutor-pro-google-authentication',
				'key'            => 'enable_google_login',
			),
			array(
				'auth'           => 'facebook',
				'placeholder_id' => 'tutor-pro-facebook-authentication',
				'key'            => 'enable_facebook_login',
			),
			array(
				'auth'           => 'twitter',
				'placeholder_id' => 'tutor-pro-twitter-authentication',
				'key'            => 'enable_twitter_login',
			),
		);
	}
}
