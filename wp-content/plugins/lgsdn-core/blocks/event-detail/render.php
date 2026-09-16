<?php
/**
 * Structured event detail layout.
 */

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_queried_object_id();

if ( ! $post_id || 'lgsdn_event' !== get_post_type( $post_id ) ) {
	return;
}

$timezone = wp_timezone();
$parse_event_date = static function ( mixed $raw ) use ( $timezone ): ?DateTimeImmutable {
	if ( ! is_string( $raw ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/D', $raw ) ) {
		return null;
	}

	$normalized = 16 === strlen( $raw ) ? $raw . ':00' : $raw;
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s', $normalized, $timezone );

	return $date && $date->format( 'Y-m-d\TH:i:s' ) === $normalized ? $date : null;
};

$starts = $parse_event_date( get_post_meta( $post_id, 'lgsdn_start_at', true ) );
$ends = $parse_event_date( get_post_meta( $post_id, 'lgsdn_end_at', true ) );
$ends = $starts && $ends && $ends > $starts ? $ends : null;
$location = trim( (string) get_post_meta( $post_id, 'lgsdn_location', true ) );
$map_url = esc_url( get_post_meta( $post_id, 'lgsdn_map_url', true ) );
$mode = (string) get_post_meta( $post_id, 'lgsdn_event_mode', true );
$booking_url = esc_url( get_post_meta( $post_id, 'lgsdn_booking_url', true ) );
$booking_label = trim( (string) get_post_meta( $post_id, 'lgsdn_booking_label', true ) );
$booking_label = $booking_label ?: 'Register';
$resources = LGSDN_Fields::sanitize_resources( get_post_meta( $post_id, 'lgsdn_event_resources', true ) );
$format_labels = array( 'online' => 'Online', 'in-person' => 'In person', 'hybrid' => 'Hybrid' );
$format_label = $format_labels[ $mode ] ?? '';
$is_past = $starts && $starts < current_datetime();
$has_featured_image = has_post_thumbnail( $post_id );
$content = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
$title = get_the_title( $post_id );
$title_id = 'lgsdn-event-title-' . $post_id;
$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
$is_external_url = static function ( string $url ) use ( $site_host ): bool {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	return '' !== $host && '' !== $site_host && $host !== $site_host;
};

$date_label = '';
$time_label = '';
if ( $starts ) {
	$date_label = wp_date( 'D jS F, Y', $starts->getTimestamp(), $timezone );
	$time_label = wp_date( 'H:i', $starts->getTimestamp(), $timezone );

	if ( $ends ) {
		if ( $ends->format( 'Y-m-d' ) === $starts->format( 'Y-m-d' ) ) {
			$time_label .= '–' . wp_date( 'H:i', $ends->getTimestamp(), $timezone );
		} else {
			$date_label .= ' – ' . wp_date( 'D jS F, Y', $ends->getTimestamp(), $timezone );
			$time_label .= ' – ' . wp_date( 'H:i', $ends->getTimestamp(), $timezone );
		}
	}
}
?>
<nav class="lgsdn-playbook-article__back-nav lgsdn-playbook-article-shell" aria-label="Back navigation">
	<a class="lgsdn-playbook-article__back-link" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
		<span class="lgsdn-playbook-article__back-arrow" aria-hidden="true">&larr;</span>
		<span class="lgsdn-playbook-article__back-label">Events</span>
	</a>
</nav>
<article <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-event lgsdn-playbook-article-shell' ) ); ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<header class="lgsdn-event__hero<?php echo $has_featured_image ? ' has-featured-image' : ' no-featured-image'; ?>">
		<div class="lgsdn-event__intro">
			<div class="lgsdn-event__heading">
				<?php if ( $is_past ) : ?><span class="lgsdn-event__past">Past</span><?php endif; ?>
				<h1 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $title ); ?></h1>
			</div>

			<?php if ( $starts || $location || $format_label ) : ?>
				<dl class="lgsdn-event__metadata">
					<?php if ( $starts ) : ?>
						<div class="lgsdn-event__metadata-item lgsdn-event__metadata-item--date">
							<dt class="screen-reader-text">Date and time</dt>
							<dd>
								<time datetime="<?php echo esc_attr( $starts->format( DATE_W3C ) ); ?>"><?php echo esc_html( $date_label ); ?></time>
								<span><?php echo esc_html( $time_label ); ?></span>
							</dd>
						</div>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<div class="lgsdn-event__metadata-item lgsdn-event__metadata-item--location">
							<dt>Location</dt>
							<dd>
								<span><?php echo esc_html( $location ); ?></span>
								<?php if ( $map_url ) : ?>
									<a class="lgsdn-event__map-link" href="<?php echo esc_url( $map_url ); ?>">View on Google Maps</a>
								<?php endif; ?>
							</dd>
						</div>
					<?php endif; ?>
					<?php if ( $format_label ) : ?>
						<div class="lgsdn-event__metadata-item lgsdn-event__metadata-item--format">
							<dt>Format</dt>
							<dd><?php echo esc_html( $format_label ); ?></dd>
						</div>
					<?php endif; ?>
				</dl>
			<?php endif; ?>

			<?php if ( $booking_url && ! $is_past ) : ?>
				<a class="button button--strong button--large lgsdn-button--external lgsdn-event__register" href="<?php echo esc_url( $booking_url ); ?>" aria-label="<?php echo esc_attr( $booking_label . ': ' . $title ); ?>">
					<span class="lgsdn-button__label"><?php echo esc_html( $booking_label ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $has_featured_image ) : ?>
			<figure class="lgsdn-event__featured-image">
				<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'alt' => '', 'loading' => 'eager', 'decoding' => 'async', 'sizes' => '(min-width: 64rem) 31rem, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $format_label ) : ?><span class="lgsdn-event__format-tag"><?php echo esc_html( $format_label ); ?></span><?php endif; ?>
			</figure>
		<?php endif; ?>
	</header>

	<?php if ( $resources || trim( wp_strip_all_tags( $content ) ) ) : ?>
		<div class="lgsdn-event__body lgsdn-playbook-article__copy">
			<?php if ( $resources ) : ?>
				<aside class="lgsdn-event__resources" aria-labelledby="lgsdn-event-resources-title-<?php echo esc_attr( (string) $post_id ); ?>">
					<h2 id="lgsdn-event-resources-title-<?php echo esc_attr( (string) $post_id ); ?>">Resources</h2>
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
			<?php endif; ?>
			<?php if ( trim( wp_strip_all_tags( $content ) ) ) : ?>
				<div class="lgsdn-article">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</article>
