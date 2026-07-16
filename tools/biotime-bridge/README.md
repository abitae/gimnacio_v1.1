# Puente BioTime ↔ Laravel

Aplicación Python que corre **en el mismo PC que BioTime** (sede). Habla HTTPS hacia Laravel (Banahosting) y HTTP local hacia BioTime 8.x.

No usa WebSockets. El puente **siempre inicia** las conexiones salientes.

## Interfaz gráfica

Ventana tkinter (sin dependencias extra) para operación en sede:

```bat
python -m bridge --config config.yaml gui
```

O doble clic en `start-gui.bat` (usa `.venv` si existe).

| Acción | Qué hace |
| --- | --- |
| Doctor | Valida Laravel health + login BioTime |
| Una vez | Un ciclo `once` (commands / roster / sync según config) |
| Iniciar / Detener | Loop `run` en segundo plano, stop cooperativo |
| Configuración | Editar y **guardar** `config.yaml` (URLs, token, áreas, tiempos) |

Pestaña **Configuración**: cambia valores y pulsa **Guardar config.yaml**. Token/password ocultos por defecto (checkbox para mostrar). No se puede guardar mientras el loop está en marcha.

---

## Puesta en marcha rápida (sede)

1. **Copiar config**
   ```bat
   cd tools\biotime-bridge
   python -m venv .venv
   .venv\Scripts\activate
   pip install -r requirements.txt
   copy config.yaml.example config.yaml
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

## URLs Laravel (API)

Auth: `Authorization: Bearer <token_sede>` (o `X-BioTime-Secret`).

| Método | Ruta | Uso |
| --- | --- | --- |
| `GET` | `/api/biotime/health` | Salud + heartbeat de la sede del token |
| `GET` | `/api/biotime/commands?limit=100` | Comandos pending → processing |
| `POST` | `/api/biotime/commands/{id}/ack` | `{ "status": "acked"\|"failed", "error": "..." }` |
| `GET` | `/api/biotime/roster` | Snapshot acceso |
| `POST` | `/api/biotime/sync` | Push (`employees`, …) |

`emp_code` = string del `cliente.id` Laravel.

## Comandos CLI

`--config` va **antes** del subcomando:

```bat
python -m bridge --config config.yaml gui
python -m bridge --config config.yaml doctor
python -m bridge --config config.yaml once
python -m bridge --config config.yaml run
python -m bridge --config config.yaml roster
python -m bridge --config config.yaml sync-employees
```

| Comando | Qué hace | Exit code |
| --- | --- | --- |
| `gui` | Interfaz gráfica (tkinter) | |
| `doctor` | Laravel `/api/biotime/health` (valida token sede) + login BioTime | `0` OK / `1` FAIL |
| `once` | Un ciclo de commands (+ roster/sync si están activos en config) | |
| `run` | Loop continuo (producción) | |
| `roster` | Solo reconcile roster | |
| `sync-employees` | Push employees a Laravel | |

Variable opcional: `BIOTIME_BRIDGE_CONFIG=C:\ruta\config.yaml`

### dry_run

`dry_run: true` en config: no escribe áreas en BioTime; sí puede ACK commands para no atascar la cola.

### laravel_verify_ssl

Por defecto `true`. En desarrollo local con HTTPS y certificado auto-firmado (`*.test`), usa:

```yaml
laravel_verify_ssl: false
```

En Banahosting / producción deja `true` (o omite la clave).

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
6. [ ] Activate/deactivate de prueba reflejado en BioTime + dispositivo  

## Relacionado

- PoC: [`../biotime-poc/`](../biotime-poc/)  
- Plan: [`../../docs/plans/08-biotime-integracion-plan.md`](../../docs/plans/08-biotime-integracion-plan.md)  
- ADR: [`../../docs/architecture/adr-biotime-puente-acceso.md`](../../docs/architecture/adr-biotime-puente-acceso.md)
