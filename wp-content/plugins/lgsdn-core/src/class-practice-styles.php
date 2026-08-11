<?php
/**
 * Controlled visual presentation metadata for Practice terms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Practice_Styles {
	public const COLOUR_META = 'lgsdn_practice_colour';
	private const DEFAULT_COLOUR = 'orange';
	private const CONTOUR_DIRECTORY = 'assets/images';
	private const CONTOUR_PREFIX = 'practice-contour-';

	private const COLOURS = array(
		'lilac' => array(
			'label' => 'Lilac',
			'background' => '#C6AFE3',
			'foreground' => '#27272D',
		),
		'olive' => array(
			'label' => 'Olive',
			'background' => '#4B5A2B',
			'foreground' => '#FFFFFF',
		),
		'orange' => array(
			'label' => 'Orange',
			'background' => '#FD7D12',
			'foreground' => '#27272D',
		),
		'blue' => array(
			'label' => 'Blue',
			'background' => '#4B66FF',
			'foreground' => '#FFFFFF',
		),
		'gold' => array(
			'label' => 'Gold',
			'background' => '#FAC558',
			'foreground' => '#27272D',
		),
	);

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'lgsdn_practice_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( 'lgsdn_practice_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_lgsdn_practice', array( __CLASS__, 'save' ) );
		add_action( 'edited_lgsdn_practice', array( __CLASS__, 'save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_missing_contour_notice' ) );
		add_filter( 'manage_edit-lgsdn_practice_columns', array( __CLASS__, 'add_admin_colour_column' ) );
		add_filter( 'manage_lgsdn_practice_custom_column', array( __CLASS__, 'render_admin_colour_column' ), 10, 3 );
	}

	public static function register_meta(): void {
		$auth_callback = static function (): bool {
			return current_user_can( 'manage_options' );
		};

		register_term_meta(
			'lgsdn_practice',
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
	}

	public static function colours(): array {
		return self::COLOURS;
	}

	public static function sanitize_colour( mixed $value ): string {
		$value = sanitize_key( (string) $value );
		return isset( self::COLOURS[ $value ] ) ? $value : self::DEFAULT_COLOUR;
	}

	/**
	 * Return the safe, complete visual style for a Practice term.
	 */
	public static function for_term( WP_Term|int $term ): array {
		$term_id = $term instanceof WP_Term ? $term->term_id : absint( $term );
		$colour_key = self::sanitize_colour( get_term_meta( $term_id, self::COLOUR_META, true ) );

		return array(
			'colour' => $colour_key,
			'background' => self::COLOURS[ $colour_key ]['background'],
			'foreground' => self::COLOURS[ $colour_key ]['foreground'],
		);
	}

	public static function contour_path( string $colour ): string {
		$colour = self::sanitize_colour( $colour );
		$path = self::expected_contour_path( $colour );

		if ( is_readable( $path ) ) {
			return $path;
		}

		$fallback_path = self::expected_contour_path( self::DEFAULT_COLOUR );

		return is_readable( $fallback_path ) ? $fallback_path : '';
	}

	public static function render_add_fields(): void {
		wp_nonce_field( 'lgsdn_save_practice_style', 'lgsdn_practice_style_nonce' );
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::COLOUR_META ); ?>">Practice colour</label>
			<?php self::render_colour_select( self::DEFAULT_COLOUR ); ?>
			<p>Choose from the approved, accessible Playbook palette. The colour determines the fallback contour.</p>
		</div>
		<?php
	}

	public static function render_edit_fields( WP_Term $term ): void {
		$style = self::for_term( $term );
		wp_nonce_field( 'lgsdn_save_practice_style', 'lgsdn_practice_style_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::COLOUR_META ); ?>">Practice colour</label></th>
			<td>
				<?php self::render_colour_select( $style['colour'] ); ?>
				<p class="description">Choose from the approved, accessible Playbook palette. The colour determines the fallback contour.</p>
			</td>
		</tr>
		<?php
	}

	public static function save( int $term_id ): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['lgsdn_practice_style_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['lgsdn_practice_style_nonce'] ) ),
				'lgsdn_save_practice_style'
			)
		) {
			return;
		}

		$colour = isset( $_POST[ self::COLOUR_META ] )
			? self::sanitize_colour( wp_unslash( $_POST[ self::COLOUR_META ] ) )
			: self::DEFAULT_COLOUR;

		update_term_meta( $term_id, self::COLOUR_META, $colour );
	}

	public static function render_missing_contour_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$missing = array();
		foreach ( array_keys( self::COLOURS ) as $colour ) {
			if ( ! is_readable( self::expected_contour_path( $colour ) ) ) {
				$missing[] = self::contour_filename( $colour );
			}
		}

		if ( ! $missing ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo '<strong>LGSDN Playbook:</strong> ';
		echo esc_html(
			sprintf(
				'Missing practice contour %s: %s. Add the missing SVG %s to the active theme’s %s directory. Cards will use the orange contour when it is available.',
				1 === count( $missing ) ? 'file' : 'files',
				implode( ', ', $missing ),
				1 === count( $missing ) ? 'file' : 'files',
				self::CONTOUR_DIRECTORY
			)
		);
		echo '</p></div>';
	}

	public static function add_admin_colour_column( array $columns ): array {
		$colour_column = array( 'lgsdn_practice_colour' => 'Colour' );
		$name_position = array_search( 'name', array_keys( $columns ), true );

		if ( false === $name_position ) {
			return $columns + $colour_column;
		}

		$insert_position = $name_position + 1;

		return array_slice( $columns, 0, $insert_position, true )
			+ $colour_column
			+ array_slice( $columns, $insert_position, null, true );
	}

	public static function render_admin_colour_column( string $content, string $column_name, int $term_id ): string {
		if ( 'lgsdn_practice_colour' !== $column_name ) {
			return $content;
		}

		$style = self::for_term( $term_id );
		$colour = self::COLOURS[ $style['colour'] ];

		return sprintf(
			'<span style="display:inline-flex;align-items:center;gap:7px"><span aria-hidden="true" style="display:inline-block;width:18px;height:18px;border:1px solid #27272d;border-radius:50%%;background:%1$s"></span><span>%2$s</span></span>',
			esc_attr( $colour['background'] ),
			esc_html( $colour['label'] )
		);
	}

	private static function render_colour_select( string $selected_colour ): void {
		echo '<select class="postform" id="' . esc_attr( self::COLOUR_META ) . '" name="' . esc_attr( self::COLOUR_META ) . '">';
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

	private static function contour_filename( string $colour ): string {
		return self::CONTOUR_PREFIX . self::sanitize_colour( $colour ) . '.svg';
	}

	private static function expected_contour_path( string $colour ): string {
		return get_theme_file_path( self::CONTOUR_DIRECTORY . '/' . self::contour_filename( $colour ) );
	}
}
