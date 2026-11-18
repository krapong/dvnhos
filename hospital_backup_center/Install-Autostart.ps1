# Run in an elevated PowerShell. This task never stores a Windows password.
$ErrorActionPreference = 'Stop'
$projectRoot = $PSScriptRoot
$taskName = 'HospitalBackupCenter-Autostart'
$pythonPath = Join-Path $projectRoot '.venv\Scripts\pythonw.exe'
$scriptPath = Join-Path $projectRoot 'auto_start.py'
if (!(Test-Path -LiteralPath $pythonPath) -or !(Test-Path -LiteralPath $scriptPath)) { throw 'Project Python or startup script is missing.' }
$account = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existing) {
    if ($existing.Actions.Execute -ne $pythonPath -or $existing.Actions.Arguments -ne ('"' + $scriptPath + '"')) { throw 'A different task already uses this name. No changes made.' }
    Write-Output 'The matching startup task is already installed.'
    exit 0
}
$action = New-ScheduledTaskAction -Execute $pythonPath -Argument ('"' + $scriptPath + '"') -WorkingDirectory $projectRoot
$boot = New-ScheduledTaskTrigger -AtStartup
$boot.Delay = 'PT30S'
$logon = New-ScheduledTaskTrigger -AtLogOn -User $account
$principal = New-ScheduledTaskPrincipal -UserId $account -LogonType S4U -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit ([TimeSpan]::Zero) -MultipleInstances IgnoreNew -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger @($boot,$logon) -Principal $principal -Settings $settings -Description 'Start Hospital Backup Center after Windows boots and restart the local server after a failure. Runs as the existing user with limited privileges.' | Out-Null
Start-ScheduledTask -TaskName $taskName
Get-ScheduledTask -TaskName $taskName | Select-Object TaskName,State
