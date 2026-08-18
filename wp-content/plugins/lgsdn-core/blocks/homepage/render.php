<?php
/**
 * Structured homepage block.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();
if ( ! $post_id ) {
	return;
}

$page_url = static function ( int $selected_id, string $fallback_path ): string {
	if ( $selected_id && 'page' === get_post_type( $selected_id ) ) {
		return (string) get_permalink( $selected_id );
	}

	$fallback = get_page_by_path( $fallback_path );
	return $fallback instanceof WP_Post ? (string) get_permalink( $fallback ) : home_url( '/' . trim( $fallback_path, '/' ) . '/' );
};

$feature_fallbacks = array( 'join', 'playbook', 'join' );
$feature_cta_labels = array( 'Join', 'Browse', 'Contribute' );
$feature_cta_suffixes = array( ' the network', ' the playbook', ' an example' );
$features = array();
for ( $index = 1; $index <= 3; $index++ ) {
	$features[] = array(
		'title' => LGSDN_Homepage_Fields::value( $post_id, "lgsdn_home_feature_{$index}_title" ),
		'body' => LGSDN_Homepage_Fields::value( $post_id, "lgsdn_home_feature_{$index}_body" ),
		'url' => $page_url( absint( get_post_meta( $post_id, "lgsdn_home_feature_{$index}_page_id", true ) ), $feature_fallbacks[ $index - 1 ] ),
	);
}

$playbook_url = $page_url( 0, 'playbook' );
$network_url = $page_url( 0, 'network' );
$join_url = $page_url( 0, 'join' );
$image_base = get_theme_file_uri( 'assets/images' );
$service_terms = LGSDN_Service_Styles::homepage_terms();

$case_studies = new WP_Query(
	array(
		'post_type' => 'lgsdn_playbook',
		'post_status' => 'publish',
		'posts_per_page' => 4,
		'orderby' => 'date',
		'order' => 'DESC',
	)
);


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
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'homepage-render alignfull' ) ); ?>>
	<a class="skip-link" href="#main-content">Skip to main content</a>
	<div class="site-frame">
		<header class="site-header">
			<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Local Government Service Design Network home">
				<img src="<?php echo esc_url( $image_base . '/logo.svg' ); ?>" alt="" width="84" height="25">
			</a>
			<nav class="site-nav" id="site-navigation" aria-label="Main navigation">
				<button class="menu-close" type="button" aria-label="Close menu"><span aria-hidden="true">×</span></button>
				<a href="#about">About</a>
				<a href="<?php echo esc_url( $playbook_url ); ?>">Playbook</a>
				<a href="#events">Events</a>
				<a href="<?php echo esc_url( $network_url ); ?>">People</a>
				<a class="button button--nav" href="<?php echo esc_url( $join_url ); ?>">Join</a>
			</nav>
			<button class="menu-backdrop" type="button" aria-label="Close menu" tabindex="-1"></button>
			<button class="menu-button" type="button" aria-label="Open menu" aria-controls="site-navigation" aria-expanded="false"><span></span><span></span><span></span></button>
		</header>

		<main id="main-content" tabindex="-1">
			<section class="home-hero" id="about">
				<div class="home-hero__copy">
					<h1 class="home-hero__title">Local Government<br>Service Design Network</h1>
					<p class="home-hero__intro"><?php echo esc_html( LGSDN_Homepage_Fields::value( $post_id, 'lgsdn_home_lead' ) ); ?></p>
				</div>
				<div class="home-hero__graphic" aria-hidden="true">
					<img src="<?php echo esc_url( $image_base . '/home-contour.svg' ); ?>" alt="">
				</div>
			</section>

			<section class="feature-grid" id="join" aria-label="Ways to take part">
				<?php foreach ( $features as $index => $feature ) : ?>
					<article class="feature-card">
						<h2><?php echo esc_html( $feature['title'] ); ?></h2>
						<p><?php echo nl2br( esc_html( $feature['body'] ) ); ?></p>
						<a class="button lgsdn-button--arrow" href="<?php echo esc_url( $feature['url'] ); ?>"><span class="lgsdn-button__label"><?php echo esc_html( $feature_cta_labels[ $index ] ); ?></span><span class="screen-reader-text"><?php echo esc_html( $feature_cta_suffixes[ $index ] ); ?></span></a>
					</article>
				<?php endforeach; ?>
			</section>

			<section class="homepage-playbook" id="playbook" aria-labelledby="homepage-playbook-title">
				<div class="homepage-playbook__intro">
					<h2 id="homepage-playbook-title">From the playbook</h2>
					<p>Find out how service design works with these services in local government.</p>
				</div>
				<div class="homepage-service-row" aria-label="Explore by service area">
					<article class="homepage-service-intro" data-service-intro>
						<h4 id="homepage-service-areas-title">Explore by service…</h4>
						<p>Find out how service design works with these services within local government.</p>
					</article>
					<p id="homepage-service-areas-hint" class="screen-reader-text">Use Tab to move through the cards. When this area is focused, use the left and right arrow keys to scroll horizontally.</p>
					<div class="homepage-service-scroller" data-service-scroller tabindex="0" role="region" aria-labelledby="homepage-service-areas-title" aria-describedby="homepage-service-areas-hint">
						<?php foreach ( $service_terms as $service_term ) : ?>
							<?php
							$service_style = LGSDN_Service_Styles::for_term( $service_term );
							$service_url = get_term_link( $service_term );
							if ( is_wp_error( $service_url ) ) {
								$service_url = $playbook_url;
							}
							?>
							<a class="homepage-service-card homepage-service-card--<?php echo esc_attr( $service_style['colour'] ); ?>" style="--lgsdn-service-card-fg:<?php echo esc_attr( $service_style['foreground'] ); ?>" href="<?php echo esc_url( $service_url ); ?>">
								<span class="homepage-service-card__label">Service</span>
								<img class="homepage-service-card__icon homepage-service-card__icon--<?php echo esc_attr( $service_style['icon'] ); ?>" src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="">
								<h3><?php echo esc_html( $service_term->name ); ?></h3>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="homepage-case-study-row">
					<article class="homepage-service-intro homepage-case-study-intro" data-case-study-intro>
						<h4 id="homepage-case-studies-title">Read a case study</h4>
						<p>Find out how service design works with these services within local government.</p>
						<a class="button button--strong lgsdn-button--arrow" href="<?php echo esc_url( $playbook_url ); ?>"><span class="lgsdn-button__label">See all</span></a>
					</article>
					<p id="homepage-case-studies-hint" class="screen-reader-text">Use Tab to move through the cards. When this area is focused, use the left and right arrow keys to scroll horizontally.</p>
					<div class="homepage-case-study-scroller" data-case-study-scroller tabindex="0" role="region" aria-labelledby="homepage-case-studies-title" aria-describedby="homepage-case-studies-hint">
						<?php if ( $case_studies->have_posts() ) : ?>
							<?php while ( $case_studies->have_posts() ) : $case_studies->the_post(); ?>
								<?php
								$item_id = get_the_ID();
								$primary_service = $primary_service_for_item( $item_id );
								$councils = get_the_terms( $item_id, 'lgsdn_council' );
								$council_label = $councils && ! is_wp_error( $councils ) ? implode( ', ', wp_list_pluck( $councils, 'name' ) ) : '';
								$service_style = $primary_service
									? LGSDN_Service_Styles::for_term( $primary_service )
									: array(
										'background' => '#FF9D4D',
										'foreground' => '#27272D',
									);
								?>
								<a class="homepage-case-study-card" href="<?php the_permalink(); ?>">
									<div class="homepage-case-study-card__media">
										<?php if ( has_post_thumbnail() ) : ?>
											<?php the_post_thumbnail( 'large', array( 'class' => 'homepage-case-study-card__image', 'loading' => 'lazy' ) ); ?>
											<?php else : ?>
											<img class="homepage-case-study-card__image" src="<?php echo esc_url( $image_base . '/home-feature-image.png' ); ?>" alt="" loading="lazy">
										<?php endif; ?>
										<?php if ( $council_label ) : ?>
											<span class="homepage-case-study-card__council"><?php echo esc_html( $council_label ); ?></span>
										<?php endif; ?>
									</div>
									<div class="homepage-case-study-card__body">
										<?php if ( $primary_service ) : ?>
											<span class="homepage-case-study-card__tag" style="--case-study-tag-bg:<?php echo esc_attr( $service_style['background'] ); ?>;--case-study-tag-fg:<?php echo esc_attr( $service_style['foreground'] ); ?>;"><?php echo esc_html( $primary_service->name ); ?></span>
										<?php endif; ?>
										<h4><?php the_title(); ?></h4>
									</div>
								</a>
							<?php endwhile; ?>
						<?php endif; ?>
					</div>
					<?php wp_reset_postdata(); ?>
				</div>
			</section>

			<div id="events">
				<?php echo do_blocks( '<!-- wp:lgsdn/events-list /-->' ); ?>
			</div>
		</main>
	</div>
	<footer class="site-footer"></footer>
</div>
