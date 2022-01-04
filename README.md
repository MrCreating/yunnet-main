# yunnet-main (yunNet.)
Социальная сеть наверное?
Исходный код проекта, ничего личного :)

# Сайт
* https://yunnet.ru
* https://m.yunnet.ru
* https://api.yunnet.ru
* https://auth.yunnet.ru?app_id=11&permissions=1,2,3,4

# Разделение директорий проекта
* `api` - исходный код API проекта: https://api.yunnet.ru/
* `bin` - ядро (движок - untEngine), здесь вся логика всех процессов
* `dev` - сайт для разработчиков (документация API): https://dev.yunnet.ru/
* `icons` - некоторые иконки, созданные самостоятельно (SVG)
* `lib` - мелкие вспомогательные скрипты (например очистка кеша) или сторонние библиотеки
* `local` - локальные конфиги для nginx, PHP, и SSL сертификаты
* `pages` - основной сайт: https://yunnet.ru/
* `public` - точка "входа", отсюда начинаются все запррсы
* `tests` - тесты :)

# Запуск
- Установить git
- Выполнить `git clone https://github.com/MrCreating/yunnet-main`
- Windows
	- Установить `Docker Desktop`
	- `start.cmd` - просто щапустите его :)
	- Открыть `localhost`
- Linux
	- Выполнить `apt-get update && apt-get install docker docker-compose`
	- Перейти в `config/local`: `cd config/local`
	- Выполнить `docker-compose up`
	- Открыть `localhost`