# -*- coding: utf-8 -*-
from __future__ import print_function

import argparse
import os
import sys

from .config import default_config_path, load_config
from .logging_setup import setup_logging
from .runner import BridgeRunner


def build_parser():
    p = argparse.ArgumentParser(description="Puente BioTime <-> Laravel (gimnasio)")
    p.add_argument(
        "--config",
        default=os.environ.get("BIOTIME_BRIDGE_CONFIG") or default_config_path(),
        help="Ruta a config.yaml",
    )
    sub = p.add_subparsers(dest="command")
    # Sin subcomando (doble clic en el .exe) abre la GUI.
    sub.required = False

    sub.add_parser("gui", help="Interfaz grafica (tkinter)")
    sub.add_parser("run", help="Loop continuo: poll commands + opcional roster/sync")
    sub.add_parser("once", help="Un ciclo: commands (+ roster/sync si toca por timers=0 forzado)")
    sub.add_parser("doctor", help="Verifica Laravel health + BioTime login")
    sub.add_parser("roster", help="Ejecuta solo reconcile de roster")
    sub.add_parser("sync-employees", help="Push employees a Laravel")
    sub.add_parser("sync-devices", help="Push catalogo: areas + departments + devices")
    sub.add_parser("sync-catalog", help="Alias de sync-devices (areas/depts/terminals)")
    sub.add_parser("sync-transactions", help="Push transactions (marcaciones) a Laravel")
    return p


def main(argv=None):
    parser = build_parser()
    args = parser.parse_args(argv)
    if not args.command:
        args.command = "gui"

    if args.command == "gui":
        from .gui import run_gui

        return run_gui(args.config)

    try:
        cfg = load_config(args.config)
    except Exception as exc:
        print("Config error: {0}".format(exc), file=sys.stderr)
        return 2

    setup_logging(cfg.log_dir, cfg.log_level)
    runner = BridgeRunner(cfg)

    try:
        if args.command == "doctor":
            return runner.doctor()

        if args.command == "run":
            runner.start()
            return 0

        # once / roster / sync need biotime login first
        runner._acquire_instance_lock()
        runner.biotime.login(
            cfg.biotime_user, cfg.biotime_password, mode=cfg.biotime_auth_mode
        )

        if args.command == "once":
            runner.poll_commands()
            if cfg.roster_reconcile_seconds > 0:
                runner.roster_reconcile()
            if cfg.sync_push_seconds > 0:
                runner.push_employees()
            if cfg.devices_push_seconds > 0:
                runner.push_catalog()
            if cfg.transactions_push_seconds > 0:
                runner.push_transactions()
            return 0

        if args.command == "roster":
            runner.roster_reconcile()
            return 0

        if args.command == "sync-employees":
            runner.push_employees()
            return 0

        if args.command == "sync-devices":
            runner.push_catalog()
            return 0

        if args.command == "sync-catalog":
            runner.push_catalog()
            return 0

        if args.command == "sync-transactions":
            runner.push_transactions()
            return 0

        parser.print_help()
        return 1
    except KeyboardInterrupt:
        print("Detenido por usuario")
        return 0
    finally:
        runner.close()


if __name__ == "__main__":
    sys.exit(main())
