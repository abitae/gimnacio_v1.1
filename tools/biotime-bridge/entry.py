# -*- coding: utf-8 -*-
"""Punto de entrada para PyInstaller (BioTimeBridge.exe)."""
from __future__ import print_function

import sys
import traceback

if getattr(sys, "frozen", False) and sys.platform == "win32":
    for stream in (sys.stdout, sys.stderr):
        if stream is not None and hasattr(stream, "reconfigure"):
            try:
                stream.reconfigure(encoding="utf-8", errors="replace")
            except Exception:
                pass


def _show_fatal_error(message):
    """Muestra error en ventana cuando console=False (doble clic sin consola)."""
    if sys.platform != "win32":
        print(message, file=sys.stderr)
        return
    try:
        import ctypes

        ctypes.windll.user32.MessageBoxW(None, message, "BioTimeBridge", 0x00000010)
    except Exception:
        pass


from bridge.__main__ import main

if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except SystemExit:
        raise
    except Exception:
        _show_fatal_error(
            "No se pudo iniciar BioTimeBridge:\n\n{0}".format(traceback.format_exc())
        )
        raise SystemExit(1)
