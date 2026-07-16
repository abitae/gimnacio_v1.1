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
            user_agent=cfg.laravel_user_agent,
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
        ensure_create = bool(cmd.get("ensure_create"))
        first_name = cmd.get("first_name") or emp_code
        last_name = cmd.get("last_name") or ""

        if not cmd_id or not emp_code or action not in ("activate", "deactivate", "delete"):
            logger.error("Command invalido: %s", cmd)
            if cmd_id:
                self._safe_ack(cmd_id, "failed", "invalid command payload")
            return

        try:
            emp = self.biotime.find_employee_by_code(emp_code)

            if action == "delete":
                if not emp or emp.get("id") is None:
                    logger.info("Delete: empleado ya ausente emp_code=%s", emp_code)
                    self._safe_ack(cmd_id, "acked")
                    return
                if self.cfg.dry_run:
                    logger.info("[dry_run] cmd=%s action=delete emp_code=%s biotime_id=%s", cmd_id, emp_code, emp.get("id"))
                    self._safe_ack(cmd_id, "acked")
                    return
                self.biotime.delete_employee(int(emp["id"]))
                logger.info("OK cmd=%s action=delete emp_code=%s biotime_id=%s", cmd_id, emp_code, emp.get("id"))
                self._safe_ack(cmd_id, "acked")
                return

            if action == "activate":
                area = int(desired_area) if desired_area else self.cfg.area_id
                areas = [area]
            else:
                areas = [self.cfg.denied_area_id]

            if not emp or emp.get("id") is None:
                if action == "deactivate":
                    logger.info(
                        "Deactivate: empleado ausente emp_code=%s (nada que hacer)",
                        emp_code,
                    )
                    self._safe_ack(cmd_id, "acked")
                    return
                if action != "activate" or not ensure_create:
                    raise BioTimeError("Empleado no encontrado emp_code={0}".format(emp_code))
                if self.cfg.dry_run:
                    logger.info(
                        "[dry_run] cmd=%s create emp_code=%s areas=%s",
                        cmd_id,
                        emp_code,
                        areas,
                    )
                    self._safe_ack(cmd_id, "acked")
                    return
                emp = self.biotime.create_employee(
                    emp_code=emp_code,
                    first_name=first_name,
                    last_name=last_name,
                    company_id=self.cfg.company_id,
                    department_id=self.cfg.department_id,
                    area_ids=areas,
                )
                created_id = emp.get("id") if isinstance(emp, dict) else None
                logger.info(
                    "OK cmd=%s created emp_code=%s biotime_id=%s",
                    cmd_id,
                    emp_code,
                    created_id,
                )
                if created_id is not None:
                    self._maybe_resync([int(created_id)])
                self._safe_ack(cmd_id, "acked")
                return

            emp_id = int(emp["id"])
            # Releer detalle: el listado a veces trae area desactualizada.
            try:
                fresh = self.biotime.get_employee(emp_id)
                if isinstance(fresh, dict) and fresh.get("id") is not None:
                    emp = fresh
            except BioTimeError as exc:
                logger.warning("get_employee %s fallo; uso listado: %s", emp_id, exc)

            current_areas = sorted(self.biotime.employee_area_ids(emp))
            desired_sorted = sorted(int(a) for a in areas)
            if current_areas == desired_sorted:
                logger.info(
                    "OK cmd=%s action=%s emp_code=%s areas already=%s (resync)",
                    cmd_id,
                    action,
                    emp_code,
                    current_areas,
                )
                if not self.cfg.dry_run:
                    self._maybe_resync([emp_id])
                self._safe_ack(cmd_id, "acked")
                return

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
            self._maybe_resync([emp_id])
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

    def _maybe_resync(self, employee_ids):
        if self.cfg.dry_run or not self.cfg.resync_after_area:
            return
        ids = [int(x) for x in employee_ids if x is not None]
        if not ids:
            return
        try:
            self.biotime.resync_employees_to_device(ids)
            logger.info("resync_to_device OK employees=%s", ids)
        except BioTimeError as exc:
            logger.warning("resync_to_device fallo employees=%s: %s", ids, exc)

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
        active=true → area autorizada (create-if-missing); active=false → denied_area_id.
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
                areas = [self.cfg.area_id] if active else [self.cfg.denied_area_id]
                if not emp or emp.get("id") is None:
                    if not active:
                        continue
                    if self.cfg.dry_run:
                        logger.info("[dry_run] roster create emp_code=%s", emp_code)
                        continue
                    created = self.biotime.create_employee(
                        emp_code=emp_code,
                        first_name=emp_code,
                        last_name="",
                        company_id=self.cfg.company_id,
                        department_id=self.cfg.department_id,
                        area_ids=areas,
                    )
                    created_id = created.get("id") if isinstance(created, dict) else None
                    if created_id is not None:
                        self._maybe_resync([int(created_id)])
                    continue
                if self.cfg.dry_run:
                    logger.info(
                        "[dry_run] roster emp_code=%s active=%s areas=%s",
                        emp_code,
                        active,
                        areas,
                    )
                    continue
                current = sorted(self.biotime.employee_area_ids(emp))
                desired = sorted(int(a) for a in areas)
                if current == desired:
                    continue
                emp_id = int(emp["id"])
                self.biotime.set_employee_areas(emp_id, areas, employee=emp)
                self._maybe_resync([emp_id])
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
        """POST /api/biotime/sync entity=employees + reporta employees_count en health."""
        try:
            rows, _meta = self.biotime.list_employees(page=1, page_size=200)
            try:
                total = self.biotime.count_employees(page_size=200)
            except BioTimeError:
                total = len(rows)
            self.laravel.health(employees_count=total)
        except BioTimeError as exc:
            logger.error("No se pudieron listar employees BioTime: %s", exc)
            return
        except LaravelError as exc:
            logger.error("Health employees_count fallo: %s", exc)

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
        import sys

        from .laravel_client import NETWORK_HINT

        ok = True
        print("=== BioTime bridge doctor ===")
        print("Laravel: {0}".format(self.cfg.laravel_base_url))
        print("Health URL: {0}".format(self.laravel.health_url()))
        print("verify_ssl: {0}".format(self.cfg.laravel_verify_ssl))
        print("User-Agent: {0}".format(self.cfg.laravel_user_agent))
        print("Python: {0}".format(sys.version.split()[0]))
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
            print(NETWORK_HINT)
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
