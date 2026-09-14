@echo off
title DrinkFlow Full Stack Runner
echo =====================================================================
echo                DRINKFLOW APPLICATION RUNNER
echo =====================================================================
echo.
echo Starting 3 services:
echo  1. Backend  : php artisan serve --port 8080 (in src/)
echo  2. Frontend : npm run dev (in src/)
echo  3. Realtime : npm start (in realtime/)
echo.
echo =====================================================================

start "DrinkFlow - Backend (Port 8080)" cmd /k "cd src && php artisan serve --port 8080"
start "DrinkFlow - Frontend Vite" cmd /k "cd src && npm run dev"
start "DrinkFlow - Realtime Gateway (Port 3001)" cmd /k "cd realtime && npm start"

echo.
echo [OK] All 3 services have been launched in separate windows!
echo Backend  : http://127.0.0.1:8080
echo Realtime : http://127.0.0.1:3001
echo.
pause
