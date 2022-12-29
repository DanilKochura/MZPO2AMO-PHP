# Интеграция с amoCRM

В основе интеграции - [API amoCRM](https://www.amocrm.ru/developers/content/crm_platform/api-reference) и их [PHP-бибиотека](https://github.com/amocrm/amocrm-api-php)

### Содержание
+ [Авторизация](#авторизация)
+ [Модели](#модели)
+ [Очереди](#очереди)
+ [Хуки](#хуки)
+ [API для 1с](#api-для-1с)
+ [Константы](#константы)
+ [Настройки](#настройки)


## Авторизация

**Авторизация** в API амо    происходит по протоколу OAuth2, 
замена и сохранение токенов происходят автоматически, эта процедура вшита в конструктор класса [MzpoAmo](/amo/model/MzpoAmo.php). 
При создании объектов его классов-наследников токен обновляется и сохраняется в базу.
Таким образом, для использования методов, не предусмотренных библиотекой, но предусмотренных API, можно обновить токен, создав объект MzpoAmo и получив токен из бд
```php 
new MzpoAmo();
$access_Token = getToken();
```
Для примера см. [Методы CourseService](/amo/services/CoursesServise.php)

___
## Модели
В папке [model](/amo/model) содержатся классы-обертки дял основных сущностей библиотеки, облегчающие работу с изменением их свойств:
+ [Контакт](/amo/model/Contact.php) - модель для работы с контактами
+ [Лид](/amo/model/Leads.php) - модель для работы с лидами
+ [Курс](/amo/model/Course.php) - модель для работы с курсами (товарами)
+ [Log](/amo/model/Log.php) - вспомогательная модель для логирования
Исключение составляет класс [CourseService](/amo/services/CoursesServise.php), который реализует функционал по удаленю курсов (элементов списка для API), которого нет в библиотеке текущей версии.
___

## Очереди

Все точки входа отправляют входящие запросы в очередь на [сервер](https://hawk.rmq.cloudamqp.com/#/). За отправку отвечает класс [QueueService](/amo/services/QueueService.php). Файлы из директории [queue](/amo/queue) - подписчики очередей:
+ [webhooks](/amo/queue/webhooks.php) - принимает сообщения о входящих лидах
+ [leads](/amo/queue/leads.php) - принимает сообщения о входящих хуках

Очереди используются для быстродействия - время ответа интеграции при отправке хука не должно превышать 2 секнуды.

___
## Хуки

[webhooks/index.php](/amo/webhooks/index.php) - точка входа для всех вебхуков amoCRM

___
## API для 1С

[api/index.php](/amo/api/index.php) - точка входа для всех методов интеграции с 1с

___
## Константы

  Директория [dict](/amo/dict) содержит интерфейсы-словари для хранения константных идентификаторов полей в системе:
+ [CustomFields](/amo/dict/CustomFields.php) - Кастомные поля заявок, контактов и курсов(товары)
+ [Pipelines](/amo/dict/Pipelines.php) - Идентификаторы всех воронок
+ [Statuses](/amo/dict/Statuses.php) - Идентификаторы статусов для воронок (основных, используемых)
+ [Tags](/amo/dict/Tags.php) - Идентификаторы тегов
___

## Настройки

Папка [config](/amo/config) содержит конфигурационные файлы: 
+ [БД](/amo/config/db.php) - класс для подключения и работы с БД
+ [Helpers](/amo/config/helpers.php) - вспомогательные методы для сохранения и получения токена авторизации из базы
+ [GetToken](/amo/config/getToken.php) - скрипт из примеров к документации библиотеки для первичного обмена ключа доступа на токены. Используется 1 раз - при регистрации интеграции и первом подключении к амо, оставлен как пример на всякицй случай

___

## Работа с кодом

Файл [index.php](/amo/index.php) - точка входа для работы с интеграцей с форм сайтов.


