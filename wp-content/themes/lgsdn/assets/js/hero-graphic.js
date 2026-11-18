( () => {
	const graphic = document.querySelector( '.home-hero__graphic-disc' );

	if ( ! graphic?.dataset.heroPalette ) {
		return;
	}

	const palette = JSON.parse( graphic.dataset.heroPalette );
	const { base, ring } = palette[ Math.floor( Math.random() * palette.length ) ];
	document.documentElement.style.setProperty( '--lgsdn-hero-graphic-accent', base );
	document.documentElement.style.setProperty( '--lgsdn-feature-ring-color', ring );
} )();
