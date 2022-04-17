@echo off
echo Start building the project ...
cd config\local
set PWD=%cd%
set UNT_PRODUCTION=0
docker-compose build
pause