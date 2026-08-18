( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.data ) {
		return;
	}

	const notices = wp.data.dispatch( 'core/notices' );
	const select = wp.data.select;
	const editorDispatch = wp.data.dispatch( 'core/editor' );
	const structuredMetaFields = new Set( [
		'lgsdn_contributor_id',
		'lgsdn_primary_service_id',
	] );
	let lastNoticeKey = '';

	const syncStructuredMetaField = ( field, allowEmpty = false ) => {
		if ( ! field || ! structuredMetaFields.has( field.name ) || ! editorDispatch || ! editorDispatch.editPost ) {
			return;
		}

		if ( ! field.value && ! allowEmpty ) {
			return;
		}

		const editorSelect = select( 'core/editor' );
		if ( ! editorSelect || ! editorSelect.getEditedPostAttribute ) {
			return;
		}

		const currentMeta = editorSelect.getEditedPostAttribute( 'meta' ) || {};
		const value = field.value ? Number.parseInt( field.value, 10 ) : 0;

		if ( currentMeta[ field.name ] === value ) {
			return;
		}

		editorDispatch.editPost( {
			meta: {
				...currentMeta,
				[ field.name ]: Number.isNaN( value ) ? 0 : value,
			},
		} );
	};

	const syncVisibleStructuredMetaFields = ( attempt = 0 ) => {
		if ( typeof document === 'undefined' ) {
			return;
		}

		document.querySelectorAll( '[name="lgsdn_contributor_id"], [name="lgsdn_primary_service_id"]' ).forEach( ( field ) => {
			syncStructuredMetaField( field );
		} );

		if ( attempt < 20 && typeof window !== 'undefined' ) {
			window.setTimeout( () => syncVisibleStructuredMetaFields( attempt + 1 ), 50 );
		}
	};

	if ( typeof document !== 'undefined' && document.addEventListener ) {
		document.addEventListener( 'change', ( event ) => {
			syncStructuredMetaField( event.target, true );
		} );
		syncVisibleStructuredMetaFields();
	}

	const getValidationMessage = ( error ) => {
		const missingFields = error && error.data && Array.isArray( error.data.missing_fields )
			? error.data.missing_fields
			: [];

		return missingFields.length
			? `Publishing failed. Complete these fields first: ${ missingFields.join( ', ' ) }.`
			: ( error && error.message ) || 'Publishing failed because the Playbook item is incomplete.';
	};

	const renderSummaryMessage = ( message, attempt = 0 ) => {
		const noticeNodes = Array.from( document.querySelectorAll( '.components-notice__content' ) );
		const summary = noticeNodes.find( ( notice ) => /^Publishing failed\.?/i.test( notice.textContent.trim() ) );

		if ( summary ) {
			summary.textContent = message;
			summary.dataset.lgsdnValidationError = 'true';
			return true;
		}

		if ( attempt < 20 ) {
			window.setTimeout( () => renderSummaryMessage( message, attempt + 1 ), 50 );
		} else {
			notices.createErrorNotice( message, {
				id: 'lgsdn-playbook-validation',
				isDismissible: true,
			} );
		}

		return false;
	};

	const showValidationError = ( error ) => {
		if ( ! error || 'lgsdn_playbook_incomplete' !== error.code ) {
			return;
		}

		const message = getValidationMessage( error );
		const noticeKey = `${ error.code }:${ message }`;

		if ( noticeKey === lastNoticeKey ) {
			return;
		}

		lastNoticeKey = noticeKey;
		renderSummaryMessage( message );
	};

	if ( wp.apiFetch && wp.apiFetch.use ) {
		wp.apiFetch.use( ( options, next ) => next( options ).catch( ( error ) => {
			showValidationError( error );
			throw error;
		} ) );
	}

	const getSaveError = () => {
		const editor = select( 'core/editor' );
		const core = select( 'core' );
		const postId = editor && editor.getCurrentPostId ? editor.getCurrentPostId() : 0;

		if ( ! core || ! core.getLastEntitySaveError ) {
			return editor && editor.getLastPostSaveError ? editor.getLastPostSaveError() : null;
		}

		return core.getLastEntitySaveError( 'postType', 'lgsdn_playbook', postId )
			|| ( editor && editor.getLastPostSaveError ? editor.getLastPostSaveError() : null );
	};

	const showStoredValidationError = () => {
		const error = getSaveError();
		showValidationError( error );
	};

	wp.data.subscribe( showStoredValidationError );

	showStoredValidationError();
}( window.wp ) );
