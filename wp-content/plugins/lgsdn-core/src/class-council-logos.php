<?php
/** Council logo metadata and taxonomy editor controls. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Council_Logos {
	public const LOGO_META = 'lgsdn_council_logo_id';
	public const DISPLAY_META = 'lgsdn_council_logo_display';

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'lgsdn_council_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( 'lgsdn_council_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_lgsdn_council', array( __CLASS__, 'save' ) );
		add_action( 'edited_lgsdn_council', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_media' ) );
		add_action( 'admin_notices', array( __CLASS__, 'svg_dependency_notice' ) );
	}

	public static function register_meta(): void {
		$auth_callback = static function (): bool {
			return current_user_can( 'edit_posts' );
		};

		register_term_meta( 'lgsdn_council', self::LOGO_META, array(
			'type' => 'integer', 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => 'absint', 'auth_callback' => $auth_callback,
		) );
		register_term_meta( 'lgsdn_council', self::DISPLAY_META, array(
			'type' => 'string', 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => array( __CLASS__, 'sanitize_display' ), 'auth_callback' => $auth_callback,
		) );
	}

	public static function sanitize_display( mixed $value ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, array( '', 'logo', 'title-logo', 'title-only' ), true ) ? $value : '';
	}

	public static function svg_dependency_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if (
			! $screen ||
			! in_array( $screen->base, array( 'edit-tags', 'term' ), true ) ||
			'lgsdn_council' !== $screen->taxonomy ||
			class_exists( '\\SafeSvg\\safe_svg' )
		) {
			return;
		}

		echo '<div class="notice notice-warning"><p>SVG council logos require the Safe SVG plugin to be installed and activated. Raster image logos can still be selected.</p></div>';
	}

	public static function enqueue_media(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && 'lgsdn_council' === $screen->taxonomy ) {
			wp_enqueue_media();
			wp_enqueue_script( 'media-editor' );
			wp_add_inline_script( 'media-editor', self::media_script(), 'after' );
		}
	}

	public static function render_add_fields(): void {
		wp_nonce_field( 'lgsdn_save_council_logo', 'lgsdn_council_logo_nonce' );
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::LOGO_META ); ?>">Council logo</label>
			<?php self::render_controls( 0, '' ); ?>
		</div>
		<?php
	}

	public static function render_edit_fields( WP_Term $term ): void {
		wp_nonce_field( 'lgsdn_save_council_logo', 'lgsdn_council_logo_nonce' );
		$logo_id = absint( get_term_meta( $term->term_id, self::LOGO_META, true ) );
		$display = self::sanitize_display( get_term_meta( $term->term_id, self::DISPLAY_META, true ) );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::LOGO_META ); ?>">Council logo</label></th>
			<td><?php self::render_controls( $logo_id, $display ); ?></td>
		</tr>
		<?php
	}

	private static function render_controls( int $logo_id, string $display ): void {
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : false;
		?>
		<div class="lgsdn-council-logo-control">
			<input type="hidden" id="<?php echo esc_attr( self::LOGO_META ); ?>" name="<?php echo esc_attr( self::LOGO_META ); ?>" value="<?php echo esc_attr( (string) $logo_id ); ?>">
			<div data-council-logo-preview><?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:180px;height:auto;display:block;margin-bottom:8px"><?php endif; ?></div>
			<button type="button" class="button" data-council-logo-select><?php echo $logo_id ? 'Change logo' : 'Choose logo'; ?></button>
			<button type="button" class="button-link-delete" data-council-logo-remove <?php echo $logo_id ? '' : 'hidden'; ?>>Remove logo</button>
			<p class="description">Optional. Choose an image from the Media Library.</p>
			<label for="<?php echo esc_attr( self::DISPLAY_META ); ?>">Logo display</label>
			<select id="<?php echo esc_attr( self::DISPLAY_META ); ?>" name="<?php echo esc_attr( self::DISPLAY_META ); ?>">
				<?php foreach ( array( '' => 'Use the logo default', 'logo' => 'Logo only', 'title-logo' => 'Title and logo', 'title-only' => 'Title only' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $display, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	public static function save( int $term_id ): void {
		if (
			! current_user_can( 'edit_term', $term_id ) ||
			! isset( $_POST['lgsdn_council_logo_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lgsdn_council_logo_nonce'] ) ), 'lgsdn_save_council_logo' )
		) {
			return;
		}

		$logo_id = isset( $_POST[ self::LOGO_META ] ) ? absint( $_POST[ self::LOGO_META ] ) : 0;
		if ( $logo_id && 'attachment' !== get_post_type( $logo_id ) ) {
			$logo_id = 0;
		}
		if ( $logo_id ) {
			update_term_meta( $term_id, self::LOGO_META, $logo_id );
		} else {
			delete_term_meta( $term_id, self::LOGO_META );
		}

		$display = isset( $_POST[ self::DISPLAY_META ] ) ? self::sanitize_display( wp_unslash( $_POST[ self::DISPLAY_META ] ) ) : '';
		update_term_meta( $term_id, self::DISPLAY_META, $display );
	}

	private static function media_script(): string {
		return <<<'JS'
(function ($, wp) {
	'use strict';
	$(document).on('click', '[data-council-logo-select]', function (event) {
		event.preventDefault();
		if (!wp || typeof wp.media !== 'function') {
			window.console.error('Council logo picker: WordPress media library is unavailable.');
			return;
		}
		var control = $(this).closest('.lgsdn-council-logo-control');
		var frame = wp.media({
			title: 'Choose council logo',
			button: { text: 'Use this logo' },
			multiple: false,
			library: { type: 'image' }
		});
		frame.on('select', function () {
			var item = frame.state().get('selection').first().toJSON();
			control.find('input[name="lgsdn_council_logo_id"]').val(item.id);
			$('<img>', {
				src: item.url,
				alt: '',
				css: { maxWidth: '180px', height: 'auto', display: 'block', marginBottom: '8px' }
			}).appendTo(control.find('[data-council-logo-preview]').empty());
			control.find('[data-council-logo-select]').text('Change logo');
			control.find('[data-council-logo-remove]').prop('hidden', false);
		});
		frame.open();
	});
	$(document).on('click', '[data-council-logo-remove]', function (event) {
		event.preventDefault();
		var control = $(this).closest('.lgsdn-council-logo-control');
		control.find('input[name="lgsdn_council_logo_id"]').val('0');
		control.find('[data-council-logo-preview]').empty();
		control.find('[data-council-logo-select]').text('Choose logo');
		$(this).prop('hidden', true);
	});
})(jQuery, window.wp);
JS;
	}
}
