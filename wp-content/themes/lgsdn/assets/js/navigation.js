( () => {
	const desktopQuery = window.matchMedia( '(min-width: 48rem)' );
	const reducedMotionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	const updateScrollState = () => {
		const isScrolled = window.scrollY > 0;

		document.querySelectorAll( '.site-header' ).forEach( ( header ) => {
			header.classList.toggle( 'is-scrolled', isScrolled );

			const stickyContainer = header.closest( 'header.wp-block-template-part' ) || header;
			stickyContainer?.classList.toggle( 'is-sticky', isScrolled );
		} );
	};

	updateScrollState();
	window.addEventListener( 'scroll', updateScrollState, { passive: true } );

	document.querySelectorAll( '.menu-button[aria-controls]' ).forEach( ( openButton ) => {
		const navigation = document.getElementById( openButton.getAttribute( 'aria-controls' ) );
		const header = openButton.closest( '.site-header' );
		const closeButton = navigation?.querySelector( '.menu-close' );
		const backdrop = header?.querySelector( '.menu-backdrop' );

		if ( ! navigation || ! closeButton || ! backdrop ) {
			return;
		}

		const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

		const finishClosing = () => {
			navigation.classList.remove( 'is-closing' );
			backdrop.classList.remove( 'is-closing' );
		};

		const closeMenu = ( restoreFocus = true, animate = true ) => {
			const shouldAnimate = animate && ! reducedMotionQuery.matches && navigation.classList.contains( 'is-open' );
			if ( shouldAnimate ) {
				navigation.classList.add( 'is-closing' );
				backdrop.classList.add( 'is-closing' );
			}

			navigation.classList.remove( 'is-open' );
			backdrop.classList.remove( 'is-open' );
			openButton.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'menu-open' );

			if ( ! shouldAnimate ) {
				finishClosing();
			}

			if ( restoreFocus ) {
				openButton.focus();
			}
		};

		const openMenu = () => {
			finishClosing();
			navigation.classList.add( 'is-open' );
			backdrop.classList.add( 'is-open' );
			openButton.setAttribute( 'aria-expanded', 'true' );
			document.body.classList.add( 'menu-open' );
			closeButton.focus();
		};

		openButton.addEventListener( 'click', openMenu );
		closeButton.addEventListener( 'click', () => closeMenu() );
		backdrop.addEventListener( 'click', () => closeMenu() );

		navigation.querySelectorAll( 'a[href]' ).forEach( ( link ) => {
			const target = new URL( link.href, window.location.href );
			const samePage = target.pathname === window.location.pathname;
			const isCurrent = target.hash ? samePage && target.hash === window.location.hash : samePage;
			if ( isCurrent ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}

			link.addEventListener( 'click', () => closeMenu() );
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( ! navigation.classList.contains( 'is-open' ) ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				event.preventDefault();
				closeMenu();
				return;
			}

			if ( event.key !== 'Tab' ) {
				return;
			}

			const focusableElements = Array.from( navigation.querySelectorAll( focusableSelector ) );
			const firstElement = focusableElements[ 0 ];
			const lastElement = focusableElements[ focusableElements.length - 1 ];

			if ( event.shiftKey && document.activeElement === firstElement ) {
				event.preventDefault();
				lastElement.focus();
			} else if ( ! event.shiftKey && document.activeElement === lastElement ) {
				event.preventDefault();
				firstElement.focus();
			}
		} );

		navigation.addEventListener( 'transitionend', ( event ) => {
			if ( 'transform' === event.propertyName && ! navigation.classList.contains( 'is-open' ) ) {
				finishClosing();
			}
		} );

		desktopQuery.addEventListener( 'change', ( event ) => {
			if ( event.matches ) {
				closeMenu( false, false );
			} else {
				finishClosing();
			}
		} );
	} );
} )();
