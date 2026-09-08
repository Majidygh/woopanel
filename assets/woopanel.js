/**
 * WooPanel — modern, dependency-free front-end script.
 * Handles appearance theme switching, form toggles, copy-to-clipboard, and print actions.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'woopanel_theme';

	function getPanels() {
		return document.querySelectorAll('[data-woopanel]');
	}

	function applyTheme(theme) {
		var all = getPanels();
		for (var i = 0; i < all.length; i++) {
			var panel = all[i];
			if (theme === 'system') {
				panel.removeAttribute('data-wpl-theme');
			} else {
				panel.setAttribute('data-wpl-theme', theme);
			}

			var btns = panel.querySelectorAll('[data-wpl-theme-set]');
			for (var b = 0; b < btns.length; b++) {
				var active = btns[b].getAttribute('data-wpl-theme-set') === theme;
				btns[b].classList.toggle('is-active', active);
				btns[b].setAttribute('aria-pressed', active ? 'true' : 'false');
			}
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
		applyTheme(theme);

		// Watch system color scheme changes if user chose 'system'
		if (window.matchMedia) {
			var mql = window.matchMedia('(prefers-color-scheme: dark)');
			var listener = function () {
				if (savedTheme() === 'system') {
					applyTheme('system');
				}
			};
			if (mql.addEventListener) {
				mql.addEventListener('change', listener);
			} else if (mql.addListener) {
				mql.addListener(listener);
			}
		}
	}

	function bindThemeButtons() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-theme-set]') : null;
			if (!btn) {
				return;
			}
			var theme = btn.getAttribute('data-wpl-theme-set');
			if (!theme) {
				return;
			}
			applyTheme(theme);
			try {
				localStorage.setItem(STORAGE_KEY, theme);
			} catch (e) {
				/* private browsing */
			}
		});
	}

	function bindToggles() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-toggle]') : null;
			if (!btn) {
				return;
			}
			var targetId = btn.getAttribute('data-wpl-toggle');
			var target = document.getElementById(targetId);
			if (!target) {
				return;
			}
			var isShowing = !target.hidden;
			target.hidden = isShowing;
			btn.setAttribute('aria-expanded', isShowing ? 'false' : 'true');

			var openLabel = btn.getAttribute('data-wpl-label-open') || 'Edit';
			var closeLabel = btn.getAttribute('data-wpl-label-close') || 'Close';
			btn.textContent = isShowing ? openLabel : closeLabel;
		});
	}

	function bindCopy() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-copy]') : null;
			if (!btn) {
				return;
			}
			var code = btn.getAttribute('data-wpl-copy') || '';
			if (!code) {
				return;
			}

			var originalHtml = btn.getAttribute('data-wpl-original');
			if (originalHtml === null) {
				originalHtml = btn.innerHTML;
				btn.setAttribute('data-wpl-original', originalHtml);
			}

			var showSuccess = function () {
				btn.classList.add('wpl-copied');
				btn.setAttribute('aria-label', 'کپی شد');
				var textSpan = btn.querySelector('.wpl-copy-text');
				if (textSpan) {
					textSpan.textContent = textSpan.getAttribute('data-done-text') || '✓ کپی شد';
				} else {
					btn.textContent = '✓ کپی شد';
				}
				window.setTimeout(function () {
					btn.innerHTML = originalHtml;
					btn.classList.remove('wpl-copied');
				}, 1800);
			};

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(code).then(showSuccess, function () {
					fallbackCopy(code);
					showSuccess();
				});
			} else {
				fallbackCopy(code);
				showSuccess();
			}
		});
	}

	function fallbackCopy(text) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		try {
			document.execCommand('copy');
		} catch (e) {}
		document.body.removeChild(ta);
	}

	function bindPrint() {
		document.addEventListener('click', function (ev) {
			var btn = ev.target.closest ? ev.target.closest('[data-wpl-print]') : null;
			if (!btn) {
				return;
			}
			window.print();
		});
	}

	function init() {
		initTheme();
		bindThemeButtons();
		bindToggles();
		bindCopy();
		bindPrint();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
