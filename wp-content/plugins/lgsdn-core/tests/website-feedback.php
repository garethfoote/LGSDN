<?php
/** Run with wp eval-file; exercises installed pages, form settings and privacy enforcement. */
$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo 'PASS: ' . $message . PHP_EOL;
};
foreach ( array( 'feedback', 'about-this-website', 'privacy' ) as $slug ) {
	$page = get_page_by_path( $slug );
	$check( $page instanceof WP_Post && 'publish' === $page->post_status, $slug . ' exists' );
	$id = $page->ID;
	$content = $page->post_content;
	LGSDN_Website_Pages::install();
	$check( $id === get_page_by_path( $slug )->ID, $slug . ' is not duplicated on setup' );
	$check( $content === get_post( $id )->post_content, $slug . ' keeps its existing editable content on setup' );
}
$form_id = absint( get_option( 'lgsdn_feedback_form_id' ) );
$meta = get_post_meta( $form_id, 'forminator_form_meta', true );
$check( ! empty( $form_id ) && empty( $meta['settings']['store_submissions'] ), 'Feedback is not stored as WordPress submissions' );
$fields = array_column( $meta['fields'], null, 'id' );
$check( empty( $fields['email-1']['required'] ) && ! empty( $fields['textarea-1']['required'] ), 'Email is optional and feedback is required' );
$check( ! empty( $meta['notifications'][0]['recipients'] ), 'Feedback notification has a recipient' );
$check( ! empty( $meta['behaviors'][0]['thankyou-message'] ), 'Successful feedback has a visible confirmation' );
$check( 'daily' === get_option( 'iawp_visitor_salt_refresh_interval' ), 'Visitor salt rotates daily' );
$check( 'database' === get_option( 'iawp_geo_provider' ), 'Geolocation uses the local database' );
$check( ! get_option( 'iawp_track_authenticated_users' ), 'Logged-in visitors are excluded' );
$request = new WP_REST_Request( 'POST', '/iawp/search' );
$old_cookies = $_COOKIE;
$old_server = $_SERVER;
try {
	unset( $_COOKIE['lgsdn_analytics_opt_out'], $_SERVER['HTTP_DNT'], $_SERVER['HTTP_SEC_GPC'] );
	$check( null === LGSDN_Website_Pages::respect_analytics_choice( null, null, $request ), 'Ordinary collection is unaffected' );
	$_COOKIE['lgsdn_analytics_opt_out'] = '1';
	$check( 204 === LGSDN_Website_Pages::respect_analytics_choice( null, null, $request )->get_status(), 'Opt-out blocks collection from cached pages too' );
	unset( $_COOKIE['lgsdn_analytics_opt_out'] );
	foreach ( array( 'HTTP_DNT', 'HTTP_SEC_GPC' ) as $header ) {
		$_SERVER[$header] = '1';
		$check( 204 === LGSDN_Website_Pages::respect_analytics_choice( null, null, $request )->get_status(), $header . ' blocks collection' );
		unset( $_SERVER[$header] );
	}
	$_COOKIE['lgsdn_analytics_opt_out'] = '1';
	$check( null === LGSDN_Website_Pages::respect_analytics_choice( null, null, new WP_REST_Request( 'POST', '/other/route' ) ), 'Unrelated REST routes are unaffected' );
} finally {
	$_COOKIE = $old_cookies;
	$_SERVER = $old_server;
}
