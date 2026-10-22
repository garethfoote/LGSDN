<?php
/** One-click repair for duplicate single-value Playbook metadata rows. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Playbook_Meta_Repair {
	private const PAGE = 'lgsdn-playbook-meta-repair';
	private const META_KEYS = array(
		'lgsdn_contributor_id',
		'lgsdn_primary_service_id',
		'lgsdn_primary_practice_id',
		'lgsdn_reviewed_on',
		'lgsdn_playbook_resources',
		'lgsdn_featured_home',
		'lgsdn_card_image_id',
		'lgsdn_card_image_window',
		'lgsdn_article_image_id',
	);

	public static function hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_tools_page' ) );
		add_action( 'admin_post_lgsdn_repair_playbook_meta', array( __CLASS__, 'repair' ) );
	}

	public static function add_tools_page(): void {
		add_management_page(
			'Repair Playbook metadata',
			'Repair Playbook metadata',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to repair Playbook metadata.', 'lgsdn' ) );
		}

		$removed = isset( $_GET['removed'] ) ? absint( $_GET['removed'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$failed = isset( $_GET['failed'] ) ? absint( $_GET['failed'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1>Repair Playbook metadata</h1>
			<p>This checks Playbook items for duplicate rows in single-value fields registered with WordPress, including LGSDN fields and core fields such as footnotes. When duplicates are found, it keeps the oldest row (the value WordPress reads) and removes the extra rows.</p>
			<?php if ( null !== $removed ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( sprintf( 'Removed %1$d duplicate metadata row(s). %2$d row(s) could not be removed.', $removed, $failed ?? 0 ) ); ?></p></div>
			<?php endif; ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="lgsdn_repair_playbook_meta">
				<?php wp_nonce_field( 'lgsdn_repair_playbook_meta' ); ?>
				<?php submit_button( 'Check and repair duplicate metadata' ); ?>
			</form>
		</div>
		<?php
	}

	public static function repair(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to repair Playbook metadata.', 'lgsdn' ) );
		}
		check_admin_referer( 'lgsdn_repair_playbook_meta' );

		$post_ids = get_posts(
			array(
				'post_type' => 'lgsdn_playbook',
				'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'numberposts' => -1,
				'fields' => 'ids',
				'orderby' => 'ID',
				'order' => 'ASC',
			)
		);
		$removed = 0;
		$failed = 0;
		$meta_keys = self::META_KEYS;
		$registered_meta = array_replace(
			get_registered_meta_keys( 'post' ),
			get_registered_meta_keys( 'post', 'lgsdn_playbook' )
		);
		foreach ( $registered_meta as $meta_key => $args ) {
			if ( ! empty( $args['single'] ) ) {
				$meta_keys[] = $meta_key;
			}
		}
		$meta_keys = array_values( array_unique( $meta_keys ) );

		global $wpdb;
		foreach ( $post_ids as $post_id ) {
			foreach ( $meta_keys as $meta_key ) {
				$meta_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC",
						$post_id,
						$meta_key
					)
				);
				foreach ( array_slice( $meta_ids, 1 ) as $meta_id ) {
					if ( delete_metadata_by_mid( 'post', (int) $meta_id ) ) {
						++$removed;
					} else {
						++$failed;
					}
				}
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::PAGE, 'removed' => $removed, 'failed' => $failed ),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}
}
