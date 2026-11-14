$projectPath = $PSScriptRoot
Set-Location -LiteralPath $projectPath
$localData = Join-Path $projectPath '.private/mysql-data'
$mysqlBinary = 'D:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
if ((Test-Path -LiteralPath $localData) -and (Test-Path -LiteralPath $mysqlBinary)) {
    $connection = [System.Net.Sockets.TcpClient]::new()
    try { $connection.Connect('127.0.0.1', 3308) } catch {
        Start-Process -FilePath $mysqlBinary -WindowStyle Hidden -ArgumentList @('--no-defaults', '--basedir=D:\laragon\bin\mysql\mysql-8.4.3-winx64', ('--datadir="' + $localData + '"'), '--port=3308', '--bind-address=127.0.0.1', '--mysqlx=OFF')
        Start-Sleep -Seconds 8
    } finally { $connection.Dispose() }
}
php -S 127.0.0.1:8081 router.php
