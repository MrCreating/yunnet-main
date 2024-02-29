# `api` - код API юнта: https://api.yunnet.ru/

# Применение
Используется в приложении для Android, скриптах или ботах

# Формат запроса
`https://api.yunnet.ru/${method_group}.${method_name}?key={$access_key}&{p1}=${v1}&{p2}={v2}&...`

Тип ответа: JSON

# Файлы
`index.php` - входная точка. Содержит вызов класса `API`, в котором происходит авторизация и вызывает переданный ему метод API

# Структура методов
`methods/${method_group}/${method_name}.php`, где `${method_group}` - группа методов (messages, users, etc), а `${method_name}` - имя метода (messages.send, users.get)

# Структура файла метода API
Это две переменных и 1 функция:
`$method_permissions_group int [0, 4]` - число от 0 до 4 обозначающее группу прав для переданныого токена
`$method_params array` - массив параметров метода, имеет следующую структуру `$param_name => ['required' => 1 || 0, 'type' => 'string' || 'integer' || 'json']`
  1. `$param_name` - имя параметра.
  2. `$type` - тип данных (строка, число или JSON)
  3. `$required` - обязателен параметр или нет
`call (API $api, array $params): APIResponse|APIException` - функция, вызывается при выполнении всех условий выше и наличии прав. Содержит саму логику методв и должна вернуть экземпляр класса `APIResponse` или `APIException`.
