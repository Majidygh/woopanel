#!/usr/bin/env python3
"""Extract all 'woopanel'-domain strings from plugin source and build pot/po/mo."""
import re, sys, struct, pathlib

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

ROOT = pathlib.Path(__file__).resolve().parent.parent
FILES = [ROOT / 'woopanel.php'] + sorted((ROOT / 'includes').glob('*.php'))

CALL = re.compile(
    r"(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x)\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'woopanel'\s*",
    re.S,
)

msgs = []
for f in FILES:
    src = f.read_text(encoding='utf-8')
    for m in CALL.finditer(src):
        s = m.group(1).replace("\\'", "'")
        if s not in msgs:
            msgs.append(s)

print(f'{len(msgs)} unique msgids')

TR = {
    # Admin header / settings
    'WooPanel': 'ووپنل',
    'WooCommerce is not active. WooPanel needs WooCommerce to display customer data.': 'ووکامرس فعال نیست. ووپنل برای نمایش داده‌های مشتری به ووکامرس نیاز دارد.',
    'Accent color': 'رنگ اصلی',
    'Used for buttons, links and active states.': 'برای دکمه‌ها، لینک‌ها و حالت‌های فعال به کار می‌رود.',
    'Accent background': 'پس‌زمینه‌ی رنگ اصلی',
    'Soft background tint for icon chips and hero.': 'رنگ ملایم پس‌زمینه برای آیکون‌ها و بنر خوش‌آمد.',
    'Panel title': 'عنوان پنل',
    'Welcome word': 'کلمه‌ی خوش‌آمد',
    'Shown before the user name on the dashboard, e.g. Hello.': 'قبل از نام کاربری در پیشخوان نمایش داده می‌شود؛ مثلاً «سلام».',
    'Orders per page': 'تعداد سفارش در هر صفحه',
    'My Account integration': 'یکپارچگی با «حساب کاربری»',
    'Replace the WooCommerce My Account area with WooPanel (on by default). Orders, downloads, addresses and account pages all render the panel; theme side-menus are hidden automatically.': 'ناحیه‌ی «حساب کاربری» ووکامرس با ووپنل جایگزین می‌شود (روشن پیش‌فرض). سفارش‌ها، دانلودها، نشانی‌ها و اطلاعات حساب همه با پنل نمایش داده می‌شوند و منوی کناری قالب خودکار پنهان می‌شود.',
    'Color mode': 'حالت رنگ',
    'Follow the site theme': 'هماهنگ با تم سایت',
    'Custom colors': 'رنگ‌های دلخواه',
    'Detected: %s': 'شناسایی‌شده: %s',
    'No brand color found in the theme — the custom color below will be used instead.': 'رنگ برندی در قالب پیدا نشد — رنگ دلخواهِ زیر به‌جای آن به کار می‌رود.',
    'In "follow" mode the panel reads your theme\'s palette server-side and, in the browser, the real rendered colors of your header and buttons — so the panel always matches the shop.': 'در حالت «هماهنگ»، پنل پالت قالب را سمت سرور و رنگ‌های واقعیِ رندرشده‌ی سربرگ و دکمه‌ها را در مرورگر می‌خواند — پنل همیشه با فروشگاه ست می‌شود.',
    'One-click palettes. The soft background tint adjusts automatically.': 'پالت‌های آماده با یک کلیک. رنگ ملایم پس‌زمینه خودکار تنظیم می‌شود.',
    'Royal violet': 'بنفش شاهانه',
    'Persian turquoise': 'فیروزه‌ی ایرانی',
    'Caspian blue': 'آبی خزر',
    'Saffron': 'زعفران',
    'Pomegranate': 'انار',
    'Forest': 'جنگل',
    'Midnight': 'نیمه‌شب',
    'Wine': 'شرابی',
    'Usage': 'نحوه‌ی استفاده',
    'Add this shortcode to any page:': 'این شورت‌کد را در هر صفحه‌ای قرار دهید:',
    'Open a specific view:': 'باز کردن یک بخش مشخص:',
    'WooPanel requires WooCommerce to be installed and active.': 'ووپنل برای کار به نصب و فعال بودن ووکامرس نیاز دارد.',
    'Customer Portal': 'پورتال مشتریان',
    'Light theme': 'تم روشن',
    'Panel appearance': 'ظاهر پنل',
    'System theme': 'تم سیستم',
    'Dark theme': 'تم تیره',
    'Light': 'روشن',
    'Auto': 'خودکار',
    'Dark': 'تیره',
    'Tracking': 'رهگیری مرسوله',
    'Tracking code': 'کد رهگیری',
    'Copy': 'کپی',
    'Copied!': 'کپی شد!',
    'Track shipment': 'رهگیری بسته',
    'Order %s': 'سفارش %s',
    'Order #%s': 'سفارش شماره %s',
    'Shipment for order %s is in transit': 'مرسوله‌ی سفارش %s در حال ارسال است',
    'No tracked shipments yet. You will see a tracking code here once your order is shipped.': 'هنوز مرسوله‌ای برای رهگیری نیست. پس از ارسال سفارش، کد رهگیری اینجا نمایش داده می‌شود.',
    'No tracked shipments yet.': 'هنوز مرسوله‌ای برای رهگیری ثبت نشده است.',
    'You will see a tracking code here once your order is shipped.': 'پس از ارسال سفارش، کد رهگیری در اینجا نمایش داده می‌شود.',
    # Navigation / shell
    'My Panel': 'پنل من',
    'Welcome, %s': 'خوش آمدید، %s',
    'Dashboard': 'پیشخوان',
    'Orders': 'سفارش‌ها',
    'Downloads': 'دانلودها',
    'Addresses': 'نشانی‌ها',
    'Account': 'حساب کاربری',
    'Log out': 'خروج',
    'Login required': 'ورود لازم است',
    'Please log in to view your panel.': 'برای مشاهده‌ی پنل خود وارد شوید.',
    'Log in': 'ورود',
    # Dashboard
    'Hello': 'سلام',
    'Here is a summary of your account.': 'خلاصه‌ای از حساب کاربری شما.',
    'Total spent': 'مجموع خرید',
    'Average order': 'میانگین سفارش',
    'Available downloads': 'دانلودهای موجود',
    'Total placed orders': 'کل سفارش‌های ثبت‌شده',
    'Successful payments': 'پرداخت‌های موفق',
    'Per purchase value': 'ارزش هر خرید',
    'Digital assets': 'فایل‌ها و دارایی‌های دیجیتال',
    'Recent orders': 'سفارش‌های اخیر',
    'View all': 'مشاهده‌ی همه',
    'All': 'همه',
    # Orders
    'No orders found.': 'سفارشی یافت نشد.',
    'You have not placed any orders yet. Visit our store to find what you need.': 'هنوز سفارشی ثبت نکرده‌اید. از فروشگاه دیدن کنید تا موارد دلخواه خود را بیابید.',
    'Go to shop': 'رفتن به فروشگاه',
    'Order': 'سفارش',
    'Date': 'تاریخ',
    'Status': 'وضعیت',
    'Total': 'مجموع',
    'Items': 'اقلام',
    'Action': 'عملیات',
    '%d item(s)': '%d قلم',
    'View': 'مشاهده',
    'Print invoice': 'چاپ فاکتور',
    'Order not found.': 'سفارشی یافت نشد.',
    'Back to orders': 'بازگشت به سفارش‌ها',
    'Product': 'محصول',
    'Quantity': 'تعداد',
    'Subtotal': 'جمع جزء',
    'Shipping': 'حمل و نقل',
    'Tax': 'مالیات',
    'Discount': 'تخفیف',
    'Total amount': 'مبلغ کل',
    'Billing address': 'نشانی صورت‌حساب',
    'Shipping address': 'نشانی حمل و نقل',
    # Downloads
    'No downloads available yet.': 'هنوز فایلی برای دانلود ندارید.',
    'When you purchase downloadable products, they will be listed here.': 'هنگامی که محصولات دانلودی خریداری کنید، در اینجا نمایش داده می‌شوند.',
    'Remaining: %s': 'باقی‌مانده: %s',
    'Expires: %s': 'انقضا: %s',
    'Download': 'دانلود',
    # Addresses
    'Edit': 'ویرایش',
    'Close': 'بستن',
    'Select an option': 'انتخاب کنید',
    'Your session expired or the form was invalid. Please try again.': 'نشست شما منقضی شده یا فرم نامعتبر است. لطفاً دوباره تلاش کنید.',
    'No address saved yet.': 'هنوز نشانی‌ای ذخیره نشده.',
    'Save address': 'ذخیره‌ی نشانی',
    'Your address has been saved.': 'نشانی شما ذخیره شد.',
    'The address could not be saved. Please check the form and try again.': 'ذخیره‌ی نشانی ممکن نشد. لطفاً فرم را بررسی و دوباره تلاش کنید.',
    'First name': 'نام',
    'Last name': 'نام خانوادگی',
    # Account
    'Account details': 'جزئیات حساب کاربری',
    'Email': 'ایمیل',
    'Save details': 'ذخیره‌ی اطلاعات',
    'Change password': 'تغییر رمز عبور',
    'Current password': 'رمز عبور فعلی',
    'New password': 'رمز عبور جدید',
    'Repeat new password': 'تکرار رمز عبور جدید',
    'Your account details have been saved.': 'اطلاعات حساب شما ذخیره شد.',
    'Your password has been changed.': 'رمز عبور شما تغییر کرد.',
    'Please enter a valid email address that is not already in use.': 'لطفاً یک ایمیل معتبر وارد کنید که قبلاً استفاده نشده باشد.',
    'New password and confirmation do not match.': 'رمز جدید و تکرار آن یکسان نیستند.',
    'New password must be at least 8 characters long.': 'رمز عبور جدید باید حداقل ۸ نویسه باشد.',
    'Your current password is not correct.': 'رمز عبور فعلی نادرست است.',
}

missing = [s for s in msgs if s not in TR]
extra = [k for k in TR if k not in msgs]
if missing:
    print('MISSING TRANSLATIONS:', file=sys.stderr)
    for s in missing:
        print(repr(s), file=sys.stderr)
    sys.exit(1)
if extra:
    print('note: unused keys:', extra)

def po_escape(s):
    return s.replace('\\', '\\\\').replace('"', '\\"')

# .po
po = ['# WooPanel Persian translation (fa_IR).', 'msgid ""', 'msgstr ""',
      '"Project-Id-Version: WooPanel 1.0.1\\n"', '"MIME-Version: 1.0\\n"',
      '"Content-Type: text/plain; charset=UTF-8\\n"', '"Content-Transfer-Encoding: 8bit\\n"',
      '"Language: fa_IR\\n"', '"Plural-Forms: nplurals=2; plural=(n > 1);\\n"', '']
for s in msgs:
    po.append(f'msgid "{po_escape(s)}"')
    po.append(f'msgstr "{po_escape(TR[s])}"')
    po.append('')
(ROOT / 'languages').mkdir(exist_ok=True)
(ROOT / 'languages' / 'woopanel-fa_IR.po').write_text('\n'.join(po), encoding='utf-8')

# .mo (pure python msgfmt) — include the header entry (charset metadata)
HEADER = ('Project-Id-Version: WooPanel 1.0.1\nMIME-Version: 1.0\n'
          'Content-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n'
          'Language: fa_IR\nPlural-Forms: nplurals=2; plural=(n > 1);\n')
catalog = {'': HEADER}
catalog.update({s: TR[s] for s in msgs})
keys = sorted(catalog)
ids = strs = b''
offsets = []
for k in keys:
    kb = k.encode('utf-8'); vb = catalog[k].encode('utf-8')
    offsets.append((len(ids), len(kb), len(strs), len(vb)))
    ids += kb + b'\x00'; strs += vb + b'\x00'
n = len(keys)
keystart = 7 * 4 + 16 * n
valuestart = keystart + len(ids)
ko, vo = [], []
for o1, l1, o2, l2 in offsets:
    ko += [l1, o1 + keystart]
    vo += [l2, o2 + valuestart]
mo = struct.pack('Iiiiiii', 0x950412de, 0, n, 7 * 4, 7 * 4 + n * 8, 0, 0)
mo += struct.pack('i' * len(ko + vo), *(ko + vo)) + ids + strs
(ROOT / 'languages' / 'woopanel-fa_IR.mo').write_bytes(mo)

# verify the mo parses back
import gettext as G
t = G.GNUTranslations(fp=open(ROOT / 'languages' / 'woopanel-fa_IR.mo', 'rb'))
for probe in ('Dashboard', 'My Panel', 'Log out', 'Pending payment', 'Total spent'):
    print(probe, '=>', t.gettext(probe))
print('MO OK, bytes:', len(mo))
