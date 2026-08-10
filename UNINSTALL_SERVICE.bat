@echo off
setlocal EnableExtensions
title Uninstall Visitor Pass Auto-Start Service
cd /d "%~dp0"

echo.
echo This will:
echo   1) Stop the local server
echo   2) Remove Windows auto-start service (Scheduled Task)
echo.
set /p CONFIRM=Type YES to continue: 
if /I not "%CONFIRM%"=="YES" (
  echo Cancelled.
  pause
  exit /b 0
)

set "PY_CMD="
where py >nul 2>&1
if %ERRORLEVEL%==0 set "PY_CMD=py -3"
if not defined PY_CMD (
  where python >nul 2>&1
  if %ERRORLEVEL%==0 set "PY_CMD=python"
)
if not defined PY_CMD (
  where python3 >nul 2>&1
  if %ERRORLEVEL%==0 set "PY_CMD=python3"
)

if not defined PY_CMD (
  echo [ERROR] Python not found.
  schtasks /Delete /TN "VisitorPassLocalHost" /F >nul 2>&1
  echo Tried to remove task VisitorPassLocalHost.
  pause
  exit /b 1
)

%PY_CMD% "%~dp0local_server.py" --uninstall-service
echo.
pause
