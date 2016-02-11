@ECHO OFF

ECHO PHP version is ...
ECHO.
php --version
ECHO.

REM the "phpdoc.dist.xml" file will automatically be used for configuration
php bin\phpDocumentor\phpDocumentor.phar

PAUSE