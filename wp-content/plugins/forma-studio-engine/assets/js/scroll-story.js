/**
 * FORMA — Scroll Story.
 *
 * Horizontal: on desktop with motion allowed, pins the story and slides its panels sideways as you scroll, with a
 * progress rail. GSAP's matchMedia reverts it cleanly below 1024px, where the story stays a vertical list.
 * Steps: highlights the step in the middle of the screen and shows its image in the sticky column.
 */
( () => {
	const { gsap, ScrollTrigger } = window;
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( gsap && ScrollTrigger && ! reduce ) {
		gsap.registerPlugin( ScrollTrigger );

		document.querySelectorAll( '.forma-story--horizontal' ).forEach( ( story ) => {
			const track = story.querySelector( '.forma-story__panels' );
			const rail = story.querySelector( '.forma-story__rail span' );

			gsap.matchMedia().add( '(min-width: 1024px)', () => {
				story.classList.add( 'is-horizontal' );

				const distance = () => Math.max( 0, track.scrollWidth - story.clientWidth );

				gsap.to( track, {
					x: () => -distance(),
					ease: 'none',
					scrollTrigger: {
						trigger: story,
						start: 'top top',
						end: () => `+=${ distance() }`,
						pin: true,
						scrub: 0.6,
						invalidateOnRefresh: true,
						onUpdate: ( self ) => {
							if ( rail ) {
								rail.style.transform = `scaleX(${ self.progress })`;
							}
						},
					},
				} );

				return () => {
					story.classList.remove( 'is-horizontal' );
					gsap.set( track, { clearProps: 'transform' } );
				};
			} );
		} );

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( () => ScrollTrigger.refresh() );
		}
	}

	document.querySelectorAll( '.forma-story--steps' ).forEach( ( story ) => {
		const panels = Array.from( story.querySelectorAll( '.forma-story__panel' ) );
		const images = Array.from( story.querySelectorAll( '.forma-story__media img' ) );

		if ( ! panels.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		const activate = ( active ) => {
			panels.forEach( ( panel, index ) => panel.classList.toggle( 'is-active', index === active ) );
			images.forEach( ( image, index ) => image.classList.toggle( 'is-active', index === active ) );
		};

		story.classList.add( 'is-steps' );
		activate( 0 );

		const observer = new window.IntersectionObserver(
			( entries ) => entries.forEach( ( entry ) => entry.isIntersecting && activate( panels.indexOf( entry.target ) ) ),
			{ rootMargin: '-45% 0px -45% 0px' }
		);

		panels.forEach( ( panel ) => observer.observe( panel ) );
	} );
} )();
