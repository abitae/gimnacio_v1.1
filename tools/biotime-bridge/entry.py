# -*- coding: utf-8 -*-
"""Punto de entrada para PyInstaller (BioTimeBridge.exe)."""
from __future__ import print_function

import sys

if getattr(sys, "frozen", False) and sys.platform == "win32":
    for stream in (sys.stdout, sys.stderr):
        if stream is not None and hasattr(stream, "reconfigure"):
            try:
                stream.reconfigure(encoding="utf-8", errors="replace")
            except Exception:
                pass

from bridge.__main__ import main

if __name__ == "__main__":
    sys.exit(main())
