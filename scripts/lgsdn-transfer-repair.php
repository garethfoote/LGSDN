<?php
/**
 * Plugin Name: LGSDN transfer repair
 * Description: One-time admin tool for repairing imported LGSDN starter content.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'lgsdn_transfer_repair_add_page' );

/** Register a temporary tool under Tools. */
function lgsdn_transfer_repair_add_page(): void {
	add_management_page(
		'LGSDN transfer repair',
		'LGSDN transfer repair',
		'manage_options',
		'lgsdn-transfer-repair',
		'lgsdn_transfer_repair_render_page'
	);
}

/** Render the upload form and run the repair only after a nonce-protected submission. */
function lgsdn_transfer_repair_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run this tool.' ) );
	}

	$result = null;
	if ( isset( $_POST['lgsdn_transfer_repair_submit'] ) ) {
		check_admin_referer( 'lgsdn_transfer_repair' );
		$result = lgsdn_transfer_repair_process_upload();
	}
	?>
	<div class="wrap">
		<h1>LGSDN transfer repair</h1>
		<p>Use this one-time tool after importing the LGSDN starter content. It merges imported pages into the site’s main page slugs, sets Home as the front page, and repairs contributor, taxonomy, linked-page, and image references.</p>
		<?php if ( is_array( $result ) ) : ?>
			<?php if ( ! empty( $result['error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $result['error'] ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success"><p><?php echo esc_html( $result['summary'] ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $result['warnings'] ) ) : ?>
				<div class="notice notice-warning"><p><strong>Items to review:</strong></p><ul>
					<?php foreach ( $result['warnings'] as $warning ) : ?>
						<li><?php echo esc_html( $warning ); ?></li>
					<?php endforeach; ?>
				</ul></div>
			<?php endif; ?>
		<?php endif; ?>
		<p>Select <code>migration-manifest.json</code> from the transfer package, then run the repair. This can be safely repeated if the report lists something to fix.</p>
		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'lgsdn_transfer_repair' ); ?>
			<input type="file" name="lgsdn_migration_manifest" accept="application/json,.json" required>
			<?php submit_button( 'Repair imported content', 'primary', 'lgsdn_transfer_repair_submit' ); ?>
		</form>
	</div>
	<?php
}

/** Validate the uploaded manifest, then restore references by stable slugs. */
function lgsdn_transfer_repair_process_upload(): array {
	if ( empty( $_FILES['lgsdn_migration_manifest']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['lgsdn_migration_manifest']['error'] ) {
		return array( 'error' => 'Choose the migration-manifest.json file from the transfer package.' );
	}
	if ( (int) $_FILES['lgsdn_migration_manifest']['size'] > 1024 * 1024 ) {
		return array( 'error' => 'The manifest is larger than the allowed 1 MB.' );
	}

	$manifest = json_decode( (string) file_get_contents( $_FILES['lgsdn_migration_manifest']['tmp_name'] ), true );
	if ( ! is_array( $manifest ) || empty( $manifest['terms'] ) || empty( $manifest['posts'] ) || empty( $manifest['attachments'] ) ) {
		return array( 'error' => 'This is not a valid LGSDN migration manifest.' );
	}

	$warnings      = array();
	$term_count    = 0;
	$post_count    = 0;
	$page_count    = 0;
	$media_ids     = array();
	$allowed_taxonomies = array( 'lgsdn_service', 'lgsdn_practice' );

	foreach ( $manifest['terms'] as $term_data ) {
		$taxonomy = $term_data['taxonomy'] ?? '';
		$slug     = sanitize_title( $term_data['slug'] ?? '' );
		if ( ! in_array( $taxonomy, $allowed_taxonomies, true ) || '' === $slug || empty( $term_data['meta'] ) || ! is_array( $term_data['meta'] ) ) {
			$warnings[] = 'Skipped an invalid taxonomy entry in the manifest.';
			continue;
		}
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			$warnings[] = 'Missing term: ' . $taxonomy . '/' . $slug;
			continue;
		}
		foreach ( $term_data['meta'] as $key => $value ) {
			if ( 0 === strpos( (string) $key, 'lgsdn_service_' ) || 0 === strpos( (string) $key, 'lgsdn_practice_' ) ) {
				update_term_meta( $term->term_id, sanitize_key( $key ), $value );
			}
		}
		++$term_count;
	}

	$page_ids = array();
	foreach ( $manifest['posts'] as $slug => $post_data ) {
		if ( 'page' !== ( $post_data['post_type'] ?? '' ) ) {
			continue;
		}
		$page_slug = sanitize_title( $slug );
		$imported  = lgsdn_transfer_repair_find_source_post( $post_data, 'page' );
		$page      = get_page_by_path( $page_slug, OBJECT, 'page' );
		if ( ! $imported instanceof WP_Post ) {
			$warnings[] = 'Missing imported page content: ' . $page_slug;
			continue;
		}
		if ( ! $page instanceof WP_Post ) {
			$page = $imported;
		} elseif ( $page->ID !== $imported->ID ) {
			$page = lgsdn_transfer_repair_merge_page( $page, $imported, $page_slug );
		}
		$page_ids[ $page_slug ] = $page->ID;
		++$page_count;
	}

	foreach ( $manifest['attachments'] as $source_id => $attachment_data ) {
		$slug = sanitize_title( $attachment_data['slug'] ?? '' );
		if ( '' === $slug ) {
			continue;
		}
		$matches = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'name'           => $slug,
				'posts_per_page' => 10,
				'fields'         => 'ids',
			)
		);
		if ( $matches ) {
			$target_id = (int) $matches[0];
			$expected  = ltrim( (string) ( $attachment_data['file'] ?? '' ), '/' );
			foreach ( $matches as $candidate_id ) {
				$actual = (string) get_post_meta( $candidate_id, '_wp_attached_file', true );
				if ( '' !== $expected && $expected === $actual ) {
					$target_id = (int) $candidate_id;
					break;
				}
			}
			$media_ids[ (int) $source_id ] = $target_id;
		} else {
			$warnings[] = 'Missing imported media: ' . $slug;
		}
	}

	$repair_blocks = static function ( array &$blocks ) use ( &$repair_blocks, $media_ids ): void {
		foreach ( $blocks as &$block ) {
			if ( 'core/image' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['id'] ) ) {
				$source_id = absint( $block['attrs']['id'] );
				if ( isset( $media_ids[ $source_id ] ) ) {
					$block['attrs']['id'] = $media_ids[ $source_id ];
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$repair_blocks( $block['innerBlocks'] );
			}
		}
		unset( $block );
	};

	foreach ( $manifest['posts'] as $slug => $post_data ) {
		$post_slug = sanitize_title( $slug );
		$post_type = sanitize_key( $post_data['post_type'] ?? '' );
		if ( ! in_array( $post_type, array( 'page', 'lgsdn_playbook', 'lgsdn_person', 'lgsdn_event' ), true ) ) {
			$warnings[] = 'Skipped unsupported content type for ' . $post_slug;
			continue;
		}
		$post = 'page' === $post_type && isset( $page_ids[ $post_slug ] )
			? get_post( $page_ids[ $post_slug ] )
			: get_page_by_path( $post_slug, OBJECT, $post_type );
		if ( ! $post instanceof WP_Post ) {
			$warnings[] = 'Missing imported item: ' . $post_type . '/' . $post_slug;
			continue;
		}

		if ( 'lgsdn_playbook' === $post_type ) {
			$contributor_slug = sanitize_title( $post_data['contributor_slug'] ?? '' );
			$contributor      = '' !== $contributor_slug ? get_page_by_path( $contributor_slug, OBJECT, 'lgsdn_person' ) : null;
			if ( $contributor instanceof WP_Post ) {
				update_post_meta( $post->ID, 'lgsdn_contributor_id', $contributor->ID );
			} else {
				$warnings[] = 'Missing contributor for ' . $post_slug . ': ' . $contributor_slug;
			}

			foreach ( array( 'service' => 'lgsdn_service', 'practice' => 'lgsdn_practice' ) as $kind => $taxonomy ) {
				$term_slug = sanitize_title( $post_data[ 'primary_' . $kind . '_slug' ] ?? '' );
				$term      = '' !== $term_slug ? get_term_by( 'slug', $term_slug, $taxonomy ) : false;
				if ( $term instanceof WP_Term ) {
					update_post_meta( $post->ID, 'lgsdn_primary_' . $kind . '_id', $term->term_id );
				} else {
					$warnings[] = 'Missing primary ' . $kind . ' for ' . $post_slug . ': ' . $term_slug;
				}
			}
		}
		if ( 'page' === $post_type && ! empty( $post_data['page_template'] ) ) {
			update_post_meta( $post->ID, '_wp_page_template', sanitize_file_name( $post_data['page_template'] ) );
		}

		foreach ( $post_data['linked_page_slugs'] ?? array() as $meta_key => $linked_slug ) {
			$linked_slug = sanitize_title( $linked_slug );
			$linked_page = '' !== $linked_slug ? ( $page_ids[ $linked_slug ] ?? 0 ) : 0;
			if ( $linked_page ) {
				update_post_meta( $post->ID, sanitize_key( $meta_key ), absint( $linked_page ) );
			} elseif ( '' !== $linked_slug ) {
				$warnings[] = 'Missing linked page for ' . $post_slug . ': ' . $linked_slug;
			}
		}

		$blocks  = parse_blocks( $post->post_content );
		$repair_blocks( $blocks );
		$content = serialize_blocks( $blocks );
		foreach ( $media_ids as $source_id => $target_id ) {
			$content = str_replace( 'wp-image-' . $source_id, 'wp-image-' . $target_id, $content );
		}
		wp_update_post( array( 'ID' => $post->ID, 'post_content' => $content ) );

		$featured_source_id = absint( $post_data['featured_image_id'] ?? 0 );
		if ( $featured_source_id && isset( $media_ids[ $featured_source_id ] ) ) {
			set_post_thumbnail( $post->ID, $media_ids[ $featured_source_id ] );
		} elseif ( $featured_source_id ) {
			$warnings[] = 'Missing featured image for ' . $post_slug;
		}
		++$post_count;
	}

	if ( isset( $page_ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', absint( $page_ids['home'] ) );
		update_option( 'page_for_posts', 0 );
	}

	return array(
		'summary'  => sprintf( 'Repair complete: restored %d service/practice terms, merged %d pages, repaired %d content items, and mapped %d of %d images.', $term_count, $page_count, $post_count, count( $media_ids ), count( $manifest['attachments'] ) ),
		'warnings' => $warnings,
	);
}

/** Find the record imported from the source by its exact title and publication date. */
function lgsdn_transfer_repair_find_source_post( array $post_data, string $post_type ): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	foreach ( $posts as $post ) {
		if ( $post->post_title === ( $post_data['post_title'] ?? '' ) && $post->post_date === ( $post_data['post_date'] ?? '' ) ) {
			return $post;
		}
	}

	return null;
}

/** Copy an imported duplicate page's content and metadata into the canonical page slug. */
function lgsdn_transfer_repair_merge_page( WP_Post $canonical, WP_Post $imported, string $slug ): WP_Post {
	wp_update_post(
		array(
			'ID'             => $canonical->ID,
			'post_title'     => $imported->post_title,
			'post_name'      => $slug,
			'post_content'   => $imported->post_content,
			'post_excerpt'   => $imported->post_excerpt,
			'post_status'    => $imported->post_status,
			'post_date'      => $imported->post_date,
			'post_date_gmt'  => $imported->post_date_gmt,
			'menu_order'     => $imported->menu_order,
			'comment_status' => $imported->comment_status,
			'ping_status'    => $imported->ping_status,
		)
	);

	foreach ( get_post_meta( $canonical->ID ) as $meta_key => $values ) {
		if ( in_array( $meta_key, array( '_edit_lock', '_edit_last' ), true ) ) {
			continue;
		}
		delete_post_meta( $canonical->ID, $meta_key );
	}
	foreach ( get_post_meta( $imported->ID ) as $meta_key => $values ) {
		if ( in_array( $meta_key, array( '_edit_lock', '_edit_last' ), true ) ) {
			continue;
		}
		foreach ( $values as $value ) {
			add_post_meta( $canonical->ID, $meta_key, maybe_unserialize( $value ) );
		}
	}

	wp_delete_post( $imported->ID, true );
	return get_post( $canonical->ID );
}
