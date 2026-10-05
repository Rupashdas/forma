/**
 * FORMA — lazy images stay lazy.
 *
 * Elementor Pro's Loop Grid calls imagesLoaded on its cards as it starts, and imagesLoaded loads every image it is given
 * through a copy of its own, whatever the image's `loading` says. On Home that fetched all five project photographs of the
 * Selected work strip, far down the page, (about 670KB) before the first paint had finished. This hands imagesLoaded only
 * the images that are already loaded or are not lazy; a lazy image that has not loaded yet is left to the browser, which
 * loads it when it nears the screen. (Nothing here uses the Loop Grid's masonry, the one thing that waits for its images.)
 */
( () => {
	const original = window.imagesLoaded;

	if ( typeof original !== 'function' ) {
		return;
	}

	const wanted = ( img ) => img.loading !== 'lazy' || img.complete;

	window.imagesLoaded = function ( elements, ...rest ) {
		const images = [];
		const add = ( node ) => {
			if ( node instanceof window.HTMLImageElement ) {
				images.push( node );
			} else if ( node && typeof node.querySelectorAll === 'function' ) {
				images.push( ...node.querySelectorAll( 'img' ) );
			}
		};

		if ( typeof elements === 'string' ) {
			document.querySelectorAll( elements ).forEach( add );
		} else if ( elements && ! elements.nodeType && typeof elements.length === 'number' ) {
			Array.from( elements ).forEach( add );
		} else {
			add( elements );
		}

		return original.call( this, images.filter( wanted ), ...rest );
	};

	Object.assign( window.imagesLoaded, original );
} )();
