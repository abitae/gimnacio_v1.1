@echo off
REM Inicia el puente BioTime en segundo plano (sin consola visible).
set ROOT=%~dp0
cd /d "%ROOT%"

if not exist "config.yaml" (
  echo Falta config.yaml. Copia config.yaml.example y completa token/URLs.
  pause
  exit /b 1
)

if exist "BioTimeBridge.exe" (
  start "" "BioTimeBridge.exe" --config "%ROOT%config.yaml" run
  goto :started
)

if exist "dist\BioTimeBridge.exe" (
  start "" "dist\BioTimeBridge.exe" --config "%ROOT%config.yaml" run
  goto :started
)

if exist ".venv\Scripts\pythonw.exe" (
  set PY=.venv\Scripts\pythonw.exe
) else if exist ".venv\Scripts\python.exe" (
  set PY=.venv\Scripts\python.exe
) else (
  echo ERROR: Falta BioTimeBridge.exe y .venv. Compila con scripts\build-exe.bat
  pause
  exit /b 1
)

start "" "%PY%" -m bridge --config "%ROOT%config.yaml" run

:started
echo Puente BioTime iniciado en segundo plano.
echo Revisa logs\ y Laravel BioTime ^> Dashboard ^(heartbeat^).
exit /b 0
