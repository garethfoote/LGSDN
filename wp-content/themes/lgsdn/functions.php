<?php
/**
 * LGSDN theme setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Partner artwork stays local; names remain usable while artwork is supplied. */
add_shortcode( 'lgsdn_footer_partners', static function (): string {
	$partners = array(
		'ahrc' => array( 'Arts and Humanities Research Council', 'https://www.ukri.org/councils/ahrc/' ),
		'camden' => array( 'Camden Council', 'https://www.camden.gov.uk/' ),
		'ual' => array( 'University of the Arts London', 'https://www.arts.ac.uk/' ),
	);
	$html = '<div class="lgsdn-site-footer__partners" aria-label="Supporting organisations">';
	foreach ( $partners as $slug => $partner ) {
		$file = 'assets/images/partners/' . $slug . '-white.svg';
		if ( 'camden' === $slug ) {
			$file = 'assets/images/partners/camden-white-01.svg';
		}
		if ( 'ahrc' === $slug && ! is_readable( get_theme_file_path( $file ) ) ) {
			$file = 'assets/images/partners/ahrc.png';
		}
		$html .= '<a class="lgsdn-site-footer__partner lgsdn-site-footer__partner--' . esc_attr( $slug ) . '" href="' . esc_url( $partner[1] ) . '">';
		$html .= is_readable( get_theme_file_path( $file ) )
			? '<img src="' . esc_url( get_theme_file_uri( $file ) ) . '" alt="' . esc_attr( $partner[0] ) . '">'
			: esc_html( $partner[0] );
		$html .= '</a>';
	}
	return $html . '</div>';
} );

add_action(
	'wp_head',
	static function (): void {
		$favicon_uri = trailingslashit( get_theme_file_uri( 'assets/favicons' ) );
		?>
		<link rel="icon" href="<?php echo esc_url( $favicon_uri . 'favicon.ico' ); ?>" sizes="any">
		<link rel="icon" href="<?php echo esc_url( $favicon_uri . 'favicon.svg' ); ?>" type="image/svg+xml">
		<link rel="apple-touch-icon" href="<?php echo esc_url( $favicon_uri . 'apple-touch-icon.png' ); ?>">
		<link rel="manifest" href="<?php echo esc_url( $favicon_uri . 'site.webmanifest' ); ?>">
		<meta name="theme-color" content="#F8F8F1">
		<?php
	}
);

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
		wp_enqueue_style(
			'lgsdn-buttons',
			get_theme_file_uri( 'assets/css/buttons.css' ),
			array( 'lgsdn' ),
			$asset_version( 'assets/css/buttons.css' )
		);

		if ( is_front_page() ) {
			wp_enqueue_style(
				'lgsdn-homepage',
				get_theme_file_uri( 'assets/css/homepage.css' ),
				array( 'lgsdn-buttons' ),
				$asset_version( 'assets/css/homepage.css' )
			);
		}

		wp_enqueue_style(
			'lgsdn-header',
			get_theme_file_uri( 'assets/css/header.css' ),
			array( is_front_page() ? 'lgsdn-homepage' : 'lgsdn-buttons' ),
			$asset_version( 'assets/css/header.css' )
		);
		wp_enqueue_style(
			'lgsdn-footer',
			get_theme_file_uri( 'assets/css/footer.css' ),
			array( 'lgsdn-header' ),
			$asset_version( 'assets/css/footer.css' )
		);
		wp_enqueue_script(
			'lgsdn-navigation',
			get_theme_file_uri( 'assets/js/navigation.js' ),
			array(),
			$asset_version( 'assets/js/navigation.js' ),
			array( 'strategy' => 'defer', 'in_footer' => true )
		);

		if ( is_front_page() || is_page( 'library' ) ) {
			wp_enqueue_script(
				'lgsdn-service-area-cards',
				get_theme_file_uri( 'assets/js/service-area-cards.js' ),
				array(),
				$asset_version( 'assets/js/service-area-cards.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
		}

		if ( is_front_page() ) {
			wp_enqueue_script(
				'lgsdn-homepage-spotlight',
				get_theme_file_uri( 'assets/js/homepage-spotlight.js' ),
				array(),
				$asset_version( 'assets/js/homepage-spotlight.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
			wp_enqueue_script(
				'lgsdn-hero-graphic',
				get_theme_file_uri( 'assets/js/hero-graphic.js' ),
				array(),
				$asset_version( 'assets/js/hero-graphic.js' ),
				array( 'strategy' => 'defer', 'in_footer' => true )
			);
			wp_enqueue_script_module(
				'lgsdn/hero-graphic-motion',
				get_theme_file_uri( 'assets/js/hero-graphic-motion.js' ),
				array(),
				$asset_version( 'assets/js/hero-graphic-motion.js' )
			);
		}

		if ( is_page( 'library' ) ) {
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
		add_editor_style( array( 'assets/css/reset.css', 'style.css', 'assets/css/buttons.css', 'assets/css/footer.css' ) );
	}
);

// Home's featured image supplies the experimental join panel.
add_action( 'after_setup_theme', static function (): void { add_theme_support( 'post-thumbnails' ); } );
