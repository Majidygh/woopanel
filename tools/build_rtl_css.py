#!/usr/bin/env python3
"""Build RTL variants of all plugin stylesheets using rtlcss if available, else a small flipper."""
import re, pathlib, shutil, subprocess, sys

ROOT = pathlib.Path('/root/woopanel')

def flip_css(css: str) -> str:
    """Conservative LTR->RTL transform for our own prefixed stylesheet."""
    out = css

    # logical properties need no flipping; handle the physical ones we actually use
    out = re.sub(r'text-align:\s*left', 'text-align: right', out)
    out = re.sub(r'text-align:\s*right', 'text-align: left', out)
    out = out.replace('margin-left', 'margin-right').replace('margin-right', 'margin-left')
    out = out.replace('padding-left', 'padding-right').replace('padding-right', 'padding-left')
    out = out.replace('border-left', 'border-right').replace('border-right', 'border-left')
    out = out.replace('border-top-left-radius', 'border-top-right-radius').replace('border-top-right-radius', 'border-top-left-radius')
    out = out.replace('border-bottom-left-radius', 'border-bottom-right-radius').replace('border-bottom-right-radius', 'border-bottom-left-radius')
    out = out.replace('left:', '@@L:@@').replace('right:', 'left:').replace('@@L:@@', 'right:')
    # direction
    out = out.replace('direction: ltr', 'direction: rtl')
    # chevron icon flip for RTL (its path is LTR-ish)
    out = out.replace('.wpl-icon--chevron', '.wpl-icon--chevron-rtl')
    return out

src = (ROOT / 'assets' / 'woopanel.css').read_text(encoding='utf-8')
rtl = flip_css(src)
# append RTL-only tweaks: flip chevron, fix scroll margins
rtl += """
/* RTL extras */
.wpl-panel [dir="rtl"] .wpl-icon--chevron,
[dir="rtl"] .wpl-icon--chevron { transform: scaleX(-1); }
"""
(ROOT / 'assets' / 'woopanel-rtl.css').write_text(rtl, encoding='utf-8')
print('wrote woopanel-rtl.css', len(rtl), 'bytes')
