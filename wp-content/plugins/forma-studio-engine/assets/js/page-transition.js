/**
 * FORMA — Page transition layer.
 * Disabled by default; enable via filter.
 */
(function (window, FORMA) {
	'use strict';

	var layer;

	function init() {
		layer = document.querySelector('.forma-page-transition');
		if (!layer) { return; }

		// Fade out on link click (internal links only).
		document.addEventListener('click', function (e) {
			var target = e.target.closest('a');
			if (!target) { return; }
			var href = target.getAttribute('href');
			if (!href) { return; }
			if (href.indexOf('#') === 0) { return; }
			if (href.indexOf('javascript:') === 0) { return; }

			try {
				var url = new URL(href, window.location.origin);
				if (url.origin !== window.location.origin) { return; }
			} catch (err) {
				return;
			}

			transitionOut(function () {
				window.location.href = href;
			});
		});
	}

	function transitionOut(cb) {
		if (!layer) { cb(); return; }
		layer.style.opacity = '1';
		layer.style.transition = 'opacity 0.4s var(--forma-ease)';
		setTimeout(cb, 400);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.initPageTransition = init;
})(window, window.FORMA = window.FORMA || {});