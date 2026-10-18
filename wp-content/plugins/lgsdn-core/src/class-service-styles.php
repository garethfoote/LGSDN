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
	public const PLAYBOOK_ORDER_META = 'lgsdn_service_playbook_order';
	public const FEATURED_META = 'lgsdn_service_featured';

	private const DEFAULT_COLOUR = 'orange';
	private const DEFAULT_ICON = 'service';

	/**
	 * Service-card gradients run from the decorative top colour to the text-bearing
	 * bottom colour. Bottom stops are selected to meet WCAG AA for white card text.
	 */
	private const CARD_GRADIENT = array(
		'angle' => 180,
	);

	private const ICONS = array(
		'service' => 'Service',
		'bricks' => 'Community safety',
		'care' => 'Adult social care',
		'phone' => 'Customer services and contact centres',
		'housing' => 'Housing and homelessness',
		'democracy' => 'Democracy and participation',
		'digital' => 'Digital and online services',
		'libraries' => 'Libraries and culture',
		'children' => 'Children and families',
		'transport' => 'Transport, streets and neighbourhoods',
		'waste' => 'Waste and environment',
		'public-health' => 'Public health',
	);

	/**
	 * Shared palette values stay available to Practice styles. Service-card gradient
	 * stops are specific to those cards; foreground pairs also keep small colored
	 * service tags readable. Admin metadata stores only the stable token key.
	 */
	private const COLOURS = array(
		'mustard' => array(
			'label' => 'Mustard',
			'background' => '#B59B00',
			'contrast' => '#8A7600',
			'foreground' => '#27272D',
			'gradient_top' => '#B59B00',
			'gradient_hold_until' => 45,
			'gradient_bottom' => '#897600',
		),
		'blue' => array(
			'label' => 'Blue',
			'background' => '#4B5AFF',
			'contrast' => '#3444C7',
			'foreground' => '#FFFFFF',
			'gradient_top' => '#5A6CFF',
			'gradient_bottom' => '#4B5AFF',
		),
		'purple' => array(
			'label' => 'Purple',
			'background' => '#C7AFE1',
			'contrast' => '#9B83B8',
			'foreground' => '#27272D',
			'gradient_top' => '#A183C2',
			'gradient_hold_until' => 45,
			'gradient_bottom' => '#876AA9',
		),
		'green' => array(
			'label' => 'Green',
			'background' => '#4B5B37',
			'contrast' => '#3A472B',
			'foreground' => '#FFFFFF',
			'gradient_top' => '#4C5B37',
			'gradient_bottom' => '#3A472B',
		),
		'orange' => array(
			'label' => 'Orange',
			'background' => '#EA7200',
			'contrast' => '#EA7200',
			'foreground' => '#27272D',
			'gradient_top' => '#F66F00',
			'gradient_hold_until' => 45,
			'gradient_bottom' => '#BD5B00',
		),
		'pink' => array(
			'label' => 'Pink',
			'background' => '#FACDE1',
			'contrast' => '#B3889B',
			'foreground' => '#27272D',
			'gradient_top' => '#D081A4',
			'gradient_hold_until' => 45,
			'gradient_bottom' => '#9D667E',
		),
	);

	private const LEGACY_COLOUR_ALIASES = array(
		'lilac' => 'purple',
		'gold' => 'mustard',
		'olive' => 'green',
	);

	public static function hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 7 );
		add_action( 'lgsdn_service_add_form_fields', array( __CLASS__, 'render_add_fields' ) );
		add_action( 'lgsdn_service_edit_form_fields', array( __CLASS__, 'render_edit_fields' ) );
		add_action( 'created_lgsdn_service', array( __CLASS__, 'save' ) );
		add_action( 'edited_lgsdn_service', array( __CLASS__, 'save' ) );
		add_filter( 'manage_edit-lgsdn_service_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_filter( 'manage_lgsdn_service_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 3 );
		add_filter( 'get_terms_args', array( __CLASS__, 'order_admin_service_terms' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_order_admin_assets' ) );
		add_action( 'wp_ajax_lgsdn_reorder_service_areas', array( __CLASS__, 'ajax_reorder_service_areas' ) );
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
			self::PLAYBOOK_ORDER_META,
			array(
				'type' => 'integer',
				'single' => true,
				'show_in_rest' => true,
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
			$defaults[ self::COLOUR_META ] = 'mustard';
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
			$defaults[ self::COLOUR_META ] = 'green';
			$defaults[ self::ICON_META ] = 'democracy';
			$defaults[ self::ORDER_META ] = 50;
			$defaults[ self::FEATURED_META ] = true;
		} elseif ( str_contains( $name, 'digital' ) && str_contains( $name, 'online' ) ) {
			$defaults[ self::ICON_META ] = 'digital';
		} elseif ( str_contains( $name, 'libraries' ) && str_contains( $name, 'culture' ) ) {
			$defaults[ self::ICON_META ] = 'libraries';
		} elseif ( str_contains( $name, 'children' ) && str_contains( $name, 'famil' ) ) {
			$defaults[ self::ICON_META ] = 'children';
		} elseif ( str_contains( $name, 'transport' ) && str_contains( $name, 'neighbourhoods' ) ) {
			$defaults[ self::ICON_META ] = 'transport';
		} elseif ( str_contains( $name, 'waste' ) && str_contains( $name, 'environment' ) ) {
			$defaults[ self::ICON_META ] = 'waste';
		} elseif ( str_contains( $name, 'public health' ) ) {
			$defaults[ self::ICON_META ] = 'public-health';
		}

		return $defaults;
	}

	public static function for_term( WP_Term|int $term ): array {
		$term_object = $term instanceof WP_Term ? $term : get_term( absint( $term ), 'lgsdn_service' );
		$defaults = $term_object instanceof WP_Term ? self::defaults_for_term( $term_object ) : array();
		$colour = self::sanitize_colour( get_term_meta( $term_object->term_id ?? 0, self::COLOUR_META, true ) ?: ( $defaults[ self::COLOUR_META ] ?? self::DEFAULT_COLOUR ) );
		$icon = self::sanitize_icon( get_term_meta( $term_object->term_id ?? 0, self::ICON_META, true ) ?: ( $defaults[ self::ICON_META ] ?? self::DEFAULT_ICON ) );
		$order = absint( get_term_meta( $term_object->term_id ?? 0, self::ORDER_META, true ) ?: ( $defaults[ self::ORDER_META ] ?? 0 ) );
		$playbook_order = absint( get_term_meta( $term_object->term_id ?? 0, self::PLAYBOOK_ORDER_META, true ) );
		$featured = (bool) get_term_meta( $term_object->term_id ?? 0, self::FEATURED_META, true );
		if ( ! metadata_exists( 'term', $term_object->term_id ?? 0, self::FEATURED_META ) ) {
			$featured = (bool) ( $defaults[ self::FEATURED_META ] ?? false );
		}
		$colours = self::colours();
		$gradient = self::card_gradient( $colours[ $colour ] );

		return array(
			'colour' => $colour,
			'background' => $colours[ $colour ]['background'],
			'contrast' => $colours[ $colour ]['contrast'],
			'foreground' => $colours[ $colour ]['foreground'],
			'gradient' => $gradient,
			'icon' => $icon,
			'order' => $order,
			'playbook_order' => $playbook_order,
			'featured' => $featured,
		);
	}

	private static function card_gradient( array $palette ): string {
		$angle = max( 0, min( 360, (int) ( self::CARD_GRADIENT['angle'] ?? 135 ) ) );
		if ( isset( $palette['gradient_hold_until'] ) ) {
			$hold_until = max( 0, min( 99, (int) $palette['gradient_hold_until'] ) );
			return sprintf( 'linear-gradient(%ddeg, %s 0%%, %s %d%%, %s 100%%)', $angle, $palette['gradient_top'], $palette['gradient_top'], $hold_until, $palette['gradient_bottom'] );
		}

		return sprintf( 'linear-gradient(%ddeg, %s, %s)', $angle, $palette['gradient_top'], $palette['gradient_bottom'] );
	}

	/**
	 * Prefer White when it passes WCAG AA, otherwise use Ink or the stronger pair.
	 */
	public static function homepage_terms(): array {
		return self::service_terms( true );
	}

	/**
	 * Return every service area for Playbook browsing, including terms hidden from the homepage.
	 */
	public static function playbook_terms(): array {
		return self::service_terms( false );
	}

	private static function service_terms( bool $featured_only ): array {
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

		if ( $featured_only ) {
			$terms = array_values(
				array_filter(
					$terms,
					static function ( WP_Term $term ): bool {
						return self::for_term( $term )['featured'];
					}
				)
			);
		}

		usort(
			$terms,
			static function ( WP_Term $left, WP_Term $right ) use ( $featured_only ): int {
				if ( ! $featured_only ) {
					$left_order = self::for_term( $left )['playbook_order'];
					$right_order = self::for_term( $right )['playbook_order'];
					return ( $left_order <=> $right_order ) ?: strcasecmp( $left->name, $right->name );
				}
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
		if ( ! metadata_exists( 'term', $term_id, self::PLAYBOOK_ORDER_META ) ) {
			self::append_playbook_order( $term_id );
		}
	}

	private static function append_playbook_order( int $term_id ): void {
		$orders = get_terms(
			array(
				'taxonomy' => 'lgsdn_service',
				'hide_empty' => false,
				'fields' => 'ids',
				'meta_key' => self::PLAYBOOK_ORDER_META,
				'orderby' => 'meta_value_num',
				'order' => 'DESC',
				'number' => 1,
			)
		);
		$last_id = is_array( $orders ) && ! empty( $orders ) ? absint( $orders[0] ) : 0;
		$last_order = $last_id ? absint( get_term_meta( $last_id, self::PLAYBOOK_ORDER_META, true ) ) : 0;
		update_term_meta( $term_id, self::PLAYBOOK_ORDER_META, $last_order + 10 );
	}

	public static function add_admin_columns( array $columns ): array {
		$service_columns = array(
			'lgsdn_service_playbook_order' => 'Playbook order',
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
		if ( 'lgsdn_service_playbook_order' === $column_name ) {
			$term = get_term( $term_id, 'lgsdn_service' );
			if ( ! $term instanceof WP_Term ) {
				return $content;
			}

			$name = esc_attr( $term->name );
			return sprintf(
				'<span class="lgsdn-service-order-controls"><button type="button" class="button-link lgsdn-service-order-handle" title="Drag to reorder" aria-label="Drag %1$s to change its Playbook order"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button><button type="button" class="button-link lgsdn-service-order-up" aria-label="Move %1$s up in Playbook order"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button><button type="button" class="button-link lgsdn-service-order-down" aria-label="Move %1$s down in Playbook order"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button></span>',
				$name
			);
		}

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

	public static function order_admin_service_terms( array $args, mixed $taxonomies ): array {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if (
			! is_admin() ||
			! $screen ||
			'edit-tags' !== $screen->base ||
			'lgsdn_service' !== $screen->taxonomy ||
			! is_array( $taxonomies ) ||
			! in_array( 'lgsdn_service', $taxonomies, true ) ||
			! empty( $_GET['orderby'] )
		) {
			return $args;
		}

		$args['meta_key'] = self::PLAYBOOK_ORDER_META;
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'ASC';
		return $args;
	}

	public static function enqueue_order_admin_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-tags' !== $screen->base || 'lgsdn_service' !== $screen->taxonomy || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$script_path = LGSDN_CORE_DIR . 'assets/js/service-area-order.js';
		$style_path = LGSDN_CORE_DIR . 'assets/css/service-area-order.css';
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script(
			'lgsdn-service-area-order',
			plugins_url( 'assets/js/service-area-order.js', LGSDN_CORE_FILE ),
			array( 'jquery', 'jquery-ui-sortable' ),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : LGSDN_CORE_VERSION,
			true
		);
		wp_localize_script(
			'lgsdn-service-area-order',
			'LGSDNServiceAreaOrder',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'lgsdn_reorder_service_areas' ),
				'termCount' => (int) wp_count_terms( array( 'taxonomy' => 'lgsdn_service', 'hide_empty' => false ) ),
				'enabled' => empty( $_GET['s'] ) && empty( $_GET['orderby'] ) && 1 >= max( 1, absint( $_GET['paged'] ?? 1 ) ),
			)
		);
		wp_enqueue_style(
			'lgsdn-service-area-order',
			plugins_url( 'assets/css/service-area-order.css', LGSDN_CORE_FILE ),
			array( 'dashicons' ),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : LGSDN_CORE_VERSION
		);
	}

	public static function ajax_reorder_service_areas(): void {
		check_ajax_referer( 'lgsdn_reorder_service_areas', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You are not allowed to reorder service areas.' ), 403 );
		}

		$submitted_ids = isset( $_POST['term_ids'] ) && is_array( $_POST['term_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['term_ids'] ) ) : array();
		$submitted_ids = array_values( array_unique( array_filter( $submitted_ids ) ) );
		$all_ids = get_terms(
			array(
				'taxonomy' => 'lgsdn_service',
				'hide_empty' => false,
				'fields' => 'ids',
				'orderby' => 'term_id',
				'order' => 'ASC',
				'number' => 0,
			)
		);
		if ( is_wp_error( $all_ids ) || count( $submitted_ids ) !== count( $all_ids ) || array_diff( $all_ids, $submitted_ids ) || array_diff( $submitted_ids, $all_ids ) ) {
			wp_send_json_error( array( 'message' => 'The service area list changed. Reload the page before trying again.' ), 400 );
		}

		foreach ( $submitted_ids as $index => $id ) {
			update_term_meta( $id, self::PLAYBOOK_ORDER_META, ( $index + 1 ) * 10 );
		}
		wp_send_json_success( array( 'message' => 'Playbook order saved.' ) );
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
		<p><label><input name="<?php echo esc_attr( self::FEATURED_META ); ?>" type="checkbox" value="1" <?php checked( $style['featured'], true ); ?>> Show as a card on the homepage</label></p>
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
