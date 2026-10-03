/**
 * FORMA — Project filter chips with AJAX.
 */
(function (window, FORMA) {
	'use strict';

	function init() {
		var filters = document.querySelectorAll('.forma-project-filter');
		filters.forEach(function (filter) {
			bindFilter(filter);
		});
	}

	function bindFilter(filter) {
		var chips = filter.querySelectorAll('.forma-project-filter__chip');
		var search = filter.querySelector('.forma-project-filter__search-input');
		var grid = filter.parentElement.querySelector('.forma-project-grid');
		if (!grid) { return; }

		var nonce = filter.getAttribute('data-nonce');
		var taxonomy = filter.getAttribute('data-taxonomy');

		var active = new Set();

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				var value = chip.getAttribute('data-filter');
				if (value === 'all') {
					active.clear();
					chips.forEach(function (c) { c.classList.remove('is-active'); });
					chip.classList.add('is-active');
					fetchGrid(grid, nonce, taxonomy, active, '');
					return;
				}

				if (active.has(value)) {
					active.delete(value);
					chip.classList.remove('is-active');
				} else {
					active.add(value);
					chip.classList.add('is-active');
				}

				var allChip = filter.querySelector('[data-filter="all"]');
				if (allChip) { allChip.classList.remove('is-active'); }

				fetchGrid(grid, nonce, taxonomy, active, search ? search.value : '');
			});
		});

		if (search) {
			search.addEventListener('input', debounce(function () {
				fetchGrid(grid, nonce, taxonomy, active, search.value);
			}, 300));
		}
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(null, args); }, wait);
		};
	}

	function fetchGrid(grid, nonce, taxonomy, terms, search) {
		var fd = new FormData();
		fd.append('action', 'forma_filter_projects');
		fd.append('nonce', nonce);
		fd.append('filters[page]', 1);
		fd.append('filters[per_page]', parseInt(grid.getAttribute('data-per-page') || '12', 10));
		fd.append('filters[search]', search);
		if (terms && terms.size) {
			terms.forEach(function (t) {
				fd.append('filters[' + taxonomy + '][]', t);
			});
		}

		grid.style.opacity = '0.5';
		fetch(window.formaAjaxUrl || window.location.href, {
			method: 'POST',
			body: fd
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (res.success) {
				var inner = grid.querySelector('.forma-project-grid__inner');
				inner.innerHTML = '';
				res.data.projects.forEach(function (p) {
					var card = buildCard(p);
					if (card) { inner.appendChild(card); }
				});
				grid.setAttribute('data-page', 1);
			}
		})
		.catch(function () {})
		.finally(function () {
			grid.style.opacity = '';
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

	FORMA.initProjectFilter = init;
})(window, window.FORMA = window.FORMA || {});