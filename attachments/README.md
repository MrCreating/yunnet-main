# attachments
Хранилище вложений пользователей.

Структура каталогов пользователей: `attachments/${entity_id}/${attachment_type}/${attachment_id}`, где:

1. `${entity_id}` - идентификатор сущности
2. `${attachment_type}` - тип вложения. Все типы вложений прописаны в UntEngine
3. `${attachment_id}` - общий идентификатор вложения. Обычно передается с общей строкой вложения

Здесь содержится реализация сервера вложений и тем.
