<?php
/**
 * Controlled visual presentation metadata for Practice terms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Practice_Styles {
	public const COLOUR_META = 'lgsdn_practice_colour';
	private const DEFAULT_COLOUR = 'orange';
	private const CONTOUR_DIRECTORY = 'assets/images';
	private const CONTOUR_PREFIX = 'practice-contour-';

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'admin_notices', array( __CLASS__, 'render_missing_contour_notice' ) );
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

	public static function contour_path( string $colour ): string {
		$colour = self::sanitize_colour( $colour );
		$path = self::expected_contour_path( $colour );

		if ( is_readable( $path ) ) {
			return $path;
		}

		$fallback_path = self::expected_contour_path( self::DEFAULT_COLOUR );

		return is_readable( $fallback_path ) ? $fallback_path : '';
	}

	public static function render_missing_contour_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$missing = array();
		foreach ( array_keys( self::colours() ) as $colour ) {
			if ( ! is_readable( self::expected_contour_path( $colour ) ) ) {
				$missing[] = self::contour_filename( $colour );
			}
		}

		if ( ! $missing ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo '<strong>LGSDN Playbook:</strong> ';
		echo esc_html(
			sprintf(
				'Missing practice contour %s: %s. Add the missing SVG %s to the active theme’s %s directory. Cards will use the orange contour when it is available.',
				1 === count( $missing ) ? 'file' : 'files',
				implode( ', ', $missing ),
				1 === count( $missing ) ? 'file' : 'files',
				self::CONTOUR_DIRECTORY
			)
		);
		echo '</p></div>';
	}

	private static function contour_filename( string $colour ): string {
		return self::CONTOUR_PREFIX . self::sanitize_colour( $colour ) . '.svg';
	}

	private static function expected_contour_path( string $colour ): string {
		return get_theme_file_path( self::CONTOUR_DIRECTORY . '/' . self::contour_filename( $colour ) );
	}
}
