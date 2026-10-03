/**
 * FORMA — Animated heading reveal via GSAP or IntersectionObserver.
 */
(function (window, FORMA) {
	'use strict';

	function init() {
		var headings = document.querySelectorAll('.forma-animated-heading[data-trigger="scroll"]');
		headings.forEach(function (heading) {
			if (FORMA.gsap && FORMA.config && !FORMA.config.reducedMotion) {
				observeWithGsap(heading);
			} else {
				observeSimple(heading);
			}
		});
	}

	function observeWithGsap(heading) {
		var words = heading.querySelectorAll('.forma-animated-heading__word, .forma-animated-heading__char');
		if (!words.length) { return; }

		gsap.fromTo(words, {
			opacity: 0,
			y: '1.2em'
		}, {
			opacity: 1,
			y: 0,
			stagger: 0.06,
			duration: 1,
			ease: 'power3.out',
			scrollTrigger: {
				trigger: heading,
				start: 'top 80%',
				once: true
			}
		});
	}

	function observeSimple(heading) {
		var words = heading.querySelectorAll('.forma-animated-heading__word, .forma-animated-heading__char');
		if (!words.length) { return; }

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					words.forEach(function (w, i) {
						setTimeout(function () {
							w.style.opacity = '1';
							w.style.transform = 'none';
						}, i * 60);
					});
					io.disconnect();
				}
			});
		}, { threshold: 0.2 });

		words.forEach(function (w) {
			w.style.opacity = '0';
			w.style.transform = 'translateY(1.2em)';
			w.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
		});
		io.observe(heading);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.initAnimatedHeadings = init;
})(window, window.FORMA = window.FORMA || {});