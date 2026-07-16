# -*- coding: utf-8 -*-
from __future__ import print_function

import logging
import time

from .biotime_client import BioTimeClient, BioTimeError
from .laravel_client import LaravelClient, LaravelError

logger = logging.getLogger(__name__)


class BridgeRunner(object):
    def __init__(self, cfg):
        self.cfg = cfg
        self.laravel = LaravelClient(
            cfg.laravel_base_url,
            cfg.laravel_token,
            timeout=cfg.http_timeout_seconds,
            max_retries=cfg.max_retries,
            backoff=cfg.retry_backoff_seconds,
            verify_ssl=cfg.laravel_verify_ssl,
        )
        self.biotime = BioTimeClient(cfg.biotime_base_url, timeout=cfg.http_timeout_seconds)
        self._last_roster = 0
        self._last_sync = 0

    def close(self):
        try:
            self.laravel.close()
        except Exception:
            pass
        try:
            self.biotime.close()
        except Exception:
            pass

    def start(self, should_stop=None):
        """
        Loop continuo. should_stop: callable opcional que retorna True para salir
        (p. ej. desde la GUI).
        """
        self.biotime.login(
            self.cfg.biotime_user,
            self.cfg.biotime_password,
            mode=self.cfg.biotime_auth_mode,
        )
        health = self.laravel.health()
        logger.info(
            "Laravel health OK sucursal_id=%s dry_run=%s",
            health.get("sucursal_id") if isinstance(health, dict) else "?",
            self.cfg.dry_run,
        )
        self._last_roster = time.time()
        self._last_sync = time.time()

        logger.info(
            "Loop iniciado poll=%ss roster=%ss sync_push=%ss sede=%s",
            self.cfg.poll_seconds,
            self.cfg.roster_reconcile_seconds,
            self.cfg.sync_push_seconds,
            self.cfg.sucursal_codigo,
        )

        while True:
            if should_stop is not None and should_stop():
                logger.info("Loop detenido por solicitud")
                break
            try:
                self.poll_commands()
                self.maybe_roster_reconcile()
                self.maybe_sync_push()
            except Exception:
                logger.exception("Error en ciclo del puente")
            # Sleep en trozos para reaccionar al stop sin esperar poll_seconds entero
            remaining = float(self.cfg.poll_seconds)
            while remaining > 0:
                if should_stop is not None and should_stop():
                    logger.info("Loop detenido por solicitud")
                    return
                step = 0.5 if remaining > 0.5 else remaining
                time.sleep(step)
                remaining -= step

    def poll_commands(self):
        try:
            commands = self.laravel.get_commands(limit=100)
        except LaravelError as exc:
            logger.error("No se pudieron obtener commands: %s", exc)
            return

        if not commands:
            logger.debug("Sin commands pendientes")
            return

        logger.info("Commands recibidos: %s", len(commands))
        for cmd in commands:
            self.apply_command(cmd)

    def apply_command(self, cmd):
        cmd_id = cmd.get("id")
        action = (cmd.get("action") or "").lower()
        emp_code = str(cmd.get("emp_code") or "")
        desired_area = cmd.get("desired_area_biotime_id")

        if not cmd_id or not emp_code or action not in ("activate", "deactivate"):
            logger.error("Command invalido: %s", cmd)
            if cmd_id:
                self._safe_ack(cmd_id, "failed", "invalid command payload")
            return

        try:
            emp = self.biotime.find_employee_by_code(emp_code)
            if not emp or emp.get("id") is None:
                raise BioTimeError("Empleado no encontrado emp_code={0}".format(emp_code))

            emp_id = int(emp["id"])
            if action == "activate":
                area = int(desired_area) if desired_area else self.cfg.area_id
                areas = [area]
            else:
                # BioTime rechaza []; se usa area denegada ("No autorizado")
                areas = [self.cfg.denied_area_id]

            if self.cfg.dry_run:
                logger.info(
                    "[dry_run] cmd=%s action=%s emp_code=%s biotime_id=%s areas=%s",
                    cmd_id,
                    action,
                    emp_code,
                    emp_id,
                    areas,
                )
                self._safe_ack(cmd_id, "acked")
                return

            self.biotime.set_employee_areas(emp_id, areas, employee=emp)
            logger.info(
                "OK cmd=%s action=%s emp_code=%s biotime_id=%s areas=%s",
                cmd_id,
                action,
                emp_code,
                emp_id,
                areas,
            )
            self._safe_ack(cmd_id, "acked")
        except (BioTimeError, LaravelError, Exception) as exc:
            logger.error("FAIL cmd=%s emp_code=%s: %s", cmd_id, emp_code, exc)
            self._safe_ack(cmd_id, "failed", str(exc))

    def _safe_ack(self, cmd_id, status, error=None):
        if self.cfg.dry_run and status == "failed":
            logger.info("[dry_run] ack skipped failed cmd=%s error=%s", cmd_id, error)
            return
        try:
            self.laravel.ack(cmd_id, status, error=error)
        except LaravelError as exc:
            logger.error("No se pudo ACK cmd=%s: %s", cmd_id, exc)

    def maybe_roster_reconcile(self):
        if self.cfg.roster_reconcile_seconds <= 0:
            return
        if time.time() - self._last_roster < self.cfg.roster_reconcile_seconds:
            return
        self._last_roster = time.time()
        self.roster_reconcile()

    def roster_reconcile(self):
        """
        Aplica el roster de Laravel directamente en BioTime.
        active=true → area autorizada; active=false → denied_area_id.
        """
        try:
            rows = self.laravel.roster()
        except LaravelError as exc:
            logger.error("Roster falló: %s", exc)
            return

        logger.info("Roster reconcile: %s clientes", len(rows))
        for row in rows:
            emp_code = str(row.get("emp_code") or row.get("cliente_id") or "")
            active = bool(row.get("active"))
            if not emp_code:
                continue
            try:
                emp = self.biotime.find_employee_by_code(emp_code)
                if not emp or emp.get("id") is None:
                    logger.warning("Roster: sin empleado BioTime emp_code=%s", emp_code)
                    continue
                areas = [self.cfg.area_id] if active else [self.cfg.denied_area_id]
                if self.cfg.dry_run:
                    logger.info(
                        "[dry_run] roster emp_code=%s active=%s areas=%s",
                        emp_code,
                        active,
                        areas,
                    )
                    continue
                self.biotime.set_employee_areas(int(emp["id"]), areas, employee=emp)
            except BioTimeError as exc:
                logger.error("Roster emp_code=%s: %s", emp_code, exc)

    def maybe_sync_push(self):
        if self.cfg.sync_push_seconds <= 0:
            return
        if time.time() - self._last_sync < self.cfg.sync_push_seconds:
            return
        self._last_sync = time.time()
        self.push_employees()

    def push_employees(self):
        """POST /api/biotime/sync entity=employees (pagina 1 basica)."""
        try:
            rows = self.biotime.list_employees(page=1, page_size=200)
        except BioTimeError as exc:
            logger.error("No se pudieron listar employees BioTime: %s", exc)
            return

        records = []
        for row in rows:
            records.append(self._normalize_employee_payload(row))

        if not records:
            logger.info("Sync push: sin employees")
            return

        if self.cfg.dry_run:
            logger.info("[dry_run] sync push employees count=%s", len(records))
            return

        try:
            result = self.laravel.sync("employees", records)
            logger.info("Sync push employees OK: %s", result)
        except LaravelError as exc:
            logger.error("Sync push employees fallo: %s", exc)

    @staticmethod
    def _normalize_employee_payload(row):
        """Ajusta forma tipica BioTime → lo que espera BioTimeSyncService."""
        payload = dict(row)
        # Laravel espera id biotime y emp_code; area puede venir como lista de objetos.
        return payload

    def doctor(self):
        """Verifica Laravel health (token sede) + BioTime auth. Return code 0/1."""
        ok = True
        print("=== BioTime bridge doctor ===")
        print("Laravel: {0}".format(self.cfg.laravel_base_url))
        print("BioTime: {0}".format(self.cfg.biotime_base_url))
        print("Sede cfg: {0}".format(self.cfg.sucursal_codigo or "(sin codigo)"))
        print("dry_run: {0}".format(self.cfg.dry_run))

        try:
            health = self.laravel.health()
            sucursal_id = health.get("sucursal_id") if isinstance(health, dict) else None
            heartbeat = health.get("last_heartbeat_at") if isinstance(health, dict) else None
            print("Laravel health: OK")
            print("  sucursal_id: {0}".format(sucursal_id))
            print("  last_heartbeat_at (respuesta): {0}".format(heartbeat))
            logger.info("doctor Laravel OK: %s", health)
        except Exception as exc:
            print("Laravel health: FAIL — {0}".format(exc))
            logger.error("doctor Laravel FAIL: %s", exc)
            ok = False

        try:
            auth = self.biotime.login(
                self.cfg.biotime_user,
                self.cfg.biotime_password,
                mode=self.cfg.biotime_auth_mode,
            )
            print("BioTime auth: OK ({0} via {1})".format(auth.scheme, auth.endpoint))
            logger.info("doctor BioTime OK")
        except Exception as exc:
            print("BioTime auth: FAIL — {0}".format(exc))
            logger.error("doctor BioTime FAIL: %s", exc)
            ok = False

        print("Resultado: {0}".format("OK" if ok else "FAIL"))
        return 0 if ok else 1
