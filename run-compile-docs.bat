@ECHO OFF

ECHO PHP version is ...
ECHO.
php --version
ECHO.

php bin\phpDocumentor\phpDocumentor.phar -d src -t docs\api

PAUSE