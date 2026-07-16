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
        data = self._request(
            "GET",
            "personnel/api/employees/",
            params={"page": page, "page_size": page_size},
        )
        return self._extract_list(data)

    def count_employees(self, page_size=200):
        """Cuenta empleados paginando. Retorna total aproximado."""
        total = 0
        page = 1
        while True:
            rows = self.list_employees(page=page, page_size=page_size)
            total += len(rows)
            if len(rows) < page_size:
                break
            page += 1
            if page > 100:
                break
        return total

    def create_employee(self, emp_code, first_name, last_name, company_id, department_id, area_ids):
        payload = {
            "emp_code": str(emp_code),
            "first_name": first_name or emp_code,
            "last_name": last_name or "",
            "company": int(company_id),
            "department": int(department_id),
            "area": [int(a) for a in area_ids],
        }
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

    def set_employee_areas(self, employee_id, area_ids, employee=None):
        """
        BioTime 8 requiere company + department en el PATCH.
        La lista area no puede estar vacia: usar denied_area_id para bloquear.
        """
        if not area_ids:
            raise BioTimeError("area=[] rechazado por BioTime; usa denied_area_id")

        if employee is None:
            employee = self.get_employee(employee_id)

        company_id = self._pk(employee.get("company"), "company")
        department_id = self._pk(employee.get("department"), "department")
        if company_id is None or department_id is None:
            raise BioTimeError("Empleado sin company/department")

        payload = {
            "company": company_id,
            "department": department_id,
            "area": [int(a) for a in area_ids],
        }

        last_error = None
        for method in ("PATCH", "PUT"):
            try:
                data = self._request(
                    method,
                    "personnel/api/employees/{0}/".format(employee_id),
                    json=payload,
                )
                return data if isinstance(data, dict) else {"result": data}
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
