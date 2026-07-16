@echo off
REM Lanza la interfaz grafica del puente BioTime.
REM IMPORTANTE: BioTime suele definir PYTHONHOME=C:\ZKBioTime\Python37 y rompe
REM cualquier otro Python (3.13 / venv). Se limpia solo para este proceso.
cd /d "%~dp0"
set "PYTHONHOME="
set "PYTHONPATH="

if exist ".venv\Scripts\python.exe" (
  ".venv\Scripts\python.exe" -m bridge --config config.yaml gui
) else (
  echo ERROR: No existe .venv. Crea el venv con Python 3.10+ SIN PYTHONHOME:
  echo   set PYTHONHOME=
  echo   "C:\Program Files\Python313\python.exe" -m venv .venv
  pause
  exit /b 1
)
pause
