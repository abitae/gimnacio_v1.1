# Puente BioTime ↔ Laravel

Aplicación Python que corre **en el mismo PC que BioTime** (sede). Habla HTTPS hacia Laravel (Banahosting) y HTTP local hacia BioTime 8.x.

No usa WebSockets. El puente **siempre inicia** las conexiones salientes.

## Ejecutable Windows (`BioTimeBridge.exe`)

Para sedes **sin instalar Python**, compila un `.exe` autocontenido (PyInstaller):

```bat
cd tools\biotime-bridge
set PYTHONHOME=
set PYTHONPATH=
scripts\build-exe.bat
```

Salida:

| Archivo | Uso |
| --- | --- |
| `BioTimeBridge.exe` | Ejecutable principal (copia en la raíz del bridge) |
| `dist\BioTimeBridge.exe` | Mismo binario generado por PyInstaller |
| `dist\config.yaml.example` | Plantilla de configuración |

### Despliegue en sede (solo .exe)

1. Copia a una carpeta local (ej. `C:\BioTimeBridge\`):
   - `BioTimeBridge.exe`
   - `config.yaml.example` → renómbralo a `config.yaml` y completa token/URLs  
     (`config.yaml` debe quedar **en la misma carpeta** que el `.exe`)
2. **Doble clic** en `BioTimeBridge.exe` abre la GUI (sin argumentos = `gui`).
3. Verifica desde la GUI (botón Doctor) o, en desarrollo:
   ```bat
   python -m bridge --config config.yaml doctor
   ```
4. Segundo plano / Task Scheduler: `start-background.bat` o `scripts\install-task-continuous.ps1` (detectan el `.exe` automáticamente).

Los `.bat` (`bridge.bat`, `start-gui.bat`, …) **prefieren** `BioTimeBridge.exe` si existe; si no, usan el venv de Python.

> Compila en un PC con **Python 3.10+** de python.org (no el Python 3.7 de ZKBioTime). El `.exe` resultante no necesita Python en la sede.

---

## Interfaz gráfica

Ventana tkinter (sin dependencias extra) para operación en sede:

```bat
python -m bridge --config config.yaml gui
```

En sede: **doble clic** en `BioTimeBridge.exe` (misma carpeta que `config.yaml`).  
En desarrollo: doble clic en `start-gui.bat` (usa el `.exe` si existe; si no, el `.venv`).

| Acción | Qué hace |
| --- | --- |
| Doctor | Valida Laravel health + login BioTime |
| Una vez | Un ciclo `once` (commands / roster / sync según config) |
| Iniciar / Detener | Loop `run` en hilo; stop cooperativo |
| Segundo plano | Inicia (si hace falta) y **oculta la ventana**; el poll sigue activo |
| Mostrar ventana | Restaura la GUI si estaba minimizada |
| Cerrar (X) | Si está corriendo: pregunta minimizar vs detener |
| Configuración | Editar y **guardar** `config.yaml` (URLs, token, áreas, tiempos) |
| Pruebas | Crear / buscar / cambiar área / eliminar empleados **directo en BioTime** (sin Laravel) |
| Log | Vista en vivo + recargar `logs/biotime-bridge.log` |

### Segundo plano (sin GUI)

```bat
REM Sin consola (pythonw):
start-background.bat

REM O Task Scheduler continuo (recomendado en producción):
powershell -ExecutionPolicy Bypass -File scripts\install-task-continuous.ps1
```

Pestaña **Configuración**: cambia valores y pulsa **Guardar config.yaml**. Token/password ocultos por defecto (checkbox para mostrar). No se puede guardar mientras el loop está en marcha.

---

## Puesta en marcha rápida (sede)

> **Python:** usa **Python 3.10+** de [python.org](https://www.python.org/downloads/windows/) (instalador Windows, marca *“Add python.exe to PATH”*).  
> **No uses** el Python embebido de ZKBioTime (`C:\ZKBioTime\Python37`) contra hosting HTTPS: suele fallar con `ConnectionResetError 10054` / TLS antiguo.  
> Si `py` no existe en PowerShell, es normal: usa el `python.exe` del instalador de python.org.
>
> **Crítico — `PYTHONHOME`:** BioTime suele dejar `PYTHONHOME=C:\ZKBioTime\Python37` en el entorno de Windows. Eso hace que **cualquier** `python` (incluso 3.13) cargue las libs de BioTime y falle con `SRE module mismatch`. Antes de crear el venv o correr el bridge:
> ```bat
> set PYTHONHOME=
> set PYTHONPATH=
> ```
> O usa `start-gui.bat` / `bridge.bat` (ya limpian esas variables).

1. **Copiar config**
   ```bat
   cd tools\biotime-bridge
   set PYTHONHOME=
   set PYTHONPATH=

   REM Tras instalar Python 3.12/3.13 desde python.org:
   "C:\Program Files\Python313\python.exe" --version
   REM Debe mostrar 3.10+ (NO 3.7.7 de ZKBioTime)

   "C:\Program Files\Python313\python.exe" -m venv .venv
   .venv\Scripts\activate
   python -m pip install -U pip
   pip install -r requirements.txt
   copy config.yaml.example config.yaml
   ```

   CLI rápida (sin activar venv):
   ```bat
   set PYTHONHOME=
   bridge.bat doctor
   bridge.bat gui
   ```
2. **Editar `config.yaml`**
   - `laravel_base_url` — ej. `https://tudominio.com`
   - `biotime_base_url` / user / password locales
   - `area_id` (autorizada, ej. `2`) y `denied_area_id` (No autorizado, ej. `1`)
3. **Regenerar token en Laravel (por sede)**  
   UI → **BioTime → Sedes** → sede correspondiente → **Regenerar** → **Mostrar / Copiar** → pegar en `laravel_token`.
4. **Doctor**
   ```bat
   python -m bridge --config config.yaml doctor
   ```
5. **Primer poll / heartbeat**  
   Arranca el puente (`run` o tarea programada). En Laravel, **BioTime → Sedes**, confirma que `last_heartbeat_at` se actualiza (health/ack del puente).
6. Registrar tarea Windows (abajo) o NSSM.

> BioTime 8 **rechaza** `area: []`. El deactivate asigna `denied_area_id` (conserva biometría).
> El `activate` con `ensure_create` **crea** el empleado si no existe (requiere `company_id` + `department_id` alineados en BioTime; Create falla con *Mismatched company pk…* si no coinciden).
> Cambio de área: `POST …/adjust_area/` (fallback `PUT` del empleado). Tras create/área: `resync_to_device` si `resync_after_area: true`.
> La reconciliación de capacidad nunca usa `delete`: mueve primero a los clientes desplazados al área denegada y conserva sus biométricos en BioTime.
> Los empleados ajenos a Laravel se consideran protegidos, consumen cupo y no se eliminan automáticamente.
> La configuración operativa de áreas, compañía, departamento, límite y versión se obtiene de Laravel por sucursal.

## URLs Laravel (API)

Auth: `Authorization: Bearer <token_sede>` (o `X-BioTime-Secret`).

| Método | Ruta | Uso |
| --- | --- | --- |
| `GET` | `/api/biotime/health` | Salud + heartbeat de la sede del token |
| `GET` | `/api/biotime/config` | Configuración vigente y estado de cada reloj |
| `GET` | `/api/biotime/commands?limit=100` | Comandos pending → processing |
| `POST` | `/api/biotime/commands/{id}/ack` | `{ "status": "acked"\|"failed", "error": "..." }` |
| `GET` | `/api/biotime/roster` | Selección completa: selected / waiting / denied |
| `POST` | `/api/biotime/heartbeat` | Inventario, capacidad y códigos por reloj |
| `POST` | `/api/biotime/sync` | Push (`employees`, …) |

`emp_code` = `cliente.codigo` Laravel (no el id interno).

## Comandos CLI

`--config` va **antes** del subcomando. Sin subcomando (doble clic en el `.exe`) abre la GUI.

```bat
python -m bridge --config config.yaml
python -m bridge --config config.yaml gui
python -m bridge --config config.yaml doctor
python -m bridge --config config.yaml once
python -m bridge --config config.yaml run
python -m bridge --config config.yaml roster
python -m bridge --config config.yaml sync-employees
python -m bridge --config config.yaml sync-devices
python -m bridge --config config.yaml sync-transactions
```

| Comando | Qué hace | Exit code |
| --- | --- | --- |
| *(ninguno)* / `gui` | Interfaz gráfica (tkinter). Default al doble clic del `.exe` | |
| `doctor` | Laravel `/api/biotime/health` (valida token sede) + login BioTime | `0` OK / `1` FAIL |
| `once` | Un ciclo de commands (+ roster/sync si están activos en config) | |
| `run` | Loop continuo (producción) | |
| `roster` | Solo reconcile roster | |
| `sync-employees` | Push employees a Laravel | |
| `sync-devices` / `sync-catalog` | Push areas + departments + terminals | |
| `sync-transactions` | Push marcaciones (`iclock/api/transactions`) | |

## Employee / Terminal / Transaction API BioTime

Docs: `http://<biotime>:8085/docs/api-docs/` (`employee_api.html`, `terminal_api.html`, `transaction_api.html`).

| Operación | Método / ruta | Uso en el puente |
| --- | --- | --- |
| List employees | `GET /personnel/api/employees/` | Sync employees + commands |
| Create employee | `POST /personnel/api/employees/` | `ensure_create` |
| Adjust area | `POST …/adjust_area/` (fallback PATCH/PUT) | Activate / deactivate |
| List terminals | `GET /iclock/api/terminals/` | Sync `devices` → Laravel |
| List transactions | `GET /iclock/api/transactions/` | Sync `transactions` → asistencia |

**Asistencia Laravel:** la dirección entrada/salida la define el **rol del terminal** en UI BioTime (Entrada / Salida / Ambos), no el `punch_state` del dispositivo. Terminal sin rol: se guarda la transacción pero no crea asistencia. Create employee requiere `company`+`department` coherentes en BioTime 8.

Config bridge:

```yaml
devices_push_seconds: 300
transactions_push_seconds: 60
transactions_lookback_minutes: 15
```

Variable opcional: `BIOTIME_BRIDGE_CONFIG=C:\ruta\config.yaml`

### dry_run

`dry_run: true` en config: no escribe áreas en BioTime; sí puede ACK commands para no atascar la cola.

### laravel_verify_ssl

Por defecto `true`. En desarrollo local con HTTPS y certificado auto-firmado (`*.test`), usa:

```yaml
laravel_verify_ssl: false
```

En Banahosting / producción deja `true` (o omite la clave).

### laravel_user_agent

Por defecto `BioTimeBridge/0.1 (+gimnasio)`. Si el WAF del hosting bloquea ese UA, cámbialo en config o en la GUI (pestaña Configuración).

## Troubleshooting: Connection reset / 10054 hacia Laravel

Si `doctor` muestra BioTime OK pero Laravel FAIL con `ConnectionResetError (10054)`:

1. El endpoint en hosting suele estar bien (401 sin token = ruta existe).
2. Desde **el mismo PC Windows**, prueba:

```powershell
curl.exe -v https://TU_DOMINIO/api/biotime/health

curl.exe -v -H "Authorization: Bearer bt_..." -H "Accept: application/json" https://TU_DOMINIO/api/biotime/health

curl.exe -v -A "Mozilla/5.0" -H "Authorization: Bearer bt_..." https://TU_DOMINIO/api/biotime/health
```

| Resultado | Acción |
| --- | --- |
| curl OK (401/200), bridge FAIL | Recrear `.venv` con **Python 3.10+** (no ZKBioTime Python37) |
| curl también reset | Red, firewall, antivirus o WAF del hosting / allowlist IP |
| curl 401 → 200 con token | Hosting OK; revisa token en config |

El cliente envía `User-Agent` fijo y en errores de red loguea la URL completa + hint de Python.

## Logging

`logs/biotime-bridge.log` (rotación 5 MB × 5) + consola.

## Windows: scripts incluidos

Carpeta [`scripts/`](scripts/):

| Script | Modo |
| --- | --- |
| `install-task-once.ps1` | Task Scheduler **cada 1 minuto** → `once` |
| `install-task-continuous.ps1` | Task Scheduler **run continuo** + restart on failure (logon/startup) |
| `uninstall-task.ps1` | Elimina la tarea |

Ejecutar PowerShell **como administrador** (recomendado):

```powershell
cd tools\biotime-bridge\scripts

# A) Cada minuto
.\install-task-once.ps1

# B) Proceso continuo con reinicio
.\install-task-continuous.ps1

# Quitar
.\uninstall-task.ps1 -TaskName BioTimeBridgeOnce
.\uninstall-task.ps1 -TaskName BioTimeBridgeRun
```

Ver estado:

```powershell
Get-ScheduledTask -TaskName BioTimeBridgeRun | Get-ScheduledTaskInfo
```

### NSSM (servicio 24/7 sin login de usuario)

Con ejecutable:

```bat
nssm install BioTimeBridge "C:\ruta\BioTimeBridge\BioTimeBridge.exe"
nssm set BioTimeBridge AppDirectory "C:\ruta\BioTimeBridge"
nssm set BioTimeBridge AppParameters "--config config.yaml run"
nssm set BioTimeBridge AppStdout "C:\ruta\BioTimeBridge\logs\stdout.log"
nssm set BioTimeBridge AppStderr "C:\ruta\BioTimeBridge\logs\stderr.log"
nssm set BioTimeBridge AppRestartDelay 5000
nssm start BioTimeBridge
```

Con Python/venv (desarrollo):

```bat
nssm install BioTimeBridge "C:\ruta\tools\biotime-bridge\.venv\Scripts\python.exe"
nssm set BioTimeBridge AppDirectory "C:\ruta\tools\biotime-bridge"
nssm set BioTimeBridge AppParameters "-m bridge --config config.yaml run"
nssm set BioTimeBridge AppStdout "C:\ruta\tools\biotime-bridge\logs\stdout.log"
nssm set BioTimeBridge AppStderr "C:\ruta\tools\biotime-bridge\logs\stderr.log"
nssm set BioTimeBridge AppRestartDelay 5000
nssm start BioTimeBridge
```

## Checklist piloto

1. [ ] `config.yaml` creado desde el example  
2. [ ] Token regenerado en UI Sedes y pegado en config  
3. [ ] `python -m bridge --config config.yaml doctor` → OK  
4. [ ] Tarea `once` o `run` instalada  
5. [ ] En UI Sedes: `last_heartbeat_at` fresco tras 1–2 min
6. [ ] Cada reloj muestra inventario reciente, conteo real y capacidad efectiva ≤ 500
7. [ ] Marcar **Inventario verificado** solo después de contrastar el conteo con el terminal real
8. [ ] Validar que mover al área denegada retira acceso sin borrar huella/rostro
9. [ ] Activar **Garantizar máximo 500**; con inventario viejo o reloj offline las altas deben quedar bloqueadas
10. [ ] Smoke acceso (con `dry_run: false`):
   - [ ] Activate cliente con `codigo` nuevo → Create + área sede (log `created` / `adjust_area`)  
   - [ ] Deactivate → `adjust_area` a `denied_area_id`  
   - [ ] Re-activate → `adjust_area` a `area_id`  
   - [ ] Confirmar área en BioTime UI + dispositivo (`resync_to_device` en log)  

## Relacionado

- PoC: [`../biotime-poc/`](../biotime-poc/)  
- Plan: [`../../docs/plans/08-biotime-integracion-plan.md`](../../docs/plans/08-biotime-integracion-plan.md)  
- ADR: [`../../docs/architecture/adr-biotime-puente-acceso.md`](../../docs/architecture/adr-biotime-puente-acceso.md)
