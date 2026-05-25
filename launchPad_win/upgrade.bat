@echo off
setlocal

REM =========================================================
REM SetupCase Upgrade Script
REM =========================================================

REM Current local folder
for %%I in ("%~dp0.") do set "TARGET_DIR=%%~fI"

REM Temp clone folder
set "TEMP_DIR=%TEMP%\setupcase_upgrade"

REM GitHub repo
set "REPO_URL=https://github.com/undoLogic/setupCase-core.git"

REM Remote folder name
set "REMOTE_FOLDER=launchPad_win"

echo.
echo ==========================================
echo FULL RESET + UPDATE
echo ==========================================
echo.
echo TARGET:
echo %TARGET_DIR%
echo.

pause

REM =========================================================
REM CREATE TARGET IF MISSING
REM =========================================================

if not exist "%TARGET_DIR%" (
    mkdir "%TARGET_DIR%"
)

REM =========================================================
REM CLEAN TARGET
REM =========================================================

echo.
echo Cleaning target directory...
echo.

for /d %%D in ("%TARGET_DIR%\*") do (
    if /I not "%%~nxD"==".git" (
        rmdir /S /Q "%%D"
    )
)

for %%F in ("%TARGET_DIR%\*") do (
    if /I not "%%~nxF"=="upgrade.bat" (
        if /I not "%%~nxF"=="settings.json" (
            if /I not "%%~nxF"=="config.json" (
                del /F /Q "%%F"
            )
        )
    )
)

REM =========================================================
REM CLEAN TEMP
REM =========================================================

if exist "%TEMP_DIR%" (
    rmdir /S /Q "%TEMP_DIR%"
)

REM =========================================================
REM CLONE REPO
REM =========================================================

echo.
echo Cloning repository...
echo.

git clone --filter=blob:none --sparse "%REPO_URL%" "%TEMP_DIR%"

if errorlevel 1 (
    echo.
    echo ERROR: Git clone failed.
    pause
    exit /b 1
)

cd /d "%TEMP_DIR%"

echo.
echo Configuring sparse checkout...
echo.

git sparse-checkout set %REMOTE_FOLDER%

if errorlevel 1 (
    echo.
    echo ERROR: Sparse checkout failed.
    pause
    exit /b 1
)

REM =========================================================
REM COPY CONTENTS ONLY
REM =========================================================

echo.
echo Copying updated files...
echo.

robocopy "%TEMP_DIR%\%REMOTE_FOLDER%" "%TARGET_DIR%" /E /XF settings.json config.json

REM =========================================================
REM CLEAN TEMP
REM =========================================================

echo.
echo Cleaning temporary files...
echo.

cd /d %TEMP%

rmdir /S /Q "%TEMP_DIR%"

echo.
echo ==========================================
echo UPDATE COMPLETE
echo ==========================================
echo.

pause