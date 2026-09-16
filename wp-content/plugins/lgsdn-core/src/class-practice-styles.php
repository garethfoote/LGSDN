<?php
/**
 * Controlled visual presentation metadata for Practice terms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Practice_Styles {
	public const COLOUR_META = 'lgsdn_practice_colour';

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
	}

	public static function register_meta(): void {
		$auth_callback = static function (): bool {
			return current_user_can( 'manage_options' );
		};

		register_term_meta(
			'lgsdn_practice',
			self::COLOUR_META,
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => 'orange',
				'sanitize_callback' => array( __CLASS__, 'sanitize_colour' ),
				'auth_callback' => $auth_callback,
			)
		);
	}

	public static function colours(): array {
		return LGSDN_Service_Styles::colours();
	}

	public static function sanitize_colour( mixed $value ): string {
		return LGSDN_Service_Styles::sanitize_colour( $value );
	}

	/**
	 * Return the safe, complete visual style for a Practice term.
	 */
	public static function for_term( WP_Term|int $term ): array {
		$term_id = $term instanceof WP_Term ? $term->term_id : absint( $term );
		$colour_key = self::sanitize_colour( get_term_meta( $term_id, self::COLOUR_META, true ) );
		$colours = self::colours();

		return array(
			'colour' => $colour_key,
			'background' => $colours[ $colour_key ]['background'],
			'contrast' => $colours[ $colour_key ]['contrast'],
			'foreground' => $colours[ $colour_key ]['foreground'],
		);
	}

}
