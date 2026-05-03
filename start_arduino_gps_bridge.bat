@echo off
set "APP_DIR=%~dp0"
cd /d "%APP_DIR%"
echo Starting Haven Zen web server if needed...
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "if (-not (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)) { Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList @('-S','localhost:8000','-t','%APP_DIR%') -WorkingDirectory '%APP_DIR%' -WindowStyle Minimized; Start-Sleep -Seconds 2 }"
echo Starting Arduino GPS bridge for Havenzen_Van on COM7...
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%APP_DIR%tools\arduino_gps_bridge.ps1" -PortName COM7 -VehicleId 3
