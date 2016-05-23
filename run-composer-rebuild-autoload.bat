@ECHO OFF

ECHO PHP version is ...
ECHO.
php --version
ECHO.

REM bin\composer\composer config -g -- disable-tls true

php bin\composer\composer.phar dump-autoload

PAUSE