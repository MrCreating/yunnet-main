@echo off
echo Init the project ...
cd unt_2
set PWD=%cd%
set UNT_PRODUCTION=0
docker-compose up -d --remove-orphans