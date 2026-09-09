<?php
/**
 * Deterministic renderer checks without WordPress or database writes.
 * Run: php wp-content/plugins/lgsdn-core/tests/events-timeline.php
 */

date_default_timezone_set( 'UTC' );
$fixtures = array();
$clock = '2026-10-20T12:00:00';
$queries = array();
$checks = 0;

function wp_timezone(): DateTimeZone {
	return new DateTimeZone( 'Europe/London' );
}

function current_datetime(): DateTimeImmutable {
	return new DateTimeImmutable( $GLOBALS['clock'], wp_timezone() );
}

function wp_date( string $format, int $timestamp, DateTimeZone $timezone ): string {
	return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format );
}

function get_posts( array $args ): array {
	$GLOBALS['queries'][] = $args;
	$rows = array_filter(
		$GLOBALS['fixtures'],
		static function ( object $event ) use ( $args ): bool {
			$date = $event->meta['lgsdn_start_at'] ?? '';
			return 'publish' === $event->post_status && ( '>=' === $args['meta_compare'] ? $date >= $args['meta_value'] : $date <= $args['meta_value'] );
		}
	);
	usort( $rows, static function ( object $a, object $b ) use ( $args ): int {
		$result = strcmp( $a->meta['lgsdn_start_at'] ?? '', $b->meta['lgsdn_start_at'] ?? '' );
		return ( 'DESC' === $args['orderby']['meta_value'] ? -$result : $result ) ?: $a->ID <=> $b->ID;
	} );
	return array_slice( $rows, ( $args['paged'] - 1 ) * $args['posts_per_page'], $args['posts_per_page'] );
}

function get_post_meta( int $id, string $key, bool $single ): string {
	return $GLOBALS['fixtures'][ $id ]->meta[ $key ] ?? '';
}

function get_permalink( object $event ): string {
	return '/events/event-' . $event->ID . '/';
}

function get_the_title( object $event ): string {
	return $event->post_title;
}

function get_the_post_thumbnail( int $id, string $size, array $attributes ): string {
	return $GLOBALS['fixtures'][ $id ]->image ?? '';
}

function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( string $value ): string {
	return esc_html( $value );
}

function esc_url( string $value ): string {
	return str_starts_with( $value, 'javascript:' ) ? '' : esc_attr( $value );
}

function get_block_wrapper_attributes( array $attributes ): string {
	return 'class="' . esc_attr( $attributes['class'] ) . '"';
}

function fixture( int $id, string $date, array $meta = array(), string $status = 'publish', string $title = '' ): void {
	$GLOBALS['fixtures'][ $id ] = (object) array(
		'ID' => $id,
		'post_status' => $status,
		'post_title' => $title ?: 'Event ' . $id,
		'meta' => array_merge( array( 'lgsdn_start_at' => $date ), $meta ),
	);
}

function render_timeline( bool $heading = true, bool $show_all = false ): string {
	$attributes = array( 'showHeading' => $heading, 'showAll' => $show_all );
	ob_start();
	include __DIR__ . '/../blocks/events-list/render.php';
	return ob_get_clean();
}

function check( bool $condition, string $message ): void {
	++$GLOBALS['checks'];
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function event_ids( string $html ): array {
	preg_match_all( '~class="lgsdn-events__title"><a href="/events/event-(\d+)/"~', $html, $matches );
	return array_map( 'intval', $matches[1] );
}

check( '' === render_timeline(), 'An empty list hides the section.' );
foreach ( array( '', 'invalid', '2026-10-32T12:00', '2026-11-01T25:00', '2026-02-29T12:00', '2026-03-29T01:30' ) as $id => $date ) {
	fixture( $id, $date );
}
check( '' === render_timeline(), 'Missing, malformed, impossible and skipped DST dates are excluded.' );

$fixtures = array();
for ( $id = 1; $id <= 7; ++$id ) {
	fixture( $id, '2026-10-' . ( 20 + $id ) . 'T12:00' );
}
fixture( 8, '2026-10-19T12:00', array( 'lgsdn_booking_url' => 'https://example.org/old-booking' ) );
fixture( 9, '2026-10-18T12:00' );
fixture( 10, '2026-10-20T13:00', array(), 'draft' );
$html = render_timeline();
check( array( 5, 4, 3, 2, 1, 8 ) === event_ids( $html ), 'Select the next five, display descending, then only the most recent past.' );
check( 1 === substr_count( $html, 'class="lgsdn-events__month-name"' ), 'Repeated month labels remain suppressed across the past boundary.' );
check( 1 === substr_count( $html, 'lgsdn-events__item--past' ), 'Only the past row gets the dashed-rail modifier.' );
check( ! str_contains( $html, 'old-booking' ), 'Past actions never link to booking.' );
check( str_contains( $html, '2026-10-25T12:00:00+00:00' ) && str_contains( $html, '2026-10-24T12:00:00+01:00' ), 'Datetime attributes use the correct side of the London DST transition.' );
check( str_contains( $html, 'Sun, 25th Oct 2026' ), 'The weekday is generated from the stored date.' );
$html = render_timeline( false, true );
check( array( 7, 6, 5, 4, 3, 2, 1, 8, 9 ) === event_ids( $html ), 'The archive includes every upcoming and past event in descending order.' );
check( str_contains( $html, 'class="lgsdn-events lgsdn-events--all"' ), 'The complete timeline exposes its roomier archive presentation.' );

$fixtures = array();
$queries = array();
for ( $id = 1; $id <= 25; ++$id ) {
	fixture( $id, '2026-09-' . str_pad( (string) $id, 2, '0', STR_PAD_LEFT ) . 'T12:00' );
}
$ids = event_ids( render_timeline( false, true ) );
check( 25 === count( $ids ) && 25 === $ids[0] && 1 === $ids[24], 'The archive retains every past event across query batches.' );
check( in_array( 2, array_column( $queries, 'paged' ), true ), 'The complete past-event query continues beyond its first batch.' );

$fixtures = array();
fixture( 1, '2026-12-18T10:00', array( 'lgsdn_booking_url' => 'https://example.org/register', 'lgsdn_event_mode' => 'online' ), 'publish', 'Meetup <script>alert(1)</script>' );
fixture( 2, '2027-01-04T10:00', array( 'lgsdn_event_mode' => 'in-person' ) );
fixture( 3, '2027-10-04T10:00', array( 'lgsdn_event_mode' => 'hybrid', 'lgsdn_location' => 'London' ) );
fixture( 4, '2026-10-30T10:00', array( 'lgsdn_event_mode' => 'hybrid' ) );
$html = render_timeline( false );
check( ! str_contains( $html, 'lgsdn-events__heading' ) && str_contains( $html, '<h2 class="lgsdn-events__title"' ), 'Heading visibility and event heading levels respect showHeading.' );
check( 4 === substr_count( $html, 'class="lgsdn-events__month-name"' ), 'The same month in different years gets separate labels.' );
check( str_contains( $html, 'class="button lgsdn-button--external lgsdn-events__link" href="https://example.org/register"' ) && str_contains( $html, '>Register</span>' ), 'Upcoming booking actions use their external URL, label and icon modifier.' );
check( str_contains( $html, 'aria-label="Details: Event 2, Mon, 4th Jan 2027"' ), 'Fallback actions have an event-specific accessible name.' );
check( str_contains( $html, '>Online</span>' ) && str_contains( $html, '>In person</span>' ) && str_contains( $html, '>Hybrid</span>' ), 'All format fallbacks use readable labels.' );
check( str_contains( $html, '>London</span>' ), 'Explicit location takes priority over format.' );
check( ! str_contains( $html, '<script>' ) && str_contains( $html, '&lt;script&gt;' ), 'Titles and accessible labels are escaped.' );
check( ! str_contains( $html, 'lgsdn-events__item--past' ), 'Upcoming-only lists have no past rail.' );
check( ! str_contains( $html, 'lgsdn-events__image' ) && ! str_contains( $html, 'lgsdn-events__body--with-image' ), 'Events without photos use the full content width without a placeholder.' );
$fixtures[1]->image = '<img src="workshop.jpg" alt="" width="150" height="150">';
$html = render_timeline();
check( 1 === substr_count( $html, 'lgsdn-events__body--with-image' ) && str_contains( $html, 'src="workshop.jpg"' ), 'Only events with photos use the image layout.' );
check( 1 === substr_count( $html, 'class="lgsdn-events__format-tag"' ) && str_contains( $html, '<span class="lgsdn-events__format-tag">Online</span>' ), 'Only pictured events expose their readable format as an image tag.' );
check( str_contains( $html, 'class="button lgsdn-button--arrow lgsdn-events__link"' ) && str_contains( $html, 'class="button lgsdn-button--external lgsdn-events__link"' ) && ! str_contains( $html, 'wp-block-button' ), 'Internal and external event actions reuse the appropriate shared LGSDN button variant.' );

$fixtures = array();
fixture( 1, '2026-10-20T12:00' );
fixture( 2, '2026-10-20T11:59:59' );
$html = render_timeline();
check( array( 1, 2 ) === event_ids( $html ) && 1 === substr_count( $html, 'lgsdn-events__item--past' ), 'An event starting exactly now is upcoming.' );
$clock = '2026-10-20T12:00:01';
$html = render_timeline();
check( array( 1 ) === event_ids( $html ) && 1 === substr_count( $html, 'lgsdn-events__item--past' ), 'An event becomes past one second after its start; past-only lists render.' );
check( strpos( $html, 'class="lgsdn-events__badge"' ) < strpos( $html, 'class="lgsdn-events__date"' ), 'The Past badge appears before the date in reading order.' );

$fixtures = array();
$queries = array();
for ( $id = 1; $id <= 21; ++$id ) {
	fixture( $id, '2026-10-21T25:00' );
}
fixture( 22, '2026-10-22T12:00' );
check( array( 22 ) === event_ids( render_timeline() ), 'Invalid dates filling a query batch cannot displace the next valid event.' );
check( in_array( 2, array_column( $queries, 'paged' ), true ), 'Selection continues beyond the first query batch.' );
echo 'Passed ' . $checks . " events timeline checks.\n";
