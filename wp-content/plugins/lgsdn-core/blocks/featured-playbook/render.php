<?php
/**
 * Featured Playbook item block.
 *
 * @var array $attributes Block attributes.
 */

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

if ( ! $featured->have_posts() ) {
	return;
}

$featured->the_post();
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
$image_base = get_theme_file_uri( 'assets/images' );
$taxonomy_icon_urls = array(
	'lgsdn_service' => $image_base . '/taxonomy-service.svg',
	'lgsdn_challenge' => $image_base . '/taxonomy-challenge.svg',
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

$classes = 'lgsdn-featured-playbook alignfull has-practice-' . $style['colour'];
?>
<article <?php echo get_block_wrapper_attributes( array( 'class' => $classes ) ); ?>>
	<div class="lgsdn-featured-playbook__media">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'large' ); ?>
		<?php endif; ?>
		<?php if ( $primary || $secondary_labels ) : ?>
			<div class="lgsdn-featured-playbook__tags" aria-label="Case study classifications">
				<?php if ( $primary ) : ?>
					<span class="lgsdn-playbook-tag lgsdn-playbook-tag--practice"><?php echo esc_html( $primary->name ); ?></span>
				<?php endif; ?>
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
	</div>
	<div class="lgsdn-featured-playbook__content">
		<p class="lgsdn-featured-playbook__eyebrow">From the playbook</p>
		<h2><?php the_title(); ?></h2>
		<?php if ( has_excerpt() ) : ?>
			<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<div class="wp-block-buttons">
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button lgsdn-button--arrow" href="<?php the_permalink(); ?>"><span class="lgsdn-button__label">Read the case study</span></a></div>
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button lgsdn-button--arrow" href="<?php echo esc_url( home_url( '/playbook/' ) ); ?>"><span class="lgsdn-button__label">See the full playbook</span></a></div>
		</div>
	</div>
</article>
<?php
wp_reset_postdata();
