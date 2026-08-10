@echo off
setlocal EnableExtensions
title Stop Visitor Pass Local Server
cd /d "%~dp0"

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

if defined PY_CMD (
  %PY_CMD% "%~dp0local_server.py" --stop
) else (
  echo Python not found - falling back to port kill...
  REM Only our ports (80, 8750+, legacy 8000) — never 8090 / DVR ports
  for %%P in (80 8750 8751 8752 8000 8001 8002 8003 8004 8005) do (
    for /f "tokens=5" %%A in ('netstat -ano ^| findstr ":%%P " ^| findstr "LISTENING"') do (
      echo Killing PID %%A on port %%P
      taskkill /F /T /PID %%A >nul 2>&1
    )
  )
)

echo.
echo Note: Auto-start service is still installed.
echo Server will start again after reboot / logon.
echo To remove auto-start permanently, run UNINSTALL_SERVICE.bat
echo.
pause
