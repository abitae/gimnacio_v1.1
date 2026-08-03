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

.PARAMETER BridgeExe
  Ruta a BioTimeBridge.exe. Default: auto (BioTimeBridge.exe en BridgeRoot o dist\).
#>
param(
    [string]$TaskName = "BioTimeBridgeOnce",
    [string]$BridgeRoot = "",
    [string]$ConfigPath = "",
    [string]$PythonExe = "",
    [string]$BridgeExe = ""
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

$runtime = & (Join-Path $PSScriptRoot "resolve-bridge-runtime.ps1") -BridgeRoot $BridgeRoot -BridgeExe $BridgeExe -PythonExe $PythonExe

$logsDir = Join-Path $BridgeRoot "logs"
New-Item -ItemType Directory -Force -Path $logsDir | Out-Null

$argList = if ($runtime.ArgumentsPrefix) {
    "{0} --config `"{1}`" once" -f $runtime.ArgumentsPrefix, $ConfigPath
} else {
    "--config `"$ConfigPath`" once"
}
$action = New-ScheduledTaskAction -Execute $runtime.Executable -Argument $argList -WorkingDirectory $BridgeRoot

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
Write-Host "  Runtime: $($runtime.Mode) -> $($runtime.Executable)"
Write-Host "  Root   : $BridgeRoot"
Write-Host "  Config : $ConfigPath"
Write-Host ""
Write-Host "Verificar:"
Write-Host "  1) bridge.bat doctor   (o BioTimeBridge.exe --config config.yaml doctor)"
Write-Host "  2) En Laravel BioTime > Sedes: last_heartbeat_at debe actualizarse tras 1-2 minutos."
Write-Host "Desinstalar: .\uninstall-task.ps1 -TaskName $TaskName"
