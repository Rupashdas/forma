/**
 * FORMA site behaviour: the header.
 *
 * The header (Theme Builder, class .forma-header) holds two floating glass pills, fixed to the top. It condenses once
 * the page has scrolled a little, slides away while scrolling down and returns on scroll up or when anything inside it
 * takes focus. It also marks the current page's nav link. The classes are styled in site.css.
 */
( function () {
	'use strict';

	var CONDENSE_AT = 60; // px scrolled before the header condenses.
	var HIDE_AFTER = 240; // px scrolled before it may hide.
	var JITTER = 4; // px of movement ignored, so a trackpad's tremor doesn't flicker the header.

	var header = document.querySelector( '.forma-header' );

	if ( ! header || document.body.classList.contains( 'elementor-editor-active' ) ) {
		return;
	}

	// The nav links are plain link widgets, which cannot know the current page: mark the one whose path matches this
	// page (a project page counts for Projects), so assistive tech and the pill styling in site.css both know.
	var path = window.location.pathname.replace( /\/+$/, '' );

	header.querySelectorAll( '.forma-nav a[href]' ).forEach( function ( link ) {
		var target = new URL( link.href, window.location.href ).pathname.replace( /\/+$/, '' );

		if ( target && ( path === target || path.indexOf( target + '/' ) === 0 ) ) {
			link.setAttribute( 'aria-current', 'page' );
		}
	} );

	var lastY = Math.max( window.scrollY, 0 );
	var frame = 0;

	function update() {
		frame = 0;

		var y = Math.max( window.scrollY, 0 );

		header.classList.toggle( 'is-condensed', y > CONDENSE_AT );

		if ( Math.abs( y - lastY ) < JITTER ) {
			return;
		}

		if ( y > lastY && y > HIDE_AFTER && ! header.contains( document.activeElement ) ) {
			header.classList.add( 'is-hidden' );
		} else if ( y < lastY ) {
			header.classList.remove( 'is-hidden' );
		}

		lastY = y;
	}

	function onScroll() {
		if ( ! frame ) {
			frame = window.requestAnimationFrame( update );
		}
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );

	header.addEventListener( 'focusin', function () {
		header.classList.remove( 'is-hidden' );
	} );

	update();
}() );
