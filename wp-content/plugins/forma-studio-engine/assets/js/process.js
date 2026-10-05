/**
 * Forma Process: the stage readout. The Process page's study model grows through six stages as you scroll, and the
 * runtime announces each one with a `forma-model:stage` event. This writes "Stage 3 of 6, Development" into the pill laid
 * over the model, taking the stage's title from the glass card of the same number. The pill is decorative (the cards say
 * the same), so nothing here is announced.
 */
( function () {
	'use strict';

	var pill = document.querySelector( '.forma-stage-pill .elementor-heading-title' );
	var cards = document.querySelectorAll( '.forma-tour__card h2' );

	if ( ! pill || ! cards.length ) {
		return;
	}

	var holder = pill.closest( '.forma-stage-pill' );

	var show = function ( stage ) {
		var title = cards[ stage - 1 ];

		if ( title ) {
			pill.textContent = 'Stage ' + stage + ' of ' + cards.length + ', ' + title.textContent.trim();

			// The pill stays hidden until the model reports a stage, so a page that cannot draw the model never shows a stale one.
			if ( holder ) {
				holder.classList.add( 'is-live' );
			}
		}
	};

	document.addEventListener( 'forma-model:stage', function ( event ) {
		show( event.detail && event.detail.stage );
	} );

	// A page opened part way down has passed the first events by the time this runs: ask the views where they are.
	window.addEventListener( 'load', function () {
		var views = window.FormaModel && window.FormaModel.views ? Array.from( window.FormaModel.views ) : [];
		var view = views.find( function ( item ) {
			return item.b && item.b.stages;
		} );

		if ( view && view.stage ) {
			show( view.stage );
		}
	} );
} )();
