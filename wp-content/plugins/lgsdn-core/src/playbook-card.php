<?php
/** Shared Playbook listing card; renders the current loop item. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lgsdn_services_for_item( int $item_id ): array {
	$services = get_the_terms( $item_id, 'lgsdn_service' );
	$services = $services && ! is_wp_error( $services ) ? array_values( $services ) : array();
	$primary_service_id = absint( get_post_meta( $item_id, 'lgsdn_primary_service_id', true ) );

	foreach ( $services as $index => $service ) {
		if ( $service->term_id !== $primary_service_id ) {
			continue;
		}

		unset( $services[ $index ] );
		array_unshift( $services, $service );
		break;
	}

	return array_values( $services );
}

function lgsdn_primary_service_for_item( int $item_id ): ?WP_Term {
	$services = lgsdn_services_for_item( $item_id );

	return $services[0] ?? null;
}

/** Independent images; legacy featured images remain a card-only fallback. */
function lgsdn_playbook_image_id( int $item_id, string $slot ): int {
	$override = absint( get_post_meta( $item_id, 'lgsdn_' . $slot . '_image_id', true ) );
	if ( $override && 'attachment' === get_post_type( $override ) && wp_attachment_is_image( $override ) ) {
		return $override;
	}

	return 'card' === $slot ? absint( get_post_thumbnail_id( $item_id ) ) : 0;
}

/** Display the saved crop without runtime image manipulation. */
function lgsdn_playbook_image( int $item_id, string $slot, string $size, array $attributes = array() ): string {
	$image_id = lgsdn_playbook_image_id( $item_id, $slot );
	if ( ! $image_id ) {
		return '';
	}
	return (string) wp_get_attachment_image( $image_id, $size, false, $attributes );
}

/** Wrap a card crop in decorative window chrome when requested for this item. */
function lgsdn_playbook_card_image_markup( int $item_id, string $size, array $attributes = array() ): string {
	$image = lgsdn_playbook_image( $item_id, 'card', $size, $attributes );
	if ( ! $image || ! get_post_meta( $item_id, 'lgsdn_card_image_window', true ) ) {
		return $image;
	}

	return '<span class="lgsdn-window-frame lgsdn-window-frame--card">' . $image . '</span>';
}

/** Return only the first sentence of a case study excerpt for compact cards. */
function lgsdn_playbook_card_excerpt( int $item_id ): string {
	$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $item_id ) ) );
	if ( ! $excerpt ) {
		return '';
	}

	$sentences = preg_split( '/(?<=[.!?])\s+/u', $excerpt, 2 );
	return trim( $sentences[0] ?? $excerpt );
}

function lgsdn_render_playbook_card( int $heading_level = 4, bool $compact = false ): void {
	$heading_tag = 'h' . max( 2, min( 6, $heading_level ) );
	$image_base = get_theme_file_uri( 'assets/images' );
	$item_id = get_the_ID();
	$has_card_image = (bool) lgsdn_playbook_image_id( $item_id, 'card' );
	$has_window_card_image = $has_card_image && (bool) get_post_meta( $item_id, 'lgsdn_card_image_window', true );
	$services = lgsdn_services_for_item( $item_id );
	$councils = get_the_terms( $item_id, 'lgsdn_council' );
	$council_label = $councils && ! is_wp_error( $councils ) ? implode( ', ', wp_list_pluck( $councils, 'name' ) ) : '';
	$case_study_tags = array();
	foreach ( $services as $service ) {
		$case_study_tags[] = array(
			'name' => $service->name,
			'taxonomy' => 'lgsdn_service',
			'style' => LGSDN_Service_Styles::for_term( $service ),
		);
	}
	foreach ( array( 'lgsdn_practice', 'lgsdn_purpose', 'lgsdn_challenge' ) as $taxonomy ) {
		if ( $compact ) {
			break;
		}
		$terms = get_the_terms( $item_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->name, array_column( $case_study_tags, 'name' ), true ) ) {
				$case_study_tags[] = array(
					'name' => $term->name,
					'taxonomy' => $taxonomy,
					'style' => null,
				);
			}
		}
	}
	?>
	<article class="lgsdn-playbook-card<?php echo $compact ? ' lgsdn-playbook-card--compact' : ''; ?>">
		<a class="lgsdn-playbook-card__link" href="<?php the_permalink(); ?>">
			<div class="lgsdn-playbook-card__media<?php echo $has_window_card_image ? ' lgsdn-playbook-card__media--window' : ''; ?>">
				<?php if ( $has_card_image ) : ?>
					<?php echo lgsdn_playbook_card_image_markup( $item_id, 'large', array( 'class' => 'lgsdn-playbook-card__image', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img class="lgsdn-playbook-card__image" src="<?php echo esc_url( $image_base . '/home-feature-image.png' ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<?php if ( $council_label ) : ?>
							<span class="lgsdn-playbook-card__council"><?php echo esc_html( $council_label ); ?></span>
						<?php endif; ?>
					</div>
			<div class="lgsdn-playbook-card__body">
				<?php if ( $case_study_tags ) : ?>
					<div class="lgsdn-playbook-card__tags" aria-label="Case study classifications">
						<?php foreach ( $case_study_tags as $label ) : ?>
							<?php $is_service = 'lgsdn_service' === $label['taxonomy']; ?>
							<span class="lgsdn-playbook-tag <?php echo $is_service ? 'lgsdn-playbook-tag--service' : 'lgsdn-playbook-tag--secondary'; ?>" <?php echo $is_service ? 'style="--lgsdn-service-tag-bg:' . esc_attr( $label['style']['background'] ) . ';--lgsdn-service-tag-fg:' . esc_attr( $label['style']['foreground'] ) . ';"' : ''; ?>><?php echo esc_html( $label['name'] ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<<?php echo $heading_tag; ?> class="lgsdn-playbook-card__title"><?php the_title(); ?></<?php echo $heading_tag; ?>>
				<?php $excerpt = $compact ? '' : lgsdn_playbook_card_excerpt( $item_id ); ?>
				<?php if ( $excerpt ) : ?>
					<p><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	</article>
<?php
}
