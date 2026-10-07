<?php
/**
 * Init Tutor Pro
 *
 * @package TutorPro
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_PRO;

use TUTOR\Input;
use TUTOR\Permalink;
use TutorPro\GiftCourse\InitGift;
use TutorPro\TemplateImport\TemplateImportInit;

defined( 'ABSPATH' ) || exit;

/**
 * Init Class
 *
 * @since 1.0.0
 */
class Init {

	/**
	 * The user's license data is saved against this option key
	 *
	 * @since 3.2.0
	 */
	const TUTOR_LICENSE_OPT_KEY = 'tutor_license_info';

	//phpcs:disable Squiz.Commenting.VariableComment.Missing
	public $version = TUTOR_PRO_VERSION;
	public $path;
	public $url;
	public $basename;
	public $gift_course;

	private $admin;
	private $assets;
	private $general;
	private $quiz;

	private $upgrader;
	private $google_meet;

	private $course_duplicator;
	private $instructor_percentage;
	private $dashboard;
	private $shortcode;
	private $updater;

	private $email_verification;
	private $device_management;
	private $instructor;
	private $rest_api;
	//phpcs:enable Squiz.Commenting.VariableComment.Missing

	/**
	 * Register hooks and dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->path     = plugin_dir_path( TUTOR_PRO_FILE );
		$this->url      = plugin_dir_url( TUTOR_PRO_FILE );
		$this->basename = plugin_basename( TUTOR_PRO_FILE );

		$this->includes();

		if ( is_plugin_active( 'tutor/tutor.php' ) && $this->is_tutor_core_has_req_verion() ) {
			add_action( 'tutor_loaded', array( $this, 'load_constructors_asset' ) );
		}

		if ( ! is_plugin_active( 'tutor/tutor.php' ) ) {
			add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
		}

		if ( is_plugin_active( 'tutor/tutor.php' ) && ! $this->is_tutor_core_has_req_verion() ) {
			add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
		}

		if ( class_exists( 'GoogleMeet' ) ) {
			require_once tutor_pro()->path . 'addons/google-meet/google-meet.php';
		}
	}

	/**
	 * Load constructors assets.
	 *
	 * @since 3.2.0
	 *
	 * License check added before loading Pro files, assets etc
	 * User 's license data must be store in database to use Pro plugin
	 * regardless of activate status. If no license data available then
	 * user must have to enter valid license key to activate Pro plugin
	 *
	 * @return void
	 */
	public function load_constructors_asset() {

		$this->admin  = new Admin();
		$this->assets = new Assets();

		$has_license = get_option( self::TUTOR_LICENSE_OPT_KEY, false );

		// Init license feature.
		add_action( 'init', fn() => $this->init_license() );

		// If license not available return.
		if ( is_admin() && false === $has_license && ! tutor_is_local_env() ) {
			$current_page = Input::get( 'page' );
			if ( 'tutor-pro-license' !== $current_page ) {
				add_action( 'admin_notices', array( $this, 'license_notice' ) );
			}

			add_filter( 'tutor_pro_flag', fn () => '' );
			add_filter( 'tutor_localize_data', array( $this, 'remove_pro_url' ) );
			add_filter( 'tutor_course_builder_localized_data', array( $this, 'remove_pro_url' ) );

			return;
		}

		$file_list = array(
			tutor_pro()->path . 'tutorai/TutorAI.php',
			tutor_pro()->path . 'ecommerce/Init.php',
			tutor_pro()->path . 'tools/Init.php',
		);

		foreach ( $file_list as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}

		do_action( 'tutor_pro_before_load' );

		// Load Component from Class.
		$this->general  = new General();
		$this->upgrader = new Upgrader();
		$this->quiz     = new Quiz();

		$this->course_duplicator     = new Course_Duplicator();
		$this->instructor_percentage = new Instructor_Percentage();
		$this->dashboard             = new Dashboard();
		$this->shortcode             = new Shortcode();
		$this->instructor            = new Instructor();
		$this->device_management     = new DeviceManagement();
		$this->email_verification    = new EmailVerification();
		$this->rest_api              = new RestAPI();

		new InitGift();
		new Filters();
		new ContentSecurity();
		new ContentDuplicator();
		new NotificationPreference();
		new GuestEmail();
		new CourseComingSoon();
		new VisibilityFieldControl();
		new LessonNotes();
		new ProgressReset();
		new ChangeEmail();
		new Webinar();

		// Template import.
		new TemplateImportInit();

		$this->load_addons();

		do_action( 'tutor_pro_loaded' );
	}

	/**
	 * Run the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run() {
		do_action( 'tutor_pro_before_run' );

		register_activation_hook( TUTOR_PRO_FILE, array( $this, 'tutor_pro_activate' ) );

		do_action( 'tutor_pro_after_run' );
	}

	/**
	 * Do some task during plugin activation
	 *
	 * @since 1.0.0
	 */
	public function tutor_pro_activate() {
		Permalink::set_permalink_flag();

		$version = get_option( 'tutor_pro_version' );
		// Save Option.
		if ( ! $version ) {
			update_option( 'tutor_pro_version', TUTOR_PRO_VERSION );
		}

		$this->migrate_membership_model();
		$this->assignment_meta_generate();
	}


	/**
	 * Migrate membership model
	 *
	 * @since 1.9.4
	 */
	private function migrate_membership_model() {
		$migrated  = get_option( 'tutor_pmpro_model_migrated', false );
		$old_model = get_option( 'tutor_pmpro_membership_model', null );

		if ( $migrated || ! $old_model || ! tutor_utils()->has_pmpro() ) {
			// Already migrated
			// or old one not saved yet
			// PM pro not available.
			return;
		}

		global $wpdb;
		$ids                      = $wpdb->get_col( "SELECT id FROM {$wpdb->pmpro_membership_levels}" );
		! is_array( $ids ) ? $ids = array() : 0;

		foreach ( $ids as $level_id ) {
			if ( function_exists( 'update_pmpro_membership_level_meta' ) ) {
				update_pmpro_membership_level_meta( $level_id, 'tutor_pmpro_membership_model', $old_model );
			}
		}

		update_option( 'tutor_pmpro_model_migrated', true );
	}


	/**
	 * Includes helper files.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function includes() {
		$tutor_pro_path = plugin_dir_path( TUTOR_PRO_FILE );
		include $tutor_pro_path . 'includes/functions.php';
	}

	/**
	 * Load addons
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added hook for each addon loaded.
	 *
	 * @return void
	 */
	public function load_addons() {

		$addons_dir = array_filter( glob( tutor_pro()->path . 'addons' . DIRECTORY_SEPARATOR . '*' ), 'is_dir' );
		if ( count( $addons_dir ) > 0 ) {
			foreach ( $addons_dir as $key => $value ) {
				$addon_dir_name = str_replace( dirname( $value ) . DIRECTORY_SEPARATOR, '', $value );
				$file_name      = tutor_pro()->path . 'addons' . DIRECTORY_SEPARATOR . $addon_dir_name . DIRECTORY_SEPARATOR . $addon_dir_name . '.php';
				if ( file_exists( $file_name ) ) {
					include_once $file_name;

					/**
					 * Hook for each addon that triggers when the addon is loaded.
					 * Example: tutor_email_addon_loaded
					 *          tutor_auth_addon_loaded
					 *
					 * @since 3.0.0
					 */
					$hook_prefix = 'tutor_';
					$addon_hook  = str_replace( '-', '_', $addon_dir_name . '_addon_loaded' );
					$addon_hook  = strpos( $addon_hook, $hook_prefix ) !== 0 ? $hook_prefix . $addon_hook : $addon_hook;
					do_action( $addon_hook, $file_name );
				}
			}
		}
	}

	/**
	 * Assignment meta generate for backward compatibility
	 *
	 * This method will update meta key for assignment total mark & pass mark
	 * required for query data
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function assignment_meta_generate(): void {
		global $wpdb;
		$already_updated = get_option( 'tutor_assignment_meta_key_updated' );
		if ( ! $already_updated ) {
			$assignments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post.ID, m1.meta_value
						FROM {$wpdb->posts} AS post
						INNER JOIN {$wpdb->postmeta} as m1
							ON m1.post_id = post.ID
							AND m1.meta_key = 'assignment_option'
						WHERE post.post_type = %s
					",
					'tutor_assignments'
				)
			);
			$this->update_assignment_meta( $assignments );
		}
	}

	/**
	 * Update assignment meta keys with value
	 *
	 * @since 2.0.0
	 *
	 * @param array $assignments | assignments array of objects.
	 *
	 * @return bool
	 */
	public function update_assignment_meta( $assignments ): bool {
		foreach ( $assignments as $assignment ) {
			$assignment_id   = $assignment->ID;
			$assignment_info = unserialize( $assignment->meta_value );
			$total_mark      = isset( $assignment_info['total_mark'] ) ? $assignment_info['total_mark'] : '';
			$pass_mark       = isset( $assignment_info['pass_mark'] ) ? $assignment_info['pass_mark'] : '';
			if ( '' !== $total_mark ) {
				update_post_meta( $assignment_id, '_tutor_assignment_total_mark', $total_mark );
			}
			if ( '' !== $pass_mark ) {
				update_post_meta( $assignment_id, '_tutor_assignment_pass_mark', $pass_mark );
			}
			add_option( 'tutor_assignment_meta_key_updated', true );
		}
		return true;
	}

	/**
	 * Check whether Tutor core has required version installed
	 *
	 * @since 2.0.0
	 *
	 * @return bool if has return true otherwise false
	 */
	public function is_tutor_core_has_req_verion() {
		$file_path              = WP_PLUGIN_DIR . '/tutor/tutor.php';
		$plugin_data            = get_file_data(
			$file_path,
			array(
				'Version' => 'Version',
			)
		);
		$tutor_version          = $plugin_data['Version'];
		$tutor_core_req_version = TUTOR_CORE_REQ_VERSION;
		$is_compatible          = version_compare( $tutor_version, $tutor_core_req_version, '>=' );
		return $is_compatible ? true : false;
	}

	/**
	 * Tutor core required version message
	 *
	 * @since 2.0.0
	 */
	public function tutor_core_version_compare() {
		$tutor_file = WP_PLUGIN_DIR . '/tutor/tutor.php';
		if ( file_exists( $tutor_file ) && defined( 'TUTOR_VERSION' ) ) {
			if ( ! $this->is_tutor_core_has_req_verion() ) {
				add_action( 'admin_notices', array( $this, 'required_version_notice' ) );
			}
		}
	}

	/**
	 * Admin notice for required Tutor core version
	 *
	 * @since 2.0.0
	 */
	public function required_version_notice() {
		?>
		<div class="notice notice-error tutor-install-notice" style="display:flex; tutor-justify: space-between; align-items: center;">
			<div class="tutor-install-notice-inner" style="display: flex; column-gap: 20px;">
				<div class="tutor-install-notice-icon">
					<img src="<?php echo esc_url( tutor_pro()->url . 'assets/images/tutor-logo.jpg' ); ?>" alt="">
				</div>
				<?php if ( ! is_plugin_active( 'tutor/tutor.php' ) ) : ?>
					<div class="tutor-install-notice-content">
					<h2><?php esc_html_e( 'WARNING: Tutor LMS Free plugin is required.', 'tutor-pro' ); ?></h2>
					<p>
						<?php
							$notice = __( 'Tutor LMS Pro requires the free version to be installed. Without it, Pro features will not be available.', 'tutor-pro' );
							echo esc_html( $notice );
						?>
					</p>
				</div>
				<?php else : ?>
					<div class="tutor-install-notice-content">
					<h2><?php esc_html_e( 'WARNING: The versions of Tutor LMS and Tutor LMS Pro are not compatible.', 'tutor-pro' ); ?></h2>
					<p>
						<?php
							$notice = __( 'It appears that you have installed mismatched versions of Tutor LMS and Tutor LMS Pro. To ensure all features work correctly, please install the same version of both plugins.', 'tutor-pro' );
							echo esc_html( $notice );
						?>
					</p>
				</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
	}

	/**
	 * License Initialization
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_license() {
		require_once dirname( __DIR__ ) . '/updater/update.php';
		$this->updater = new \TutorPRO\ThemeumUpdater\Update();
	}

	/**
	 * Show admin notice for license activation
	 *
	 * @since 3.2.0
	 */
	public function license_notice() {
		?>
		<div class="tutor-license-notice notice is-dismissible">
			<div class="tutor-license-notice-inner">
				<div class="tutor-license-notice-left">
					<svg width="144" height="32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M105.714 25.347h7.519v1.55h-9.165V10.45h1.646v14.897ZM129.915 10.45v16.448h-1.645V12.94l-5.757 9.634h-.235l-5.756-9.634v13.958h-1.645V10.45h2.018l5.501 9.207 5.501-9.207h2.018ZM137.903 27.18c-1.503 0-2.792-.357-3.865-1.07-1.073-.712-1.821-1.695-2.244-2.948l1.41-.823c.297 1.02.846 1.814 1.644 2.385.8.572 1.825.858 3.078.858 1.222 0 2.174-.27 2.855-.81.683-.541 1.023-1.258 1.023-2.15 0-.862-.314-1.512-.94-1.951-.627-.438-1.66-.893-3.102-1.362-1.707-.565-2.835-1.019-3.383-1.364-1.254-.752-1.88-1.84-1.88-3.266 0-1.394.485-2.494 1.457-3.301.971-.806 2.169-1.21 3.595-1.21 1.284 0 2.397.333 3.336.998a6.056 6.056 0 0 1 2.091 2.55l-1.386.775c-.752-1.817-2.099-2.725-4.041-2.725-1.019 0-1.841.25-2.467.752-.628.501-.941 1.198-.941 2.09 0 .815.283 1.426.847 1.834.564.407 1.496.822 2.796 1.245.595.203 1.021.35 1.28.435.258.086.635.227 1.129.422.493.197.857.368 1.092.517.235.15.521.353.858.612.336.258.579.516.728.775.149.258.281.572.399.94s.176.764.176 1.186c0 1.395-.509 2.51-1.526 3.348-1.019.839-2.359 1.258-4.019 1.258ZM88.383 26.911V10.798h3.115v1.61h.052c1.402-2 3.687-2.05 4.518-2.077v3.428c-.831.051-1.662.26-2.415.649-1.064.57-1.947 1.92-1.947 4.465v8.064l-3.323-.026ZM83.537 18.864c0 2.741-2.212 4.97-4.932 4.97-2.719 0-4.93-2.229-4.93-4.97 0-2.74 2.212-4.97 4.93-4.97 2.72 0 4.931 2.23 4.931 4.97Zm.853-5.699a8.16 8.16 0 0 0-5.784-2.386 8.158 8.158 0 0 0-5.784 2.386 8 8 0 0 0-2.37 5.699 8 8 0 0 0 2.37 5.698 8.17 8.17 0 0 0 5.784 2.386 8.171 8.171 0 0 0 5.784-2.386 7.996 7.996 0 0 0 2.37-5.698c0-2.15-.842-4.174-2.37-5.699ZM66.783 26.898H63.46V13.813h-2.414v-3.038h2.414V3.874h3.324v6.9h2.908v3.039h-2.908v13.085ZM46.4 10.775v7.555c0 4.389 1.947 5.557 4.414 5.557 2.752 0 4.647-1.766 4.647-5.66v-7.452h3.324v16.123h-3.038v-2.362h-.078c-.908 1.532-2.986 2.362-5.219 2.362-2.025 0-3.79-.648-5.062-1.765-1.48-1.273-2.337-3.271-2.337-6.829v-7.529H46.4ZM37.885 26.943H34.56V13.801h-2.414v-3.037h2.414v-6.89h3.324v6.89h2.907v3.037h-2.907v13.142Z" fill="#000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.738 19.843a1.507 1.507 0 0 1-1.433-1.433v-3.264c0-.79.643-1.434 1.433-1.434s1.434.644 1.434 1.434v3.262c0 .79-.593 1.433-1.336 1.433-.047.002-.098.002-.098.002ZM17.018 19.843c-.79 0-1.433-.593-1.433-1.382v-3.315c0-.79.644-1.434 1.433-1.434.79 0 1.434.644 1.434 1.434v3.262a1.438 1.438 0 0 1-1.434 1.435Z" fill="#0049F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.394 12.694a3.904 3.904 0 0 1 3.413-2.242c2.132.056 3.84 1.866 3.79 4v7.206c.105.8.852 1.335 1.652 1.224.64-.105 1.174-.584 1.224-1.224v-7.2c-.055-2.132 1.653-3.946 3.79-4 1.441 0 2.72.8 3.305 2.08 2.4 4.748.534 10.508-4.213 12.911-4.747 2.403-10.507.53-12.905-4.218-1.335-2.667-1.385-5.867-.056-8.537ZM9.423 3.2h5.386v2.081a11.993 11.993 0 0 0-2.77-.321c-.908 0-1.814.105-2.667.266l.05-2.026ZM24.09 17.974c0-.321.055-.584.055-.962 0-4.374-2.347-8.375-6.187-10.508V3.2h2.348c.907 0 1.602-.695 1.602-1.602C21.91.69 21.214 0 20.307 0H3.929C3.02.055 2.33.745 2.33 1.653c0 .907.695 1.602 1.602 1.602h2.348V6.51C.462 9.706-1.725 17.016 1.475 22.83c.161.266.267.479.429.745C6.439 31.203 18.599 31.948 22.706 32c.374 0 .695-.16 1.014-.373.266-.267.373-.64.373-1.014-.002.005-.002-12.639-.002-12.639Z" fill="#0049F8"/></svg>
					<svg width="144" height="48" viewBox="0 0 144 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect x="0.5" y="1" width="143" height="46" rx="23" fill="white"/>
						<rect x="0.5" y="1" width="143" height="46" rx="23" stroke="black"/>
						<circle cx="33" cy="21" r="6" fill="#FF4131"/>
						<rect x="23" y="36.5" width="20" height="1" rx="0.5" fill="black"/>
						<path d="M61.7519 15.2018C61.8615 15.0101 62.1062 14.9431 62.298 15.0526C62.4897 15.1622 62.5567 15.4069 62.4472 15.5987L59.6028 20.5742L65.301 20.6C65.5217 20.6012 65.6995 20.7808 65.6986 21.0016C65.6976 21.2225 65.518 21.401 65.2971 21.4L59.5747 21.3742L62.4472 26.4013L62.48 26.4755C62.5379 26.6522 62.4657 26.8515 62.298 26.9474C62.1062 27.0569 61.8615 26.9899 61.7519 26.7982L58.8997 21.8062L56.0475 26.7982C55.9379 26.9899 55.6932 27.0569 55.5014 26.9474C55.3097 26.8378 55.2427 26.5931 55.3522 26.4013L58.2279 21.3679L52.3984 21.3422C52.1775 21.3412 51.999 21.1615 52 20.9406C52.001 20.7198 52.1808 20.5413 52.4015 20.5422L58.1919 20.568L55.3522 15.5987C55.2427 15.4069 55.3097 15.1622 55.5014 15.0526C55.6692 14.9568 55.8771 14.9964 55.9998 15.1362L56.0475 15.2018L58.8997 20.193L61.7519 15.2018Z" fill="black"/>
						<rect x="49" y="36.5" width="20" height="1" rx="0.5" fill="black"/>
						<rect x="77" y="20" width="12" height="12" rx="1.9894" transform="rotate(-43.8644 77 20)" fill="#01AB4B"/>
						<rect x="75" y="36.5" width="20" height="1" rx="0.5" fill="black"/>
						<rect x="105" y="15" width="12" height="12" rx="2.18182" fill="#FFBE00"/>
						<rect x="101" y="36.5" width="20" height="1" rx="0.5" fill="black"/>
					</svg>
				</div>
				<div class="tutor-license-notice-right">
					<div class="tutor-license-notice-right-content">
						<h3><?php esc_html_e( 'Connect your license key', 'tutor-pro' ); ?></h3>
						<p><?php esc_html_e( 'Unlock all premium features, future updates, and priority support by connecting your license key.', 'tutor-pro' ); ?></p>
					</div>
					<div class="tutor-license-notice-right-buttons">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor_settings&tab_page=license' ) ); ?>" class="tutor-btn tutor-btn-tertiary tutor-gap-4px tutor-text-nowrap">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M20 4L17 7M17 7C15.8954 5.89543 14.1046 5.89543 13 7L12 8L16 12L17 11C18.1046 9.89543 18.1046 8.10457 17 7ZM4 20L7 17M7 17C5.89543 15.8954 5.89543 14.1046 7 13L8 12L12 16L11 17C9.89543 18.1046 8.10457 18.1046 7 17ZM9 13L10.5 11.5M11 15L12.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<?php esc_html_e( 'Connect Now', 'tutor-pro' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Remove tutor_pro_url if there is no license available
	 *
	 * @since 3.2.0
	 *
	 * @param array $localize_data Default localize data.
	 *
	 * @return array
	 */
	public function remove_pro_url( $localize_data ) {
		$localize_data['tutor_pro_url'] = null;
		return $localize_data;
	}
}
