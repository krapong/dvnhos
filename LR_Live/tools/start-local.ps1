$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$phpExe='C:\xampp\php\php.exe'
$mysqlBase='D:\laragon\bin\mysql\mysql-8.4.3-winx64'
$dataDir='D:\laragon\data\lr-supplement'
$mysqlListener=Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue
if(!$mysqlListener){
    if(!(Test-Path -LiteralPath (Join-Path $dataDir 'auto.cnf'))){throw 'Local MySQL data directory is missing. Do not initialize over existing data.'}
    Start-Process -FilePath (Join-Path $mysqlBase 'bin\mysqld.exe') -ArgumentList '--no-defaults',"--basedir=$mysqlBase","--datadir=$dataDir",'--bind-address=127.0.0.1','--port=3308','--mysqlx=0','--skip-log-bin',"--log-error=$dataDir\server.err" -WindowStyle Hidden
}else{
    foreach($listener in $mysqlListener){
        $owner=Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
        if($owner.Name -ne 'mysqld.exe' -or !$owner.CommandLine.Contains($dataDir)){throw 'Port 3308 belongs to a different server.'}
    }
}
$webListeners=Get-NetTCPConnection -State Listen -LocalPort 8089 -ErrorAction SilentlyContinue
foreach($listener in $webListeners){
    $owner=Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
    if($owner.Name -ne 'php.exe' -or !$owner.CommandLine.Contains($projectRoot)){throw 'Port 8089 belongs to a different application.'}
    Stop-Process -Id $owner.ProcessId -Force
}
# Also stop matching project servers that are not visible in the TCP inventory.
Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine.Contains($projectRoot) -and $_.CommandLine -match '-S\s+127\.0\.0\.1:8089(?:\s|$)' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
New-Item -ItemType Directory -Path (Join-Path $projectRoot '.private\logs') -Force | Out-Null
Start-Process -FilePath $phpExe -ArgumentList '-n','-d','extension_dir=C:\xampp\php\ext','-d','extension=pdo_mysql','-d','extension=openssl','-S','127.0.0.1:8089','-t',$projectRoot,(Join-Path $projectRoot 'router.php') -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardError (Join-Path $projectRoot '.private\logs\server-error.log') -RedirectStandardOutput (Join-Path $projectRoot '.private\logs\server-output.log')
Write-Output 'Local MySQL: 127.0.0.1:3308 / lr_supplement'
Write-Output 'Web: http://127.0.0.1:8089/index.php'
