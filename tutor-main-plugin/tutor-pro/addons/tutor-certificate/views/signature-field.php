<?php
/**
 * Certificate Signature Field
 *
 * @package TutorPro/Addons
 * @subpackage Certificate
 * @author Themeum <support@themeum.com>
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

use TUTOR\Icon;
use Tutor\Components\FileUploader;

$file_id_string = $this->file_id_string;
?>

<div class="tutor-flex tutor-flex-column tutor-gap-4">
	<h5 class="tutor-h5"><?php echo esc_html__( 'Certificate Signature', 'tutor-pro' ); ?></h5>

	<div class="tutor-card tutor-card-rounded-2xl tutor-pb-6">
		<?php
			FileUploader::make()
				->name( $file_id_string )
				->id( $file_id_string )
				->variant( FileUploader::IMAGE_UPLOADER )
				->uploader_icon( Icon::SIGNATURE_UPLOAD )
				->accept( '.png,.jpg,.jpeg' )
				->use_wp_media()
				->wp_media_title( __( 'Select Signature', 'tutor-pro' ) )
				->wp_media_button_text( __( 'Use this signature', 'tutor-pro' ) )
				->wp_media_library_type( 'image' )
				->render();
		?>
	</div>
</div>