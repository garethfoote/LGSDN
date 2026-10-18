<?php
/** WordPress crop dialog integration for independent Playbook image files. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Playbook_Images {
	public const SLOTS = array(
		'card' => array( 'width' => 1200, 'height' => 800, 'ratio_label' => '3:2' ),
		'article' => array( 'width' => 1500, 'height' => 400, 'ratio_label' => '15:4' ),
	);

	public static function hooks(): void {
		add_action( 'wp_ajax_lgsdn_crop_playbook_image', array( __CLASS__, 'crop' ) );
		add_action( 'rest_after_insert_lgsdn_playbook', array( __CLASS__, 'sync_card_thumbnail' ), 10, 2 );
	}

	/** Keep WordPress's underlying featured image aligned with the required card. */
	public static function sync_card_thumbnail( WP_Post $post, WP_REST_Request $request ): void {
		$meta = $request->get_param( 'meta' );
		if ( ! is_array( $meta ) || ! array_key_exists( 'lgsdn_card_image_id', $meta ) ) {
			return;
		}
		$card_id = absint( $meta['lgsdn_card_image_id'] );
		if ( $card_id && wp_attachment_is_image( $card_id ) ) {
			set_post_thumbnail( $post->ID, $card_id );
		} else {
			delete_post_thumbnail( $post->ID );
		}
	}

	public static function crop(): void {
		check_ajax_referer( 'lgsdn_playbook_crop', 'nonce' );
		$post_id = absint( $_POST['post_id'] ?? 0 );
		$source_id = absint( $_POST['id'] ?? 0 );
		$slot = sanitize_key( wp_unslash( $_POST['slot'] ?? '' ) );
		if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'edit_post', $source_id ) || 'lgsdn_playbook' !== get_post_type( $post_id ) || ! isset( self::SLOTS[ $slot ] ) || ! wp_attachment_is_image( $source_id ) ) {
			wp_send_json_error( array( 'message' => 'You cannot crop this image.' ), 403 );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$source = get_attached_file( $source_id );
		$size = $source ? wp_getimagesize( $source ) : false;
		$crop = isset( $_POST['cropDetails'] ) && is_array( $_POST['cropDetails'] ) ? array_map( 'absint', wp_unslash( $_POST['cropDetails'] ) ) : array();
		$x = $crop['x1'] ?? 0;
		$y = $crop['y1'] ?? 0;
		$width = $crop['width'] ?? 0;
		$height = $crop['height'] ?? 0;
		$target = self::SLOTS[ $slot ];
		$ratio = $target['width'] / $target['height'];
		if ( ! $size || $width < 1 || $height < 1 || $x + $width > $size[0] || $y + $height > $size[1] || abs( $width - $height * $ratio ) > $ratio + 1 ) {
			wp_send_json_error( array( 'message' => 'Choose a crop within the image using the required shape.' ), 400 );
		}

		// Generate actual pixels at the chosen ratio, without enlarging the source.
		$scale = min( 1, $target['width'] / $width, $target['height'] / $height );
		$destination_width = max( 1, (int) round( $width * $scale ) );
		$destination_height = max( 1, (int) round( $height * $scale ) );
		$directory = dirname( $source );
		$destination = trailingslashit( $directory ) . wp_unique_filename( $directory, 'lgsdn-' . $slot . '-' . wp_basename( $source ) );
		$file = wp_crop_image( $source_id, $x, $y, $width, $height, $destination_width, $destination_height, false, $destination );
		if ( ! $file || is_wp_error( $file ) ) {
			wp_send_json_error( array( 'message' => 'WordPress could not crop this image. Try another image.' ), 400 );
		}
		$attachment = array(
			'post_title' => get_the_title( $source_id ) . ( 'card' === $slot ? ' — card' : ' — article header' ),
			'post_mime_type' => wp_get_image_mime( $file ),
			'post_status' => 'inherit',
		);
		$id = wp_insert_attachment( $attachment, $file, $post_id, true );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $file );
			wp_send_json_error( array( 'message' => 'The cropped image could not be saved.' ), 400 );
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
		update_post_meta( $id, '_wp_attachment_image_alt', get_post_meta( $source_id, '_wp_attachment_image_alt', true ) );
		update_post_meta( $id, '_lgsdn_crop_source_id', $source_id );
		update_post_meta( $id, '_lgsdn_crop_slot', $slot );
		$result = wp_prepare_attachment_for_js( $id );
		$result['lgsdnCropSourceId'] = $source_id;
		wp_send_json_success( $result );
	}
}
