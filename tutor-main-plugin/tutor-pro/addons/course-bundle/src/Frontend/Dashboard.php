<?php
/**
 * Manage dashboard for course bundle.
 *
 * @package TutorPro\CourseBundle
 * @subpackage Frontend
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle\Frontend;

use TUTOR\Icon;
use Tutor\Components\SvgIcon;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Class
 *
 * @since 2.2.0
 */
class Dashboard {

	/**
	 * Register hooks
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'tutor_wishlist_post_types', array( $this, 'add_wishlist_post_types' ) );
		add_action( 'tutor_course_create_button', array( $this, 'create_bundle_button' ) );
		add_action( 'tutor_course_create_mobile_button', array( $this, 'create_bundle_mobile_button' ) );
		add_filter( 'tutor_get_enrolled_courses_by_user', array( $this, 'filter_courses_by_user_args' ) );
		add_filter( 'tutor_get_active_courses_by_user', array( $this, 'filter_courses_by_user_args' ) );
		add_filter( 'tutor_get_completed_courses_by_user', array( $this, 'filter_courses_by_user_args' ) );
		add_filter( 'tutor_dashboard_course_card_template', array( $this, 'get_bundle_progress_card' ), 10, 2 );
	}

	/**
	 * Add create new bundle button on dashboard page.
	 *
	 * @since 3.5.0
	 *
	 * @return void
	 */
	public function create_bundle_button() {
		?>
		<button data-source="frontend" class="tutor-btn tutor-btn-outline tutor-btn-small tutor-add-new-course-bundle">
			<?php esc_html_e( 'New Bundle', 'tutor-pro' ); ?>
		</button>
		<?php
	}

	/**
	 * Add create new bundle button on dashboard page for small devices.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function create_bundle_mobile_button() {
		?>
		<div
			x-data="tutorPopover({
				placement: 'bottom-end',
				offset: 4,
				onShow: () => { $el.classList.add('tutor-popover-open') },
				onHide: () => { $el.classList.remove('tutor-popover-open') }
			})"
		>
			<button x-ref="trigger" @click="toggle()" class="tutor-btn tutor-btn-primary tutor-btn-x-small tutor-btn-icon" aria-label="<?php esc_attr_e( 'Toggle menu', 'tutor-pro' ); ?>">
				<?php SvgIcon::make()->name( Icon::PLUS )->render(); ?>
			</button>

			<div 
				x-ref="content"
				x-show="open"
				x-cloak
				@click.outside="handleClickOutside()"
				class="tutor-popover"
			>
				<div class="tutor-popover-menu" style="min-width: 112px;">
					<button 
						class="tutor-popover-menu-item tutor-tiny"
						:class="createMutation.isPending ? 'tutor-btn-loading' : ''"
						@click="handleCreateCourse()"
						:disabled="createMutation.isPending"
					>
						<?php esc_html_e( 'New Course', 'tutor-pro' ); ?>
					</button>
					<button data-source="frontend" class="tutor-popover-menu-item tutor-tiny tutor-add-new-course-bundle">
						<?php esc_html_e( 'New Bundle', 'tutor-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add course bundle post type to wishlist post types.
	 *
	 * @since 2.2.0
	 *
	 * @param array $post_types post types.
	 *
	 * @return array
	 */
	public function add_wishlist_post_types( $post_types ) {
		$post_types[] = CourseBundle::POST_TYPE;
		return $post_types;
	}

	/**
	 * Add course bundle post type to dashboard courses.
	 *
	 * @since 3.9.0
	 *
	 * @param array $args Args.
	 *
	 * @return array
	 */
	public function filter_courses_by_user_args( $args ) {
		$args['post_type'] = array( tutor()->course_post_type, tutor()->bundle_post_type );

		return $args;
	}

	/**
	 * Get bundle progress card with collapsible courses.
	 *
	 * @since 4.0.0
	 *
	 * @param string $default_template Default template path.
	 * @param int    $course_id Course or bundle ID.
	 *
	 * @return string
	 */
	public function get_bundle_progress_card( $default_template, $course_id ) {
		if ( get_post_type( $course_id ) !== CourseBundle::POST_TYPE ) {
			return $default_template;
		}

		return Utils::template_path( 'dashboard/bundle-course-card.php' );
	}
}
