/**
 * Forma Accordion link: opens the Nested Accordion item that the address points at (`/services/#architecture`).
 *
 * The browser scrolls to the item and may open its `details` element natively, which leaves Elementor's accordion out of
 * step (the panel is open but the title still says it is not). So the item is closed again and its summary clicked:
 * Elementor then opens it, shows its panel and keeps its own state. Elementor loads the accordion's script on demand, so
 * until it has arrived the click only toggles the native element; the item is looked at again for a few seconds, until
 * Elementor reports it open.
 */
( function () {
	'use strict';

	var tries = 0;

	var open = function () {
		var id = window.location.hash ? decodeURIComponent( window.location.hash.slice( 1 ) ) : '';
		var item = id ? document.getElementById( id ) : null;
		var summary = item && 'DETAILS' === item.tagName && item.classList.contains( 'e-n-accordion-item' ) ? item.querySelector( 'summary' ) : null;

		if ( ! summary || 'true' === summary.getAttribute( 'aria-expanded' ) ) {
			return;
		}

		item.open = false;
		summary.click();

		if ( ++tries < 10 ) {
			window.setTimeout( open, 400 );
		}
	};

	window.addEventListener( 'hashchange', function () {
		tries = 0;
		open();
	} );
	window.addEventListener( 'load', open );
} )();
