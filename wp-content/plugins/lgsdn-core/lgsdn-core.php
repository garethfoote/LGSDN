<?php
/**
 * Plugin Name: LGSDN Core
 * Description: Structured content and editorial safeguards for the LGSDN website.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Author: Local Government Service Design Network
 * Text Domain: lgsdn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LGSDN_CORE_VERSION', '0.1.0' );
define( 'LGSDN_CORE_FILE', __FILE__ );
define( 'LGSDN_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once LGSDN_CORE_DIR . 'src/class-content-types.php';
require_once LGSDN_CORE_DIR . 'src/class-taxonomies.php';
require_once LGSDN_CORE_DIR . 'src/class-practice-styles.php';
require_once LGSDN_CORE_DIR . 'src/class-service-styles.php';
require_once LGSDN_CORE_DIR . 'src/class-fields.php';
require_once LGSDN_CORE_DIR . 'src/class-playbook-validation.php';
require_once LGSDN_CORE_DIR . 'src/class-homepage-fields.php';
require_once LGSDN_CORE_DIR . 'src/class-editor.php';
require_once LGSDN_CORE_DIR . 'src/class-installer.php';

add_action( 'init', array( 'LGSDN_Content_Types', 'register' ), 5 );
add_action( 'init', array( 'LGSDN_Taxonomies', 'register' ), 6 );
add_action( 'init', array( 'LGSDN_Fields', 'register_meta' ), 7 );
add_action( 'init', 'lgsdn_register_dynamic_blocks', 8 );
add_action( 'enqueue_block_editor_assets', 'lgsdn_enqueue_editor_assets' );

LGSDN_Fields::hooks();
LGSDN_Practice_Styles::hooks();
LGSDN_Service_Styles::hooks();
LGSDN_Playbook_Validation::hooks();
LGSDN_Homepage_Fields::hooks();
LGSDN_Editor::hooks();
LGSDN_Installer::hooks();

register_activation_hook( __FILE__, array( 'LGSDN_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LGSDN_Installer', 'deactivate' ) );

/**
 * Load editor-only safeguards for Playbook items.
 */
function lgsdn_enqueue_editor_assets(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'lgsdn_playbook' !== $screen->post_type ) {
		return;
	}

	$path = LGSDN_CORE_DIR . 'assets/js/playbook-validation.js';

	wp_enqueue_script(
		'lgsdn-playbook-validation',
		plugins_url( 'assets/js/playbook-validation.js', LGSDN_CORE_FILE ),
		array( 'wp-api-fetch', 'wp-data' ),
		file_exists( $path ) ? (string) filemtime( $path ) : LGSDN_CORE_VERSION,
		true
	);
}

/**
 * Register server-rendered blocks used by the theme templates.
 */
function lgsdn_register_dynamic_blocks(): void {
	$homepage_preview_path = get_theme_file_path( 'assets/css/homepage.css' );
	$homepage_editor_path = LGSDN_CORE_DIR . 'blocks/homepage/index.js';
	wp_register_style(
		'lgsdn-homepage-editor',
		get_theme_file_uri( 'assets/css/homepage.css' ),
		array(),
		file_exists( $homepage_preview_path ) ? (string) filemtime( $homepage_preview_path ) : LGSDN_CORE_VERSION
	);
	wp_register_script(
		'lgsdn-homepage-editor',
		plugins_url( 'blocks/homepage/index.js', LGSDN_CORE_FILE ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-server-side-render' ),
		file_exists( $homepage_editor_path ) ? (string) filemtime( $homepage_editor_path ) : LGSDN_CORE_VERSION,
		true
	);
	wp_localize_script(
		'lgsdn-homepage-editor',
		'lgsdnHomepageEditor',
		array(
			'imageBase' => get_theme_file_uri( 'assets/images' ),
			'defaults' => LGSDN_Homepage_Fields::defaults(),
		)
	);

	register_block_type( LGSDN_CORE_DIR . 'blocks/featured-playbook' );
	register_block_type( LGSDN_CORE_DIR . 'blocks/events-list' );
	register_block_type( LGSDN_CORE_DIR . 'blocks/homepage' );
	register_block_type( LGSDN_CORE_DIR . 'blocks/playbook-index' );
}
