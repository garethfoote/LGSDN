( () => {
	const graphic = document.querySelector( '.home-hero__graphic-disc' );

	if ( ! graphic ) {
		return;
	}

	const palette = [
		[ '--wp--preset--color--practice-gold', '#fac558' ],
		[ '--wp--preset--color--practice-blue', '#4b66ff' ],
		[ '--wp--preset--color--practice-purple', '#c6afe3' ],
		[ '--wp--preset--color--practice-olive', '#4b5a2b' ],
		[ '--wp--preset--color--practice-orange', '#ff9d4d' ],
	];
	const rootStyles = getComputedStyle( document.documentElement );
	const colors = palette.map( ( [ variable, fallback ] ) => rootStyles.getPropertyValue( variable ).trim() || fallback );
	const selectedColor = colors[ Math.floor( Math.random() * colors.length ) ];

	graphic.style.setProperty( '--lgsdn-hero-graphic-bg', selectedColor );
} )();
