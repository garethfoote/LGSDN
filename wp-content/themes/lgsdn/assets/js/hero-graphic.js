( () => {
	const graphic = document.querySelector( '.home-hero__graphic-disc' );

	if ( ! graphic ) {
		return;
	}

	const palette = [
		[ '--wp--preset--color--practice-mustard', '#b59b00' ],
		[ '--wp--preset--color--practice-blue', '#4b5aff' ],
		[ '--wp--preset--color--practice-purple', '#c7afe1' ],
		[ '--wp--preset--color--practice-green', '#4b5b37' ],
		[ '--wp--preset--color--practice-orange', '#ff7d01' ],
	];
	const rootStyles = getComputedStyle( document.documentElement );
	const colors = palette.map( ( [ variable, fallback ] ) => rootStyles.getPropertyValue( variable ).trim() || fallback );
	const selectedColor = colors[ Math.floor( Math.random() * colors.length ) ];

	graphic.style.setProperty( '--lgsdn-hero-graphic-bg', selectedColor );
} )();
