<?php
/** Shared Playbook listing card; renders the current loop item. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lgsdn_primary_service_for_item ( int $item_id ): ?WP_Term {
	$services = get_the_terms( $item_id, 'lgsdn_service' );
	$services = $services && ! is_wp_error( $services ) ? array_values( $services ) : array();
	$primary_service_id = absint( get_post_meta( $item_id, 'lgsdn_primary_service_id', true ) );

	foreach ( $services as $service ) {
		if ( $service->term_id === $primary_service_id ) {
			return $service;
		}
	}

	return $services[0] ?? null;
}

function lgsdn_render_playbook_card( int $heading_level = 4 ): void {
	$heading_tag = 'h' . max( 2, min( 6, $heading_level ) );
	$image_base = get_theme_file_uri( 'assets/images' );
	$item_id = get_the_ID();
	$primary_service = lgsdn_primary_service_for_item( $item_id );
	$councils = get_the_terms( $item_id, 'lgsdn_council' );
	$council_label = $councils && ! is_wp_error( $councils ) ? implode( ', ', wp_list_pluck( $councils, 'name' ) ) : '';
	$service_style = $primary_service
		? LGSDN_Service_Styles::for_term( $primary_service )
		: array(
			'background' => '#E4E7EE',
			'foreground' => '#27272D',
		);
	$case_study_tags = array();
	if ( $primary_service ) {
		$case_study_tags[] = array(
			'name' => $primary_service->name,
			'taxonomy' => 'lgsdn_service',
			'primary' => true,
		);
	}
	foreach ( array( 'lgsdn_service', 'lgsdn_practice', 'lgsdn_purpose', 'lgsdn_challenge' ) as $taxonomy ) {
		$terms = get_the_terms( $item_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			if ( $primary_service && 'lgsdn_service' === $taxonomy && $term->term_id === $primary_service->term_id ) {
				continue;
			}
			if ( ! in_array( $term->name, array_column( $case_study_tags, 'name' ), true ) ) {
				$case_study_tags[] = array(
					'name' => $term->name,
					'taxonomy' => $taxonomy,
					'primary' => false,
				);
			}
		}
	}
	?>
	<article class="lgsdn-playbook-card">
		<a class="lgsdn-playbook-card__link" href="<?php the_permalink(); ?>">
			<div class="lgsdn-playbook-card__media">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php
					the_post_thumbnail(
						'large',
						array(
							'class' => 'lgsdn-playbook-card__image',
							'loading' => 'lazy',
						)
					);
					?>
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
							<span class="lgsdn-playbook-tag <?php echo $label['primary'] ? 'lgsdn-playbook-tag--service' : 'lgsdn-playbook-tag--secondary'; ?>" <?php echo $label['primary'] ? 'style="--lgsdn-service-tag-bg:' . esc_attr( $service_style['background'] ) . ';--lgsdn-service-tag-fg:' . esc_attr( $service_style['foreground'] ) . ';"' : ''; ?>><?php echo esc_html( $label['name'] ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<<?php echo $heading_tag; ?> class="lgsdn-playbook-card__title"><?php the_title(); ?></<?php echo $heading_tag; ?>>
				<?php $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 32 ); ?>
				<?php if ( $excerpt ) : ?>
					<p><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	</article>
<?php
}
