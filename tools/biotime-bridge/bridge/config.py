# -*- coding: utf-8 -*-
from __future__ import print_function

import os

import yaml


class BridgeConfig(object):
    def __init__(self, data):
        self.laravel_base_url = (data.get("laravel_base_url") or "").rstrip("/")
        self.laravel_token = data.get("laravel_token") or ""
        self.biotime_base_url = (data.get("biotime_base_url") or "http://127.0.0.1:8090").rstrip("/")
        self.biotime_user = data.get("biotime_user") or ""
        self.biotime_password = data.get("biotime_password") or ""
        self.biotime_auth_mode = data.get("biotime_auth_mode") or "auto"
        self.area_id = int(data.get("area_id") or 0)
        self.denied_area_id = int(data.get("denied_area_id") or 1)
        # Opcional en docs oficiales, pero requerido en runtime BioTime 8
        # (company debe coincidir con department: "Mismatched company pk…").
        self.company_id = int(data.get("company_id") or 0)
        self.department_id = int(data.get("department_id") or 0)
        self.sucursal_codigo = data.get("sucursal_codigo") or ""
        self.poll_seconds = max(5, int(data.get("poll_seconds") or 60))
        self.roster_reconcile_seconds = int(data.get("roster_reconcile_seconds") or 0)
        self.sync_push_seconds = int(data.get("sync_push_seconds") or 0)
        # Tras create/cambio de area: POST resync_to_device (default true).
        self.resync_after_area = bool(data.get("resync_after_area", True))
        self.dry_run = bool(data.get("dry_run", False))
        # True por defecto (produccion). false solo para HTTPS local con cert auto-firmado.
        self.laravel_verify_ssl = bool(data.get("laravel_verify_ssl", True))
        ua = data.get("laravel_user_agent")
        self.laravel_user_agent = (ua or "BioTimeBridge/0.1 (+gimnasio)").strip() or "BioTimeBridge/0.1 (+gimnasio)"
        self.http_timeout_seconds = float(data.get("http_timeout_seconds") or 30)
        self.max_retries = max(1, int(data.get("max_retries") or 3))
        self.retry_backoff_seconds = float(data.get("retry_backoff_seconds") or 2)
        self.log_dir = data.get("log_dir") or "logs"
        self.log_level = (data.get("log_level") or "INFO").upper()

    def to_dict(self):
        return {
            "laravel_base_url": self.laravel_base_url,
            "laravel_token": self.laravel_token,
            "laravel_verify_ssl": self.laravel_verify_ssl,
            "laravel_user_agent": self.laravel_user_agent,
            "biotime_base_url": self.biotime_base_url,
            "biotime_user": self.biotime_user,
            "biotime_password": self.biotime_password,
            "biotime_auth_mode": self.biotime_auth_mode,
            "area_id": self.area_id,
            "denied_area_id": self.denied_area_id,
            "company_id": self.company_id,
            "department_id": self.department_id,
            "sucursal_codigo": self.sucursal_codigo,
            "poll_seconds": self.poll_seconds,
            "roster_reconcile_seconds": self.roster_reconcile_seconds,
            "sync_push_seconds": self.sync_push_seconds,
            "resync_after_area": self.resync_after_area,
            "dry_run": self.dry_run,
            "http_timeout_seconds": self.http_timeout_seconds,
            "max_retries": self.max_retries,
            "retry_backoff_seconds": self.retry_backoff_seconds,
            "log_dir": self.log_dir,
            "log_level": self.log_level,
        }

    def validate(self):
        errors = []
        if not self.laravel_base_url:
            errors.append("laravel_base_url requerido")
        if not self.laravel_token:
            errors.append("laravel_token requerido")
        if not self.biotime_user or not self.biotime_password:
            errors.append("biotime_user / biotime_password requeridos")
        if self.area_id <= 0:
            errors.append("area_id debe ser > 0 (area autorizada)")
        if self.denied_area_id <= 0:
            errors.append("denied_area_id debe ser > 0")
        if self.area_id == self.denied_area_id:
            errors.append("area_id y denied_area_id no deben ser iguales")
        if self.company_id <= 0:
            errors.append(
                "company_id debe ser > 0 (requerido al crear; debe coincidir con department)"
            )
        if self.department_id <= 0:
            errors.append("department_id debe ser > 0 (requerido para create employee)")
        return errors


def load_config_dict(path):
    """Carga YAML crudo (dict). No valida."""
    if not os.path.isfile(path):
        raise IOError("No existe config: {0}".format(path))
    with open(path, "r", encoding="utf-8") as fh:
        data = yaml.safe_load(fh) or {}
    if not isinstance(data, dict):
        raise ValueError("config.yaml debe ser un objeto YAML")
    return data


def load_config(path):
    data = load_config_dict(path)
    cfg = BridgeConfig(data)
    errors = cfg.validate()
    if errors:
        raise ValueError("Config invalida: " + "; ".join(errors))
    return cfg


def save_config(path, updates, base=None):
    """
    Guarda config.yaml fusionando updates sobre base (o archivo existente).
    Valida antes de escribir. Retorna BridgeConfig.
    """
    if base is None:
        if os.path.isfile(path):
            base = load_config_dict(path)
        else:
            base = {}
    if not isinstance(base, dict):
        base = {}

    merged = dict(base)
    for key, value in updates.items():
        merged[key] = value

    cfg = BridgeConfig(merged)
    errors = cfg.validate()
    if errors:
        raise ValueError("Config invalida: " + "; ".join(errors))

    out = cfg.to_dict()
    # Conservar claves desconocidas del archivo original
    for key, value in merged.items():
        if key not in out:
            out[key] = value

    parent = os.path.dirname(os.path.abspath(path))
    if parent and not os.path.isdir(parent):
        os.makedirs(parent)

    with open(path, "w", encoding="utf-8") as fh:
        fh.write("# Generado/actualizado por la GUI del puente BioTime.\n")
        fh.write("# Token: BioTime UI Laravel → Sedes → regenerar/copiar.\n\n")
        yaml.safe_dump(
            out,
            fh,
            default_flow_style=False,
            allow_unicode=True,
            sort_keys=False,
        )

    return cfg


def default_config_path():
    here = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    return os.path.join(here, "config.yaml")
