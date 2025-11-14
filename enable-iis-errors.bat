@echo off
REM Enable detailed errors in IIS for bkuteam site

echo Configuring IIS to show detailed errors...

cd C:\Windows\System32\inetsrv

REM Enable detailed errors for the site
appcmd.exe set config "bkuteam.site" -section:system.webServer/httpErrors /existingResponse:"PassThrough" /commit:apphost

REM Set FastCGI error mode
appcmd.exe set config -section:system.webServer/fastCgi /+"[fullPath='C:\php\php-cgi.exe'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS',value='10000']" /commit:apphost

REM Enable stderr logging for PHP
appcmd.exe set config -section:system.webServer/fastCgi /+"[fullPath='C:\php\php-cgi.exe'].environmentVariables.[name='PHPRC',value='C:\php']" /commit:apphost

echo.
echo IIS configured. Restarting...
iisreset

echo.
echo Done! Now try accessing the PHP files again.
pause
