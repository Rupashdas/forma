/**
 * FORMA — Custom cursor.
 * Disabled on touch devices.
 */
(function (window, FORMA) {
	'use strict';

	if (FORMA.config && FORMA.config.isMobile) {
		return;
	}

	var cursor, dot, label;

	function create() {
		cursor = document.createElement('div');
		cursor.className = 'forma-cursor';
		label = document.createElement('span');
		label.className = 'forma-cursor__label';
		label.textContent = 'VIEW';
		cursor.appendChild(label);
		document.body.appendChild(cursor);

		dot = cursor;
	}

	function move(x, y) {
		if (!cursor) { return; }
		cursor.style.transform = 'translate3d(' + x + 'px, ' + y + 'px, 0) translate(-50%, -50%)';
	}

	function setHover(state) {
		if (!cursor) { return; }
		cursor.classList.toggle('is-hover', state);
	}

	function init() {
		create();
		document.addEventListener('mousemove', function (e) {
			move(e.clientX, e.clientY);
		});

		// Attach hover behavior to links/buttons/projects.
		var hoverTargets = document.querySelectorAll('a, button, .forma-project-card, [data-hover]');
		hoverTargets.forEach(function (el) {
			el.addEventListener('mouseenter', function () { setHover(true); });
			el.addEventListener('mouseleave', function () { setHover(false); });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.cursor = { move: move, setHover: setHover };
})(window, window.FORMA = window.FORMA || {});