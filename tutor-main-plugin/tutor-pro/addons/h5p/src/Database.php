<?php
/**
 * Handle Tutor H5P Database
 *
 * @package TutorPro\Addons
 * @subpackage H5P
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\H5P;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tutor H5P Database class
 *
 * @since 3.0.0
 */
class Database {

	/**
	 * Register Hooks and Dependencies.
	 */
	public function __construct() {
		add_action( 'tutor_addon_after_enable_tutor-pro/addons/h5p/h5p.php', array( $this, 'create_tables' ) );
	}

	/**
	 * Create tables for Tutor H5P statements and results.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function create_tables() {

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$h5p_quiz_result_sql = "CREATE TABLE {$wpdb->prefix}tutor_h5p_quiz_result (
            result_id  BIGINT(20) NOT NULL AUTO_INCREMENT,
            quiz_id BIGINT(20),
            attempt_id BIGINT(20),
            question_id BIGINT(20),
            user_id BIGINT(20),
            content_id BIGINT(20),
            response TEXT,
            max_score INT,
            raw_score INT,
            scaled_score INT,
            min_score INT,
            completion BOOLEAN,
            success BOOLEAN,
            opened INT(10) ,
            finished INT(10) ,
            duration BIGINT(20),
            PRIMARY KEY (result_id)
        ) $charset_collate;";

		$h5p_quiz_statement_sql = "CREATE TABLE {$wpdb->prefix}tutor_h5p_quiz_statement (
            statement_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            instructor_id BIGINT(20) DEFAULT NULL,
            course_id BIGINT(20) DEFAULT NULL,
            topic_id  BIGINT(20) DEFAULT NULL,
            quiz_id      BIGINT(20) DEFAULT NULL,
            question_id  BIGINT(20) DEFAULT NULL,
            content_id   BIGINT(20) DEFAULT NULL,
            user_id      BIGINT(20) DEFAULT NULL,
            verb         VARCHAR(20),
            verb_id      TEXT,
            activity_name TEXT,
            activity_description TEXT,
            activity_choices TEXT,
            activity_target TEXT,
            activity_interaction_type TEXT,
            activity_correct_response_pattern TEXT,
            result_response TEXT,
            result_max_score INT,
            result_raw_score INT,
            result_scaled_score INT,
            result_min_score INT,
            result_completion BOOLEAN,
            result_success BOOLEAN,
            result_duration TEXT,
            created_at DATETIME,
            quiz_result_id BIGINT(20),
            PRIMARY KEY (statement_id)
        ) $charset_collate;";

		$h5p_lesson_statement_sql = "CREATE TABLE {$wpdb->prefix}tutor_h5p_lesson_statement (
            statement_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            instructor_id BIGINT(20) DEFAULT NULL,
            course_id BIGINT(20) DEFAULT NULL,
            topic_id  BIGINT(20) DEFAULT NULL,
            lesson_id  BIGINT(20) DEFAULT NULL,
            content_id BIGINT(20) DEFAULT NULL,
            user_id      BIGINT(20) DEFAULT NULL,
            verb         VARCHAR(20),
            verb_id      TEXT,
            activity_name TEXT,
            activity_description TEXT,
            activity_choices TEXT,
            activity_target TEXT,
            activity_interaction_type TEXT,
            activity_correct_response_pattern TEXT,
            result_response TEXT,
            result_max_score INT,
            result_raw_score INT,
            result_scaled_score INT,
            result_min_score INT,
            result_completion BOOLEAN,
            result_success BOOLEAN,
            result_duration TEXT,
            created_at DATETIME,
            PRIMARY KEY (statement_id)
        ) $charset_collate;";

		$h5p_statement = "CREATE TABLE {$wpdb->prefix}tutor_h5p_statement (
            statement_id BIGINT(20) NOT NULL AUTO_INCREMENT,
            instructor_id BIGINT(20) DEFAULT NULL,
            content_id BIGINT(20) DEFAULT NULL,
            user_id      BIGINT(20) DEFAULT NULL,
            verb         VARCHAR(20),
            verb_id      TEXT,
            activity_name TEXT,
            activity_description TEXT,
            activity_choices TEXT,
            activity_target TEXT,
            activity_interaction_type TEXT,
            activity_correct_response_pattern TEXT,
            result_response TEXT,
            result_max_score INT,
            result_raw_score INT,
            result_scaled_score INT,
            result_min_score INT,
            result_completion BOOLEAN,
            result_success BOOLEAN,
            result_duration TEXT,
            created_at DATETIME,
            PRIMARY KEY (statement_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $h5p_quiz_result_sql );
		dbDelta( $h5p_quiz_statement_sql );
		dbDelta( $h5p_lesson_statement_sql );
		dbDelta( $h5p_statement );
	}
}
