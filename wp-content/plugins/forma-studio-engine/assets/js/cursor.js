/**
 * FORMA — cursor.
 *
 * A small Sodium dot trails the pointer and grows into a labelled disc over [data-cursor] elements.
 * Fine pointers only; the native cursor stays visible, so nothing is lost if this never runs.
 */
( () => {
	if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}

	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const cursor = document.createElement( 'div' );
	const label = document.createElement( 'span' );

	cursor.className = 'forma-cursor';
	cursor.setAttribute( 'aria-hidden', 'true' );
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
		},
		{ passive: true }
	);

	document.documentElement.addEventListener( 'pointerleave', () => {
		visible = false;
		cursor.classList.remove( 'is-visible' );
	} );

	document.addEventListener( 'pointerover', ( event ) => {
		const target = event.target.closest( '[data-cursor]' );

		if ( target ) {
			label.textContent = target.dataset.cursor;
			cursor.classList.add( 'is-active' );
		}
	} );

	document.addEventListener( 'pointerout', ( event ) => {
		const target = event.target.closest( '[data-cursor]' );

		if ( target && ! target.contains( event.relatedTarget ) ) {
			cursor.classList.remove( 'is-active' );
		}
	} );
} )();
