( function () {
	'use strict';

	document.querySelectorAll( '[data-event-resources]' ).forEach( function ( editor ) {
		var rows = editor.querySelector( '[data-event-resource-rows]' );
		var template = editor.querySelector( '[data-event-resource-template]' );
		var nextIndex = rows.querySelectorAll( '[data-event-resource-row]' ).length;

		editor.querySelector( '[data-add-event-resource]' ).addEventListener( 'click', function () {
			var markup = template.innerHTML.replaceAll( '__INDEX__', String( nextIndex++ ) );
			rows.insertAdjacentHTML( 'beforeend', markup );
			rows.lastElementChild.querySelector( 'input' ).focus();
		} );

		editor.addEventListener( 'click', function ( event ) {
			var remove = event.target.closest( '[data-remove-event-resource]' );
			if ( remove ) {
				remove.closest( '[data-event-resource-row]' ).remove();
				return;
			}

			var choose = event.target.closest( '[data-choose-event-resource]' );
			if ( ! choose || ! window.wp || ! wp.media ) {
				return;
			}

			var row = choose.closest( '[data-event-resource-row]' );
			var frame = wp.media( {
				title: 'Choose resource file',
				button: { text: 'Use this file' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var label = row.querySelector( '[data-event-resource-label]' );
				row.querySelector( '[data-event-resource-attachment-id]' ).value = attachment.id;
				row.querySelector( '[data-event-resource-url]' ).value = attachment.url;
				if ( ! label.value.trim() ) {
					label.value = attachment.title || attachment.filename || '';
				}
			} );

			frame.open();
		} );

		editor.addEventListener( 'input', function ( event ) {
			if ( ! event.target.matches( '[data-event-resource-url]' ) ) {
				return;
			}

			event.target.closest( '[data-event-resource-row]' ).querySelector( '[data-event-resource-attachment-id]' ).value = '';
		} );
	} );
}() );
