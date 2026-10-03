/**
 * FORMA — Drawing / Built. Mirrors each range's value into --forma-ba and keeps its spoken value in sync.
 */
( () => {
	document.querySelectorAll( '.forma-ba' ).forEach( ( figure ) => {
		const range = figure.querySelector( '.forma-ba__range' );
		const label = figure.dataset.after || '';

		if ( ! range ) {
			return;
		}

		range.addEventListener( 'input', () => {
			figure.style.setProperty( '--forma-ba', `${ range.value }%` );
			range.setAttribute( 'aria-valuetext', `${ range.value }% ${ label }`.trim() );
		} );
	} );
} )();
