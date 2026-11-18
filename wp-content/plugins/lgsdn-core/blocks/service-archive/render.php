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
	<a class="lgsdn-playbook-article__back-link" href="<?php echo esc_url( home_url( '/library/' ) ); ?>">
		<svg class="lgsdn-playbook-article__back-arrow" aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m10 5-7 7 7 7M3 12h18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<span class="lgsdn-playbook-article__back-label">Library</span>
	</a>
</nav>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-service-archive lgsdn-playbook-article-shell' ) ); ?>>
	<div class="lgsdn-service-archive__header">
		<header class="lgsdn-playbook-article__title"><h1><?php echo esc_html( $term->name ); ?></h1></header>
		<div class="lgsdn-playbook-article__service-card lgsdn-service-archive__service-icon lgsdn-service-archive__service-icon--<?php echo esc_attr( $service_style['icon'] ); ?>" style="--lgsdn-service-card-bg:<?php echo esc_attr( $service_style['gradient'] ); ?>;--lgsdn-service-card-fg:#FFFFFF;" aria-hidden="true">
			<img class="lgsdn-playbook-article__service-icon lgsdn-playbook-article__service-icon--<?php echo esc_attr( $service_style['icon'] ); ?>" src="<?php echo esc_url( LGSDN_Service_Styles::icon_url( $service_style['icon'] ) ); ?>" alt="">
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
			<div class="lgsdn-playbook-contribute lgsdn-playbook-contribute--service-empty">
				<h3 class="lgsdn-playbook-contribute__prompt">Do you have a service design case study about <?php echo esc_html( strtolower( $term->name ) ); ?>?</h3>
				<a class="button button--strong button--large lgsdn-button--arrow" href="<?php echo esc_url( home_url( '/contribute/' ) ); ?>">
					<span class="lgsdn-button__label">Learn how to contribute</span>
				</a>
			</div>
		<?php endif; ?>
	</section>
</div>
<?php wp_reset_postdata(); ?>
