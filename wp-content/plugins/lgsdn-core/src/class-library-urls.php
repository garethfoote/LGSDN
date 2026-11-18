<?php
/** Move public URLs without renaming the stored post type or metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class LGSDN_Library_URLs {
	public static function hooks(): void {
		add_action( 'admin_init', array( __CLASS__, 'migrate' ), 40 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect' ), 1 );
	}

	/** Rewrite internal links while leaving other sites' /playbook/ URLs alone. */
	public static function links( string $text ): string {
		$hosts = array_map( 'strtolower', array_filter( array_unique( array(
			wp_parse_url( home_url(), PHP_URL_HOST ),
			wp_parse_url( site_url(), PHP_URL_HOST ),
			wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST ),
			wp_parse_url( (string) get_option( 'siteurl' ), PHP_URL_HOST ),
		) ) ) );
		return preg_replace_callback(
			'~[a-z][a-z0-9+.-]*:[^\s<>"\']+|//[^\s<>"\']+|(?<![\w/])/(?!/)[^\s<>"\']*~i',
			static function ( array $match ) use ( $hosts ): string {
				$url = $match[0];
				$relative = str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' );
				$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
				if ( '' !== $scheme && ! in_array( $scheme, array( 'http', 'https' ), true ) ) { return $url; }
				if ( ! $relative && ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ), $hosts, true ) ) { return $url; }
				// Limit replacements to the URL path; preserve query values and fragments.
				$path = wp_parse_url( $url, PHP_URL_PATH );
				$old_path = trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) . 'playbook';
				if ( ! is_string( $path ) || ( $path !== $old_path && ! str_starts_with( $path, $old_path . '/' ) ) ) { return $url; }
				$new_path = substr_replace( $path, 'library', strlen( $old_path ) - strlen( 'playbook' ), strlen( 'playbook' ) );
				$origin_length = $relative ? 0 : strpos( $url, '/', str_starts_with( $url, '//' ) ? 2 : strpos( $url, '://' ) + 3 );
				return substr_replace( $url, $new_path, $origin_length, strlen( $path ) );
			},
			$text
		);
	}

	public static function migrate(): void {
		if ( get_option( 'lgsdn_library_urls_v1' ) ) { return; }
		$old = get_page_by_path( 'playbook', OBJECT, 'page' );
		$new = get_page_by_path( 'library', OBJECT, 'page' );
		if ( $old && $new && $old->ID !== $new->ID ) {
			add_action( 'admin_notices', static function (): void {
				echo '<div class="notice notice-error"><p>The Library URL migration is paused because both /playbook/ and /library/ pages exist. Resolve the conflicting page before retrying.</p></div>';
			} );
			return;
		}
		if ( ! $old && ! $new ) { return; }
		if ( $old ) {
			$result = wp_update_post( array( 'ID' => $old->ID, 'post_name' => 'library' ), true );
			if ( is_wp_error( $result ) || 'library' !== get_post_field( 'post_name', $old->ID ) ) { return; }
		}
		$posts = get_posts( array( 'post_type' => array( 'page', 'post', 'lgsdn_playbook', 'lgsdn_person', 'lgsdn_event', 'wp_navigation', 'nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part' ), 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1 ) );
		foreach ( $posts as $post ) {
			$content = self::links( $post->post_content );
			$excerpt = self::links( $post->post_excerpt );
			if ( $content !== $post->post_content || $excerpt !== $post->post_excerpt ) {
				wp_save_post_revision( $post->ID );
				$result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $content, 'post_excerpt' => $excerpt ) ), true );
				if ( is_wp_error( $result ) ) { return; }
			}
			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( ! str_starts_with( $key, 'lgsdn_' ) && '_menu_item_url' !== $key ) { continue; }
				$value = get_post_meta( $post->ID, $key, true );
				$rewrite = static function ( $item ) use ( &$rewrite ) {
					if ( is_string( $item ) ) { return self::links( $item ); }
					if ( is_array( $item ) ) { return array_map( $rewrite, $item ); }
					return $item;
				};
				$updated = $rewrite( $value );
				if ( $updated !== $value ) { update_post_meta( $post->ID, $key, wp_slash( $updated ) ); }
			}
		}
		flush_rewrite_rules( false );
		update_option( 'lgsdn_library_urls_v1', true, false );
	}

	public static function target( string $uri ): ?string {
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		$prefix = trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) . 'playbook';
		if ( ! is_string( $path ) || ( $path !== $prefix && ! str_starts_with( $path, $prefix . '/' ) ) ) { return null; }
		$suffix = substr( $path, strlen( $prefix ) );
		$target = home_url( '/library' . ( '' === $suffix ? '/' : $suffix ) );
		$query = wp_parse_url( $uri, PHP_URL_QUERY );
		return $target . ( is_string( $query ) && '' !== $query ? '?' . $query : '' );
	}
	public static function redirect(): void {
		if ( ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { return; }
		$target = self::target( (string) ( $_SERVER['REQUEST_URI'] ?? '' ) );
		if ( $target ) {
			wp_safe_redirect( $target, 301, 'LGSDN Library URL migration' );
			exit;
		}
	}
}
