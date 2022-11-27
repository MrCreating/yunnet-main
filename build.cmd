echo Start building the Main project ...
cd unt_2
set PWD=%cd%
set UNT_PRODUCTION=0

mkdir docker\context\memcached
mkdir docker\context\nginx
mkdir docker\context\poll_engine
mkdir docker\context\text_engine

docker-compose build