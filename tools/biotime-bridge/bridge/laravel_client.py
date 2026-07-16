# -*- coding: utf-8 -*-
from __future__ import print_function

import json
import logging
import sys
import time

import requests

logger = logging.getLogger(__name__)

DEFAULT_USER_AGENT = "BioTimeBridge/0.1 (+gimnasio)"
NETWORK_HINT = (
    "Hint: probar curl.exe hacia la misma URL; si curl OK y bridge FAIL, "
    "usar Python 3.10+ (no el Python embebido de ZKBioTime)."
)


class LaravelError(Exception):
    def __init__(self, message, status=None, body=None):
        super(LaravelError, self).__init__(message)
        self.status = status
        self.body = body


class LaravelClient(object):
    """Cliente HTTPS hacia Laravel Banahosting (Bearer token por sede)."""

    def __init__(
        self,
        base_url,
        token,
        timeout=30.0,
        max_retries=3,
        backoff=2.0,
        verify_ssl=True,
        user_agent=None,
    ):
        self.base = base_url.rstrip("/")
        self.token = token
        self._timeout = timeout
        self.max_retries = max_retries
        self.backoff = backoff
        self.verify_ssl = verify_ssl
        self.user_agent = (user_agent or DEFAULT_USER_AGENT).strip() or DEFAULT_USER_AGENT
        self._session = requests.Session()
        if not verify_ssl:
            # Local HTTPS con cert auto-firmado (Herd/Valet). No usar en produccion.
            try:
                from urllib3.exceptions import InsecureRequestWarning

                requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
            except Exception:
                pass
            logger.warning("Laravel SSL verify DESACTIVADO (solo desarrollo local)")

    def close(self):
        self._session.close()

    def __enter__(self):
        return self

    def __exit__(self, *args):
        self.close()

    def health_url(self):
        return self.base + "/api/biotime/health"

    def _headers(self):
        return {
            "Authorization": "Bearer {0}".format(self.token),
            "Accept": "application/json",
            "Content-Type": "application/json",
            "User-Agent": self.user_agent,
        }

    def _request(self, method, path, **kwargs):
        url = self.base + path
        last_exc = None
        for attempt in range(1, self.max_retries + 1):
            try:
                resp = self._session.request(
                    method,
                    url,
                    headers=self._headers(),
                    timeout=self._timeout,
                    verify=self.verify_ssl,
                    **kwargs
                )
            except requests.RequestException as exc:
                last_exc = LaravelError(
                    "Red Laravel [{0} {1}]: {2}".format(method, url, exc)
                )
                logger.error(
                    "Laravel red FAIL attempt=%s/%s method=%s url=%s verify_ssl=%s py=%s: %s",
                    attempt,
                    self.max_retries,
                    method,
                    url,
                    self.verify_ssl,
                    sys.version.split()[0],
                    exc,
                )
                if attempt >= self.max_retries:
                    logger.error(NETWORK_HINT)
                self._sleep(attempt)
                continue

            if resp.status_code >= 500:
                last_exc = LaravelError(self._err(resp), status=resp.status_code, body=resp.text)
                self._sleep(attempt)
                continue

            if resp.status_code >= 400:
                raise LaravelError(self._err(resp), status=resp.status_code, body=resp.text)

            if resp.status_code == 204 or not resp.content:
                return None
            try:
                return resp.json()
            except Exception:
                return {"raw": resp.text}

        raise last_exc or LaravelError("Laravel request fallido")

    def _sleep(self, attempt):
        delay = self.backoff * (2 ** (attempt - 1))
        logger.warning("Retry Laravel en %.1fs (intento %s)", delay, attempt)
        time.sleep(delay)

    @staticmethod
    def _err(resp):
        try:
            payload = resp.json()
            detail = json.dumps(payload, ensure_ascii=False)
        except Exception:
            detail = (resp.text or "")[:1500]
        return "HTTP {0} {1} {2}: {3}".format(
            resp.status_code, resp.request.method, resp.url, detail
        )

    def health(self):
        return self._request("GET", "/api/biotime/health")

    def get_commands(self, limit=100):
        data = self._request("GET", "/api/biotime/commands", params={"limit": limit})
        if isinstance(data, dict) and isinstance(data.get("data"), list):
            return data["data"]
        return []

    def ack(self, command_id, status, error=None):
        body = {"status": status}
        if error:
            body["error"] = str(error)[:2000]
        return self._request("POST", "/api/biotime/commands/{0}/ack".format(command_id), json=body)

    def roster(self):
        data = self._request("GET", "/api/biotime/roster")
        if isinstance(data, dict) and isinstance(data.get("data"), list):
            return data["data"]
        return []

    def sync(self, entity, records, timestamp=None):
        if timestamp is None:
            from datetime import datetime

            timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        payload = {
            "entity": entity,
            "timestamp": timestamp,
            "data": records,
        }
        return self._request("POST", "/api/biotime/sync", json=payload)
