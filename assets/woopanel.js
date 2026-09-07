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
		}
	}

	function initTheme() {
		var saved = 'system';
		try {
			saved = localStorage.getItem(STORAGE_KEY) || 'system';
		} catch (e) {
			saved = 'system';
		}
		var all = panels();
		for (var i = 0; i < all.length; i++) {
			applyTheme(all[i], saved);
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
			btn.textContent = show ? btn.getAttribute('data-wpl-label-close') || 'Close' : btn.getAttribute('data-wpl-label-open') || 'Edit';
		});
	}

	function init() {
		initTheme();
		bindThemeButtons();
		bindToggles();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
