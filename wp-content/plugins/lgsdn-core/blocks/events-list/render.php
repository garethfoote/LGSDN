<?php
/**
 * Events list block.
 */

$now = current_time( 'Y-m-d\\TH:i' );
$upcoming = get_posts(
	array(
		'post_type' => 'lgsdn_event',
		'post_status' => 'publish',
		'posts_per_page' => 5,
		'meta_key' => 'lgsdn_start_at',
		'meta_value' => $now,
		'meta_compare' => '>=',
		'orderby' => 'meta_value',
		'order' => 'ASC',
	)
);
$past = get_posts(
	array(
		'post_type' => 'lgsdn_event',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_key' => 'lgsdn_start_at',
		'meta_value' => $now,
		'meta_compare' => '<',
		'orderby' => 'meta_value',
		'order' => 'DESC',
	)
);
$events = array_merge( $upcoming, $past );

if ( empty( $events ) ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'lgsdn-events events' ) ); ?>>
	<h2>Events</h2>
	<ul class="lgsdn-events__list">
		<?php foreach ( $events as $event ) : ?>
			<?php
			$starts = get_post_meta( $event->ID, 'lgsdn_start_at', true );
			$is_past = $starts && $starts < $now;
			$location = get_post_meta( $event->ID, 'lgsdn_location', true );
			$mode = get_post_meta( $event->ID, 'lgsdn_event_mode', true );
			$booking_url = get_post_meta( $event->ID, 'lgsdn_booking_url', true );
			?>
			<li class="lgsdn-events__item event-row<?php echo $is_past ? ' is-past event-row--past' : ''; ?>">
				<div class="event-row__left">
					<a class="event-row__title" href="<?php echo esc_url( get_permalink( $event ) ); ?>"><?php echo esc_html( get_the_title( $event ) ); ?></a>
					<?php if ( $location || $mode ) : ?>
						<span class="event-row__location"><?php echo esc_html( $location ?: ucfirst( $mode ) ); ?></span>
					<?php endif; ?>
				</div>
				<div class="event-row__date-group">
					<?php if ( $starts ) : ?>
						<time class="event-row__date" datetime="<?php echo esc_attr( $starts ); ?>"><?php echo esc_html( wp_date( 'j M Y', strtotime( $starts ) ) ); ?></time>
					<?php endif; ?>
					<?php if ( $is_past ) : ?><span class="tag tag--past">Past</span><?php endif; ?>
					<?php if ( $booking_url && ! $is_past ) : ?><a class="button event-row__book" href="<?php echo esc_url( $booking_url ); ?>">Book</a><?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
