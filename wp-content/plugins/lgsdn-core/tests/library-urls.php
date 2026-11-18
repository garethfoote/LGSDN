<?php
/** Run via wp eval-file; checks migration and redirect targets. */
$assert = static function ( bool $ok, string $message ): void {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	echo 'PASS: ' . $message . PHP_EOL;
};
$page = get_page_by_path( 'playbook' ) ?: get_page_by_path( 'library' );
$id = $page->ID;
$items = get_posts( array( 'post_type' => 'lgsdn_playbook', 'post_status' => 'publish', 'numberposts' => -1 ) );
$before = array();
foreach ( $items as $item ) { $before[$item->ID] = array( $item->post_name, get_post_meta( $item->ID ) ); }
LGSDN_Library_URLs::migrate();
$assert( get_page_by_path( 'library' )->ID === $id && ! get_page_by_path( 'playbook' ), 'Listing is moved in place without a duplicate' );
foreach ( $before as $item_id => $snapshot ) {
	$assert( $snapshot === array( get_post( $item_id )->post_name, get_post_meta( $item_id ) ), 'Case study slug and metadata preserved: ' . $item_id );
	$assert( str_contains( get_permalink( $item_id ), '/library/' ), 'Case study has a Library permalink: ' . $item_id );
}
$assert( home_url( '/library/' ) === LGSDN_Library_URLs::target( '/playbook/' ), 'Old listing redirects to Library' );
$assert( home_url( '/library/example/?service=housing&sort=recent' ) === LGSDN_Library_URLs::target( '/playbook/example/?service=housing&sort=recent' ), 'Redirect retains article suffix and query string' );
$assert( null === LGSDN_Library_URLs::target( '/library/' ) && null === LGSDN_Library_URLs::target( '/playbook-other/' ), 'New and unrelated paths do not redirect' );
$assert( '/library/?service=housing#case-study-panel' === LGSDN_Library_URLs::links( '/playbook/?service=housing#case-study-panel' ), 'Internal link retains filters and anchors' );
$external = 'https://example.org/playbook/example/';
$assert( $external === LGSDN_Library_URLs::links( $external ), 'External sites are not rewritten' );
$assert( home_url( '/library/example/' ) === LGSDN_Library_URLs::links( home_url( '/playbook/example/' ) ), 'Absolute internal links are rewritten' );
$assert( home_url( '/library/?next=/playbook/' ) === LGSDN_Library_URLs::links( home_url( '/playbook/?next=/playbook/' ) ), 'Absolute URL query values are preserved' );
$assert( '/library/?next=/playbook/#/playbook/' === LGSDN_Library_URLs::links( '/playbook/?next=/playbook/#/playbook/' ), 'Relative URL query values and fragments are preserved' );
foreach ( array( '//example.org/playbook/example/', 'mailto:hello@example.org/playbook/example/', '/other/playbook/example/' ) as $untouched ) {
	$assert( $untouched === LGSDN_Library_URLs::links( $untouched ), 'Unrelated URL is preserved: ' . $untouched );
}
$scheme_relative = preg_replace( '~^https?:~', '', home_url( '/playbook/example/' ) );
$assert( preg_replace( '~^https?:~', '', home_url( '/library/example/' ) ) === LGSDN_Library_URLs::links( $scheme_relative ), 'Scheme-relative internal URL is rewritten' );
LGSDN_Library_URLs::migrate();
$assert( get_page_by_path( 'library' )->ID === $id, 'Repeated setup retains the same page' );
