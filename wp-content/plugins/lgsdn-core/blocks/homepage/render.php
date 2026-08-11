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
$taxonomy_icon_urls = array(
	'lgsdn_service' => $image_base . '/taxonomy-service.svg',
	'lgsdn_challenge' => $image_base . '/taxonomy-challenge.svg',
);

$featured = new WP_Query(
	array(
		'post_type' => 'lgsdn_playbook',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_key' => 'lgsdn_featured_home',
		'meta_value' => '1',
		'orderby' => 'modified',
		'order' => 'DESC',
	)
);

if ( ! $featured->have_posts() ) {
	$featured = new WP_Query(
		array(
			'post_type' => 'lgsdn_playbook',
			'post_status' => 'publish',
			'posts_per_page' => 1,
		)
	);
}

$spotlight_id = $featured->have_posts() ? (int) $featured->posts[0]->ID : 0;
$spotlight_title = $spotlight_id ? get_the_title( $spotlight_id ) : 'Digital prototyping: Shaping a Platform with Parents';
$spotlight_description = $spotlight_id && has_excerpt( $spotlight_id )
	? get_the_excerpt( $spotlight_id )
	: 'A short description of this featured item goes here.';
$spotlight_url = $spotlight_id ? get_permalink( $spotlight_id ) : '#';
$spotlight_image = $spotlight_id && has_post_thumbnail( $spotlight_id )
	? get_the_post_thumbnail(
		$spotlight_id,
		'large',
		array(
			'class' => 'spotlight__image',
		)
	)
	: sprintf(
		'<img class="spotlight__image" src="%s" alt="%s">',
		esc_url( $image_base . '/home-feature-image.png' ),
		esc_attr( 'Three people working together around a table covered with notes and prototypes' )
	);
$spotlight_practices = $spotlight_id ? get_the_terms( $spotlight_id, 'lgsdn_practice' ) : array();
$spotlight_practices = $spotlight_practices && ! is_wp_error( $spotlight_practices ) ? array_values( $spotlight_practices ) : array();
$spotlight_primary_id = $spotlight_id ? absint( get_post_meta( $spotlight_id, 'lgsdn_primary_practice_id', true ) ) : 0;
$spotlight_primary = null;

foreach ( $spotlight_practices as $practice ) {
	if ( $practice->term_id === $spotlight_primary_id ) {
		$spotlight_primary = $practice;
		break;
	}
}

if ( ! $spotlight_primary && $spotlight_practices ) {
	$spotlight_primary = $spotlight_practices[0];
}

$spotlight_style = $spotlight_primary
	? LGSDN_Practice_Styles::for_term( $spotlight_primary )
	: array(
		'colour' => 'orange',
	);
$spotlight_secondary_labels = array();

if ( $spotlight_id ) {
	foreach ( array( 'lgsdn_service', 'lgsdn_purpose', 'lgsdn_challenge', 'lgsdn_council' ) as $taxonomy ) {
		$terms = get_the_terms( $spotlight_id, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			if ( ! in_array( $term->name, array_column( $spotlight_secondary_labels, 'name' ), true ) ) {
				$spotlight_secondary_labels[] = array(
					'name' => $term->name,
					'taxonomy' => $taxonomy,
				);
			}

			if ( 2 === count( $spotlight_secondary_labels ) ) {
				break 2;
			}
		}
	}
}
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
					<h1 class="home-hero__title">Local Government Service Design Network</h1>
					<p class="home-hero__intro"><?php echo esc_html( LGSDN_Homepage_Fields::value( $post_id, 'lgsdn_home_lead' ) ); ?></p>
				</div>
				<div class="home-hero__graphic" aria-hidden="true">
					<img src="<?php echo esc_url( $image_base . '/home-contour.svg' ); ?>" alt="">
				</div>
			</section>

			<section class="feature-grid" id="join" aria-label="Ways to take part">
				<?php foreach ( $features as $feature ) : ?>
					<article class="feature-card">
						<h2><?php echo esc_html( $feature['title'] ); ?></h2>
						<p><?php echo nl2br( esc_html( $feature['body'] ) ); ?></p>
						<a class="button lgsdn-button--arrow" href="<?php echo esc_url( $feature['url'] ); ?>"><span class="lgsdn-button__label"><?php echo esc_html( $feature['title'] ); ?></span></a>
					</article>
				<?php endforeach; ?>
			</section>

			<article class="spotlight spotlight--practice-<?php echo esc_attr( $spotlight_style['colour'] ); ?>" id="playbook">
				<div class="spotlight__media">
					<?php echo wp_kses_post( $spotlight_image ); ?>
					<?php if ( $spotlight_primary || $spotlight_secondary_labels ) : ?>
						<div class="spotlight__tags" aria-label="Case study classifications">
							<?php if ( $spotlight_primary ) : ?>
								<span class="tag tag--practice"><?php echo esc_html( $spotlight_primary->name ); ?></span>
							<?php endif; ?>
							<?php foreach ( $spotlight_secondary_labels as $label ) : ?>
								<?php $taxonomy_object = get_taxonomy( $label['taxonomy'] ); ?>
								<span class="tag" aria-label="<?php echo esc_attr( ( $taxonomy_object ? $taxonomy_object->labels->singular_name : 'Classification' ) . ': ' . $label['name'] ); ?>">
									<?php if ( isset( $taxonomy_icon_urls[ $label['taxonomy'] ] ) ) : ?>
										<img class="taxonomy-tag-icon" src="<?php echo esc_url( $taxonomy_icon_urls[ $label['taxonomy'] ] ); ?>" alt="" width="16" height="16">
									<?php endif; ?>
									<?php echo esc_html( $label['name'] ); ?>
								</span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="spotlight__content">
					<p class="spotlight__eyebrow">From the playbook</p>
					<h2><?php echo esc_html( $spotlight_title ); ?></h2>
					<p><?php echo esc_html( $spotlight_description ); ?></p>
					<div class="button-list"><a class="button button--strong lgsdn-button--arrow" href="<?php echo esc_url( $spotlight_url ); ?>"><span class="lgsdn-button__label">Read the case study</span></a><a class="button lgsdn-button--arrow" href="<?php echo esc_url( $playbook_url ); ?>"><span class="lgsdn-button__label">See the full playbook</span></a></div>
				</div>
			</article>

			<div id="events">
				<?php echo do_blocks( '<!-- wp:lgsdn/events-list /-->' ); ?>
			</div>
		</main>
	</div>
	<footer class="site-footer"></footer>
</div>
