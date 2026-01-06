@echo off
echo ========================================
echo Stopping Laravel Development Environment
echo ========================================
echo.

REM Kill processes running on specific ports
echo Stopping Laravel Server (port 8000)...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8000" ^| find "LISTENING"') do taskkill /F /PID %%a 2>nul

echo Stopping Reverb WebSocket (port 8080)...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8080" ^| find "LISTENING"') do taskkill /F /PID %%a 2>nul

echo Stopping Vite Dev Server (port 5173)...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":5173" ^| find "LISTENING"') do taskkill /F /PID %%a 2>nul

echo.
echo ========================================
echo All development servers stopped!
echo ========================================
echo.
pause
