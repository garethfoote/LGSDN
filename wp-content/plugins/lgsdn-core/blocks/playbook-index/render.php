<?php
/**
 * Structured Playbook index.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();
$page_title = $post_id ? get_the_title( $post_id ) : 'Playbook';
$page_title = 'Playbook' === $page_title ? 'LGSDN Playbook' : $page_title;
$image_base = get_theme_file_uri( 'assets/images' );
$taxonomy_icon_urls = array(
	'lgsdn_service' => $image_base . '/taxonomy-service.svg',
	'lgsdn_challenge' => $image_base . '/taxonomy-challenge.svg',
);

$filter_taxonomies = array(
	'lgsdn_practice' => 'Practice',
	'lgsdn_service' => 'Service',
	'lgsdn_challenge' => 'Challenge',
);
$filter_sentence_labels = array(
	'lgsdn_practice' => 'practices',
	'lgsdn_service' => 'services',
	'lgsdn_challenge' => 'challenges',
);
$active_filters = array();

foreach ( $filter_taxonomies as $taxonomy => $label ) {
	$query_key = str_replace( 'lgsdn_', '', $taxonomy );
	$submitted_values = isset( $_GET[ $query_key ] ) ? wp_unslash( $_GET[ $query_key ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$submitted_values = is_array( $submitted_values ) ? $submitted_values : array( $submitted_values );
	$valid_values = array();

	foreach ( $submitted_values as $submitted_value ) {
		$value = sanitize_title( $submitted_value );
		if ( $value && term_exists( $value, $taxonomy ) ) {
			$valid_values[] = $value;
		}
	}

	if ( $valid_values ) {
		$active_filters[ $taxonomy ] = array_values( array_unique( $valid_values ) );
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
	'posts_per_page' => 12,
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

$render_contour = static function ( string $colour ): void {
	$path = LGSDN_Practice_Styles::contour_path( $colour );
	if ( ! $path ) {
		return;
	}

	$svg = file_get_contents( $path );
	if ( false === $svg ) {
		return;
	}

	$svg = preg_replace(
		'/#(?:58d5d2|ff7657|e4d85b)/i',
		'var(--lgsdn-practice-accent)',
		$svg
	);
	$svg = preg_replace(
		'/<svg\s/',
		'<svg class="lgsdn-playbook-card__motif" aria-hidden="true" focusable="false" ',
		$svg,
		1
	);

	// The source is a trusted theme asset and contains no user-authored markup.
	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

	<section class="lgsdn-playbook-contribute">
		<div class="lgsdn-playbook-shell lgsdn-playbook-contribute__inner">
			<div>
				<h2>Contribute an example</h2>
				<p>Share a real example of service design practice, including what happened, what you learned, and the organisational conditions around the work.</p>
			</div>
			<a class="lgsdn-playbook-button lgsdn-button--arrow" href="<?php echo esc_url( home_url( '/join/' ) ); ?>"><span class="lgsdn-button__label">Contribute an example</span></a>
		</div>
	</section>

	<section class="lgsdn-playbook-listing lgsdn-playbook-shell" aria-labelledby="case-studies-title">
		<header class="lgsdn-playbook-listing__header">
			<div class="lgsdn-playbook-listing__title">
				<h2 id="case-studies-title">Filter the case studies</h2>
				<span aria-hidden="true">or</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'lgsdn_playbook' ) ?: '#case-studies-title' ); ?>">Explore areas of practice</a>
			</div>
			<p>We capture insights from our innovation processes to share learnable knowledge — through studies, analyses, data visualisations, and publications.</p>
		</header>

		<div class="lgsdn-playbook-browse">
			<form class="lgsdn-playbook-filters" method="get" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>" data-playbook-filter-form aria-describedby="playbook-filter-hint">
				<p id="playbook-filter-hint" class="screen-reader-text">Select any that apply. Selections within a category match any selected value. Selections across categories must all match.</p>

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
					$selected_values = $active_filters[ $taxonomy ] ?? array();
					$selected_count = count( $selected_values );
					$facet_id = 'lgsdn-filter-' . $query_key;
					?>
					<details class="lgsdn-playbook-facet" data-filter-facet="<?php echo esc_attr( $query_key ); ?>">
						<summary>
							<span><?php echo esc_html( $label ); ?></span>
							<span class="lgsdn-playbook-facet__count screen-reader-text" data-facet-count>
								<?php if ( $selected_count ) : ?>
									<?php echo esc_html( sprintf( _n( '%d selected', '%d selected', $selected_count, 'lgsdn' ), $selected_count ) ); ?>
								<?php endif; ?>
							</span>
						</summary>
						<div class="lgsdn-playbook-facet__panel">
							<div class="lgsdn-playbook-facet__search" data-facet-search hidden>
								<label for="<?php echo esc_attr( $facet_id . '-search' ); ?>">Search <?php echo esc_html( strtolower( $label ) ); ?> options</label>
								<input id="<?php echo esc_attr( $facet_id . '-search' ); ?>" type="search" autocomplete="off" data-facet-search-input>
							</div>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( 'Filter case studies by ' . strtolower( $label ) ); ?></legend>
								<p class="lgsdn-playbook-facet__hint">Select all that apply</p>
								<div class="lgsdn-playbook-facet__options" data-facet-options>
							<?php if ( ! is_wp_error( $terms ) ) : ?>
								<?php foreach ( $terms as $term ) : ?>
											<?php $option_id = $facet_id . '-' . $term->term_id; ?>
											<label class="lgsdn-playbook-checkbox" data-filter-option="<?php echo esc_attr( strtolower( $term->name ) ); ?>">
												<input
													id="<?php echo esc_attr( $option_id ); ?>"
													type="checkbox"
													name="<?php echo esc_attr( $query_key ); ?>[]"
													value="<?php echo esc_attr( $term->slug ); ?>"
													<?php checked( in_array( $term->slug, $selected_values, true ) ); ?>
												>
												<span><?php echo esc_html( $term->name ); ?></span>
											</label>
								<?php endforeach; ?>
							<?php endif; ?>
								</div>
								<p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-facet-search-status></p>
							</fieldset>
						</div>
					</details>
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
				<div class="lgsdn-playbook-active-filters__sentence">
					<span class="lgsdn-playbook-filter-scaffolding">Show case studies with </span>
					<?php $active_group_index = 0; ?>
					<?php foreach ( $active_filters as $taxonomy => $slugs ) : ?>
						<?php if ( $active_group_index > 0 ) : ?>
							<span class="lgsdn-playbook-filter-group-connector">And </span>
						<?php endif; ?>
						<span class="lgsdn-playbook-filter-group-label"><?php echo esc_html( $filter_sentence_labels[ $taxonomy ] ); ?></span><span class="lgsdn-playbook-filter-scaffolding"> matching </span>
						<?php foreach ( $slugs as $term_index => $slug ) : ?>
							<?php
							$query_key = str_replace( 'lgsdn_', '', $taxonomy );
							$term = get_term_by( 'slug', $slug, $taxonomy );
							$remaining_args = array( 'sort' => $sort );
							$is_last_term = $term_index === count( $slugs ) - 1;
							$has_comma = $term_index < count( $slugs ) - 2;

							foreach ( $active_filters as $remaining_taxonomy => $remaining_slugs ) {
								$remaining_values = $remaining_taxonomy === $taxonomy
									? array_values( array_diff( $remaining_slugs, array( $slug ) ) )
									: $remaining_slugs;

								if ( $remaining_values ) {
									$remaining_args[ str_replace( 'lgsdn_', '', $remaining_taxonomy ) ] = $remaining_values;
								}
							}

							$remove_url = add_query_arg( $remaining_args, get_permalink( $post_id ) );
							?>
							<?php if ( $term instanceof WP_Term ) : ?>
								<?php if ( $is_last_term && $term_index > 0 ) : ?>
									<span class="lgsdn-playbook-filter-last-value"> <span class="lgsdn-playbook-filter-value-connector">or</span>
								<?php endif; ?>
								<span class="lgsdn-playbook-filter-value-unit">
									<a class="lgsdn-playbook-filter-value" href="<?php echo esc_url( $remove_url ); ?>" data-remove-filter="<?php echo esc_attr( $query_key ); ?>" data-remove-value="<?php echo esc_attr( $slug ); ?>">
										<span class="lgsdn-playbook-filter-value__label"><?php echo esc_html( $term->name ); ?></span><span class="lgsdn-playbook-filter-value__remove" aria-hidden="true">×</span>
										<span class="screen-reader-text"><?php echo esc_html( 'Remove ' . strtolower( $filter_taxonomies[ $taxonomy ] ) . ' filter: ' . $term->name ); ?></span>
									</a><?php if ( $has_comma ) : ?><span class="lgsdn-playbook-filter-punctuation" aria-hidden="true">,</span><?php elseif ( $is_last_term ) : ?><span class="lgsdn-playbook-filter-period" aria-hidden="true">.</span><?php endif; ?>
								</span>
								<?php if ( $is_last_term && $term_index > 0 ) : ?>
									</span>
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php ++$active_group_index; ?>
					<?php endforeach; ?>
				</div>
				<a class="lgsdn-playbook-clear-filters" href="<?php echo esc_url( add_query_arg( array( 'sort' => $sort ), get_permalink( $post_id ) ) ); ?>" data-clear-filters>Clear all filters</a>
			</div>

			<p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-filter-status><?php echo esc_html( $filter_status ); ?></p>
		</div>

		<div class="lgsdn-playbook-results" data-playbook-results aria-busy="false">
			<p class="lgsdn-playbook-results__count"><?php echo esc_html( $result_count_label ); ?></p>
			<?php if ( $items->have_posts() ) : ?>
				<div id="case-study-results" class="lgsdn-playbook-grid">
				<?php
				while ( $items->have_posts() ) :
					$items->the_post();
					$item_id = get_the_ID();
					$practices = get_the_terms( $item_id, 'lgsdn_practice' );
					$practices = $practices && ! is_wp_error( $practices ) ? array_values( $practices ) : array();
					$primary_id = absint( get_post_meta( $item_id, 'lgsdn_primary_practice_id', true ) );
					$primary = null;

					foreach ( $practices as $practice ) {
						if ( $practice->term_id === $primary_id ) {
							$primary = $practice;
							break;
						}
					}

					if ( ! $primary && $practices ) {
						$primary = $practices[0];
					}

					$style = $primary
						? LGSDN_Practice_Styles::for_term( $primary )
						: array(
							'colour' => 'orange',
						);
					$secondary_labels = array();
					foreach ( array( 'lgsdn_service', 'lgsdn_purpose', 'lgsdn_challenge', 'lgsdn_council' ) as $taxonomy ) {
						$terms = get_the_terms( $item_id, $taxonomy );
						if ( ! $terms || is_wp_error( $terms ) ) {
							continue;
						}

						foreach ( $terms as $term ) {
							if ( ! in_array( $term->name, array_column( $secondary_labels, 'name' ), true ) ) {
								$secondary_labels[] = array(
									'name' => $term->name,
									'taxonomy' => $taxonomy,
								);
							}

							if ( 2 === count( $secondary_labels ) ) {
								break 2;
							}
						}
					}
					$contributor_id = absint( get_post_meta( $item_id, 'lgsdn_contributor_id', true ) );
					$contributor_name = $contributor_id && 'lgsdn_person' === get_post_type( $contributor_id )
						? get_the_title( $contributor_id )
						: 'LGSDN';
					?>
					<article class="lgsdn-playbook-card has-practice-<?php echo esc_attr( $style['colour'] ); ?>">
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
									<?php $render_contour( $style['colour'] ); ?>
								<?php endif; ?>
								<div class="lgsdn-playbook-card__media-meta">
									<span class="lgsdn-playbook-card__media-label lgsdn-playbook-card__media-label--author"><?php echo esc_html( $contributor_name ); ?></span>
									<?php if ( $primary ) : ?>
										<span class="lgsdn-playbook-card__media-label lgsdn-playbook-card__media-label--practice"><?php echo esc_html( $primary->name ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<div class="lgsdn-playbook-card__body">
								<h3><?php the_title(); ?></h3>
								<?php if ( $secondary_labels ) : ?>
									<div class="lgsdn-playbook-card__tags" aria-label="Other case study classifications">
										<?php foreach ( $secondary_labels as $label ) : ?>
											<?php $taxonomy_object = get_taxonomy( $label['taxonomy'] ); ?>
											<span class="lgsdn-playbook-tag lgsdn-playbook-tag--secondary" aria-label="<?php echo esc_attr( ( $taxonomy_object ? $taxonomy_object->labels->singular_name : 'Classification' ) . ': ' . $label['name'] ); ?>">
												<?php if ( isset( $taxonomy_icon_urls[ $label['taxonomy'] ] ) ) : ?>
													<img class="lgsdn-playbook-tag__icon" src="<?php echo esc_url( $taxonomy_icon_urls[ $label['taxonomy'] ] ); ?>" alt="" width="16" height="16">
												<?php endif; ?>
												<?php echo esc_html( $label['name'] ); ?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
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
</div>
<?php
wp_reset_postdata();
