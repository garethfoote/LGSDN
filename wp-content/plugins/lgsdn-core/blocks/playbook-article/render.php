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
	'background' => '#FF9D4D',
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
	$terms = get_the_terms( $post_id, $taxonomy );
	$taxonomy_terms[ $taxonomy ] = $terms && ! is_wp_error( $terms ) ? array_values( $terms ) : array();
}

$title_id = 'lgsdn-playbook-article-title-' . $post_id;
$excerpt = get_the_excerpt( $post_id );
$content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
?>
<nav class="lgsdn-playbook-article__back-nav lgsdn-playbook-article-shell" aria-label="Back navigation">
	<a class="lgsdn-playbook-article__back-link" href="<?php echo esc_url( home_url( '/playbook/' ) ); ?>">
		<span class="lgsdn-playbook-article__back-arrow" aria-hidden="true">←</span>
		<span class="lgsdn-playbook-article__back-label">Playbook</span>
	</a>
</nav>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-playbook-article-shell' ) ); ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="lgsdn-playbook-article-layout">
		<div class="lgsdn-playbook-article__hero">
			<header class="lgsdn-playbook-article__title">
				<h1 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
				<?php if ( $excerpt ) : ?>
					<div class="lgsdn-playbook-article__lead"><?php echo wp_kses_post( wpautop( $excerpt ) ); ?></div>
				<?php endif; ?>
			</header>

			<?php if ( $primary_service ) : ?>
				<a class="lgsdn-playbook-article__service-card" href="<?php echo esc_url( get_term_link( $primary_service ) ); ?>" style="--lgsdn-service-card-bg:<?php echo esc_attr( $service_style['background'] ); ?>;--lgsdn-service-card-fg:<?php echo esc_attr( $service_style['foreground'] ); ?>;">
					<span class="lgsdn-playbook-article__service-label">Service</span>
					<img src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="" class="lgsdn-playbook-article__service-icon">
					<span class="lgsdn-playbook-service-card__title"><?php echo esc_html( $primary_service->name ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( has_post_thumbnail( $post_id ) ) : ?>
			<figure class="lgsdn-playbook-article__featured-image">
				<?php echo get_the_post_thumbnail( $post_id, 'full', array( 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</figure>
		<?php endif; ?>

		<div class="lgsdn-playbook-article__body">
			<aside class="lgsdn-playbook-article__taxonomy" aria-label="Case study classifications">
				<?php foreach ( $taxonomy_groups as $taxonomy => $label ) : ?>
					<?php if ( empty( $taxonomy_terms[ $taxonomy ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<section class="lgsdn-playbook-article__taxonomy-group">
						<h2><?php echo esc_html( $label ); ?></h2>
						<div class="lgsdn-playbook-card__tags lgsdn-playbook-card__tags--article">
							<?php foreach ( $taxonomy_terms[ $taxonomy ] as $term ) : ?>
								<?php
								$tag_style = '';
								$tag_class = 'lgsdn-playbook-tag lgsdn-playbook-tag--secondary';
								if ( 'lgsdn_service' === $taxonomy ) {
									$term_style = LGSDN_Service_Styles::for_term( $term );
									$tag_class = 'lgsdn-playbook-tag lgsdn-playbook-tag--service';
									$tag_style = sprintf(
										' style="--lgsdn-service-tag-bg:%1$s;--lgsdn-service-tag-fg:%2$s;"',
										esc_attr( $term_style['background'] ),
										esc_attr( $term_style['foreground'] )
									);
								}
								?>
								<span class="<?php echo esc_attr( $tag_class ); ?>"<?php echo $tag_style; ?>><?php echo esc_html( $term->name ); ?></span>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</aside>

			<div class="lgsdn-playbook-article__copy">
				<div class="lgsdn-article">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	</div>
</article>
