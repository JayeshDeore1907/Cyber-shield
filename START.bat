@echo off
cd /d %~dp0
where php >nul 2>nul
if errorlevel 1 (
  echo PHP was not found. Install PHP 8+ and ensure php.exe is in PATH.
  pause
  exit /b 1
)
echo CyberShield starting at http://localhost:8000
start "" "http://localhost:8000"
php -S localhost:8000 -t .
