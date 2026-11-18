<?php
/** Editorial rename only: existing storage identifiers and permalinks stay stable. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class LGSDN_Library_Naming {
	public static function hooks(): void {
		add_action( 'admin_init', array( __CLASS__, 'migrate' ), 30 );
	}
	private static function copy( string $text ): string {
		// Replace prose, never HTML attributes, URLs or serialized block attributes.
		$parts = preg_split( '/(<[^>]*>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $parts as $index => $part ) {
			if ( str_starts_with( $part, '<' ) ) { continue; }
			$part = str_ireplace( array( 'Design in Local Government Playbook', 'Local Government Design Playbook' ), 'Library of Practice', $part );
			$part = preg_replace( '/\bplaybook coordinator\b/i', 'Library coordinator', $part );
			$parts[$index] = preg_replace( '/\bplaybook\b/i', 'Library of Practice', $part );
		}
		return implode( '', $parts );
	}
	public static function migrate(): void {
		if ( get_option( 'lgsdn_library_naming_v1' ) ) { return; }
		$pages = get_posts( array( 'post_type' => array( 'page', 'lgsdn_person', 'lgsdn_playbook', 'lgsdn_event' ), 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1 ) );
		foreach ( $pages as $page ) {
			if ( 'lgsdn_event' === $page->post_type ) {
				$start = get_post_meta( $page->ID, 'lgsdn_start_at', true );
				// Preserve historical event names and descriptions.
				if ( ! is_string( $start ) || '' === $start || $start < current_time( 'Y-m-d\TH:i' ) ) { continue; }
			}
			$content = self::copy( $page->post_content );
			$excerpt = self::copy( $page->post_excerpt );
			$title = $page->post_title;
			if ( 'lgsdn_event' === $page->post_type ) { $title = self::copy( $title ); }
			if ( 'page' === $page->post_type && 'playbook' === $page->post_name && in_array( $title, array( 'Playbook', 'Library' ), true ) ) { $title = 'Library of Practice'; }
			if ( $content !== $page->post_content || $excerpt !== $page->post_excerpt || $title !== $page->post_title ) {
				wp_save_post_revision( $page->ID );
				$result = wp_update_post( wp_slash( array( 'ID' => $page->ID, 'post_content' => $content, 'post_excerpt' => $excerpt, 'post_title' => $title ) ), true );
				if ( is_wp_error( $result ) ) { return; }
			}
			if ( 'page' === $page->post_type ) {
				foreach ( get_post_meta( $page->ID ) as $key => $values ) {
					if ( ! str_starts_with( $key, 'lgsdn_home_' ) || str_ends_with( $key, '_url' ) || str_ends_with( $key, '_page_id' ) ) { continue; }
					$value = get_post_meta( $page->ID, $key, true );
					if ( is_string( $value ) && preg_match( '/\bplaybook\b/i', $value ) ) {
						$new_value = str_ireplace( 'Browse the playbook', 'Browse the library', $value );
						update_post_meta( $page->ID, $key, wp_slash( self::copy( $new_value ) ) );
					}
				}
			}
		}
		update_option( 'lgsdn_library_naming_v1', true, false );
	}
}
