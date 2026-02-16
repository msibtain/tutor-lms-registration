<?php
/**
 * H5P Quiz result modal
 *
 * @package TutorPro\Addons
 * @subpackage H5P\Views
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */


use TutorPro\H5P\Results;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<?php
if ( is_array( $h5p_quiz_result_statements ) && count( $h5p_quiz_result_statements ) ) {
	?>
				<div class="tutor-d-flex tutor-flex-column ">
		<?php
		foreach ( $h5p_quiz_result_statements as $statement ) {
			$choices                  = maybe_unserialize( $statement->activity_choices );
			$h5p_targets              = maybe_unserialize( $statement->activity_target );
			$correct_response_pattern = is_array( maybe_unserialize( $statement->activity_correct_response_pattern ) ) ? maybe_unserialize( $statement->activity_correct_response_pattern )[0] : null;
			$response_results         = isset( $correct_response_pattern ) ? Results::get_h5p_statement_result_response( $statement, $choices, $correct_response_pattern, $h5p_targets ) : Results::get_h5p_statement_result_response( $statement, $choices, null, $h5p_targets );
			?>
		<div class="tutor-d-flex tutor-flex-column tutor-mb-12">
			<?php
			if ( ! empty( $statement->activity_name ) ) {
				?>
					<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-mb-12">
						<p class="tutor-fs-5 tutor-text-regular tutor-fw-normal">
						<?php echo esc_attr( $statement->activity_name ); ?>
						</p>
						<p class="tutor-fs-5 <?php echo $statement->result_raw_score === $statement->result_max_score ? 'tutor-color-success' : 'tutor-color-danger'; ?> ">
						<?php echo esc_attr( $statement->result_raw_score ) . '/' . esc_attr( $statement->result_max_score ); ?>
						</p>
					</div>
					<?php

			} else {
				?>
					<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-mb-12">
						<p class="tutor-fs-5 tutor-text-regular tutor-fw-normal">
						<?php echo esc_attr( $statement->activity_description ); ?>
						</p>
						<p class="tutor-fs-5 tutor-text-regular">
						<?php echo esc_attr( $statement->result_raw_score ) . '/' . esc_attr( $statement->result_max_score ); ?>
						</p>
					</div>
					<?php
			}

			if ( ! empty( $statement->activity_description ) && $statement->activity_name !== $statement->activity_description ) {
				?>
					<div class="tutor-d-flex tutor-align-center tutor-justify-between tutor-mb-12">
						<p class="tutor-fs-6 tutor-text-regular tutor-fw-normal">
						<?php echo esc_attr( $statement->activity_description ); ?>
						</p>
						
					</div>
					<?php
			}

			if ( is_array( $response_results ) && count( $response_results ) ) {
				?>
					<div class="tutor-d-flex tutor-flex-column tutor-mb-16">
						<ul class="tutor-d-flex tutor-flex-column tutor-pl-0" style="padding-left: 0;">
						<?php
						foreach ( $response_results as $result ) {
							if ( ! is_null( $result ) && is_object( $result ) ) {
								$is_default = ! isset( $result->is_correct ) && ! isset( $result->is_solution ) && ! isset( $result->is_match );
								if ( $is_default ) {
									?>
											<li class="tutor-list-item tutor-bg-white tutor-border tutor-p-8 tutor-radius-10 tutor-d-flex tutor-align-center tutor-justify-between">
												<p class=" tutor-fw-normal">
											<?php echo esc_attr( stripslashes( $result->description ) ); ?>
												</p>
											</li>
										<?php
								} elseif ( isset( $result->is_match ) ) {
									if ( $result->is_match ) {
										?>
													<li class="tutor-list-item tutor-d-flex tutor-align-center tutor-justify-between">
														<div class=" tutor-d-inline-flex tutor-align-center tutor-justify-between">
															<div class="tutor-bg-white tutor-p-12 tutor-radius-10 tutor-border">
																<p class=" tutor-color-success tutor-fw-normal"><?php echo esc_attr( stripslashes( $result->match_description ) ); ?></p>
															</div>
															<div class="tutor-p-12"> 
																&equals;
															</div>
															<div class="tutor-bg-white tutor-p-12 tutor-radius-10 tutor-border">
																<p class="tutor-color-success tutor-fw-normal"><?php echo esc_attr( stripslashes( $result->description ) ); ?></p>
															</div>
														</div>
														<div>
															<span class="tutor-icon-mark tutor-color-success"></span>
														</div>
													</li>
											<?php
									} else {
										?>
													<li class="tutor-list-item tutor-d-flex tutor-align-center tutor-justify-between">
														<div class=" tutor-d-inline-flex tutor-align-center tutor-justify-between">
															<div class="tutor-bg-white tutor-p-12 tutor-radius-10 tutor-border">
																<p class=" tutor-color-success tutor-fw-normal"><?php echo esc_attr( stripslashes( $result->match_description ) ); ?></p>
															</div>
															<div class="tutor-p-12"> 
																&equals;
															</div>
															<div class="tutor-bg-white tutor-p-12 tutor-radius-10 tutor-border">
																<p class="tutor-color-danger tutor-fw-normal"><?php echo esc_attr( stripslashes( $result->description ) ); ?></p>
															</div>
														</div>
														<div>
															<span class="tutor-icon-times tutor-color-danger"></span>
														</div>
													</li>
											<?php
									}
								} elseif ( isset( $result->is_correct ) && true === $result->is_correct ) {
									?>
													<li class="tutor-list-item tutor-bg-white tutor-border tutor-p-8 tutor-radius-10 tutor-d-flex tutor-align-center tutor-justify-between">
														<p class="tutor-fw-normal tutor-color-success">
												<?php echo esc_attr( stripslashes( $result->description ) ); ?>
														</p>
														<div>
															<span class="tutor-icon-mark tutor-color-success"></span>
														</div>
													</li>
											<?php
								} elseif ( isset( $result->is_solution ) ) {
									?>
													<li class="tutor-list-item tutor-btn-ghost-light tutor-border tutor-p-8 tutor-radius-10 tutor-d-flex tutor-align-center tutor-justify-between">
														<p class="tutor-fw-normal tutor-color-muted">
											<?php echo esc_attr( stripslashes( $result->description ) ); ?>
														</p>
														<div>
															<span class="tutor-icon-mark tutor-color-muted"></span>
														</div>
													</li>
										<?php
								} else {
									?>
													<li class="tutor-list-item tutor-bg-white tutor-border tutor-p-8 tutor-radius-10 tutor-d-inline-flex tutor-align-center tutor-justify-between">
														<p class="tutor-fw-normal tutor-color-danger">
													<?php echo esc_attr( stripslashes( $result->description ) ); ?>
														</p>
														<div>
															<span class="tutor-icon-times tutor-color-danger"></span>
														</div>
													</li>
												<?php

								}
							} elseif ( isset( $result['essay_result'] ) ) {
								?>
										<li class="tutor-list-item tutor-bg-white tutor-border tutor-p-8 tutor-radius-10 tutor-d-flex tutor-align-center tutor-justify-between">
											<p class=" tutor-fw-normal tutor-break-all">
										<?php echo wp_kses_post( $result['essay_result'] ); ?>
											</p>
										</li>
									<?php

							}
						}
						?>
						</ul>
					</div>
					<?php
			}
			?>
		</div>
			<?php
		}
		?>
		</div>
		<?php
}
