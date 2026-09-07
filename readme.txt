== WooPanel ==
Contributors: majidygh
Tags: woocommerce, dashboard, my account, customer panel, rtl
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern user dashboard for WooCommerce — orders, downloads, addresses and account settings in one clean panel.

== Description ==

WooPanel replaces the dated WooCommerce "My Account" area with a clean, fast, modern customer dashboard.

* Dashboard with order stats and recent orders
* Order history with status badges, filters and pagination
* Detailed order view with strict ownership checks
* Downloads center with remaining counts and expiry dates
* Inline billing & shipping address editing (WooCommerce-native fields)
* Account settings + password change
* Optional My Account takeover (endpoints keep working)
* Dark mode: light / dark / system
* RTL-ready with logical CSS properties
* Zero build step — no React, no npm, works from the zip
* HPOS compatible, translation ready

= Usage =

Add `[woopanel]` to any page, or `[woopanel view="orders"]` to open a specific view.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New.
2. Activate the plugin.
3. Add the `[woopanel]` shortcode to a page, or enable "Replace My Account dashboard" in Settings → WooPanel.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. WooPanel displays WooCommerce customer data and requires an active WooCommerce installation.

= Does it change my WooCommerce endpoints? =

No. The optional takeover only replaces the dashboard area; all My Account endpoints (orders, downloads, addresses, account) keep working.

= Is it RTL friendly? =

Yes — the stylesheet is built with CSS logical properties and is RTL-first.

= Where are the settings? =

Settings → WooPanel: accent colors, panel title, welcome word, orders per page, My Account takeover.

== Changelog ==

= 1.0.1 =
* Bundled complete Persian (fa_IR) translation — panel UI is fully localized on Persian sites.
* Fixed: plugin never called `load_plugin_textdomain()`, so translations never loaded.
* Fixed: `panel_title` / `welcome_text` options stored English defaults at activation and never re-translated; now empty options fall back to translated strings at render time.
* Fixed: bidi scrambling when a Latin username appears inside Persian sentences (`<bdi>` isolation).

= 1.0.0 =
* Initial release.
