#Requires -Version 5.1
<#
.SYNOPSIS
  Resuelve el ejecutable del puente (BioTimeBridge.exe) o Python del venv.
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$BridgeRoot,
    [string]$BridgeExe = "",
    [string]$PythonExe = ""
)

$BridgeRoot = (Resolve-Path $BridgeRoot).Path

if ($BridgeExe -and (Test-Path $BridgeExe)) {
    return @{
        Mode = "exe"
        Executable = (Resolve-Path $BridgeExe).Path
        ArgumentsPrefix = ""
    }
}

foreach ($candidate in @(
    (Join-Path $BridgeRoot "BioTimeBridge.exe"),
    (Join-Path $BridgeRoot "dist\BioTimeBridge.exe")
)) {
    if (Test-Path $candidate) {
        return @{
            Mode = "exe"
            Executable = (Resolve-Path $candidate).Path
            ArgumentsPrefix = ""
        }
    }
}

if (-not $PythonExe) {
    $venvPy = Join-Path $BridgeRoot ".venv\Scripts\python.exe"
    if (Test-Path $venvPy) {
        $PythonExe = $venvPy
    } else {
        $PythonExe = (Get-Command python -ErrorAction Stop).Source
    }
}

return @{
    Mode = "python"
    Executable = $PythonExe
    ArgumentsPrefix = "-m bridge"
}
