( function ( blocks, element, blockEditor, components, data, config ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;

	function Edit() {
		var blockProps = blockEditor.useBlockProps( {
			className: 'lgsdn-playbook-index alignfull',
		} );
		var meta = data.useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var editor = data.useDispatch( 'core/editor' );
		var defaults = config.defaults || {};

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

		return el(
			Fragment,
			null,
			el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: 'Contribution banner link', initialOpen: true },
					el( TextControl, {
						label: 'Button link',
						help: 'Use a page path such as /contribute/ or a complete web address.',
						value: value( 'lgsdn_playbook_contribute_button_url' ),
						onChange: function ( nextValue ) {
							updateMeta( 'lgsdn_playbook_contribute_button_url', nextValue );
						},
					} )
				)
			),
			el(
				'div',
				blockProps,
				el(
					'section',
					{ className: 'lgsdn-playbook-contribute', 'aria-label': 'Editable contribution banner' },
					el(
						'div',
						{ className: 'lgsdn-playbook-contribute__inner lgsdn-playbook-shell' },
						el(
							'div',
							{ className: 'lgsdn-playbook-contribute__copy' },
							el( RichText, {
								tagName: 'h2',
								value: value( 'lgsdn_playbook_contribute_title' ),
								onChange: function ( nextValue ) {
									updateMeta( 'lgsdn_playbook_contribute_title', nextValue );
								},
								allowedFormats: [],
								placeholder: 'Banner heading',
							} ),
							el( RichText, {
								tagName: 'p',
								value: value( 'lgsdn_playbook_contribute_body' ),
								onChange: function ( nextValue ) {
									updateMeta( 'lgsdn_playbook_contribute_body', nextValue );
								},
								allowedFormats: [],
								placeholder: 'Banner text',
							} )
						),
						el(
							'a',
							{ className: 'button button--strong button--large lgsdn-button--arrow', href: '#', onClick: stopLink },
							el( RichText, {
								tagName: 'span',
								className: 'lgsdn-button__label',
								value: value( 'lgsdn_playbook_contribute_button_label' ),
								onChange: function ( nextValue ) {
									updateMeta( 'lgsdn_playbook_contribute_button_label', nextValue );
								},
								allowedFormats: [],
								placeholder: 'Button text',
							} )
						)
					)
				),
				el( 'p', { className: 'lgsdn-playbook-editor-note' }, 'Service areas and case studies are generated automatically on the published page.' )
			)
		);
	}

	blocks.registerBlockType( 'lgsdn/playbook-index', {
		edit: Edit,
		save: function () {
			return null;
		},
	} );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data, window.lgsdnPlaybookEditor || {} ) );
