@echo off
REM Test PHP files directly via CLI to see actual errors

cd C:\inetpub\wwwroot\bkuteam.site\php\api\auth

echo ============================================
echo Testing sync-user-debug.php via CLI
echo ============================================
echo.

REM Simulate POST request
echo {"idToken":"test","user":{"uid":"test123","email":"test@example.com","displayName":"Test User","emailVerified":true,"provider":"password"}} > test-input.json

php -d display_errors=1 -d error_reporting=E_ALL sync-user-debug.php < test-input.json

echo.
echo ============================================
echo If you see PHP errors above, that's the issue!
echo ============================================
echo.

pause

del test-input.json
