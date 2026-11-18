<?php
/** Run with wp eval-file; no database content is changed. */
$sample = get_posts( array( 'post_type' => 'lgsdn_event', 'posts_per_page' => 1 ) )[0] ?? null;
if ( ! $sample ) { throw new RuntimeException( 'An event fixture is required.' ); }
$extra = get_posts( array( 'post_type' => 'lgsdn_event', 'posts_per_page' => 1, 'post__not_in' => array( $sample->ID ) ) )[0] ?? null;
$fixtures = array( $sample );
$extra_dates = array();
$raw = '';
$has_candidate = true;
$without_image = false;
$query_filter = static function ( $posts, $query ) use ( &$has_candidate, &$fixtures ) {
 return 'lgsdn_event' === $query->get( 'post_type' ) && 'lgsdn_start_at' === $query->get( 'meta_key' ) ? ( $has_candidate ?  $fixtures : array() ) : $posts;
};
$meta_filter = static function ( $value, $id, $key ) use ( &$raw, &$without_image, &$extra_dates, $sample ) {
 if ( 'lgsdn_start_at' === $key && isset( $extra_dates[ $id ] ) ) { return $extra_dates[ $id ]; }
 if ( $id !== $sample->ID ) { return $value; }
 if ( 'lgsdn_start_at' === $key ) { return $raw; }
 if ( '_thumbnail_id' === $key && $without_image ) { return 0; }
 return $value;
};
add_filter( 'posts_pre_query', $query_filter, 10, 2 );
add_filter( 'get_post_metadata', $meta_filter, 10, 3 );
try {
 $cases = array(
  'no event' => array( false, '+1 day', false ),
  'yesterday at midnight' => array( true, 'yesterday 00:00:00', false ),
  'yesterday just before midnight' => array( true, 'yesterday 23:59:59', false ),
  'today at midnight' => array( true, 'today 00:00:00', true ),
  'today in the evening' => array( true, 'today 18:00:00', true ),
  'within week' => array( true, '+6 days', true ),
  'outside week' => array( true, '+8 days', false ),
  'invalid date' => array( true, '2026-02-30T10:00', false ),
 );
 foreach ( $cases as $name => [ $has_candidate, $when, $expected ] ) {
  $raw = str_starts_with( $when, '2026' ) ? $when : current_datetime()->modify( $when )->format( 'Y-m-d\TH:i:s' );
  $post_id = absint( get_option( 'page_on_front' ) );
  $image_base = get_theme_file_uri( 'assets/images' );
  $features = array( array( 'title' => 'Join the network', 'body' => 'Join copy', 'url' => home_url( '/join/' ) ) );
  ob_start(); require dirname( __DIR__ ) . '/blocks/homepage/spotlight.php'; $html = ob_get_clean();
  if ( str_contains( $html, 'data-spotlight-carousel' ) !== $expected ) { throw new RuntimeException( $name ); }
  if ( str_starts_with( $when, 'today' ) && ! str_contains( $html, '>Today</span>' ) ) { throw new RuntimeException( 'Missing Today label: ' . $name ); }
  if ( ! $expected && str_contains( $html, 'homepage-spotlight__controls' ) ) { throw new RuntimeException( 'Unexpected dots' ); }
  echo $name . ": passed\n";
 }
 $raw = current_datetime()->modify( '+1 day' )->format( 'Y-m-d\TH:i:s' );
 $without_image = true;
 ob_start(); require dirname( __DIR__ ) . '/blocks/homepage/spotlight.php'; $html = ob_get_clean();
 if ( ! str_contains( $html, 'homepage-spotlight__fallback' ) || ! str_contains( $html, 'home-feature-join-contours.svg' ) ) { throw new RuntimeException( 'Missing image fallback' ); }
 echo "image fallback: passed\n";
 foreach ( array( '+2 days' => 'In 2 days', '+1 day' => 'Tomorrow', '+5 minutes' => 'Today' ) as $offset => $label ) {
  $raw = current_datetime()->modify( $offset )->format( 'Y-m-d\TH:i:s' );
  ob_start(); require dirname( __DIR__ ) . '/blocks/homepage/spotlight.php'; $html = ob_get_clean();
  if ( ! str_contains( $html, '>' . $label . '</span>' ) ) { throw new RuntimeException( 'Countdown: ' . $label ); }
 }
 echo "calendar countdowns: passed\n";
 if ( ! $extra ) { throw new RuntimeException( 'A second event fixture is required.' ); }
 $fixtures = array( $sample, $extra );
 $raw = current_datetime()->modify( '+1 day' )->format( 'Y-m-d\TH:i:s' );
 $extra_dates[ $extra->ID ] = current_datetime()->modify( '+2 days' )->format( 'Y-m-d\TH:i:s' );
 ob_start(); require dirname( __DIR__ ) . '/blocks/homepage/spotlight.php'; $html = ob_get_clean();
 if ( 3 !== substr_count( $html, 'class="lgsdn-horizontal-scroll-map__card"' ) ) { throw new RuntimeException( 'Multiple event dots' ); }
 $first = strpos( $html, 'id="homepage-spotlight-event-' . $sample->ID . '"' );
 $second = strpos( $html, 'id="homepage-spotlight-event-' . $extra->ID . '"' );
 $join = strpos( $html, 'id="homepage-spotlight-join"' );
 if ( false === $first || false === $second || ! ( $first < $second && $second < $join ) ) { throw new RuntimeException( 'Slide order' ); }
 echo "multiple events followed by join: passed\n";

} finally {
 remove_filter( 'posts_pre_query', $query_filter, 10 );
 remove_filter( 'get_post_metadata', $meta_filter, 10 );
}
