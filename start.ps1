# DrinkFlow Full-Stack Runner Script (PowerShell)
Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host "               DRINKFLOW APPLICATION RUNNER                          " -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Starting 3 services in parallel:" -ForegroundColor Yellow
Write-Host " 1. Backend  : php artisan serve --port 8080 (in src/)" -ForegroundColor Gray
Write-Host " 2. Frontend : npm run dev (in src/)" -ForegroundColor Gray
Write-Host " 3. Realtime : npm start (in realtime/)" -ForegroundColor Gray
Write-Host ""

Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\src'; $Host.UI.RawUI.WindowTitle='DrinkFlow Backend (8080)'; php artisan serve --port 8080"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\src'; $Host.UI.RawUI.WindowTitle='DrinkFlow Frontend Vite'; npm run dev"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\realtime'; $Host.UI.RawUI.WindowTitle='DrinkFlow Realtime (3001)'; npm start"

Write-Host "[OK] All 3 services have been launched in separate terminal windows!" -ForegroundColor Green
Write-Host "Backend  : http://127.0.0.1:8080" -ForegroundColor White
Write-Host "Realtime : http://127.0.0.1:3001" -ForegroundColor White
Write-Host ""
