@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Visitor Pass - Install XAMPP PHP 8.2 + Replace folders
cd /d "%~dp0"

echo.
echo ================================================================
echo  Visitor Pass - XAMPP PHP 8.2 setup
echo ================================================================
echo.
echo  This will:
echo    1) Install XAMPP 8.2.12 (if not already installed)
echo    2) Backup then REPLACE:  C:\xampp\php\
echo    3) Copy ImageMagick / helper DLLs into:  C:\xampp\apache\bin\
echo.
echo  Project source folders:
echo    PHP  : %~dp0php\
echo    XAMPP installer: xampp-windows-x64-8.2.12-0-VS16-installer (1).exe
echo.
echo  IMPORTANT: Run this as Administrator.
echo  Stop Apache and MySQL in XAMPP Control Panel before continuing.
echo.
pause

REM ------------------------------------------------------------------
REM Admin check
REM ------------------------------------------------------------------
net session >nul 2>&1
if errorlevel 1 (
  echo.
  echo [ERROR] Not running as Administrator.
  echo Right-click INSTALL_XAMPP.bat -^> Run as administrator
  echo.
  pause
  exit /b 1
)

set "SRC_PHP=%~dp0php"
set "XAMPP=C:\xampp"
set "DST_PHP=%XAMPP%\php"
set "DST_APACHE_BIN=%XAMPP%\apache\bin"
set "BACKUP_ROOT=%XAMPP%\_backup_visitorpass_%DATE:~-4%%DATE:~3,2%%DATE:~0,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%"
set "BACKUP_ROOT=%BACKUP_ROOT: =0%"

set "INSTALLER="
for %%F in ("%~dp0xampp-windows-x64-8.2.12-0-VS16-installer*.exe") do set "INSTALLER=%%~fF"

if not exist "%SRC_PHP%\php.exe" (
  echo [ERROR] Source PHP folder not found: %SRC_PHP%
  echo Put the custom php folder next to this BAT file.
  pause
  exit /b 1
)

REM ------------------------------------------------------------------
REM Step 1: Install XAMPP if missing
REM ------------------------------------------------------------------
if exist "%XAMPP%\xampp-control.exe" (
  echo [OK] XAMPP already installed at %XAMPP%
) else (
  echo [..] XAMPP not found at %XAMPP%
  if "%INSTALLER%"=="" (
    echo [ERROR] XAMPP installer not found in this folder.
    echo Expected: xampp-windows-x64-8.2.12-0-VS16-installer (1).exe
    echo Download XAMPP PHP 8.2 and install to C:\xampp first.
    pause
    exit /b 1
  )
  echo [..] Starting XAMPP installer:
  echo     %INSTALLER%
  echo.
  echo Install to: C:\xampp
  echo Components: Apache + MySQL + PHP + phpMyAdmin (default is fine)
  echo.
  start /wait "" "%INSTALLER%"
  if not exist "%XAMPP%\xampp-control.exe" (
    echo [ERROR] XAMPP still not found after installer. Install to C:\xampp and re-run.
    pause
    exit /b 1
  )
  echo [OK] XAMPP installed
)

if not exist "%DST_APACHE_BIN%" (
  echo [ERROR] Missing: %DST_APACHE_BIN%
  echo XAMPP install looks incomplete.
  pause
  exit /b 1
)

echo.
echo [..] Make sure Apache and MySQL are STOPPED in XAMPP Control Panel.
echo     Press any key only after they are stopped...
pause >nul

REM ------------------------------------------------------------------
REM Step 2: Backup existing folders
REM ------------------------------------------------------------------
echo.
echo [..] Creating backup under:
echo     %BACKUP_ROOT%
mkdir "%BACKUP_ROOT%" 2>nul

if exist "%DST_PHP%\php.exe" (
  echo [..] Backing up C:\xampp\php ...
  xcopy "%DST_PHP%" "%BACKUP_ROOT%\php\" /E /I /H /Y /Q >nul
  if errorlevel 1 (
    echo [ERROR] Backup of php failed. Aborting.
    pause
    exit /b 1
  )
  echo [OK] Backed up php
)

if exist "%DST_APACHE_BIN%\httpd.exe" (
  echo [..] Backing up C:\xampp\apache\bin ...
  xcopy "%DST_APACHE_BIN%" "%BACKUP_ROOT%\apache_bin\" /E /I /H /Y /Q >nul
  if errorlevel 1 (
    echo [ERROR] Backup of apache\bin failed. Aborting.
    pause
    exit /b 1
  )
  echo [OK] Backed up apache\bin
)

REM ------------------------------------------------------------------
REM Step 3: Replace C:\xampp\php  (full folder replace)
REM ------------------------------------------------------------------
echo.
echo [..] Replacing C:\xampp\php with project php folder...
REM Remove old php tree carefully (keep parent xampp)
if exist "%DST_PHP%" (
  rmdir /S /Q "%DST_PHP%" 2>nul
)
if exist "%DST_PHP%" (
  echo [ERROR] Could not remove old %DST_PHP%
  echo Close XAMPP, any PHP windows, antivirus locks, then re-run.
  pause
  exit /b 1
)

mkdir "%DST_PHP%" >nul
xcopy "%SRC_PHP%" "%DST_PHP%\" /E /I /H /Y /Q
if errorlevel 1 (
  echo [ERROR] Copy to C:\xampp\php failed.
  echo Restore from: %BACKUP_ROOT%\php
  pause
  exit /b 1
)
if not exist "%DST_PHP%\php.exe" (
  echo [ERROR] php.exe missing after copy.
  pause
  exit /b 1
)
echo [OK] C:\xampp\php replaced

REM Ensure logs + tmp dirs exist
if not exist "%DST_PHP%\logs" mkdir "%DST_PHP%\logs"
if not exist "%DST_PHP%\tmp" mkdir "%DST_PHP%\tmp"

REM ------------------------------------------------------------------
REM Step 4: Update apache\bin with ImageMagick + runtime DLLs
REM (Do NOT wipe entire apache\bin — that would break Apache.
REM  install_note says "replace apache\bin"; we merge required DLLs.)
REM ------------------------------------------------------------------
echo.
echo [..] Copying ImageMagick / PHP helper DLLs into C:\xampp\apache\bin ...

REM Core PHP / SSL / sodium libs often needed by Apache module
for %%D in (
  libsodium.dll
  libcrypto-3-x64.dll
  libssl-3-x64.dll
  libsqlite3.dll
  libssh2.dll
  nghttp2.dll
  php8ts.dll
  php8apache2_4.dll
  glib-2.dll
  gmodule-2.dll
) do (
  if exist "%SRC_PHP%\%%D" copy /Y "%SRC_PHP%\%%D" "%DST_APACHE_BIN%\%%D" >nul
)

REM ImageMagick CORE + FILTER + IM_MOD DLLs (required for imagick under Apache)
for %%F in ("%SRC_PHP%\CORE_RL_*.dll") do copy /Y "%%~fF" "%DST_APACHE_BIN%\" >nul
for %%F in ("%SRC_PHP%\FILTER_*.dll") do copy /Y "%%~fF" "%DST_APACHE_BIN%\" >nul
for %%F in ("%SRC_PHP%\IM_MOD_RL_*.dll") do copy /Y "%%~fF" "%DST_APACHE_BIN%\" >nul
if exist "%SRC_PHP%\ImageMagickObject.dll" copy /Y "%SRC_PHP%\ImageMagickObject.dll" "%DST_APACHE_BIN%\" >nul
if exist "%SRC_PHP%\php_imagick.dll" copy /Y "%SRC_PHP%\php_imagick.dll" "%DST_APACHE_BIN%\" >nul

echo [OK] apache\bin updated with helper DLLs

REM ------------------------------------------------------------------
REM Step 5: Quick PHP check
REM ------------------------------------------------------------------
echo.
echo [..] Checking PHP version / extensions...
"%DST_PHP%\php.exe" -v
echo.
"%DST_PHP%\php.exe" -m 2>nul | findstr /I "sodium openssl imagick mysqli pdo_mysql zip gd mbstring curl"
echo.

echo ================================================================
echo  DONE
echo ================================================================
echo  Backup:  %BACKUP_ROOT%
echo.
echo  Next steps:
echo    1) Open XAMPP Control Panel as Administrator
echo    2) Start Apache + MySQL
echo    3) If Apache fails, restore apache\bin from backup and re-copy DLLs only
echo    4) Put Laravel project under:
echo         C:\xampp\htdocs\visitor-pass\
echo       (or link document root to the app public folder)
echo    5) Open phpMyAdmin: http://localhost/phpmyadmin
echo    6) Create database / user as in install_note.txt
echo    7) Browse the app installer URL
echo.
echo  PHP path: C:\xampp\php\php.exe
echo  App public example:
echo    http://localhost/visitor-pass/public/
echo ================================================================
echo.
pause
exit /b 0
