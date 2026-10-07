<?php
/**
 * Editable content for the Playbook landing-page contribution banner.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Playbook_Page_Fields {
	private const DEFAULTS = array(
		'lgsdn_playbook_contribute_title' => 'Contribute',
		'lgsdn_playbook_contribute_body' => 'We capture insights from our innovation processes to share learnable knowledge — through studies, analyses, data visualisations, and publications.',
		'lgsdn_playbook_contribute_button_label' => 'Contribute',
		'lgsdn_playbook_contribute_button_url' => '/contribute/',
	);

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_filter( 'allowed_block_types_all', array( __CLASS__, 'limit_blocks' ), 10, 2 );
	}

	public static function register_meta(): void {
		foreach ( self::DEFAULTS as $key => $default ) {
			register_post_meta(
				'page',
				$key,
				array(
					'single' => true,
					'type' => 'string',
					'show_in_rest' => true,
					'default' => $default,
					'sanitize_callback' => 'lgsdn_playbook_contribute_button_url' === $key ? 'esc_url_raw' : ( 'lgsdn_playbook_contribute_body' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
					'auth_callback' => static function (): bool {
						return current_user_can( 'edit_pages' );
					},
				)
			);
		}
	}

	public static function limit_blocks( $allowed_block_types, WP_Block_Editor_Context $context ) {
		if ( $context->post instanceof WP_Post && 'page' === $context->post->post_type && 'playbook' === $context->post->post_name ) {
			return array( 'lgsdn/playbook-index' );
		}

		return $allowed_block_types;
	}

	public static function value( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );
		return '' !== $value ? (string) $value : ( self::DEFAULTS[ $key ] ?? '' );
	}

	public static function defaults(): array {
		return self::DEFAULTS;
	}
}
