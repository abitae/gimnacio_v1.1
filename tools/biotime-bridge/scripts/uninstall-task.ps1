#Requires -Version 5.1
param(
    [Parameter(Mandatory = $false)]
    [string]$TaskName = "BioTimeBridgeOnce"
)

$ErrorActionPreference = "Stop"

$existing = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if (-not $existing) {
    Write-Host "No existe la tarea '$TaskName'."
    exit 0
}

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
Write-Host "OK: tarea '$TaskName' eliminada."
Write-Host "Si usaste continuous, prueba tambien: .\uninstall-task.ps1 -TaskName BioTimeBridgeRun"
