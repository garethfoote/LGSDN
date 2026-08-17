<?php
/**
 * LGSDN theme setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$theme = wp_get_theme();
		$asset_version = static function ( string $relative_path ) use ( $theme ): string {
			$path = get_theme_file_path( $relative_path );
			return file_exists( $path ) ? (string) filemtime( $path ) : $theme->get( 'Version' );
		};

		wp_enqueue_style(
			'lgsdn-reset',
			get_theme_file_uri( 'assets/css/reset.css' ),
			array(),
			$asset_version( 'assets/css/reset.css' )
		);
		wp_enqueue_style(
			'lgsdn',
			get_stylesheet_uri(),
			array( 'lgsdn-reset' ),
			$asset_version( 'style.css' )
		);

		if ( is_front_page() ) {
			wp_enqueue_style(
				'lgsdn-homepage-prototype',
				get_theme_file_uri( 'assets/css/prototype.css' ),
				array( 'lgsdn' ),
				$asset_version( 'assets/css/prototype.css' )
			);
		}

		wp_enqueue_style(
			'lgsdn-header',
			get_theme_file_uri( 'assets/css/header.css' ),
			array( is_front_page() ? 'lgsdn-homepage-prototype' : 'lgsdn' ),
			$asset_version( 'assets/css/header.css' )
		);
		wp_enqueue_script(
			'lgsdn-navigation',
			get_theme_file_uri( 'assets/js/navigation.js' ),
			array(),
			$asset_version( 'assets/js/navigation.js' ),
			array( 'strategy' => 'defer', 'in_footer' => true )
		);

		if ( is_front_page() || is_page( 'playbook' ) ) {
			wp_enqueue_script(
				'lgsdn-service-area-cards',
				get_theme_file_uri( 'assets/js/service-area-cards.js' ),
				array(),
				$asset_version( 'assets/js/service-area-cards.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
		}

		if ( is_page( 'playbook' ) ) {
			wp_enqueue_script(
				'lgsdn-playbook-filters',
				get_theme_file_uri( 'assets/js/playbook-filters.js' ),
				array(),
				$asset_version( 'assets/js/playbook-filters.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
		}

		if ( is_page( 'design-system' ) || is_page_template( 'page-design-system' ) ) {
			wp_enqueue_script(
				'lgsdn-design-system',
				get_theme_file_uri( 'assets/js/design-system.js' ),
				array(),
				$asset_version( 'assets/js/design-system.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
		}
	}
);

add_action(
	'after_setup_theme',
	static function (): void {
		add_editor_style( array( 'assets/css/reset.css', 'style.css' ) );
	}
);
