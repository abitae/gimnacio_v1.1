# -*- coding: utf-8 -*-
from __future__ import print_function

import logging
import os
from logging.handlers import RotatingFileHandler


def setup_logging(log_dir, level="INFO"):
    if not os.path.isdir(log_dir):
        os.makedirs(log_dir)

    root = logging.getLogger()
    root.setLevel(getattr(logging, level.upper(), logging.INFO))

    fmt = logging.Formatter(
        "%(asctime)s [%(levelname)s] %(name)s: %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S",
    )

    # Evitar handlers duplicados si se reinicia
    if root.handlers:
        for h in list(root.handlers):
            root.removeHandler(h)

    sh = logging.StreamHandler()
    sh.setFormatter(fmt)
    root.addHandler(sh)

    fh = RotatingFileHandler(
        os.path.join(log_dir, "biotime-bridge.log"),
        maxBytes=5 * 1024 * 1024,
        backupCount=5,
        encoding="utf-8",
    )
    fh.setFormatter(fmt)
    root.addHandler(fh)

    return root
