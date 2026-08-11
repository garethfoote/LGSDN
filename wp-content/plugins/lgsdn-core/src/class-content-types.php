<?php
/**
 * Custom post types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Content_Types {
	/**
	 * Register all site-owned post types.
	 */
	public static function register(): void {
		register_post_type(
			'lgsdn_playbook',
			array(
				'labels' => self::labels( 'Playbook item', 'Playbook items' ),
				'public' => true,
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => array( 'slug' => 'playbook' ),
				'menu_icon' => 'dashicons-lightbulb',
				'menu_position' => 20,
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
				'template_lock' => false,
			)
		);

		register_post_type(
			'lgsdn_person',
			array(
				'labels' => self::labels( 'Person', 'People' ),
				'public' => true,
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => array( 'slug' => 'network' ),
				'menu_icon' => 'dashicons-groups',
				'menu_position' => 21,
				'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);

		register_post_type(
			'lgsdn_event',
			array(
				'labels' => self::labels( 'Event', 'Events' ),
				'public' => true,
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => array( 'slug' => 'events' ),
				'menu_icon' => 'dashicons-calendar-alt',
				'menu_position' => 22,
				'supports' => array( 'title', 'revisions', 'custom-fields' ),
			)
		);
	}

	/**
	 * Build the common WordPress labels for a post type.
	 */
	private static function labels( string $singular, string $plural ): array {
		return array(
			'name' => $plural,
			'singular_name' => $singular,
			'add_new_item' => sprintf( 'Add new %s', strtolower( $singular ) ),
			'edit_item' => sprintf( 'Edit %s', strtolower( $singular ) ),
			'new_item' => sprintf( 'New %s', strtolower( $singular ) ),
			'view_item' => sprintf( 'View %s', strtolower( $singular ) ),
			'search_items' => sprintf( 'Search %s', strtolower( $plural ) ),
			'not_found' => sprintf( 'No %s found', strtolower( $plural ) ),
			'all_items' => sprintf( 'All %s', strtolower( $plural ) ),
		);
	}
}
