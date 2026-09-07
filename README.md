# WooPanel — Modern User Dashboard for WooCommerce

**WooPanel** replaces the dated WooCommerce "My Account" area with a clean, fast, modern customer dashboard — orders, downloads, addresses and account settings in one panel.

![WooPanel](assets/screenshot-1.png)

## Why another dashboard plugin?

We tested the popular options (NextDash, Advanced Customer Account, premium account-page builders) and kept running into the same problems:

| Problem in existing plugins | WooPanel |
|---|---|
| React bundles (hundreds of KB) or build steps required | **Zero build step** — plain PHP + ~7 KB of JS, works out of the zip |
| Unresolved AJAX/REST errors in support threads | **No AJAX at all** — server-rendered pages, redirect-based forms |
| English-only LTR layouts | **RTL-first** with logical CSS properties, plus `rtl.css` support |
| Dark mode locked behind premium tiers | **Dark mode included** — light / dark / system, remembered per browser |
| Settings deep inside WooCommerce menus | Simple **Settings → WooPanel** page with live accent colors |

## Features

- **Dashboard** — order count, total spent, average order and available downloads at a glance, plus recent orders.
- **Orders** — full history with status badges, status filters, pagination and a detailed order view.
- **Downloads** — all available downloadable files with remaining counts and expiry dates.
- **Addresses** — inline edit for billing & shipping, powered by WooCommerce's own address fields and customer CRUD.
- **Account** — update name/email, change password (requires current password).
- **My Account takeover (optional)** — replace the default dashboard area without breaking existing endpoints.
- **Theme switcher** — light / dark / follow-system, persisted in `localStorage`.
- **Customizable accent color** via CSS variables — change it once in settings, the whole panel follows.
- **HPOS compatible** (WooCommerce custom order tables).
- **Translation ready** (`woopanel` text domain).
- **Mobile responsive** with a sidebar→tab navigation on small screens.

## Security model

- All form submissions are **server-side POST with nonces** and capability/ownership checks — no guest-reachable AJAX endpoints.
- Order detail view enforces **strict ownership** (`order->get_user_id() === current user`).
- Password change requires the **current password** and re-authenticates the user afterwards.
- All output escaped; inputs sanitized with WooCommerce-native sanitizers.

## Installation

1. Download the latest zip from [Releases](https://github.com/Majidygh/woopanel/releases).
2. WordPress admin → Plugins → Add New → Upload Plugin → choose the zip → Activate.
3. Add the `[woopanel]` shortcode to any page (or enable My Account takeover in settings).

## Usage

```
[woopanel]                 → full panel with navigation
[woopanel view="orders"]   → open on a specific view
```

Views: `dashboard`, `orders`, `downloads`, `address`, `account`.

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Credits

Created by [majidygh](https://github.com/majidygh).

## License

GPL-2.0-or-later
