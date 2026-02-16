<?php
/**
 * Handle H5P Results
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
 * Result class
 */
class Results {

	/**
	 * Provide the result response of h5p statements.
	 *
	 * @since 3.0.0
	 *
	 * @param object $statement the statement object.
	 * @param array  $choices possible choices for result.
	 * @param string $correct_response_pattern correct response pattern.
	 * @param array  $h5p_targets possible targets for result.
	 *
	 * @return array
	 */
	public static function get_h5p_statement_result_response( $statement, $choices, $correct_response_pattern, $h5p_targets ) {
		$response_results = array();
		if ( ! str_contains( $correct_response_pattern, '[,]' ) && str_contains( $correct_response_pattern, ',' ) ) {
			$correct_responses        = explode( ',', $correct_response_pattern );
			$correct_response_pattern = implode( '[,]', $correct_responses );
		}

		if ( is_array( $choices ) && count( $choices ) ) {

			$all_choices = array_column( $choices, 'description', 'id' );

			if ( str_contains( $correct_response_pattern, '[,]' ) && ! str_contains( $correct_response_pattern, '[.]' ) ) {
				$correct_response_ids = explode( '[,]', $correct_response_pattern );
				$user_response_ids    = explode( '[,]', $statement->result_response );

				self::prepare_choices_statements_correct_response_results( $correct_response_ids, $user_response_ids, $response_results, $all_choices, $choices );

				if ( count( $choices ) === count( $user_response_ids ) ) {
					self::get_sequencing_statements_result_response( $response_results, $user_response_ids );
				} else {
					self::get_choices_statements_user_result_response( $user_response_ids, $response_results );
				}
			}

			if ( ! str_contains( $correct_response_pattern, '[,]' ) && ! str_contains( $correct_response_pattern, '[.]' ) ) {
				$user_response = $statement->result_response;

				if ( isset( $statement->activity_interaction_type ) && 'true-false' === $statement->activity_interaction_type ) {
					self::get_true_false_statement_response( $correct_response_pattern, $user_response, $response_results );
				} else {
					self::get_choices_statement_response( $all_choices, $correct_response_pattern, $user_response, $response_results );
				}
			}

			if ( str_contains( $correct_response_pattern, '[.]' ) ) {
				self::get_drag_and_drop_statements_result_response( $correct_response_pattern, $statement, $h5p_targets, $choices, $all_choices, $response_results );
			}
		}
		if ( isset( $correct_response_pattern ) && ! isset( $choices ) ) {
			if ( str_contains( $correct_response_pattern, '[,]' ) && ! str_contains( $correct_response_pattern, '[.]' ) ) {
				$correct_responses = explode( '[,]', $correct_response_pattern );
				$user_responses    = explode( '[,]', $statement->result_response );

				if ( count( $correct_responses ) === count( $user_responses ) ) {
					if ( isset( $statement->activity_interaction_type ) && 'fill-in' === $statement->activity_interaction_type ) {
						self::get_fill_in_statement_response( $statement, $user_responses, $correct_responses, $response_results );
					} else {
						self::get_sequencing_statements_result_response( $response_results, $user_responses );
					}
				} else {
					self::prepare_choices_statements_correct_response_results( $correct_responses, $user_responses, $response_results );
					self::get_choices_statements_user_result_response( $user_responses, $response_results );
				}
			}

			if ( ! str_contains( $correct_response_pattern, '[,]' ) && ! str_contains( $correct_response_pattern, '[.]' ) ) {
				$user_response = $statement->result_response;

				if ( isset( $statement->activity_interaction_type ) && 'true-false' === $statement->activity_interaction_type ) {
					self::get_true_false_statement_response( $correct_response_pattern, $user_response, $response_results );
				}
			}
		}

		if ( isset( $statement->activity_interaction_type ) &&  'long-fill-in' === $statement->activity_interaction_type ) {
			self::get_essay_statement_response( $statement, $response_results );
		}

		return $response_results;
	}

	/**
	 * Provide the correct response for choices h5p statement in response result array.
	 *
	 * @since 3.0.0
	 *
	 * @param array $correct_responses the correct responses for the h5p content.
	 * @param array $user_responses the user provided responses for the content.
	 * @param array $response_results the final response result.
	 * @param array $all_choices possible choices for h5p content that could be correct.
	 * @param array $choices possible choices for the h5p content.
	 *
	 * @return void
	 */
	public static function prepare_choices_statements_correct_response_results( $correct_responses, $user_responses, &$response_results, $all_choices = null, $choices = null ) {

		if ( isset( $all_choices ) && isset( $choices ) ) {
			foreach ( $correct_responses as $response_id ) {
				if ( isset( $all_choices[ $response_id ] ) && count( $choices ) === count( $user_responses ) ) {
					$response_results[ $response_id ] = (object) array(
						'is_correct'  => true,
						'description' => Utils::get_xpi_locale_property( $all_choices[ $response_id ] ),
					);
					unset( $all_choices[ $response_id ] );
				} elseif ( isset( $all_choices[ $response_id ] ) ) {
					$response_results[ $response_id ] = (object) array(
						'is_solution' => true,
						'description' => Utils::get_xpi_locale_property( $all_choices[ $response_id ] ),
					);
					unset( $all_choices[ $response_id ] );
				}
			}

			foreach ( $all_choices as $choice_id => $choice ) {
				$response_results[ $choice_id ] = (object) array(
					'description' => Utils::get_xpi_locale_property( $all_choices[ $choice_id ] ),
				);
			}
		} else {
			foreach ( $correct_responses as $response_id => $response ) {
				if ( count( $correct_responses ) === count( $user_responses ) ) {
					$response_results[ $response ] = (object) array(
						'is_correct'  => true,
						'description' => $response,
					);
					unset( $correct_responses[ $response_id ] );
				} else {
					$response_results[ $response ] = (object) array(
						'is_solution' => true,
						'description' => $response,
					);
					unset( $correct_responses[ $response_id ] );
				}
			}

			foreach ( $correct_responses as $response ) {
				$response_results[ $response ] = (object) array(
					'description' => $response,
				);
			}
		}
	}

	/**
	 * Provide the user result formatted for final result for choices type h5p content.
	 *
	 * @since 3.0.0
	 *
	 * @param array $user_responses the user provided responses for the h5p content.
	 * @param array $response_results the final result returned.
	 *
	 * @return void
	 */
	public static function get_choices_statements_user_result_response( $user_responses, &$response_results ) {
		foreach ( $user_responses as $user_id ) {
			$correct_response = $response_results[ $user_id ];
			if ( ! property_exists( $correct_response, 'is_correct' ) && ! property_exists( $correct_response, 'is_solution' ) ) {
				$correct_response               = (array) $correct_response;
				$correct_response['is_correct'] = false;
				$response_results[ $user_id ]   = (object) $correct_response;
			} elseif ( ! property_exists( $correct_response, 'is_correct' ) && property_exists( $correct_response, 'is_solution' ) ) {
				$correct_response               = (array) $correct_response;
				$correct_response['is_correct'] = true;
				unset( $correct_response['is_solution'] );
				$response_results[ $user_id ] = (object) $correct_response;
			}
		}
	}

	/**
	 * Sequencing type h5p content result response.
	 *
	 * @since 3.0.0
	 *
	 * @param array $response_results the final response result array.
	 * @param array $user_responses the user provided response for the h5p content.
	 *
	 * @return void
	 */
	public static function get_sequencing_statements_result_response( &$response_results, $user_responses ) {
		$idx = 0;
		foreach ( $response_results as $result_id => $result ) {
			// taking only the number portion from the value that contains the id.
			$user_response = (int) filter_var( $user_responses[ $idx ], FILTER_SANITIZE_NUMBER_INT );
			if ( $idx !== $user_response ) {
				$result->is_correct             = false;
				$response_results[ $result_id ] = $result;
			}
			++$idx;
		}
	}

	/**
	 * Drag and drop type h5p content result response.
	 *
	 * @since 3.0.0
	 *
	 * @param string $correct_response_pattern the correct response pattern for the content.
	 * @param object $statement the h5p statement object.
	 * @param array  $h5p_targets the possible targets for the h5p content.
	 * @param array  $choices the choices for the h5p content.
	 * @param array  $all_choices all possible choices for the h5p content.
	 * @param array  $response_results final response result array.
	 *
	 * @return void
	 */
	public static function get_drag_and_drop_statements_result_response( $correct_response_pattern, $statement, $h5p_targets, $choices, $all_choices, &$response_results ) {

		$correct_responses = explode( '[,]', $correct_response_pattern );
		$user_responses    = explode( '[,]', $statement->result_response );
		$all_targets       = is_array( $h5p_targets ) && count( $h5p_targets ) ? array_column( $h5p_targets, 'description', 'id' ) : null;

		foreach ( $correct_responses as $correct_response ) {

			$correct_response = explode( '[.]', $correct_response );
			$correct_response = $correct_response[1] . '[.]' . $correct_response[0];

			$correct_response                         = explode( '[.]', $correct_response );
			$matches                                  = (object) array(
				'description'       => Utils::get_xpi_locale_property( $all_choices[ $correct_response[0] ] ),
				'match_id'          => $correct_response[1],
				'match_description' => is_array( $all_targets ) && count( $all_targets ) > 1 ? Utils::get_xpi_locale_property( $all_targets[ $correct_response[1] ] ) : Utils::get_xpi_locale_property( $all_targets[0] ),
				'is_match'          => true,
			);
			$response_results[ $correct_response[0] ] = $matches;
		}

		$final_response = array();
		foreach ( $user_responses as $user_response ) {
			$user_response = explode( '[.]', $user_response );
			$user_response = $user_response[1] . '[.]' . $user_response[0];

			$user_response = explode( '[.]', $user_response );
			$response      = isset( $response_results[ $user_response[0] ] ) ? $response_results[ $user_response[0] ] : null;
			if ( $response ) {
				if ( $response->match_id !== $user_response[1] && count( $all_choices ) <= count( $all_targets ) ) {
					$matches                               = (object) array(
						'description'       => Utils::get_xpi_locale_property( $all_choices[ $user_response[0] ] ),
						'match_id'          => $user_response[1],
						'match_description' => is_array( $all_targets ) && count( $all_targets ) > 1 ? Utils::get_xpi_locale_property( $all_targets[ $user_response[1] ] ) : Utils::get_xpi_locale_property( $all_targets[0] ?? $all_choices[ $user_response[1] ] ?? $choices[ $user_response[1] ]->description ),
						'is_match'          => false,
					);
					$response_results[ $user_response[0] ] = $matches;
					array_push( $final_response, $response_results[ $user_response[0] ] );
				} elseif ( $response->match_id !== $user_response[1] ) {
					$matches = (object) array(
						'description'       => Utils::get_xpi_locale_property( $all_choices[ $user_response[0] ] ),
						'match_id'          => $user_response[1],
						'match_description' => is_array( $all_targets ) && count( $all_targets ) > 1 ? Utils::get_xpi_locale_property( $all_targets[ $user_response[1] ] ) : Utils::get_xpi_locale_property( $all_targets[0] ?? $all_choices[ $user_response[1] ] ?? $choices[ $user_response[1] ]->description ),
						'is_match'          => false,
					);
					array_push( $final_response, $matches );
				} else {
					array_push( $final_response, $response );
				}
			} else {
				$matches = (object) array(
					'description'       => Utils::get_xpi_locale_property( $all_choices[ $user_response[0] ] ),
					'match_id'          => $user_response[1],
					'match_description' => is_array( $all_targets ) && count( $all_targets ) > 1 ? Utils::get_xpi_locale_property( $all_targets[ $user_response[1] ] ) : Utils::get_xpi_locale_property( $all_targets[0] ?? $all_choices[ $user_response[1] ] ?? $choices[ $user_response[1] ]->description ),
					'is_match'          => false,
				);
				array_push( $final_response, $matches );
			}
		}
		$response_results = $final_response;
	}

	/**
	 * Get statement result response for choice type question
	 * such as multiple choice, single choice etc.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $all_choices the possible choices for h5p result.
	 * @param string $correct_response_pattern correct response pattern.
	 * @param string $user_response user given response pattern.
	 * @param array  $response_results the returned response result.
	 *
	 * @return void
	 */
	public static function get_choices_statement_response( $all_choices, $correct_response_pattern, $user_response, &$response_results ) {
		if ( isset( $all_choices[ $correct_response_pattern ] ) ) {
			$response_results[ $correct_response_pattern ] = (object) array(
				'is_correct'  => true,
				'description' => Utils::get_xpi_locale_property( $all_choices[ $correct_response_pattern ] ),
			);
			unset( $all_choices[ $correct_response_pattern ] );
		}

		foreach ( $all_choices as $choice_id => $choice ) {
			$response_results[ $choice_id ] = (object) array(
				'description' => Utils::get_xpi_locale_property( $all_choices[ $choice_id ] ),
			);
		}

		$correct_response = (array) $response_results[ $user_response ];
		if ( ! isset( $correct_response['is_correct'] ) ) {
			$correct_response['is_correct'] = false;
		}

		$response_results[ $user_response ] = (object) $correct_response;
	}

	/**
	 * Get statement result for true false type h5p content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $correct_response_pattern the correct response pattern.
	 * @param string $user_response the user provided response pattern.
	 * @param array  $response_results the final returned response result.
	 *
	 * @return void
	 */
	public static function get_true_false_statement_response( $correct_response_pattern, $user_response, &$response_results ) {
		$response_results = array(
			'true'  => (object) array(
				'description' => __( 'True', 'tutor-pro' ),
			),
			'false' => (object) array(
				'description' => __( 'False', 'tutor-pro' ),
			),
		);

		$correct_response                              = (array) $response_results[ $correct_response_pattern ];
		$correct_response['is_correct']                = true;
		$response_results[ $correct_response_pattern ] = (object) $correct_response;

		$correct_response = (array) $response_results[ $user_response ];

		if ( ! isset( $correct_response['is_correct'] ) ) {
			$correct_response['is_correct'] = false;
		}

		$response_results[ $user_response ] = (object) $correct_response;
	}

	/**
	 * Get response result for fill in the blank type h5p content.
	 *
	 * @since 3.0.0
	 *
	 * @param object $statement the statement object.
	 * @param array  $user_responses the user provided response array.
	 * @param array  $correct_responses the possible correct response array.
	 * @param array  $response_results the returned response result.
	 *
	 * @return void
	 */
	public static function get_fill_in_statement_response( $statement, $user_responses, $correct_responses, &$response_results ) {
		$content          = Utils::addon_config()->h5p_plugin->get_content( $statement->content_id );
		$content_settings = Utils::addon_config()->h5p_plugin->get_content_settings( $content );
		$main_content     = isset( json_decode( $content_settings['jsonContent'] )->content ) ? json_decode( $content_settings['jsonContent'] )->content : null;
		if ( isset( $main_content ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$correct_responses = $main_content->blanksList;
			foreach ( $user_responses as $key => $user_response ) {
				$haystack = (object) $correct_responses[ $key ];
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$haystack = html_entity_decode( $haystack->correctAnswerText );
				$haystack = explode( '/', $haystack );
				if ( in_array( $user_response, $haystack, true ) ) {
					array_push(
						$response_results,
						(object) array(
							'description' => $user_response,
							'is_correct'  => true,
						)
					);
				} else {
					array_push(
						$response_results,
						(object) array(
							'description' => $user_response,
							'is_correct'  => false,
						)
					);
				}
			}
		} else {
			foreach ( $user_responses as $key => $user_response ) {
				$correct_answer = strtolower( preg_replace( '/\{[^{]+\}/', '', $correct_responses[ $key ] ) );
				$correct_answer = str_replace( ' ', '', preg_replace( '/[^a-zA-Z0-9]+/', '', $correct_answer ) );
				$user_answer    = strtolower( str_replace( ' ', '', preg_replace( '/[^a-zA-Z0-9]+/', '', $user_response ) ) );
				if ( $correct_answer === $user_answer ) {
					array_push(
						$response_results,
						(object) array(
							'description' => $user_answer,
							'is_correct'  => true,
						)
					);
				} else {
					array_push(
						$response_results,
						(object) array(
							'description' => $user_answer,
							'is_correct'  => false,
						)
					);
				}
			}
		}
	}

	/**
	 * Get essay type h5p content statement result.
	 *
	 * @since 3.0.0
	 *
	 * @param object $statement the statement object.
	 * @param array  $response_results the final returned response result.
	 *
	 * @return void
	 */
	public static function get_essay_statement_response( $statement, &$response_results ) {
		$content          = Utils::addon_config()->h5p_plugin->get_content( $statement->content_id );
		$content_settings = Utils::addon_config()->h5p_plugin->get_content_settings( $content );
		$essay_keywords   = isset( json_decode( $content_settings['jsonContent'] )->keywords ) ? json_decode( $content_settings['jsonContent'] )->keywords : null;

		if ( isset( $statement->activity_interaction_type ) && 'long-fill-in' === $statement->activity_interaction_type ) {
			$user_answer = $statement->result_response;
			if ( isset( $essay_keywords ) ) {
				foreach ( $essay_keywords as $essay_keyword ) {
					if ( isset( $essay_keyword->keyword ) ) {
						$user_answer = str_replace(
							preg_replace( '/[^a-zA-Z]+/', '', $essay_keyword->keyword ),
							"<span class='tutor-fw-bold tutor-color-success'>" . esc_attr( preg_replace( '/[^a-zA-Z]+/', '', $essay_keyword->keyword ) ) . '</span>',
							$user_answer
						);
					}
					if ( isset( $essay_keyword->alternatives ) ) {
						foreach ( $essay_keyword->alternatives as $alternative ) {
							$user_answer = str_replace(
								preg_replace( '/[^a-zA-Z]+/', '', $alternative ),
								"<span class='tutor-fw-bold tutor-color-success'>" . esc_attr( preg_replace( '/[^a-zA-Z]+/', '', $alternative ) ) . '</span>',
								$user_answer
							);
						}
					}
				}
				array_push(
					$response_results,
					array(
						'essay_result' => $user_answer,
					)
				);
			}
		}
	}
}
