/**
 * FORMA — GSAP initialization helper.
 * Respects reduced-motion and loads ScrollTrigger when available.
 */
(function (window, FORMA) {
	'use strict';

	if (FORMA.config && FORMA.config.reducedMotion) {
		return;
	}

	function init() {
		if (!window.gsap || typeof window.gsap.registerPlugin !== 'function') {
			return;
		}
		if (window.ScrollTrigger) {
			gsap.registerPlugin(window.ScrollTrigger);
		}
		FORMA.gsap = window.gsap;
	}

	if (window.gsap) {
		init();
	} else {
		window.addEventListener('gsap-loaded', init);
	}

	FORMA.gsapReady = init;
})(window, window.FORMA || (window.FORMA = {}));