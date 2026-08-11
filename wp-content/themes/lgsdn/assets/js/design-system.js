( () => {
	const breakpointQueries = [
		{ name: 'wide', query: window.matchMedia( '(min-width: 64rem)' ) },
		{ name: 'medium', query: window.matchMedia( '(min-width: 48rem)' ) },
		{ name: 'compact', query: window.matchMedia( '(min-width: 0)' ) },
	];

	const updateCurrentBreakpoint = () => {
		const current = breakpointQueries.find( ( breakpoint ) => breakpoint.query.matches ).name;

		document.querySelectorAll( '[data-lgsdn-breakpoint]' ).forEach( ( row ) => {
			const isCurrent = row.dataset.lgsdnBreakpoint === current;
			row.classList.toggle( 'is-current', isCurrent );

			if ( isCurrent ) {
				row.setAttribute( 'aria-current', 'true' );
			} else {
				row.removeAttribute( 'aria-current' );
			}
		} );

		document.querySelectorAll( '.lgsdn-specimen__item' ).forEach( ( item ) => {
			const sample = item.lastElementChild;
			const currentValue = item.querySelector( 'tr.is-current td' );

			if ( ! sample || ! currentValue ) {
				return;
			}

			const styles = window.getComputedStyle( sample );
			const fontSize = Math.round( Number.parseFloat( styles.fontSize ) * 100 ) / 100;
			const lineHeight = styles.lineHeight === 'normal'
				? 'normal'
				: `${ Math.round( Number.parseFloat( styles.lineHeight ) * 100 ) / 100 }px`;

			currentValue.textContent = `${ fontSize }px / ${ lineHeight }`;
		} );
	};

	breakpointQueries.forEach( ( breakpoint ) => {
		breakpoint.query.addEventListener( 'change', updateCurrentBreakpoint );
	} );

	updateCurrentBreakpoint();
} )();
