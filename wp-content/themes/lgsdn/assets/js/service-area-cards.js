( function () {
	'use strict';

	const rows = document.querySelectorAll( '[data-service-scroller], [data-case-study-scroller]' );
	const reducedMotionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	rows.forEach( ( scroller ) => {
		const row = scroller.closest( '.homepage-service-row, .homepage-case-study-row, .lgsdn-playbook-service-row' );
		const intro = row ? row.querySelector( '[data-service-intro], [data-case-study-intro]' ) : null;
		const cards = Array.from( scroller.children );
		const scrollIndicators = document.createElement( 'div' );
		const dots = cards.map( ( card, index ) => {
			const dot = document.createElement( 'button' );
			const title = card.querySelector( 'h3, h4' );

			dot.className = 'lgsdn-horizontal-scroll-dot';
			dot.type = 'button';
			dot.setAttribute( 'aria-label', `Show ${ title ? title.textContent.trim() : `card ${ index + 1 }` }` );
			dot.setAttribute( 'aria-pressed', 'false' );
			dot.addEventListener( 'click', () => {
				const maxScrollLeft = Math.max( 0, scroller.scrollWidth - scroller.clientWidth );
				const centeredScrollLeft = card.offsetLeft + ( card.offsetWidth / 2 ) - ( scroller.clientWidth / 2 );
				const targetScrollLeft = Math.max( 0, Math.min( maxScrollLeft, centeredScrollLeft ) );

				scroller.scrollTo( { left: targetScrollLeft, behavior: reducedMotionQuery.matches ? 'auto' : 'smooth' } );
			} );
			scrollIndicators.appendChild( dot );
			return dot;
		} );

		scrollIndicators.className = 'lgsdn-horizontal-scroll-indicators';
		scrollIndicators.setAttribute( 'role', 'group' );
		scrollIndicators.setAttribute( 'aria-label', 'Choose a card to bring into view' );
		scrollIndicators.hidden = cards.length < 2;
		row.appendChild( scrollIndicators );
		scroller.classList.add( 'has-scroll-indicators' );

		const updateScrollIndicators = () => {
			const scrollerRect = scroller.getBoundingClientRect();
			const viewportLeft = scrollerRect.left;
			const viewportRight = viewportLeft + scroller.clientWidth;

			cards.forEach( ( card, index ) => {
				const cardRect = card.getBoundingClientRect();
				const visibleWidth = Math.max( 0, Math.min( cardRect.right, viewportRight ) - Math.max( cardRect.left, viewportLeft ) );
				const isVisible = cardRect.width > 0 && visibleWidth / cardRect.width >= 0.5;

				dots[ index ].classList.toggle( 'is-active', isVisible );
				dots[ index ].setAttribute( 'aria-pressed', isVisible ? 'true' : 'false' );
			} );
		};

		if ( 'ResizeObserver' in window ) {
			const resizeObserver = new ResizeObserver( updateScrollIndicators );
			resizeObserver.observe( scroller );
		}

		let introWasHidden = false;

		const updateIntroVisibility = () => {
			const isHidden = scroller.scrollLeft > 1;

			if ( ! intro ) {
				return;
			}

			if ( isHidden ) {
				intro.classList.remove( 'is-entering' );
				intro.classList.add( 'is-hidden' );
			} else if ( introWasHidden ) {
				intro.classList.remove( 'is-hidden' );
				intro.classList.add( 'is-entering' );
			} else {
				intro.classList.remove( 'is-hidden' );
			}

			introWasHidden = isHidden;
		};

		const updateLayout = () => {
			const rowLeft = row.getBoundingClientRect().left;
			const layoutViewportWidth = document.documentElement.clientWidth;
			row.style.width = `${ Math.max( 0, layoutViewportWidth - rowLeft ) }px`;
			updateIntroVisibility();
			updateScrollIndicators();
		};

		let pointerStartX = 0;
		let pointerStartScrollLeft = 0;
		let activePointerId = null;
		let isDragging = false;
		let suppressClick = false;

		const handlePointerDown = ( event ) => {
			if ( event.pointerType === 'mouse' && event.button !== 0 ) {
				return;
			}

			pointerStartX = event.clientX;
			pointerStartScrollLeft = scroller.scrollLeft;
			activePointerId = event.pointerId;
			isDragging = false;
		};

		const handlePointerMove = ( event ) => {
			if ( activePointerId !== event.pointerId ) {
				return;
			}

			const distance = event.clientX - pointerStartX;

			if ( ! isDragging && Math.abs( distance ) < 6 ) {
				return;
			}

			isDragging = true;
			scroller.setPointerCapture( event.pointerId );
			scroller.classList.add( 'is-dragging' );
			scroller.scrollLeft = pointerStartScrollLeft - distance;
			event.preventDefault();
		};

		const finishPointerDrag = ( event ) => {
			if ( activePointerId !== event.pointerId ) {
				return;
			}

			if ( isDragging && scroller.hasPointerCapture( event.pointerId ) ) {
				scroller.releasePointerCapture( event.pointerId );
			}

			if ( isDragging ) {
				suppressClick = true;
			}

			scroller.classList.remove( 'is-dragging' );
			isDragging = false;
			activePointerId = null;

			if ( suppressClick ) {
				window.setTimeout( () => {
					suppressClick = false;
				}, 0 );
			}
		};

		const preventDraggedClick = ( event ) => {
			if ( ! suppressClick ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			suppressClick = false;
		};

		const forwardIntroWheel = ( event ) => {
			const delta = Math.abs( event.deltaX ) > Math.abs( event.deltaY )
				? event.deltaX
				: event.shiftKey
					? event.deltaY
					: 0;

			if ( ! delta ) {
				return;
			}

			scroller.scrollLeft += delta;
			event.preventDefault();
		};

		scroller.addEventListener( 'scroll', () => {
			updateIntroVisibility();
			updateScrollIndicators();
		}, { passive: true } );
		if ( intro ) {
			intro.addEventListener( 'wheel', forwardIntroWheel, { passive: false } );
		}
		scroller.addEventListener( 'pointerdown', handlePointerDown );
		scroller.addEventListener( 'pointermove', handlePointerMove, { passive: false } );
		scroller.addEventListener( 'pointerup', finishPointerDrag );
		scroller.addEventListener( 'pointercancel', finishPointerDrag );
		scroller.addEventListener( 'click', preventDraggedClick, true );
		scroller.addEventListener( 'dragstart', ( event ) => event.preventDefault() );
		window.addEventListener( 'resize', updateLayout );
		updateLayout();
	} );
}() );
