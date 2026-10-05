/**
 * FORMA — smooth scroll.
 *
 * Lenis eases the wheel into the page's own scroll. It runs from GSAP's ticker and tells ScrollTrigger about every
 * scroll, so pinned sections, scrubbed models and the horizontal strip stay in step. It does not start for people who
 * prefer reduced motion, on touch-only devices, or inside Elementor's editor; those just scroll natively. The instance
 * is exposed as window.formaLenis, and in-page anchor links ease to their target.
 */
( () => {
	const root = document.documentElement;
	const editing = document.body.classList.contains( 'elementor-editor-active' ) || root.classList.contains( 'elementor-html' );
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const touchOnly = window.matchMedia( '(hover: none) and (pointer: coarse)' );

	if ( editing || reduce.matches || touchOnly.matches || ! window.Lenis || ! window.gsap || ! window.ScrollTrigger ) {
		return;
	}

	const { gsap, ScrollTrigger } = window;
	gsap.registerPlugin( ScrollTrigger );

	// Anchors ease to their target, clear of the floating header.
	const lenis = new window.Lenis( { lerp: 0.1, anchors: { offset: -88 } } );
	const tick = ( time ) => lenis.raf( time * 1000 );

	window.formaLenis = lenis;
	lenis.on( 'scroll', ScrollTrigger.update );
	gsap.ticker.add( tick );
	gsap.ticker.lagSmoothing( 0 );

	// A full-screen popup (the menu) owns the scroll while it is open.
	if ( window.jQuery ) {
		window.jQuery( document ).on( 'elementor/popup/show', () => lenis.stop() );
		window.jQuery( document ).on( 'elementor/popup/hide', () => lenis.start() );
	}

	// Switching reduced motion on while the page is open hands the scroll back to the browser.
	reduce.addEventListener( 'change', () => {
		if ( reduce.matches ) {
			gsap.ticker.remove( tick );
			lenis.destroy();
			delete window.formaLenis;
		}
	} );
} )();
