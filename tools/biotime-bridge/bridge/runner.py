# -*- coding: utf-8 -*-
from __future__ import print_function

import logging
import os
import time
from datetime import datetime

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
        self._last_devices = 0
        self._last_transactions = 0
        self.remote_config = {}
        self._lock_handle = None
        self._reserved_additions = 0

    def _find_employee(self, emp_code):
        """Busca empleado BioTime solo por emp_code (numero_documento Laravel)."""
        code = str(emp_code or "").strip()
        if not code:
            return None
        emp = self.biotime.find_employee_by_code(code)
        if emp and emp.get("id") is not None:
            return emp
        return None

    def close(self):
        self._release_instance_lock()
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
        self._acquire_instance_lock()
        self.biotime.login(
            self.cfg.biotime_user,
            self.cfg.biotime_password,
            mode=self.cfg.biotime_auth_mode,
        )
        health = self.laravel.health()
        self.refresh_config()
        logger.info(
            "Laravel health OK sucursal_id=%s dry_run=%s",
            health.get("sucursal_id") if isinstance(health, dict) else "?",
            self.cfg.dry_run,
        )
        self._last_roster = time.time()
        self._last_sync = time.time()
        self._last_devices = time.time()
        self._last_transactions = time.time()

        logger.info(
            "Loop iniciado poll=%ss roster=%ss sync_push=%ss devices=%ss tx=%ss sede=%s",
            self.cfg.poll_seconds,
            self.cfg.roster_reconcile_seconds,
            self.cfg.sync_push_seconds,
            self.cfg.devices_push_seconds,
            self.cfg.transactions_push_seconds,
            self.cfg.sucursal_codigo,
        )

        # Catalogo inmediato: areas/departamentos/dispositivos para el mapeo Laravel.
        if self.cfg.devices_push_seconds > 0:
            self.push_catalog()
        if self.cfg.sync_push_seconds > 0:
            self.push_employees()

        while True:
            if should_stop is not None and should_stop():
                logger.info("Loop detenido por solicitud")
                break
            try:
                self.poll_commands()
                self.maybe_roster_reconcile()
                self.maybe_sync_push()
                self.maybe_devices_push()
                self.maybe_transactions_push()
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
        self.refresh_config()
        try:
            commands = self.laravel.get_commands(limit=100)
        except LaravelError as exc:
            logger.error("No se pudieron obtener commands: %s", exc)
            return

        if not commands:
            logger.debug("Sin commands pendientes")
            return

        logger.info("Commands recibidos: %s", len(commands))
        # Nunca agregar antes de retirar usuarios desplazados y releer inventario.
        removals = [
            cmd for cmd in commands if (cmd.get("action") or "").lower() != "activate"
        ]
        additions = [
            cmd for cmd in commands if (cmd.get("action") or "").lower() == "activate"
        ]
        for cmd in removals:
            self.apply_command(cmd)
        if additions and self.remote_config.get("capacity_enforcement_enabled"):
            self.push_heartbeat()
            self.refresh_config()
        self._reserved_additions = 0
        for cmd in additions:
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
            emp = self._find_employee(emp_code)

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
                if not self._can_create_employee():
                    raise BioTimeError(
                        "Alta bloqueada: inventario sin verificar, desconectado o cupo 500 alcanzado"
                    )
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
                self._reserved_additions += 1
                created_id = emp.get("id") if isinstance(emp, dict) else None
                logger.info(
                    "OK cmd=%s created emp_code=%s biotime_id=%s",
                    cmd_id,
                    emp_code,
                    created_id,
                )
                if created_id is not None:
                    self._maybe_resync([int(created_id)])
                self._safe_ack(cmd_id, "acked", biotime_id=created_id)
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
                self._safe_ack(cmd_id, "acked", biotime_id=emp_id)
                return

            if action == "activate" and not self._can_create_employee():
                raise BioTimeError(
                    "Alta bloqueada: inventario sin verificar, desconectado o cupo 500 alcanzado"
                )

            if self.cfg.dry_run:
                logger.info(
                    "[dry_run] cmd=%s action=%s emp_code=%s biotime_id=%s areas=%s",
                    cmd_id,
                    action,
                    emp_code,
                    emp_id,
                    areas,
                )
                self._safe_ack(cmd_id, "acked", biotime_id=emp_id)
                return

            self.biotime.set_employee_areas(emp_id, areas, employee=emp)
            if action == "activate":
                self._reserved_additions += 1
            self._maybe_resync([emp_id])
            logger.info(
                "OK cmd=%s action=%s emp_code=%s biotime_id=%s areas=%s",
                cmd_id,
                action,
                emp_code,
                emp_id,
                areas,
            )
            self._safe_ack(cmd_id, "acked", biotime_id=emp_id)
        except (BioTimeError, LaravelError, Exception) as exc:
            logger.error("FAIL cmd=%s emp_code=%s: %s", cmd_id, emp_code, exc)
            self._safe_ack(cmd_id, "failed", str(exc))

    def _safe_ack(self, cmd_id, status, error=None, biotime_id=None):
        if self.cfg.dry_run and status == "failed":
            logger.info("[dry_run] ack skipped failed cmd=%s error=%s", cmd_id, error)
            return
        try:
            self.laravel.ack(cmd_id, status, error=error, biotime_id=biotime_id)
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
            snapshot = self.laravel.roster_snapshot()
            rows = snapshot.get("data") or []
        except LaravelError as exc:
            logger.error("Roster falló: %s", exc)
            return

        capacity = snapshot.get("capacity") or {}
        enforcement = bool(capacity.get("enforcement_enabled"))
        inventory_ready = bool(capacity.get("inventory_ready"))

        def should_be_active(row):
            if not enforcement or not inventory_ready:
                return str(row.get("status") or "") in ("selected", "waiting")
            return bool(row.get("desired_access", row.get("active")))

        logger.info("Roster reconcile: %s clientes capacity=%s", len(rows), capacity)
        removals = [row for row in rows if not should_be_active(row)]
        additions = [row for row in rows if should_be_active(row)]
        self._reserved_additions = 0
        for row in removals + additions:
            if additions and row is additions[0] and enforcement:
                self.push_heartbeat()
                self.refresh_config()
                capacity = self.remote_config.get("capacity") or capacity
            emp_code = str(row.get("emp_code") or row.get("cliente_id") or "")
            active = should_be_active(row)
            if not emp_code:
                continue
            try:
                emp = self._find_employee(emp_code)
                areas = [self.cfg.area_id] if active else [self.cfg.denied_area_id]
                if not emp or emp.get("id") is None:
                    if not active:
                        continue
                    if enforcement and not bool(row.get("desired_access")):
                        logger.warning(
                            "Roster alta diferida emp_code=%s: requiere nuevo roster con inventario listo",
                            emp_code,
                        )
                        continue
                    if self.cfg.dry_run:
                        logger.info("[dry_run] roster create emp_code=%s", emp_code)
                        continue
                    if not self._can_create_employee(capacity):
                        logger.warning(
                            "Roster alta bloqueada emp_code=%s capacity=%s",
                            emp_code,
                            capacity,
                        )
                        continue
                    created = self.biotime.create_employee(
                        emp_code=emp_code,
                        first_name=emp_code,
                        last_name="",
                        company_id=self.cfg.company_id,
                        department_id=self.cfg.department_id,
                        area_ids=areas,
                    )
                    self._reserved_additions += 1
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
                if active and not self._can_create_employee(capacity):
                    logger.warning(
                        "Roster alta bloqueada emp_code=%s capacity=%s",
                        emp_code,
                        capacity,
                    )
                    continue
                self.biotime.set_employee_areas(emp_id, areas, employee=emp)
                if active:
                    self._reserved_additions += 1
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

    def maybe_devices_push(self):
        if self.cfg.devices_push_seconds <= 0:
            return
        if time.time() - self._last_devices < self.cfg.devices_push_seconds:
            return
        self._last_devices = time.time()
        self.push_catalog()

    def maybe_transactions_push(self):
        if self.cfg.transactions_push_seconds <= 0:
            return
        if time.time() - self._last_transactions < self.cfg.transactions_push_seconds:
            return
        self._last_transactions = time.time()
        self.push_transactions()

    def push_catalog(self):
        """Empuja areas + departments + devices a Laravel (mapeo UI)."""
        self.push_areas()
        self.push_departments()
        self.push_devices()

    def push_employees(self):
        """POST /api/biotime/sync entity=employees + reporta employees_count en health."""
        try:
            rows = self.biotime.list_all_employees(page_size=200)
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
            self.push_heartbeat(employee_rows=rows)
        except LaravelError as exc:
            logger.error("Sync push employees fallo: %s", exc)

    def push_areas(self):
        """POST /api/biotime/sync entity=areas."""
        try:
            rows = self.biotime.list_all_areas(page_size=100)
        except BioTimeError as exc:
            logger.error("No se pudieron listar areas BioTime: %s", exc)
            return

        records = [self._normalize_area_payload(row) for row in rows if isinstance(row, dict)]
        records = [r for r in records if r.get("id") is not None]
        if not records:
            logger.info("Sync push: sin areas")
            return

        if self.cfg.dry_run:
            logger.info("[dry_run] sync push areas count=%s", len(records))
            return

        try:
            result = self.laravel.sync("areas", records)
            logger.info("Sync push areas OK: %s", result)
        except LaravelError as exc:
            logger.error("Sync push areas fallo: %s", exc)

    def push_departments(self):
        """POST /api/biotime/sync entity=departments."""
        try:
            rows = self.biotime.list_all_departments(page_size=100)
        except BioTimeError as exc:
            logger.error("No se pudieron listar departments BioTime: %s", exc)
            return

        records = [
            self._normalize_department_payload(row) for row in rows if isinstance(row, dict)
        ]
        records = [r for r in records if r.get("id") is not None]
        if not records:
            logger.info("Sync push: sin departments")
            return

        if self.cfg.dry_run:
            logger.info("[dry_run] sync push departments count=%s", len(records))
            return

        try:
            result = self.laravel.sync("departments", records)
            logger.info("Sync push departments OK: %s", result)
        except LaravelError as exc:
            logger.error("Sync push departments fallo: %s", exc)

    def push_devices(self):
        """POST /api/biotime/sync entity=devices (terminals BioTime)."""
        try:
            rows = self.biotime.list_all_terminals(page_size=100)
        except BioTimeError as exc:
            logger.error("No se pudieron listar terminals BioTime: %s", exc)
            return

        records = [self._normalize_device_payload(row) for row in rows]
        if not records:
            logger.info("Sync push: sin devices")
            return

        if self.cfg.dry_run:
            logger.info("[dry_run] sync push devices count=%s", len(records))
            return

        try:
            result = self.laravel.sync("devices", records)
            logger.info("Sync push devices OK: %s", result)
            self.push_heartbeat(terminal_rows=rows)
        except LaravelError as exc:
            logger.error("Sync push devices fallo: %s", exc)

    def refresh_config(self):
        try:
            config = self.laravel.config()
        except LaravelError as exc:
            if exc.status == 404:
                logger.warning(
                    "Config remota: Laravel respondio 404 en GET /api/biotime/config. "
                    "Despliega la version actual del backend y ejecuta: php artisan route:clear. "
                    "Se mantiene config local/remota previa."
                )
            else:
                logger.error("Config remota fallo: %s", exc)
            return self.remote_config
        if not isinstance(config, dict):
            return self.remote_config
        self.remote_config = config
        for remote_key, local_key in (
            ("area_biotime_id", "area_id"),
            ("denied_area_biotime_id", "denied_area_id"),
            ("company_biotime_id", "company_id"),
            ("department_biotime_id", "department_id"),
        ):
            value = config.get(remote_key)
            if value is not None and int(value) > 0:
                setattr(self.cfg, local_key, int(value))
        return config

    def push_heartbeat(self, terminal_rows=None, employee_rows=None):
        """Reporta inventario proyectado por area para validacion por reloj."""
        self.refresh_config()
        try:
            if terminal_rows is None:
                terminal_rows = self.biotime.list_all_terminals(page_size=100)
            if employee_rows is None:
                employee_rows = self.biotime.list_all_employees(page_size=200)
        except BioTimeError as exc:
            logger.error("Heartbeat inventario fallo: %s", exc)
            return

        authorized_codes = []
        for employee in employee_rows:
            if self.cfg.area_id in self.biotime.employee_area_ids(employee):
                code = str(employee.get("emp_code") or "").strip()
                if code:
                    authorized_codes.append(code)
        authorized_codes = sorted(set(authorized_codes))

        configured = {
            str(row.get("serial_number") or "")
            for row in (self.remote_config.get("devices") or [])
            if row.get("access_enabled")
        }
        devices = []
        now_value = datetime.now().isoformat()
        for terminal in terminal_rows:
            serial = str(terminal.get("sn") or terminal.get("serial_number") or "").strip()
            if not serial or (configured and serial not in configured):
                continue
            raw_count = (
                terminal.get("users_count")
                or terminal.get("user_count")
                or terminal.get("employee_count")
            )
            count = max(int(raw_count or 0), len(authorized_codes))
            devices.append(
                {
                    "biotime_id": terminal.get("id"),
                    "serial_number": serial,
                    "online": int(terminal.get("state") or 0) in (1, 2),
                    "capacity": min(500, int(terminal.get("user_capacity") or 500)),
                    "employees_count": count,
                    "employee_codes": authorized_codes,
                    "inventory_at": now_value,
                    "inventory_source": "terminal_counter"
                    if raw_count is not None
                    else "biotime_area_projection",
                }
            )
        if not devices:
            logger.warning("Heartbeat: no hay relojes configurados/descubiertos")
            return
        try:
            result = self.laravel.heartbeat(devices)
            logger.info("Heartbeat inventario OK: %s", result)
        except LaravelError as exc:
            if exc.status == 404:
                total_employees = max(
                    (int(device.get("employees_count") or 0) for device in devices),
                    default=0,
                )
                logger.warning(
                    "Heartbeat inventario: Laravel respondio 404 en POST /api/biotime/heartbeat. "
                    "Despliega la version actual del backend y ejecuta: php artisan route:clear. "
                    "Intentando fallback GET /api/biotime/health (employees_count=%s).",
                    total_employees,
                )
                try:
                    self.laravel.health(employees_count=total_employees)
                    logger.info("Heartbeat fallback health OK")
                except LaravelError as health_exc:
                    logger.error("Heartbeat fallback health fallo: %s", health_exc)
                return
            logger.error("Heartbeat inventario Laravel fallo: %s", exc)

    def _can_create_employee(self, capacity=None):
        if not self.remote_config.get("capacity_enforcement_enabled"):
            return True
        capacity = capacity or self.remote_config.get("capacity") or {}
        if not bool(capacity.get("inventory_ready")):
            return False
        if int(capacity.get("selected_count") or 0) > int(
            capacity.get("client_slots") or 0
        ):
            return False
        branch_limit = min(500, int(self.remote_config.get("hard_limit") or 500))
        devices = [
            row
            for row in (self.remote_config.get("devices") or [])
            if row.get("access_enabled")
        ]
        if not devices:
            return False
        return all(
            row.get("inventory_verified")
            and row.get("inventory_source")
            in ("terminal_counter", "terminal_inventory")
            and row.get("reported_users_count") is not None
            and int(row.get("reported_users_count")) + self._reserved_additions
            < min(
                branch_limit,
                int(row.get("capacity_limit") or 500),
            )
            for row in devices
        )

    def _acquire_instance_lock(self):
        if self._lock_handle is not None:
            return
        lock_path = os.path.abspath(os.path.join(self.cfg.log_dir, "biotime-bridge.lock"))
        parent = os.path.dirname(lock_path)
        if parent and not os.path.isdir(parent):
            os.makedirs(parent)
        handle = open(lock_path, "a+")
        try:
            if os.name == "nt":
                import msvcrt

                handle.seek(0)
                msvcrt.locking(handle.fileno(), msvcrt.LK_NBLCK, 1)
            else:
                import fcntl

                fcntl.flock(handle.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        except (IOError, OSError):
            handle.close()
            raise RuntimeError("Ya existe otra instancia del puente en ejecucion")
        self._lock_handle = handle

    def _release_instance_lock(self):
        if self._lock_handle is None:
            return
        try:
            if os.name == "nt":
                import msvcrt

                self._lock_handle.seek(0)
                msvcrt.locking(self._lock_handle.fileno(), msvcrt.LK_UNLCK, 1)
            else:
                import fcntl

                fcntl.flock(self._lock_handle.fileno(), fcntl.LOCK_UN)
        except (IOError, OSError):
            pass
        self._lock_handle.close()
        self._lock_handle = None

    def push_transactions(self):
        """POST /api/biotime/sync entity=transactions (ventana lookback)."""
        from datetime import datetime, timedelta

        end = datetime.now()
        start = end - timedelta(minutes=int(self.cfg.transactions_lookback_minutes))
        start_s = start.strftime("%Y-%m-%d %H:%M:%S")
        end_s = end.strftime("%Y-%m-%d %H:%M:%S")

        try:
            rows = self.biotime.list_transactions_window(start_time=start_s, end_time=end_s)
        except BioTimeError as exc:
            logger.error("No se pudieron listar transactions BioTime: %s", exc)
            return

        records = [self._normalize_transaction_payload(row) for row in rows]
        if not records:
            logger.info("Sync push: sin transactions (%s .. %s)", start_s, end_s)
            return

        if self.cfg.dry_run:
            logger.info(
                "[dry_run] sync push transactions count=%s window=%s..%s",
                len(records),
                start_s,
                end_s,
            )
            return

        try:
            result = self.laravel.sync("transactions", records)
            logger.info(
                "Sync push transactions OK count=%s window=%s..%s: %s",
                len(records),
                start_s,
                end_s,
                result,
            )
        except LaravelError as exc:
            logger.error("Sync push transactions fallo: %s", exc)

    @staticmethod
    def _normalize_employee_payload(row):
        """Ajusta forma tipica BioTime → lo que espera BioTimeSyncService."""
        payload = dict(row)
        return payload

    @staticmethod
    def _normalize_area_payload(row):
        if not isinstance(row, dict):
            return {}
        payload = dict(row)
        if "id" not in payload and payload.get("pk") is not None:
            payload["id"] = payload["pk"]
        return payload

    @staticmethod
    def _normalize_department_payload(row):
        if not isinstance(row, dict):
            return {}
        payload = dict(row)
        if "id" not in payload and payload.get("pk") is not None:
            payload["id"] = payload["pk"]
        return payload

    @staticmethod
    def _normalize_device_payload(row):
        if not isinstance(row, dict):
            return {}
        payload = dict(row)
        # Laravel upsertDevice acepta sn / id / area objeto.
        if "sn" not in payload and payload.get("serial_number"):
            payload["sn"] = payload["serial_number"]
        return payload

    @staticmethod
    def _normalize_transaction_payload(row):
        if not isinstance(row, dict):
            return {}
        return dict(row)

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
