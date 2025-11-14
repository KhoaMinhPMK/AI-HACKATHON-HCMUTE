REM =============================================
REM Enable PDO_MYSQL Extension
REM Run this in VPS PowerShell as Administrator
REM =============================================

@echo off
echo Enabling PDO_MYSQL extension...
echo.

REM Backup php.ini
copy C:\php\php.ini C:\php\php.ini.backup

REM Check if extension is commented
findstr /C:"extension=pdo_mysql" C:\php\php.ini
if %ERRORLEVEL% EQU 0 (
    echo Extension line exists, removing semicolon...
    powershell -Command "(Get-Content C:\php\php.ini) -replace ';extension=pdo_mysql', 'extension=pdo_mysql' | Set-Content C:\php\php.ini"
) else (
    echo Adding extension line...
    echo extension=pdo_mysql >> C:\php\php.ini
)

echo.
echo Restarting IIS...
iisreset

echo.
echo Done! Testing PHP extensions...
php -m | findstr pdo

echo.
echo Verification:
php -r "echo 'PDO drivers: ' . implode(', ', PDO::getAvailableDrivers());"

pause
