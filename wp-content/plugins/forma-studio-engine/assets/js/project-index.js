/**
 * FORMA — Project Index preview.
 *
 * Fine pointers: the hovered row's image follows the pointer. Keyboard focus (and reduced motion) parks it beside
 * the active row instead. Touch devices never run this; their rows show thumbnails from CSS.
 */
( () => {
	if ( ! window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		return;
	}

	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	document.querySelectorAll( '.forma-index' ).forEach( ( index ) => {
		const preview = index.querySelector( '.forma-index__preview' );
		const image = preview && preview.querySelector( 'img' );

		if ( ! image ) {
			return;
		}

		let x = 0;
		let y = 0;
		let px = 0;
		let py = 0;
		let frame = 0;

		const show = ( link ) => {
			const thumb = link.querySelector( '.forma-index__thumb' );

			if ( ! thumb ) {
				return;
			}

			if ( image.getAttribute( 'src' ) !== thumb.currentSrc ) {
				image.src = thumb.currentSrc || thumb.src;
			}

			index.classList.add( 'is-previewing' );
		};

		const hide = () => index.classList.remove( 'is-previewing', 'is-pinned' );

		const pin = ( link ) => {
			const row = link.getBoundingClientRect();
			const box = index.getBoundingClientRect();
			index.style.setProperty( '--forma-index-pin', `${ row.top - box.top - preview.offsetHeight / 2 + row.height / 2 }px` );
			index.classList.add( 'is-pinned' );
			show( link );
		};

		const tick = () => {
			const follow = 0.16;
			px += ( x - px ) * follow;
			py += ( y - py ) * follow;
			preview.style.transform = `translate3d(${ px + 32 }px, ${ py - preview.offsetHeight / 2 }px, 0)`;
			frame = Math.abs( x - px ) > 0.2 || Math.abs( y - py ) > 0.2 ? window.requestAnimationFrame( tick ) : 0;
		};

		index.addEventListener( 'pointermove', ( event ) => {
			x = event.clientX;
			y = event.clientY;

			if ( reduce ) {
				return;
			}

			if ( ! index.classList.contains( 'is-previewing' ) ) {
				px = x;
				py = y;
			}

			if ( ! frame ) {
				frame = window.requestAnimationFrame( tick );
			}
		} );

		index.querySelectorAll( '.forma-index__link' ).forEach( ( link ) => {
			link.addEventListener( 'pointerenter', () => ( reduce ? pin( link ) : ( index.classList.remove( 'is-pinned' ), show( link ) ) ) );
			link.addEventListener( 'focus', () => link.matches( ':focus-visible' ) && pin( link ) );
			link.addEventListener( 'blur', hide );
		} );

		index.addEventListener( 'pointerleave', hide );
	} );
} )();
