/**
 * FORMA — model stage loader.
 *
 * The study models are an enhancement: the page reads and works without them. So the runtime and Three.js (about 190KB,
 * the heaviest thing on the page) are fetched once the page has finished loading and the browser has a spare moment,
 * rather than racing the styles, the fonts and Elementor's own scripts for the first paint. The editor loads them at
 * once, so a widget never appears empty while it is being edited.
 */
( () => {
	const run = () => import( 'forma-model-runtime' );
	const editing = () => document.body.classList.contains( 'elementor-editor-active' ) || document.documentElement.classList.contains( 'elementor-html' );

	const later = () => {
		if ( 'requestIdleCallback' in window ) {
			window.requestIdleCallback( run, { timeout: 1200 } );
		} else {
			window.setTimeout( run, 60 );
		}
	};

	if ( editing() ) {
		run();
	} else if ( document.readyState === 'complete' ) {
		later();
	} else {
		window.addEventListener( 'load', later, { once: true } );
	}
} )();
