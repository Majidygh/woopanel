#!/usr/bin/env python3
"""Generate pixel-perfect high-resolution screenshots for WooPanel README using headless Chrome."""
import os, subprocess, pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
SCREENSHOTS_DIR = ROOT / 'docs' / 'screenshots'
SCREENSHOTS_DIR.mkdir(parents=True, exist_ok=True)
SCRATCH_DIR = ROOT / 'scratch_previews'
SCRATCH_DIR.mkdir(exist_ok=True)

CHROME_PATH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
if not os.path.exists(CHROME_PATH):
    CHROME_PATH = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

ICONS = {
    'grid': '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'bag': '<path d="M6 7h12l1.2 13.1a1 1 0 0 1-1 1.1H5.8a1 1 0 0 1-1-1.1L6 7z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
    'download': '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
    'pin': '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    'user': '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
    'sun': '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
    'moon': '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>',
    'monitor': '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
    'chevron': '<path d="m9 6 6 6-6 6"/>',
    'check': '<circle cx="12" cy="12" r="10"/><path d="m8.5 12.5 2.5 2.5 5-5.5"/>',
    'alert': '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
    'box': '<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
    'card': '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
    'wallet': '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
    'file': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15h6"/>',
    'truck': '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    'copy': '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
    'external': '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
    'printer': '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
    'store': '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><circle cx="12" cy="12" r="2"/>',
    'logout': '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
}

def icon(name, size=18):
    p = ICONS.get(name, '')
    return f'<svg class="wpl-icon wpl-icon--{name}" width="{size}" height="{size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{p}</svg>'

RAW_CSS = (ROOT / 'assets' / 'woopanel-rtl.css').read_text(encoding='utf-8')

# Fix relative font url to absolute file uri for Chrome
FONTS_DIR = (ROOT / 'assets' / 'fonts').as_uri()
RAW_CSS = RAW_CSS.replace('url("fonts/', f'url("{FONTS_DIR}/')

def template(content, theme="light", active_nav="dashboard", is_mobile=False):
    sidebar_html = f'''
    <aside class="wpl-sidebar">
        <div class="wpl-sidebar__brand">
            <div class="wpl-brand">
                <div class="wpl-brand__logo" aria-hidden="true">و</div>
                <div class="wpl-brand__text">
                    <span class="wpl-brand__title">ووپنل</span>
                    <span class="wpl-brand__badge">پورتال مشتریان</span>
                </div>
            </div>
        </div>

        <nav class="wpl-nav" aria-label="منوی پنل">
            <div class="wpl-nav__list">
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'dashboard' else ''}">
                    <span class="wpl-nav__icon">{icon('grid', 18)}</span>
                    <span class="wpl-nav__label">پیشخوان</span>
                </a>
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'orders' else ''}">
                    <span class="wpl-nav__icon">{icon('bag', 18)}</span>
                    <span class="wpl-nav__label">سفارش‌ها</span>
                    <span class="wpl-nav__badge">۱۲</span>
                </a>
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'downloads' else ''}">
                    <span class="wpl-nav__icon">{icon('download', 18)}</span>
                    <span class="wpl-nav__label">دانلودها</span>
                    <span class="wpl-nav__badge">۴</span>
                </a>
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'address' else ''}">
                    <span class="wpl-nav__icon">{icon('pin', 18)}</span>
                    <span class="wpl-nav__label">نشانی‌ها</span>
                </a>
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'account' else ''}">
                    <span class="wpl-nav__icon">{icon('user', 18)}</span>
                    <span class="wpl-nav__label">حساب کاربری</span>
                </a>
                <a href="#" class="wpl-nav__item {'wpl-nav__item--active' if active_nav == 'tracking' else ''}">
                    <span class="wpl-nav__icon">{icon('truck', 18)}</span>
                    <span class="wpl-nav__label">رهگیری مرسوله</span>
                </a>
            </div>
        </nav>

        <div class="wpl-sidebar__footer">
            <div class="wpl-theme-wrap">
                <div class="wpl-theme" data-wpl-theme role="group" aria-label="ظاهر پنل">
                    <button type="button" class="wpl-theme__btn {'wpl-theme__btn--active' if theme == 'light' else ''}">
                        {icon('sun', 14)}
                        <span>روشن</span>
                    </button>
                    <button type="button" class="wpl-theme__btn">
                        {icon('monitor', 14)}
                        <span>خودکار</span>
                    </button>
                    <button type="button" class="wpl-theme__btn {'wpl-theme__btn--active' if theme == 'dark' else ''}">
                        {icon('moon', 14)}
                        <span>تیره</span>
                    </button>
                </div>
            </div>

            <div class="wpl-usercard">
                <div class="wpl-usercard__avatar" aria-hidden="true">ک</div>
                <div class="wpl-usercard__info">
                    <span class="wpl-usercard__name">کاربر گرامی</span>
                    <span class="wpl-usercard__email">user@example.com</span>
                </div>
                <a href="#" class="wpl-usercard__logout" title="خروج">
                    {icon('logout', 16)}
                </a>
            </div>
        </div>
    </aside>
    '''

    wrap_style = "max-width: 420px; margin: 0 auto;" if is_mobile else "max-width: 1200px; margin: 0 auto;"

    return f'''<!DOCTYPE html>
<html lang="fa" dir="rtl" data-wpl-theme="{theme}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WooPanel Preview</title>
    <style>
        {RAW_CSS}

        body {{
            margin: 0;
            padding: { '12px' if is_mobile else '28px' };
            background: { '#040711' if theme == 'dark' else '#f1f5f9' };
            box-sizing: border-box;
            font-family: "Vazirmatn", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }}
        .wpl-preview-wrapper {{
            {wrap_style}
        }}
    </style>
</head>
<body>
    <div class="wpl-preview-wrapper">
        <div class="wpl-panel wpl-panel--takeover" data-woopanel data-wpl-theme="{theme}">
            <div class="wpl-shell">
                {sidebar_html}
                <main class="wpl-main">
                    {content}
                </main>
            </div>
        </div>
    </div>
</body>
</html>'''

DASHBOARD_CONTENT = f'''
<div class="wpl-main__top">
    <div class="wpl-main__titles">
        <h2 class="wpl-main__title">پیشخوان</h2>
    </div>
</div>

<div class="wpl-welcome-card">
    <div class="wpl-welcome-card__content">
        <div class="wpl-welcome-card__avatar" aria-hidden="true">ک</div>
        <div class="wpl-welcome-card__text">
            <h3 class="wpl-welcome-card__greeting">سلام، کاربر گرامی 👋</h3>
            <p class="wpl-welcome-card__sub">خلاصه‌ای از سفارش‌ها و وضعیت حساب کاربری شما.</p>
        </div>
    </div>
    <div class="wpl-welcome-card__badge">
        <span class="wpl-pulse-dot" aria-hidden="true"></span>
        <span>پورتال مشتریان</span>
    </div>
</div>

<div class="wpl-track-banner">
    <div class="wpl-track-banner__icon">
        {icon('truck', 22)}
    </div>
    <div class="wpl-track-banner__info">
        <span class="wpl-track-banner__title">مرسوله‌ی سفارش ۲۸ در حال ارسال است</span>
        <div class="wpl-track-banner__meta">
            <span class="wpl-track-banner__label">کد رهگیری:</span>
            <code class="wpl-code-pill" dir="ltr">14030908000123456789</code>
        </div>
    </div>
    <div class="wpl-track-banner__actions">
        <button type="button" class="wpl-btn wpl-btn--xs">
            {icon('copy', 14)}
            <span>کپی</span>
        </button>
        <a href="#" class="wpl-btn wpl-btn--xs">
            {icon('external', 14)}
            <span>رهگیری بسته</span>
        </a>
    </div>
</div>

<div class="wpl-cards">
    <div class="wpl-card">
        <div class="wpl-card__icon">{icon('bag', 22)}</div>
        <div class="wpl-card__body">
            <span class="wpl-card__label">سفارش‌ها</span>
            <span class="wpl-card__value">۱۲</span>
            <span class="wpl-card__hint">کل سفارش‌های ثبت‌شده</span>
        </div>
    </div>

    <div class="wpl-card">
        <div class="wpl-card__icon">{icon('wallet', 22)}</div>
        <div class="wpl-card__body">
            <span class="wpl-card__label">مجموع خرید</span>
            <span class="wpl-card__value">۲۴,۵۰۰,۰۰۰ تومان</span>
            <span class="wpl-card__hint">پرداخت‌های موفق</span>
        </div>
    </div>

    <div class="wpl-card">
        <div class="wpl-card__icon">{icon('card', 22)}</div>
        <div class="wpl-card__body">
            <span class="wpl-card__label">میانگین سفارش</span>
            <span class="wpl-card__value">۲,۰۴۱,۶۶۶ تومان</span>
            <span class="wpl-card__hint">ارزش هر خرید</span>
        </div>
    </div>

    <div class="wpl-card">
        <div class="wpl-card__icon">{icon('file', 22)}</div>
        <div class="wpl-card__body">
            <span class="wpl-card__label">دانلودهای موجود</span>
            <span class="wpl-card__value">۴</span>
            <span class="wpl-card__hint">فایل‌ها و دارایی‌های دیجیتال</span>
        </div>
    </div>
</div>

<div class="wpl-section">
    <div class="wpl-section__head">
        <div class="wpl-section__title-group">
            <span class="wpl-section__icon">{icon('bag', 18)}</span>
            <h4>سفارش‌های اخیر</h4>
        </div>
        <a class="wpl-link" href="#">
            <span>مشاهده‌ی همه</span>
            {icon('chevron', 13)}
        </a>
    </div>

    <div class="wpl-table-wrap">
        <table class="wpl-table">
            <thead>
                <tr>
                    <th>سفارش</th>
                    <th>اقلام</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>مجموع</th>
                    <th class="wpl-table__action-col">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="wpl-table__order-id">
                        <a class="wpl-orderlink" href="#">#۲۸</a>
                    </td>
                    <td class="wpl-table__items">
                        <div class="wpl-items-preview">
                            <span class="wpl-thumb-wrap" style="background:#e0e7ff; color:#3730a3; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👕</span>
                            <span class="wpl-thumb-wrap" style="background:#fce7f3; color:#9d174d; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👖</span>
                            <span class="wpl-thumb-more">+۱</span>
                        </div>
                    </td>
                    <td>۱۸ شهریور ۱۴۰۳</td>
                    <td><span class="wpl-badge wpl-badge--completed">تکمیل شده</span></td>
                    <td class="wpl-table__total"><strong>۳,۴۰۰,۰۰۰ تومان</strong></td>
                    <td class="wpl-table__action-col">
                        <a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a>
                    </td>
                </tr>
                <tr>
                    <td class="wpl-table__order-id">
                        <a class="wpl-orderlink" href="#">#۲۷</a>
                    </td>
                    <td class="wpl-table__items">
                        <div class="wpl-items-preview">
                            <span class="wpl-thumb-wrap" style="background:#dbeafe; color:#1e40af; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">🧥</span>
                            <span class="wpl-thumb-wrap" style="background:#ede9fe; color:#5b21b6; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">🧢</span>
                        </div>
                    </td>
                    <td>۱۲ شهریور ۱۴۰۳</td>
                    <td><span class="wpl-badge wpl-badge--processing">در حال انجام</span></td>
                    <td class="wpl-table__total"><strong>۱,۸۵۰,۰۰۰ تومان</strong></td>
                    <td class="wpl-table__action-col">
                        <a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a>
                    </td>
                </tr>
                <tr>
                    <td class="wpl-table__order-id">
                        <a class="wpl-orderlink" href="#">#۲۶</a>
                    </td>
                    <td class="wpl-table__items">
                        <div class="wpl-items-preview">
                            <span class="wpl-thumb-wrap" style="background:#fef3c7; color:#b45309; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👟</span>
                        </div>
                    </td>
                    <td>۰۵ شهریور ۱۴۰۳</td>
                    <td><span class="wpl-badge wpl-badge--pending">در انتظار پرداخت</span></td>
                    <td class="wpl-table__total"><strong>۴,۲۰۰,۰۰۰ تومان</strong></td>
                    <td class="wpl-table__action-col">
                        <a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
'''

ORDERS_CONTENT = f'''
<div class="wpl-main__top">
    <div class="wpl-main__titles">
        <h2 class="wpl-main__title">سفارش‌ها</h2>
    </div>
</div>

<div class="wpl-filters" role="navigation" aria-label="فیلتر وضعیت سفارش‌ها">
    <a href="#" class="wpl-chip wpl-chip--active">همه <span class="wpl-chip__count">۱۲</span></a>
    <a href="#" class="wpl-chip">در حال انجام <span class="wpl-chip__count">۲</span></a>
    <a href="#" class="wpl-chip">تکمیل شده <span class="wpl-chip__count">۸</span></a>
    <a href="#" class="wpl-chip">در انتظار پرداخت <span class="wpl-chip__count">۱</span></a>
    <a href="#" class="wpl-chip">لغو شده <span class="wpl-chip__count">۱</span></a>
</div>

<div class="wpl-table-wrap">
    <table class="wpl-table">
        <thead>
            <tr>
                <th>سفارش</th>
                <th>اقلام</th>
                <th>تاریخ</th>
                <th>وضعیت</th>
                <th>مجموع</th>
                <th class="wpl-table__action-col">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="wpl-table__order-id"><a class="wpl-orderlink" href="#">#۲۸</a></td>
                <td class="wpl-table__items">
                    <div class="wpl-items-preview">
                        <span class="wpl-thumb-wrap" style="background:#e0e7ff; color:#3730a3; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👕</span>
                        <span class="wpl-thumb-wrap" style="background:#fce7f3; color:#9d174d; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👖</span>
                        <span class="wpl-thumb-more">+۱</span>
                    </div>
                </td>
                <td>۱۸ شهریور ۱۴۰۳</td>
                <td><span class="wpl-badge wpl-badge--completed">تکمیل شده</span></td>
                <td class="wpl-table__total"><strong>۳,۴۰۰,۰۰۰ تومان</strong></td>
                <td class="wpl-table__action-col"><a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a></td>
            </tr>
            <tr>
                <td class="wpl-table__order-id"><a class="wpl-orderlink" href="#">#۲۷</a></td>
                <td class="wpl-table__items">
                    <div class="wpl-items-preview">
                        <span class="wpl-thumb-wrap" style="background:#dbeafe; color:#1e40af; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">🧥</span>
                        <span class="wpl-thumb-wrap" style="background:#ede9fe; color:#5b21b6; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">🧢</span>
                    </div>
                </td>
                <td>۱۲ شهریور ۱۴۰۳</td>
                <td><span class="wpl-badge wpl-badge--processing">در حال انجام</span></td>
                <td class="wpl-table__total"><strong>۱,۸۵۰,۰۰۰ تومان</strong></td>
                <td class="wpl-table__action-col"><a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a></td>
            </tr>
            <tr>
                <td class="wpl-table__order-id"><a class="wpl-orderlink" href="#">#۲۶</a></td>
                <td class="wpl-table__items">
                    <div class="wpl-items-preview">
                        <span class="wpl-thumb-wrap" style="background:#fef3c7; color:#b45309; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">👟</span>
                    </div>
                </td>
                <td>۰۵ شهریور ۱۴۰۳</td>
                <td><span class="wpl-badge wpl-badge--pending">در انتظار پرداخت</span></td>
                <td class="wpl-table__total"><strong>۴,۲۰۰,۰۰۰ تومان</strong></td>
                <td class="wpl-table__action-col"><a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a></td>
            </tr>
            <tr>
                <td class="wpl-table__order-id"><a class="wpl-orderlink" href="#">#۲۵</a></td>
                <td class="wpl-table__items">
                    <div class="wpl-items-preview">
                        <span class="wpl-thumb-wrap" style="background:#e0e7ff; color:#3730a3; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:16px;">🎒</span>
                    </div>
                </td>
                <td>۲۲ مرداد ۱۴۰۳</td>
                <td><span class="wpl-badge wpl-badge--completed">تکمیل شده</span></td>
                <td class="wpl-table__total"><strong>۹۵۰,۰۰۰ تومان</strong></td>
                <td class="wpl-table__action-col"><a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="#">مشاهده</a></td>
            </tr>
        </tbody>
    </table>
</div>

<nav class="wpl-pagination" aria-label="صفحه‌بندی">
    <span class="wpl-page wpl-page--current">۱</span>
    <a href="#" class="wpl-page">۲</a>
    <a href="#" class="wpl-page">۳</a>
    <a href="#" class="wpl-page wpl-page--next">بعدی</a>
</nav>
'''

ORDER_DETAIL_CONTENT = f'''
<div class="wpl-order-detail">
    <div class="wpl-order-head">
        <a class="wpl-backlink" href="#">
            {icon('chevron', 14)}
            <span>بازگشت به سفارش‌ها</span>
        </a>
        <div class="wpl-order-head__actions">
            <button type="button" class="wpl-btn wpl-btn--secondary wpl-btn--sm">
                {icon('printer', 15)}
                <span>چاپ فاکتور</span>
            </button>
        </div>
    </div>

    <div class="wpl-order-summary">
        <div class="wpl-order-summary__id">
            <h2>سفارش #۲۸</h2>
            <span class="wpl-badge wpl-badge--completed">تکمیل شده</span>
        </div>
        <div class="wpl-order-summary__meta">
            <span>تاریخ ثبت: ۱۸ شهریور ۱۴۰۳</span>
            <span class="wpl-sep">·</span>
            <span>مبلغ کل: <strong>۳,۴۶۵,۰۰۰ تومان</strong></span>
        </div>
    </div>

    <div class="wpl-track-banner">
        <div class="wpl-track-banner__icon">{icon('truck', 22)}</div>
        <div class="wpl-track-banner__info">
            <span class="wpl-track-banner__title">مرسوله‌ی این سفارش ارسال شده است</span>
            <div class="wpl-track-banner__meta">
                <span class="wpl-track-banner__label">کد رهگیری پستی:</span>
                <code class="wpl-code-pill" dir="ltr">14030908000123456789</code>
            </div>
        </div>
        <div class="wpl-track-banner__actions">
            <button type="button" class="wpl-btn wpl-btn--xs">{icon('copy', 14)} <span>کپی</span></button>
            <a href="#" class="wpl-btn wpl-btn--xs">{icon('external', 14)} <span>رهگیری در سامانه پست</span></a>
        </div>
    </div>

    <div class="wpl-section">
        <div class="wpl-section__head">
            <div class="wpl-section__title-group">
                <span class="wpl-section__icon">{icon('bag', 18)}</span>
                <h4>اقلام سفارش</h4>
            </div>
        </div>

        <div class="wpl-table-wrap">
            <table class="wpl-table">
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>تعداد</th>
                        <th>جمع جزء</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="wpl-thumb-wrap" style="background:#e0e7ff; color:#3730a3; display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:10px; font-size:20px;">👕</span>
                                <div>
                                    <div style="font-weight:600; color:var(--wpl-text);">پیراهن لینن نچرال مردانه</div>
                                    <div style="font-size:12px; color:var(--wpl-muted);">سایز: L | رنگ: کرم</div>
                                </div>
                            </div>
                        </td>
                        <td>۱</td>
                        <td><strong>۱,۲۰۰,۰۰۰ تومان</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="wpl-thumb-wrap" style="background:#fce7f3; color:#9d174d; display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:10px; font-size:20px;">👖</span>
                                <div>
                                    <div style="font-weight:600; color:var(--wpl-text);">شلوار کتان راسته کلاسیک</div>
                                    <div style="font-size:12px; color:var(--wpl-muted);">سایز: ۳۲ | رنگ: دودی</div>
                                </div>
                            </div>
                        </td>
                        <td>۱</td>
                        <td><strong>۱,۹۵۰,۰۰۰ تومان</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="wpl-thumb-wrap" style="background:#fef3c7; color:#92400e; display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:10px; font-size:20px;">🧦</span>
                                <div>
                                    <div style="font-weight:600; color:var(--wpl-text);">جوراب ساقدار نخی ارگانیک</div>
                                    <div style="font-size:12px; color:var(--wpl-muted);">پک ۳ عددی</div>
                                </div>
                            </div>
                        </td>
                        <td>۲</td>
                        <td><strong>۲۵۰,۰۰۰ تومان</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
'''

TARGETS = [
    {
        'name': 'dashboard.png',
        'html': template(DASHBOARD_CONTENT, theme="light", active_nav="dashboard"),
        'width': 1280,
        'height': 920,
    },
    {
        'name': 'dark.png',
        'html': template(DASHBOARD_CONTENT, theme="dark", active_nav="dashboard"),
        'width': 1280,
        'height': 920,
    },
    {
        'name': 'orders.png',
        'html': template(ORDERS_CONTENT, theme="light", active_nav="orders"),
        'width': 1280,
        'height': 820,
    },
    {
        'name': 'order-detail.png',
        'html': template(ORDER_DETAIL_CONTENT, theme="light", active_nav="orders"),
        'width': 1280,
        'height': 920,
    },
    {
        'name': 'mobile.png',
        'html': template(DASHBOARD_CONTENT, theme="light", active_nav="dashboard", is_mobile=True),
        'width': 440,
        'height': 980,
    }
]

for t in TARGETS:
    html_file = SCRATCH_DIR / f"{t['name']}.html"
    html_file.write_text(t['html'], encoding='utf-8')
    out_png = SCREENSHOTS_DIR / t['name']

    cmd = [
        CHROME_PATH,
        "--headless",
        "--disable-gpu",
        "--hide-scrollbars",
        f"--window-size={t['width']},{t['height']}",
        f"--screenshot={str(out_png)}",
        html_file.as_uri()
    ]
    print(f"Rendering {t['name']} ({t['width']}x{t['height']})...")
    subprocess.run(cmd, check=True)
    print(f"Saved {out_png} ({out_png.stat().st_size} bytes)")

print("All screenshots successfully generated!")
