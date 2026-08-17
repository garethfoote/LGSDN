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
			var ctaSuffixes = [ ' the network', ' the playbook', ' an example' ];

			return el(
				'article',
				{ className: 'feature-card', key: index },
				editableText( 'h2', '', title, function ( nextValue ) {
					updateMeta( titleKey, nextValue );
				}, 'Feature title' ),
				editableText( 'p', '', value( bodyKey ), function ( nextValue ) {
					updateMeta( bodyKey, nextValue );
				}, 'Feature description' ),
				el( 'a', { className: 'button lgsdn-button--arrow', href: '#', onClick: stopLink }, el( 'span', { className: 'lgsdn-button__label' }, ctaLabels[ index - 1 ] ), el( 'span', { className: 'screen-reader-text' }, ctaSuffixes[ index - 1 ] ) )
			);
		} );

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
								el( 'h1', { className: 'home-hero__title' }, 'Local Government Service Design Network' ),
								editableText( 'p', 'home-hero__intro', value( 'lgsdn_home_lead' ), function ( nextValue ) {
									updateMeta( 'lgsdn_home_lead', nextValue );
								}, 'Homepage introduction' )
							),
							el(
								'div',
								{ className: 'home-hero__graphic', 'aria-hidden': true },
								el( 'img', { src: imageBase + '/home-contour.svg', alt: '' } )
							)
						),
						el( 'section', { className: 'feature-grid', id: 'join', 'aria-label': 'Ways to take part' }, features ),
						el(
							'article',
							{ className: 'spotlight spotlight--practice-purple', id: 'playbook' },
							el(
								'div',
								{ className: 'spotlight__media' },
								el( 'img', {
									className: 'spotlight__image',
									src: imageBase + '/home-feature-image.png',
									alt: 'Three people working together around a table covered with notes and prototypes',
								} ),
								el(
									'div',
									{ className: 'spotlight__tags', 'aria-label': 'Case study classifications' },
									el( 'span', { className: 'tag tag--practice' }, 'User Research' ),
									el(
										'span',
										{ className: 'tag', 'aria-label': 'Service: Adult Social Care' },
										el( 'img', {
											className: 'taxonomy-tag-icon',
											src: imageBase + '/taxonomy-service.svg',
											alt: '',
											width: 16,
											height: 16,
										} ),
										'Adult Social Care'
									),
									el(
										'span',
										{ className: 'tag', 'aria-label': 'Challenge: Silos' },
										el( 'img', {
											className: 'taxonomy-tag-icon',
											src: imageBase + '/taxonomy-challenge.svg',
											alt: '',
											width: 16,
											height: 16,
										} ),
										'Silos'
									)
								)
							),
							el(
								'div',
								{ className: 'spotlight__content' },
								el( 'p', { className: 'spotlight__eyebrow' }, 'From the playbook' ),
								el( 'h2', null, 'Digital prototyping: Shaping a Platform with Parents' ),
								el( 'p', null, 'A short description of this featured item goes here.' ),
								el(
									'div',
									{ className: 'button-list' },
									el( 'a', { className: 'button button--strong lgsdn-button--arrow', href: '#', onClick: stopLink }, el( 'span', { className: 'lgsdn-button__label' }, 'Read the case study' ) ),
									el( 'a', { className: 'button lgsdn-button--arrow', href: '#', onClick: stopLink }, el( 'span', { className: 'lgsdn-button__label' }, 'See the full playbook' ) )
								)
							)
						),
						el(
							'div',
							{ id: 'events' },
							el( ServerSideRender, { block: 'lgsdn/events-list' } )
						)
					)
				),
				el( 'footer', { className: 'site-footer' } )
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
