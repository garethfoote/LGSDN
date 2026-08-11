<?php
/**
 * Playbook taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Taxonomies {
	public const CONTROLLED = array(
		'lgsdn_service' => array( 'Service', 'Services' ),
		'lgsdn_practice' => array( 'Practice', 'Practices' ),
		'lgsdn_purpose' => array( 'Purpose', 'Purposes' ),
		'lgsdn_challenge' => array( 'Challenge', 'Challenges' ),
	);

	/**
	 * Register the five facets used to browse Playbook items.
	 */
	public static function register(): void {
		foreach ( self::CONTROLLED as $taxonomy => $names ) {
			self::register_taxonomy( $taxonomy, $names[0], $names[1], array( 'lgsdn_playbook' ), false );
		}

		self::register_taxonomy(
			'lgsdn_council',
			'Council',
			'Councils',
			array( 'lgsdn_playbook', 'lgsdn_person' ),
			true
		);
	}

	private static function register_taxonomy(
		string $taxonomy,
		string $singular,
		string $plural,
		array $object_types,
		bool $editors_can_manage
	): void {
		$management_capability = $editors_can_manage ? 'manage_categories' : 'manage_options';

		register_taxonomy(
			$taxonomy,
			$object_types,
			array(
				'labels' => array(
					'name' => $plural,
					'singular_name' => $singular,
					'search_items' => sprintf( 'Search %s', strtolower( $plural ) ),
					'all_items' => sprintf( 'All %s', strtolower( $plural ) ),
					'edit_item' => sprintf( 'Edit %s', strtolower( $singular ) ),
					'update_item' => sprintf( 'Update %s', strtolower( $singular ) ),
					'add_new_item' => sprintf( 'Add new %s', strtolower( $singular ) ),
					'new_item_name' => sprintf( 'New %s name', strtolower( $singular ) ),
					'menu_name' => $plural,
				),
				'public' => true,
				'publicly_queryable' => true,
				'hierarchical' => false,
				'show_in_rest' => true,
				'show_admin_column' => true,
				'rewrite' => array( 'slug' => strtolower( $plural ) ),
				'capabilities' => array(
					'manage_terms' => $management_capability,
					'edit_terms' => $management_capability,
					'delete_terms' => $management_capability,
					'assign_terms' => 'edit_posts',
				),
			)
		);
	}
}

