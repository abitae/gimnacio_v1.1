# -*- coding: utf-8 -*-
"""Atajo de inicio de Windows (carpeta Startup del usuario, sin admin)."""
from __future__ import print_function

import os
import sys

from .app_paths import application_dir, bridge_executable_path, default_config_path

STARTUP_FILENAME = "BioTimeBridge.cmd"


def is_windows():
    return sys.platform == "win32"


def startup_dir():
    appdata = os.environ.get("APPDATA") or ""
    return os.path.join(
        appdata, "Microsoft", "Windows", "Start Menu", "Programs", "Startup"
    )


def startup_script_path():
    return os.path.join(startup_dir(), STARTUP_FILENAME)


def is_enabled():
    return is_windows() and os.path.isfile(startup_script_path())


def startup_command():
    """Comando que lanza el puente con --autostart (sin el wrapper start)."""
    root = application_dir()
    config = default_config_path()
    if getattr(sys, "frozen", False):
        return '"{0}" --config "{1}" --autostart'.format(sys.executable, config)
    exe = bridge_executable_path()
    if exe:
        return '"{0}" --config "{1}" --autostart'.format(exe, config)
    return '"{0}" -m bridge --config "{1}" --autostart'.format(sys.executable, config)


def _cmd_contents():
    root = application_dir()
    config = default_config_path()
    if getattr(sys, "frozen", False):
        target = sys.executable
        launch = 'start "" /D "{0}" "{1}" --config "{2}" --autostart'.format(
            root, target, config
        )
    else:
        exe = bridge_executable_path()
        if exe:
            launch = 'start "" /D "{0}" "{1}" --config "{2}" --autostart'.format(
                root, exe, config
            )
        else:
            launch = (
                'start "" /D "{0}" "{1}" -m bridge --config "{2}" --autostart'.format(
                    root, sys.executable, config
                )
            )
    return (
        "@echo off\r\n"
        "REM BioTime Bridge - arranque con Windows (generado por la GUI)\r\n"
        "{0}\r\n".format(launch)
    )


def enable():
    if not is_windows():
        raise OSError("Iniciar con Windows solo esta disponible en Windows.")
    folder = startup_dir()
    if not os.path.isdir(folder):
        os.makedirs(folder)
    path = startup_script_path()
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(_cmd_contents())
    return path


def disable():
    path = startup_script_path()
    if os.path.isfile(path):
        os.remove(path)
    return path
