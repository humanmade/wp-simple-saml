@echo off

SET app=%0
SET lib=%~dp0

php "%lib%sniff.php" %*

echo.

exit /B %ERRORLEVEL%
