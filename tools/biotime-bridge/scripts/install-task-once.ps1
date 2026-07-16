#Requires -Version 5.1
<#
.SYNOPSIS
  Registra una tarea en Task Scheduler que ejecuta el puente cada minuto (comando once).

.PARAMETER TaskName
  Nombre de la tarea. Default: BioTimeBridgeOnce

.PARAMETER BridgeRoot
  Carpeta tools/biotime-bridge. Default: directorio padre de este script.

.PARAMETER ConfigPath
  Ruta a config.yaml. Default: <BridgeRoot>\config.yaml

.PARAMETER PythonExe
  Interprete Python. Default: <BridgeRoot>\.venv\Scripts\python.exe si existe, sino python.
#>
param(
    [string]$TaskName = "BioTimeBridgeOnce",
    [string]$BridgeRoot = "",
    [string]$ConfigPath = "",
    [string]$PythonExe = ""
)

$ErrorActionPreference = "Stop"

# Script vive en tools/biotime-bridge/scripts/
$BridgeRoot = if ($BridgeRoot) { (Resolve-Path $BridgeRoot).Path } else { (Resolve-Path (Join-Path $PSScriptRoot "..")).Path }

if (-not $ConfigPath) {
    $ConfigPath = Join-Path $BridgeRoot "config.yaml"
}
if (-not (Test-Path $ConfigPath)) {
    throw "No existe config.yaml en '$ConfigPath'. Copia config.yaml.example y completa token/URLs."
}

if (-not $PythonExe) {
    $venvPy = Join-Path $BridgeRoot ".venv\Scripts\python.exe"
    if (Test-Path $venvPy) {
        $PythonExe = $venvPy
    } else {
        $PythonExe = (Get-Command python -ErrorAction Stop).Source
    }
}

$logsDir = Join-Path $BridgeRoot "logs"
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

$argList = "-m bridge --config `"$ConfigPath`" once"
$action = New-ScheduledTaskAction -Execute $PythonExe -Argument $argList -WorkingDirectory $BridgeRoot

# Cada 1 minuto, indefinido
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)

$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Force | Out-Null

Write-Host "OK: tarea '$TaskName' registrada (cada 1 min -> once)."
Write-Host "  Python : $PythonExe"
Write-Host "  Root   : $BridgeRoot"
Write-Host "  Config : $ConfigPath"
Write-Host ""
Write-Host "Verificar:"
Write-Host "  1) python -m bridge --config `"$ConfigPath`" doctor"
Write-Host "  2) En Laravel BioTime > Sedes: last_heartbeat_at debe actualizarse tras 1-2 minutos."
Write-Host "Desinstalar: .\uninstall-task.ps1 -TaskName $TaskName"
