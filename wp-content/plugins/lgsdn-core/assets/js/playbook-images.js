( function ( wp ) {
	'use strict';

	const settings = window.lgsdnPlaybookImages;
	if ( ! wp || ! wp.media || ! settings ) return;

	const NativeCropper = wp.media.controller.Cropper.extend( {
		doCrop: function ( attachment ) {
			return wp.ajax.post( 'lgsdn_crop_playbook_image', {
				nonce: settings.nonce,
				post_id: this.get( 'postId' ),
				slot: this.get( 'slot' ),
				id: attachment.id,
				cropDetails: attachment.get( 'cropDetails' ),
			} );
		},
		removeCropper: function () {
			if ( this.imgSelect && this.cropperView ) {
				wp.media.controller.Cropper.prototype.removeCropper.call( this );
				this.imgSelect = null;
			}
		},
	} );

	document.querySelectorAll( '[data-playbook-image]' ).forEach( function ( picker ) {
		const slot = picker.dataset.slot;
		const dimensions = settings.slots[ slot ];
		const field = picker.querySelector( '[data-image-id]' );
		const preview = picker.querySelector( '[data-image-preview]' );
		const message = picker.querySelector( '[data-image-message]' );

		function setImage( image ) {
			field.value = image ? image.id : '0';
			picker.dataset.sourceId = image ? ( image.lgsdnCropSourceId || image.id ) : '0';
			preview.replaceChildren();
			if ( image ) {
				const element = document.createElement( 'img' );
				element.src = image.url;
				element.alt = image.alt || '';
				preview.appendChild( element );
			}
			picker.querySelector( '[data-image-recrop]' ).hidden = slot !== 'card' || ! image;
			picker.querySelector( '[data-image-remove]' ).hidden = ! image;
			field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			if ( wp.data ) {
				const editor = wp.data.dispatch( 'core/editor' );
				if ( editor && editor.editPost ) editor.editPost( { meta: { [ field.name ]: Number( field.value ) } } );
			}
			message.textContent = image ? ( slot === 'card' ? 'Cropped copy selected. Save the case study to apply it.' : 'Header image selected. Save the case study to apply it.' ) : ( slot === 'article' ? 'No article header image will be shown.' : 'Choose a card image before publishing.' );
		}

		function openPicker( adjust ) {
			message.textContent = '';
			if ( slot === 'article' ) {
				const headerFrame = wp.media( {
					title: 'Choose article header image',
					button: { text: 'Use header image' },
					library: { type: 'image' },
					multiple: false,
				} );
				headerFrame.on( 'select', function () {
					setImage( headerFrame.state().get( 'selection' ).first().toJSON() );
				} );
				headerFrame.open();
				return;
			}
			const frame = wp.media( {
				button: { text: 'Select and crop', close: false },
				states: [
					new wp.media.controller.Library( {
						id: 'library',
						title: slot === 'card' ? 'Choose card image' : 'Choose article header image',
						library: wp.media.query( { type: 'image' } ),
						multiple: false,
						date: false,
						suggestedWidth: dimensions.width,
						suggestedHeight: dimensions.height,
					} ),
					new NativeCropper( {
						id: 'cropper',
						title: 'Crop ' + ( slot === 'card' ? 'card' : 'article header' ) + ' image (' + dimensions.ratio_label + ')',
						slot: slot,
						postId: Number( picker.dataset.postId ),
						canSkipCrop: false,
						imgSelectOptions: function ( attachment ) {
							const width = attachment.get( 'width' );
							const height = attachment.get( 'height' );
							const ratio = dimensions.width / dimensions.height;
							const cropWidth = Math.min( width, height * ratio );
							const cropHeight = cropWidth / ratio;
							const x = Math.round( ( width - cropWidth ) / 2 );
							const y = Math.round( ( height - cropHeight ) / 2 );
							return {
								handles: true, keys: true, instance: true, persistent: true,
								imageWidth: width, imageHeight: height,
								aspectRatio: dimensions.width + ':' + dimensions.height,
								x1: x, y1: y, x2: Math.min( width, Math.round( x + cropWidth ) ), y2: Math.min( height, Math.round( y + cropHeight ) ),
							};
						},
					} ),
				],
			} );
			frame.$el.addClass( 'lgsdn-playbook-image-modal' );
			frame.on( 'select', function () { frame.setState( 'cropper' ); } );
			frame.on( 'cropped', setImage );
			if ( adjust && Number( picker.dataset.sourceId ) ) {
				frame.once( 'open', function () {
					const attachment = wp.media.attachment( Number( picker.dataset.sourceId ) );
					attachment.fetch().then( function () {
						if ( ! frame.$el.is( ':visible' ) ) return;
						frame.state( 'library' ).get( 'selection' ).reset( [ attachment ] );
						frame.trigger( 'select' );
					}, function () { message.textContent = 'The original image is unavailable. Choose another image.'; } );
				} );
			}
			frame.open();
		}

		picker.querySelector( '[data-image-choose]' ).addEventListener( 'click', function () { openPicker( false ); } );
		picker.querySelector( '[data-image-recrop]' ).addEventListener( 'click', function () { openPicker( true ); } );
		picker.querySelector( '[data-image-remove]' ).addEventListener( 'click', function () { setImage( null ); } );
	} );
}( window.wp ) );
