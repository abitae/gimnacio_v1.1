#Requires -Version 5.1
<#
.SYNOPSIS
  Compila BioTimeBridge.exe con PyInstaller.

.DESCRIPTION
  Requiere Python 3.10+ (python.org), venv del bridge y sin PYTHONHOME de ZKBioTime.

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File scripts\build-exe.ps1
#>
param(
    [string]$BridgeRoot = "",
    [string]$PythonExe = ""
)

$ErrorActionPreference = "Stop"

$BridgeRoot = if ($BridgeRoot) { (Resolve-Path $BridgeRoot).Path } else { (Resolve-Path (Join-Path $PSScriptRoot "..")).Path }
Set-Location $BridgeRoot

$env:PYTHONHOME = $null
$env:PYTHONPATH = $null

if (-not $PythonExe) {
    $venvPy = Join-Path $BridgeRoot ".venv\Scripts\python.exe"
    if (Test-Path $venvPy) {
        $PythonExe = $venvPy
    } else {
        $PythonExe = (Get-Command python -ErrorAction Stop).Source
    }
}

Write-Host "Bridge root : $BridgeRoot"
Write-Host "Python      : $PythonExe"
& $PythonExe --version

& $PythonExe -m pip install -U pip | Out-Host
& $PythonExe -m pip install -r requirements.txt -r requirements-build.txt | Out-Host

if (Test-Path (Join-Path $BridgeRoot "build")) {
    Remove-Item -Recurse -Force (Join-Path $BridgeRoot "build")
}
if (Test-Path (Join-Path $BridgeRoot "dist\BioTimeBridge.exe")) {
    Remove-Item -Force (Join-Path $BridgeRoot "dist\BioTimeBridge.exe")
}

& $PythonExe -m PyInstaller BioTimeBridge.spec --noconfirm --clean | Out-Host

$exeDist = Join-Path $BridgeRoot "dist\BioTimeBridge.exe"
if (-not (Test-Path $exeDist)) {
    throw "No se genero dist\BioTimeBridge.exe"
}

Copy-Item -Force (Join-Path $BridgeRoot "config.yaml.example") (Join-Path $BridgeRoot "dist\config.yaml.example")

$publicDir = Join-Path (Resolve-Path (Join-Path $BridgeRoot "..\..")).Path "public\dist\dist"
if (-not (Test-Path $publicDir)) {
    New-Item -ItemType Directory -Path $publicDir -Force | Out-Null
}
Copy-Item -Force $exeDist (Join-Path $publicDir "BioTimeBridge.exe")
Copy-Item -Force (Join-Path $BridgeRoot "config.yaml.example") (Join-Path $publicDir "config.yaml.example")

$exeRoot = Join-Path $BridgeRoot "BioTimeBridge.exe"
Copy-Item -Force $exeDist $exeRoot

Write-Host ""
Write-Host "OK: ejecutable generado"
Write-Host "  dist\BioTimeBridge.exe"
Write-Host "  public\dist\dist\BioTimeBridge.exe (descarga Laravel)"
Write-Host "  BioTimeBridge.exe (copia en raiz del bridge)"
Write-Host ""
Write-Host "Despliegue en sede:"
Write-Host "  1) Copia BioTimeBridge.exe + config.yaml.example a la carpeta de la sede"
Write-Host "  2) Renombra/copia config.yaml.example -> config.yaml y completa token/URLs"
Write-Host "  3) BioTimeBridge.exe --config config.yaml doctor"
Write-Host "  4) start-gui.bat o BioTimeBridge.exe --config config.yaml gui"
