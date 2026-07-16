@echo off
REM Wrapper CLI: limpia PYTHONHOME de ZKBioTime y usa el venv del bridge.
cd /d "%~dp0"
set "PYTHONHOME="
set "PYTHONPATH="

if not exist ".venv\Scripts\python.exe" (
  echo ERROR: Falta .venv. Ver README ^(Python 3.10+ y set PYTHONHOME^=^).
  exit /b 1
)

".venv\Scripts\python.exe" -m bridge %*
