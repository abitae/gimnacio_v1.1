@echo off
REM Lanza la interfaz grafica del puente BioTime.
cd /d "%~dp0"
if exist ".venv\Scripts\python.exe" (
  ".venv\Scripts\python.exe" -m bridge --config config.yaml gui
) else (
  python -m bridge --config config.yaml gui
)
pause
