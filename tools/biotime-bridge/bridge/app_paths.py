# -*- coding: utf-8 -*-
"""Rutas de datos cuando el puente corre como script o como .exe (PyInstaller)."""
from __future__ import print_function

import os
import sys


def application_dir():
    """Directorio donde viven config.yaml, logs/ y el ejecutable."""
    if getattr(sys, "frozen", False):
        return os.path.dirname(os.path.abspath(sys.executable))
    return os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def resolve_data_path(path):
    """Resuelve rutas relativas (p. ej. logs/) respecto al directorio de la app."""
    path = (path or "").strip() or "logs"
    if os.path.isabs(path):
        return path
    return os.path.join(application_dir(), path)


def default_config_path():
    env = os.environ.get("BIOTIME_BRIDGE_CONFIG")
    if env:
        return env
    return os.path.join(application_dir(), "config.yaml")


def bridge_executable_path():
    """Ruta al .exe empaquetado si existe en la instalacion tipica."""
    for candidate in (
        os.path.join(application_dir(), "BioTimeBridge.exe"),
        os.path.join(application_dir(), "dist", "BioTimeBridge.exe"),
    ):
        if os.path.isfile(candidate):
            return candidate
    return None
