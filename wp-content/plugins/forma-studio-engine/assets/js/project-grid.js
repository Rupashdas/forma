/**
 * FORMA — Project grid + load more + AJAX filtering integration.
 */
(function (window, FORMA) {
	'use strict';

	function init() {
		var grids = document.querySelectorAll('.forma-project-grid');
		grids.forEach(function (grid) {
			bindLoadMore(grid);
		});
	}

	function bindLoadMore(grid) {
		var btn = grid.querySelector('.forma-project-grid__load-more-btn');
		if (!btn) { return; }

		btn.addEventListener('click', function () {
			var page = parseInt(grid.getAttribute('data-page') || '1', 10);
			var perPage = parseInt(grid.getAttribute('data-per-page') || '12', 10);
			var nonce = grid.getAttribute('data-nonce');

			btn.disabled = true;
			btn.textContent = 'Loading…';

			var fd = new FormData();
			fd.append('action', 'forma_filter_projects');
			fd.append('nonce', nonce);
			fd.append('filters[page]', page + 1);
			fd.append('filters[per_page]', perPage);

			fetch(window.formaAjaxUrl || window.location.href, {
				method: 'POST',
				body: fd
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success && res.data.projects.length) {
					var inner = grid.querySelector('.forma-project-grid__inner');
					res.data.projects.forEach(function (p) {
						var card = buildCard(p);
						if (card) { inner.appendChild(card); }
					});
					grid.setAttribute('data-page', page + 1);
					if (res.data.current_page >= res.data.max_pages) {
						btn.parentElement.removeChild(btn);
					}
				} else {
					btn.parentElement.removeChild(btn);
				}
			})
			.catch(function () {
				btn.parentElement.removeChild(btn);
			})
			.finally(function () {
				btn.disabled = false;
			});
		});
	}

	function buildCard(p) {
		if (!p || !p.id) { return null; }
		var card = document.createElement('a');
		card.className = 'forma-project-card';
		card.href = p.link;
		card.setAttribute('aria-label', p.title);

		var img = document.createElement('div');
		img.className = 'forma-project-card__image';
		if (p.image) {
			var i = document.createElement('img');
			i.src = p.image;
			i.alt = p.title;
			img.appendChild(i);
		}

		var body = document.createElement('div');
		body.className = 'forma-project-card__body';

		var cat = document.createElement('span');
		cat.className = 'forma-project-card__category';
		cat.textContent = p.category || '';

		var title = document.createElement('div');
		title.className = 'forma-project-card__title';
		title.textContent = p.title;

		var meta = document.createElement('div');
		meta.className = 'forma-project-card__meta';
		meta.textContent = [p.location, p.year].filter(Boolean).join(' · ');

		body.appendChild(cat);
		body.appendChild(title);
		body.appendChild(meta);
		card.appendChild(img);
		card.appendChild(body);
		return card;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	FORMA.initProjectGrid = init;
})(window, window.FORMA = window.FORMA || {});