<?php
/**
 * Base Model
 *
 * @package TutorPro\Subscription
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\Subscription\Models;

use Tutor\Helpers\QueryHelper;

/**
 * BaseModel Class.
 *
 * @since 3.0.0
 */
abstract class BaseModel {
	/**
	 * WP db instance
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * Guard which fields can not be filled
	 *
	 * @var array
	 */
	protected $guarded = array( 'id' );

	/**
	 * Set which fields can be filled
	 *
	 * @var string|array star(*) means all are fillable.
	 */
	protected $fillable = '*';

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;

		if ( $this->is_set_table() && ! $this->has_table_prefix() ) {
			$this->table_name = $this->db->prefix . $this->table_name;
		}

	}

	/**
	 * Check model is associated with a db table.
	 *
	 * @return boolean
	 */
	public function is_set_table() {
		return isset( $this->table_name );
	}

	/**
	 * Check table as table prefix.
	 *
	 * @return boolean
	 */
	public function has_table_prefix() {
		return strpos( $this->table_name, $this->db->prefix ) === 0;
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get table fields
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = array();
		if ( isset( $this->table_name ) ) {
			foreach ( $this->db->get_col( 'DESC ' . $this->table_name, 0 ) as $column_name ) {
				$fields[] = $column_name;
			}
		}

		return $fields;
	}

	/**
	 * Filter inputs.
	 *
	 * @param array $inputs user inputs.
	 *
	 * @return array
	 */
	private function filter_inputs( $inputs ) {
		$table_fields = $this->get_fields();

		// Remove unwanted user input key.
		$inputs = array_intersect_key( $inputs, array_flip( $table_fields ) );

		// Handle fillable fields.
		if ( is_array( $this->fillable ) ) {
			$inputs = array_intersect_key( $inputs, array_flip( $this->fillable ) );
		}

		// Handle guarded fields.
		if ( is_array( $this->guarded ) ) {
			$inputs = array_diff_key( $inputs, array_flip( $this->guarded ) );
		}

		return $inputs;
	}

	/**
	 * Get a record.
	 *
	 * @param array $where where clause.
	 *
	 * @return object|false object or false when not found.
	 */
	public function get_row( $where ) {
		$result = false;
		if ( $this->is_set_table() ) {
			$result = QueryHelper::get_row( $this->table_name, $where, 'id' );
		}

		return $result;
	}

	/**
	 * Get all record from table.
	 *
	 * @param array  $where where clause.
	 * @param string $order_by order by.
	 * @param string $order order.
	 * @param int    $limit limit.
	 *
	 * @return array|false list of rows or false when table not set.
	 */
	public function get_all( $where, $order_by = 'id', $order = 'DESC', $limit = -1 ) {
		$results = false;
		if ( $this->is_set_table() ) {
			$results = QueryHelper::get_all( $this->table_name, $where, $order_by, $limit, $order );
		}

		return $results;
	}

	/**
	 * Get table record with pagination.
	 *
	 * @param integer $per_page per page record.
	 * @param integer $page current page number.
	 * @param array   $args options like where, search.
	 *
	 * @return object|false object contains total_record, total_page, per_page, current_page, data.
	 */
	public function paginate( $per_page = 10, $page = 1, $args = array() ) {
		if ( ! $this->is_set_table() ) {
			return false;
		}

		$where_clause = '';

		if ( isset( $args['where'] ) && is_array( $args['where'] ) ) {
			$where        = $args['where'];
			$where_clause = count( $where ) ? 'WHERE' . QueryHelper::build_where_clause( $where ) : '';
		}

		if ( isset( $args['search'] ) && is_array( $args['search'] ) ) {
			$search       = $args['search'];
			$where_clause = count( $search ) ? 'WHERE' . QueryHelper::build_like_clause( $search, 'AND' ) : '';
		}

		$orderby     = $args['orderby'] ?? 'id';
		$order       = 'DESC' === strtoupper( $args['order'] ?? 'DESC' ) ? 'DESC' : 'ASC';
		$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

		$page   = max( $page, 1 );
		$offset = ( $page - 1 ) * $per_page;

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT SQL_CALC_FOUND_ROWS t.* 
				FROM {$this->table_name} AS t {$where_clause}
				ORDER BY {$orderby_sql}
				LIMIT %d, %d",
				$offset,
				$per_page
			)
		);

		$has_records  = is_array( $rows ) && count( $rows );
		$total_record = (int) $has_records ? $this->db->get_var( 'SELECT FOUND_ROWS()' ) : 0;
		$total_page   = ( $total_record > 0 && $per_page > 0 ) ? floor( $total_record / $per_page ) : 0;
		$pagination   = array(
			'total_record' => $total_record,
			'per_page'     => $per_page,
			'current_page' => $page,
			'total_page'   => $total_page,
			'data'         => $rows,
		);

		return (object) $pagination;

	}

	/**
	 * Count record.
	 *
	 * @param array $where where clause.
	 *
	 * @return int
	 */
	public function count( $where ) {
		if ( ! $this->is_set_table() ) {
			return 0;
		}

		return QueryHelper::get_count( $this->table_name, $where );
	}

	/**
	 * Create record
	 *
	 * @since 3.0.0
	 *
	 * @param array $data data.
	 *
	 * @return int|false id or false when creation failed.
	 */
	public function create( $data ) {
		$insert   = false;
		$filtered = $this->filter_inputs( $data );

		if ( $this->is_set_table() && count( $filtered ) ) {
			$insert = $this->db->insert( $this->table_name, $filtered );
			if ( $insert ) {
				return $this->db->insert_id;
			}
		}

		return $insert;
	}

	/**
	 * Update record
	 *
	 * @since 3.0.0
	 *
	 * @param int   $id id.
	 * @param array $data data.
	 *
	 * @return int|false id or false when failed.
	 */
	public function update( $id, $data ) {
		$update   = false;
		$filtered = $this->filter_inputs( $data );

		if ( $this->is_set_table() && count( $filtered ) ) {
			$update = $this->db->update( $this->table_name, $filtered, array( 'id' => $id ) );
			if ( $update ) {
				return $id;
			}
		}

		return $update;
	}

	/**
	 * Delete a record.
	 *
	 * @since 3.0.0
	 *
	 * @param int $id id.
	 *
	 * @return bool
	 */
	public function delete( $id ) {
		$deleted = false;
		if ( $this->is_set_table() ) {
			$deleted = (bool) $this->db->delete( $this->table_name, array( 'id' => $id ) );
		}

		return $deleted;
	}
}
