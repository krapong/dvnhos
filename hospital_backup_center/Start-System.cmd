@echo off
cd /d "%~dp0"
if not exist ".venv\Scripts\pythonw.exe" (
  echo Python environment missing. See AUTOSTART_TH.md
  pause
  exit /b 1
)
start "" /b ".venv\Scripts\pythonw.exe" "%~dp0auto_start.py"
start "" "http://127.0.0.1:8088"
