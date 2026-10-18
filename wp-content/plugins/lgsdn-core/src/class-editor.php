<?php
/**
 * Keep editorial tools focused and protect layout decisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Editor {
	public static function hooks(): void {
		add_filter( 'block_editor_settings_all', array( __CLASS__, 'block_editor_settings' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_block_editor' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_image_styles' ) );
	}

	/** Add a reusable window frame option to WordPress image blocks. */
	public static function register_image_styles(): void {
		if ( function_exists( 'register_block_style' ) ) {
			register_block_style(
				'core/image',
				array(
					'name'  => 'window',
					'label' => 'Window',
				)
			);
		}
	}

	public static function block_editor_settings( array $settings ): array {
		$settings['canLockBlocks'] = current_user_can( 'edit_theme_options' );
		return $settings;
	}

	public static function use_block_editor( bool $use_block_editor, string $post_type ): bool {
		return in_array( $post_type, array( 'page', 'post', 'lgsdn_person', 'lgsdn_playbook' ), true )
			? true
			: $use_block_editor;
	}
}
