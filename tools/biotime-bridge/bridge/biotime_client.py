# -*- coding: utf-8 -*-
from __future__ import print_function

import json
import logging
from collections import namedtuple

import requests

logger = logging.getLogger(__name__)

AuthResult = namedtuple("AuthResult", ["scheme", "token", "endpoint"])


class BioTimeError(Exception):
    def __init__(self, message, status=None, body=None):
        super(BioTimeError, self).__init__(message)
        self.status = status
        self.body = body


def _format_http_error(resp):
    try:
        payload = resp.json()
        detail = json.dumps(payload, ensure_ascii=False, indent=2)
    except Exception:
        detail = (resp.text or "")[:2000]
    return "HTTP {0} {1} {2}\n{3}".format(
        resp.status_code, resp.request.method, resp.url, detail
    )


class BioTimeClient(object):
    def __init__(self, base_url, timeout=30.0):
        self.url = base_url.rstrip("/") + "/"
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
        url = self.url + path.lstrip("/")
        try:
            resp = self._session.request(
                method, url, headers=self._headers(), timeout=self._timeout, **kwargs
            )
        except requests.RequestException as exc:
            raise BioTimeError("Error de red BioTime: {0}".format(exc))

        if resp.status_code >= 400:
            raise BioTimeError(_format_http_error(resp), status=resp.status_code, body=resp.text)

        if resp.status_code == 204 or not resp.content:
            return None
        try:
            return resp.json()
        except Exception:
            return {"raw": resp.text}

    def login(self, username, password, mode="auto"):
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
                errors.append("{0}: sin token {1!r}".format(endpoint, data))
                continue
            self.auth = AuthResult(scheme=scheme, token=str(token), endpoint=endpoint)
            logger.info("BioTime auth OK via %s (%s)", endpoint, scheme)
            return self.auth

        raise BioTimeError("Auth BioTime fallo:\n" + "\n---\n".join(errors))

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
        if isinstance(data, dict) and "emp_code" not in data and isinstance(data.get("data"), dict):
            return data["data"]
        return data

    def list_employees(self, page=1, page_size=100):
        """Lista empleados. Retorna (rows, meta) donde meta tiene count/next si vienen en la respuesta."""
        data = self._request(
            "GET",
            "personnel/api/employees/",
            params={"page": page, "page_size": page_size},
        )
        rows = self._extract_list(data)
        meta = self._extract_list_meta(data)
        return rows, meta

    def count_employees(self, page_size=200):
        """
        Cuenta empleados usando el campo count del envelope documentado.
        Si no hay count, pagina siguiendo next.
        """
        rows, meta = self.list_employees(page=1, page_size=page_size)
        if meta.get("count") is not None:
            try:
                return int(meta["count"])
            except (TypeError, ValueError):
                pass

        total = len(rows)
        page = 2
        next_url = meta.get("next")
        while next_url or (len(rows) >= page_size and page <= 100):
            if page > 100:
                break
            rows, meta = self.list_employees(page=page, page_size=page_size)
            total += len(rows)
            next_url = meta.get("next")
            if not next_url and len(rows) < page_size:
                break
            page += 1
        return total

    def list_terminals(self, page=1, page_size=100):
        """GET /iclock/api/terminals/ → (rows, meta)."""
        data = self._request(
            "GET",
            "iclock/api/terminals/",
            params={"page": page, "page_size": page_size},
        )
        return self._extract_list(data), self._extract_list_meta(data)

    def list_all_terminals(self, page_size=100):
        rows, meta = self.list_terminals(page=1, page_size=page_size)
        all_rows = list(rows)
        page = 2
        while meta.get("next") or (len(rows) >= page_size and page <= 50):
            if page > 50:
                break
            rows, meta = self.list_terminals(page=page, page_size=page_size)
            if not rows:
                break
            all_rows.extend(rows)
            if not meta.get("next") and len(rows) < page_size:
                break
            page += 1
        return all_rows

    def list_transactions(self, page=1, page_size=100, start_time=None, end_time=None, terminal_sn=None):
        """GET /iclock/api/transactions/ → (rows, meta)."""
        params = {"page": page, "page_size": page_size}
        if start_time:
            params["start_time"] = start_time
        if end_time:
            params["end_time"] = end_time
        if terminal_sn:
            params["terminal_sn"] = terminal_sn
        data = self._request("GET", "iclock/api/transactions/", params=params)
        return self._extract_list(data), self._extract_list_meta(data)

    def list_transactions_window(self, start_time, end_time=None, page_size=200):
        """Todas las transacciones en la ventana start_time..end_time."""
        rows, meta = self.list_transactions(
            page=1, page_size=page_size, start_time=start_time, end_time=end_time
        )
        all_rows = list(rows)
        page = 2
        while meta.get("next") or (len(rows) >= page_size and page <= 100):
            if page > 100:
                break
            rows, meta = self.list_transactions(
                page=page,
                page_size=page_size,
                start_time=start_time,
                end_time=end_time,
            )
            if not rows:
                break
            all_rows.extend(rows)
            if not meta.get("next") and len(rows) < page_size:
                break
            page += 1
        return all_rows

    def create_employee(self, emp_code, first_name, last_name, department_id, area_ids, company_id=None):
        """
        POST /personnel/api/employees/
        Docs: emp_code, department, area. En BioTime 8 real, company debe coincidir
        con el department ("Mismatched company pk and deparment pk" si falta o no cuadra).
        """
        if not area_ids:
            raise BioTimeError("area=[] rechazado por BioTime; usa denied_area_id")
        if company_id is None or int(company_id) <= 0:
            raise BioTimeError(
                "company_id requerido al crear (debe coincidir con el department en BioTime)"
            )

        payload = {
            "emp_code": str(emp_code),
            "company": int(company_id),
            "department": int(department_id),
            "area": [int(a) for a in area_ids],
        }
        if first_name:
            payload["first_name"] = first_name
        elif emp_code:
            payload["first_name"] = str(emp_code)
        if last_name:
            payload["last_name"] = last_name

        data = self._request("POST", "personnel/api/employees/", json=payload)
        if isinstance(data, dict) and "emp_code" not in data and isinstance(data.get("data"), dict):
            return data["data"]
        return data if isinstance(data, dict) else {"result": data}

    def delete_employee(self, employee_id):
        self._request("DELETE", "personnel/api/employees/{0}/".format(employee_id))
        return True

    def employee_area_ids(self, employee):
        """Normaliza area del empleado a lista de ints."""
        if not isinstance(employee, dict):
            return []
        raw = employee.get("area")
        if raw is None:
            return []
        if isinstance(raw, list):
            ids = []
            for item in raw:
                if isinstance(item, dict) and item.get("id") is not None:
                    ids.append(int(item["id"]))
                else:
                    try:
                        ids.append(int(item))
                    except (TypeError, ValueError):
                        pass
            return ids
        try:
            return [int(raw)]
        except (TypeError, ValueError):
            return []

    @staticmethod
    def _pk(value, field_name):
        if value is None:
            return None
        if isinstance(value, dict) and value.get("id") is not None:
            return int(value["id"])
        try:
            return int(value)
        except (TypeError, ValueError):
            raise BioTimeError("No se pudo resolver {0} desde {1!r}".format(field_name, value))

    def adjust_employee_areas(self, employee_ids, area_ids):
        """
        POST /personnel/api/employees/adjust_area/
        Body: { employees: [id…], areas: [id…] }
        """
        if not area_ids:
            raise BioTimeError("areas=[] rechazado; usa denied_area_id")
        if not employee_ids:
            raise BioTimeError("employees=[] requerido por adjust_area")

        payload = {
            "employees": [int(x) for x in employee_ids],
            "areas": [int(a) for a in area_ids],
        }
        return self._request("POST", "personnel/api/employees/adjust_area/", json=payload)

    def resync_employees_to_device(self, employee_ids):
        """POST /personnel/api/employees/resync_to_device/"""
        if not employee_ids:
            return None
        payload = {"employees": [int(x) for x in employee_ids]}
        return self._request("POST", "personnel/api/employees/resync_to_device/", json=payload)

    def set_employee_areas(self, employee_id, area_ids, employee=None):
        """
        Cambia areas del empleado.
        1) POST adjust_area (docs) — en algunas instalaciones BioTime 8 responde 500.
        2) Fallback PATCH/PUT con company + department + area (requerido en runtime;
           sin company → "Mismatched company pk and deparment pk").
        La lista area no puede estar vacia: usar denied_area_id para bloquear.
        """
        if not area_ids:
            raise BioTimeError("area=[] rechazado por BioTime; usa denied_area_id")

        emp_id = int(employee_id)
        areas = [int(a) for a in area_ids]

        try:
            data = self.adjust_employee_areas([emp_id], areas)
            logger.info("adjust_area OK employee_id=%s areas=%s", emp_id, areas)
            return data if isinstance(data, dict) else {"result": data}
        except BioTimeError as adj_exc:
            logger.warning(
                "adjust_area fallo employee_id=%s; fallback PATCH/PUT: %s",
                emp_id,
                adj_exc,
            )

        if employee is None:
            employee = self.get_employee(emp_id)
        if not isinstance(employee, dict):
            raise BioTimeError("Empleado invalido para fallback de areas")

        emp_code = employee.get("emp_code")
        company_id = self._pk(employee.get("company"), "company")
        department_id = self._pk(employee.get("department"), "department")
        if company_id is None or department_id is None:
            raise BioTimeError(
                "Empleado sin company/department; no se puede actualizar areas "
                "(adjust_area previo fallo; ver log)"
            )

        # BioTime 8 exige company + department en el mismo body al actualizar areas.
        payload = {
            "company": company_id,
            "department": department_id,
            "area": areas,
        }
        if emp_code:
            payload["emp_code"] = str(emp_code)

        last_error = None
        for method in ("PATCH", "PUT"):
            try:
                data = self._request(
                    method,
                    "personnel/api/employees/{0}/".format(emp_id),
                    json=payload,
                )
                logger.info(
                    "%s employee areas OK employee_id=%s areas=%s",
                    method,
                    emp_id,
                    areas,
                )
                return data if isinstance(data, dict) else {"result": data}
            except BioTimeError as exc:
                last_error = exc
                if method == "PATCH":
                    logger.warning(
                        "PATCH areas fallo employee_id=%s; intento PUT: %s",
                        emp_id,
                        exc,
                    )
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

    @staticmethod
    def _extract_list_meta(data):
        meta = {"count": None, "next": None, "previous": None}
        if not isinstance(data, dict):
            return meta
        for key in ("count", "next", "previous"):
            if key in data:
                meta[key] = data.get(key)
        return meta
