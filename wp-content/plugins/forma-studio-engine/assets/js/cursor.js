/**
 * FORMA — cursor.
 *
 * A small Signal blue dot trails the pointer and grows into a labelled disc over [data-cursor] elements, or into an
 * image preview over [data-cursor-image] elements (the value is the image URL). Buttons and [data-magnetic] elements
 * lean towards the pointer when it comes near and spring back when it leaves. Fine pointers only; the native cursor
 * stays visible, so nothing is lost if this never runs. Reduced motion keeps the dot and the preview but not the lean.
 */
( () => {
	if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}

	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const cursor = document.createElement( 'div' );
	const label = document.createElement( 'span' );
	const image = document.createElement( 'i' );

	cursor.className = 'forma-cursor';
	cursor.setAttribute( 'aria-hidden', 'true' );
	image.className = 'forma-cursor__image';
	cursor.appendChild( image );
	cursor.appendChild( label );
	document.body.appendChild( cursor );

	let x = 0;
	let y = 0;
	let cx = 0;
	let cy = 0;
	let frame = 0;
	let visible = false;

	const tick = () => {
		const follow = reduce ? 1 : 0.2;
		cx += ( x - cx ) * follow;
		cy += ( y - cy ) * follow;
		cursor.style.transform = `translate3d(${ cx }px, ${ cy }px, 0)`;
		frame = Math.abs( x - cx ) > 0.1 || Math.abs( y - cy ) > 0.1 ? window.requestAnimationFrame( tick ) : 0;
	};

	/* ---- magnetic elements ---- */

	const REACH = 40; // px beyond an element's box in which it starts to lean.
	const PULL = 0.25; // fraction of the pointer's offset from the element's centre that it follows.
	const magnets = [];
	let magnetFrame = 0;

	const collectMagnets = () => {
		document.querySelectorAll( '.elementor-button, [data-magnetic]' ).forEach( ( el ) => {
			if ( el.formaMagnet ) {
				return;
			}

			// translate has no effect on an inline box.
			if ( window.getComputedStyle( el ).display === 'inline' ) {
				el.style.display = 'inline-block';
			}

			el.classList.add( 'forma-magnet' );
			el.formaMagnet = { el, x: 0, y: 0, vx: 0, vy: 0, tx: 0, ty: 0 };
			magnets.push( el.formaMagnet );
		} );
	};

	/** A light spring: it follows the target and overshoots a little when released. */
	const springMagnets = () => {
		let moving = false;

		magnets.forEach( ( m ) => {
			m.vx = ( m.vx + ( m.tx - m.x ) * 0.14 ) * 0.76;
			m.vy = ( m.vy + ( m.ty - m.y ) * 0.14 ) * 0.76;
			m.x += m.vx;
			m.y += m.vy;

			const settled = Math.abs( m.vx ) < 0.02 && Math.abs( m.vy ) < 0.02 && Math.abs( m.tx - m.x ) < 0.05 && Math.abs( m.ty - m.y ) < 0.05;

			if ( settled ) {
				m.x = m.tx;
				m.y = m.ty;
				m.vx = 0;
				m.vy = 0;
			} else {
				moving = true;
			}

			m.el.style.translate = m.x || m.y ? `${ m.x.toFixed( 2 ) }px ${ m.y.toFixed( 2 ) }px` : '';
		} );

		magnetFrame = moving ? window.requestAnimationFrame( springMagnets ) : 0;
	};

	const aimMagnets = () => {
		magnets.forEach( ( m ) => {
			const box = m.el.getBoundingClientRect();
			// The box includes the lean already applied; take it back out to read where the element rests.
			const left = box.left - m.x;
			const top = box.top - m.y;
			const near = x > left - REACH && x < left + box.width + REACH && y > top - REACH && y < top + box.height + REACH && box.width > 0;

			m.tx = near ? ( x - ( left + box.width / 2 ) ) * PULL : 0;
			m.ty = near ? ( y - ( top + box.height / 2 ) ) * PULL : 0;
		} );

		if ( ! magnetFrame ) {
			magnetFrame = window.requestAnimationFrame( springMagnets );
		}
	};

	const releaseMagnets = () => {
		magnets.forEach( ( m ) => {
			m.tx = 0;
			m.ty = 0;
		} );

		if ( ! magnetFrame ) {
			magnetFrame = window.requestAnimationFrame( springMagnets );
		}
	};

	if ( ! reduce ) {
		collectMagnets();
		window.addEventListener( 'load', collectMagnets, { once: true } );
	}

	/* ---- pointer ---- */

	document.addEventListener(
		'pointermove',
		( event ) => {
			x = event.clientX;
			y = event.clientY;

			if ( ! visible ) {
				visible = true;
				cx = x;
				cy = y;
				cursor.classList.add( 'is-visible' );
			}

			if ( ! frame ) {
				frame = window.requestAnimationFrame( tick );
			}

			if ( magnets.length ) {
				aimMagnets();
			}
		},
		{ passive: true }
	);

	document.documentElement.addEventListener( 'pointerleave', () => {
		visible = false;
		cursor.classList.remove( 'is-visible' );
		releaseMagnets();
	} );

	/* ---- label and image preview ---- */

	const preloaded = new Set();

	/** The image is fetched the first time the pointer nears it, so the preview is not empty when it opens. */
	const preload = ( url ) => {
		if ( ! preloaded.has( url ) ) {
			preloaded.add( url );
			new window.Image().src = url;
		}
	};

	document.addEventListener( 'pointerover', ( event ) => {
		const preview = event.target.closest( '[data-cursor-image]' );
		const target = event.target.closest( '[data-cursor]' );

		if ( preview && preview.dataset.cursorImage ) {
			const url = preview.dataset.cursorImage;

			preload( url );
			image.style.backgroundImage = `url("${ url.replace( /["\\]/g, '\\$&' ) }")`;
			cursor.classList.add( 'is-image' );
			cursor.classList.remove( 'is-active' );

			return;
		}

		if ( target ) {
			label.textContent = target.dataset.cursor;
			cursor.classList.add( 'is-active' );
		}
	} );

	// Scrolling moves the page under a still pointer without a pointerout; settle the label or preview once it stops.
	let scrollCheck = 0;

	document.addEventListener(
		'scroll',
		() => {
			if ( scrollCheck || ! visible || ! cursor.matches( '.is-active, .is-image' ) ) {
				return;
			}

			scrollCheck = window.requestAnimationFrame( () => {
				scrollCheck = 0;

				const under = document.elementFromPoint( x, y );

				if ( ! under || ! under.closest( '[data-cursor-image]' ) ) {
					cursor.classList.remove( 'is-image' );
				}

				if ( ! under || ! under.closest( '[data-cursor]' ) ) {
					cursor.classList.remove( 'is-active' );
				}
			} );
		},
		{ passive: true }
	);

	document.addEventListener( 'pointerout', ( event ) => {
		const preview = event.target.closest( '[data-cursor-image]' );
		const target = event.target.closest( '[data-cursor]' );

		if ( preview && ! preview.contains( event.relatedTarget ) ) {
			cursor.classList.remove( 'is-image' );
		}

		if ( target && ! target.contains( event.relatedTarget ) ) {
			cursor.classList.remove( 'is-active' );
		}
	} );
} )();
