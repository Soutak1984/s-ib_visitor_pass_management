@echo off
setlocal EnableExtensions
title Visitor Pass - Start (Auto-Service + Minimized)
cd /d "%~dp0"

REM Local DNS name (registered in Windows hosts by local_server.py)
REM Opens as:  http://visitorpass.localhost/   (port 80 — no :port in URL)
REM NOTE: use .localhost NOT .local ( .local can point at LAN devices like a DVR )

REM Prefer "py" launcher, then python, then python3
set "PY_CMD="

where py >nul 2>&1
if %ERRORLEVEL%==0 (
  set "PY_CMD=py -3"
  goto :run
)

where python >nul 2>&1
if %ERRORLEVEL%==0 (
  set "PY_CMD=python"
  goto :run
)

where python3 >nul 2>&1
if %ERRORLEVEL%==0 (
  set "PY_CMD=python3"
  goto :run
)

echo.
echo [ERROR] Python was not found on this PC.
echo.
echo Install Python 3 from https://www.python.org/downloads/
echo IMPORTANT: during install, tick "Add python.exe to PATH"
echo.
echo Then double-click START.bat again.
echo.
pause
exit /b 1

:run
REM Install auto-start Windows service + start server minimized
REM Local URL: http://visitorpass.localhost/  (hosts: 127.0.0.1 visitorpass.localhost, port 80)
%PY_CMD% "%~dp0local_server.py" --install-and-start
set "EXITCODE=%ERRORLEVEL%"
if not "%EXITCODE%"=="0" (
  echo.
  echo Start failed with code %EXITCODE%.
  echo Check runtime\server.log for details.
  pause
)
exit /b %EXITCODE%
