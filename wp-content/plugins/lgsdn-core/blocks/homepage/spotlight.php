<?php
/** Homepage spotlight: valid events in the next seven days, then join. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$now = current_datetime();
$today = $now->setTime( 0, 0 );
$until = $now->modify( '+7 days' );
$spotlight_events = array();
$candidates = get_posts( array(
	'post_type' => 'lgsdn_event',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'meta_key' => 'lgsdn_start_at',
	'meta_query' => array( array(
		'key' => 'lgsdn_start_at',
		'value' => array( $today->format( 'Y-m-d\TH:i' ), $until->format( 'Y-m-d\TH:i:s' ) ),
		'compare' => 'BETWEEN',
	) ),
	'orderby' => array( 'meta_value' => 'ASC', 'ID' => 'ASC' ),
) );
foreach ( $candidates as $candidate ) {
	$raw = (string) get_post_meta( $candidate->ID, 'lgsdn_start_at', true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/D', $raw ) ) {
		continue;
	}
	$normalized = strlen( $raw ) === 16 ? $raw . ':00' : $raw;
	$starts = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s', $normalized, wp_timezone() );
	if ( $starts && $starts->format( 'Y-m-d\TH:i:s' ) === $normalized && $starts >= $today && $starts <= $until ) {
		$spotlight_events[] = array( 'post' => $candidate, 'starts' => $starts );
	}
}
?>
<section class="homepage-participation" id="join" aria-label="Network highlights" <?php if ( $spotlight_events ) : ?>data-spotlight-carousel<?php endif; ?>>
	<div class="homepage-spotlight__viewport"><div class="homepage-spotlight__slides">
		<?php foreach ( $spotlight_events as $entry ) : ?>
			<?php
			$event_id = $entry['post']->ID;
			$starts = $entry['starts'];
			$days = (int) $now->setTime( 0, 0 )->diff( $starts->setTime( 0, 0 ) )->format( '%a' );
			$countdown = 0 === $days ? 'Today' : ( 1 === $days ? 'Tomorrow' : sprintf( 'In %d days', $days ) );
			$event_title = get_the_title( $event_id );
			$event_url = get_permalink( $event_id );
			// Aim for 180 characters, finishing the sentence; cap unusually long copy at 360.
			$summary_source = trim( (string) $entry['post']->post_excerpt ) ?: $entry['post']->post_content;
			$summary_source = preg_replace( '/<\/(?:p|h[1-6]|li|div)>|<br\s*\/?\s*>/i', ' ', $summary_source );
			$summary = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $summary_source ) ) ) );
			if ( preg_match_all( '/.*?[.!?](?:[”’"\'])?(?=\s|$)|.+$/us', $summary, $sentences ) ) {
				$summary = '';
				foreach ( $sentences[0] as $sentence ) {
					$summary = trim( $summary . ' ' . trim( $sentence ) );
					if ( mb_strlen( $summary ) >= 180 ) { break; }
				}
			}
			if ( mb_strlen( $summary ) > 360 ) {
				$summary = preg_replace( '/\s+\S*$/u', '', mb_substr( $summary, 0, 360 ) ) . '…';
			}
			$event_date = wp_date( 'D, jS M Y', $starts->getTimestamp(), wp_timezone() );
			$location = get_post_meta( $event_id, 'lgsdn_location', true );
			$mode = get_post_meta( $event_id, 'lgsdn_event_mode', true );
			$format_label = array( 'online' => 'Online', 'in-person' => 'In person', 'hybrid' => 'Hybrid' )[ $mode ] ?? '';
			$image = get_the_post_thumbnail( $event_id, 'large', array( 'class' => 'homepage-join__image', 'alt' => '', 'loading' => 'eager' ) );
			?>
			<article class="homepage-join homepage-spotlight__slide homepage-spotlight__event" id="homepage-spotlight-event-<?php echo esc_attr( (string) $event_id ); ?>" aria-label="<?php echo esc_attr( $event_title ); ?>">
				<div class="homepage-join__media<?php echo $image ? '' : ' homepage-spotlight__fallback'; ?>">
					<?php if ( $image ) : ?>
						<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<img src="<?php echo esc_url( $image_base . '/home-feature-join-contours.svg' ); ?>" alt="" class="homepage-spotlight__contour">
					<?php endif; ?>
					<?php if ( $format_label ) : ?><span class="lgsdn-events__format-tag"><?php echo esc_html( $format_label ); ?></span><?php endif; ?>
				</div>
				<div class="homepage-participation__copy">
					<span class="homepage-spotlight__eyebrow"><?php echo esc_html( $countdown ); ?></span>
					<h2 class="lgsdn-events__title"><a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( $event_title ); ?></a></h2>
					<div class="lgsdn-events__meta">
						<time class="lgsdn-events__date" datetime="<?php echo esc_attr( $starts->format( DATE_W3C ) ); ?>"><?php echo esc_html( $event_date ); ?></time>
						<?php if ( $location ) : ?><span class="lgsdn-events__location"><?php echo esc_html( $location ); ?></span><?php endif; ?>
					</div>
					<?php if ( $summary ) : ?><p class="homepage-spotlight__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
					<a class="button lgsdn-button--arrow lgsdn-events__link" href="<?php echo esc_url( $event_url ); ?>" aria-label="<?php echo esc_attr( 'Details: ' . $event_title . ', ' . $event_date ); ?>"><span class="lgsdn-button__label">Details</span></a>
				</div>
			</article>
		<?php endforeach; ?>
		<article class="homepage-join homepage-spotlight__slide" id="homepage-spotlight-join" aria-label="Join the network">
			<div class="homepage-join__media"><?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'homepage-join__image', 'loading' => $spotlight_events ? 'lazy' : 'eager' ) ); ?></div>
			<div class="homepage-participation__copy">
				<h2><?php echo esc_html( $features[0]['title'] ); ?></h2>
				<p><?php echo nl2br( esc_html( $features[0]['body'] ) ); ?></p>
				<a class="button lgsdn-button--arrow" href="<?php echo esc_url( $features[0]['url'] ); ?>"><span class="lgsdn-button__label">Join</span><span class="screen-reader-text"> the network</span></a>
			</div>
		</article>
	</div></div>
	<?php if ( $spotlight_events ) : ?>
		<div class="homepage-spotlight__controls" aria-label="Choose a highlight" hidden>
			<?php foreach ( $spotlight_events as $index => $entry ) : ?>
				<button type="button" class="lgsdn-horizontal-scroll-map__card" aria-controls="homepage-spotlight-event-<?php echo esc_attr( (string) $entry['post']->ID ); ?>" aria-label="<?php echo esc_attr( 'Show ' . get_the_title( $entry['post'] ) ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"></button>
			<?php endforeach; ?>
			<button type="button" class="lgsdn-horizontal-scroll-map__card" aria-controls="homepage-spotlight-join" aria-label="Show join the network" aria-pressed="false"></button>
		</div>
	<?php endif; ?>
</section>
