$sourcePath = "D:\project\drugalert"
$zipPath = "D:\project\drugalert_deploy.zip"

# Clean up dev files
Remove-Item -Path "$sourcePath\storage\logs\*.log" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$sourcePath\storage\framework\sessions\*" -Exclude ".gitignore" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$sourcePath\storage\framework\views\*" -Exclude ".gitignore" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "$sourcePath\storage\framework\cache\data\*" -Exclude ".gitignore" -Recurse -Force -ErrorAction SilentlyContinue

# Clear old zip if exists
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

# Zip the directory, excluding unnecessary files
# Compress-Archive has limited exclude capabilities, so we will create a temp directory or use 7z if available.
# Actually, since it's Windows, let's just copy what we need to a temp build folder, zip that, and delete it.

$buildPath = "D:\project\drugalert_build"
if (Test-Path $buildPath) {
    Remove-Item $buildPath -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $buildPath

Write-Host "Copying files to build folder..."
# Copy everything except node_modules, .git, tests
$exclude = @("node_modules", ".git", "tests", "test_output.txt", "drop_tables.php", "cookies.txt", "storage\test_*.php", "legacy_nodejs")
Copy-Item -Path "$sourcePath\*" -Destination $buildPath -Recurse -Exclude $exclude -Force

Write-Host "Compressing to ZIP..."
Compress-Archive -Path "$buildPath\*" -DestinationPath $zipPath -Force

Write-Host "Cleaning up build folder..."
Remove-Item $buildPath -Recurse -Force

Write-Host "Done! Deployment package created at $zipPath"
