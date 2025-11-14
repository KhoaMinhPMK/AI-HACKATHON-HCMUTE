@echo off
REM Check PHP syntax errors

cd C:\inetpub\wwwroot\bkuteam.site\php

echo Checking PHP syntax errors...
echo.

echo [1/5] Checking config/database.php...
php -l config\database.php
echo.

echo [2/5] Checking helpers/response.php...
php -l helpers\response.php
echo.

echo [3/5] Checking helpers/validator.php...
php -l helpers\validator.php
echo.

echo [4/5] Checking api/auth/sync-user.php...
php -l api\auth\sync-user.php
echo.

echo [5/5] Checking api/auth/sync-user-debug.php...
php -l api\auth\sync-user-debug.php
echo.

echo ============================================
echo All files checked!
echo ============================================
pause
