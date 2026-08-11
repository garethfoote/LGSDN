<?php
/**
 * Publishing safeguards for Playbook items.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Playbook_Validation {
	private const REQUIRED_TAXONOMIES = array(
		'lgsdn_practice' => 'Practice',
		'lgsdn_service' => 'Service',
		'lgsdn_challenge' => 'Challenge',
	);

	public static function hooks(): void {
		add_filter( 'rest_pre_insert_lgsdn_playbook', array( __CLASS__, 'validate_rest_publish' ), 10, 3 );
	}

	/**
	 * Prevent publishing through the block editor until the structured fields
	 * needed by the public card are complete.
	 */
	public static function validate_rest_publish(
		stdClass $prepared_post,
		WP_REST_Request $request,
		bool $creating
	): stdClass|WP_Error {
		$status = (string) ( $request->get_param( 'status' ) ?? $prepared_post->post_status ?? '' );
		if ( 'publish' !== $status ) {
			return $prepared_post;
		}

		$post_id = $creating ? 0 : absint( $prepared_post->ID ?? 0 );
		$errors = array();
		$term_ids = array();

		foreach ( self::REQUIRED_TAXONOMIES as $taxonomy => $label ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			$rest_base = $taxonomy_object && $taxonomy_object->rest_base
				? $taxonomy_object->rest_base
				: $taxonomy;
			$submitted_terms = $request->get_param( $rest_base );

			if ( is_array( $submitted_terms ) ) {
				$term_ids[ $taxonomy ] = array_values( array_filter( array_map( 'absint', $submitted_terms ) ) );
			} elseif ( $post_id ) {
				$term_ids[ $taxonomy ] = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
				if ( is_wp_error( $term_ids[ $taxonomy ] ) ) {
					$term_ids[ $taxonomy ] = array();
				}
			} else {
				$term_ids[ $taxonomy ] = array();
			}

			if ( empty( $term_ids[ $taxonomy ] ) ) {
				$errors[] = $label;
			}
		}

		$meta = $request->get_param( 'meta' );
		$meta = is_array( $meta ) ? $meta : array();
		$contributor_id = isset( $meta['lgsdn_contributor_id'] )
			? absint( $meta['lgsdn_contributor_id'] )
			: ( $post_id ? absint( get_post_meta( $post_id, 'lgsdn_contributor_id', true ) ) : 0 );
		$primary_practice_id = isset( $meta['lgsdn_primary_practice_id'] )
			? absint( $meta['lgsdn_primary_practice_id'] )
			: ( $post_id ? absint( get_post_meta( $post_id, 'lgsdn_primary_practice_id', true ) ) : 0 );

		if ( ! $contributor_id || 'lgsdn_person' !== get_post_type( $contributor_id ) ) {
			$errors[] = 'Case study author';
		}

		if (
			! $primary_practice_id ||
			empty( $term_ids['lgsdn_practice'] ) ||
			! in_array( $primary_practice_id, $term_ids['lgsdn_practice'], true )
		) {
			$errors[] = 'Primary practice (which must also be assigned under Practices)';
		}

		if ( $errors ) {
			return new WP_Error(
				'lgsdn_playbook_incomplete',
				sprintf(
					'Complete these Playbook fields before publishing: %s.',
					implode( ', ', $errors )
				),
				array( 'status' => 400 )
			);
		}

		return $prepared_post;
	}
}
