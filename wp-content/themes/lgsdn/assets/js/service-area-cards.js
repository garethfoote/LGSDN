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
				const visibilityRatio = cardRect.width > 0 ? visibleWidth / cardRect.width : 0;
				const isFullyVisible = visibilityRatio >= 0.999;
				const isPartiallyVisible = visibleWidth > 0 && ! isFullyVisible;
				const isClippedOnLeft = isPartiallyVisible && cardRect.left < viewportLeft;
				const isClippedOnRight = isPartiallyVisible && cardRect.right > viewportRight;

				dots[ index ].classList.toggle( 'is-active', isFullyVisible );
				dots[ index ].classList.toggle( 'is-partial-left', isClippedOnLeft );
				dots[ index ].classList.toggle( 'is-partial-right', isClippedOnRight );
				dots[ index ].setAttribute( 'aria-pressed', isFullyVisible ? 'true' : 'false' );
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
				intro.classList.remove( 'is-hidden', 'is-entering' );
				// Force a layout pass so the entrance animation can restart each time.
				void intro.offsetWidth;
				intro.classList.add( 'is-entering' );
			} else {
				intro.classList.remove( 'is-hidden', 'is-entering' );
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
		window.addEventListener( 'resize', updateLayout );
		updateLayout();
	} );
}() );
