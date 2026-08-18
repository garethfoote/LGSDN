<?php
/**
 * Versioned content-model installation and upgrades.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Installer {
	private const SCHEMA_VERSION = '7';
	private const OPTION_NAME = 'lgsdn_content_schema_version';

	private const TERMS = array(
		'lgsdn_service' => array(
			'Adult social care',
			'Children and families',
			'Housing and homelessness',
			'Planning and place',
			'Waste and environment',
			'Libraries and culture',
			'Customer services/contact centres',
			'Digital and online services',
			'Revenues and benefits',
			'Public health',
			'Community safety',
			'Transport, streets and neighbourhoods',
			'Democracy and participation',
		),
		'lgsdn_practice' => array(
			'User research',
			'Journey mapping',
			'Responsibility mapping',
			'Service mapping',
			'Service blueprinting',
			'Co-design',
			'Participatory workshops',
			'Prototyping',
			'Usability testing',
			'Design critique',
			'Research synthesis',
			'Accessibility and inclusive design',
			'Discovery and alpha work',
			'Stakeholder mapping',
			'Content design',
		),
		'lgsdn_purpose' => array(
			'Understand the problem',
			'Make sense of evidence',
			'Communicate insights and ideas',
			'Create options for change',
			'Prototype, test, learn and adapt',
			'Engage people',
			'Share and reuse',
		),
		'lgsdn_challenge' => array(
			'Silos',
			'Constrained budgets',
			'Political sensitivity',
			'Legacy systems',
			'Procurement barriers',
			'Limited design capacity',
			'Low design maturity',
			'Frontline pressure',
			'Data access',
			'Ethics and consent',
			'Organisational change',
			'Sustaining work after a project ends',
		),
	);

	public static function hooks(): void {
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
	}

	public static function activate(): void {
		LGSDN_Content_Types::register();
		LGSDN_Taxonomies::register();
		self::seed_terms();
		self::seed_practice_styles();
		self::seed_service_styles();
		self::migrate_primary_services();
		self::seed_homepage_links();
		self::seed_homepage_preview();
		update_option( self::OPTION_NAME, self::SCHEMA_VERSION, false );
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function maybe_upgrade(): void {
		if ( self::SCHEMA_VERSION === get_option( self::OPTION_NAME ) ) {
			return;
		}

		self::seed_terms();
		self::seed_practice_styles();
		self::seed_service_styles();
		self::migrate_primary_services();
		self::seed_homepage_links();
		self::seed_homepage_preview();
		update_option( self::OPTION_NAME, self::SCHEMA_VERSION, false );
	}

	private static function seed_terms(): void {
		foreach ( self::TERMS as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! term_exists( $term, $taxonomy ) ) {
					wp_insert_term( $term, $taxonomy );
				}
			}
		}
	}

	private static function seed_practice_styles(): void {
		$colours = array_keys( LGSDN_Service_Styles::colours() );
		$practices = get_terms(
			array(
				'taxonomy' => 'lgsdn_practice',
				'hide_empty' => false,
				'orderby' => 'term_id',
				'order' => 'ASC',
			)
		);

		if ( is_wp_error( $practices ) ) {
			return;
		}

		foreach ( $practices as $index => $practice ) {
			if ( ! metadata_exists( 'term', $practice->term_id, LGSDN_Practice_Styles::COLOUR_META ) ) {
				update_term_meta(
					$practice->term_id,
					LGSDN_Practice_Styles::COLOUR_META,
					$colours[ $index % count( $colours ) ]
				);
			}
		}
	}

	private static function seed_service_styles(): void {
		$services = get_terms(
			array(
				'taxonomy' => 'lgsdn_service',
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			)
		);

		if ( is_wp_error( $services ) ) {
			return;
		}

		foreach ( $services as $service ) {
			$defaults = LGSDN_Service_Styles::defaults_for_term( $service );
			foreach ( $defaults as $key => $value ) {
				if ( ! metadata_exists( 'term', $service->term_id, $key ) ) {
					update_term_meta( $service->term_id, $key, $value );
				}
			}

			// Democracy and participation existed before it had a dedicated
			// homepage card style, so move only its old untouched defaults forward.
			if ( 'democracy and participation' === strtolower( $service->name ) ) {
				$legacy_defaults = array(
					LGSDN_Service_Styles::COLOUR_META => 'orange',
					LGSDN_Service_Styles::ICON_META => 'service',
					LGSDN_Service_Styles::ORDER_META => 0,
					LGSDN_Service_Styles::FEATURED_META => false,
				);

				foreach ( $legacy_defaults as $key => $legacy_value ) {
					$current_value = get_term_meta( $service->term_id, $key, true );
					$is_legacy = match ( $key ) {
						LGSDN_Service_Styles::COLOUR_META => sanitize_key( (string) $current_value ) === $legacy_value,
						LGSDN_Service_Styles::ICON_META => sanitize_key( (string) $current_value ) === $legacy_value,
						LGSDN_Service_Styles::ORDER_META => absint( $current_value ) === $legacy_value,
						LGSDN_Service_Styles::FEATURED_META => in_array( $current_value, array( '', '0', 0, false ), true ),
						default => false,
					};

					if ( $is_legacy ) {
						update_term_meta( $service->term_id, $key, $defaults[ $key ] );
					}
				}
			}
		}
	}

	private static function migrate_primary_services(): void {
		$items = get_posts(
			array(
				'post_type' => 'lgsdn_playbook',
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
			)
		);

		foreach ( $items as $item_id ) {
			if ( get_post_meta( $item_id, 'lgsdn_primary_service_id', true ) ) {
				continue;
			}

			$services = wp_get_object_terms( $item_id, 'lgsdn_service', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $services ) && ! empty( $services ) ) {
				update_post_meta( $item_id, 'lgsdn_primary_service_id', absint( $services[0] ) );
			}
		}
	}

	private static function seed_homepage_links(): void {
		$homepage_id = absint( get_option( 'page_on_front' ) );
		if ( ! $homepage_id ) {
			return;
		}

		$paths = array( 'join', 'playbook', 'join' );
		foreach ( $paths as $offset => $path ) {
			$key = 'lgsdn_home_feature_' . ( $offset + 1 ) . '_page_id';
			if ( metadata_exists( 'post', $homepage_id, $key ) ) {
				continue;
			}

			$page = get_page_by_path( $path );
			if ( $page instanceof WP_Post ) {
				update_post_meta( $homepage_id, $key, $page->ID );
			}
		}
	}

	private static function seed_homepage_preview(): void {
		$homepage_id = absint( get_option( 'page_on_front' ) );
		if ( ! $homepage_id ) {
			return;
		}

		$current_content = (string) get_post_field( 'post_content', $homepage_id );
		if ( '' !== trim( $current_content ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID' => $homepage_id,
				'post_content' => '<!-- wp:lgsdn/homepage {"align":"full","lock":{"move":true,"remove":true}} /-->',
			)
		);
	}
}
