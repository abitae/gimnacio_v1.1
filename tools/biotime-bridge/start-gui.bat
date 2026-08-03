@echo off
REM Lanza la interfaz grafica del puente BioTime.
cd /d "%~dp0"
set "PYTHONHOME="
set "PYTHONPATH="

if exist "BioTimeBridge.exe" (
  start "" "BioTimeBridge.exe" --config config.yaml gui
  exit /b 0
)

if exist "dist\BioTimeBridge.exe" (
  start "" "dist\BioTimeBridge.exe" --config config.yaml gui
  exit /b 0
)

if exist ".venv\Scripts\python.exe" (
  start "" ".venv\Scripts\python.exe" -m bridge --config config.yaml gui
  exit /b 0
)

echo ERROR: Falta BioTimeBridge.exe. Compila con scripts\build-exe.bat o crea .venv ^(README^).
pause
exit /b 1
