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

	function rgbToHex(r, g, b) {
		function h(n) { return ('0' + Math.round(n).toString(16)).slice(-2); }
		return '#' + h(r) + h(g) + h(b);
	}

	function parseColor(str) {
		if (!str) return null;
		var m = str.match(/^rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/);
		if (m) return [ +m[1], +m[2], +m[3] ];
		m = str.match(/^#([0-9a-f]{6})$/i);
		if (m) {
			var v = m[1];
			return [ parseInt(v.slice(0, 2), 16), parseInt(v.slice(2, 4), 16), parseInt(v.slice(4, 6), 16) ];
		}
		return null;
	}

	function isUsable(rgb) {
		var max = Math.max(rgb[0], rgb[1], rgb[2]);
		var min = Math.min(rgb[0], rgb[1], rgb[2]);
		if (max > 240 && min > 225) return false; // near-white
		if (max < 28) return false;               // near-black
		return (max - min) >= 24;                 // has chroma
	}

	function luminance(rgb) {
		var f = function (c) { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
		return 0.2126 * f(rgb[0]) + 0.7152 * f(rgb[1]) + 0.0722 * f(rgb[2]);
	}

	// Darken until the color carries white text (>= ~4.6:1 against white) —
	// the panel paints white text on the accent (hero, buttons), so a pale
	// site brand color (e.g. sky blue) must deepen before it can hold it.
	function ensureContrast(rgb) {
		var out = rgb.slice();
		for (var i = 0; i < 24 && luminance(out) > 0.175; i++) {
			out = out.map(function (c) { return c * 0.92; });
		}
		return out;
	}

	function detectSiteAccent() {
		var probes = [
			['a.custom-logo-link', 'color'],
			['.wp-block-button__link', 'backgroundColor'],
			['a.site-logo', 'color'],
			['header a', 'color'],
			['nav a', 'color'],
			['.wp-block-navigation a', 'color'],
			['a', 'color']
		];
		for (var i = 0; i < probes.length; i++) {
			var els = document.querySelectorAll(probes[i][0]);
			for (var j = 0; j < Math.min(els.length, 6); j++) {
				var rgb = parseColor(getComputedStyle(els[j])[probes[i][1]]);
				if (rgb && isUsable(rgb)) {
					return ensureContrast(rgb);
				}
			}
		}
		return null;
	}

	function applyAutoAccent(panel, rgb) {
		var hex = rgbToHex(rgb[0], rgb[1], rgb[2]);
		panel.style.setProperty('--wpl-accent', hex);
		panel.style.setProperty('--wpl-accent-rgb', Math.round(rgb[0]) + ' ' + Math.round(rgb[1]) + ' ' + Math.round(rgb[2]));
		// Soft tint: same hue at ~95% lightness.
		var soft = rgb.map(function (c) { return Math.round(c + (255 - c) * 0.9); });
		panel.style.setProperty('--wpl-accent-soft', rgbToHex(soft[0], soft[1], soft[2]));
	}

	function initAutoAccent() {
		var panelsWithAuto = document.querySelectorAll('[data-woopanel][data-wpl-auto-accent]');
		if (!panelsWithAuto.length) return;
		var rgb = detectSiteAccent();
		if (!rgb) return; // PHP already shipped a server-side guess as fallback.
		for (var i = 0; i < panelsWithAuto.length; i++) {
			applyAutoAccent(panelsWithAuto[i], rgb);
		}
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
			btn.setAttribute('aria-expanded', show ? 'true' : 'false');
			btn.textContent = show
				? btn.getAttribute('data-wpl-label-close') || 'Close'
				: btn.getAttribute('data-wpl-label-open') || 'Edit';
		});
	}

	function init() {
		initTheme();
		initAutoAccent();
		bindThemeButtons();
		bindToggles();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
