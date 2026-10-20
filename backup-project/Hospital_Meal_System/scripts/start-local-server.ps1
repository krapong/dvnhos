param(
    [int] $Port = 8010
)

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

& 'C:\xampp\php\php.exe' artisan serve --host=127.0.0.1 --port=$Port
