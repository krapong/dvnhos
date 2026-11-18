$ErrorActionPreference='Stop'
$project=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$version='LR-EMR-AlmaLinux9-'+([DateTime]::Now.ToString('yyyyMMdd-HHmmss',[Globalization.CultureInfo]::InvariantCulture))
$release=Join-Path $project "output\deploy\$version"
New-Item -ItemType Directory -Path $release -ErrorAction Stop | Out-Null
$files=@('mfa.php','mfa-store.php','export-report.php','branding.php','branding.config.php','usage-log.php','usage.php','usage-history.php','api.php','auth-bootstrap.php','field-options.php','family.php','hosxp-auth.php','index.php','infant-risk.php','login.php','login-throttle.php','logout.php','manual.php','manual-store.php','providers.php','report-fields.php','report-labs.php','report.php','risk.php','security.php','.htaccess')
foreach($file in $files){Copy-Item -LiteralPath (Join-Path $project $file) -Destination (Join-Path $release $file)}
if(Test-Path -LiteralPath (Join-Path $project 'assets')){
    New-Item -ItemType Directory -Path (Join-Path $release 'assets') -Force | Out-Null
    foreach($logo in @('hospital-logo.png','hospital-logo.jpg','hospital-logo.webp')){
        $source=Join-Path $project "assets\$logo"
        if(Test-Path -LiteralPath $source){Copy-Item -LiteralPath $source -Destination (Join-Path $release "assets\$logo")}
    }
}
New-Item -ItemType Directory -Path (Join-Path $release 'assets\vendor') -Force | Out-Null
foreach($vendorFile in @('qrcode.js','qrcode-LICENSE.txt','qrcode-source.txt')){Copy-Item -LiteralPath (Join-Path $project ("assets\vendor\"+$vendorFile)) -Destination (Join-Path $release ("assets\vendor\"+$vendorFile))}
foreach($dir in @('database','deploy','tools','.private')){New-Item -ItemType Directory -Path (Join-Path $release $dir) | Out-Null}
foreach($file in @('supplement.sql','sheet-options-source.json','grants-example.sql')){Copy-Item -LiteralPath (Join-Path $project "database\$file") -Destination (Join-Path $release "database\$file")}
foreach($file in @('README-server.md','apache-lr-emr.conf','selinux-almalinux.sh')){Copy-Item -LiteralPath (Join-Path $project "deploy\$file") -Destination (Join-Path $release "deploy\$file")}
Copy-Item -LiteralPath (Join-Path $project 'tools\reset-mfa.php') -Destination (Join-Path $release 'tools\reset-mfa.php')
Copy-Item -LiteralPath (Join-Path $project 'tools\check-server.php') -Destination (Join-Path $release 'tools\check-server.php')
Copy-Item -LiteralPath (Join-Path $project 'tools\prune-usage.php') -Destination (Join-Path $release 'tools\prune-usage.php')
Copy-Item -LiteralPath (Join-Path $project 'deploy\config-production.example.php') -Destination (Join-Path $release 'config.example.php')
Copy-Item -LiteralPath (Join-Path $project 'supplement.config.example.php') -Destination (Join-Path $release 'supplement.config.example.php')
$utf8=New-Object System.Text.UTF8Encoding($false)
[IO.File]::WriteAllText((Join-Path $release '.private\.htaccess'),"Require all denied`n",$utf8)
# Normalize text to LF and UTF-8 without BOM for Linux / Apache / shell scripts.
Get-ChildItem -LiteralPath $release -Recurse -File -Force | ForEach-Object { $content=[IO.File]::ReadAllText($_.FullName);[IO.File]::WriteAllText($_.FullName,$content.Replace("`r`n","`n"),$utf8) }
$manifest=Get-ChildItem -LiteralPath $release -Recurse -File -Force | Sort-Object FullName | ForEach-Object {$rel=$_.FullName.Substring($release.Length+1).Replace('\','/');(Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()+'  '+$rel}
[IO.File]::WriteAllLines((Join-Path $release 'SHA256SUMS.txt'),$manifest,$utf8)
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipPath="$release.zip"
[IO.Compression.ZipFile]::CreateFromDirectory($release,$zipPath)
Write-Output $zipPath
