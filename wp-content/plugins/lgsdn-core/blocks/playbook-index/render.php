<?php
/**
 * Structured Playbook index.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();
$page_title = $post_id ? get_the_title( $post_id ) : 'Playbook';
$page_title = 'Playbook' === $page_title ? 'LGSDN Playbook' : $page_title;
$image_base = get_theme_file_uri( 'assets/images' );

$filter_taxonomies = array(
	'lgsdn_service' => 'Service area',
	'lgsdn_practice' => 'Practice',
	'lgsdn_challenge' => 'Challenge',
);
$filter_sentence_labels = array(
	'lgsdn_service' => 'service areas',
	'lgsdn_practice' => 'practices',
	'lgsdn_challenge' => 'challenges',
);
$active_filters = array();

foreach ( $filter_taxonomies as $taxonomy => $label ) {
	$query_key = str_replace( 'lgsdn_', '', $taxonomy );
	$submitted_values = isset( $_GET[ $query_key ] ) ? wp_unslash( $_GET[ $query_key ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$submitted_values = is_array( $submitted_values ) ? $submitted_values : array( $submitted_values );
	$submitted_value = $submitted_values[0] ?? '';
	$value           = sanitize_title( $submitted_value );

	if ( $value && term_exists( $value, $taxonomy ) ) {
		$active_filters[ $taxonomy ] = array( $value );
	}
}

$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'recent'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sort_options = array(
	'recent' => 'Most recently added',
	'oldest' => 'Oldest first',
	'title' => 'Title A–Z',
);

if ( ! isset( $sort_options[ $sort ] ) ) {
	$sort = 'recent';
}

$query_args = array(
	'post_type' => 'lgsdn_playbook',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'orderby' => 'title' === $sort ? 'title' : 'date',
	'order' => 'oldest' === $sort ? 'ASC' : ( 'title' === $sort ? 'ASC' : 'DESC' ),
);

if ( $active_filters ) {
	$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'relation' => 'AND',
	);

	foreach ( $active_filters as $taxonomy => $slugs ) {
		$query_args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'field' => 'slug',
			'terms' => $slugs,
			'operator' => 'IN',
		);
	}
}

$items = new WP_Query(
	$query_args
);

$result_count = (int) $items->found_posts;
$result_count_label = sprintf(
	_n( '%d case study', '%d case studies', $result_count, 'lgsdn' ),
	$result_count
);
$active_filter_labels = array();

foreach ( $active_filters as $taxonomy => $slugs ) {
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term instanceof WP_Term ) {
			$active_filter_labels[] = $filter_taxonomies[ $taxonomy ] . ': ' . $term->name;
		}
	}
}

$filter_status = 'Showing ' . $result_count_label . '.';
if ( $active_filter_labels ) {
	$filter_status .= ' Active filters: ' . implode( ', ', $active_filter_labels ) . '.';
} else {
	$filter_status .= ' No filters applied.';
}

$service_terms = LGSDN_Service_Styles::homepage_terms();
$primary_service_for_item = static function ( int $item_id ): ?WP_Term {
	$services = get_the_terms( $item_id, 'lgsdn_service' );
	$services = $services && ! is_wp_error( $services ) ? array_values( $services ) : array();
	$primary_service_id = absint( get_post_meta( $item_id, 'lgsdn_primary_service_id', true ) );

	foreach ( $services as $service ) {
		if ( $service->term_id === $primary_service_id ) {
			return $service;
		}
	}

	return $services[0] ?? null;
};
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-playbook-index alignfull' ) ); ?>>
	<section class="lgsdn-playbook-hero lgsdn-playbook-shell" aria-labelledby="playbook-title">
		<div class="lgsdn-playbook-hero__copy">
			<h1 id="playbook-title"><?php echo esc_html( $page_title ); ?></h1>
			<p>The playbook is co-produced by service design practitioners working in UK local governments, who share real examples of design practice in context, under real organisational conditions.</p>
		</div>
		<figure class="lgsdn-playbook-hero__media">
			<img src="<?php echo esc_url( $image_base . '/home-feature-image.png' ); ?>" alt="Service design practitioners working together around a table" width="597" height="449">
		</figure>
	</section>

	<section class="lgsdn-playbook-listing lgsdn-playbook-shell" aria-labelledby="case-studies-title">
		<section class="lgsdn-playbook-services" aria-labelledby="service-areas-title">
			<header class="lgsdn-playbook-section-header">
				<h2 id="service-areas-title">Explore by service area</h2>
				<p>Find out how service design works with these services in local government.</p>
			</header>
			<?php if ( $service_terms ) : ?>
				<div class="lgsdn-playbook-service-row" aria-label="Explore by service area">
					<p id="service-areas-hint" class="screen-reader-text">Use Tab to move through the cards. When this area is focused, use the left and right arrow keys to scroll horizontally.</p>
					<div class="lgsdn-playbook-service-scroller" data-service-scroller tabindex="0" role="region" aria-labelledby="service-areas-title" aria-describedby="service-areas-hint">
						<?php foreach ( $service_terms as $service_term ) : ?>
							<?php
							$service_style = LGSDN_Service_Styles::for_term( $service_term );
							$service_url = get_term_link( $service_term );
							if ( is_wp_error( $service_url ) ) {
								$service_url = get_permalink( $post_id );
							}
							?>
							<a class="lgsdn-playbook-service-card lgsdn-playbook-service-card--<?php echo esc_attr( $service_style['colour'] ); ?>" style="--lgsdn-service-card-fg:<?php echo esc_attr( $service_style['foreground'] ); ?>" href="<?php echo esc_url( $service_url ); ?>">
								<span class="lgsdn-playbook-service-card__label">Service</span>
								<img class="lgsdn-playbook-service-card__icon lgsdn-playbook-service-card__icon--<?php echo esc_attr( $service_style['icon'] ); ?>" src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="">
								<h3 class="lgsdn-playbook-service-card__title"><?php echo esc_html( $service_term->name ); ?></h3>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<section class="lgsdn-playbook-case-studies" id="case-study-panel" role="tabpanel" data-playbook-panel="case-studies" aria-labelledby="case-studies-title">
			<header class="lgsdn-playbook-section-header">
				<h2 id="case-studies-title" tabindex="-1" data-playbook-heading="case-studies">Read the case studies</h2>
				<p>Explore real examples of service design practice in local government.</p>
			</header>

		<div class="lgsdn-playbook-browse">
			<form class="lgsdn-playbook-filters" method="get" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>" data-playbook-filter-form aria-describedby="playbook-filter-hint">
				<p id="playbook-filter-hint" class="screen-reader-text">Choose one option from each filter. Selections across categories must all match.</p>

				<?php foreach ( $filter_taxonomies as $taxonomy => $label ) : ?>
					<?php
					$query_key = str_replace( 'lgsdn_', '', $taxonomy );
					$terms = get_terms(
						array(
							'taxonomy' => $taxonomy,
							'hide_empty' => true,
							'orderby' => 'name',
							'order' => 'ASC',
						)
					);
					$selected_value = $active_filters[ $taxonomy ][0] ?? '';
					$facet_id       = 'lgsdn-filter-' . $query_key;
					?>
					<div class="lgsdn-playbook-facet<?php echo $selected_value ? ' has-selection' : ''; ?>" data-filter-facet="<?php echo esc_attr( $query_key ); ?>">
						<label class="screen-reader-text" for="<?php echo esc_attr( $facet_id ); ?>"><?php echo esc_html( 'Filter case studies by ' . strtolower( $label ) ); ?></label>
						<select id="<?php echo esc_attr( $facet_id ); ?>" name="<?php echo esc_attr( $query_key ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" data-filter-select>
							<option value=""><?php echo esc_html( $label ); ?></option>
							<?php if ( ! is_wp_error( $terms ) ) : ?>
								<?php foreach ( $terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $selected_value ); ?>><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<button class="lgsdn-playbook-facet__clear" type="button" data-clear-facet <?php echo $selected_value ? '' : 'hidden'; ?> aria-label="<?php echo esc_attr( 'Remove ' . strtolower( $label ) . ' filter' ); ?>">×</button>
					</div>
				<?php endforeach; ?>

				<button class="lgsdn-playbook-filter-button" type="submit">Apply filters</button>

				<label class="lgsdn-playbook-search" hidden>
					<span class="screen-reader-text">Search case studies (not yet available)</span>
					<input type="search" placeholder="Search" aria-disabled="true" readonly>
					<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
						<circle cx="10.75" cy="10.75" r="5.75"></circle>
						<path d="m15 15 4 4"></path>
					</svg>
				</label>

				<label class="lgsdn-playbook-sort" hidden>
					<span>Sort by</span>
					<span class="lgsdn-playbook-select">
						<select name="sort">
							<?php foreach ( $sort_options as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sort, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</span>
				</label>
			</form>

			<div class="lgsdn-playbook-active-filters" aria-labelledby="active-filters-label" data-active-filters <?php echo $active_filters ? '' : 'hidden'; ?>>
				<span id="active-filters-label" class="screen-reader-text">Active filters</span>
				<div class="lgsdn-playbook-active-filters__sentence lgsdn-type-filter-sentence">
					<span class="lgsdn-playbook-filter-scaffolding">Showing </span>
					<?php $active_group_index = 0; ?>
					<?php foreach ( $active_filters as $taxonomy => $slugs ) : ?>
						<?php if ( $active_group_index > 0 ) : ?>
							<span class="lgsdn-playbook-filter-group-connector">and </span>
						<?php endif; ?>
						<span class="lgsdn-playbook-filter-group-label"><?php echo esc_html( $filter_sentence_labels[ $taxonomy ] ); ?></span><span class="lgsdn-playbook-filter-scaffolding"> matching </span>
						<?php foreach ( $slugs as $slug ) : ?>
			<?php
			$term = get_term_by( 'slug', $slug, $taxonomy );
			?>
			<?php if ( $term instanceof WP_Term ) : ?>
				<?php $filter_value_classes = array( 'lgsdn-playbook-filter-value' ); ?>
				<?php if ( 'lgsdn_service' === $taxonomy ) : ?>
					<?php $filter_value_classes[] = 'has-service-' . LGSDN_Service_Styles::for_term( $term )['colour']; ?>
				<?php endif; ?>
				<span class="lgsdn-playbook-filter-value-unit">
					<span class="<?php echo esc_attr( implode( ' ', $filter_value_classes ) ); ?>"><span class="lgsdn-playbook-filter-value__label"><?php echo esc_html( $term->name ); ?></span></span><?php if ( $active_group_index < count( $active_filters ) - 1 ) : ?><span class="lgsdn-playbook-filter-punctuation" aria-hidden="true">,</span><?php else : ?><span class="lgsdn-playbook-filter-period" aria-hidden="true">.</span><?php endif; ?>
				</span>
			<?php endif; ?>
						<?php endforeach; ?>
						<?php ++$active_group_index; ?>
					<?php endforeach; ?>
				</div>
				<a class="lgsdn-playbook-clear-filters" href="<?php echo esc_url( add_query_arg( array( 'sort' => $sort ), get_permalink( $post_id ) ) ); ?>" data-clear-filters hidden><span aria-hidden="true">×</span> Reset all filters</a>
			</div>

			<p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-filter-status><?php echo esc_html( $filter_status ); ?></p>
		</div>

		<div class="lgsdn-playbook-results" data-playbook-results aria-busy="false">
			<p class="lgsdn-playbook-results__count" hidden><?php echo esc_html( $result_count_label ); ?></p>
			<?php if ( $items->have_posts() ) : ?>
				<div id="case-study-results" class="lgsdn-playbook-grid">
				<?php
				while ( $items->have_posts() ) :
					$items->the_post();
					$item_id = get_the_ID();
					$primary_service = $primary_service_for_item( $item_id );
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
								<h4><?php the_title(); ?></h4>
								<?php $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 32 ); ?>
								<?php if ( $excerpt ) : ?>
									<p><?php echo esc_html( $excerpt ); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php else : ?>
				<p class="lgsdn-playbook-empty">No case studies match those filters. Remove one or more filters to broaden the results.</p>
			<?php endif; ?>
		</div>
		</section>
	</section>
</div>
<?php
wp_reset_postdata();
