<?php
/**
 * Controlled visual and homepage presentation metadata for Service areas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Service_Styles {
	public const COLOUR_META = 'lgsdn_service_colour';
	public const ICON_META = 'lgsdn_service_icon';
	public const ORDER_META = 'lgsdn_service_home_order';
	public const FEATURED_META = 'lgsdn_service_featured';

	private const DEFAULT_COLOUR = 'orange';
	private const DEFAULT_ICON = 'service';

	private const ICONS = array(
		'service' => 'Service',
		'bricks' => 'Community safety',
		'care' => 'Adult social care',
		'phone' => 'Customer services and contact centres',
		'housing' => 'Housing and homelessness',
		'democracy' => 'Democracy and participation',
	);

	/**
	 * Brand Colors from the Figma "Sketching" file, Base mode.
	 *
	 * The Contrast values are kept alongside the selectable Base values so the
	 * complete Figma token pair remains available to future components. Admin
	 * metadata stores only the stable token key.
	 */
	private const COLOURS = array(
		'gold' => array(
			'label' => 'Gold',
			'background' => '#FAC558',
			'contrast' => '#AB9300',
			'foreground' => '#27272D',
		),
		'blue' => array(
			'label' => 'Blue',
			'background' => '#4B66FF',
			'contrast' => '#4B5AFF',
			'foreground' => '#FFFFFF',
		),
		'purple' => array(
			'label' => 'Purple',
			'background' => '#C6AFE3',
			'contrast' => '#9F8AC0',
			'foreground' => '#27272D',
		),
		'olive' => array(
			'label' => 'Olive',
			'background' => '#4B5A2B',
			'contrast' => '#4B5A37',
			'foreground' => '#FFFFFF',
		),
		'orange' => array(
			'label' => 'Orange',
			'background' => '#FF9D4D',
			'contrast' => '#EA7200',
			'foreground' => '#27272D',
		),
		'pink' => array(
			'label' => 'Pink',
			'background' => '#FACDE1',
			'contrast' => '#B3889B',
			'foreground' => '#27272D',
		),
	);

	private const LEGACY_COLOUR_ALIASES = array(
		'lilac' => 'purple',
	);

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'lgsdn_service_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( 'lgsdn_service_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_lgsdn_service', array( __CLASS__, 'save' ) );
		add_action( 'edited_lgsdn_service', array( __CLASS__, 'save' ) );
		add_filter( 'manage_edit-lgsdn_service_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_filter( 'manage_lgsdn_service_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 3 );
	}

	public static function register_meta(): void {
		$auth_callback = static function (): bool {
			return current_user_can( 'manage_options' );
		};

		register_term_meta(
			'lgsdn_service',
			self::COLOUR_META,
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => self::DEFAULT_COLOUR,
				'sanitize_callback' => array( __CLASS__, 'sanitize_colour' ),
				'auth_callback' => $auth_callback,
			)
		);
		register_term_meta(
			'lgsdn_service',
			self::ICON_META,
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => self::DEFAULT_ICON,
				'sanitize_callback' => array( __CLASS__, 'sanitize_icon' ),
				'auth_callback' => $auth_callback,
			)
		);
		register_term_meta(
			'lgsdn_service',
			self::ORDER_META,
			array(
				'type' => 'integer',
				'single' => true,
				'show_in_rest' => true,
				'default' => 0,
				'sanitize_callback' => 'absint',
				'auth_callback' => $auth_callback,
			)
		);
		register_term_meta(
			'lgsdn_service',
			self::FEATURED_META,
			array(
				'type' => 'boolean',
				'single' => true,
				'show_in_rest' => true,
				'default' => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback' => $auth_callback,
			)
		);
	}

	public static function colours(): array {
		return self::COLOURS;
	}

	public static function icons(): array {
		return self::ICONS;
	}

	public static function sanitize_colour( mixed $value ): string {
		$value = sanitize_key( (string) $value );
		$value = self::LEGACY_COLOUR_ALIASES[ $value ] ?? $value;
		return isset( self::colours()[ $value ] ) ? $value : self::DEFAULT_COLOUR;
	}

	public static function sanitize_icon( mixed $value ): string {
		$value = sanitize_key( (string) $value );
		return isset( self::ICONS[ $value ] ) ? $value : self::DEFAULT_ICON;
	}

	public static function defaults_for_term( WP_Term $term ): array {
		$name = strtolower( $term->name );
		$defaults = array(
			self::COLOUR_META => self::DEFAULT_COLOUR,
			self::ICON_META => self::DEFAULT_ICON,
			self::ORDER_META => 0,
			self::FEATURED_META => false,
		);

		if ( str_contains( $name, 'community safety' ) ) {
			$defaults[ self::COLOUR_META ] = 'orange';
			$defaults[ self::ICON_META ] = 'bricks';
			$defaults[ self::ORDER_META ] = 10;
			$defaults[ self::FEATURED_META ] = true;
		} elseif ( str_contains( $name, 'adult social care' ) ) {
			$defaults[ self::COLOUR_META ] = 'gold';
			$defaults[ self::ICON_META ] = 'care';
			$defaults[ self::ORDER_META ] = 20;
			$defaults[ self::FEATURED_META ] = true;
		} elseif ( str_contains( $name, 'customer services' ) ) {
			$defaults[ self::COLOUR_META ] = 'blue';
			$defaults[ self::ICON_META ] = 'phone';
			$defaults[ self::ORDER_META ] = 30;
			$defaults[ self::FEATURED_META ] = true;
		} elseif ( str_contains( $name, 'housing and homelessness' ) ) {
			$defaults[ self::COLOUR_META ] = 'purple';
			$defaults[ self::ICON_META ] = 'housing';
			$defaults[ self::ORDER_META ] = 40;
			$defaults[ self::FEATURED_META ] = true;
		} elseif ( str_contains( $name, 'democracy and participation' ) ) {
			$defaults[ self::COLOUR_META ] = 'olive';
			$defaults[ self::ICON_META ] = 'democracy';
			$defaults[ self::ORDER_META ] = 50;
			$defaults[ self::FEATURED_META ] = true;
		}

		return $defaults;
	}

	public static function for_term( WP_Term|int $term ): array {
		$term_object = $term instanceof WP_Term ? $term : get_term( absint( $term ), 'lgsdn_service' );
		$defaults = $term_object instanceof WP_Term ? self::defaults_for_term( $term_object ) : array();
		$colour = self::sanitize_colour( get_term_meta( $term_object->term_id ?? 0, self::COLOUR_META, true ) ?: ( $defaults[ self::COLOUR_META ] ?? self::DEFAULT_COLOUR ) );
		$icon = self::sanitize_icon( get_term_meta( $term_object->term_id ?? 0, self::ICON_META, true ) ?: ( $defaults[ self::ICON_META ] ?? self::DEFAULT_ICON ) );
		$order = absint( get_term_meta( $term_object->term_id ?? 0, self::ORDER_META, true ) ?: ( $defaults[ self::ORDER_META ] ?? 0 ) );
		$featured = (bool) get_term_meta( $term_object->term_id ?? 0, self::FEATURED_META, true );
		if ( ! metadata_exists( 'term', $term_object->term_id ?? 0, self::FEATURED_META ) ) {
			$featured = (bool) ( $defaults[ self::FEATURED_META ] ?? false );
		}
		$colours = self::colours();

		return array(
			'colour' => $colour,
			'background' => $colours[ $colour ]['background'],
			'contrast' => $colours[ $colour ]['contrast'],
			'foreground' => self::foreground_for_background( $colours[ $colour ]['background'] ),
			'icon' => $icon,
			'order' => $order,
			'featured' => $featured,
		);
	}

	/**
	 * Pick Ink or White based on the stronger WCAG contrast ratio.
	 */
	private static function foreground_for_background( string $hex ): string {
		$hex = ltrim( $hex, '#' );
		$rgb = array_map(
			static function ( string $channel ): float {
				$value = hexdec( $channel ) / 255;
				return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
			},
			str_split( $hex, 2 )
		);
		$luminance = ( 0.2126 * $rgb[0] ) + ( 0.7152 * $rgb[1] ) + ( 0.0722 * $rgb[2] );
		$ink_contrast = ( $luminance + 0.05 ) / 0.05;
		$white_contrast = 1.05 / ( $luminance + 0.05 );

		// Prefer white when it still passes AA; this keeps dark saturated
		// cards legible while retaining Ink on lighter palette colours.
		if ( $white_contrast >= 4.5 ) {
			return '#FFFFFF';
		}

		if ( $ink_contrast >= 4.5 ) {
			return '#27272D';
		}

		return $white_contrast >= $ink_contrast ? '#FFFFFF' : '#27272D';
	}

	public static function homepage_terms(): array {
		$terms = get_terms(
			array(
				'taxonomy' => 'lgsdn_service',
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$terms = array_values(
			array_filter(
				$terms,
				static function ( WP_Term $term ): bool {
					return self::for_term( $term )['featured'];
				}
			)
		);

		usort(
			$terms,
			static function ( WP_Term $left, WP_Term $right ): int {
				$left_style = self::for_term( $left );
				$right_style = self::for_term( $right );
				return ( $left_style['order'] <=> $right_style['order'] ) ?: strcasecmp( $left->name, $right->name );
			}
		);

		return $terms;
	}

	public static function icon_url( string $icon ): string {
		$icon = self::sanitize_icon( $icon );
		$path = get_theme_file_path( 'assets/images/service-areas/' . $icon . '.svg' );
		if ( ! is_readable( $path ) ) {
			$icon = self::DEFAULT_ICON;
		}

		return get_theme_file_uri( 'assets/images/service-areas/' . $icon . '.svg' );
	}

	public static function render_add_fields(): void {
		wp_nonce_field( 'lgsdn_save_service_style', 'lgsdn_service_style_nonce' );
		self::render_fields( null );
	}

	public static function render_edit_fields( WP_Term $term ): void {
		wp_nonce_field( 'lgsdn_save_service_style', 'lgsdn_service_style_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::COLOUR_META ); ?>">Service area colour</label></th>
			<td><?php self::render_fields( $term ); ?></td>
		</tr>
		<?php
	}

	public static function save( int $term_id ): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['lgsdn_service_style_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['lgsdn_service_style_nonce'] ) ),
				'lgsdn_save_service_style'
			)
		) {
			return;
		}

		$colour = isset( $_POST[ self::COLOUR_META ] ) ? self::sanitize_colour( wp_unslash( $_POST[ self::COLOUR_META ] ) ) : self::DEFAULT_COLOUR;
		$icon = isset( $_POST[ self::ICON_META ] ) ? self::sanitize_icon( wp_unslash( $_POST[ self::ICON_META ] ) ) : self::DEFAULT_ICON;
		$order = isset( $_POST[ self::ORDER_META ] ) ? absint( $_POST[ self::ORDER_META ] ) : 0;
		$featured = isset( $_POST[ self::FEATURED_META ] );

		update_term_meta( $term_id, self::COLOUR_META, $colour );
		update_term_meta( $term_id, self::ICON_META, $icon );
		update_term_meta( $term_id, self::ORDER_META, $order );
		update_term_meta( $term_id, self::FEATURED_META, $featured );
	}

	public static function add_admin_columns( array $columns ): array {
		$service_columns = array(
			'lgsdn_service_colour' => 'Colour',
			'lgsdn_service_featured' => 'Homepage card',
		);
		$name_position = array_search( 'name', array_keys( $columns ), true );

		if ( false === $name_position ) {
			return $columns + $service_columns;
		}

		$insert_position = $name_position + 1;

		return array_slice( $columns, 0, $insert_position, true )
			+ $service_columns
			+ array_slice( $columns, $insert_position, null, true );
	}

	public static function render_admin_column( string $content, string $column_name, int $term_id ): string {
		$style = self::for_term( $term_id );
		if ( 'lgsdn_service_colour' === $column_name ) {
			$colour = self::COLOURS[ $style['colour'] ];

			return sprintf(
				'<span style="display:inline-flex;align-items:center;gap:7px"><span aria-hidden="true" style="display:inline-block;width:18px;height:18px;border:1px solid #27272d;border-radius:50%%;background:%1$s"></span><span>%2$s</span></span>',
				esc_attr( $colour['background'] ),
				esc_html( $colour['label'] )
			);
		}

		if ( 'lgsdn_service_featured' !== $column_name ) {
			return $content;
		}

		return $style['featured'] ? 'Yes · ' . esc_html( (string) $style['order'] ) : 'No';
	}

	private static function render_fields( ?WP_Term $term ): void {
		$style = $term ? self::for_term( $term ) : array(
			'colour' => self::DEFAULT_COLOUR,
			'icon' => self::DEFAULT_ICON,
			'order' => 0,
			'featured' => false,
		);
		?>
		<label for="<?php echo esc_attr( self::COLOUR_META ); ?>" class="screen-reader-text">Service area colour</label>
		<?php self::render_colour_select( $style['colour'] ); ?>
		<p class="description">The approved colour used by service-area cards.</p>
		<p><label for="<?php echo esc_attr( self::ICON_META ); ?>">Card illustration</label><br>
		<select id="<?php echo esc_attr( self::ICON_META ); ?>" name="<?php echo esc_attr( self::ICON_META ); ?>">
			<?php foreach ( self::ICONS as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $style['icon'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select></p>
		<p><label for="<?php echo esc_attr( self::ORDER_META ); ?>">Homepage order</label><br>
		<input id="<?php echo esc_attr( self::ORDER_META ); ?>" name="<?php echo esc_attr( self::ORDER_META ); ?>" type="number" min="0" step="10" value="<?php echo esc_attr( (string) $style['order'] ); ?>"></p>
		<p><label><input name="<?php echo esc_attr( self::FEATURED_META ); ?>" type="checkbox" value="1" <?php checked( $style['featured'], true ); ?>> Show in the homepage service-area row</label></p>
		<?php
	}

	private static function render_colour_select( string $selected_colour ): void {
		echo '<select id="' . esc_attr( self::COLOUR_META ) . '" name="' . esc_attr( self::COLOUR_META ) . '">';
		foreach ( self::COLOURS as $key => $colour ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_colour, $key, false ) . '>';
			echo esc_html( $colour['label'] . ' — ' . $colour['background'] );
			echo '</option>';
		}
		echo '</select>';

		echo '<div style="display:flex;gap:6px;margin-top:10px" aria-hidden="true">';
		foreach ( self::COLOURS as $colour ) {
			echo '<span style="display:block;width:28px;height:28px;border:1px solid #27272d;border-radius:50%;background:' . esc_attr( $colour['background'] ) . '"></span>';
		}
		echo '</div>';
	}
}
