@echo off
echo ========================================
echo Starting Laravel Development Environment
echo ========================================
echo.
echo This will open 3 terminal windows:
echo 1. Laravel Server (php artisan serve)
echo 2. Reverb WebSocket Server (php artisan reverb:start)
echo 3. Vite Dev Server (npm run dev)
echo.
echo Press any key to continue...
pause > nul

REM Start Laravel Server
start "Laravel Server" cmd /k "echo [Laravel Server] && php artisan serve"

REM Wait 2 seconds
timeout /t 2 /nobreak > nul

REM Start Reverb WebSocket Server
start "Reverb WebSocket" cmd /k "echo [Reverb WebSocket Server] && php artisan reverb:start"

REM Wait 2 seconds
timeout /t 2 /nobreak > nul

REM Start Vite Dev Server
start "Vite Dev Server" cmd /k "echo [Vite Dev Server] && npm run dev"

echo.
echo ========================================
echo All servers are starting...
echo ========================================
echo.
echo Laravel Server: http://localhost:8000
echo Reverb WebSocket: ws://localhost:8080
echo Vite Dev Server: http://localhost:5173
echo.
echo To stop all servers, close each terminal window.
echo.
pause
