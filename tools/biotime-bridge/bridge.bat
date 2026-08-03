@echo off
REM Wrapper CLI: usa BioTimeBridge.exe si existe; si no, Python del venv.
cd /d "%~dp0"
set "PYTHONHOME="
set "PYTHONPATH="

if exist "BioTimeBridge.exe" (
  "BioTimeBridge.exe" %*
  exit /b %ERRORLEVEL%
)

if exist "dist\BioTimeBridge.exe" (
  "dist\BioTimeBridge.exe" %*
  exit /b %ERRORLEVEL%
)

if not exist ".venv\Scripts\python.exe" (
  echo ERROR: Falta BioTimeBridge.exe y .venv. Compila con scripts\build-exe.bat o crea el venv ^(README^).
  exit /b 1
)

".venv\Scripts\python.exe" -m bridge %*
