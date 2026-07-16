# -*- coding: utf-8 -*-
"""Interfaz grafica del puente BioTime (tkinter, Python 3.7+)."""
from __future__ import print_function

import json
import logging
import os
import queue
import threading
import traceback

try:
    import tkinter as tk
    from tkinter import filedialog, messagebox, scrolledtext, ttk
except ImportError:  # pragma: no cover
    raise SystemExit(
        "tkinter no esta disponible en este Python. Instala el paquete Tk "
        "o usa la CLI: python -m bridge --config config.yaml doctor"
    )

from .biotime_client import BioTimeClient, BioTimeError
from .config import default_config_path, load_config, save_config
from .logging_setup import setup_logging
from .runner import BridgeRunner


class QueueLogHandler(logging.Handler):
    """Envia lineas de log a una queue.Queue para la GUI."""

    def __init__(self, log_queue):
        super(QueueLogHandler, self).__init__()
        self.log_queue = log_queue

    def emit(self, record):
        try:
            self.log_queue.put(self.format(record))
        except Exception:
            self.handleError(record)


class BridgeGuiApp(object):
    def __init__(self, config_path=None):
        self.config_path = config_path or default_config_path()
        self.cfg = None
        self.runner = None
        self._worker = None
        self._stop_event = threading.Event()
        self._busy = False
        self._log_queue = queue.Queue()
        self._form = {}

        self.root = tk.Tk()
        self.root.title("Puente BioTime")
        self.root.minsize(760, 580)
        self.root.geometry("920x680")

        # En Python 3.7 las Variables de tkinter requieren master (root) ya creado.
        self._show_secrets = tk.BooleanVar(master=self.root, value=False)
        self.status_var = tk.StringVar(master=self.root, value="Detenido")
        self.laravel_var = tk.StringVar(master=self.root, value="—")
        self.biotime_var = tk.StringVar(master=self.root, value="—")
        self.sede_var = tk.StringVar(master=self.root, value="—")
        self.config_var = tk.StringVar(master=self.root, value=self.config_path)
        self.dry_run_var = tk.StringVar(master=self.root, value="—")

        self._build_ui()
        self.root.protocol("WM_DELETE_WINDOW", self._on_close)
        self.root.after(200, self._drain_log_queue)

        self._reload_config(silent=True)

    def _build_ui(self):
        pad = {"padx": 10, "pady": 6}

        header = ttk.Frame(self.root)
        header.pack(fill=tk.X, **pad)
        ttk.Label(header, text="Puente BioTime ↔ Laravel", font=("Segoe UI", 14, "bold")).pack(
            side=tk.LEFT
        )
        ttk.Label(header, textvariable=self.status_var, font=("Segoe UI", 10)).pack(side=tk.RIGHT)

        cfg_row = ttk.Frame(self.root)
        cfg_row.pack(fill=tk.X, **pad)
        ttk.Label(cfg_row, text="Archivo:").pack(side=tk.LEFT)
        ttk.Entry(cfg_row, textvariable=self.config_var).pack(
            side=tk.LEFT, fill=tk.X, expand=True, padx=6
        )
        ttk.Button(cfg_row, text="Examinar…", command=self._browse_config).pack(side=tk.LEFT)
        ttk.Button(cfg_row, text="Recargar", command=self._reload_config).pack(side=tk.LEFT, padx=(6, 0))

        notebook = ttk.Notebook(self.root)
        notebook.pack(fill=tk.BOTH, expand=True, **pad)
        self.notebook = notebook

        tab_ops = ttk.Frame(notebook)
        tab_cfg = ttk.Frame(notebook)
        tab_tests = ttk.Frame(notebook)
        tab_log = ttk.Frame(notebook)
        notebook.add(tab_ops, text="Operación")
        notebook.add(tab_cfg, text="Configuración")
        notebook.add(tab_tests, text="Pruebas")
        notebook.add(tab_log, text="Log")

        self._build_ops_tab(tab_ops)
        self._build_config_tab(tab_cfg)
        self._build_tests_tab(tab_tests)
        self._build_log_tab(tab_log)

        footer = ttk.Frame(self.root)
        footer.pack(fill=tk.X, **pad)
        ttk.Label(
            footer,
            text="Segundo plano: botón o cerrar ventana (minimizar). Producción: Task Scheduler / start-background.bat",
            foreground="#555555",
        ).pack(side=tk.LEFT)

    def _build_ops_tab(self, parent):
        pad = {"padx": 10, "pady": 6}

        info = ttk.LabelFrame(parent, text="Estado (config cargada)")
        info.pack(fill=tk.X, **pad)
        grid = ttk.Frame(info)
        grid.pack(fill=tk.X, padx=8, pady=8)
        self._status_row(grid, 0, "Laravel", self.laravel_var)
        self._status_row(grid, 1, "BioTime", self.biotime_var)
        self._status_row(grid, 2, "Sede", self.sede_var)
        self._status_row(grid, 3, "dry_run", self.dry_run_var)

        actions = ttk.Frame(parent)
        actions.pack(fill=tk.X, **pad)
        self.btn_doctor = ttk.Button(actions, text="Doctor", command=self._do_doctor)
        self.btn_doctor.pack(side=tk.LEFT)
        self.btn_once = ttk.Button(actions, text="Una vez (once)", command=self._do_once)
        self.btn_once.pack(side=tk.LEFT, padx=6)
        self.btn_start = ttk.Button(actions, text="Iniciar (run)", command=self._do_start)
        self.btn_start.pack(side=tk.LEFT)
        self.btn_background = ttk.Button(
            actions, text="Segundo plano", command=self._do_background
        )
        self.btn_background.pack(side=tk.LEFT, padx=6)
        self.btn_stop = ttk.Button(actions, text="Detener", command=self._do_stop, state=tk.DISABLED)
        self.btn_stop.pack(side=tk.LEFT, padx=6)
        self.btn_restore = ttk.Button(
            actions, text="Mostrar ventana", command=self._restore_window, state=tk.DISABLED
        )
        self.btn_restore.pack(side=tk.LEFT, padx=6)

        log_frame = ttk.LabelFrame(parent, text="Registro")
        log_frame.pack(fill=tk.BOTH, expand=True, **pad)
        self.log_text = scrolledtext.ScrolledText(
            log_frame, height=16, wrap=tk.WORD, font=("Consolas", 9), state=tk.DISABLED
        )
        self.log_text.pack(fill=tk.BOTH, expand=True, padx=6, pady=6)

    def _build_tests_tab(self, parent):
        """Operaciones manuales contra BioTime (sin Laravel)."""
        pad = {"padx": 10, "pady": 6}
        self._test_btns = []

        warn = ttk.Label(
            parent,
            text=(
                "Opera directo sobre BioTime local (no encola en Laravel). "
                "Usa emp_code de prueba. dry_run de config no aplica aquí."
            ),
            foreground="#8a5a00",
            wraplength=860,
        )
        warn.pack(fill=tk.X, **pad)

        find_fr = ttk.LabelFrame(parent, text="Buscar empleado")
        find_fr.pack(fill=tk.X, **pad)
        find_row = ttk.Frame(find_fr)
        find_row.pack(fill=tk.X, padx=8, pady=6)
        ttk.Label(find_row, text="emp_code", width=12).pack(side=tk.LEFT)
        self.test_emp_code = tk.StringVar(master=self.root)
        ttk.Entry(find_row, textvariable=self.test_emp_code, width=24).pack(side=tk.LEFT, padx=4)
        self.btn_test_find = ttk.Button(find_row, text="Buscar", command=self._test_find)
        self.btn_test_find.pack(side=tk.LEFT, padx=4)
        self._test_btns.append(self.btn_test_find)
        self.test_found_var = tk.StringVar(master=self.root, value="(sin búsqueda)")
        ttk.Label(find_fr, textvariable=self.test_found_var).pack(anchor=tk.W, padx=8, pady=(0, 6))

        create_fr = ttk.LabelFrame(parent, text="Crear empleado")
        create_fr.pack(fill=tk.X, **pad)
        crow = ttk.Frame(create_fr)
        crow.pack(fill=tk.X, padx=8, pady=4)
        ttk.Label(crow, text="emp_code", width=12).pack(side=tk.LEFT)
        self.test_create_code = tk.StringVar(master=self.root)
        ttk.Entry(crow, textvariable=self.test_create_code, width=16).pack(side=tk.LEFT, padx=4)
        ttk.Label(crow, text="nombre").pack(side=tk.LEFT, padx=(8, 0))
        self.test_first_name = tk.StringVar(master=self.root)
        ttk.Entry(crow, textvariable=self.test_first_name, width=14).pack(side=tk.LEFT, padx=4)
        ttk.Label(crow, text="apellido").pack(side=tk.LEFT)
        self.test_last_name = tk.StringVar(master=self.root)
        ttk.Entry(crow, textvariable=self.test_last_name, width=14).pack(side=tk.LEFT, padx=4)

        crow2 = ttk.Frame(create_fr)
        crow2.pack(fill=tk.X, padx=8, pady=4)
        ttk.Label(crow2, text="área id", width=12).pack(side=tk.LEFT)
        self.test_create_area = tk.StringVar(master=self.root)
        ttk.Entry(crow2, textvariable=self.test_create_area, width=10).pack(side=tk.LEFT, padx=4)
        ttk.Label(crow2, text="company").pack(side=tk.LEFT, padx=(8, 0))
        self.test_company = tk.StringVar(master=self.root)
        ttk.Entry(crow2, textvariable=self.test_company, width=8).pack(side=tk.LEFT, padx=4)
        ttk.Label(crow2, text="department").pack(side=tk.LEFT, padx=(8, 0))
        self.test_department = tk.StringVar(master=self.root)
        ttk.Entry(crow2, textvariable=self.test_department, width=8).pack(side=tk.LEFT, padx=4)
        self.btn_test_create = ttk.Button(crow2, text="Crear", command=self._test_create)
        self.btn_test_create.pack(side=tk.LEFT, padx=8)
        self._test_btns.append(self.btn_test_create)
        ttk.Label(
            create_fr,
            text="Vacío → area_id / company_id / department_id de config. "
            "company y department deben coincidir en BioTime.",
            foreground="#555555",
        ).pack(anchor=tk.W, padx=8, pady=(0, 6))

        area_fr = ttk.LabelFrame(parent, text="Actualizar área")
        area_fr.pack(fill=tk.X, **pad)
        arow = ttk.Frame(area_fr)
        arow.pack(fill=tk.X, padx=8, pady=6)
        self.test_area_mode = tk.StringVar(master=self.root, value="authorized")
        ttk.Radiobutton(
            arow, text="Autorizada (config)", variable=self.test_area_mode, value="authorized"
        ).pack(side=tk.LEFT)
        ttk.Radiobutton(
            arow, text="Denegada (config)", variable=self.test_area_mode, value="denied"
        ).pack(side=tk.LEFT, padx=8)
        ttk.Radiobutton(
            arow, text="Personalizada", variable=self.test_area_mode, value="custom"
        ).pack(side=tk.LEFT)
        self.test_custom_area = tk.StringVar(master=self.root)
        ttk.Entry(arow, textvariable=self.test_custom_area, width=8).pack(side=tk.LEFT, padx=4)
        self.btn_test_area = ttk.Button(arow, text="Aplicar área", command=self._test_set_area)
        self.btn_test_area.pack(side=tk.LEFT, padx=8)
        self._test_btns.append(self.btn_test_area)
        self.btn_test_resync = ttk.Button(arow, text="Resync dispositivo", command=self._test_resync)
        self.btn_test_resync.pack(side=tk.LEFT)
        self._test_btns.append(self.btn_test_resync)

        del_fr = ttk.LabelFrame(parent, text="Eliminar empleado")
        del_fr.pack(fill=tk.X, **pad)
        drow = ttk.Frame(del_fr)
        drow.pack(fill=tk.X, padx=8, pady=6)
        ttk.Label(
            drow,
            text="Borra por emp_code (pierde biometría). Confirmación obligatoria.",
            foreground="#555555",
        ).pack(side=tk.LEFT)
        self.btn_test_delete = ttk.Button(drow, text="Eliminar…", command=self._test_delete)
        self.btn_test_delete.pack(side=tk.RIGHT, padx=4)
        self._test_btns.append(self.btn_test_delete)

        out_fr = ttk.LabelFrame(parent, text="Resultado")
        out_fr.pack(fill=tk.BOTH, expand=True, **pad)
        self.test_out = scrolledtext.ScrolledText(
            out_fr, height=10, wrap=tk.WORD, font=("Consolas", 9), state=tk.DISABLED
        )
        self.test_out.pack(fill=tk.BOTH, expand=True, padx=6, pady=6)

        self._last_test_emp = None  # dict empleado encontrado

    def _build_log_tab(self, parent):
        pad = {"padx": 10, "pady": 6}
        self.log_file_var = tk.StringVar(master=self.root, value="—")

        top = ttk.Frame(parent)
        top.pack(fill=tk.X, **pad)
        ttk.Label(top, text="Archivo:").pack(side=tk.LEFT)
        ttk.Label(top, textvariable=self.log_file_var, foreground="#333333").pack(
            side=tk.LEFT, padx=6
        )
        ttk.Button(top, text="Recargar archivo", command=self._log_reload_file).pack(side=tk.RIGHT)
        ttk.Button(top, text="Limpiar vista", command=self._log_clear_view).pack(
            side=tk.RIGHT, padx=6
        )
        ttk.Button(top, text="Abrir carpeta", command=self._log_open_dir).pack(side=tk.RIGHT)

        tip = ttk.Label(
            parent,
            text="Vista en vivo del bridge + contenido de logs/biotime-bridge.log",
            foreground="#555555",
        )
        tip.pack(anchor=tk.W, padx=10)

        self.log_tab_text = scrolledtext.ScrolledText(
            parent, height=28, wrap=tk.WORD, font=("Consolas", 9), state=tk.DISABLED
        )
        self.log_tab_text.pack(fill=tk.BOTH, expand=True, padx=10, pady=6)
        self._refresh_log_file_path()

    def _build_config_tab(self, parent):
        pad = {"padx": 10, "pady": 6}

        canvas = tk.Canvas(parent, highlightthickness=0)
        scroll = ttk.Scrollbar(parent, orient=tk.VERTICAL, command=canvas.yview)
        form = ttk.Frame(canvas)
        form.bind(
            "<Configure>",
            lambda e: canvas.configure(scrollregion=canvas.bbox("all")),
        )
        canvas.create_window((0, 0), window=form, anchor=tk.NW)
        canvas.configure(yscrollcommand=scroll.set)
        canvas.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=(10, 0), pady=6)
        scroll.pack(side=tk.RIGHT, fill=tk.Y, pady=6, padx=(0, 10))

        def _on_mousewheel(event):
            canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")

        canvas.bind_all("<MouseWheel>", _on_mousewheel)

        laravel = ttk.LabelFrame(form, text="Laravel")
        laravel.pack(fill=tk.X, **pad)
        self._add_field(laravel, "laravel_base_url", "URL base", width=56)
        self._add_field(laravel, "laravel_token", "Token sede", width=56, secret=True)
        self._add_check(laravel, "laravel_verify_ssl", "Verificar SSL (desmarcar en HTTPS local)")
        self._add_field(laravel, "laravel_user_agent", "User-Agent HTTP", width=56)

        biotime = ttk.LabelFrame(form, text="BioTime local")
        biotime.pack(fill=tk.X, **pad)
        self._add_field(biotime, "biotime_base_url", "URL BioTime", width=56)
        self._add_field(biotime, "biotime_user", "Usuario", width=28)
        self._add_field(biotime, "biotime_password", "Password", width=28, secret=True)
        self._add_combo(
            biotime,
            "biotime_auth_mode",
            "Auth",
            ("auto", "jwt", "token"),
        )

        areas = ttk.LabelFrame(form, text="Áreas y sede")
        areas.pack(fill=tk.X, **pad)
        self._add_field(areas, "area_id", "Área autorizada (id)", width=12)
        self._add_field(areas, "denied_area_id", "Área denegada (id)", width=12)
        self._add_field(areas, "department_id", "Department (id, create)", width=12)
        self._add_field(areas, "company_id", "Company (id, create)", width=12)
        self._add_field(areas, "sucursal_codigo", "Código sede", width=28)

        timing = ttk.LabelFrame(form, text="Tiempos y opciones")
        timing.pack(fill=tk.X, **pad)
        self._add_field(timing, "poll_seconds", "Poll (seg)", width=12)
        self._add_field(timing, "roster_reconcile_seconds", "Roster (seg, 0=off)", width=12)
        self._add_field(timing, "sync_push_seconds", "Sync employees (seg, 0=off)", width=12)
        self._add_check(
            timing,
            "resync_after_area",
            "resync_after_area (empujar a dispositivos tras create/área)",
        )
        self._add_check(timing, "dry_run", "dry_run (no escribe áreas en BioTime)")
        self._add_field(timing, "http_timeout_seconds", "Timeout HTTP", width=12)
        self._add_field(timing, "max_retries", "Reintentos", width=12)
        self._add_field(timing, "retry_backoff_seconds", "Backoff (seg)", width=12)
        self._add_field(timing, "log_dir", "Carpeta logs", width=28)
        self._add_combo(timing, "log_level", "Nivel log", ("DEBUG", "INFO", "WARNING", "ERROR"))

        secrets_row = ttk.Frame(form)
        secrets_row.pack(fill=tk.X, **pad)
        ttk.Checkbutton(
            secrets_row,
            text="Mostrar token y password",
            variable=self._show_secrets,
            command=self._toggle_secrets,
        ).pack(side=tk.LEFT)

        btns = ttk.Frame(form)
        btns.pack(fill=tk.X, **pad)
        self.btn_save = ttk.Button(btns, text="Guardar config.yaml", command=self._save_config)
        self.btn_save.pack(side=tk.LEFT)
        ttk.Button(btns, text="Descartar cambios (recargar)", command=self._reload_config).pack(
            side=tk.LEFT, padx=8
        )

    def _add_field(self, parent, key, label, width=40, secret=False):
        row = ttk.Frame(parent)
        row.pack(fill=tk.X, padx=8, pady=3)
        ttk.Label(row, text=label, width=28).pack(side=tk.LEFT)
        var = tk.StringVar(master=self.root)
        entry = ttk.Entry(row, textvariable=var, width=width, show="•" if secret else "")
        entry.pack(side=tk.LEFT, fill=tk.X, expand=True)
        self._form[key] = {"var": var, "widget": entry, "secret": secret, "kind": "entry"}

    def _add_check(self, parent, key, label):
        row = ttk.Frame(parent)
        row.pack(fill=tk.X, padx=8, pady=3)
        var = tk.BooleanVar(master=self.root, value=False)
        ttk.Checkbutton(row, text=label, variable=var).pack(side=tk.LEFT)
        self._form[key] = {"var": var, "kind": "check"}

    def _add_combo(self, parent, key, label, values):
        row = ttk.Frame(parent)
        row.pack(fill=tk.X, padx=8, pady=3)
        ttk.Label(row, text=label, width=28).pack(side=tk.LEFT)
        var = tk.StringVar(master=self.root)
        combo = ttk.Combobox(row, textvariable=var, values=values, state="readonly", width=16)
        combo.pack(side=tk.LEFT)
        self._form[key] = {"var": var, "widget": combo, "kind": "combo"}

    def _toggle_secrets(self):
        show = "" if self._show_secrets.get() else "•"
        for meta in self._form.values():
            if meta.get("secret") and meta.get("widget") is not None:
                meta["widget"].configure(show=show)

    @staticmethod
    def _status_row(parent, row, label, var):
        ttk.Label(parent, text=label + ":", width=10).grid(row=row, column=0, sticky=tk.W, pady=2)
        ttk.Label(parent, textvariable=var).grid(row=row, column=1, sticky=tk.W, pady=2)

    def _browse_config(self):
        path = filedialog.askopenfilename(
            title="Seleccionar config.yaml",
            filetypes=[("YAML", "*.yaml;*.yml"), ("Todos", "*.*")],
            initialdir=os.path.dirname(self.config_path) or os.getcwd(),
        )
        if path:
            self.config_var.set(path)
            self._reload_config()

    def _fill_form_from_cfg(self):
        if self.cfg is None:
            return
        c = self.cfg
        mapping = {
            "laravel_base_url": c.laravel_base_url,
            "laravel_token": c.laravel_token,
            "laravel_verify_ssl": c.laravel_verify_ssl,
            "laravel_user_agent": c.laravel_user_agent,
            "biotime_base_url": c.biotime_base_url,
            "biotime_user": c.biotime_user,
            "biotime_password": c.biotime_password,
            "biotime_auth_mode": c.biotime_auth_mode,
            "area_id": str(c.area_id),
            "denied_area_id": str(c.denied_area_id),
            "company_id": str(c.company_id),
            "department_id": str(c.department_id),
            "sucursal_codigo": c.sucursal_codigo,
            "poll_seconds": str(c.poll_seconds),
            "roster_reconcile_seconds": str(c.roster_reconcile_seconds),
            "sync_push_seconds": str(c.sync_push_seconds),
            "resync_after_area": c.resync_after_area,
            "dry_run": c.dry_run,
            "http_timeout_seconds": str(int(c.http_timeout_seconds)
                if c.http_timeout_seconds == int(c.http_timeout_seconds)
                else c.http_timeout_seconds),
            "max_retries": str(c.max_retries),
            "retry_backoff_seconds": str(int(c.retry_backoff_seconds)
                if c.retry_backoff_seconds == int(c.retry_backoff_seconds)
                else c.retry_backoff_seconds),
            "log_dir": c.log_dir,
            "log_level": c.log_level,
        }
        for key, value in mapping.items():
            meta = self._form.get(key)
            if not meta:
                continue
            if meta["kind"] == "check":
                meta["var"].set(bool(value))
            else:
                meta["var"].set("" if value is None else str(value))

    def _form_to_updates(self):
        def text(key):
            return (self._form[key]["var"].get() or "").strip()

        def as_int(key, label):
            raw = text(key)
            try:
                return int(raw)
            except Exception:
                raise ValueError("{0} debe ser un entero".format(label))

        def as_float(key, label):
            raw = text(key)
            try:
                return float(raw)
            except Exception:
                raise ValueError("{0} debe ser un numero".format(label))

        return {
            "laravel_base_url": text("laravel_base_url").rstrip("/"),
            "laravel_token": text("laravel_token"),
            "laravel_verify_ssl": bool(self._form["laravel_verify_ssl"]["var"].get()),
            "laravel_user_agent": text("laravel_user_agent") or "BioTimeBridge/0.1 (+gimnasio)",
            "biotime_base_url": text("biotime_base_url").rstrip("/"),
            "biotime_user": text("biotime_user"),
            "biotime_password": text("biotime_password"),
            "biotime_auth_mode": text("biotime_auth_mode") or "auto",
            "area_id": as_int("area_id", "area_id"),
            "denied_area_id": as_int("denied_area_id", "denied_area_id"),
            "company_id": as_int("company_id", "company_id"),
            "department_id": as_int("department_id", "department_id"),
            "sucursal_codigo": text("sucursal_codigo"),
            "poll_seconds": as_int("poll_seconds", "poll_seconds"),
            "roster_reconcile_seconds": as_int(
                "roster_reconcile_seconds", "roster_reconcile_seconds"
            ),
            "sync_push_seconds": as_int("sync_push_seconds", "sync_push_seconds"),
            "resync_after_area": bool(self._form["resync_after_area"]["var"].get()),
            "dry_run": bool(self._form["dry_run"]["var"].get()),
            "http_timeout_seconds": as_float("http_timeout_seconds", "http_timeout_seconds"),
            "max_retries": as_int("max_retries", "max_retries"),
            "retry_backoff_seconds": as_float(
                "retry_backoff_seconds", "retry_backoff_seconds"
            ),
            "log_dir": text("log_dir") or "logs",
            "log_level": text("log_level") or "INFO",
        }

    def _save_config(self):
        if self._busy:
            messagebox.showwarning("Config", "Detén el puente antes de guardar la configuración.")
            return
        path = (self.config_var.get() or "").strip() or default_config_path()
        try:
            updates = self._form_to_updates()
            self.cfg = save_config(path, updates)
        except Exception as exc:
            messagebox.showerror("Guardar config", str(exc))
            self._append_log("Guardar config FAIL: {0}".format(exc))
            return

        self.config_path = path
        self.config_var.set(path)
        setup_logging(self.cfg.log_dir, self.cfg.log_level)
        self._attach_gui_log_handler()
        self._apply_status_from_cfg()
        self._append_log("Config guardada: {0}".format(path))
        self.status_var.set("Config guardada")
        messagebox.showinfo("Config", "config.yaml guardado correctamente.")

    def _apply_status_from_cfg(self):
        if self.cfg is None:
            self.laravel_var.set("—")
            self.biotime_var.set("—")
            self.sede_var.set("—")
            self.dry_run_var.set("—")
            return
        self.laravel_var.set(self.cfg.laravel_base_url)
        self.biotime_var.set(self.cfg.biotime_base_url)
        self.sede_var.set(self.cfg.sucursal_codigo or "(sin codigo)")
        self.dry_run_var.set("si" if self.cfg.dry_run else "no")
        self._prefill_test_defaults()

    def _prefill_test_defaults(self):
        if self.cfg is None:
            return
        if hasattr(self, "test_create_area") and not (self.test_create_area.get() or "").strip():
            self.test_create_area.set(str(self.cfg.area_id))
        if hasattr(self, "test_company") and not (self.test_company.get() or "").strip():
            self.test_company.set(str(self.cfg.company_id))
        if hasattr(self, "test_department") and not (self.test_department.get() or "").strip():
            self.test_department.set(str(self.cfg.department_id))
        if hasattr(self, "test_custom_area") and not (self.test_custom_area.get() or "").strip():
            self.test_custom_area.set(str(self.cfg.area_id))
        self._refresh_log_file_path()

    def _log_path(self):
        log_dir = "logs"
        if self.cfg is not None and self.cfg.log_dir:
            log_dir = self.cfg.log_dir
        if not os.path.isabs(log_dir):
            base = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            log_dir = os.path.join(base, log_dir)
        return os.path.join(log_dir, "biotime-bridge.log")

    def _refresh_log_file_path(self):
        if hasattr(self, "log_file_var"):
            self.log_file_var.set(self._log_path())

    def _log_clear_view(self):
        for widget in (getattr(self, "log_text", None), getattr(self, "log_tab_text", None)):
            if widget is None:
                continue
            widget.configure(state=tk.NORMAL)
            widget.delete("1.0", tk.END)
            widget.configure(state=tk.DISABLED)

    def _log_reload_file(self):
        path = self._log_path()
        self._refresh_log_file_path()
        if not os.path.isfile(path):
            messagebox.showinfo("Log", "Aún no existe el archivo:\n{0}".format(path))
            return
        try:
            with open(path, "r", encoding="utf-8", errors="replace") as fh:
                # Últimas ~400 líneas para no congelar la GUI
                lines = fh.readlines()
                content = "".join(lines[-400:])
        except Exception as exc:
            messagebox.showerror("Log", str(exc))
            return

        for widget in (getattr(self, "log_text", None), getattr(self, "log_tab_text", None)):
            if widget is None:
                continue
            widget.configure(state=tk.NORMAL)
            widget.delete("1.0", tk.END)
            widget.insert(tk.END, content)
            widget.see(tk.END)
            widget.configure(state=tk.DISABLED)
        self._append_log("(Recargado desde archivo: {0})".format(path))

    def _log_open_dir(self):
        path = self._log_path()
        folder = os.path.dirname(path)
        if not os.path.isdir(folder):
            try:
                os.makedirs(folder)
            except Exception as exc:
                messagebox.showerror("Log", str(exc))
                return
        try:
            os.startfile(folder)
        except Exception as exc:
            messagebox.showerror("Log", str(exc))

    def _reload_config(self, silent=False):
        path = (self.config_var.get() or "").strip() or default_config_path()
        self.config_path = path
        self.config_var.set(path)
        try:
            self.cfg = load_config(path)
        except Exception as exc:
            self.cfg = None
            self._apply_status_from_cfg()
            if not silent:
                messagebox.showerror("Config", str(exc))
            self._append_log("Config error: {0}".format(exc))
            return

        setup_logging(self.cfg.log_dir, self.cfg.log_level)
        self._attach_gui_log_handler()
        self._fill_form_from_cfg()
        self._apply_status_from_cfg()
        self._append_log("Config cargada: {0}".format(path))
        if not silent:
            self.status_var.set("Config OK — detenido")

    def _attach_gui_log_handler(self):
        root_logger = logging.getLogger()
        for h in list(root_logger.handlers):
            if isinstance(h, QueueLogHandler):
                root_logger.removeHandler(h)
        handler = QueueLogHandler(self._log_queue)
        handler.setFormatter(
            logging.Formatter(
                "%(asctime)s [%(levelname)s] %(message)s",
                datefmt="%H:%M:%S",
            )
        )
        root_logger.addHandler(handler)

    def _append_log(self, line):
        for widget in (getattr(self, "log_text", None), getattr(self, "log_tab_text", None)):
            if widget is None:
                continue
            widget.configure(state=tk.NORMAL)
            widget.insert(tk.END, line + "\n")
            widget.see(tk.END)
            widget.configure(state=tk.DISABLED)

    def _drain_log_queue(self):
        try:
            while True:
                line = self._log_queue.get_nowait()
                self._append_log(line)
        except queue.Empty:
            pass
        self.root.after(200, self._drain_log_queue)

    def _set_busy(self, busy, running_loop=False):
        self._busy = busy
        state_idle = tk.DISABLED if busy else tk.NORMAL
        self.btn_doctor.configure(state=state_idle)
        self.btn_once.configure(state=state_idle)
        self.btn_start.configure(state=state_idle)
        self.btn_background.configure(state=state_idle)
        self.btn_save.configure(state=state_idle)
        for btn in getattr(self, "_test_btns", []):
            btn.configure(state=state_idle)
        if running_loop:
            self.btn_stop.configure(state=tk.NORMAL)
            self.btn_background.configure(state=tk.NORMAL)
            self.btn_doctor.configure(state=tk.DISABLED)
            self.btn_once.configure(state=tk.DISABLED)
            self.btn_start.configure(state=tk.DISABLED)
            self.btn_save.configure(state=tk.DISABLED)
            for btn in getattr(self, "_test_btns", []):
                btn.configure(state=tk.DISABLED)
        elif not busy:
            self.btn_stop.configure(state=tk.DISABLED)
            self.btn_background.configure(state=tk.DISABLED)
            self.btn_restore.configure(state=tk.DISABLED)

    def _ensure_config(self):
        if self.cfg is None:
            self._reload_config()
        if self.cfg is None:
            return False
        return True

    def _make_runner(self):
        if self.runner is not None:
            try:
                self.runner.close()
            except Exception:
                pass
        self.runner = BridgeRunner(self.cfg)
        return self.runner

    def _run_job(self, title, fn, running_loop=False):
        if self._busy:
            return
        if not self._ensure_config():
            return

        self._stop_event.clear()
        self._set_busy(True, running_loop=running_loop)
        self.status_var.set(title)

        def worker():
            code = 1
            try:
                runner = self._make_runner()
                code = fn(runner)
            except Exception as exc:
                logging.getLogger(__name__).error("%s fallo: %s", title, exc)
                logging.getLogger(__name__).debug(traceback.format_exc())
                self.root.after(
                    0,
                    lambda e=exc, t=title: messagebox.showerror(t, str(e)),
                )
                code = 1
            finally:
                def done():
                    if not running_loop or self._stop_event.is_set() or code != 0:
                        self._set_busy(False, running_loop=False)
                        if self._stop_event.is_set():
                            self.status_var.set("Detenido")
                        elif code == 0 and not running_loop:
                            self.status_var.set("{0} — OK".format(title))
                        elif code != 0:
                            self.status_var.set("{0} — FAIL".format(title))
                        else:
                            self.status_var.set("Detenido")

                self.root.after(0, done)

        self._worker = threading.Thread(target=worker, name="bridge-gui-worker")
        self._worker.daemon = True
        self._worker.start()

    def _append_test_out(self, text):
        self.test_out.configure(state=tk.NORMAL)
        self.test_out.insert(tk.END, text + "\n")
        self.test_out.see(tk.END)
        self.test_out.configure(state=tk.DISABLED)

    def _summarize_emp(self, emp):
        if not isinstance(emp, dict):
            return str(emp)
        area_ids = BioTimeClient.employee_area_ids(None, emp)
        return "id={0} emp_code={1} name={2} {3} areas={4}".format(
            emp.get("id"),
            emp.get("emp_code"),
            emp.get("first_name") or "",
            emp.get("last_name") or "",
            area_ids,
        )

    def _run_biotime_test(self, title, fn):
        """Ejecuta fn(client) contra BioTime en hilo; no toca Laravel."""
        if self._busy:
            messagebox.showwarning("Pruebas", "Detén el puente antes de usar Pruebas.")
            return
        if not self._ensure_config():
            return

        self._set_busy(True, running_loop=False)
        self.status_var.set(title)

        def worker():
            client = None
            try:
                client = BioTimeClient(
                    self.cfg.biotime_base_url,
                    timeout=self.cfg.http_timeout_seconds,
                )
                client.login(
                    self.cfg.biotime_user,
                    self.cfg.biotime_password,
                    mode=self.cfg.biotime_auth_mode,
                )
                result = fn(client)
                msg = result if isinstance(result, str) else json.dumps(
                    result, ensure_ascii=False, indent=2, default=str
                )
                logging.getLogger(__name__).info("%s OK", title)

                def ok():
                    self._append_test_out("=== {0} ===".format(title))
                    self._append_test_out(msg)
                    self._append_log("{0}: OK".format(title))
                    self.status_var.set("{0} — OK".format(title))
                    self._set_busy(False)

                self.root.after(0, ok)
            except Exception as exc:
                logging.getLogger(__name__).error("%s FAIL: %s", title, exc)
                logging.getLogger(__name__).debug(traceback.format_exc())

                def fail(e=exc):
                    self._append_test_out("=== {0} FAIL ===\n{1}".format(title, e))
                    self._append_log("{0}: FAIL — {1}".format(title, e))
                    messagebox.showerror(title, str(e))
                    self.status_var.set("{0} — FAIL".format(title))
                    self._set_busy(False)

                self.root.after(0, fail)
            finally:
                if client is not None:
                    try:
                        client.close()
                    except Exception:
                        pass

        t = threading.Thread(target=worker, name="bridge-gui-test")
        t.daemon = True
        t.start()

    def _test_emp_code_value(self):
        code = (self.test_emp_code.get() or "").strip()
        if not code:
            code = (self.test_create_code.get() or "").strip()
        return code

    def _resolve_area_id_for_mode(self):
        mode = self.test_area_mode.get() or "authorized"
        if mode == "denied":
            return int(self.cfg.denied_area_id)
        if mode == "custom":
            raw = (self.test_custom_area.get() or "").strip()
            if not raw:
                raise ValueError("Indica el id de área personalizada")
            return int(raw)
        return int(self.cfg.area_id)

    def _test_find(self):
        code = self._test_emp_code_value()
        if not code:
            messagebox.showwarning("Buscar", "Indica emp_code")
            return

        def job(client):
            emp = client.find_employee_by_code(code)
            if not emp:
                self.root.after(
                    0,
                    lambda: self.test_found_var.set("No encontrado: emp_code={0}".format(code)),
                )
                self._last_test_emp = None
                return "No encontrado emp_code={0}".format(code)
            self._last_test_emp = emp
            summary = self._summarize_emp(emp)
            self.root.after(0, lambda s=summary: self.test_found_var.set(s))
            return emp

        self._run_biotime_test("Buscar {0}".format(code), job)

    def _test_create(self):
        code = (self.test_create_code.get() or "").strip() or (self.test_emp_code.get() or "").strip()
        if not code:
            messagebox.showwarning("Crear", "Indica emp_code")
            return
        first = (self.test_first_name.get() or "").strip() or code
        last = (self.test_last_name.get() or "").strip()
        try:
            area_raw = (self.test_create_area.get() or "").strip()
            area_id = int(area_raw) if area_raw else int(self.cfg.area_id)
            company_raw = (self.test_company.get() or "").strip()
            company_id = int(company_raw) if company_raw else int(self.cfg.company_id)
            dept_raw = (self.test_department.get() or "").strip()
            dept_id = int(dept_raw) if dept_raw else int(self.cfg.department_id)
        except ValueError:
            messagebox.showerror("Crear", "área / company / department deben ser enteros")
            return
        if company_id <= 0 or dept_id <= 0:
            messagebox.showerror(
                "Crear",
                "company_id y department_id deben ser > 0 y coincidir en BioTime",
            )
            return

        def job(client):
            existing = client.find_employee_by_code(code)
            if existing and existing.get("id") is not None:
                raise BioTimeError(
                    "Ya existe emp_code={0} id={1}".format(code, existing.get("id"))
                )
            emp = client.create_employee(
                emp_code=code,
                first_name=first,
                last_name=last,
                company_id=company_id,
                department_id=dept_id,
                area_ids=[area_id],
            )
            self._last_test_emp = emp if isinstance(emp, dict) else None
            if self.cfg.resync_after_area and isinstance(emp, dict) and emp.get("id") is not None:
                try:
                    client.resync_employees_to_device([int(emp["id"])])
                except BioTimeError as exc:
                    logging.getLogger(__name__).warning("resync tras create: %s", exc)
            self.root.after(
                0,
                lambda: (
                    self.test_emp_code.set(code),
                    self.test_found_var.set(self._summarize_emp(emp) if isinstance(emp, dict) else str(emp)),
                ),
            )
            return emp

        self._run_biotime_test("Crear {0}".format(code), job)

    def _test_set_area(self):
        code = self._test_emp_code_value()
        if not code:
            messagebox.showwarning("Área", "Indica emp_code (buscar o crear)")
            return
        try:
            area_id = self._resolve_area_id_for_mode()
        except ValueError as exc:
            messagebox.showerror("Área", str(exc))
            return

        def job(client):
            emp = client.find_employee_by_code(code)
            if not emp or emp.get("id") is None:
                raise BioTimeError("Empleado no encontrado emp_code={0}".format(code))
            emp_id = int(emp["id"])
            client.set_employee_areas(emp_id, [area_id], employee=emp)
            if self.cfg.resync_after_area:
                try:
                    client.resync_employees_to_device([emp_id])
                except BioTimeError as exc:
                    logging.getLogger(__name__).warning("resync tras área: %s", exc)
            after = client.get_employee(emp_id)
            self._last_test_emp = after if isinstance(after, dict) else emp
            summary = self._summarize_emp(self._last_test_emp)
            self.root.after(0, lambda s=summary: self.test_found_var.set(s))
            return {"emp_code": code, "biotime_id": emp_id, "areas": [area_id], "employee": after}

        self._run_biotime_test("Área emp_code={0} → {1}".format(code, area_id), job)

    def _test_resync(self):
        code = self._test_emp_code_value()
        if not code:
            messagebox.showwarning("Resync", "Indica emp_code")
            return

        def job(client):
            emp = client.find_employee_by_code(code)
            if not emp or emp.get("id") is None:
                raise BioTimeError("Empleado no encontrado emp_code={0}".format(code))
            emp_id = int(emp["id"])
            result = client.resync_employees_to_device([emp_id])
            return {"emp_code": code, "biotime_id": emp_id, "resync": result}

        self._run_biotime_test("Resync {0}".format(code), job)

    def _test_delete(self):
        code = self._test_emp_code_value()
        if not code:
            messagebox.showwarning("Eliminar", "Indica emp_code")
            return
        if not messagebox.askyesno(
            "Eliminar empleado",
            "¿Eliminar emp_code={0} de BioTime?\n"
            "Se pierde la biometría. Esta acción no se puede deshacer.".format(code),
        ):
            return

        def job(client):
            emp = client.find_employee_by_code(code)
            if not emp or emp.get("id") is None:
                return "Ya ausente emp_code={0}".format(code)
            emp_id = int(emp["id"])
            client.delete_employee(emp_id)
            self._last_test_emp = None
            self.root.after(
                0,
                lambda: self.test_found_var.set("Eliminado emp_code={0} id={1}".format(code, emp_id)),
            )
            return {"deleted": True, "emp_code": code, "biotime_id": emp_id}

        self._run_biotime_test("Eliminar {0}".format(code), job)

    def _do_doctor(self):
        def job(runner):
            return runner.doctor()

        self._run_job("Doctor", job)

    def _do_once(self):
        def job(runner):
            runner.biotime.login(
                self.cfg.biotime_user,
                self.cfg.biotime_password,
                mode=self.cfg.biotime_auth_mode,
            )
            runner.poll_commands()
            if self.cfg.roster_reconcile_seconds > 0:
                runner.roster_reconcile()
            if self.cfg.sync_push_seconds > 0:
                runner.push_employees()
            return 0

        self._run_job("Once", job)

    def _do_start(self):
        def job(runner):
            runner.start(should_stop=self._stop_event.is_set)
            return 0

        self._run_job("En ejecucion", job, running_loop=True)

    def _do_background(self):
        """Inicia (si no corre) y oculta la ventana; el puente sigue en segundo plano."""
        if not self._busy:
            self._do_start()
        self.root.after(300, self._withdraw_to_background)

    def _withdraw_to_background(self):
        self.root.withdraw()
        self.status_var.set("Segundo plano (poll activo)")
        self.btn_restore.configure(state=tk.NORMAL)
        try:
            self.root.iconify()
        except Exception:
            pass
        logging.getLogger(__name__).info(
            "Puente en segundo plano. Usa 'Mostrar ventana' o la barra de tareas."
        )

    def _restore_window(self):
        try:
            self.root.deiconify()
        except Exception:
            pass
        self.root.lift()
        self.btn_restore.configure(state=tk.DISABLED)
        if self._busy:
            self.status_var.set("En ejecucion")

    def _do_stop(self):
        self._stop_event.set()
        self.status_var.set("Deteniendo…")
        self.btn_stop.configure(state=tk.DISABLED)
        self._restore_window()

    def _on_close(self):
        if self._busy and not self._stop_event.is_set():
            choice = messagebox.askyesnocancel(
                "Puente BioTime",
                "El puente esta en ejecucion.\n\n"
                "Si = minimizar a segundo plano (sigue corriendo)\n"
                "No = detener y salir\n"
                "Cancelar = volver",
            )
            if choice is None:
                return
            if choice:
                self._withdraw_to_background()
                return
            self._stop_event.set()
            if self._worker is not None and self._worker.is_alive():
                self._worker.join(timeout=3.0)

        self._stop_event.set()
        if self.runner is not None:
            try:
                self.runner.close()
            except Exception:
                pass
        try:
            self.root.unbind_all("<MouseWheel>")
        except Exception:
            pass
        self.root.destroy()

    def run(self):
        self.root.mainloop()
        return 0


def run_gui(config_path=None):
    app = BridgeGuiApp(config_path=config_path)
    return app.run()
