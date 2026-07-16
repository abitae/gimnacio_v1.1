#Requires -Version 5.1
<#
.SYNOPSIS
  Registra Task Scheduler con proceso continuo (bridge run) y reinicio ante fallo.

.PARAMETER TaskName
  Default: BioTimeBridgeRun

.PARAMETER BridgeRoot / ConfigPath / PythonExe
  Igual que install-task-once.ps1
#>
param(
    [string]$TaskName = "BioTimeBridgeRun",
    [string]$BridgeRoot = "",
    [string]$ConfigPath = "",
    [string]$PythonExe = ""
)

$ErrorActionPreference = "Stop"

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

$argList = "-m bridge --config `"$ConfigPath`" run"
$action = New-ScheduledTaskAction -Execute $PythonExe -Argument $argList -WorkingDirectory $BridgeRoot

# Al inicio de sesion + al arrancar el equipo (si hay usuario)
$triggerLogon = New-ScheduledTaskTrigger -AtLogOn
$triggerStartup = New-ScheduledTaskTrigger -AtStartup

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -MultipleInstances IgnoreNew

$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Highest

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger @($triggerLogon, $triggerStartup) `
    -Settings $settings `
    -Principal $principal `
    -Description "Puente BioTime continuo (run) con reinicio ante fallo" `
    -Force | Out-Null

# Arrancar ahora
Start-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

Write-Host "OK: tarea '$TaskName' registrada (run continuo + restart on failure)."
Write-Host "  Python : $PythonExe"
Write-Host "  Root   : $BridgeRoot"
Write-Host "  Config : $ConfigPath"
Write-Host ""
Write-Host "Verificar:"
Write-Host "  1) Get-ScheduledTask -TaskName $TaskName | Get-ScheduledTaskInfo"
Write-Host "  2) python -m bridge --config `"$ConfigPath`" doctor"
Write-Host "  3) Laravel BioTime > Sedes: last_heartbeat_at actualizado."
Write-Host "Desinstalar: .\uninstall-task.ps1 -TaskName $TaskName"
Write-Host ""
Write-Host "Nota: para servicio SYSTEM 24/7 sin login, preferir NSSM (ver README)."
