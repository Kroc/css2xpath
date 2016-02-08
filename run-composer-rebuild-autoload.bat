@ECHO OFF

ECHO PHP version is ...
ECHO.
php --version
ECHO.

php bin\composer\composer.phar dump-autoload

PAUSE