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

			// Lines step one after another; words and characters share a fixed total, so long text never drags.
			const spread =
				stagger !== undefined && stagger !== ''
					? parseFloat( stagger )
					: { lines: 0.1, words: { amount: 0.6 }, chars: { amount: 0.45 } }[ type ];

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
							stagger: spread,
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

	// Expand is a wide-screen effect: below 1024px the image simply stays put. The start is clamped so an image that is
	// already on screen at load begins its scrub from scroll 0 instead of being half open.
	gsap.matchMedia().add( '(min-width: 1024px)', () => {
		document.querySelectorAll( '[data-fm-scroll="expand"]' ).forEach( ( el ) => {
			gsap.fromTo(
				el,
				{ clipPath: 'inset(6% 9% 6% 9%)' },
				{ clipPath: 'inset(0% 0% 0% 0%)', ease: 'none', scrollTrigger: { trigger: el, start: 'clamp(top 85%)', end: 'center 45%', scrub: 0.6 } }
			);
		} );
	} );

	// Horizontal scroll is a wide-screen effect too: from 1024px the closest top-level container (the band the strip sits
	// in) sticks to the top of the screen while the strip slides sideways. It is plain CSS sticky, so the hand-off at both
	// ends is the browser's own and cannot jump: the band goes into a wrapper made as much taller than itself as the strip
	// is wider than its window, the band sticks inside that wrapper, and the scroll through the wrapper (a ScrollTrigger
	// with no pin) sets the strip's x. Below that, and under reduced motion (where this file stops above), the strip is the
	// swipe row that motion.css makes it.
	gsap.matchMedia().add( '(min-width: 1024px)', () => {
		const restore = [];

		document.querySelectorAll( '[data-fm-scroll="hscroll"]' ).forEach( ( el ) => {
			const track = el.querySelector( '.elementor-loop-container' ) || el;
			const band = el.closest( '.e-con.e-parent' ) || el;
			// The element that clips the strip: the widget itself, or (when the widget is the strip) its parent.
			const clip = track === el ? el.parentElement : el;

			if ( ! clip || ! band.parentNode || band === document.body ) {
				return;
			}

			const style = clip.style;
			const was = { overflow: style.overflow, snap: style.scrollSnapType };

			style.overflow = 'hidden';
			style.scrollSnapType = 'none';
			clip.scrollLeft = 0;

			if ( track === el ) {
				// The strip is the widget itself and slides inside its clipping parent.
				el.style.overflow = 'visible';
			}

			// The wrapper takes the band's vertical margins (so the gap above and below stays as it was) and the band sticks
			// inside it, level with where its own top margin put it.
			const bandStyle = window.getComputedStyle( band );
			const gap = parseFloat( bandStyle.marginTop ) || 0;
			const wrap = document.createElement( 'div' );
			const inline = { position: band.style.position, top: band.style.top };

			wrap.className = 'forma-hscroll';
			wrap.style.marginTop = bandStyle.marginTop;
			wrap.style.marginBottom = bandStyle.marginBottom;
			band.parentNode.insertBefore( wrap, band );
			wrap.appendChild( band );
			band.style.marginTop = '0px';
			band.style.marginBottom = '0px';
			band.style.position = 'sticky';
			band.style.top = `${ gap }px`;

			const distance = () => Math.max( 1, track.scrollWidth - clip.clientWidth );
			const setX = gsap.quickSetter( track, 'x', 'px' );
			const size = () => {
				wrap.style.height = `${ band.offsetHeight + distance() }px`;
			};

			size();

			// ScrollTrigger measures every trigger after this one from the page as it is, so the wrapper is resized first.
			ScrollTrigger.addEventListener( 'refreshInit', size );

			const trigger = ScrollTrigger.create( {
				trigger: wrap,
				start: `top ${ gap }px`,
				end: () => `+=${ distance() }`,
				invalidateOnRefresh: true,
				onUpdate: ( self ) => setX( -self.progress * distance() ),
				onRefresh: ( self ) => setX( -self.progress * distance() ),
			} );

			// Focus (Tab) on a card that is out of view makes the browser scroll the clip itself, which would pull the strip out
			// of step with the page's scroll. The clip is kept still and the page is scrolled to the place where the card is
			// in the middle of the window instead.
			const holdClip = () => {
				clip.scrollLeft = 0;
			};
			const reveal = ( event ) => {
				const card = event.target.closest( '.elementor-loop-container > div' );

				holdClip();

				if ( ! card ) {
					return;
				}

				const left = card.getBoundingClientRect().left - track.getBoundingClientRect().left;
				const x = Math.min( distance(), Math.max( 0, left - ( clip.clientWidth - card.offsetWidth ) / 2 ) );
				const to = () => {
					holdClip();
					window.formaLenis ? window.formaLenis.scrollTo( trigger.start + x, { immediate: true, force: true } ) : window.scrollTo( 0, trigger.start + x );
				};

				to();
				// The browser's own scroll-into-view may follow the focus event; this puts it right afterwards.
				window.requestAnimationFrame( to );
			};

			clip.addEventListener( 'scroll', holdClip );
			el.addEventListener( 'focusin', reveal );

			restore.push( () => {
				clip.removeEventListener( 'scroll', holdClip );
				el.removeEventListener( 'focusin', reveal );
				trigger.kill();
				ScrollTrigger.removeEventListener( 'refreshInit', size );
				gsap.set( track, { clearProps: 'transform' } );
				wrap.parentNode?.insertBefore( band, wrap );
				wrap.remove();
				band.style.marginTop = '';
				band.style.marginBottom = '';
				band.style.position = inline.position;
				band.style.top = inline.top;
				style.overflow = was.overflow;
				style.scrollSnapType = was.snap;
				el.style.overflow = '';
			} );
		} );

		return () => restore.forEach( ( undo ) => undo() );
	} );

	const refresh = () => ScrollTrigger.refresh();

	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( refresh );
	}

	window.addEventListener( 'load', refresh, { once: true } );
} )();
