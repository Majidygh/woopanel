#!/usr/bin/env python3
"""Build the RTL variant of the plugin stylesheet.

woopanel.css is written with logical properties (margin-inline-*, border-inline-*,
inset-inline-*, text-align: start/end), so almost nothing needs flipping. What the
LTR->RTL pass must handle:

  * the few physical declarations we still use (left:/right: offsets)
  * `direction: ltr` markers embedded for LTR data (file names) — these must NOT
    be flipped into `direction: rtl` (the old blind replace corrupted them).

If rtlcss is installed we use it; otherwise we emit a byte-identical copy plus the
RTL extras (the stylesheet is already direction-agnostic).
"""
import pathlib
import shutil
import subprocess

ROOT = pathlib.Path('/root/woopanel')
SRC = ROOT / 'assets' / 'woopanel.css'
DST = ROOT / 'assets' / 'woopanel-rtl.css'

RTL_EXTRAS = """
/* RTL extras */
.wpl-panel [dir="rtl"] .wpl-icon--chevron,
[dir="rtl"] .wpl-icon--chevron { transform: scaleX(-1); }
"""

css = SRC.read_text(encoding='utf-8')

if shutil.which('rtlcss'):
    subprocess.run(['rtlcss', str(SRC), str(DST)], check=True)
    out = DST.read_text(encoding='utf-8')
    # rtlcss flips `direction: ltr` -> rtl; restore our LTR data markers.
    out = out.replace('.wpl-dlcard__file {\n\tdirection: rtl;', '.wpl-dlcard__file {\n\tdirection: ltr;')
    DST.write_text(out + RTL_EXTRAS, encoding='utf-8')
else:
    # Logical-property stylesheet: no flip needed, ship as-is + extras.
    DST.write_text(css + RTL_EXTRAS, encoding='utf-8')

print('wrote', DST.name, len(DST.read_bytes()), 'bytes')
