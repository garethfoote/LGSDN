<?php
/** Editable introduction for service area landing pages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class LGSDN_Service_Content {
	public const INTRO_META = 'lgsdn_service_introduction';

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'lgsdn_service_edit_form_fields', array( __CLASS__, 'render_edit' ) );
		add_action( 'created_lgsdn_service', array( __CLASS__, 'save' ) );
		add_action( 'edited_lgsdn_service', array( __CLASS__, 'save' ) );
	}

	public static function register_meta(): void {
		register_term_meta( 'lgsdn_service', self::INTRO_META, array(
			'type' => 'string', 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => 'wp_kses_post',
			'auth_callback' => static function (): bool { return current_user_can( 'manage_options' ); },
		) );
	}

	private static function editor( string $value ): void {
		wp_nonce_field( 'lgsdn_save_service_content', 'lgsdn_service_content_nonce' );
		wp_editor( $value, self::INTRO_META, array(
			'textarea_name' => self::INTRO_META, 'textarea_rows' => 8, 'media_buttons' => false,
			'tinymce' => array(
				'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
				'toolbar2' => '', 'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3',
			),
		) );
		echo '<p class="description">Short introduction shown above the case studies. Leave blank to use the description.</p>';
	}

	public static function render_edit( WP_Term $term ): void {
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( self::INTRO_META ) . '">Introduction</label></th><td>';
		self::editor( (string) get_term_meta( $term->term_id, self::INTRO_META, true ) );
		echo '</td></tr>';
	}

	public static function save( int $term_id ): void {
		if ( ! current_user_can( 'edit_term', $term_id ) ||
			! isset( $_POST['lgsdn_service_content_nonce'], $_POST[ self::INTRO_META ] ) ||
			! is_string( $_POST['lgsdn_service_content_nonce'] ) || ! is_string( $_POST[ self::INTRO_META ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lgsdn_service_content_nonce'] ) ), 'lgsdn_save_service_content' )
		) { return; }
		update_term_meta( $term_id, self::INTRO_META, wp_kses_post( wp_unslash( $_POST[ self::INTRO_META ] ) ) );
	}
}
