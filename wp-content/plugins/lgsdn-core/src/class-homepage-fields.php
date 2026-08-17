<?php
/**
 * Structured fields for the static front page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Homepage_Fields {
	private const NONCE_ACTION = 'lgsdn_save_homepage_fields';
	private const NONCE_NAME = 'lgsdn_homepage_fields_nonce';

	private const DEFAULTS = array(
		'lgsdn_home_lead' => 'A peer network for people improving public services across UK councils. Real examples, real conditions, real practice.',
		'lgsdn_home_feature_1_title' => 'Join the network',
		'lgsdn_home_feature_1_body' => 'This network is a safe and supportive space for people working in service design in local government. It’s here to help you grow your service design practice by.',
		'lgsdn_home_feature_2_title' => 'Browse the playbook',
		'lgsdn_home_feature_2_body' => 'This network is a safe and supportive space for people working in service design in local government. It’s here to help you grow your service design practice by.',
		'lgsdn_home_feature_3_title' => 'Contribute an example',
		'lgsdn_home_feature_3_body' => '',
	);

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'load-post.php', array( __CLASS__, 'protect_homepage_title' ) );
		add_action( 'admin_head-post.php', array( __CLASS__, 'hide_homepage_title' ) );
		add_filter( 'allowed_block_types_all', array( __CLASS__, 'limit_homepage_blocks' ), 10, 2 );
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save' ) );
	}

	/**
	 * Keep the internal "Home" label, but do not offer it as homepage content.
	 */
	public static function protect_homepage_title(): void {
		$post = self::current_admin_post();

		if ( $post && self::is_homepage( $post ) ) {
			remove_post_type_support( 'page', 'title' );
		}
	}

	/**
	 * Keep one non-insertable preview block in the homepage canvas.
	 */
	public static function limit_homepage_blocks( $allowed_block_types, WP_Block_Editor_Context $context ) {
		if ( $context->post instanceof WP_Post && self::is_homepage( $context->post ) ) {
			return array( 'lgsdn/homepage' );
		}

		return $allowed_block_types;
	}

	/**
	 * CSS fallback for editor versions that render the title before supports load.
	 */
	public static function hide_homepage_title(): void {
		$post = self::current_admin_post();

		if ( ! $post || ! self::is_homepage( $post ) ) {
			return;
		}
		?>
		<style>
			.editor-post-title,
			.edit-post-visual-editor__post-title-wrapper,
			#titlediv {
				display: none !important;
			}
		</style>
		<?php
	}

	public static function register_meta(): void {
		foreach ( array_keys( self::DEFAULTS ) as $key ) {
			register_post_meta(
				'page',
				$key,
				array(
					'single' => true,
					'type' => 'string',
					'show_in_rest' => true,
					'default' => self::DEFAULTS[ $key ],
					'sanitize_callback' => str_ends_with( $key, '_body' ) || 'lgsdn_home_lead' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field',
					'auth_callback' => static function (): bool {
						return current_user_can( 'edit_pages' );
					},
				)
			);
		}

		for ( $index = 1; $index <= 3; $index++ ) {
			register_post_meta(
				'page',
				"lgsdn_home_feature_{$index}_page_id",
				array(
					'single' => true,
					'type' => 'integer',
					'show_in_rest' => true,
					'default' => 0,
					'sanitize_callback' => 'absint',
					'auth_callback' => static function (): bool {
						return current_user_can( 'edit_pages' );
					},
				)
			);
		}
	}

	public static function add_meta_box( WP_Post $post ): void {
		if ( ! self::is_homepage( $post ) || use_block_editor_for_post( $post ) ) {
			return;
		}

		add_meta_box(
			'lgsdn-homepage-content',
			'Homepage content',
			array( __CLASS__, 'render' ),
			'page',
			'normal',
			'high'
		);
	}

	public static function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>Edit the homepage introduction and its three feature cards. Feature links are limited to pages on this site.</p>
		<table class="form-table" role="presentation"><tbody>
			<tr>
				<th scope="row"><label for="lgsdn_home_lead">Lead copy</label></th>
				<td><textarea class="large-text" rows="4" id="lgsdn_home_lead" name="lgsdn_home_lead"><?php echo esc_textarea( self::value( $post->ID, 'lgsdn_home_lead' ) ); ?></textarea></td>
			</tr>
			<?php for ( $index = 1; $index <= 3; $index++ ) : ?>
				<?php
				$title_key = "lgsdn_home_feature_{$index}_title";
				$body_key = "lgsdn_home_feature_{$index}_body";
				$page_key = "lgsdn_home_feature_{$index}_page_id";
				?>
				<tr><th colspan="2"><h3>Feature <?php echo esc_html( (string) $index ); ?></h3></th></tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $title_key ); ?>">Title</label></th>
					<td><input class="large-text" type="text" id="<?php echo esc_attr( $title_key ); ?>" name="<?php echo esc_attr( $title_key ); ?>" value="<?php echo esc_attr( self::value( $post->ID, $title_key ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $body_key ); ?>">Body</label></th>
					<td><textarea class="large-text" rows="4" id="<?php echo esc_attr( $body_key ); ?>" name="<?php echo esc_attr( $body_key ); ?>"><?php echo esc_textarea( self::value( $post->ID, $body_key ) ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $page_key ); ?>">Linked page</label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name' => $page_key,
								'id' => $page_key,
								'selected' => absint( get_post_meta( $post->ID, $page_key, true ) ),
								'show_option_none' => 'Select a page',
								'option_none_value' => '0',
							)
						);
						?>
					</td>
				</tr>
			<?php endfor; ?>
		</tbody></table>
		<?php
	}

	public static function save( int $post_id ): void {
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ||
			wp_is_post_autosave( $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		foreach ( array_keys( self::DEFAULTS ) as $key ) {
			$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			$value = str_ends_with( $key, '_body' ) || 'lgsdn_home_lead' === $key ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			update_post_meta( $post_id, $key, $value );
		}

		for ( $index = 1; $index <= 3; $index++ ) {
			$key = "lgsdn_home_feature_{$index}_page_id";
			update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : 0 );
		}
	}

	public static function value( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );
		return '' !== $value ? (string) $value : ( self::DEFAULTS[ $key ] ?? '' );
	}

	public static function defaults(): array {
		return self::DEFAULTS;
	}

	private static function is_homepage( WP_Post $post ): bool {
		$front_page_id = absint( get_option( 'page_on_front' ) );
		return $front_page_id === $post->ID || ( 0 === $front_page_id && 'home' === $post->post_name );
	}

	private static function current_admin_post(): ?WP_Post {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$post = $post_id ? get_post( $post_id ) : null;

		return $post instanceof WP_Post ? $post : null;
	}
}
