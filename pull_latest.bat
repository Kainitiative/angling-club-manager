@echo off
title Angling Club Manager - Pull Latest

echo ===============================
echo  Pulling latest from GitHub
echo ===============================
echo.

REM Move to the folder where this bat file lives
cd /d %~dp0

echo Current branch:
git branch --show-current
echo.

echo Fetching from origin...
git fetch origin
echo.

echo Pulling latest changes...
git pull origin main
echo.

echo Recent commits:
git log --oneline -5
echo.

echo Done.
echo.
pause
