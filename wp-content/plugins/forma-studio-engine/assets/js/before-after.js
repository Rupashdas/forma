/**
 * FORMA — Before / After comparison slider.
 */
(function (window, FORMA) {
	'use strict';

	function init() {
		var widgets = document.querySelectorAll('.forma-before-after');
		widgets.forEach(function (widget) {
			bindWidget(widget);
		});
	}

	function bindWidget(widget) {
		var handle = widget.querySelector('.forma-before-after__handle');
		var before = widget.querySelector('.forma-before-after__before');
		if (!handle || !before) { return; }

		var start = parseFloat(widget.getAttribute('data-start') || '50');
		setPosition(widget, start);

		var dragging = false;

		function onMove(clientX) {
			var rect = widget.getBoundingClientRect();
			var x = clientX - rect.left;
			var pct = Math.max(0, Math.min(100, (x / rect.width) * 100));
			setPosition(widget, pct);
		}

		handle.addEventListener('mousedown', function (e) {
			dragging = true;
			document.body.style.userSelect = 'none';
		});

		document.addEventListener('mousemove', function (e) {
			if (!dragging) { return; }
			onMove(e.clientX);
		});

		document.addEventListener('mouseup', function () {
			dragging = false;
			document.body.style.userSelect = '';
		});

		// Touch support.
		handle.addEventListener('touchstart', function (e) {
			dragging = true;
		}, { passive: true });

		handle.addEventListener('touchmove', function (e) {
			if (!dragging) { return; }
			onMove(e.touches[0].clientX);
		}, { passive: true });

		handle.addEventListener('touchend', function () {
			dragging = false;
		});
	}

	function setPosition(widget, pct) {
		var handle = widget.querySelector('.forma-before-after__handle');
		var before = widget.querySelector('.forma-before-after__before');
		handle.style.left = pct + '%';
		if (before) {
			before.style.clipPath = 'inset(0 0 0 ' + pct + '%)';
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.initBeforeAfter = init;
})(window, window.FORMA = window.FORMA || {});