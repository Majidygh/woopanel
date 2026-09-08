/**
 * WooPanel — front-end behavior (theme switcher, edit toggles).
 * No dependencies.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'woopanel_theme';

	function panels() {
		return document.querySelectorAll('[data-woopanel]');
	}

	function applyTheme(panel, theme) {
		if (theme === 'system') {
			panel.removeAttribute('data-wpl-theme');
		} else {
			panel.setAttribute('data-wpl-theme', theme);
		}
		var btns = panel.querySelectorAll('[data-wpl-theme-set]');
		for (var i = 0; i < btns.length; i++) {
			var active = btns[i].getAttribute('data-wpl-theme-set') === theme;
			btns[i].classList.toggle('is-active', active);
			btns[i].setAttribute('aria-pressed', active ? 'true' : 'false');
		}
	}

	function savedTheme() {
		try {
			return localStorage.getItem(STORAGE_KEY) || 'system';
		} catch (e) {
			return 'system';
		}
	}

	function initTheme() {
		var theme = savedTheme();
		if (theme === 'system') {
			return; // default markup already means "system"
		}
		var all = panels();
		for (var i = 0; i < all.length; i++) {
			applyTheme(all[i], theme);
		}
	}

	function bindThemeButtons() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-theme-set]') : null;
			if (!btn) {
				return;
			}
			var panel = btn.closest('[data-woopanel]');
			if (!panel) {
				return;
			}
			var theme = btn.getAttribute('data-wpl-theme-set');
			applyTheme(panel, theme);
			try {
				localStorage.setItem(STORAGE_KEY, theme);
			} catch (e) {
				/* private mode */
			}
		});
	}

	/* ---- "Follow the site theme": read the store's real brand color from
	   its own rendered styles and paint the panel with it. ---- */

	function bindToggles() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-toggle]') : null;
			if (!btn) {
				return;
			}
			var target = document.getElementById(btn.getAttribute('data-wpl-toggle'));
			if (!target) {
				return;
			}
			var show = target.hidden;
			target.hidden = !show;
			btn.setAttribute('aria-expanded', show ? 'true' : 'false');
			btn.textContent = show
				? btn.getAttribute('data-wpl-label-close') || 'Close'
				: btn.getAttribute('data-wpl-label-open') || 'Edit';
		});
	}

	function initCopy() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-copy]') : null;
			if (!btn) {
				return;
			}
			var code = btn.getAttribute('data-wpl-copy') || '';
			var original = btn.getAttribute('data-wpl-original');
			var done = function () {
				if (original === null) {
					original = btn.innerHTML;
					btn.setAttribute('data-wpl-original', original);
				}
				btn.classList.add('wpl-copied');
				btn.textContent = '✓';
				window.setTimeout(function () {
					btn.innerHTML = original;
					btn.classList.remove('wpl-copied');
				}, 1600);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(code).then(done, function () { fallbackCopy(code); done(); });
			} else {
				fallbackCopy(code);
				done();
			}
		});
	}

	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		try { document.execCommand('copy'); } catch (e) {}
		document.body.removeChild(ta);
	}

	function initSpotlight() {
		var panels = document.querySelectorAll('[data-woopanel]');
		for (var i = 0; i < panels.length; i++) {
			(function (panel) {
				if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
					return;
				}
				panel.addEventListener('mousemove', function (ev) {
					var card = ev.target.closest ? ev.target.closest('.wpl-card, .wpl-trackcard') : null;
					if (!card || !panel.contains(card)) {
						return;
					}
					var r = card.getBoundingClientRect();
					card.style.setProperty('--wpl-mx', Math.round(ev.clientX - r.left) + 'px');
					card.style.setProperty('--wpl-my', Math.round(ev.clientY - r.top) + 'px');
				});
			})(panels[i]);
		}
	}

	function init() {
		initTheme();
		bindThemeButtons();
		bindToggles();
		initCopy();
		initSpotlight();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
