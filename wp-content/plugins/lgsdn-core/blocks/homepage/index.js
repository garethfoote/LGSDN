( function ( blocks, element, blockEditor, components, data, ServerSideRender, config ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;

	function editableText( tagName, className, value, onChange, placeholder ) {
		return el( RichText, {
			tagName: tagName,
			className: className,
			value: value,
			onChange: onChange,
			allowedFormats: [],
			placeholder: placeholder,
		} );
	}

	function Edit() {
		var blockProps = blockEditor.useBlockProps( {
			className: 'homepage-render alignfull',
		} );
		var meta = data.useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var pages = data.useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'postType', 'page', {
				per_page: -1,
				status: 'publish',
				orderby: 'title',
				order: 'asc',
			} ) || [];
		}, [] );
		var editor = data.useDispatch( 'core/editor' );
		var defaults = config.defaults || {};
		var imageBase = config.imageBase || '';

		function value( key ) {
			return undefined !== meta[ key ] && '' !== meta[ key ] ? meta[ key ] : ( defaults[ key ] || '' );
		}

		function updateMeta( key, nextValue ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ key ] = nextValue;
			editor.editPost( { meta: nextMeta } );
		}

		function stopLink( event ) {
			event.preventDefault();
		}

		var pageOptions = [
			{ label: 'Select a page', value: 0 },
		].concat( pages.map( function ( page ) {
			return { label: page.title.rendered, value: page.id };
		} ) );

		var features = [ 1, 2, 3 ].map( function ( index ) {
			var titleKey = 'lgsdn_home_feature_' + index + '_title';
			var bodyKey = 'lgsdn_home_feature_' + index + '_body';
			var title = value( titleKey );
			var ctaLabels = [ 'Join', 'Browse', 'Contribute' ];
			var ctaContext = [ ' the network', ' the playbook', ' an example' ];
			var artNames = [ 'join', 'playbook', 'contribute' ];

			return el(
				'article',
				{ className: 'homepage-feature-row homepage-feature-row--' + artNames[ index - 1 ], key: index },
				el(
					'div',
					{ className: 'homepage-feature-row__copy' },
					editableText( 'h2', '', title, function ( nextValue ) {
						updateMeta( titleKey, nextValue );
					}, 'Feature title' ),
					editableText( 'p', '', value( bodyKey ), function ( nextValue ) {
						updateMeta( bodyKey, nextValue );
					}, 'Feature description' ),
					el( 'a', { className: 'button lgsdn-button--arrow', href: '#', onClick: stopLink }, el( 'span', { className: 'lgsdn-button__label' }, ctaLabels[ index - 1 ] ), el( 'span', { className: 'screen-reader-text' }, ctaContext[ index - 1 ] ) )
				),
				el( 'svg', { className: 'homepage-feature-row__art', viewBox: '0 0 226 226', 'aria-hidden': true, focusable: false },
					el( 'use', { href: imageBase + '/home-feature-' + artNames[ index - 1 ] + '-contours.svg#contours' } )
				)
			);
		} );

		function editIntroCard( className, titleKey, bodyKey, buttonKey ) {
			return el(
				'article',
				{ className: className },
				editableText( 'h4', '', value( titleKey ), function ( nextValue ) {
					updateMeta( titleKey, nextValue );
				}, 'Intro card title' ),
				editableText( 'p', '', value( bodyKey ), function ( nextValue ) {
					updateMeta( bodyKey, nextValue );
				}, 'Intro card description' ),
				el(
					'a',
					{ className: 'button button--strong lgsdn-button--arrow', href: '#', onClick: stopLink },
					editableText( 'span', 'lgsdn-button__label', value( buttonKey ), function ( nextValue ) {
						updateMeta( buttonKey, nextValue );
					}, 'Button label' )
				)
			);
		}

		var homepagePlaybook = el(
			'section',
			{ className: 'homepage-playbook', id: 'playbook', 'aria-labelledby': 'homepage-playbook-title' },
			el( 'div', { className: 'homepage-playbook__intro' }, el( 'h2', { id: 'homepage-playbook-title' }, 'From the playbook' ) ),
			el( 'div', { className: 'homepage-service-row', 'aria-label': 'Explore by service area' },
				editIntroCard( 'homepage-service-intro', 'lgsdn_home_service_intro_title', 'lgsdn_home_service_intro_body', 'lgsdn_home_service_intro_button' )
			),
			el( 'div', { className: 'homepage-case-study-row' },
				editIntroCard( 'homepage-service-intro homepage-case-study-intro', 'lgsdn_home_case_study_intro_title', 'lgsdn_home_case_study_intro_body', 'lgsdn_home_case_study_intro_button' )
			)
		);

		var linkControls = [ 1, 2, 3 ].map( function ( index ) {
			var pageKey = 'lgsdn_home_feature_' + index + '_page_id';
			return el( SelectControl, {
				key: index,
				label: 'Feature ' + index + ' destination',
				value: Number( meta[ pageKey ] || 0 ),
				options: pageOptions,
				onChange: function ( nextValue ) {
					updateMeta( pageKey, Number( nextValue ) );
				},
			} );
		} );

		return el(
			Fragment,
			null,
			el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: 'Homepage links', initialOpen: true },
					el( 'p', { className: 'components-base-control__help' }, 'Optional overrides. Leave a destination unselected to keep its built-in page, or select a page to send visitors elsewhere.' ),
					linkControls
				)
			),
			el(
				'div',
				blockProps,
				el( 'a', { className: 'skip-link', href: '#main-content', onClick: stopLink }, 'Skip to main content' ),
				el(
					'div',
					{ className: 'site-frame' },
					el(
						'header',
						{ className: 'site-header' },
						el(
							'a',
							{ className: 'site-logo', href: '#', onClick: stopLink, 'aria-label': 'Local Government Service Design Network home' },
							el( 'img', { className: 'site-logo__lg', src: imageBase + '/logo-lg.svg', alt: '' } ),
							el( 'img', { className: 'site-logo__sdn', src: imageBase + '/logo-sdn.svg', alt: '' } )
						),
						el(
							'nav',
							{ className: 'site-nav', 'aria-label': 'Main navigation' },
							el( 'a', { href: '#', onClick: stopLink }, 'About' ),
							el( 'a', { href: '#', onClick: stopLink }, 'Playbook' ),
							el( 'a', { href: '#', onClick: stopLink }, 'Events' ),
							el( 'a', { href: '#', onClick: stopLink }, 'People' ),
							el( 'a', { className: 'button button--nav', href: '#', onClick: stopLink }, 'Join' )
						),
						el(
							'button',
							{ className: 'menu-button', type: 'button', 'aria-label': 'Open menu' },
							el( 'span' ),
							el( 'span' ),
							el( 'span' )
						)
					),
					el(
						'main',
						{ id: 'main-content' },
						el(
							'section',
							{ className: 'home-hero', id: 'about' },
							el(
								'div',
								{ className: 'home-hero__copy' },
								el( 'h1', { className: 'home-hero__title' }, 'Local Government', el( 'br' ), 'Service Design Network' ),
								editableText( 'p', 'home-hero__intro', value( 'lgsdn_home_lead' ), function ( nextValue ) {
									updateMeta( 'lgsdn_home_lead', nextValue );
								}, 'Homepage introduction' )
							),
							el(
								'div',
								{ className: 'home-hero__graphic', 'aria-hidden': true },
								el(
									'div',
									{ className: 'home-hero__graphic-disc' },
									el( 'img', { src: imageBase + '/hero-graphic-mark.svg', alt: '' } )
								)
							)
						),
						el( 'section', { className: 'homepage-feature-list', id: 'join', 'aria-label': 'Ways to take part' }, features ),
						homepagePlaybook,
						el(
							'div',
							{ id: 'events' },
							el( ServerSideRender, { block: 'lgsdn/events-list' } )
						)
					)
				),
				el(
					'footer',
					{ className: 'lgsdn-site-footer' },
					el(
						'div',
						{ className: 'lgsdn-site-footer__inner' },
						el(
							'a',
							{ className: 'lgsdn-site-footer__logo', href: '#', onClick: stopLink, 'aria-label': 'Local Government Service Design Network home' },
							el( 'img', { src: imageBase + '/logo.svg', alt: '' } )
						),
						el(
							'nav',
							{ className: 'lgsdn-site-footer__nav', 'aria-label': 'Footer navigation' },
							el( 'a', { href: '#', onClick: stopLink }, 'Accessibility statement' ),
							el( 'a', { href: '#', onClick: stopLink }, 'Join the network' ),
							el( 'a', { href: '#', onClick: stopLink }, 'GitHub' )
						),
						el(
							'p',
							{ className: 'lgsdn-site-footer__copyright' },
							'Except where otherwise stated, original content published by the Local Government Service Design Network is available under the ',
							el( 'a', { href: 'https://creativecommons.org/licenses/by/4.0/', rel: 'license', onClick: stopLink }, 'Creative Commons Attribution 4.0 International licence' ),
							'.'
						)
					)
				)
			)
		);
	}

	blocks.registerBlockType( 'lgsdn/homepage', {
		edit: Edit,
		save: function () {
			return null;
		},
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.wp.serverSideRender, window.lgsdnHomepageEditor || {} ) );
