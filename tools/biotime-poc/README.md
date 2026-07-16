# PoC BioTime 8.x (paso 0.1)

Script mínimo para validar en la sede piloto:

1. Autenticación API (`/jwt-api-token-auth/` y/o `/api-token-auth/`)
2. Búsqueda de empleado por `emp_code`
3. Asignar área (acceso) y quitar áreas (bloqueo sin borrar biometría)

No habla con Laravel. Sin WebSockets. Solo HTTP contra BioTime local (típicamente `:8090`).

Relacionado: [ADR puente](../../docs/architecture/adr-biotime-puente-acceso.md) · [Plan 08](../../docs/plans/08-biotime-integracion-plan.md)

---

## Requisitos

- Python **3.7+** (3.10+ recomendado)
- BioTime 8.x en marcha (ej. `http://127.0.0.1:8090`)
- Usuario/contraseña con permiso API
- Un empleado de prueba y el ID numérico del área de la sede

## Instalación

```bash
cd tools/biotime-poc
python -m venv .venv

# Windows
.venv\Scripts\activate

# Linux/macOS
# source .venv/bin/activate

pip install -r requirements.txt
copy .env.example .env
# Editar .env con credenciales reales
```

Dependencia: `requests` (HTTP).
## Variables de entorno

| Variable | Descripción | Ejemplo |
| --- | --- | --- |
| `BIOTIME_URL` | Base URL del servidor BioTime | `http://127.0.0.1:8090` |
| `BIOTIME_USER` | Usuario API | `admin` |
| `BIOTIME_PASS` | Contraseña | `***` |
| `BIOTIME_EMP_CODE` | Código del empleado (en producción = `cliente.id`) | `4` |
| `BIOTIME_AREA_ID` | ID del área **autorizada** (ej. "autorizado") | `2` |
| `BIOTIME_DENIED_AREA_ID` | ID del área **denegada** (ej. "No autorizado") | `1` |
| `BIOTIME_AUTH_MODE` | `auto` (default), `jwt` o `token` | `auto` |

También puedes pasar `--emp-code` / `--area-id` en la CLI (tienen prioridad sobre el `.env` donde aplica).

Documentación interactiva en el propio BioTime: `http://<host>:8090/api/docs/` y `/api/personnel_docs/`.

---

## Comandos

Desde `tools/biotime-poc/` con el venv activo y `.env` cargado:

### Probar autenticación

```bash
python main.py auth
```

### Buscar empleado

```bash
python main.py find --emp-code 1
```

### Listar áreas (importante)

```bash
python main.py areas
```

En tu instalación típica:

| id | Nombre | Uso |
| --- | --- | --- |
| 1 | No autorizado | `deactivate` / `BIOTIME_DENIED_AREA_ID` |
| 2 | autorizado | `activate` / `BIOTIME_AREA_ID` |

**No uses el id 1 como `BIOTIME_AREA_ID`.**

### Activar acceso (asignar área autorizada)

```bash
python main.py activate --emp-code 4 --area-id 2
```

### Desactivar acceso (mover a "No autorizado")

BioTime **rechaza** `area: []` (`"Esta lista no puede estar vacía"`). El PoC mueve al área denegada:

```bash
python main.py deactivate --emp-code 4
# o
python main.py deactivate --emp-code 4 --denied-area-id 1
```

### Por qué fallaba el HTTP 500

Enviar solo `{"area": [1]}` (o cualquier PATCH parcial sin compañía) provoca **500 genérico** en BioTime 8.

El update correcto incluye PK de compañía y departamento del empleado:

```json
{ "company": 1, "department": 1, "area": [2] }
```

El PoC ya construye ese body automáticamente.
### Demo rápida

Solo asigna área:

```bash
python main.py demo
```

Ciclo completo (asignar y luego quitar):

```bash
python main.py demo --full
```

---

## Errores HTTP

Si BioTime responde 4xx/5xx, el script imprime método, URL, código y cuerpo JSON (o texto truncado), por ejemplo:

```text
ERROR BioTime:
HTTP 401 POST http://127.0.0.1:8090/jwt-api-token-auth/
{ ... }
```

Si `auto` falla en JWT, intenta `api-token-auth/`. Fija `BIOTIME_AUTH_MODE=jwt` o `token` si ya sabes cuál usa tu instalación.

---

## Checklist de prueba en dispositivo

Usar un empleado de prueba (no un cliente real en horario pico).

| # | Acción | Resultado esperado | OK |
| --- | --- | --- | --- |
| 1 | `python main.py auth` | Token OK | ☐ |
| 2 | `python main.py find --emp-code …` | Ves `id`, `emp_code`, `area` | ☐ |
| 3 | Empleado tiene huella/cara enrollada en BioTime | Biometría ya cargada | ☐ |
| 4 | `python main.py activate --emp-code … --area-id …` | `area` incluye el área de la sede | ☐ |
| 5 | Sincronizar usuario al terminal (UI BioTime o espera sync automática) | Persona en dispositivo | ☐ |
| 6 | Marcar en lector (huella/facial) | **Autorizado / pasa** | ☐ |
| 7 | `python main.py deactivate --emp-code …` | `area` vacío o solo “Not Authorized” | ☐ |
| 8 | Forzar sync al terminal si no baja solo | Dispositivo actualizado | ☐ |
| 9 | Intentar marcar de nuevo | **Denegado / no autoriza** | ☐ |
| 10 | `python main.py activate …` otra vez | Vuelve a autorizar | ☐ |

### Notas si el dispositivo no cambia tras el API

- En muchas instalaciones hay que **empujar** el usuario al device desde BioTime (Device → sync / data transfer).
- Confirma que `BIOTIME_AREA_ID` es el área que tiene el terminal (no el área “Not Authorized”).
- Anota aquí el procedimiento exacto de tu sede: _______________________________

**Sede piloto:** _______________ **Fecha:** _______________ **Operador:** _______________

---

## Criterios de aceptación (paso 0.1)

- [x] Token JWT o Token obtenido contra `:8090`
- [x] Empleado con área asignada y luego con `area: []` vía CLI
- [ ] Checklist dispositivo firmado en sede piloto (pendiente ops)
