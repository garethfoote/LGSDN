<?php
/** Editable website information and a minimal Forminator feedback form. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LGSDN_Website_Pages {
	public static function hooks(): void {
		add_action( 'admin_init', array( __CLASS__, 'install' ) );
		add_shortcode( 'lgsdn_feedback_form', array( __CLASS__, 'form' ) );
		add_shortcode( 'lgsdn_analytics_choice', array( __CLASS__, 'analytics_choice' ) );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'respect_analytics_choice' ), 10, 3 );
	}

	public static function install(): void {
		foreach ( array( 'feedback' => 'Website feedback', 'about-this-website' => 'About this website', 'privacy' => 'Privacy and analytics' ) as $slug => $title ) {
			if ( get_page_by_path( $slug, OBJECT, 'page' ) instanceof WP_Post ) {
				continue;
			}
			$path = get_theme_file_path( 'patterns/' . $slug . '-content.php' );
			if ( ! is_readable( $path ) ) {
				continue;
			}
			ob_start();
			include $path;
			$content = trim( (string) ob_get_clean() );
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ), true );
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_wp_page_template', 'page-contribute' );
			}
		}
		self::install_form();
		$form_id = absint( get_option( 'lgsdn_feedback_form_id' ) );
		if ( $form_id && class_exists( 'Forminator_Form_Model' ) ) {
			$model = Forminator_Form_Model::model()->load( $form_id );
			if ( $model && empty( $model->behaviors ) ) {
				$model->behaviors = array( array( 'slug' => 'lgsdn-feedback-confirmation', 'label' => 'Thank you', 'submission-behaviour' => 'behaviour-thankyou', 'thankyou-message' => 'Thank you. Your feedback will help us improve this website.', 'conditions' => array() ) );
				$model->save();
			}
		}
		if ( class_exists( 'IAWP\\Independent_Analytics' ) && ! get_option( 'lgsdn_analytics_configured' ) ) {
			update_option( 'iawp_visitor_salt_refresh_interval', 'daily' );
			update_option( 'iawp_track_authenticated_users', false );
			update_option( 'iawp_geo_provider', 'database' );
			update_option( 'lgsdn_analytics_configured', true, false );
		}
	}

	/** Enforce preferences at collection time, including pages served from cache. */
	public static function respect_analytics_choice( $result, $server, $request ) {
		if ( ! in_array( $request->get_route(), array( '/iawp/search' ), true ) ) {
			return $result;
		}
		if ( '1' === ( $_COOKIE['lgsdn_analytics_opt_out'] ?? '' ) || '1' === ( $_SERVER['HTTP_DNT'] ?? '' ) || '1' === ( $_SERVER['HTTP_SEC_GPC'] ?? '' ) ) {
			return new WP_REST_Response( null, 204 );
		}
		return $result;
	}

	public static function analytics_choice(): string {
		wp_enqueue_script( 'lgsdn-analytics-choice', get_theme_file_uri( 'assets/js/analytics-choice.js' ), array(), LGSDN_CORE_VERSION, true );
		return '<div class="lgsdn-analytics-choice"><p data-analytics-status role="status">You can stop this browser’s visits being included in analytics.</p><button class="button" type="button" data-analytics-opt-out hidden>Stop counting my visits</button> <button class="button" type="button" data-analytics-opt-in hidden>Allow counting my visits</button><noscript><p>Email <a href="mailto:hello@gfoot.me">hello@gfoot.me</a> if you need help with an analytics preference.</p></noscript></div>';
	}

	private static function install_form(): void {
		if ( get_option( 'lgsdn_feedback_form_id' ) || ! class_exists( 'Forminator_API' ) ) {
			return;
		}
		$wrappers = array();
		foreach ( array(
			array( 'text-1', 'text', 'What brought you to the website? (optional)', false ),
			array( 'textarea-1', 'textarea', 'What worked well, or what could we improve?', true ),
			array( 'textarea-2', 'textarea', 'Has anything here helped your work? (optional)', false ),
			array( 'email-1', 'email', 'Email address, if you would like a reply (optional)', false ),
		) as $index => $definition ) {
			list( $id, $type, $label, $required ) = $definition;
			$wrapper = 'wrapper-lgsdn-feedback-' . $index;
			$field = array( 'id' => $id, 'element_id' => $id, 'type' => $type, 'cols' => '12', 'required' => $required ? 'true' : '', 'field_label' => $label, 'wrapper_id' => $wrapper );
			if ( 'textarea' === $type ) {
				$field['input_type'] = 'paragraph';
				$field['limit'] = '3000';
				$field['limit_type'] = 'characters';
			}
			$wrappers[] = array( 'wrapper_id' => $wrapper, 'fields' => array( $field ) );
		}
		$settings = array(
			'formName' => 'Website feedback', 'form-style' => 'default', 'enable-ajax' => 'true',
			'validation' => 'on_submit', 'validation-inline' => '1', 'store_submissions' => '',
			'form-expire' => 'no_expire', 'honeypot' => '1', 'submission-behaviour' => 'behaviour-thankyou',
			'thankyou-message' => 'Thank you. Your feedback will help us improve this website.',
			'submitData' => array( 'custom-submit-text' => 'Send feedback', 'custom-invalid-form-message' => 'Please check the highlighted fields and try again.' ),
			'submission-indicator' => 'show', 'indicator-label' => 'Sending feedback…',
		);
		$id = Forminator_API::add_form( 'Website feedback', $wrappers, $settings );
		if ( is_wp_error( $id ) ) {
			return;
		}
		$notifications = array( array(
			'slug' => 'lgsdn-feedback', 'label' => 'Website feedback', 'email-recipients' => 'default',
			'recipients' => get_option( 'admin_email' ), 'email-subject' => 'LGSDN website feedback',
			'email-editor' => '{all_fields}', 'email-attachment' => 'false',
		) );
		$result = Forminator_API::update_form( $id, $wrappers, $settings, 'publish', $notifications );
		if ( ! is_wp_error( $result ) ) {
			update_option( 'lgsdn_feedback_form_id', $id, false );
		}
	}

	public static function form(): string {
		$id = absint( get_option( 'lgsdn_feedback_form_id' ) );
		if ( ! $id || ! shortcode_exists( 'forminator_form' ) ) {
			return '<p>The feedback form is temporarily unavailable. Please try again later.</p>';
		}
		return do_shortcode( '[forminator_form id="' . $id . '"]' );
	}
}
