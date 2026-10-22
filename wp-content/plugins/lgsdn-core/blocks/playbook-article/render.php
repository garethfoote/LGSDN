<?php
/**
 * Structured Playbook article layout.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();

if ( ! $post_id ) {
	return;
}

$service_terms = get_the_terms( $post_id, 'lgsdn_service' );
$service_terms = $service_terms && ! is_wp_error( $service_terms ) ? array_values( $service_terms ) : array();
$primary_service_id = absint( get_post_meta( $post_id, 'lgsdn_primary_service_id', true ) );
$primary_service = null;

foreach ( $service_terms as $service_term ) {
	if ( $service_term->term_id === $primary_service_id ) {
		$primary_service = $service_term;
		break;
	}
}

$primary_service = $primary_service ?: ( $service_terms[0] ?? null );
$service_excerpt = '';
if ( $primary_service ) {
	$service_introduction = (string) get_term_meta( $primary_service->term_id, LGSDN_Service_Content::INTRO_META, true );
	$service_introduction = trim( wp_strip_all_tags( $service_introduction ) ) ? $service_introduction : term_description( $primary_service->term_id, $primary_service->taxonomy );
	$service_excerpt = wp_trim_words( $service_introduction, 70 );
}
$service_terms = array_values(
	array_filter(
		array_merge( $primary_service ? array( $primary_service ) : array(), $service_terms ),
		static function ( WP_Term $term, int $index ) use ( $primary_service ): bool {
			if ( 0 === $index && $primary_service ) {
				return true;
			}

			return ! $primary_service || $term->term_id !== $primary_service->term_id;
		},
		ARRAY_FILTER_USE_BOTH
	)
);

$service_style = $primary_service ? LGSDN_Service_Styles::for_term( $primary_service ) : array(
	'background' => '#EA7200',
	'foreground' => '#27272D',
	'icon' => 'service',
);

$taxonomy_groups = array(
	'lgsdn_service' => 'Service area',
	'lgsdn_practice' => 'Design practice',
	'lgsdn_challenge' => 'Challenge',
	'lgsdn_council' => 'Council',
);

$taxonomy_terms = array();
foreach ( $taxonomy_groups as $taxonomy => $label ) {
	$terms = 'lgsdn_service' === $taxonomy ? $service_terms : get_the_terms( $post_id, $taxonomy );
	$taxonomy_terms[ $taxonomy ] = $terms && ! is_wp_error( $terms ) ? array_values( $terms ) : array();
}
$has_filterable_taxonomy_terms = (bool) array_filter(
	$taxonomy_terms,
	static function ( array $terms, string $taxonomy ): bool {
		return 'lgsdn_council' !== $taxonomy && ! empty( $terms );
	},
	ARRAY_FILTER_USE_BOTH
);

$title_id = 'lgsdn-playbook-article-title-' . $post_id;
$playbook_url = home_url( '/playbook/' );
$excerpt = get_the_excerpt( $post_id );
$has_featured_image = (bool) lgsdn_playbook_image_id( $post_id, 'article' );
$contributor_id = absint( get_post_meta( $post_id, 'lgsdn_contributor_id', true ) );
$contributor = $contributor_id && 'lgsdn_person' === get_post_type( $contributor_id ) ? get_post( $contributor_id ) : null;
$resources = LGSDN_Fields::sanitize_resources( get_post_meta( $post_id, 'lgsdn_playbook_resources', true ) );
if ( ! $resources ) {
	$legacy_resource_url = esc_url_raw( (string) get_post_meta( $post_id, 'lgsdn_resource_url', true ) );
	if ( $legacy_resource_url ) {
		$resources = array( array( 'label' => 'Resource', 'url' => $legacy_resource_url, 'attachment_id' => 0 ) );
	}
}
$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
$is_external_url = static function ( string $url ) use ( $site_host ): bool {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	return '' !== $host && '' !== $site_host && $host !== $site_host;
};
$content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

// Offer one case study per classification, avoiding duplicate recommendations.
$related_items = array();
$related_taxonomies = array( 'lgsdn_service', 'lgsdn_challenge', 'lgsdn_practice' );
foreach ( $related_taxonomies as $related_taxonomy ) {
	$related_terms = 'lgsdn_service' === $related_taxonomy
		? lgsdn_services_for_item( $post_id )
		: get_the_terms( $post_id, $related_taxonomy );
	if ( ! $related_terms || is_wp_error( $related_terms ) ) {
		continue;
	}

	$related_query = new WP_Query(
		array(
			'post_type' => 'lgsdn_playbook',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'post__not_in' => array_merge( array( $post_id ), array_keys( $related_items ) ),
			'tax_query' => array(
				array(
					'taxonomy' => $related_taxonomy,
					'field' => 'term_id',
					'terms' => wp_list_pluck( $related_terms, 'term_id' ),
					'operator' => 'IN',
				),
			),
			'orderby' => 'date',
			'order' => 'DESC',
		)
	);
	if ( $related_query->have_posts() ) {
		$related_items[ (int) $related_query->posts[0]->ID ] = true;
	}
	wp_reset_postdata();
}
?>
<nav class="lgsdn-playbook-article__back-nav lgsdn-playbook-article-shell" aria-label="Back navigation">
	<a class="lgsdn-playbook-article__back-link" href="<?php echo esc_url( home_url( '/playbook/' ) ); ?>">
		<span class="lgsdn-playbook-article__back-arrow" aria-hidden="true">←</span>
		<span class="lgsdn-playbook-article__back-label">Playbook</span>
	</a>
</nav>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-playbook-article-shell' ) ); ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="lgsdn-playbook-article-layout <?php echo $has_featured_image ? 'has-featured-image' : 'no-featured-image'; ?>">
		<div class="lgsdn-playbook-article__hero <?php echo $has_featured_image ? 'has-featured-image' : 'no-featured-image'; ?>">
			<header class="lgsdn-playbook-article__title">
				<h1 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
			</header>

			<?php if ( $has_featured_image ) : ?>
				<figure class="lgsdn-playbook-article__featured-image" data-lgsdn-article-image-stage>
					<?php echo lgsdn_playbook_image( $post_id, 'article', 'full', array( 'class' => 'lgsdn-playbook-article__primary-image', 'loading' => 'eager', 'sizes' => '100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
			<?php endif; ?>

		</div>

		<div class="lgsdn-playbook-article__body">
			<div class="lgsdn-playbook-article__sidebar">
				<aside class="lgsdn-playbook-article__taxonomy" aria-label="Case study classifications">
					<?php if ( $has_filterable_taxonomy_terms ) : ?>
						<p class="lgsdn-playbook-article__filter-hint">
							<svg class="lgsdn-playbook-tag__filter-icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
								<path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"></path>
							</svg>
							<span>Filter Playbook case studies</span>
						</p>
					<?php endif; ?>
					<?php foreach ( $taxonomy_groups as $taxonomy => $label ) : ?>
						<?php if ( empty( $taxonomy_terms[ $taxonomy ] ) ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php if ( 'lgsdn_council' === $taxonomy ) : ?>
							<section class="lgsdn-playbook-article__taxonomy-group">
								<h2><?php echo esc_html( $label ); ?></h2>
								<p class="lgsdn-playbook-article__author"><?php echo esc_html( implode( ', ', wp_list_pluck( $taxonomy_terms[ $taxonomy ], 'name' ) ) ); ?></p>
							</section>
							<?php continue; ?>
						<?php endif; ?>
						<section class="lgsdn-playbook-article__taxonomy-group">
							<h2><?php echo esc_html( $label ); ?></h2>
							<div class="lgsdn-playbook-card__tags lgsdn-playbook-card__tags--article">
								<?php foreach ( $taxonomy_terms[ $taxonomy ] as $term ) : ?>
								<?php $filter_taxonomy = str_replace( 'lgsdn_', '', $taxonomy ); ?>
									<a class="lgsdn-playbook-tag lgsdn-playbook-tag--secondary" href="<?php echo esc_url( add_query_arg( $filter_taxonomy, $term->slug, $playbook_url ) . '#case-study-panel' ); ?>" aria-label="<?php echo esc_attr( 'Filter playbook by ' . $term->name ); ?>">
										<?php echo esc_html( $term->name ); ?>
										<svg class="lgsdn-playbook-tag__filter-icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
											<path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"></path>
										</svg>
									</a>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
					<?php if ( $contributor instanceof WP_Post ) : ?>
						<section class="lgsdn-playbook-article__taxonomy-group">
							<h2>Contributors</h2>
							<p class="lgsdn-playbook-article__author"><?php echo esc_html( get_the_title( $contributor ) ); ?></p>
						</section>
					<?php endif; ?>
					<?php
					$published_timestamp = (int) get_post_time( 'U', true, $post_id );
					?>
					<section class="lgsdn-playbook-article__taxonomy-group">
						<h2>Published</h2>
						<p class="lgsdn-playbook-article__author"><time datetime="<?php echo esc_attr( gmdate( 'c', $published_timestamp ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ), $published_timestamp ) ); ?></time></p>
					</section>
				</aside>
			</div>

			<div class="lgsdn-playbook-article__copy">
				<?php if ( $excerpt ) : ?>
					<div class="lgsdn-playbook-article__lead"><?php echo wp_kses_post( wpautop( $excerpt ) ); ?></div>
				<?php endif; ?>
				<?php if ( $resources ) : ?>
					<aside class="lgsdn-event__resources" aria-labelledby="lgsdn-playbook-resources-title-<?php echo esc_attr( (string) $post_id ); ?>">
						<h2 id="lgsdn-playbook-resources-title-<?php echo esc_attr( (string) $post_id ); ?>">Resources</h2>
						<ul role="list">
							<?php foreach ( $resources as $resource ) : ?>
								<li>
									<a<?php echo $is_external_url( $resource['url'] ) ? ' class="lgsdn-button--external"' : ''; ?> href="<?php echo esc_url( $resource['url'] ); ?>">
										<span class="lgsdn-button__label"><?php echo esc_html( $resource['label'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</aside>
					<?php if ( $excerpt ) : ?>
						<hr class="lgsdn-playbook-article__lead-rule">
					<?php endif; ?>
				<?php endif; ?>
				<div class="lgsdn-article">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( $primary_service ) : ?>
					<section class="lgsdn-playbook-article__service-area" aria-labelledby="lgsdn-playbook-service-area-title-<?php echo esc_attr( (string) $post_id ); ?>">
						<a class="lgsdn-playbook-article__service-card" href="<?php echo esc_url( get_term_link( $primary_service ) ); ?>" style="--lgsdn-service-card-bg:<?php echo esc_attr( $service_style['gradient'] ); ?>;--lgsdn-service-card-fg:#FFFFFF;">
							<span class="lgsdn-playbook-article__service-label">Service</span>
							<img src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="" class="lgsdn-playbook-article__service-icon lgsdn-playbook-article__service-icon--<?php echo esc_attr( $service_style['icon'] ); ?>">
							<span class="lgsdn-playbook-article__service-name">
								<span class="lgsdn-service-card__title"><?php echo esc_html( $primary_service->name ); ?></span>
							</span>
						</a>
						<div class="lgsdn-playbook-article__service-area-copy">
							<span class="lgsdn-playbook-article__service-eyebrow">This case study is part of</span>
							<h2 id="lgsdn-playbook-service-area-title-<?php echo esc_attr( (string) $post_id ); ?>"><?php echo esc_html( $primary_service->name ); ?></h2>
							<?php if ( $service_excerpt ) : ?>
								<p><?php echo esc_html( $service_excerpt ); ?></p>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php if ( $related_items ) : ?>
		<section class="lgsdn-playbook-related" aria-labelledby="lgsdn-playbook-related-title-<?php echo esc_attr( (string) $post_id ); ?>">
			<header class="lgsdn-playbook-section-header">
				<h2 id="lgsdn-playbook-related-title-<?php echo esc_attr( (string) $post_id ); ?>">More from the Playbook</h2>
			</header>
			<div class="lgsdn-playbook-grid">
				<?php foreach ( array_keys( $related_items ) as $related_item_id ) : ?>
					<?php
					$related_item = get_post( $related_item_id );
					if ( ! $related_item instanceof WP_Post ) {
						continue;
					}
					$GLOBALS['post'] = $related_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $related_item );
					lgsdn_render_playbook_card( 3, true );
					?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</article>
<?php wp_reset_postdata(); ?>
