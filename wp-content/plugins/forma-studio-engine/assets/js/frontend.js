/**
 * FORMA — Frontend entry point.
 * Loads GSAP and registers modules conditionally.
 */
(function () {
	'use strict';

	var FORMA = (window.FORMA || {});

	FORMA.config = {
		reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
		isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
	};

	FORMA.utils = {
		$delegate: function (selector, handler) {
			return function (e) {
				var target = e.target.closest(selector);
				if (target) { handler.call(target, e); }
			};
		}
	};

	// GSAP init.
	if (window.gsap && typeof window.gsap.registerPlugin === 'function' && window.ScrollTrigger) {
		gsap.registerPlugin(window.ScrollTrigger);
		FORMA.gsap = window.gsap;
	}

	// Modules.
 document.addEventListener('DOMContentLoaded', function () {
		FORMA.initCursor();
		FORMA.initProjectGrid();
		FORMA.initProjectFilter();
		FORMA.initAnimatedHeadings();
		FORMA.initBeforeAfter();
		FORMA.initMarquee();
		FORMA.initPageTransition();
	});

	window.FORMA = FORMA;
})();