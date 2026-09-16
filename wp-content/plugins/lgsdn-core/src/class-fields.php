<?php
/**
 * Structured fields and their editing UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Fields {
	private const FIELD_GROUPS = array(
		'lgsdn_person' => array(
			'lgsdn_role' => array( 'Role', 'text' ),
			'lgsdn_organisation' => array( 'Organisation', 'text' ),
			'lgsdn_profile_url' => array( 'Profile link', 'url' ),
		),
		'lgsdn_playbook' => array(
			'lgsdn_contributor_id' => array( 'Case study author', 'person-select' ),
			'lgsdn_primary_service_id' => array( 'Primary service area', 'service-select' ),
			'lgsdn_primary_practice_id' => array( 'Primary practice (optional)', 'practice-select' ),
			'lgsdn_reviewed_on' => array( 'Last reviewed', 'date' ),
			'lgsdn_resource_url' => array( 'Resource link', 'url' ),
			'lgsdn_featured_home' => array( 'Feature on the homepage', 'checkbox' ),
		),
		'lgsdn_event' => array(
			'lgsdn_start_at' => array( 'Starts', 'datetime-local' ),
			'lgsdn_end_at' => array( 'Ends', 'datetime-local' ),
			'lgsdn_location' => array( 'Location', 'text' ),
			'lgsdn_map_url' => array( 'Map link (optional)', 'url' ),
			'lgsdn_event_mode' => array( 'Format', 'select' ),
			'lgsdn_booking_url' => array( 'Booking link (optional)', 'url' ),
			'lgsdn_booking_label' => array( 'Call to action text (optional)', 'text' ),
			'lgsdn_event_resources' => array( 'Resources (optional)', 'resource-links' ),
		),
	);

	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
		add_filter( 'manage_lgsdn_event_posts_columns', array( __CLASS__, 'event_columns' ) );
		add_action( 'manage_lgsdn_event_posts_custom_column', array( __CLASS__, 'render_event_column' ), 10, 2 );
		add_filter( 'manage_edit-lgsdn_event_sortable_columns', array( __CLASS__, 'sortable_event_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'order_events_admin_list' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Load the repeatable resource-link controls on Event edit screens.
	 */
	public static function enqueue_admin_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'lgsdn_event' !== $screen->post_type ) {
			return;
		}

		$path = LGSDN_CORE_DIR . 'assets/js/event-resources.js';
		wp_enqueue_media();
		wp_enqueue_script(
			'lgsdn-event-resources',
			plugins_url( 'assets/js/event-resources.js', LGSDN_CORE_FILE ),
			array( 'media-editor' ),
			file_exists( $path ) ? (string) filemtime( $path ) : LGSDN_CORE_VERSION,
			true
		);
	}

	/**
	 * Add the event start date alongside WordPress's publication date.
	 */
	public static function event_columns( array $columns ): array {
		$event_columns = array();

		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$event_columns['lgsdn_start_at'] = 'Starts';
			}

			$event_columns[ $key ] = $label;
		}

		if ( ! isset( $event_columns['lgsdn_start_at'] ) ) {
			$event_columns['lgsdn_start_at'] = 'Starts';
		}

		return $event_columns;
	}

	/**
	 * Display an event's start date in the site timezone.
	 */
	public static function render_event_column( string $column, int $post_id ): void {
		if ( 'lgsdn_start_at' !== $column ) {
			return;
		}

		$raw = get_post_meta( $post_id, 'lgsdn_start_at', true );
		if ( ! is_string( $raw ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/D', $raw ) ) {
			echo '&mdash;';
			return;
		}

		$normalized = 16 === strlen( $raw ) ? $raw . ':00' : $raw;
		$timezone = wp_timezone();
		$starts = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s', $normalized, $timezone );

		if ( ! $starts || $starts->format( 'Y-m-d\TH:i:s' ) !== $normalized ) {
			echo '&mdash;';
			return;
		}

		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		echo esc_html( wp_date( $format, $starts->getTimestamp(), $timezone ) );
	}

	/**
	 * Make the event start date available as an admin-list sort option.
	 */
	public static function sortable_event_columns( array $columns ): array {
		$columns['lgsdn_start_at'] = 'lgsdn_start_at';
		return $columns;
	}

	/**
	 * Sort the Events admin list by its local ISO start-date metadata.
	 */
	public static function order_events_admin_list( WP_Query $query ): void {
		if (
			! is_admin() ||
			! $query->is_main_query() ||
			'lgsdn_event' !== $query->get( 'post_type' ) ||
			'lgsdn_start_at' !== $query->get( 'orderby' )
		) {
			return;
		}

		$query->set( 'meta_key', 'lgsdn_start_at' );
		$query->set( 'orderby', 'meta_value' );
	}

	/**
	 * Register metadata for REST API and block editor compatibility.
	 */
	public static function register_meta(): void {
		foreach ( self::FIELD_GROUPS as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				$is_boolean = 'checkbox' === $field[1];
				$is_integer = in_array( $field[1], array( 'person-select', 'service-select', 'practice-select' ), true );
				$is_resources = 'resource-links' === $field[1];
				$show_in_rest = true;
				if ( $is_resources ) {
					$show_in_rest = array(
						'schema' => array(
							'type' => 'array',
							'items' => array(
								'type' => 'object',
								'properties' => array(
									'label' => array( 'type' => 'string' ),
									'url' => array( 'type' => 'string', 'format' => 'uri' ),
									'attachment_id' => array( 'type' => 'integer' ),
								),
							),
						),
					);
				}
				register_post_meta(
					$post_type,
					$key,
					array(
						'single' => true,
						'type' => $is_resources ? 'array' : ( $is_boolean ? 'boolean' : ( $is_integer ? 'integer' : 'string' ) ),
						'show_in_rest' => $show_in_rest,
						'default' => $is_resources ? array() : ( $is_boolean ? false : ( $is_integer ? 0 : '' ) ),
						'sanitize_callback' => $is_resources ? array( __CLASS__, 'sanitize_resources' ) : ( $is_boolean ? 'rest_sanitize_boolean' : ( $is_integer ? 'absint' : array( __CLASS__, 'sanitize_meta' ) ) ),
						'auth_callback' => static function (): bool {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	public static function sanitize_meta( mixed $value, string $key = '' ): string {
		if ( str_ends_with( $key, '_url' ) ) {
			return esc_url_raw( (string) $value );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Keep only complete, safe event resource links.
	 *
	 * @return array<int, array{label:string,url:string,attachment_id:int}>
	 */
	public static function sanitize_resources( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$resources = array();
		foreach ( array_slice( $value, 0, 20 ) as $resource ) {
			if ( ! is_array( $resource ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $resource['label'] ?? '' ) );
			$url = esc_url_raw( (string) ( $resource['url'] ?? '' ) );
			$attachment_id = absint( $resource['attachment_id'] ?? 0 );
			if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) ) {
				$attachment_url = wp_get_attachment_url( $attachment_id );
				$url = $attachment_url ? esc_url_raw( $attachment_url ) : $url;
			} else {
				$attachment_id = 0;
			}
			if ( '' === $label || '' === $url ) {
				continue;
			}

			$resources[] = array( 'label' => $label, 'url' => $url, 'attachment_id' => $attachment_id );
		}

		return $resources;
	}

	public static function add_meta_boxes(): void {
		foreach ( array_keys( self::FIELD_GROUPS ) as $post_type ) {
			add_meta_box(
				'lgsdn-details',
				self::box_title( $post_type ),
				array( __CLASS__, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	public static function render( WP_Post $post ): void {
		wp_nonce_field( 'lgsdn_save_fields', 'lgsdn_fields_nonce' );
		$fields = self::FIELD_GROUPS[ $post->post_type ] ?? array();

		echo '<table class="form-table" role="presentation"><tbody>';
		foreach ( $fields as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td>';
			self::render_control( $key, $field[1], $value );
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function render_control( string $key, string $type, mixed $value ): void {
		if ( 'resource-links' === $type ) {
			$resources = self::sanitize_resources( $value );
			$resources = $resources ?: array( array( 'label' => '', 'url' => '', 'attachment_id' => 0 ) );
			echo '<div class="lgsdn-event-resources-editor" data-event-resources data-field-name="' . esc_attr( $key ) . '">';
			echo '<div data-event-resource-rows>';
			foreach ( $resources as $index => $resource ) {
				self::render_resource_row( $key, (string) $index, $resource );
			}
			echo '</div>';
			echo '<button type="button" class="button" data-add-event-resource>Add resource</button>';
			echo '<p class="description">Enter an external URL or choose an uploaded file from the Media Library.</p>';
			echo '<template data-event-resource-template>';
			self::render_resource_row( $key, '__INDEX__', array( 'label' => '', 'url' => '', 'attachment_id' => 0 ) );
			echo '</template></div>';
			return;
		}

		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( (bool) $value, true, false ) . '> Show this item in the featured homepage position</label>';
			return;
		}

		if ( 'select' === $type ) {
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( array( '' => 'Select a format', 'in-person' => 'In person', 'online' => 'Online', 'hybrid' => 'Hybrid' ) as $option => $label ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			return;
		}

		if ( 'person-select' === $type ) {
			$people = get_posts(
				array(
					'post_type' => 'lgsdn_person',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			echo '<option value="">Select a person</option>';
			foreach ( $people as $person ) {
				echo '<option value="' . esc_attr( (string) $person->ID ) . '" ' . selected( absint( $value ), $person->ID, false ) . '>' . esc_html( $person->post_title ) . '</option>';
			}
			echo '</select>';
			echo '<p class="description">The person credited publicly on this case study. Add them under People first.</p>';
			return;
		}

		if ( 'practice-select' === $type ) {
			$practices = get_terms(
				array(
					'taxonomy' => 'lgsdn_practice',
					'hide_empty' => false,
					'orderby' => 'name',
					'order' => 'ASC',
				)
			);
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			echo '<option value="">Select a primary practice</option>';
			if ( ! is_wp_error( $practices ) ) {
				foreach ( $practices as $practice ) {
					echo '<option value="' . esc_attr( (string) $practice->term_id ) . '" ' . selected( absint( $value ), $practice->term_id, false ) . '>' . esc_html( $practice->name ) . '</option>';
				}
			}
			echo '</select>';
			echo '<p class="description">Optional. The primary service area is required and controls the service-area card colour.</p>';
			return;
		}

		if ( 'service-select' === $type ) {
			$services = get_terms(
				array(
					'taxonomy' => 'lgsdn_service',
					'hide_empty' => false,
					'orderby' => 'name',
					'order' => 'ASC',
				)
			);
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			echo '<option value="">Select a primary service area</option>';
			if ( ! is_wp_error( $services ) ) {
				foreach ( $services as $service ) {
					echo '<option value="' . esc_attr( (string) $service->term_id ) . '" ' . selected( absint( $value ), $service->term_id, false ) . '>' . esc_html( $service->name ) . '</option>';
				}
			}
			echo '</select>';
			echo '<p class="description">This must also be assigned in Service areas. It controls the primary grouping for the item.</p>';
			return;
		}

		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '">';
	}

	/**
	 * Render one editable event resource row.
	 *
	 * @param array{label:string,url:string,attachment_id:int} $resource Resource values.
	 */
	private static function render_resource_row( string $key, string $index, array $resource ): void {
		$name = esc_attr( $key . '[' . $index . ']' );
		echo '<div class="lgsdn-event-resource-row" data-event-resource-row style="display:grid;grid-template-columns:minmax(10rem,1fr) minmax(16rem,2fr) auto auto;gap:8px;margin-bottom:8px;align-items:center">';
		echo '<input type="hidden" name="' . $name . '[attachment_id]" value="' . esc_attr( (string) $resource['attachment_id'] ) . '" data-event-resource-attachment-id>';
		echo '<input type="text" name="' . $name . '[label]" value="' . esc_attr( $resource['label'] ) . '" placeholder="Presentation slides" aria-label="Resource label" data-event-resource-label>';
		echo '<input type="url" name="' . $name . '[url]" value="' . esc_attr( $resource['url'] ) . '" placeholder="https://" aria-label="Resource URL" data-event-resource-url>';
		echo '<button type="button" class="button" data-choose-event-resource>Choose file</button>';
		echo '<button type="button" class="button-link-delete" data-remove-event-resource>Remove</button>';
		echo '</div>';
	}

	public static function save( int $post_id ): void {
		$post_type = get_post_type( $post_id );
		if (
			! isset( self::FIELD_GROUPS[ $post_type ] ) ||
			! isset( $_POST['lgsdn_fields_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lgsdn_fields_nonce'] ) ), 'lgsdn_save_fields' ) ||
			wp_is_post_autosave( $post_id ) ||
			wp_is_post_revision( $post_id ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		foreach ( self::FIELD_GROUPS[ $post_type ] as $key => $field ) {
			if ( 'checkbox' === $field[1] ) {
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) );
				continue;
			}

			if ( 'resource-links' === $field[1] ) {
				$value = isset( $_POST[ $key ] ) ? self::sanitize_resources( wp_unslash( $_POST[ $key ] ) ) : array();
				if ( empty( $value ) ) {
					delete_post_meta( $post_id, $key );
				} else {
					update_post_meta( $post_id, $key, $value );
				}
				continue;
			}

			$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			if ( in_array( $field[1], array( 'person-select', 'service-select', 'practice-select' ), true ) ) {
				$value = absint( $value );
			} else {
				$value = 'url' === $field[1] ? esc_url_raw( $value ) : sanitize_text_field( $value );
			}

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	private static function box_title( string $post_type ): string {
		return match ( $post_type ) {
			'lgsdn_person' => 'Person details',
			'lgsdn_playbook' => 'Playbook details',
			'lgsdn_event' => 'Event details',
			default => 'Details',
		};
	}
}
