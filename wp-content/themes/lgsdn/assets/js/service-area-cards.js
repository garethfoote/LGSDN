( function () {
	'use strict';

	const rows = document.querySelectorAll( '[data-service-scroller], [data-case-study-scroller]' );
	const reducedMotionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	rows.forEach( ( scroller ) => {
		const row = scroller.closest( '.homepage-service-row, .homepage-case-study-row, .lgsdn-playbook-service-row' );
		const isHomepageRow = row && row.matches( '.homepage-service-row, .homepage-case-study-row' );
		const isPlaybookRow = row && row.matches( '.lgsdn-playbook-service-row' );
		const intro = row ? row.querySelector( '[data-service-intro], [data-case-study-intro]' ) : null;
		const cards = Array.from( scroller.children );
		const scrollIndicators = document.createElement( 'div' );
		const previousButton = document.createElement( 'button' );
		const nextButton = document.createElement( 'button' );

		previousButton.className = 'lgsdn-horizontal-scroll-arrow lgsdn-horizontal-scroll-arrow--previous';
		previousButton.type = 'button';
		previousButton.setAttribute( 'aria-label', 'Show previous cards' );
		previousButton.innerHTML = '<span aria-hidden="true">←</span>';
		nextButton.className = 'lgsdn-horizontal-scroll-arrow lgsdn-horizontal-scroll-arrow--next';
		nextButton.type = 'button';
		nextButton.setAttribute( 'aria-label', 'Show next cards' );
		nextButton.innerHTML = '<span aria-hidden="true">→</span>';
		scrollIndicators.append( previousButton );

		const getScrollStep = () => {
			if ( ! cards[ 0 ] ) {
				return scroller.clientWidth;
			}

			const nextCardOffset = cards[ 1 ] ? cards[ 1 ].offsetLeft : cards[ 0 ].offsetLeft + cards[ 0 ].offsetWidth;
			return Math.max( 1, nextCardOffset - cards[ 0 ].offsetLeft );
		};

		const scrollCards = ( direction ) => {
			scroller.scrollBy( {
				left: direction * getScrollStep(),
				behavior: reducedMotionQuery.matches ? 'auto' : 'smooth',
			} );
		};

		previousButton.addEventListener( 'click', () => scrollCards( -1 ) );
		nextButton.addEventListener( 'click', () => scrollCards( 1 ) );

		const minimap = document.createElement( 'div' );
		minimap.className = 'lgsdn-horizontal-scroll-map';
		const viewportFrame = document.createElement( 'span' );
		viewportFrame.className = 'lgsdn-horizontal-scroll-map__viewport';
		viewportFrame.setAttribute( 'aria-hidden', 'true' );
		scrollIndicators.appendChild( minimap );

		const dots = cards.map( ( card, index ) => {
			const dot = document.createElement( 'button' );
			const title = card.querySelector( 'h3, h4' );

			dot.className = 'lgsdn-horizontal-scroll-map__card';
			dot.type = 'button';
			dot.setAttribute( 'aria-label', `Show ${ title ? title.textContent.trim() : `card ${ index + 1 }` }` );
			dot.addEventListener( 'click', () => {
				const maxScrollLeft = Math.max( 0, scroller.scrollWidth - scroller.clientWidth );
				const centeredScrollLeft = card.offsetLeft + ( card.offsetWidth / 2 ) - ( scroller.clientWidth / 2 );
				const targetScrollLeft = Math.max( 0, Math.min( maxScrollLeft, centeredScrollLeft ) );

				scroller.scrollTo( { left: targetScrollLeft, behavior: reducedMotionQuery.matches ? 'auto' : 'smooth' } );
			} );
			minimap.appendChild( dot );
			return dot;
		} );
		minimap.appendChild( viewportFrame );
		scrollIndicators.append( nextButton );

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
			const maxScrollLeft = Math.max( 0, scroller.scrollWidth - scroller.clientWidth );

			previousButton.disabled = scroller.scrollLeft <= 1;
			nextButton.disabled = scroller.scrollLeft >= maxScrollLeft - 1;

			if ( ! cards.length ) {
				return;
			}

			const cardRects = cards.map( ( card ) => card.getBoundingClientRect() );
			cardRects.forEach( ( rect, index ) => {
				dots[ index ].classList.toggle( 'is-visible', rect.right > viewportLeft && rect.left < viewportRight );
			} );
			// Map cards and the gaps between them separately, preserving exact
			// fractions even though the miniature gaps use a different scale.
			const mapPosition = ( position ) => {
				for ( let index = 0; index < cards.length; index++ ) {
					const rect = cardRects[ index ];
					const dot = dots[ index ];
					if ( position <= rect.left ) {
						if ( index === 0 ) {
							// Represent only real empty space before the first card.
							// Cap its miniature width at two pixels, without a state switch.
							const clearance = rect.width > 0 ? Math.min( 2, ( rect.left - position ) / rect.width * dot.offsetWidth ) : 0;
							return dot.offsetLeft - clearance;
						}
						const previousRect = cardRects[ index - 1 ];
						const previousDot = dots[ index - 1 ];
						const gapStart = previousDot.offsetLeft + previousDot.offsetWidth;
						const fraction = ( position - previousRect.right ) / ( rect.left - previousRect.right );
						return gapStart + fraction * ( dot.offsetLeft - gapStart );
					}
					if ( position <= rect.right ) {
						return dot.offsetLeft + ( position - rect.left ) / rect.width * dot.offsetWidth;
					}
				}
				const lastDot = dots[ dots.length - 1 ];
				const lastRect = cardRects[ cardRects.length - 1 ];
				const clearance = lastRect.width > 0 ? Math.min( 2, ( position - lastRect.right ) / lastRect.width * lastDot.offsetWidth ) : 0;
				return lastDot.offsetLeft + lastDot.offsetWidth + clearance;
			};
			const frameLeft = mapPosition( viewportLeft );
			const frameRight = mapPosition( viewportRight );
			viewportFrame.style.left = `${ frameLeft }px`;
			viewportFrame.style.width = `${ Math.max( 0, frameRight - frameLeft ) }px`;
		};

		if ( 'ResizeObserver' in window ) {
			const resizeObserver = new ResizeObserver( updateScrollIndicators );
			resizeObserver.observe( scroller );
			resizeObserver.observe( minimap );
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
			const layoutViewportWidth = document.documentElement.clientWidth;

			if ( isHomepageRow ) {
				const rowLeft = row.getBoundingClientRect().left;
				row.style.width = `${ Math.max( 0, layoutViewportWidth - rowLeft ) }px`;
			} else if ( isPlaybookRow ) {
				const contentLeft = row.parentElement.getBoundingClientRect().left;

				row.style.width = `${ layoutViewportWidth }px`;
				row.style.marginLeft = `${ -contentLeft }px`;
				scroller.style.paddingLeft = `${ contentLeft }px`;
				scroller.style.scrollPaddingLeft = `${ contentLeft }px`;
			}
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
