@echo off
REM Test PHP in VPS terminal

echo Testing PHP installation...
php -v

echo.
echo Testing PHP syntax...
cd C:\inetpub\wwwroot\bkuteam.site\php
php -l debug.php

echo.
echo Testing PHP execution...
php debug.php

pause
