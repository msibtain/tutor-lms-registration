<?php
/**
 * Manage course bundle meta data
 *
 * @package TutorPro\CourseBundle\CustomPosts
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.2.0
 */

namespace TutorPro\CourseBundle\CustomPosts;

use WP_Post;
use TUTOR\Input;
use TUTOR\Course;
use Tutor\Models\CourseModel;
use Tutor\Helpers\QueryHelper;
use TutorPro\CourseBundle\Models\BundleModel;
use TutorPro\CourseBundle\CustomPosts\CourseBundle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage course bundle post meta
 */
class ManagePostMeta {

	/**
	 * Course bundle product meta key
	 *
	 * @var string
	 */
	const BUNDLE_RIBBON_META_KEY = 'tutor_bundle_ribbon_type';

	/**
	 * Register hooks
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'save_post_' . CourseBundle::POST_TYPE, array( $this, 'update_bundle_meta' ), 10, 2 );
	}

	/**
	 * Update bundle product
	 *
	 * @since 2.2.0
	 *
	 * @since 3.0.0
	 *
	 * @since 3.2.0 WC product will created only if bundle price exists
	 *
	 * Monetize by tutor support added
	 *
	 * @param int     $post_id post id.
	 * @param WP_Post $post post.
	 *
	 * @return void
	 */
	public function update_bundle_meta( int $post_id, WP_Post $post ) {
		$product_id = (int) get_post_meta( $post_id, Course::COURSE_PRODUCT_ID_META, true );

		$sale_price = Input::post( 'sale_price', BundleModel::get_bundle_sale_price( $post_id ), Input::TYPE_NUMERIC );
		$reg_price  = BundleModel::get_bundle_regular_price( $post_id );
		$price_type = $reg_price > 0 ? Course::PRICE_TYPE_PAID : Course::PRICE_TYPE_FREE;

		update_post_meta( $post->ID, Course::COURSE_PRICE_TYPE_META, $price_type );

		self::update_course_bundle_price( $post_id, $reg_price, $sale_price );

		// Ignore product creation if bundle is free.
		if ( 'wc' === tutor_utils()->get_option( 'monetize_by' ) && ( $reg_price > 0 || $sale_price > 0 ) ) {
			// Update product meta.
			$product_id = Course::create_wc_product( $post->post_title, $reg_price, $sale_price, $product_id );

			Course::sync_course_with_wc_product( $post_id, $product_id );
		}

		// Update ribbon type.
		$ribbon_type = Input::post( 'ribbon_type', self::get_ribbon_type( $post_id ), Input::TYPE_STRING );
		self::update_bundle_ribbon_meta( $post_id, $ribbon_type );

		// Update additional data.
		$benefits = Input::post( 'course_benefits', '', Input::TYPE_TEXTAREA );
		self::update_bundle_benefits( $post_id, $benefits );

		$author_id = get_post_field( 'post_author', $post_id );

		global $wpdb;
		$attached = (int) QueryHelper::get_count(
			$wpdb->usermeta,
			array(
				'user_id'    => $author_id,
				'meta_key'   => '_tutor_instructor_course_id',
				'meta_value' => $post_id,
			),
			array(),
			'meta_value'
		);

		if ( ! $attached ) {
			add_user_meta( $post->post_author, '_tutor_instructor_course_id', $post_id );
		}

		// TODO Certificate will be used later on.
	}

	/**
	 * Update bundle ribbon meta
	 *
	 * @since 2.2.0
	 *
	 * @param integer $post_id post id.
	 * @param string  $ribbon_type ribbon type.
	 *
	 * @return int|bool
	 */
	public static function update_bundle_ribbon_meta( int $post_id, string $ribbon_type ) {
		return update_post_meta( $post_id, self::BUNDLE_RIBBON_META_KEY, $ribbon_type );
	}

	/**
	 * Update bundle benefits meta (what i will learn)
	 *
	 * @since 2.2.0
	 *
	 * @param integer $post_id post id.
	 * @param string  $content benefits content.
	 *
	 * @return int|bool
	 */
	public static function update_bundle_benefits( int $post_id, string $content = '' ) {
		return update_post_meta( $post_id, CourseModel::BENEFITS_META_KEY, $content );
	}

	/**
	 * Get ribbon type
	 *
	 * @since 2.2.0
	 *
	 * @param integer $post_id post id.
	 *
	 * @return string
	 */
	public static function get_ribbon_type( int $post_id ): string {
		$type = get_post_meta( $post_id, self::BUNDLE_RIBBON_META_KEY, true );
		return $type ? $type : '';
	}

	/**
	 * Update course bundle price
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Bundle id.
	 * @param int $reg_price the regular price.
	 * @param int $sale_price the sale price.
	 *
	 * @return void
	 */
	public static function update_course_bundle_price( $post_id, $reg_price, $sale_price ) {
		if ( ! empty( $reg_price ) ) {
			update_post_meta( $post_id, Course::COURSE_PRICE_META, $reg_price );
			update_post_meta( $post_id, Course::COURSE_SALE_PRICE_META, $sale_price );
		}
	}
}
