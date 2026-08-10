@echo off
setlocal EnableExtensions
title Reset Local SQLite Database
cd /d "%~dp0"

echo.
echo This will DELETE the local SQLite database and re-seed on next START.
echo Your remote/Hostinger .env backup (if any) is NOT deleted.
echo.
set /p CONFIRM=Type YES to continue: 
if /I not "%CONFIRM%"=="YES" (
  echo Cancelled.
  pause
  exit /b 0
)

if exist "runtime\.local_setup_done" del /f /q "runtime\.local_setup_done"
if exist "codecanyon-24643230-visitor-pass-management-system\database\database.sqlite" (
  del /f /q "codecanyon-24643230-visitor-pass-management-system\database\database.sqlite"
)
if exist "codecanyon-24643230-visitor-pass-management-system\storage\installed" (
  del /f /q "codecanyon-24643230-visitor-pass-management-system\storage\installed"
)

echo Local database markers cleared. Run START.bat to reinstall.
pause
