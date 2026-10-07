<?php
/**
 * Utils class
 *
 * @package TutorPro\CourseBundle
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle;

use TUTOR\Input;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;
use TutorPro\CourseBundle\Frontend\BundleBuilder;
use TutorPro\CourseBundle\Models\BundleModel;

/**
 * Utils Class.
 *
 * @since 2.2.0
 */
class Utils {
	/**
	 * Get view path.
	 *
	 * @since 2.2.0
	 *
	 * @param string $path path.
	 *
	 * @return string
	 */
	public static function view_path( $path = null ) {
		$final_path = TUTOR_COURSE_BUNDLE_DIR . 'views';
		if ( $path ) {
			$final_path .= '/' . $path;
		}
		return $final_path;
	}

	/**
	 * Get template path.
	 *
	 * @since 2.2.0
	 *
	 * @param string $path path.
	 *
	 * @return string
	 */
	public static function template_path( $path = null ) {
		$final_path = TUTOR_COURSE_BUNDLE_DIR . 'templates';
		if ( $path ) {
			$final_path .= '/' . $path;
		}
		return $final_path;
	}

	/**
	 * Get asset URL.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url url of assets.
	 *
	 * @return string
	 */
	public static function asset_url( $url = null ) {
		$final_url = plugin_dir_url( TUTOR_COURSE_BUNDLE_FILE ) . 'assets';
		if ( $url ) {
			$final_url .= '/' . $url;
		}
		return $final_url;
	}

	/**
	 * Get asset file path.
	 *
	 * @since 3.2.0
	 *
	 * @param string $path Relative path from assets dir with file ext.
	 *
	 * @return string
	 */
	public static function asset_path( $path = null ) {
		$final_url = TUTOR_COURSE_BUNDLE_DIR . 'assets';
		if ( $path ) {
			$final_url .= '/' . $path;
		}
		return $final_url;
	}

	/**
	 * Get bundle author avatars.
	 *
	 * @since 2.2.0
	 *
	 * @param int  $bundle_id course bundle id.
	 * @param bool $print_names print names along with avatars.
	 *
	 * @return void
	 */
	public static function get_bundle_author_avatars( $bundle_id, $print_names = false ) {
		$authors       = BundleModel::get_bundle_course_authors( $bundle_id );
		$total_authors = count( $authors );

		if ( 0 === $total_authors ) {
			echo esc_html( '...' );
			return;
		}

		$first_author   = $authors[0];
		$avatar_authors = array_slice( $authors, 0, 3 );
		?>
		<div class="tutor-bundle-authors">
		<?php
		foreach ( $avatar_authors as $author ) {
			echo wp_kses(
				tutor_utils()->get_tutor_avatar( $author->user_id, 'sm' ),
				tutor_utils()->allowed_avatar_tags()
			);
		}

		?>
		<div>
			<?php
			if ( $print_names ) :
				// Print Jhon & 2 Others.
				echo esc_html( $first_author->display_name );
				if ( $total_authors > 1 ) {
					echo esc_html( ' & ' . ( $total_authors - 1 ) . ' ' . _n( 'Other', 'Others', $total_authors - 1, 'tutor-pro' ) );
				}
			endif;
			?>
		</div>
		<!-- end of author names -->
		</div>
		<!-- end of tutor-bundle-authors -->
		<?php
	}

	/**
	 * Get current bundle id
	 *
	 * It will first look at the query string, then the post data.
	 *
	 * @return int
	 */
	public static function get_bundle_id() {
		$id = Input::get( 'id', 0, Input::TYPE_INT );
		return (int) $id ? $id : get_the_ID();
	}

	/**
	 * Check if user is bundle author
	 *
	 * @since 2.2.0
	 *
	 * @param int $bundle_id bundle id.
	 * @param int $user_id   user id, default to current user.
	 *
	 * @return bool
	 */
	public static function is_bundle_author( int $bundle_id, int $user_id = 0 ): bool {
		$post_type = get_post_type( $bundle_id );

		if ( CourseBundle::POST_TYPE !== $post_type ) {
			return false;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$post_author = (int) get_post_field( 'post_author', $bundle_id );
		return $user_id === $post_author;
	}

	/**
	 * Check is bundle single page
	 *
	 * @since 2.2.0
	 *
	 * @return boolean
	 */
	public static function is_bundle_single_page() {
		global $wp_query;
		if ( $wp_query->is_single && ! empty( $wp_query->query_vars['post_type'] ) && CourseBundle::POST_TYPE === $wp_query->query_vars['post_type'] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Construct course bundle add-new/edit page url
	 *
	 * @since 3.2.0
	 *
	 * @param string $page      Page type, should be either 'add-new' or 'edit'.
	 * @param int    $bundle_id Bundle ID required for edit URL but optional for add new.
	 *
	 * @throws \InvalidArgumentException If $page is not 'add-new' or 'edit'.
	 * @throws \InvalidArgumentException If $bundle_id is 0 or empty for edit page.
	 *
	 * @return string Constructed URL
	 */
	public static function construct_page_url( $page = 'add-new', $bundle_id = 0 ) {
		$url = '';

		// Validate input parameters.
		if ( ! in_array( $page, array( BundleBuilder::ACTION_TYPE_ADD, BundleBuilder::ACTION_TYPE_EDIT ) ) ) {
			throw new \InvalidArgumentException( 'Page type must be either add-new or edit' );
		}

		if ( BundleBuilder::ACTION_TYPE_EDIT === $page && empty( $bundle_id ) ) {
			throw new \InvalidArgumentException( 'Bundle ID is required for edit page' );
		}

		if ( is_admin() ) {
			if ( BundleBuilder::ACTION_TYPE_ADD === $page ) {
				$url = get_admin_url( null, 'admin.php?page=course-bundle&action=add-new' );
			} else {
				$url = get_admin_url( null, 'admin.php?page=course-bundle&action=edit&id=' . $bundle_id );
			}
		} else {
			// Keep the page url create-bundle for backward compatibility.
			if ( BundleBuilder::ACTION_TYPE_ADD === $page ) {
				$url = tutor_utils()->tutor_dashboard_url( 'create-bundle?action=add-new' );
			} else {
				$url = tutor_utils()->tutor_dashboard_url( 'create-bundle?action=edit&id=' . $bundle_id );
			}
		}

		return $url;
	}

	/**
	 * Construct bundle builder page url for the frontend only
	 *
	 * @since 3.2.0
	 *
	 * @param string $action Builder action type add-new or edit.
	 * @param int    $bundle_id Bundle id.
	 *
	 * @return string
	 */
	public static function construct_front_url( $action = 'add-new', $bundle_id = 0 ) {
		$page_slug = BundleBuilder::QUERY_PARAM;

		$url = tutor_utils()->tutor_dashboard_url( "{$page_slug}?action={$action}" );
		if ( $bundle_id ) {
			return $url . '&id=' . $bundle_id;
		}

		return $url;
	}

	/**
	 * Check if bundle editor page
	 *
	 * @since 3.2.0
	 *
	 * @return boolean
	 */
	public static function is_bundle_editor() {
		$is_editor = false;
		if ( is_admin() ) {
			$page_slug = Input::get( 'page', '', Input::TYPE_STRING );
			$action    = Input::get( 'action', '', Input::TYPE_STRING );
			$is_editor = BundleBuilder::ADMIN_PAGE_SLUG === $page_slug && ( BundleBuilder::ACTION_TYPE_EDIT === $action || BundleBuilder::ACTION_TYPE_ADD === $action );
		} else {
			$is_editor = get_query_var( 'tutor_dashboard_page' ) === BundleBuilder::QUERY_PARAM;
		}

		return ! empty( $is_editor ) ? true : false;

	}
	/**
	 * Check if current user can create bundle
	 *
	 * @since 3.2.0
	 *
	 * @return boolean
	 */
	public static function current_user_can_create_bundle(): bool {
		return (bool) current_user_can( tutor()->instructor_role ) || current_user_can( 'administrator' );
	}

	/**
	 * Check if current user can update bundle
	 *
	 * @since 3.2.0
	 *
	 * @param integer $bundle_id Course bundle id.
	 *
	 * @return boolean
	 */
	public static function current_user_can_update_bundle( int $bundle_id ): bool {
		$can = current_user_can( 'administrator' );
		if ( ! $can ) {
			$can = current_user_can( tutor()->instructor_role ) && self::is_bundle_author( $bundle_id );
		}

		return $can;
	}

	/**
	 * Check if bundle is free
	 *
	 * @since 3.2.0
	 *
	 * @param integer $bundle_id Course bundle id.
	 *
	 * @return boolean
	 */
	public static function is_free( $bundle_id ) {
		$price   = tutor_utils()->get_raw_course_price( $bundle_id );
		$is_free = ! $price->regular_price && ! $price->sale_price;

		return $is_free;
	}

}
