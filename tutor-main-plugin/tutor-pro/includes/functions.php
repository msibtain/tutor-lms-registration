<?php
/**
 * Helper functions
 *
 * @package TutorPro\Includes
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_generated_gradebook' ) ) {
	/**
	 * Get generated gradebook.
	 *
	 * @since 1.4.2
	 *
	 * @param string  $type type.
	 * @param integer $ref_id ref id.
	 * @param integer $user_id user id.
	 *
	 * @return array|bool|null|object|void
	 */
	function get_generated_gradebook( $type = 'final', $ref_id = 0, $user_id = 0 ) {
		global $wpdb;

		$user_id = tutor_utils()->get_user_id( $user_id );

		$res = false;
		if ( 'all' === $type ) {
			$res = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results} .*, grade_config FROM {$wpdb->tutor_gradebooks_results} 
					LEFT JOIN {$wpdb->tutor_gradebooks} ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE user_id = %d 
					AND course_id = %d 
					AND result_for != %s",
					$user_id,
					$ref_id,
					'final'
				)
			);

		} elseif ( 'quiz' === $type ) {

			$res = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results} .*, grade_config FROM {$wpdb->tutor_gradebooks_results} 
					LEFT JOIN {$wpdb->tutor_gradebooks} ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE user_id = %d 
					AND quiz_id = %d 
					AND result_for = %s ",
					$user_id,
					$ref_id,
					'quiz'
				)
			);

		} elseif ( 'assignment' === $type ) {
			$res = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results} .*, grade_config FROM {$wpdb->tutor_gradebooks_results} 
					LEFT 	JOIN {$wpdb->tutor_gradebooks} 
							ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE 	user_id = %d 
							AND assignment_id = %d 
							AND result_for = %s ",
					$user_id,
					$ref_id,
					'assignment'
				)
			);
		} elseif ( 'final' === $type ) {
			$res = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results}.*, grade_config FROM {$wpdb->tutor_gradebooks_results} 
					LEFT 	JOIN {$wpdb->tutor_gradebooks} 
							ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE 	user_id = %d 
							AND course_id = %d 
							AND result_for = %s",
					$user_id,
					$ref_id,
					'final'
				)
			);

		} elseif ( 'byID' === $type ) {
			$res = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results}.*, grade_config FROM {$wpdb->tutor_gradebooks_results} 
					LEFT 	JOIN {$wpdb->tutor_gradebooks} 
							ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE gradebook_result_id = %d",
					$ref_id
				)
			);
		}

		return $res;
	}
}

if ( ! function_exists( 'get_assignment_gradebook_by_course' ) ) {
	/**
	 * Get assignment gradebook by course
	 *
	 * @param integer $course_id course id.
	 * @param integer $user_id user id.
	 *
	 * @return array|null|object|void
	 */
	function get_assignment_gradebook_by_course( $course_id = 0, $user_id = 0 ) {
		global $wpdb;

		$user_id = tutor_utils()->get_user_id( $user_id );

		$res = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$wpdb->tutor_gradebooks_results}.grade_point, 
						COUNT({$wpdb->tutor_gradebooks_results}.earned_percent) AS res_count, 
						AVG({$wpdb->tutor_gradebooks_results}.earned_percent) as earned_percent,
                		FORMAT(AVG({$wpdb->tutor_gradebooks_results}.earned_grade_point), 2) as earned_grade_point,
                		grade_config 
				FROM 	{$wpdb->tutor_gradebooks_results} 
				LEFT 	JOIN {$wpdb->tutor_gradebooks} 
						ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
				WHERE 	course_id = %d 
						AND user_id = %d
						AND result_for = %s",
				$course_id,
				$user_id,
				'assignment'
			)
		);

		$res_count = (int) $res->res_count;
		if ( ! $res_count ) {
			return false;
		}

		return $res;
	}
}

if ( ! function_exists( 'get_quiz_gradebook_by_course' ) ) {
	/**
	 * Get quiz gradebook by course
	 *
	 * @param integer $course_id course id.
	 * @param integer $user_id user id.
	 * @return array|null|object|void
	 */
	function get_quiz_gradebook_by_course( $course_id = 0, $user_id = 0 ) {
		global $wpdb;

		$user_id = tutor_utils()->get_user_id( $user_id );

		$res = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT	{$wpdb->tutor_gradebooks_results}.grade_point, 
						COUNT({$wpdb->tutor_gradebooks_results}.earned_percent) AS res_count, 
                		AVG({$wpdb->tutor_gradebooks_results}.earned_percent) as earned_percent,
                		FORMAT(AVG({$wpdb->tutor_gradebooks_results}.earned_grade_point), 2) as earned_grade_point, grade_config 
				FROM 	{$wpdb->tutor_gradebooks_results}
				LEFT JOIN {$wpdb->tutor_gradebooks} 
						ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
				WHERE 	user_id = %d 
						AND result_for = %s",
				$user_id,
				'quiz'
			)
		);

		$res_count = (int) $res->res_count;
		if ( ! $res_count ) {
			return false;
		}

		return $res;

	}
}

if ( ! function_exists( 'get_gradebook_by_percent' ) ) {
	/**
	 * Get gradebook by percent
	 *
	 * @param integer $percent percent.
	 *
	 * @return array|null|object|void
	 */
	function get_gradebook_by_percent( $percent = 0 ) {
		if ( ! $percent ) {
			return false;
		}

		global $wpdb;

		$gradebook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->tutor_gradebooks} 
				WHERE 	percent_from <= %d 
						AND percent_to >= %d
				ORDER BY gradebook_id ASC LIMIT 1",
				$percent,
				$percent
			)
		);

		return $gradebook;
	}
}

if ( ! function_exists( 'get_gradebook_by_point' ) ) {
	/**
	 * Get gradebook by point.
	 *
	 * @param integer $point point.
	 *
	 * @return array|bool|null|object|void
	 */
	function get_gradebook_by_point( $point = 0 ) {
		if ( ! $point ) {
			return false;
		}
		global $wpdb;
		$gradebook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->tutor_gradebooks} 
				WHERE grade_point <= %s 
				ORDER BY grade_point DESC LIMIT 1",
				$point
			)
		);
		return $gradebook;
	}
}

if ( ! function_exists( 'tutor_generate_grade_html' ) ) {
	/**
	 * Generate grade HTML
	 *
	 * @param  mixed  $grade grade.
	 * @param string $style style.
	 *
	 * @return string
	 */
	function tutor_generate_grade_html( $grade, $style = 'bgfill' ) {
		if ( ! $grade ) {
			return;
		}

		// Get grade object if it is grade ID in fact.
		if ( ! is_object( $grade ) ) {
			global $wpdb;
			$grade = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$wpdb->tutor_gradebooks_results} .*, grade_config 
					FROM {$wpdb->tutor_gradebooks_results} 
					LEFT JOIN {$wpdb->tutor_gradebooks} ON {$wpdb->tutor_gradebooks_results}.gradebook_id = {$wpdb->tutor_gradebooks}.gradebook_id
					WHERE gradebook_result_id = %d",
					$grade
				)
			);
		}

		if ( ! $grade || empty( $grade->earned_grade_point ) ) {
			// No grade found.
			return;
		}

		// Prepare config.
		$config                                = maybe_unserialize( $grade->grade_config );
		$stat                                  = tutor_gradebook_get_stats( $grade );
		( $stat && $stat['config'] ) ? $config = $stat['config'] : 0;

		if ( null === $style ) {
			return $stat;
		}

		ob_start();
		$bgcolor = tutor_utils()->array_get( 'grade_color', $config );

		if ( 'bgfill' === $style ) {
			echo "<span class='gradename-bg " . esc_attr( $style ) . "' style='background-color: " . esc_attr( $bgcolor ) . ";'>" . esc_html( $stat['gradename'] ) . '</span> ';
		} else {
			echo "<span class='gradename-outline " . esc_attr( $style ) . "' style='color: " . esc_attr( $bgcolor ) . ";'>" . esc_html( $stat['gradename'] ) . '</span> ';
		}

		if ( $stat['gradepoint'] ) {
			echo "<span class='gradebook-earned-grade-point'>" . esc_html( $stat['gradepoint'] ) . '</span>';
		}

		return ob_get_clean();
	}
}

if ( ! function_exists( 'tutor_gradebook_get_stats' ) ) {
	/**
	 * Get stats of gradebook
	 *
	 * @param mixed $grade grade.
	 *
	 * @return array
	 */
	function tutor_gradebook_get_stats( $grade ) {

		$grade_name       = '';
		$grade_point      = '';
		$grade_point_only = '';
		$config           = null;

		$gradebook_scale = get_tutor_option( 'gradebook_scale' );

		// Get grade name.
		if ( ! empty( $grade->grade_name ) ) {
			$grade_name = $grade->grade_name;
		} else {
			$new_grade = get_gradebook_by_point( $grade->earned_grade_point );
			if ( $new_grade ) {
				$grade_name = $new_grade->grade_name;
				$config     = maybe_unserialize( $new_grade->grade_config );
			}
		}

		// Get grade point.
		if ( get_tutor_option( 'gradebook_enable_grade_point' ) ) {
			$grade_point_only = ! empty( $grade->earned_grade_point ) ? $grade->earned_grade_point : $grade->grade_point;
			$grade_point      = $grade_point_only;
		}

		// Add scale.
		if ( get_tutor_option( 'gradebook_show_grade_scale' ) ) {
			$separator   = get_tutor_option( 'gradebook_scale_separator', '/' );
			$grade_point = $grade_point . $separator . $gradebook_scale;
		}

		return array(
			'gradename'       => $grade_name,
			'gradepoint'      => $grade_point,
			'gradescale'      => $gradebook_scale,
			'gradepoint_only' => $grade_point_only,
			'config'          => $config,
		);
	}
}

if ( ! function_exists( 'get_gradebook_by_id' ) ) {
	/**
	 * Get gradebook by gradebook id
	 *
	 * @param int $gradebook_id gradebook id.
	 *
	 * @return mixed
	 */
	function get_gradebook_by_id( $gradebook_id ) {
		global $wpdb;
		$gradebook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->tutor_gradebooks} 
				WHERE gradebook_id = %d",
				$gradebook_id
			)
		);

		if ( $gradebook ) {
			$gradebook->grade_config = maybe_unserialize( tutor_utils()->array_get( 'grade_config', $gradebook ) );

			return $gradebook;
		}

		return false;
	}
}

/**
 * Get grading content by course id.
 *
 * @param integer $course_id course id.
 *
 * @return mixed
 */
function get_grading_contents_by_course_id( $course_id = 0 ) {
	global $wpdb;

	$course_id = tutor_utils()->get_post_id( $course_id );
	$contents  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT items.* FROM {$wpdb->posts} topic
				INNER JOIN {$wpdb->posts} items ON topic.ID = items.post_parent 
				WHERE topic.post_parent = %d 
				AND items.post_status = 'publish' 
				AND (items.post_type = 'tutor_quiz' || items.post_type = 'tutor_assignments') 
				order by topic.menu_order ASC, items.menu_order ASC;",
			$course_id
		)
	);

	return $contents;
}

/**
 * Get gradebook list
 *
 * @param array $config | config for filter / sorting query.
 * @return object
 */
function get_generated_gradebooks( $config = array() ) {
	global $wpdb;

	$default_attr = array(
		'course_id' => 0,
		'start'     => '0',
		'limit'     => '20',
		'order'     => sanitize_sql_orderby( Input::get( 'order', 'DESC' ) ),
		'order_by'  => 'gradebook_result_id',
		'date'      => Input::has( 'date' ) ? tutor_get_formated_date( 'Y-m-d', Input::get( 'date' ) ) : '',
	);

	$attr = array_merge( $default_attr, $config );
	/**
	 * It contains $default_attr with override $config
	 */
	extract( $attr );//phpcs:ignore

	$gradebooks = array(
		'count' => 0,
		'res'   => false,
	);

	$term = Input::get( 'search', '' );
	// Prepare filters.
	$filter_sql = '';

	if ( $course_id ) {
		$filter_sql .= " AND gradebook_result.course_id = {$course_id} ";
	}
	if ( $term ) {
		$search_term = '%' . $wpdb->esc_like( $term ) . '%';
		$filter_sql .= $wpdb->prepare( ' AND (course.post_title LIKE %s OR student.display_name LIKE %s) ', $search_term, $search_term );
	}
	if ( '' !== $date ) {
		$filter_sql .= " AND DATE(gradebook_result.update_date) = CAST('$date' AS DATE) ";
	}
	$order = sanitize_sql_orderby( $order );

	$gradebooks['count'] = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(gradebook_result.gradebook_result_id) total_res
			FROM {$wpdb->tutor_gradebooks_results} gradebook_result
			LEFT JOIN {$wpdb->posts} course 
					ON gradebook_result.course_id = course.ID
			LEFT  JOIN {$wpdb->users} student 
					ON gradebook_result.user_id = student.ID
			WHERE gradebook_result.result_for = %s {$filter_sql} ;", //phpcs:ignore
			'final'
		)
	);

	//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$gradebooks['res'] = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gradebook_result.*, 
			(SELECT COUNT(quizzes.quiz_id) FROM {$wpdb->tutor_gradebooks_results} quizzes WHERE quizzes.user_id = gradebook_result.user_id AND quizzes.course_id = gradebook_result.course_id AND quizzes.result_for = 'quiz') as quiz_count,
			(SELECT COUNT(assignments.assignment_id) FROM {$wpdb->tutor_gradebooks_results} assignments WHERE assignments.user_id = gradebook_result.user_id AND assignments.course_id = gradebook_result.course_id AND assignments.result_for = 'assignment') as assignment_count,
			grade_config,
			student.display_name,
			course.post_title as course_title
			FROM {$wpdb->tutor_gradebooks_results} gradebook_result
				LEFT JOIN {$wpdb->tutor_gradebooks} gradebook ON gradebook_result.gradebook_id = gradebook.gradebook_id
				LEFT JOIN {$wpdb->posts} course ON gradebook_result.course_id = course.ID
				LEFT  JOIN {$wpdb->users} student ON gradebook_result.user_id = student.ID
			WHERE gradebook_result.result_for = %s {$filter_sql} 
			ORDER BY gradebook_result.generate_date {$order}
			LIMIT %d, %d",
			'final',
			$start,
			$limit
		)
	);
	//phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$gradebooks = (object) $gradebooks;

	return $gradebooks;
}

if ( ! function_exists( 'tutor_pro_email_global_footer' ) ) {
	/**
	 * Get the global email footer
	 *
	 * @since 2.1.9
	 *
	 * @return string email footer as string
	 */
	function tutor_pro_email_global_footer() {
		$string            = '';
		$email_footer_text = tutor_utils()->get_option( 'email_footer_text' );
		$email_footer_text = str_replace( '{site_name}', get_bloginfo( 'name' ), $email_footer_text );

		if ( $email_footer_text ) {
			$string .= '<div class="tutor-email-footer-content">' . wp_unslash( json_decode( $email_footer_text ) ) . '</div>';
		}
		return $string;
	}
}

if ( ! function_exists( 'is_tutor_pro_in_wpdc_env' ) ) {
	/**
	 * Check plugin is running on WPDC(WordPress.com) environment.
	 *
	 * @see https://developer.wordpress.com/wordpress-com-marketplace/vendor-apis
	 * @since 2.6.1
	 *
	 * @return bool true if running on WordPress.com environment.
	 */
	function is_tutor_pro_in_wpdc_env() {
		return defined( 'IS_ATOMIC' ) && IS_ATOMIC && defined( 'ATOMIC_CLIENT_ID' ) && '2' === ATOMIC_CLIENT_ID;
	}
}
