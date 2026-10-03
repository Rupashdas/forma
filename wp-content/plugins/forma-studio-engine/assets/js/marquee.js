/**
 * FORMA — Marquee animation control.
 */
(function (window, FORMA) {
	'use strict';

	function init() {
		// CSS animation handles movement; JS only adds pause on hover.
		var marquees = document.querySelectorAll('.forma-marquee');
		marquees.forEach(function (m) {
			if (m.getAttribute('data-pause') === 'yes') {
				m.addEventListener('mouseenter', function () {
					var track = m.querySelector('.forma-marquee__track');
					if (track) { track.style.animationPlayState = 'paused'; }
				});
				m.addEventListener('mouseleave', function () {
					var track = m.querySelector('.forma-marquee__track');
					if (track) { track.style.animationPlayState = 'running'; }
				});
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.initMarquee = init;
})(window, window.FORMA = window.FORMA || {});