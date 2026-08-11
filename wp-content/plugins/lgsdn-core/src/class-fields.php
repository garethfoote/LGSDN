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
			'lgsdn_primary_practice_id' => array( 'Primary practice', 'practice-select' ),
			'lgsdn_reviewed_on' => array( 'Last reviewed', 'date' ),
			'lgsdn_resource_url' => array( 'Resource link', 'url' ),
			'lgsdn_featured_home' => array( 'Feature on the homepage', 'checkbox' ),
		),
		'lgsdn_event' => array(
			'lgsdn_start_at' => array( 'Starts', 'datetime-local' ),
			'lgsdn_end_at' => array( 'Ends', 'datetime-local' ),
			'lgsdn_location' => array( 'Location', 'text' ),
			'lgsdn_event_mode' => array( 'Format', 'select' ),
			'lgsdn_booking_url' => array( 'Booking link (optional)', 'url' ),
		),
	);

	public static function hooks(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	/**
	 * Register metadata for REST API and block editor compatibility.
	 */
	public static function register_meta(): void {
		foreach ( self::FIELD_GROUPS as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				$is_boolean = 'checkbox' === $field[1];
				$is_integer = in_array( $field[1], array( 'person-select', 'practice-select' ), true );
				register_post_meta(
					$post_type,
					$key,
					array(
						'single' => true,
						'type' => $is_boolean ? 'boolean' : ( $is_integer ? 'integer' : 'string' ),
						'show_in_rest' => true,
						'default' => $is_boolean ? false : ( $is_integer ? 0 : '' ),
						'sanitize_callback' => $is_boolean ? 'rest_sanitize_boolean' : ( $is_integer ? 'absint' : array( __CLASS__, 'sanitize_meta' ) ),
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
			echo '<p class="description">This must also be assigned in Practices. Its colour controls the card colour and fallback contour.</p>';
			return;
		}

		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '">';
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

			$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			if ( in_array( $field[1], array( 'person-select', 'practice-select' ), true ) ) {
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
