<div dir="rtl">

# ووپنل — پنل کاربری مدرن برای ووکامرس

**ووپنل** ناحیه‌ی «حساب کاربری» ووکامرس را با یک پنل مدرن، سریع و تماماً فارسی جایگزین می‌کند — سفارش‌ها، دانلودها، نشانی‌ها و اطلاعات حساب در یک پنل تمیز.

<p align="center">
  <img src="docs/screenshots/dashboard.png" width="720" alt="پیشخوان ووپنل">
</p>

## چرا ووپنل؟

پلاگین‌های پنل کاربری موجود یا سنگین‌اند (React، build step، ده‌ها فایل)، یا نسخه‌ی رایگان‌شان قفل است، یا RTL را جدی نگرفته‌اند. ووپنل با این اصول ساخته شده:

- **بدون build step** — PHP خالص و رندر سمت سرور؛ هیچ dependency جاوااسکریپتی
- **فارسی اول** — فونت وزیرمتن باندل‌شده، ارقام فارسی (۰-۹) در قیمت‌ها و تاریخ‌ها، چیدمان کامل RTL
- **سبک و امن** — ۱۲ فایل، بدون endpoint عمومی AJAX

## امکانات

| بخش | توضیح |
|---|---|
| پیشخوان | تعداد سفارش، مجموع خرید، میانگین سفارش، دانلودهای موجود + سفارش‌های اخیر |
| سفارش‌ها | تاریخچه کامل با فیلتر وضعیت، صفحه‌بندی و جزئیات سفارش با اقلام و جمع فاکتور |
| دانلودها | فایل‌های قابل دانلود با تعداد باقی‌مانده و تاریخ انقضا |
| نشانی‌ها | ویرایش درجای صورت‌حساب و حمل‌ونقل با فیلدهای خود ووکامرس |
| حساب کاربری | ویرایش نام/ایمیل، تغییر رمز عبور (با تأیید رمز فعلی) |

<p align="center">
  <img src="docs/screenshots/orders.png" width="720" alt="سفارش‌ها">
</p>

- **سه حالت تم**: روشن / تیره / هماهنگ با سیستم — با یک کلیک، ذخیره در مرورگر
- **رنگ اصلی دلخواه**: از تنظیمات عوضش کنید؛ کل پنل (دکمه‌ها، لینک‌ها، آیکون‌ها) دنبالش می‌رود
- **جایگزینی اختیاری My Account**: پیشخوان پیش‌فرض ووکامرس جایگزین می‌شود و endpointهای دیگر دست‌نخورده کار می‌کنند
- **سازگار با HPOS** (جدول سفارش سفارشی ووکامرس)
- **ریسپانسیو**: در موبایل سایدبار به نوار تب افقی تبدیل می‌شود
- **ترجمه‌پذیر** (دامنه‌ی `woopanel`) — ترجمه‌ی کامل فارسی همراه پلاگین است

<p align="center">
  <img src="docs/screenshots/dark.png" width="720" alt="حالت تیره">
</p>

## نصب

1. از [بخش Releases](https://github.com/Majidygh/woopanel/releases) فایل `woopanel.zip` را دانلود کنید
2. پیشخوان وردپرس → افزونه‌ها → افزودن → بارگذاری افزونه
3. فعال کنید و شورت‌کد را در یک صفحه بگذارید:

```
[woopanel]
```

باز کردن مستقیم یک بخش:

```
[woopanel view="orders"]
```

## نحوه‌ی کار با ترجمه

ووپنل رشته‌هایش را با دامنه‌ی `woopanel` بارگذاری می‌کند. روی سایت‌های فارسی (fa_IR) رابط به‌صورت خودکار فارسی و راست‌چین می‌شود؛ متن عنوان پنل و کلمه‌ی خوش‌آمد هم از تنظیمات قابل تغییر است.

## امنیت

- همه‌ی فرم‌ها **POST سمت سرور با nonce** و بررسی مالکیت — هیچ endpoint AJAX مهمان‌دستی وجود ندارد
- نمای جزئیات سفارش **مالکیت را سختگیرانه چک می‌کند** (`order->get_user_id() === کاربر جاری`)
- هیچ اطلاعات شخصی در URL منتقل نمی‌شود (الگوی PRG، بدون فوروارد nonce)

## پیش‌نیازها

- وردپرس ۶.۰ به بالا (تست‌شده تا ۷.۱)
- ووکامرس ۷.۰ به بالا (تست‌شده تا ۱۱.۱)
- PHP 7.4 به بالا

## اعتبار

ساخته‌شده توسط [majidygh](https://github.com/majidygh) · فونت [وزیرمتن](https://github.com/rastikerdar/vazirmatn) (SIL OFL 1.1)

## مجوز

GPL-2.0-or-later

</div>

---

# WooPanel — Modern user dashboard for WooCommerce (English)

**WooPanel** replaces the WooCommerce "My Account" area with a clean, fast, fully RTL-aware customer panel — orders, downloads, addresses and account settings.

**Highlights:** zero JS dependencies · server-rendered · light/dark/system themes · custom accent color · optional My Account takeover · HPOS compatible · bundled Persian (fa_IR) translation with Vazirmatn font and native Persian digits.

**Install:** download `woopanel.zip` from [Releases](https://github.com/Majidygh/woopanel/releases) → Plugins → Add New → Upload → activate → place `[woopanel]` on any page.

**Security:** nonce-verified server-side POST forms, strict order-ownership gate, no guest-reachable AJAX endpoints, no PII in URLs.

**Author:** [majidygh](https://github.com/majidygh) · License: GPL-2.0-or-later
