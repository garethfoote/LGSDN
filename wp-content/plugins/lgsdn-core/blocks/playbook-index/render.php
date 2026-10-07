<?php
/**
 * Structured Playbook index.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();
$page_title = $post_id ? get_the_title( $post_id ) : 'Playbook';
$image_base = get_theme_file_uri( 'assets/images' );
$contribute_title = LGSDN_Playbook_Page_Fields::value( $post_id, 'lgsdn_playbook_contribute_title' );
$contribute_body = LGSDN_Playbook_Page_Fields::value( $post_id, 'lgsdn_playbook_contribute_body' );
$contribute_button_label = LGSDN_Playbook_Page_Fields::value( $post_id, 'lgsdn_playbook_contribute_button_label' );
$contribute_button_url = LGSDN_Playbook_Page_Fields::value( $post_id, 'lgsdn_playbook_contribute_button_url' );
$contribute_button_href = str_starts_with( $contribute_button_url, '/' ) && ! str_starts_with( $contribute_button_url, '//' )
	? home_url( $contribute_button_url )
	: $contribute_button_url;

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
$submitted_search = isset( $_GET['search'] ) ? wp_unslash( $_GET['search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search_query = is_string( $submitted_search ) ? sanitize_text_field( $submitted_search ) : '';
$search_query = trim( $search_query );
$contributor_id = isset( $_GET['contributor'] ) ? absint( wp_unslash( $_GET['contributor'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( $contributor_id && ( 'lgsdn_person' !== get_post_type( $contributor_id ) || 'publish' !== get_post_status( $contributor_id ) ) ) {
	$contributor_id = 0;
}

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

if ( $search_query ) {
	$query_args['s'] = $search_query;
}

if ( $contributor_id ) {
	$query_args['meta_query'] = array(
		array(
			'key' => 'lgsdn_contributor_id',
			'value' => $contributor_id,
			'compare' => '=',
			'type' => 'NUMERIC',
		),
	);
}

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

$search_posts_search = null;
if ( $search_query ) {
	$search_posts_search = static function ( string $search, WP_Query $query ) use ( $search_query ): string {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $search_query ) . '%';
		return $wpdb->prepare(
			" AND (
				{$wpdb->posts}.post_title LIKE %s
				OR {$wpdb->posts}.post_excerpt LIKE %s
				OR EXISTS (
					SELECT 1
					FROM {$wpdb->term_relationships} AS council_relationship
					INNER JOIN {$wpdb->term_taxonomy} AS council_taxonomy
						ON council_taxonomy.term_taxonomy_id = council_relationship.term_taxonomy_id
					INNER JOIN {$wpdb->terms} AS council_term
						ON council_term.term_id = council_taxonomy.term_id
					WHERE council_relationship.object_id = {$wpdb->posts}.ID
						AND council_taxonomy.taxonomy = 'lgsdn_council'
						AND council_term.name LIKE %s
				)
			)",
			$like,
			$like,
			$like
		);
	};
	add_filter( 'posts_search', $search_posts_search, 10, 2 );
}

try {
	$items = new WP_Query( $query_args );
} finally {
	if ( $search_posts_search ) {
		remove_filter( 'posts_search', $search_posts_search, 10 );
	}
}

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
if ( $search_query ) {
	$filter_status .= ' Search: ' . $search_query . '.';
}
if ( $active_filter_labels ) {
	$filter_status .= ' Active filters: ' . implode( ', ', $active_filter_labels ) . '.';
} else {
	$filter_status .= ' No filters applied.';
}
if ( $contributor_id ) {
	$filter_status .= ' Author: ' . get_the_title( $contributor_id ) . '.';
}

$has_active_criteria = $search_query || $active_filters || $contributor_id;

$service_terms = LGSDN_Service_Styles::playbook_terms();

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

	<section class="lgsdn-playbook-contribute" aria-labelledby="contribute-title">
		<div class="lgsdn-playbook-contribute__inner lgsdn-playbook-shell">
			<div class="lgsdn-playbook-contribute__copy">
				<h2 id="contribute-title"><?php echo esc_html( $contribute_title ); ?></h2>
				<p><?php echo esc_html( $contribute_body ); ?></p>
			</div>
			<a class="button button--strong button--large lgsdn-button--arrow" href="<?php echo esc_url( $contribute_button_href ); ?>">
				<span class="lgsdn-button__label"><?php echo esc_html( $contribute_button_label ); ?></span>
			</a>
		</div>
	</section>

	<section class="lgsdn-playbook-listing lgsdn-playbook-shell" aria-labelledby="case-studies-title">
		<section class="lgsdn-playbook-services" aria-labelledby="service-areas-title">
			<header class="lgsdn-playbook-section-header">
				<h2 id="service-areas-title">Explore by service area</h2>
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
							<a class="lgsdn-service-card lgsdn-service-card--<?php echo esc_attr( $service_style['colour'] ); ?>" style="--lgsdn-service-card-bg:<?php echo esc_attr( $service_style['gradient'] ); ?>" href="<?php echo esc_url( $service_url ); ?>">
								<span class="lgsdn-service-card__label">Service</span>
								<img class="lgsdn-service-card__icon lgsdn-service-card__icon--<?php echo esc_attr( $service_style['icon'] ); ?>" src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="">
								<h3 class="lgsdn-service-card__title"><?php echo esc_html( $service_term->name ); ?></h3>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<section class="lgsdn-playbook-case-studies" id="case-study-panel" role="tabpanel" data-playbook-panel="case-studies" aria-labelledby="case-studies-title">
			<header class="lgsdn-playbook-section-header">
				<h2 id="case-studies-title" tabindex="-1" data-playbook-heading="case-studies">Read the case studies</h2>
			</header>

		<div class="lgsdn-playbook-browse">
			<form class="lgsdn-playbook-filters" method="get" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>" data-playbook-filter-form aria-describedby="playbook-filter-hint">
				<p id="playbook-filter-hint" class="screen-reader-text">Choose one option from each filter, or search case study titles, excerpts and council names. Selections across categories must all match.</p>

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
				<?php if ( $contributor_id ) : ?><input type="hidden" name="contributor" value="<?php echo esc_attr( (string) $contributor_id ); ?>"><?php endif; ?>

				<label class="lgsdn-playbook-search" for="lgsdn-playbook-search">
					<span class="screen-reader-text">Search case studies by title, excerpt or council</span>
					<input id="lgsdn-playbook-search" type="search" name="search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Search case studies" autocomplete="off" data-playbook-search>
					<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
						<circle cx="10.75" cy="10.75" r="5.75"></circle>
						<path d="m15 15 4 4"></path>
					</svg>
				</label>

				<button class="lgsdn-playbook-filter-button" type="submit">Apply filters</button>

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

			<div class="lgsdn-playbook-active-filters" aria-labelledby="active-filters-label" data-active-filters <?php echo $has_active_criteria ? '' : 'hidden'; ?>>
				<span id="active-filters-label" class="screen-reader-text">Active filters</span>
				<div class="lgsdn-playbook-active-filters__sentence lgsdn-type-filter-sentence">
					<span class="lgsdn-playbook-filter-scaffolding">Showing </span>
					<?php if ( $search_query ) : ?>
						<span class="lgsdn-playbook-filter-group-label">case studies</span><span class="lgsdn-playbook-filter-scaffolding"> matching </span>
						<span class="lgsdn-playbook-filter-value-unit">
							<span class="lgsdn-playbook-filter-value"><span class="lgsdn-playbook-filter-value__label">“<?php echo esc_html( $search_query ); ?>”</span></span><?php if ( $active_filters ) : ?><span class="lgsdn-playbook-filter-punctuation" aria-hidden="true">,</span><?php else : ?><span class="lgsdn-playbook-filter-period" aria-hidden="true">.</span><?php endif; ?>
						</span>
					<?php endif; ?>
					<?php $active_group_index = 0; ?>
					<?php foreach ( $active_filters as $taxonomy => $slugs ) : ?>
						<?php if ( $search_query || $active_group_index > 0 ) : ?>
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
				<div class="lgsdn-playbook-active-filters__controls">
					<?php if ( $contributor_id ) : ?>
						<a class="lgsdn-playbook-active-filter" href="<?php echo esc_url( remove_query_arg( 'contributor' ) ); ?>" aria-label="<?php echo esc_attr( 'Remove author filter: ' . get_the_title( $contributor_id ) ); ?>">
							<span class="lgsdn-playbook-active-filter__type">Author</span>
							<span class="lgsdn-playbook-active-filter__value"><?php echo esc_html( get_the_title( $contributor_id ) ); ?></span>
							<span class="lgsdn-playbook-active-filter__remove" aria-hidden="true"></span>
						</a>
						<span class="lgsdn-playbook-active-filters__separator" aria-hidden="true"></span>
					<?php endif; ?>
					<a class="lgsdn-playbook-clear-filters" href="<?php echo esc_url( add_query_arg( array( 'sort' => $sort ), get_permalink( $post_id ) ) ); ?>" data-clear-filters><span class="lgsdn-playbook-clear-filters__icon" aria-hidden="true">×</span><span class="lgsdn-playbook-clear-filters__label">Reset all filters</span></a>
				</div>
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
					lgsdn_render_playbook_card();
					?>
				<?php endwhile; ?>
			</div>
			<?php else : ?>
				<p class="lgsdn-playbook-empty">No case studies match your search and filters. Try another search or remove a filter to broaden the results.</p>
			<?php endif; ?>
		</div>
		</section>
	</section>
</div>
<?php
wp_reset_postdata();
