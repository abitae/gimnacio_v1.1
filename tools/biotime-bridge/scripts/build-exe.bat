@echo off
REM Compila BioTimeBridge.exe (PyInstaller). Requiere venv + Python 3.10+.
cd /d "%~dp0.."
set "PYTHONHOME="
set "PYTHONPATH="
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-exe.ps1"
if errorlevel 1 (
  echo.
  echo ERROR al compilar. Revisa que exista .venv y PyInstaller.
  pause
  exit /b 1
)
echo.
pause
