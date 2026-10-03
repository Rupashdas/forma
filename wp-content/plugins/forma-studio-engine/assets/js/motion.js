/**
 * FORMA — Forma Motion runtime.
 *
 * Reads the data-fm-* attributes the Elementor "Forma Motion" panel writes and animates them with GSAP.
 * Nothing runs for people who prefer reduced motion or inside the editor: elements simply stay visible.
 */
( () => {
	const root = document.documentElement;
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const editing = document.body.classList.contains( 'elementor-editor-active' ) || root.classList.contains( 'elementor-html' );

	if ( reduce || editing || ! window.gsap || ! window.ScrollTrigger ) {
		root.classList.add( 'fm-armed' );
		return;
	}

	const { gsap, ScrollTrigger, SplitText } = window;
	gsap.registerPlugin( ScrollTrigger );

	if ( SplitText ) {
		gsap.registerPlugin( SplitText );
	}

	const ease = 'expo.out';
	const num = ( value, fallback ) => ( value === undefined || value === '' ? fallback : parseFloat( value ) );
	const entrances = Array.from( document.querySelectorAll( '[data-fm-entrance]' ) );

	// Take over from the CSS guard: hide synchronously, then let the guard go.
	gsap.set( entrances, { autoAlpha: 0 } );
	root.classList.add( 'fm-armed' );

	const textTargets = ( el ) => {
		const blocks = el.querySelectorAll( 'h1, h2, h3, h4, h5, h6, p, li, blockquote, figcaption' );
		return blocks.length ? Array.from( blocks ) : [ el ];
	};

	const reveal = {
		'fade-up': ( el, trigger, delay ) =>
			gsap.fromTo( el, { autoAlpha: 0, y: 40 }, { autoAlpha: 1, y: 0, duration: 1.2, ease, delay, scrollTrigger: trigger } ),

		'clip-up': ( el, trigger, delay ) =>
			gsap.fromTo( el, { autoAlpha: 1, clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease, delay, scrollTrigger: trigger } ),

		'clip-left': ( el, trigger, delay ) =>
			gsap.fromTo( el, { autoAlpha: 1, clipPath: 'inset(0% 100% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease, delay, scrollTrigger: trigger } ),

		'scale-in': ( el, trigger, delay ) => {
			const media = el.querySelector( 'img, video' ) || el;
			const tl = gsap.timeline( { delay, scrollTrigger: trigger } );
			tl.to( el, { autoAlpha: 1, duration: 0.8, ease: 'power2.out' }, 0 );
			tl.fromTo( media, { scale: 1.12 }, { scale: 1, duration: 1.8, ease }, 0 );
			return tl;
		},

		split: ( el, trigger, delay, type, stagger ) => {
			gsap.set( el, { autoAlpha: 1 } );

			if ( ! SplitText ) {
				return gsap.from( el, { autoAlpha: 0, y: 24, duration: 1, ease, delay, scrollTrigger: trigger } );
			}

			const pieces = { lines: 'lines', words: 'words,lines', chars: 'chars,words,lines' }[ type ];
			const mask = type === 'chars' ? 'words' : 'lines';

			textTargets( el ).forEach( ( target ) => {
				SplitText.create( target, {
					type: pieces,
					mask,
					autoSplit: true,
					onSplit: ( self ) =>
						gsap.from( self[ type ], {
							yPercent: 110,
							duration: type === 'chars' ? 0.9 : 1.2,
							ease,
							delay,
							stagger: num( stagger, type === 'chars' ? 0.02 : 0.08 ),
							scrollTrigger: { ...trigger },
						} ),
				} );
			} );
		},
	};

	entrances.forEach( ( el ) => {
		const type = el.dataset.fmEntrance;
		const delay = num( el.dataset.fmDelay, 0 );
		const trigger = { trigger: el, start: 'top 88%', once: true };

		if ( type === 'lines' || type === 'words' || type === 'chars' ) {
			reveal.split( el, trigger, delay, type, el.dataset.fmStagger );
		} else if ( reveal[ type ] ) {
			reveal[ type ]( el, trigger, delay );
		} else {
			gsap.set( el, { autoAlpha: 1 } );
		}
	} );

	document.querySelectorAll( '[data-fm-scroll="parallax"]' ).forEach( ( el ) => {
		const depth = num( el.dataset.fmSpeed, 12 );
		const media = el.querySelector( 'img, video' );
		const target = media || el;

		if ( media ) {
			gsap.set( media, { scale: 1 + Math.abs( depth ) / 100 + 0.02 } );
		}

		gsap.fromTo(
			target,
			{ yPercent: -depth / 2 },
			{ yPercent: depth / 2, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true } }
		);
	} );

	document.querySelectorAll( '[data-fm-scroll="expand"]' ).forEach( ( el ) => {
		gsap.fromTo(
			el,
			{ clipPath: 'inset(6% 9% 6% 9%)' },
			{ clipPath: 'inset(0% 0% 0% 0%)', ease: 'none', scrollTrigger: { trigger: el, start: 'top 85%', end: 'center 45%', scrub: 0.6 } }
		);
	} );

	const refresh = () => ScrollTrigger.refresh();

	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( refresh );
	}

	window.addEventListener( 'load', refresh, { once: true } );
} )();
