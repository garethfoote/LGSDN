<?php
/** Verify the editorial rename never changes storage identifiers or permalinks. */
$assert = static function ( bool $ok, string $message ): void {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	echo 'PASS: ' . $message . PHP_EOL;
};
$type = get_post_type_object( 'lgsdn_playbook' );
$assert( $type && 'Case studies' === $type->labels->name && 'Case study' === $type->labels->singular_name, 'Case study labels use the existing post type' );
$assert( 'library' === $type->rewrite['slug'], 'Case studies use the Library URL prefix' );
$page = get_page_by_path( 'library' );
$assert( $page && 'Library of Practice' === $page->post_title, 'Library page uses its agreed title' );
$items = get_posts( array( 'post_type' => 'lgsdn_playbook', 'post_status' => 'publish', 'numberposts' => -1 ) );
$assert( count( $items ) > 0, 'Existing case studies remain available' );
$before = array();
foreach ( array_merge( array( $page ), $items ) as $post ) {
	$before[$post->ID] = array( $post->post_content, get_permalink( $post->ID ), get_post_meta( $post->ID ) );
}
LGSDN_Library_Naming::migrate();
foreach ( $before as $id => $snapshot ) {
	$assert( $snapshot === array( get_post( $id )->post_content, get_permalink( $id ), get_post_meta( $id ) ), 'Repeated setup preserves content, URL and metadata for ' . $id );
}
