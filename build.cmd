echo Start building the Main project ...
cd unt_2
set PWD=%cd%

mkdir docker\context\memcached
mkdir docker\context\nginx
mkdir docker\context\poll_engine
mkdir docker\context\text_engine

docker-compose build --no-cache