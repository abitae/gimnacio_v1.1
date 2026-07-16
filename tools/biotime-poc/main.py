#!/usr/bin/env python3
"""
PoC BioTime 8.x — auth + buscar empleado + asignar/quitar areas.
Compatible con Python 3.7+. Uso: ver README.md
"""

from __future__ import annotations

import argparse
import json
import os
import sys
from collections import namedtuple

try:
    import requests
except ImportError:
    print("Falta requests. Instala: pip install -r requirements.txt", file=sys.stderr)
    sys.exit(1)


AuthResult = namedtuple("AuthResult", ["scheme", "token", "endpoint"])


class BioTimeError(Exception):
    def __init__(self, message, status=None, body=None):
        super(BioTimeError, self).__init__(message)
        self.status = status
        self.body = body


def env(name, default=None):
    value = os.environ.get(name, default)
    if value in (None, ""):
        return default
    return value


def load_dotenv(path=".env"):
    """Carga .env simple (KEY=VALUE) sin dependencia externa."""
    if not os.path.isfile(path):
        return
    with open(path, encoding="utf-8") as fh:
        for line in fh:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, val = line.partition("=")
            key = key.strip()
            val = val.strip().strip('"').strip("'")
            if key and key not in os.environ:
                os.environ[key] = val


def base_url():
    url = env("BIOTIME_URL", "http://127.0.0.1:8090") or "http://127.0.0.1:8090"
    return url.rstrip("/") + "/"


def format_http_error(resp):
    try:
        payload = resp.json()
        detail = json.dumps(payload, ensure_ascii=False, indent=2)
    except Exception:
        detail = (resp.text or "")[:2000]
    return "HTTP {0} {1} {2}\n{3}".format(
        resp.status_code, resp.request.method, resp.url, detail
    )


class BioTimeClient(object):
    def __init__(self, url, timeout=30.0):
        self.url = url if url.endswith("/") else url + "/"
        self._session = requests.Session()
        self._timeout = timeout
        self.auth = None

    def close(self):
        self._session.close()

    def __enter__(self):
        return self

    def __exit__(self, *args):
        self.close()

    def _headers(self):
        headers = {"Content-Type": "application/json", "Accept": "application/json"}
        if self.auth:
            headers["Authorization"] = "{0} {1}".format(self.auth.scheme, self.auth.token)
        return headers

    def _request(self, method, path, **kwargs):
        path = path.lstrip("/")
        url = self.url + path
        try:
            resp = self._session.request(
                method, url, headers=self._headers(), timeout=self._timeout, **kwargs
            )
        except requests.RequestException as exc:
            raise BioTimeError("Error de red: {0}".format(exc))

        if resp.status_code >= 400:
            raise BioTimeError(format_http_error(resp), status=resp.status_code, body=resp.text)

        if resp.status_code == 204 or not resp.content:
            return None
        try:
            return resp.json()
        except Exception:
            return {"raw": resp.text}

    def login(self, username, password, mode="auto"):
        """Prueba JWT y/o token auth segun mode: auto | jwt | token."""
        body = {"username": username, "password": password}
        attempts = []
        if mode in ("auto", "jwt"):
            attempts.append(("jwt-api-token-auth/", "JWT"))
        if mode in ("auto", "token"):
            attempts.append(("api-token-auth/", "Token"))

        errors = []
        for endpoint, scheme in attempts:
            try:
                data = self._request("POST", endpoint, json=body)
            except BioTimeError as exc:
                errors.append("{0}: {1}".format(endpoint, exc))
                continue

            token = None
            if isinstance(data, dict):
                token = data.get("token") or data.get("access") or data.get("key")
            if not token:
                errors.append("{0}: respuesta sin token: {1!r}".format(endpoint, data))
                continue

            self.auth = AuthResult(scheme=scheme, token=str(token), endpoint=endpoint)
            return self.auth

        raise BioTimeError("No se pudo autenticar. Intentos:\n" + "\n---\n".join(errors))

    def find_employee_by_code(self, emp_code):
        data = self._request("GET", "personnel/api/employees/", params={"emp_code": emp_code})
        rows = self._extract_list(data)
        for row in rows:
            if str(row.get("emp_code", "")) == str(emp_code):
                return row
        if len(rows) == 1:
            return rows[0]
        return None

    def get_employee(self, employee_id):
        data = self._request("GET", "personnel/api/employees/{0}/".format(employee_id))
        if not isinstance(data, dict):
            raise BioTimeError(
                "Respuesta inesperada al obtener empleado {0}: {1!r}".format(employee_id, data)
            )
        if "emp_code" not in data and isinstance(data.get("data"), dict):
            return data["data"]
        return data

    def list_areas(self):
        data = self._request("GET", "personnel/api/areas/")
        return self._extract_list(data)

    @staticmethod
    def _pk(value, field_name):
        """Extrae id entero de un objeto anidado o de un int."""
        if value is None:
            return None
        if isinstance(value, dict) and value.get("id") is not None:
            return int(value["id"])
        if isinstance(value, int):
            return value
        try:
            return int(value)
        except (TypeError, ValueError):
            raise BioTimeError(
                "No se pudo resolver {0} desde {1!r}".format(field_name, value)
            )

    def set_employee_areas(self, employee_id, area_ids, employee=None):
        """
        Actualiza areas del empleado.

        BioTime 8 (probado en 8085) requiere company + department en el mismo PATCH;
        enviar solo {area: [...]} provoca HTTP 500.
        La lista area no puede estar vacia: para bloquear usar el area 'No autorizado'.
        """
        if not area_ids:
            raise BioTimeError(
                "BioTime rechaza area=[]. Usa el id del area 'No autorizado' para desactivar."
            )

        if employee is None:
            employee = self.get_employee(employee_id)

        company_id = self._pk(employee.get("company"), "company")
        department_id = self._pk(employee.get("department"), "department")
        if company_id is None or department_id is None:
            raise BioTimeError(
                "Empleado sin company/department; no se puede actualizar areas de forma segura."
            )

        payload = {
            "company": company_id,
            "department": department_id,
            "area": [int(a) for a in area_ids],
        }

        last_error = None
        # Docs: PATCH; PUT tambien funciona si el body incluye company+department.
        for method in ("PATCH", "PUT"):
            try:
                data = self._request(
                    method,
                    "personnel/api/employees/{0}/".format(employee_id),
                    json=payload,
                )
                if isinstance(data, dict):
                    return data
                return {"result": data}
            except BioTimeError as exc:
                last_error = exc
                if method == "PATCH":
                    continue
                raise
        raise last_error

    @staticmethod
    def _extract_list(data):
        if isinstance(data, list):
            return [x for x in data if isinstance(x, dict)]
        if isinstance(data, dict):
            inner = data.get("data")
            if isinstance(inner, list):
                return [x for x in inner if isinstance(x, dict)]
            if isinstance(inner, dict) and isinstance(inner.get("results"), list):
                return [x for x in inner["results"] if isinstance(x, dict)]
            if isinstance(data.get("results"), list):
                return [x for x in data["results"] if isinstance(x, dict)]
        return []


def require_env(*names):
    missing = [n for n in names if not env(n)]
    if missing:
        raise SystemExit(
            "Faltan variables de entorno: {0}. Copia .env.example a .env".format(
                ", ".join(missing)
            )
        )
    return {n: env(n) for n in names}


def cmd_auth(args):
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    with BioTimeClient(base_url()) as client:
        result = client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        print("OK auth via {0}".format(result.endpoint))
        print("Scheme: {0}".format(result.scheme))
        print("Token (primeros 16): {0}...".format(result.token[:16]))
    return 0


def cmd_find(args):
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS")
    emp_code = args.emp_code or env("BIOTIME_EMP_CODE")
    if not emp_code:
        raise SystemExit("Indica --emp-code o BIOTIME_EMP_CODE")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    with BioTimeClient(base_url()) as client:
        client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        emp = client.find_employee_by_code(str(emp_code))
        if not emp:
            print("No se encontro empleado emp_code={0}".format(emp_code), file=sys.stderr)
            return 1
        print(json.dumps(emp, ensure_ascii=False, indent=2))
    return 0


def _resolve_employee(client, emp_code):
    emp = client.find_employee_by_code(emp_code)
    if not emp or emp.get("id") is None:
        raise BioTimeError("Empleado no encontrado: emp_code={0}".format(emp_code))
    return emp


def cmd_areas(args):
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    with BioTimeClient(base_url()) as client:
        client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        rows = client.list_areas()
        for row in rows:
            print(
                "id={0} code={1} name={2}".format(
                    row.get("id"), row.get("area_code"), row.get("area_name")
                )
            )
        if not rows:
            print("(sin areas)")
    return 0


def cmd_activate(args):
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS")
    emp_code = args.emp_code or env("BIOTIME_EMP_CODE")
    area_id = args.area_id if args.area_id is not None else env("BIOTIME_AREA_ID")
    if not emp_code or area_id is None:
        raise SystemExit("Requiere EMP_CODE y AREA_ID (--emp-code / --area-id o .env)")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    with BioTimeClient(base_url()) as client:
        client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        emp = _resolve_employee(client, str(emp_code))
        emp_id = int(emp["id"])
        result = client.set_employee_areas(emp_id, [int(area_id)], employee=emp)
        print(
            "OK activate: employee_id={0} emp_code={1} area=[{2}]".format(
                emp_id, emp_code, area_id
            )
        )
        print(json.dumps(_summarize_employee(result) or result, ensure_ascii=False, indent=2))
    return 0


def cmd_deactivate(args):
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS")
    emp_code = args.emp_code or env("BIOTIME_EMP_CODE")
    denied_id = (
        args.denied_area_id
        if args.denied_area_id is not None
        else env("BIOTIME_DENIED_AREA_ID", "1")
    )
    if not emp_code:
        raise SystemExit("Requiere EMP_CODE (--emp-code o BIOTIME_EMP_CODE)")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    with BioTimeClient(base_url()) as client:
        client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        emp = _resolve_employee(client, str(emp_code))
        emp_id = int(emp["id"])
        result = client.set_employee_areas(emp_id, [int(denied_id)], employee=emp)
        print(
            "OK deactivate: employee_id={0} emp_code={1} area=[{2}] (no autorizado)".format(
                emp_id, emp_code, denied_id
            )
        )
        print(json.dumps(_summarize_employee(result) or result, ensure_ascii=False, indent=2))
    return 0


def cmd_demo(args):
    """Auth -> find -> activate -> deactivate si --full."""
    creds = require_env("BIOTIME_USER", "BIOTIME_PASS", "BIOTIME_EMP_CODE", "BIOTIME_AREA_ID")
    mode = env("BIOTIME_AUTH_MODE", "auto") or "auto"
    emp_code = creds["BIOTIME_EMP_CODE"]
    area_id = int(creds["BIOTIME_AREA_ID"])
    denied_id = int(env("BIOTIME_DENIED_AREA_ID", "1") or "1")
    with BioTimeClient(base_url()) as client:
        auth = client.login(creds["BIOTIME_USER"], creds["BIOTIME_PASS"], mode=mode)
        print("[1] Auth OK ({0} via {1})".format(auth.scheme, auth.endpoint))

        emp = _resolve_employee(client, emp_code)
        emp_id = int(emp["id"])
        print(
            "[2] Empleado id={0} emp_code={1} areas={2}".format(
                emp_id, emp.get("emp_code"), emp.get("area")
            )
        )

        client.set_employee_areas(emp_id, [area_id], employee=emp)
        after = client.get_employee(emp_id)
        print("[3] Activate area=[{0}] -> areas={1}".format(area_id, after.get("area")))

        if args.full:
            client.set_employee_areas(emp_id, [denied_id], employee=after)
            after2 = client.get_employee(emp_id)
            print(
                "[4] Deactivate area=[{0}] -> areas={1}".format(denied_id, after2.get("area"))
            )
            # Restaurar acceso al final del demo
            client.set_employee_areas(emp_id, [area_id], employee=after2)
            print("[5] Restaurado area=[{0}]".format(area_id))
        else:
            print("[4] Omitido deactivate (usa --full para ciclo completo).")
    return 0


def _summarize_employee(data):
    if not isinstance(data, dict):
        return None
    if "emp_code" in data or "id" in data:
        return {
            "id": data.get("id"),
            "emp_code": data.get("emp_code"),
            "area": data.get("area"),
            "app_status": data.get("app_status"),
        }
    inner = data.get("data")
    if isinstance(inner, dict):
        return _summarize_employee(inner)
    return None


def build_parser():
    p = argparse.ArgumentParser(
        description="PoC BioTime 8.x: auth, buscar empleado, asignar/quitar areas.",
    )
    sub = p.add_subparsers(dest="command")
    sub.required = True

    sub.add_parser("auth", help="Probar autenticacion JWT/Token")

    p_find = sub.add_parser("find", help="Buscar empleado por emp_code")
    p_find.add_argument("--emp-code", default=None)

    sub.add_parser("areas", help="Listar areas BioTime (id / code / name)")

    p_act = sub.add_parser("activate", help="Asignar AREA_ID (autorizado) al empleado")
    p_act.add_argument("--emp-code", default=None)
    p_act.add_argument("--area-id", type=int, default=None)

    p_deact = sub.add_parser(
        "deactivate",
        help="Mover al area 'No autorizado' (DENIED_AREA_ID; no usa lista vacia)",
    )
    p_deact.add_argument("--emp-code", default=None)
    p_deact.add_argument("--denied-area-id", type=int, default=None)

    p_demo = sub.add_parser("demo", help="Auth + find + activate (+ deactivate con --full)")
    p_demo.add_argument(
        "--full",
        action="store_true",
        help="Ciclo activate -> deactivate -> restaurar.",
    )

    return p


def main(argv=None):
    script_dir = os.path.dirname(os.path.abspath(__file__))
    load_dotenv(os.path.join(script_dir, ".env"))
    load_dotenv(".env")

    parser = build_parser()
    args = parser.parse_args(argv)
    if not args.command:
        parser.print_help()
        return 1

    handlers = {
        "auth": cmd_auth,
        "find": cmd_find,
        "areas": cmd_areas,
        "activate": cmd_activate,
        "deactivate": cmd_deactivate,
        "demo": cmd_demo,
    }
    try:
        return handlers[args.command](args)
    except BioTimeError as exc:
        print("ERROR BioTime:\n{0}".format(exc), file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
