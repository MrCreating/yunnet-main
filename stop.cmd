@echo off
echo Init the project ...
cd config\local
set PWD=%cd%
set UNT_PRODUCTION=0
docker-compose down
pause