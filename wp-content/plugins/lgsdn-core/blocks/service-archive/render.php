<?php
/** Service area landing page. */
$term = get_queried_object();
if ( ! $term instanceof WP_Term || 'lgsdn_service' !== $term->taxonomy ) { return; }
$service_style = LGSDN_Service_Styles::for_term( $term );
$introduction = (string) get_term_meta( $term->term_id, LGSDN_Service_Content::INTRO_META, true );
$introduction = trim( wp_strip_all_tags( $introduction ) ) ? wpautop( $introduction ) : term_description( $term->term_id, $term->taxonomy );
$items = new WP_Query( array(
	'post_type' => 'lgsdn_playbook', 'post_status' => 'publish',
	'posts_per_page' => -1, 'no_found_rows' => true,
	'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
	'tax_query' => array( array( 'taxonomy' => 'lgsdn_service', 'field' => 'term_id', 'terms' => $term->term_id ) ),
) );
?>
<nav class="lgsdn-playbook-article__back-nav lgsdn-playbook-article-shell" aria-label="Back navigation">
	<a class="lgsdn-playbook-article__back-link" href="<?php echo esc_url( home_url( '/playbook/' ) ); ?>">
		<span class="lgsdn-playbook-article__back-arrow" aria-hidden="true">←</span>
		<span class="lgsdn-playbook-article__back-label">Playbook</span>
	</a>
</nav>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-service-archive lgsdn-playbook-article-shell' ) ); ?>>
	<div class="lgsdn-service-archive__header">
		<header class="lgsdn-playbook-article__title"><h1><?php echo esc_html( $term->name ); ?></h1></header>
		<div class="lgsdn-playbook-article__service-card" style="--lgsdn-service-card-bg:<?php echo esc_attr( $service_style['background'] ); ?>;--lgsdn-service-card-fg:<?php echo esc_attr( $service_style['foreground'] ); ?>;">
			<span class="lgsdn-playbook-article__service-label">Service</span>
			<img class="lgsdn-playbook-article__service-icon" src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="">
			<span class="lgsdn-playbook-service-card__title"><?php echo esc_html( $term->name ); ?></span>
		</div>
		<?php if ( trim( wp_strip_all_tags( $introduction ) ) ) : ?>
			<div class="lgsdn-service-archive__introduction lgsdn-playbook-article__copy">
				<div class="lgsdn-article"><?php echo wp_kses_post( $introduction ); ?></div>
			</div>
		<?php endif; ?>
	</div>
	<section class="lgsdn-playbook-case-studies" aria-labelledby="service-case-studies-title">
		<header class="lgsdn-playbook-section-header"><h2 id="service-case-studies-title">Case studies</h2></header>
		<?php if ( $items->have_posts() ) : ?>
			<div class="lgsdn-playbook-grid">
				<?php while ( $items->have_posts() ) : $items->the_post(); ?>
					<?php lgsdn_render_playbook_card( 3 ); ?>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p class="lgsdn-playbook-empty">No case studies have been published for this service area yet.</p>
		<?php endif; ?>
	</section>
</div>
<?php wp_reset_postdata(); ?>
