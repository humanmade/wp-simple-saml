@echo off

SET app=%0
SET lib=%~dp0

php "%lib%tokenize.php" %*

echo.

exit /B %ERRORLEVEL%
