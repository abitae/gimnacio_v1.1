# -*- mode: python ; coding: utf-8 -*-
"""PyInstaller spec: genera dist/BioTimeBridge.exe"""
import sys
from pathlib import Path

block_cipher = None
root = Path(SPECPATH)

a = Analysis(
    [str(root / "entry.py")],
    pathex=[str(root)],
    binaries=[],
    datas=[(str(root / "config.yaml.example"), ".")],
    hiddenimports=[
        "bridge",
        "bridge.__main__",
        "bridge.app_paths",
        "bridge.biotime_client",
        "bridge.config",
        "bridge.gui",
        "bridge.laravel_client",
        "bridge.logging_setup",
        "bridge.runner",
        "certifi",
        "charset_normalizer",
        "requests",
        "urllib3",
        "yaml",
    ],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    win_no_prefer_redirects=False,
    win_private_assemblies=False,
    cipher=block_cipher,
    noarchive=False,
)

pyz = PYZ(a.pure, a.zipped_data, cipher=block_cipher)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.zipfiles,
    a.datas,
    [],
    name="BioTimeBridge",
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=False,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=True,
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
    icon=None,
)
