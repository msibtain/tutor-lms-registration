<?php
/**
 * Certificate
 *
 * @package TutorPro\Addon
 * @subpackage Certificate
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.0.0
 */

namespace TUTOR_CERT;

defined( 'ABSPATH' ) || exit;

use TUTOR\Dashboard;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\HttpHelper;
use Tutor\Helpers\QueryHelper;
use Tutor\Helpers\UrlHelper;
use TUTOR\Icon;
use TUTOR\Input;
use Tutor\Models\CourseModel;
use Tutor\Models\EnrollmentModel;
use Tutor\Traits\JsonResponse;
use TUTOR\User;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Models\BundleModel;

/**
 * Class Certificate
 *
 * @since 2.0.0
 */
class Certificate {
	use JsonResponse;

	/**
	 * Option keys
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	const OPTION_CERTIFICATE_PAGE        = 'tutor_certificate_page';
	const OPTION_CERTIFICATE_VERIFY_PAGE = 'tutor_certificate_verify_page';

	/**
	 * Template
	 *
	 * @var string
	 */
	private $template;
	/**
	 * Directory name
	 *
	 * @var string
	 */
	public $certificates_dir_name = 'tutor-certificates';
	/**
	 * Store Key
	 *
	 * @var string
	 */
	public $certificate_stored_key = 'tutor_certificate_has_image';
	/**
	 * Meta key
	 *
	 * @var string
	 */
	public static $template_meta_key = 'tutor_course_certificate_template';
	/**
	 * Image Url Base
	 *
	 * @var string
	 */
	public static $certificate_img_url_base = 'https://assets.tutorlms.io/certificate-builder/images/previews/';

	/**
	 * Certificate for individual courses in bundle meta key.
	 *
	 * @var string
	 */
	public static $bundle_allow_individual_certificates_meta_key = 'certificate_for_individual_courses';

	/**
	 * Register hooks
	 *
	 * @since 2.0.0
	 *
	 * @param boolean $reuse reuse.
	 */
	public function __construct( $reuse = false ) {
		if ( ! function_exists( 'tutor_utils' ) || true === $reuse ) {
			return;
		}

		add_action( 'tutor_course/single/actions_btn_group/before', array( $this, 'certificate_download_btn' ) );
		add_action( 'tutor_report_course_certificate', array( $this, 'download_btn_in_report' ), 10, 2 );

		add_action( 'wp_loaded', array( $this, 'get_fonts' ) );

		/**
		 * Hook `template_redirect` to `template_include`
		 * for elementor custom header footer support.
		 *
		 * @since 2.4.0
		 */
		add_filter( 'template_include', array( $this, 'view_certificate' ) );

		add_action( 'wp_ajax_tutor_generate_course_certificate', array( $this, 'send_certificate_html' ) );
		add_action( 'wp_ajax_nopriv_tutor_generate_course_certificate', array( $this, 'send_certificate_html' ) );

		add_action( 'wp_ajax_tutor_store_certificate_image', array( $this, 'store_certificate_image' ) );
		add_action( 'wp_ajax_nopriv_tutor_store_certificate_image', array( $this, 'store_certificate_image' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_script' ) );
		add_action( 'wp_head', array( $this, 'certificate_header_content' ) );

		/**
		 * Certificate template metabox in course for per course template
		 *
		 * @since v1.9.0
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'load_field_scripts' ) );
		add_action( 'tutor_save_course', array( $this, 'save_certificate_template_meta' ) );
		add_action( 'tutor_save_bundle', array( $this, 'save_bundle_certificate_data' ) );

		/**
		 * Certificate builder support
		 *
		 * @since v1.9.11
		 */
		add_filter( 'tutor_certificate_completion_data', array( $this, 'completed_course' ), 10, 2 );
		add_filter( 'tutor_certificate_public_url', array( $this, 'tutor_certificate_public_url' ), 10, 1 );
		add_filter( 'tutor_certificate_instructor_signature', array( $this, 'get_signature_url' ), 10, 2 );

		/**
		 * Show whether single course has course in course sidebar meta
		 *
		 * @ince v2.0.0
		 */
		add_filter( 'tutor/course/single/sidebar/metadata', array( $this, 'show_course_has_certificate' ), 10, 2 );

		// Download certificate button for completed courses in all kind of archive.
		add_filter( 'tutor_course/loop/start/button', array( $this, 'course_loop_btn' ), 99, 2 );
		add_action( 'tutor_course_action_btn', array( $this, 'render_course_action_btn' ) );
		add_action( 'tutor_dashboard_bundle_card_actions', array( $this, 'render_course_action_btn' ) );

		add_action( 'admin_footer', array( $this, 'add_button_to_certificate_edit_page' ) );

		add_action( 'tutor_course/single/after/topics', array( $this, 'add_certificate_showcase' ) );
		add_action( 'tutor_bundle_single_after_courses', array( $this, 'add_certificate_showcase' ) );

		// Alter yoast og tags.
		add_filter( 'wpseo_opengraph_url', array( $this, 'remove_yoast_seo_og_tags' ), 10, 1 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'remove_yoast_seo_og_tags' ), 10, 1 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'remove_yoast_seo_og_tags' ), 10, 1 );
		add_filter( 'wpseo_opengraph_desc', array( $this, 'remove_yoast_seo_og_tags' ), 10, 1 );
		add_filter( 'wpseo_opengraph_image', array( $this, 'remove_yoast_seo_og_tags' ), 10, 1 );

		// @since 3.0.0
		add_action( 'wp_ajax_tutor_course_certificate_list', array( $this, 'ajax_course_certificate_list' ) );
		add_filter( 'tutor_course_details_response', array( $this, 'extend_course_details_response' ) );
		add_filter( 'tutor_bundle_details_response', array( $this, 'extend_bundle_details_response' ) );

		// @since 3.2.2
		add_action( 'tutor_draft_course_created', array( $this, 'draft_course_created' ) );

		// @since 4.0.0
		add_filter( 'tutor_should_load_legacy_scripts', array( $this, 'filter_should_load_legacy_scripts' ) );
		add_filter( 'template_include', array( $this, 'load_certificate_verification_page' ) );
		add_action( 'wp_ajax_tutor_verify_certificate', array( $this, 'ajax_verify_certificate' ) );
		add_action( 'wp_ajax_nopriv_tutor_verify_certificate', array( $this, 'ajax_verify_certificate' ) );

		add_filter( 'tutor_enrollment_action_dropdown_items', array( $this, 'add_download_certificate_button' ), 10, 6 );
		add_action( 'wp_ajax_tutor_download_course_certificate', array( $this, 'ajax_download_course_certificate' ) );

		add_filter( 'tutor_learning_area_sub_page_nav_item', array( $this, 'add_subpage_nav_item' ), 10, 2 );
		add_filter( 'tutor_learning_area_course_info_metadata', array( $this, 'add_certificate_metadata' ), 10, 2 );
		add_action( 'tutor_learning_area_before_course_info', array( $this, 'add_certificate_preview' ) );

		add_filter( 'tutor_dashboard_account_pages', array( $this, 'add_account_certificate_page' ) );
	}

	/**
	 * Add download button in course loop.
	 *
	 * @since 4.0.0
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 *
	 * @return string
	 */
	public function course_loop_btn( $html, $course_id ) {
		return $this->download_btn_in_archive( $html, $course_id );
	}

	/**
	 * Render download button in enrolled course action btn.
	 *
	 * @since 4.0.0
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function render_course_action_btn( $course_id ) {
		$btn = $this->download_btn_in_archive( '', $course_id, 'tutor-btn tutor-btn-primary tutor-btn-x-small' );
		if ( ! empty( $btn ) ) {
			echo $btn; //phpcs:ignore
		}
	}

	/**
	 * Add Nav Item to tutor subpage.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $nav_items the array of nav items.
	 * @param string $base_url the base url.
	 *
	 * @return array
	 */
	public function add_subpage_nav_item( $nav_items, $base_url ): array {
		global $tutor_course_id;
		if ( ! $tutor_course_id ) {
			return $nav_items;
		}

		if ( $this->disable_certificate_for_individual_courses( $tutor_course_id ) ) {
			return $nav_items;
		}

		if ( ! $this->has_course_certificate_template( $tutor_course_id ) ) {
			return $nav_items;
		}

		/**
		 * Prevent certificate access with a locked modal until the course is completed.
		 *
		 * @since 4.0.0
		 */
		if ( ! tutor_utils()->is_completed_course( $tutor_course_id ) ) {
			tutor_load_template_from_custom_path( TUTOR_CERT()->path . 'views/certificate-locked-modal.php' );
			$nav_items['certificate'] = array(
				'title'   => __( 'Certificate', 'tutor-pro' ),
				'icon'    => Icon::CERTIFICATE_2,
				'url'     => '#',
				'onclick' => 'TutorCore.modal.showModal("certificate-modal")',
				'locked'  => true,
			);

			return $nav_items;
		}

		$url_params   = array( 'subpage' => 'certificate' );
		$user_id      = get_current_user_id();
		$is_completed = tutor_utils()->is_completed_course( $tutor_course_id, $user_id, false );
		if ( $is_completed ) {
			$url_params['cert_hash'] = $is_completed->completed_hash;
		}

		$nav_items['certificate'] = array(
			'title'    => __( 'Certificate', 'tutor-pro' ),
			'icon'     => Icon::CERTIFICATE_2,
			'url'      => UrlHelper::add_query_params( $base_url, $url_params ),
			'template' => TUTOR_CERT()->path . 'views/learning-area-certificate.php',
		);

		return $nav_items;
	}

	/**
	 * Certificate Showcase
	 *
	 * @since 2.2.3
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function add_certificate_showcase( $course_id ) {
		$is_enabled = (bool) tutor_utils()->get_option( 'enable_certificate_showcase', false );
		if ( ! $is_enabled ) {
			return;
		}

		if ( self::disable_certificate_for_individual_courses( $course_id ) ) {
			return;
		}

		$template_key = get_post_meta( $course_id, self::$template_meta_key, true );
		if ( in_array( $template_key, array( '', 'none', 'off' ) ) ) {
			return;
		}

		$templates = $this->get_templates();
		if ( ! isset( $templates[ $template_key ] ) ) {
			return;
		}

		$template = $templates[ $template_key ];
		?>

		<div id="tutor-certificate-showcase" class="tutor-my-52">
			<div class="tutor-cs-text">
				<div>
					<h3 class="tutor-course-details-widget-title tutor-fs-5 tutor-fw-bold tutor-color-black tutor-mb-8">
						<?php echo esc_html( tutor_utils()->get_option( 'certificate_showcase_title', '' ) ); ?>
					</h3>
					<p><?php echo esc_html( tutor_utils()->get_option( 'certificate_showcase_desc', '' ) ); ?></p>
				</div>
			</div>
			<div class="tutor-cs-wrapper">
				<div class="tutor-cs-image-wrapper">
					<img src="<?php echo esc_url( $template['preview_src'] ); ?>" alt="selected template">
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Add button to certificate page edit mode.
	 *
	 * @since 2.1.7
	 *
	 * @return void
	 */
	public function add_button_to_certificate_edit_page() {
		global $post;
		$certificate_page_id = (int) tutor_utils()->get_option( 'tutor_certificate_page' );

		if ( isset( $post ) && $certificate_page_id === $post->ID ) {
			$hash = get_transient( 'tutor_cert_hash' );
			$url  = '#';
			if ( false !== $hash ) {
				$url = home_url( $post->post_name . '?cert_hash=' . $hash );
				?>
				<script type="text/javascript">
					jQuery(document).ready( function($) {
						setTimeout(function(){
							let btn = '<a href="<?php echo esc_url( $url ); ?>" class="button" target="_blank">'. __( 'View Certificate', 'tutor-pro' ).'</a>';
							$('div.edit-post-header__settings').prepend( btn );
						})
					});
				</script>
				<?php
			}
		}
	}

	/**
	 * Show view certificate button in analytics/report page.
	 *
	 * @since 3.9.5
	 *
	 * @param int $course_id the course id.
	 * @param int $user_id the user id.
	 *
	 * @return void
	 */
	public function download_btn_in_report( $course_id = 0, $user_id = 0 ) {
		$html = '';

		if ( ! $this->has_course_certificate( $course_id, array( $user_id ) ) ) {
			return;
		}

		$certificate_url = $this->get_certificate( $course_id, false, $user_id );
		ob_start();
		include TUTOR_CERT()->path . 'views/report-analytics-courses.php';
		$html = ob_get_clean();

		echo $html; //phpcs:ignore -- sanitized html
	}

	/**
	 * Condition on showing course certificate.
	 *
	 * @since 3.9.5
	 *
	 * @param integer $course_id the course id.
	 * @param array   $user_ids   the user id.
	 *
	 * @return bool
	 */
	public function has_course_certificate( $course_id = 0, $user_ids = array() ) {
		if ( ! count( $user_ids ) ) {
			return false;
		}

		$has_certificate = false;

		foreach ( $user_ids as $user_id ) {
			$is_completed_course = tutor_utils()->is_completed_course( $course_id, $user_id );

			if ( ! $is_completed_course ) {
				continue;
			}

			if ( self::disable_certificate_for_individual_courses( $course_id, $user_id ) ) {
				continue;
			}

			$completed  = $this->completed_course( $is_completed_course->completed_hash );
			$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
			if ( ! $has_access ) {
				continue;
			}

			if ( ! $this->has_course_certificate_template( $course_id ) ) {
				continue;
			}

			$has_certificate = true;
		}

		return $has_certificate;
	}

	/**
	 * Download button in archive
	 *
	 * @param string $html html.
	 * @param int    $course_id course id.
	 * @param string $btn_class button class.
	 *
	 * @return string
	 */
	public function download_btn_in_archive( $html, $course_id, $btn_class = '' ) {
		$btn_class           = empty( $btn_class ) ? 'tutor-btn tutor-btn-outline-primary tutor-btn-md tutor-btn-block' : $btn_class;
		$is_completed_course = tutor_utils()->is_completed_course( $course_id );
		if ( $is_completed_course ) {
			if ( self::disable_certificate_for_individual_courses( $course_id ) ) {
				return $html;
			}

			$completed  = $this->completed_course( $is_completed_course->completed_hash );
			$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
			if ( ! $has_access ) {
				return $html;
			}

			//phpcs:ignore
			if ( $this->has_course_certificate_template( $course_id )) {
				$certificate_url = $this->get_certificate( $course_id );
				$html            = '<a href="' . $certificate_url . '" class="' . $btn_class . '">' . __( 'Download Certificate', 'tutor-pro' ) . '</a>';
				return $html;
			}
		}

		return $html;
	}

	/**
	 * Get certificate public URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cert_hash unique hash.
	 * @return string
	 */
	public function tutor_certificate_public_url( $cert_hash ) {
		$url     = '#';
		$page_id = (int) tutor_utils()->get_option( 'tutor_certificate_page' );

		if ( ! in_array( $page_id, array( 0, -1 ) ) ) {
			$page = get_post( $page_id );
			$url  = home_url() . DIRECTORY_SEPARATOR . $page->post_name . '?cert_hash=' . $cert_hash;
		}

		return $url;
	}

	/**
	 * Load field scripts.
	 *
	 * @return void
	 */
	public function load_field_scripts() {
		if ( 'tutor_settings' === Input::get( 'page', '' ) ) {
			wp_enqueue_style( 'tutor-pro-certificate-field-css', TUTOR_CERT()->url . 'assets/css/certificate-field.css', array(), TUTOR_PRO_VERSION );
		}
	}

	/**
	 * Get course certificate list.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function ajax_course_certificate_list() {
		if ( ! tutor_utils()->is_nonce_verified() ) {
			$this->json_response( tutor_utils()->error_message( 'nonce' ), null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$has_access_role = User::has_any_role( array( User::ADMIN, User::INSTRUCTOR ) );

		if ( ! $has_access_role ) {
			$this->json_response(
				tutor_utils()->error_message( HttpHelper::STATUS_UNAUTHORIZED ),
				null,
				HttpHelper::STATUS_UNAUTHORIZED
			);
		}

		$templates = $this->get_templates();
		$data      = array_values( $templates );

		$this->json_response(
			__( 'Certificate list fetched successfully!', 'tutor-pro' ),
			$data
		);
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param array $data response data.
	 *
	 * @return array
	 */
	public function extend_course_details_response( array $data ) {
		$course_id    = $data['ID'];
		$template_key = get_post_meta( $course_id, self::$template_meta_key, true );
		$template_key = $template_key ? $template_key : 'default';

		$templates = $this->get_templates( false, true );

		$hide_default_certificates_for_instructors = (bool) tutor_utils()->get_option( 'hide_default_certificates_for_instructors', false );
		if ( User::is_only_instructor() && $hide_default_certificates_for_instructors ) {
			$templates = array_filter(
				$templates,
				function ( $template ) use ( $template_key ) {
					return ( $template['key'] === $template_key ) || ! isset( $template['is_default'] );
				}
			);
		}

		$templates = array_values( $templates );

		$data['course_certificate_template']   = $template_key;
		$data['course_certificates_templates'] = $templates;

		return $data;
	}

	/**
	 * Save certificate template meta.
	 *
	 * @param int $post_id post id.
	 *
	 * @return void
	 */
	public function save_certificate_template_meta( $post_id ) {
		if ( Input::has( self::$template_meta_key ) ) {
			update_post_meta( $post_id, self::$template_meta_key, Input::post( self::$template_meta_key ) );
		}
	}

	/**
	 * Extend course details response
	 *
	 * @since 3.0.0
	 *
	 * @param object $data response data.
	 *
	 * @return object
	 */
	public function extend_bundle_details_response( $data ) {
		$course_id    = $data->ID;
		$template_key = get_post_meta( $course_id, self::$template_meta_key, true );
		$template_key = $template_key ? $template_key : 'none';

		$templates = $this->get_templates( false, true );

		$hide_default_certificates_for_instructors = (bool) tutor_utils()->get_option( 'hide_default_certificates_for_instructors', false );
		if ( User::is_only_instructor() && $hide_default_certificates_for_instructors ) {
			$templates = array_filter(
				$templates,
				function ( $template ) use ( $template_key ) {
					return ( $template['key'] === $template_key ) || ! isset( $template['is_default'] );
				}
			);
		}

		$templates = array_values( $templates );

		$data->certificate_for_individual_courses = get_post_meta( $course_id, self::$bundle_allow_individual_certificates_meta_key, true );
		$data->course_certificate_template        = $template_key;
		$data->course_certificates_templates      = $templates;

		return $data;
	}

	/**
	 * Save bundle certificate data.
	 *
	 * @since 3.9.0
	 *
	 * @param int $post_id post id.
	 *
	 * @return void
	 */
	public function save_bundle_certificate_data( $post_id ) {
		if ( Input::has( self::$template_meta_key ) ) {
			update_post_meta( $post_id, self::$template_meta_key, Input::post( self::$template_meta_key ) );
		}

		if ( Input::has( self::$bundle_allow_individual_certificates_meta_key ) ) {
			update_post_meta( $post_id, self::$bundle_allow_individual_certificates_meta_key, Input::post( self::$bundle_allow_individual_certificates_meta_key ) );
		}
	}

	/**
	 * Disable certificate for individual courses
	 *
	 * @since 3.9.0
	 * @since 3.9.5 param $user_id added for getting enrolled student id
	 * when viewing certificate from report / analytics.
	 *
	 * @param int $course_id course id.
	 * @param int $user_id   the user id.
	 *
	 * @return bool
	 */
	public function disable_certificate_for_individual_courses( $course_id, $user_id = 0 ) {
		if ( ! tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
			return false;
		}

		if ( CourseBundle::POST_TYPE === get_post_type( $course_id ) ) {
			return false;
		}

		$bundle_id = BundleModel::get_enrolled_bundle_id_by_course( $course_id, tutor_utils()->get_user_id( $user_id ) );
		if ( $bundle_id ) {
			$certificate_for_individual_courses = get_post_meta( $bundle_id, self::$bundle_allow_individual_certificates_meta_key, true );
			if ( '0' === $certificate_for_individual_courses ) {
				return true;
			}
		}
	}

	/**
	 * Load Script
	 *
	 * @return void
	 */
	public function load_script() {
		if ( ! empty( Input::get( 'cert_hash', '' ) ) ) {
			wp_enqueue_script( 'html2canvas', tutor_pro()->url . 'assets/lib/html2canvas/html2canvas.min.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_script( 'jsPDf', tutor_pro()->url . 'assets/lib/jspdf/jspdf.umd.min.js', array( 'jquery' ), TUTOR_PRO_VERSION, true );
			wp_enqueue_script( 'tutor-social-share', tutor()->url . 'assets/lib/SocialShare/SocialShare.min.js', array( 'jquery' ), TUTOR_VERSION, true );

			wp_enqueue_style( 'tutor-pro-certificate', TUTOR_CERT()->url . 'assets/css/certificate.css', array(), TUTOR_PRO_VERSION );
			wp_enqueue_script( 'tutor-pro-certificate', TUTOR_CERT()->url . 'assets/js/certificate.js', array( 'jquery', 'wp-i18n', 'html2canvas', 'jsPDf', 'tutor-social-share' ), TUTOR_PRO_VERSION, true );
		}

		if ( self::is_certificate_verification_page() ) {
			wp_enqueue_script( 'tutor-certificate-verification', TUTOR_CERT()->url . 'assets/js/certificate-verification.js', array(), TUTOR_PRO_VERSION, true );
			wp_enqueue_style( 'tutor-certificate-verification', TUTOR_CERT()->url . 'assets/css/certificate-verification.css', array(), TUTOR_PRO_VERSION );
		}

		if ( tutor_utils()->is_dashboard_page( 'account/certificates' ) ) {
			wp_enqueue_style( 'tutor-user-certificates', TUTOR_CERT()->url . 'assets/css/user-certificates.css', array(), TUTOR_PRO_VERSION );
		}
	}

	/**
	 * Get Fonts
	 *
	 * @return void
	 */
	public function get_fonts() {
		if ( 'get_fonts' !== Input::get( 'tutor_action' ) ) {
			return;
		}

		$url_base  = tutor_pro()->url . 'addons/tutor-certificate/assets/fonts/';
		$path_base = $this->cross_platform_path( dirname( __DIR__ ) . '/assets/css/' );

		$default_files = $path_base . 'font-loader.css';
		$default_fonts = file_get_contents( $default_files );

		$font_faces = str_replace( './fonts/', $url_base, $default_fonts );

		// Now load template fonts.
		$this->prepare_template_data( Input::get( 'course_id' ) );
		$font_css = $this->template['path'] . 'font.css';
		if ( file_exists( $font_css ) ) {
			$faces       = file_get_contents( $font_css );
			$faces       = str_replace( './fonts/', $this->template['url'] . 'fonts/', $faces );
			$font_faces .= $faces;
		}

		exit( $font_faces );//phpcs:ignore
	}

	/**
	 * Send Certificate HTML
	 *
	 * @return void
	 */
	public function send_certificate_html() {
		tutor_utils()->checking_nonce();
		$course_id = Input::post( 'course_id', '' );
		$cert_hash = Input::post( 'certificate_hash' );

		if ( $course_id && is_numeric( $course_id ) ) {

			$this->prepare_template_data( $course_id );
			$completed = $cert_hash ? $this->completed_course( $cert_hash ) : false;

			if ( strpos( $this->template['key'], 'tutor_cb_' ) === 0 ) {
				$template_id = preg_replace( '/\D/', '', $this->template['key'] );
				wp_send_json_success(
					array(
						'certificate_builder_url' => apply_filters(
							'tutor_certificate_builder_url',
							$template_id,
							array(
								'cert_hash'   => $cert_hash,
								'course_id'   => $course_id,
								'orientation' => $this->template['orientation'],
							)
						),
					)
				);
				exit;
			}

			// Get certificate html.
			$content = $this->generate_certificate( $course_id, $completed );
			wp_send_json_success( array( 'html' => $content ) );
		}

		wp_send_json_error( array( 'message' => __( 'Invalid Course ID', 'tutor-pro' ) ) );
	}

	/**
	 * Course Platform Path
	 *
	 * @param string $path path.
	 *
	 * @return string
	 */
	private function cross_platform_path( $path ) {
		$path = str_replace( '/', DIRECTORY_SEPARATOR, $path );
		$path = str_replace( '\\', DIRECTORY_SEPARATOR, $path );

		return $path;
	}

	/**
	 * Prepare template data.
	 *
	 * @param int     $course_id course id.
	 * @param boolean $check_if_none check has or not.
	 *
	 * @return mixed
	 */
	private function prepare_template_data( $course_id, $check_if_none = false ) {
		if ( ! $this->template ) {

			// Get from settings. Set default one if not set somehow.
			$template               = tutor_utils()->get_option( 'certificate_template' );
			! $template ? $template = 'default' : 0;

			$global_template = $template;

			// Assign from course meta if custom one chosen.
			$course_template = get_post_meta( $course_id, self::$template_meta_key, true );

			// Get the selected template.
			$template_arg                      = array();
			$template_arg[]                    = $template;
			$course_template ? $template_arg[] = $course_template : 0;
			$templates                         = $this->get_templates( false, false, $template_arg );

			( $course_template && isset( $templates[ $course_template ] ) ) ? $template = $course_template : 0;

			// If explicitly set as none.
			if ( $check_if_none && in_array( $course_template, array( 'none', 'off' ) ) ) {
				return false;
			}

			// Make sure not to use templates from builder if the plugin is not active.
			if ( strpos( $template, 'tutor_cb_' ) === 0 && ! tutor_utils()->is_plugin_active( 'tutor-lms-certificate-builder/tutor-lms-certificate-builder.php' ) ) {
				// Use default if builder is not active somehow.
				$template = $global_template;
			}

			$this->template = tutor_utils()->avalue_dot( $template, $templates );
		}
	}

	/**
	 * Store certificate image
	 *
	 * @return void
	 */
	public function store_certificate_image() {
		tutor_utils()->checking_nonce();
		// Collect post data.
		$hash      = Input::post( 'cert_hash', '' );
		$completed = is_string( $hash ) ? $this->completed_course( $hash ) : null;

		// Check if the course is complete.
		if ( ! $completed ) {
			wp_send_json_error( array( 'message' => __( 'Course not yet completed', 'tutor-pro' ) ) );
			return;
		}

		// Check if valid image.
		//phpcs:ignore
		if ( ! isset( $_FILES['certificate_image'] ) || $_FILES['certificate_image']['error'] || $_FILES['certificate_image']['type'] !== 'image/jpeg' ) 
		{
			wp_send_json_error( array( 'message' => __( 'Certificate Image Error', 'tutor-pro' ) ) );
		}

		// The dir from outside of the filter hook. Otherwise infinity loop will coccur.
		$certificates_dir = wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . $this->certificates_dir_name;
		$rand_string      = substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( 10 / strlen( $x ) ) ) ), 1, 10 );//phpcs:ignore

		// Store new file.
		wp_mkdir_p( $certificates_dir );
		$file_dest = $certificates_dir . DIRECTORY_SEPARATOR . $rand_string . '-' . $hash . '.jpg';
		move_uploaded_file( $_FILES['certificate_image']['tmp_name'], $file_dest );//phpcs:ignore

		// Delete old one.
		$old_rand_string = get_comment_meta( $completed->certificate_id, $this->certificate_stored_key, true );
		$old_path        = $this->cross_platform_path( $certificates_dir . '/' . $old_rand_string . '-' . $hash . '.jpg' );
		file_exists( $old_path ) ? unlink( $old_path ) : 0;

		// Update new.
		update_comment_meta( $completed->certificate_id, $this->certificate_stored_key, $rand_string );

		wp_send_json_success();
	}

	/**
	 * View Certificate
	 *
	 * @since 1.5.1
	 *
	 * @param string $template template path.
	 *
	 * @return string
	 */
	public function view_certificate( $template ) {

		$cert_hash = Input::get( 'cert_hash' );

		if ( ! $cert_hash || ! empty( Input::get( 'tutor_action', '' ) ) ) {
			return $template;
		}

		$completed = $this->completed_course( $cert_hash );
		if ( ! is_object( $completed ) || ! property_exists( $completed, 'completed_user_id' ) ) {
			return $template;
		}

		$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
		if ( ! $has_access ) {
			return $template;
		}

		/**
		 * Load the certificate in WordPress native page.
		 *
		 * @since 2.1.7
		 */
		global $post;

		if ( isset( $post ) && 'page' === $post->post_type ) {
			$certificate_page_id = (int) tutor_utils()->get_option( 'tutor_certificate_page' );
			if ( $post->ID === $certificate_page_id ) {
				set_transient( 'tutor_cert_hash', $cert_hash );
				return TUTOR_CERT()->path . '/views/single-certificate.php';
			}
		}

		return $template;
	}

	/**
	 * Get Signature URL
	 *
	 * @param int   $instructor_id instructor id.
	 * @param mixed $use_default default.
	 *
	 * @return string
	 */
	public function get_signature_url( $instructor_id, $use_default = null ) {

		// Get personal signature first.
		$custom_signature    = ( new Instructor_Signature( false ) )->get_instructor_signature( $instructor_id );
		$signature_image_url = $custom_signature['url'];

		// Set default signature from global setting if personal one is not set.
		if ( ! $signature_image_url ) {
			// Get default ID.
			$default_sinature_id = (int) tutor_utils()->get_option( 'tutor_cert_signature_image_id' );

			if ( ! $default_sinature_id && false === $use_default ) {
				return null;
			}

			// Assign default image from plugin file system if even global one is not set yet.
			$signature_image_url = $default_sinature_id ?
										wp_get_attachment_url( $default_sinature_id ) :
										TUTOR_CERT()->url . 'assets/images/signature.png';
		}

		return $signature_image_url;
	}

	/**
	 * Generate Certificate.
	 *
	 * @param int     $course_id course id.
	 * @param boolean $completed completed.
	 *
	 * @return mixed
	 */
	public function generate_certificate( $course_id, $completed = false ) {
		$duration         = get_post_meta( $course_id, '_course_duration', true );
		$duration_hours   = (int) tutor_utils()->avalue_dot( 'hours', $duration );
		$duration_minutes = (int) tutor_utils()->avalue_dot( 'minutes', $duration );
		$course           = get_post( $course_id );
		$completed        = $completed ? $completed : tutor_utils()->is_completed_course( $course_id );
		$user             = $completed ? get_userdata( $completed->completed_user_id ) : wp_get_current_user();
		$completed_date   = '';
		if ( $completed ) {
			$wp_date_format = get_option( 'date_format' );
			$completed_date = gmdate( $wp_date_format, strtotime( $completed->completion_date ) );

			// Translate month name.
			$converter      = function ( $matches ) {
				$month = __( $matches[0] );//phpcs:ignore

				// Make first letter uppercase if it's not unicode character.
				strlen( $month ) == strlen( utf8_decode( $month ) ) ? $month = ucfirst( $month ) : 0;

				return $month;
			};
			$completed_date = preg_replace_callback( '/[a-z]+/i', $converter, $completed_date );

			// Translate day and year digits.
			$completed_date = preg_replace_callback(
				'/[0-9]/',
				function ( $m ) {
					return __( $m[0] );//phpcs:ignore
				},
				$completed_date
			);
		}

		// Prepare signature image.
		$signature_image_url = $this->get_signature_url( $course->post_author );

		// Include instructor name if enabled.
		$enabled = tutor_utils()->get_option( 'show_instructor_name_on_certificate', false );

		if ( $enabled ) {

			$user_info       = get_userdata( $course->post_author );
			$instructor_name = $user_info ? $user_info->display_name : '';

			add_filter(
				'tutor_cert_authorised_name',
				function ( $authorized ) use ( $instructor_name ) {
					$suthorized = is_string( $authorized ) ? trim( $authorized ) : '';
					$authorized = $instructor_name . ( strlen( $authorized ) ? ', ' : '' ) . $authorized;

					return $authorized;
				}
			);
		}

		// Generate duration text.
		$hour_text = '';
		$min_text  = '';

		if ( $duration_hours ) {
			/* translators: %s: hour number */
			$hour_text = sprintf( _n( '%s hour', '%s hours', $duration_hours, 'tutor-pro' ), number_format_i18n( $duration_hours ) );
		}
		if ( $duration_minutes ) {
			/* translators: %s: minute number */
			$min_text = sprintf( _n( '%s minute', '%s minutes', $duration_minutes, 'tutor-pro' ), number_format_i18n( $duration_minutes ) );
		}

		$duration_text = $hour_text . ' ' . $min_text;

		ob_start();
		include $this->template['path'] . 'certificate.php';
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * PDF style
	 *
	 * @return void
	 */
	public function pdf_style() {
		$css = $this->template['path'] . 'pdf.css';

		ob_start();
		if ( file_exists( $css ) ) {
			include $css;
		}
		$css = ob_get_clean();
		$css = apply_filters( 'tutor_cer_css', $css, $this );

		echo $css;//phpcs:ignore
	}

	/**
	 * Download button for certificate
	 *
	 * @return void
	 */
	public function certificate_download_btn() {
		$course_id = get_the_ID();

		if ( self::disable_certificate_for_individual_courses( $course_id ) ) {
			echo '<div class="tutor-fs-7 tutor-color-subdued tutor-bg-white tutor-border tutor-radius-6 tutor-p-12 tutor-mb-16">' .
				esc_html__( 'This course does not include an individual certificate. Complete the full bundle to earn your certificate.', 'tutor-pro' ) .
			'</div>';
			return;
		}

		$certificate = $this->get_certificate( $course_id, true );

		if ( ! $certificate || $this->prepare_template_data( $course_id, true ) === false ) {
			// No certificate assigned or course not completed.
			return;
		}

		// It has $certificate_hash, $certificate_url.
		extract( $certificate );//phpcs:ignore

		ob_start();
		include TUTOR_CERT()->path . 'views/lesson-menu-after.php';
		$content = ob_get_clean();

		echo $content;//phpcs:ignore
	}

	/**
	 * Get templates
	 *
	 * @param boolean $add_none add none.
	 * @param boolean $include_admins include admins.
	 * @param array   $template_in template in.
	 *
	 * @return array
	 */
	public function get_templates( $add_none = false, $include_admins = false, $template_in = array() ) {
		$templates = array(
			'default'     => array(
				'name'        => 'Default',
				'orientation' => 'landscape',
			),
			'template_1'  => array(
				'name'        => 'Abstract Landscape',
				'orientation' => 'landscape',
			),
			'template_2'  => array(
				'name'        => 'Abstract Portrait',
				'orientation' => 'portrait',
			),
			'template_3'  => array(
				'name'        => 'Decorative Landscape',
				'orientation' => 'landscape',
			),
			'template_4'  => array(
				'name'        => 'Decorative Portrait',
				'orientation' => 'portrait',
			),
			'template_5'  => array(
				'name'        => 'Geometric Landscape',
				'orientation' => 'landscape',
			),
			'template_6'  => array(
				'name'        => 'Geometric Portrait',
				'orientation' => 'portrait',
			),
			'template_7'  => array(
				'name'        => 'Minimal Landscape',
				'orientation' => 'landscape',
			),
			'template_8'  => array(
				'name'        => 'Minimal Portrait',
				'orientation' => 'portrait',
			),
			'template_9'  => array(
				'name'        => 'Floating Landscape',
				'orientation' => 'landscape',
			),
			'template_10' => array(
				'name'        => 'Floating Portrait',
				'orientation' => 'portrait',
			),
			'template_11' => array(
				'name'        => 'Stripe Landscape',
				'orientation' => 'landscape',
			),
			'template_12' => array(
				'name'        => 'Stripe Portrait',
				'orientation' => 'portrait',
			),
		);

		foreach ( $templates as $key => $template ) {

			$path = trailingslashit( TUTOR_CERT()->path . 'templates/' . $key );
			$url  = trailingslashit( TUTOR_CERT()->url . 'templates/' . $key );

			$templates[ $key ]['is_default']     = true;
			$templates[ $key ]['path']           = $path;
			$templates[ $key ]['url']            = $url;
			$templates[ $key ]['preview_src']    = self::$certificate_img_url_base . $key . '/preview.png';
			$templates[ $key ]['background_src'] = self::$certificate_img_url_base . $key . '/background.png';
		}

		$filtered = apply_filters( 'tutor_certificate_templates', $templates, $include_admins, $template_in );

		// Customizer plugin compatibility.
		foreach ( $filtered as $index => $values ) {

			$filtered[ $index ]['key'] = $index;

			if ( ! array_key_exists( 'background_src', $values ) ) {
				$filtered[ $index ]['preview_src']    = $values['url'] . 'preview.png';
				$filtered[ $index ]['background_src'] = $values['url'] . 'preview.png';
			}
		}

		if ( $add_none ) {
			// This block is only for course editor.
			$filtered = array_merge(
				array(
					'none' => array(
						'name'           => 'none',
						'orientation'    => 'landscape',
						'path'           => '',
						'url'            => '',
						'preview_src'    => TUTOR_CERT()->url . 'assets/images/certificate-none.svg',
						'background_src' => '',
					),
					'off'  => array(
						'name'           => 'off',
						'orientation'    => 'portrait',
						'path'           => '',
						'url'            => '',
						'preview_src'    => TUTOR_CERT()->url . 'assets/images/certificate-none-portrait.svg',
						'background_src' => '',
					),
				),
				$filtered
			);
		}

		return $filtered;
	}

	/**
	 * Get course certificate template
	 *
	 * @since 3.8.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array
	 */
	public function get_course_certificate_template( $course_id ) {
		$this->prepare_template_data( $course_id );
		return $this->template;
	}

	/**
	 * Get completed course data
	 *
	 * @since 1.5.1
	 *
	 * @param mixed $cert_hash certificate hash.
	 * @param mixed $data data.
	 *
	 * @return mixed
	 */
	public function completed_course( $cert_hash, $data = false ) {
		global $wpdb;
		$is_completed = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT comment_ID as certificate_id,
					comment_post_ID as course_id,
					user_id as completed_user_id,
					comment_date as completion_date,
					comment_content as completed_hash
			FROM	$wpdb->comments
			WHERE 	comment_agent = %s
					AND comment_type = %s
					AND comment_content = %s",
				'TutorLMSPlugin',
				'course_completed',
				$cert_hash
			)
		);

		return ! empty( $is_completed ) ? $is_completed : $data;
	}


	/**
	 * Add meta tags for the certificate page
	 *
	 * @since 2.4.0
	 *
	 * @return void
	 */
	public function certificate_header_content() {
		$cert_hash = Input::get( 'cert_hash' );

		if ( ! $cert_hash || ! empty( Input::get( 'tutor_action', '' ) ) ) {
			return;
		}

		$completed = $this->completed_course( $cert_hash );
		if ( ! is_object( $completed ) || ! property_exists( $completed, 'completed_user_id' ) ) {
			return;
		}

		$course     = get_post( $completed->course_id );
		$upload_dir = wp_upload_dir();

		$certificate_dir_url  = $upload_dir['baseurl'] . '/' . $this->certificates_dir_name;
		$certificate_dir_path = $upload_dir['basedir'] . '/' . $this->certificates_dir_name;

		$rand_string = get_comment_meta( $completed->certificate_id, $this->certificate_stored_key, true );

		$cert_path = '/' . $rand_string . '-' . $cert_hash . '.jpg';
		$cert_file = $certificate_dir_path . $cert_path;

		! file_exists( $cert_file ) ? $cert_file = null : 0;

		$generate_cert              = ! $cert_file || ( 1 == Input::get( 'regenerate' ) );
		$generate_cert ? $cert_file = null : 0;

		$cert_img = $generate_cert ? get_admin_url() . 'images/loading.gif' : $certificate_dir_url . $cert_path;

		$title       = __( 'Course Completion Certificate', 'tutor-pro' );
		$description = __( 'My course completion certificate for', 'tutor-pro' ) . ' ' . $course->post_title;

		$og_url = get_page_link();
		if ( is_singular() && $cert_hash ) {
			// Get the current page's URL.
			$og_url = trailingslashit( $og_url ) . '?cert_hash=' . $cert_hash;
		}

		echo '
		<meta property="og:url" content="' . esc_url( $og_url ) . '" />
		<meta property="og:title" content="' . esc_attr( $title ) . '"/>
		<meta property="og:description" content="' . esc_attr( $description ) . '"/>
		<meta property="og:image" content="' . esc_url( $cert_img ) . '"/>
		<meta name="twitter:title" content="' . esc_attr( $title ) . '"/>
		<meta name="twitter:description" content="' . esc_attr( $description ) . '"/>
		<meta name="twitter:image" content="' . esc_url( $cert_img ) . '"/>
		<meta name="twitter:card" content="summary_large_image">
		';
	}

	/**
	 * Remove yoast seo tags if it is certificate page
	 *
	 * @since 2.4.0
	 *
	 * @param string $str og tag.
	 *
	 * @return string
	 */
	public function remove_yoast_seo_og_tags( $str ) {
		$cert_hash = Input::get( 'cert_hash' );
		if ( ! $cert_hash || ! empty( Input::get( 'tutor_action', '' ) ) ) {
			return $str;
		}
		return '';
	}

	/**
	 * Get certificate
	 *
	 * @since 3.9.3 param $user_id added.
	 *
	 * @param int     $course_id course id.
	 * @param boolean $full full.
	 * @param int     $user_id the user id.
	 *
	 * @return mixed
	 */
	public function get_certificate( $course_id, $full = false, $user_id = 0 ) {

		$is_completed = tutor_utils()->is_completed_course( $course_id, $user_id, false );
		$url          = $is_completed ? apply_filters( 'tutor_certificate_public_url', $is_completed->completed_hash ) : null;

		if ( $full && $is_completed ) {
			return array(
				'certificate_url'  => $url,
				'certificate_hash' => $is_completed->completed_hash,
			);
		}

		return $url;
	}

	/**
	 * Check certificate has template.
	 *
	 * @param int $course_id course id.
	 *
	 * @return boolean
	 */
	public function has_course_certificate_template( $course_id ) {
		$course_template = get_post_meta( $course_id, self::$template_meta_key, true );

		if ( in_array( $course_template, array( 'none', 'off' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Show course certificate.
	 *
	 * @param array $meta meta.
	 * @param int   $course_id course id.
	 *
	 * @return array.
	 */
	public function show_course_has_certificate( $meta, $course_id ) {
		if ( $this->disable_certificate_for_individual_courses( $course_id ) ) {
			return $meta;
		}
		if ( $this->has_course_certificate_template( $course_id ) ) {
			$is_completed_course = tutor_utils()->is_completed_course();
			if ( is_object( $is_completed_course ) && isset( $is_completed_course->completed_hash ) ) {
				$completed  = $this->completed_course( $is_completed_course->completed_hash );
				$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
				if ( ! $has_access ) {
					return $meta;
				}
			}

			$meta[] = array(
				'list_class' => 'tutor-course-certificate-meta',
				'icon_class' => 'tutor-icon-ribbon-o',
				'label'      => __( 'Certificate', 'tutor-pro' ),
				'value'      => __( 'Certificate of completion', 'tutor-pro' ),
			);
		}

		return $meta;
	}

	/**
	 * Draft certificate created callback.
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function draft_course_created( $course_id ) {
		$hide_default_certificates_for_instructors = (bool) tutor_utils()->get_option( 'hide_default_certificates_for_instructors', false );
		if ( User::is_only_instructor() && $hide_default_certificates_for_instructors ) {
			update_post_meta( $course_id, 'tutor_course_certificate_template', 'none' );
		}
	}

	/**
	 * Check if the current page is certificate page.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function is_certificate_page(): bool {
		return is_singular()
				&& is_page( tutor_utils()->get_option( self::OPTION_CERTIFICATE_PAGE ) )
				&& Input::has( 'cert_hash', Input::GET_REQUEST );
	}

	/**
	 * Check if the current page is certificate verification page.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function is_certificate_verification_page(): bool {
		return is_singular() && is_page( tutor_utils()->get_option( self::OPTION_CERTIFICATE_VERIFY_PAGE ) );
	}

	/**
	 * Filter tutor should load legacy scripts.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $load load.
	 *
	 * @return bool
	 */
	public function filter_should_load_legacy_scripts( bool $load ): bool {
		if ( self::is_certificate_page() || self::is_certificate_verification_page() ) {
			return false;
		}

		return $load;
	}

	/**
	 * Load certificate verification page.
	 *
	 * @since 4.0.0
	 *
	 * @param string $template template.
	 *
	 * @return string
	 */
	public function load_certificate_verification_page( string $template ): string {
		if ( self::is_certificate_verification_page() ) {
			return TUTOR_CERT()->path . '/views/single-certificate-verification.php';
		}

		return $template;
	}

	/**
	 * Verify certificate with AJAX
	 * Add download certificate button to enrollment action dropdown.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $items The items.
	 * @param int    $enrollment_id id The enrollment id.
	 * @param int    $course_id id The course id.
	 * @param int    $student_id id The student id.
	 * @param int    $progress The progress.
	 * @param string $context The context.
	 *
	 * @return array
	 */
	public function add_download_certificate_button( $items, $enrollment_id, $course_id, $student_id, $progress, $context ) {
		$modal_id       = 'tutor-download-certificate-' . $enrollment_id;
		$modal_template = 'dashboard' === $context
							? TUTOR_CERT()->path . 'views/modals/download-certificate-dashboard-modal.php'
							: TUTOR_CERT()->path . 'views/modals/download-certificate-modal.php';

		$items['download_certificate'] = array(
			'label' => __( 'Download Certificate', 'tutor-pro' ),
			'icon'  => 'dashboard' === $context ? Icon::CERTIFICATE_2 : 'tutor-icon-certificate-landscape',
			'modal' => array(
				'id'       => $modal_id,
				'template' => $modal_template,
				'data'     => array(
					'modal_id'   => $modal_id,
					'course_id'  => $course_id,
					'student_id' => $student_id,
				),
			),
		);

		return $items;
	}

	/**
	 * Ajax download course certificate.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function ajax_verify_certificate() {
		tutor_utils()->check_nonce();

		$certificate_id = Input::post( 'certificate_id' );
		if ( empty( $certificate_id ) ) {
			$this->response_bad_request( __( 'Certificate ID is required', 'tutor-pro' ) );
		}

		$certificate = $this->completed_course( $certificate_id );
		if ( empty( $certificate ) ) {
			$this->response_bad_request( __( 'Certificate not found', 'tutor-pro' ) );
		}

		$certificate_url = $this->tutor_certificate_public_url( $certificate_id );

		$this->json_response(
			__( 'Certificate verified successfully', 'tutor-pro' ),
			array(
				'certificate_url' => $certificate_url,
			)
		);
	}

	/**
	 * Ajax download course certificate.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function ajax_download_course_certificate() {
		tutor_utils()->check_nonce();

		$course_id  = Input::post( 'course_id', 0, Input::TYPE_INT );
		$student_id = Input::post( 'student_id', 0, Input::TYPE_INT );

		if ( ! tutor_utils()->can_user_edit_course( get_current_user_id(), $course_id ) ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}

		if ( ! $course_id || ! $student_id ) {
			$this->response_bad_request( tutor_utils()->error_message( 'invalid_req' ) );
		}

		$is_enrolled = EnrollmentModel::is_enrolled( $course_id, $student_id );
		if ( ! $is_enrolled ) {
			$this->response_bad_request( tutor_utils()->error_message( 'invalid_req' ) );
		}

		$is_completed_course = tutor_utils()->is_completed_course( $course_id, $student_id );
		if ( ! $is_completed_course ) {
			CourseModel::mark_course_as_completed( $course_id, $student_id );
		}

		$certificate_url = $this->get_certificate( $course_id, false, $student_id );
		if ( ! $certificate_url ) {
			$this->response_bad_request( tutor_utils()->error_message( 'invalid_req' ) );
		}

		$this->json_response(
			__( 'Certificate generated successfully', 'tutor-pro' ),
			array(
				'certificate_url' => $certificate_url,
			)
		);
	}

	/**
	 * Add certificate meta data on learning area course info.
	 *
	 * @since 4.0.0
	 *
	 * @param array $meta meta.
	 * @param int   $course_id course id.
	 *
	 * @return array
	 */
	public function add_certificate_metadata( $meta, $course_id ) {
		if ( $this->disable_certificate_for_individual_courses( $course_id ) ) {
			return $meta;
		}
		if ( $this->has_course_certificate_template( $course_id ) ) {
			$is_completed_course = tutor_utils()->is_completed_course();
			if ( is_object( $is_completed_course ) && isset( $is_completed_course->completed_hash ) ) {
				$completed  = $this->completed_course( $is_completed_course->completed_hash );
				$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
				if ( ! $has_access ) {
					return $meta;
				}
			}

			$meta[] = array(
				'icon'    => Icon::CERTIFICATE_2,
				'title'   => __( 'Certificate', 'tutor-pro' ),
				'content' => __( 'You will receive certificate on completion', 'tutor-pro' ),
			);
		}

		return $meta;
	}

	/**
	 * Add certificate preview on learning area course info.
	 *
	 * @param int $course_id course id.
	 *
	 * @return void
	 */
	public function add_certificate_preview( $course_id ) {
		if ( $this->disable_certificate_for_individual_courses( $course_id ) ) {
			return;
		}

		if ( $this->has_course_certificate_template( $course_id ) ) {
			$is_completed_course = tutor_utils()->is_completed_course( $course_id );
			if ( ! $is_completed_course ) {
				return;
			}

			if ( is_object( $is_completed_course ) && isset( $is_completed_course->completed_hash ) ) {
				$completed  = $this->completed_course( $is_completed_course->completed_hash );
				$has_access = (bool) apply_filters( 'tutor_pro_certificate_access', true, $completed );
				if ( ! $has_access ) {
					return;
				}
			}

			$certificate_url = UrlHelper::add_query_params(
				get_the_permalink( $course_id ),
				array(
					'subpage'   => 'certificate',
					'cert_hash' => $is_completed_course->completed_hash,
				)
			);
			?>
		<div class="tutor-course-info-certificate">
			<div class="tutor-course-info-certificate-thumb">
				<?php include_once TUTOR_CERT()->path . '/assets/images/certificate-placeholder.svg'; ?>
			</div>
			<div class="tutor-course-info-certificate-content">
				<h4 class="tutor-h4 tutor-sm-text-medium"><?php esc_html_e( 'Congratulations on getting your certificate!', 'tutor-pro' ); ?></h4>
				<div class="tutor-medium tutor-sm-text-small tutor-mt-4 tutor-sm-mt-3">
					<span class="tutor-text-subdued tutor-sm-block"><?php esc_html_e( 'You completed this course on ', 'tutor-pro' ); ?></span>
				<?php echo esc_html( DateTimeHelper::create( $is_completed_course->completion_date ?? '' )->format( get_option( 'date_format' ) ) ); ?>
				</div>
				<?php do_action( 'tutor_pro/learning_area/course_info/before_view_certifiacte', $course_id ); ?>
			</div>
			<div class="tutor-course-info-certificate-buttons">
				<a href="<?php echo esc_url( $certificate_url ); ?>" class="tutor-btn tutor-btn-primary tutor-btn-x-small">
					<?php esc_html_e( 'View Certificate', 'tutor-pro' ); ?>
				</a>
			</div>
		</div>
			<?php
		}
	}

	/**
	 * Add certificates page to dashboard account pages.
	 *
	 * @since 4.0.0
	 *
	 * @param array $pages Account pages.
	 *
	 * @return array
	 */
	public function add_account_certificate_page( array $pages ): array {
		if ( ! User::is_student_view() ) {
			return $pages;
		}

		$certificates = array(
			'certificates' => array(
				'title'       => esc_html__( 'Certificates', 'tutor-pro' ),
				'icon'        => Icon::CERTIFICATE_2,
				'icon_active' => Icon::CERTIFICATE_2,
				'url'         => Dashboard::get_account_page_url( 'certificates' ),
				'template'    => TUTOR_CERT()->path . 'views/user-certificates.php',
			),
		);

		// Insert after 'profile' (second position).
		$position = array_search( 'profile', array_keys( $pages ), true );
		if ( false === $position ) {
			return $pages + $certificates;
		}

		return array_slice( $pages, 0, $position + 1, true )
			+ $certificates
			+ array_slice( $pages, $position + 1, null, true );
	}

	/**
	 * Get user's earned certificates list.
	 *
	 * @since 4.0.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array List of certificate data arrays with keys: title, course_url, certificate_url, is_bundle.
	 */
	public function get_user_certificates( int $user_id ): array {
		if ( ! $user_id ) {
			return array();
		}

		$certificates = QueryHelper::query(
			'comments',
			array(
				'select' => array(
					'cm.comment_ID AS certificate_id',
					'cm.comment_post_ID AS course_id',
					'p.post_title AS course_title',
					'cm.comment_author AS completed_user_id',
					'cm.comment_date AS completion_date',
					'cm.comment_content AS completed_hash',
				),
				'alias'  => 'cm',
				'joins'  => array(
					array(
						'table' => 'posts p',
						'on'    => 'p.ID = cm.comment_post_ID',
						'type'  => 'LEFT',
					),
					array(
						'table' => 'postmeta pm',
						'on'    => "pm.post_id = p.ID AND pm.meta_key = 'tutor_course_certificate_template'",
						'type'  => 'LEFT',
					),
				),
				'where'  => array(
					'cm.comment_agent'  => 'TutorLMSPlugin',
					'cm.comment_type'   => 'course_completed',
					'cm.comment_author' => $user_id,
					'pm.meta_value'     => array( '!=', 'none' ),
				),
			)
		);

		$certificate_data     = array();
		$course_bundle_enable = tutor_utils()->is_addon_enabled( 'course-bundle' );

		foreach ( $certificates as $certificate ) {
			$course_id       = $certificate->course_id;
			$certificate_url = $this->get_certificate( $course_id );
			if ( ! $certificate_url || $this->disable_certificate_for_individual_courses( $course_id ) ) {
				continue;
			}

			$certificate_data[] = array(
				'title'           => $certificate->course_title,
				'course_url'      => get_the_permalink( $course_id ),
				'certificate_url' => $certificate_url,
				'is_bundle'       => $course_bundle_enable && tutor()->bundle_post_type === get_post_type( $course_id ),
			);
		}

		return $certificate_data;
	}
}
