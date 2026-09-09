<?php
/**
 * Integration checks for the Events admin list.
 */

define( 'WP_ADMIN', true );
require dirname( __DIR__, 4 ) . '/wp-load.php';

$failures = array();
$check = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$columns = apply_filters(
	'manage_lgsdn_event_posts_columns',
	array( 'cb' => 'Checkbox', 'title' => 'Title', 'date' => 'Date' )
);
$check(
	array( 'cb', 'title', 'lgsdn_start_at', 'date' ) === array_keys( $columns ),
	'The Starts column should appear immediately before Date.'
);

$sortable = apply_filters( 'manage_edit-lgsdn_event_sortable_columns', array() );
$check(
	isset( $sortable['lgsdn_start_at'] ) && 'lgsdn_start_at' === $sortable['lgsdn_start_at'],
	'The Starts column should be sortable.'
);

$query = new WP_Query();
$GLOBALS['wp_the_query'] = $query;
$query->query(
	array(
		'post_type' => 'lgsdn_event',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'orderby' => 'lgsdn_start_at',
		'order' => 'ASC',
	)
);
$check( false !== strpos( $query->request, "meta_key = 'lgsdn_start_at'" ), 'The sorted query should join the start-date metadata.' );
$check( false !== strpos( $query->request, 'meta_value ASC' ), 'The sorted query should order start dates ascending.' );

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Events admin-list checks passed.\n";
