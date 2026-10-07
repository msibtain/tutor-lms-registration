<?php
/**
 * Assignment List
 *
 * @package TutorPro/Addons
 * @subpackage Assignment
 * @author: themeum
 * @author Themeum <support@themeum.com>
 * @since 1.0.0
 */

namespace TUTOR_ASSIGNMENTS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TUTOR\Backend_Page_Trait;
use Tutor\Helpers\DateTimeHelper;
use Tutor\Helpers\QueryHelper;
use Tutor\Input;
use TUTOR\User;
use TUTOR\Instructors_List;

/**
 * Assignments List class
 */
class Assignments_List {

	/**
	 * Trait for utilities
	 *
	 * @var $page_title
	 */
	use Backend_Page_Trait;

	const POST_TYPE   = 'tutor_assignments';
	const TAB_ALL     = 'all';
	const TAB_PENDING = 'pending';
	const TAB_PASS    = 'pass';
	const TAB_FAIL    = 'fail';

	/**
	 * Page Title
	 *
	 * @var $page_title
	 */
	public $page_title;

	/**
	 * Bulk Action
	 *
	 * @var $bulk_action
	 */
	public $bulk_action = true;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->page_title = __( 'Assignments', 'tutor-pro' );
	}

	/**
	 * Total points
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_mark( $item ) {
		echo (int) tutor_utils()->get_assignment_option( $item->comment_post_ID, 'total_mark' );
	}

	/**
	 * Passing mark
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_passing_mark( $item ) {
		echo (int) tutor_utils()->get_assignment_option( $item->comment_post_ID, 'pass_mark' );
	}

	/**
	 * Student
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_student( $item ) {
		echo '<div class="tutor-d-flex tutor-align-center">';
		echo wp_kses( tutor_utils()->get_tutor_avatar( $item->user_id, 'sm' ), tutor_utils()->allowed_avatar_tags() );
		echo '<div class="tutor-ml-12">' . esc_html( $item->comment_author ) . '</div>';
		echo '</div>';
	}

	/**
	 * Assignment title.
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_title( $item ) {
		echo '<a href="' . esc_url( get_the_permalink( $item->comment_post_ID ) ) . '">' . esc_html( get_the_title( $item->comment_post_ID ) ) . '</a>';
		echo '<div class="tutor-fs-7 tutor-fw-normal tutor-color-secondary tutor-mt-8">
				<strong class="tutor-fs-7 tutor-fw-medium">' . esc_html__( 'Course', 'tutor-pro' ) . ': </strong>' . esc_html( $item->post_title ) .
			'</div>';
	}

	/**
	 * Duration
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_duration( $item ) {
		$value = tutor_utils()->get_assignment_option( $item->comment_post_ID, 'time_duration.value' );
		$time  = tutor_utils()->get_assignment_option( $item->comment_post_ID, 'time_duration.time' );
		$time  = trim( $time, 's' );

		echo $value ? ( $value . ' ' . __( $time, 'tutor-pro' ) . ( $value > 1 ? 's' : '' ) ) : __( 'No Limit', 'tutor-pro' );//phpcs:ignore
	}

	/**
	 * Date
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_date( $item ) {
		$deadline = tutor_utils()->get_assignment_deadline_date_in_gmt( $item->comment_post_ID, null, $item->user_id, $item->ID );

		// Deadline.
		if ( $deadline ) {
			?>
			<div class="course-meta" style="margin-top:0">
				<span class="tutor-color-black tutor-fs-7">
					<strong><?php esc_html_e( 'Deadline', 'tutor-pro' ); ?></strong>
					<?php echo esc_html( DateTimeHelper::get_gmt_to_user_timezone_date( $deadline ) ); ?>
				</span>
			</div>
			<?php
		}
		?>
		<div class="course-meta" style="margin-top:0">
			<span class="tutor-color-secondary tutor-fs-7"> 
				<?php esc_html_e( 'Started', 'tutor-pro' ); ?>
				<?php
					echo esc_html( tutor_utils()->convert_date_into_wp_timezone( $item->comment_date_gmt ) );
				?>
			</span>
		</div>
		<?php
	}

	/**
	 * Evaluate action.
	 *
	 * @param object $item item.
	 * @param int    $post_id post id.
	 *
	 * @return void
	 */
	public function column_action_evaluate( $item, $post_id ) {
		$assignment_mark = get_comment_meta( $item->comment_ID, 'assignment_mark', true );
		$is_evaluated    = '' !== $assignment_mark;
		$button_text     = $is_evaluated ? __( 'Details', 'tutor-pro' ) : __( 'Evaluate', 'tutor-pro' );

		echo "<a class='tutor-btn tutor-btn-outline-primary tutor-btn-sm' href='" . esc_url( admin_url( "admin.php?page=tutor-assignments&view_assignment=$item->comment_ID&post-id=$post_id" ) ) . "'>" . esc_html( $button_text ) . '</a>';
	}

	/**
	 * Delete action.
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_action_delete( $item ) {
		echo '<a class="tutor-btn tutor-btn-outline-primary tutor-btn-sm" data-assignment_id="' . esc_attr( $item->comment_ID ) . '" href="#" data-tutor-modal-target="assignment-' . esc_attr( $item->comment_ID ) . '">' . esc_html( _x( 'Delete', 'assignment delete', 'tutor-pro' ) ) . '</a>';
	}

	/**
	 * Course column.
	 *
	 * @param object $item item.
	 *
	 * @return void
	 */
	public function column_course( $item ) {
		echo '<a href="' . esc_url( get_the_permalink( $item->comment_parent ) ) . '" target="_blank">' . esc_html( get_the_title( $item->comment_parent ) ) . '</a>';
	}

	/**
	 * Available tabs that will visible on the right side of page navbar
	 *
	 * @param int    $course_id course id.
	 * @param string $start_date range start date.
	 * @param string $end_date range end date.
	 * @param string $search search term.
	 *
	 * @return array
	 */
	public function tabs_key_value( $course_id, $start_date, $end_date, $search ): array {
		$data = $this->tabs_data( $course_id, $start_date, $end_date, $search );
		$tabs = array(
			array(
				'key'   => '',
				'title' => __( 'All', 'tutor-pro' ),
				'value' => $data[ self::TAB_ALL ],
				'url'   => '?page=tutor-assignments&data=' . self::TAB_ALL,
			),
			array(
				'key'   => self::TAB_PASS,
				'title' => __( 'Pass', 'tutor-pro' ),
				'value' => $data[ self::TAB_PASS ],
				'url'   => '?page=tutor-assignments&data=' . self::TAB_PASS,
			),
			array(
				'key'   => self::TAB_FAIL,
				'title' => __( 'Fail', 'tutor-pro' ),
				'value' => $data[ self::TAB_FAIL ],
				'url'   => '?page=tutor-assignments&data=' . self::TAB_FAIL,
			),
			array(
				'key'   => self::TAB_PENDING,
				'title' => __( 'Pending', 'tutor-pro' ),
				'value' => $data[ self::TAB_PENDING ],
				'url'   => '?page=tutor-assignments&data=' . self::TAB_PENDING,
			),
		);

		return $tabs;
	}

	/**
	 * Provide data for tabs
	 *
	 * @since 2.0.0
	 *
	 * @param int    $course_id course id.
	 * @param string $start_date range start date.
	 * @param string $end_date range end date.
	 * @param string $search search term.
	 *
	 * @return array
	 */
	public function tabs_data( $course_id, $start_date = '', $end_date = '', $search = '' ) {
		/**
		 * If current user is admin then 0 to get all assignments
		 * otherwise just get that belongs to instructor
		 */
		$user_id = current_user_can( 'administrator' ) ? 0 : get_current_user_id();
		$all     = self::get_submitted_assignment_list( self::TAB_ALL, $course_id, $start_date, $end_date, $search, '', '', '', $user_id )->total_count;
		$pass    = self::get_submitted_assignment_list( self::TAB_PASS, $course_id, $start_date, $end_date, $search, '', '', '', $user_id )->total_count;
		$fail    = self::get_submitted_assignment_list( self::TAB_FAIL, $course_id, $start_date, $end_date, $search, '', '', '', $user_id )->total_count;
		$pending = self::get_submitted_assignment_list( self::TAB_PENDING, $course_id, $start_date, $end_date, $search, '', '', '', $user_id )->total_count;
		return array(
			self::TAB_ALL     => $all,
			self::TAB_PASS    => $pass,
			self::TAB_FAIL    => $fail,
			self::TAB_PENDING => $pending,
		);
	}

	/**
	 * Get submitted assignment list on backend.
	 *
	 * @since 3.6.0
	 *
	 * @param string  $status status.
	 * @param string  $course_id course id.
	 * @param string  $start_date range start date.
	 * @param string  $end_date range end date.
	 * @param string  $search_term search term.
	 * @param string  $offset offset.
	 * @param string  $limit limit.
	 * @param string  $order order.
	 * @param integer $user_id user id.
	 *
	 * @return object
	 */
	public static function get_submitted_assignment_list( string $status, $course_id = '', $start_date = '', $end_date = '', $search_term = '', $offset = '', $limit = '', $order = '', $user_id = 0 ) {
		global $wpdb;
		$course_id   = Input::sanitize( $course_id, 0, INPUT::TYPE_INT );
		$user_id     = Input::sanitize( $user_id, 0, INPUT::TYPE_INT );
		$start_date  = Input::sanitize( $start_date, '' );
		$end_date    = Input::sanitize( $end_date, '' );
		$search_term = Input::sanitize( $search_term, '' );
		$status      = Input::sanitize( $status, '' );
		$order       = sanitize_sql_orderby( $order );

		if ( ! $user_id ) {
			$user_id = tutor_utils()->get_user_id();
		}

		$primary_tables = "{$wpdb->posts} as post";
		$joining_tables = array(
			array(
				'type'  => 'INNER',
				'table' => "{$wpdb->posts} as topic",
				'on'    => 'topic.ID = post.post_parent',
			),
			array(
				'type'  => 'INNER',
				'table' => "{$wpdb->posts} as course",
				'on'    => 'course.ID = topic.post_parent',
			),
			array(
				'type'  => 'INNER',
				'table' => "{$wpdb->comments} as submit",
				'on'    => 'submit.comment_post_ID = post.ID',
			),
			array(
				'type'  => 'INNER',
				'table' => "{$wpdb->users} as user",
				'on'    => 'user.ID = submit.user_id',
			),
		);

		$where = array(
			'post.post_type'          => self::POST_TYPE,
			'submit.comment_approved' => 'submitted',
		);

		if ( '' !== $start_date && '' !== $end_date ) {
			$where['DATE(submit.comment_date)'] = array(
				'BETWEEN',
				array( $start_date, $end_date ),
			);
		}

		if ( $course_id ) {
			$where['course.ID'] = $course_id;
		}

		if ( ! User::is_admin( $user_id ) ) {
			// Get the course ids the instructor is assigned to as a co-instructor.
			$assigned_course_ids = tutor_utils()->get_assigned_courses_ids_by_instructors( $user_id, Instructors_List::STATUS_APPROVED );

			if ( is_array( $assigned_course_ids ) && count( $assigned_course_ids ) ) {
				$course_ids = implode( ',', array_map( 'intval', $assigned_course_ids ) );
				$where[ '(course.post_author = %d OR course.ID IN (' . $course_ids . '))' ] = array(
					'RAW',
					array( $user_id ),
				);
			} else {
				$where['course.post_author'] = $user_id;
			}
		}

		$search_clause = array();

		if ( '' !== $search_term ) {
			$search_clause = array(
				'post.post_title'   => $search_term,
				'course.post_title' => $search_term,
				'user.user_login'   => $search_term,
				'user.display_name' => $search_term,
			);
		}

		if ( self::TAB_ALL === $status ) {
			$where['submit.comment_post_ID'] = array(
				'IS NOT',
				'NULL',
			);
		}

		if ( self::TAB_PENDING === $status ) {
			$join_table = array(
				'type'  => 'LEFT',
				'table' => "{$wpdb->commentmeta} AS evaluate",
				'on'    => "evaluate.comment_ID = submit.comment_ID AND evaluate.meta_key = 'assignment_mark'",
			);

			array_push( $joining_tables, $join_table );

			$where['submit.comment_post_ID'] = array(
				'IS NOT',
				'NULL',
			);

			$where['evaluate.meta_value'] = 'null';

		}

		if ( self::TAB_PASS === $status || self::TAB_FAIL === $status ) {
			$join_table = array(
				array(
					'type'  => 'INNER',
					'table' => "{$wpdb->postmeta} AS pass_mark",
					'on'    => "pass_mark.post_id = post.ID AND pass_mark.meta_key = '_tutor_assignment_pass_mark'",
				),
				array(
					'type'  => 'INNER',
					'table' => "{$wpdb->commentmeta} AS evaluate_mark",
					'on'    => "evaluate_mark.comment_ID = submit.comment_ID AND evaluate_mark.meta_key = 'assignment_mark'",
				),
			);

			$joining_tables  = array_merge( $joining_tables, $join_table );
			$evaluate_signed = '';
			$pass_signed     = '';

			if ( self::TAB_PASS === $status ) {
				$where['CAST(evaluate_mark.meta_value AS SIGNED) >= CAST(pass_mark.meta_value AS SIGNED)'] = array(
					'RAW',
					array(),
				);
			} else {
				$where['CAST(evaluate_mark.meta_value AS SIGNED) < CAST(pass_mark.meta_value AS SIGNED)'] = array(
					'RAW',
					array(),
				);
			}
		}

		$results = (object) QueryHelper::get_joined_data(
			$primary_tables,
			$joining_tables,
			array( '*' ),
			$where,
			$search_clause,
			'submit.comment_date',
			$limit,
			$offset,
			$order,
			'OBJECT',
		);

		return $results;
	}

	/**
	 * Get student's submitted assignments by assignment id
	 *
	 * @param integer $assignment_id  required argument assignment id.
	 * @param string  $order_filter  optional default value DESC.
	 *
	 * @return array  list of assignments.
	 */
	public static function get_submitted_assignments( int $assignment_id, string $order_filter = 'DESC' ): array {
		global $wpdb;

		$order_filter = QueryHelper::get_valid_sort_order( $order_filter );

		$assignments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					* 
			  	FROM 
					{$wpdb->comments} 
			  	WHERE 
					comment_type = 'tutor_assignment' 
					AND comment_post_ID = %d
					AND comment_approved = 'submitted'
			  	ORDER BY 
				  	comment_ID {$order_filter}", //phpcs:ignore
				$assignment_id
			)
		);

		return is_array( $assignments ) && count( $assignments ) ? $assignments : array();
	}
}
