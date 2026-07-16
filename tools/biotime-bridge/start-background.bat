@echo off
REM Inicia el puente BioTime en segundo plano (sin consola visible).
REM Preferible: instalar Task Scheduler con scripts\install-task-continuous.ps1

set ROOT=%~dp0
cd /d "%ROOT%"

if not exist "config.yaml" (
  echo Falta config.yaml. Copia config.yaml.example y completa token/URLs.
  pause
  exit /b 1
)

if exist ".venv\Scripts\pythonw.exe" (
  set PY=.venv\Scripts\pythonw.exe
) else if exist ".venv\Scripts\python.exe" (
  set PY=.venv\Scripts\python.exe
) else (
  set PY=pythonw
)

start "" "%PY%" -m bridge --config "%ROOT%config.yaml" run
echo Puente BioTime iniciado en segundo plano.
echo Revisa logs\ y Laravel BioTime ^> Dashboard ^(heartbeat^).
exit /b 0
