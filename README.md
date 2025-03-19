# REST API для управления задачами

Этот проект представляет собой REST API для управления списком задач. API позволяет создавать, просматривать, обновлять и удалять задачи. Документация API генерируется с помощью Swagger (swagger-php аннотации). Проект разработан с использованием PHP 8.2, PDO и поддерживается принципами DRY, KISS, SOLID, PSR и MVC.

[!] Slim Framework есть в проекте, но ведь в ТЗ не было сказано за разработку на чистом PHP

## Содержание
- [Требования](#требования)
- [Установка и настройка](#установка-и-настройка)
  - [Установка PHP и Composer](#установка-php-и-composer)
  - [Установка зависимостей](#установка-зависимостей)
  - [Настройка базы данных](#настройка-базы-данных)
  - [Запуск в Docker](#запуск-в-docker)
- [Использование API](#использование-api)
- [Тестирование](#тестирование)
- [Документация Swagger](#документация-swagger)

## Стек

- **PHP 8.2**
- **Composer** 
- **Docker** и **Docker Compose** 
- **MySQL** 

## Установка и настройка

### Установка PHP и Composer

Установим PHP на Linux =D:
```bash
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-common php8.2-pdo php8.2-mysql php8.2-json php8.2-mbstring
```

Установим Composer:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### Установка зависимостей

В корневой директории проекта выполните команду:
```bash
composer install
```

Это установит все необходимые библиотеки, включая Slim Framework, swagger-php, PHPUnit и т.д.

### Настройка базы данных

1. Создайте базу данных (например, `tasks_db`) и пользователя с необходимыми правами.
2. Отредактируйте файл `app/Config/database.php` и укажите актуальные данные для подключения к базе данных:
   ```php
   $host = 'localhost';
   $dbname = 'tasks_db';
   $username = 'db_user';
   $password = 'db_password';
   ```
3. Создайте таблицу `tasks` согласно схеме, описанной в модели (или выполните SQL-скрипт для миграции).

### Запуск в Docker

Если вы предпочитаете использовать Docker, убедитесь, что Docker и Docker Compose установлены.

1. Проверьте содержимое `docker/Dockerfile` и `docker/docker-compose.yml`.
2. В корневой директории выполните:
   ```bash
   docker-compose up --build
   ```
3. Приложение будет доступно по адресу [http://localhost:8080](http://localhost:8080).


## Использование API

### Создадим задачу `POST /api/tasks`

```
curl -X POST http://localhost:8080/api/tasks \
     -H "Content-Type: application/json" \
     -d '{
           "title": "Пример задачи",
           "description": "Описание задачи",
           "due_date": "2025-01-20T15:00:00",
           "create_date": "2025-01-20T15:00:00",
           "status": "не выполнена",
           "priority": "высокий",
           "category": "Работа"
         }'
```

### Получим список задач `GET /api/tasks`

```
curl -X GET "http://localhost:8080/api/tasks?search=Задача1&sort=due_date&page=1&limit=10"
```

### Или одну задачу... `GET /api/tasks/{id}`

```
curl -X GET http://localhost:8080/api/tasks/1
```

### Обновим задачу `PUT /api/tasks/{id}`

```
curl -X PUT http://localhost:8080/api/tasks/1 \
     -H "Content-Type: application/json" \
     -d '{
           "title": "Обновленная задача",
           "description": "Обновленное описание",
           "due_date": "2025-01-25T18:00:00",
           "status": "выполнена",
           "priority": "низкий"
         }'
```

### А можно и удалить задачу `DELETE /api/tasks/{id}`=) 

```
curl -X DELETE http://localhost:8080/api/tasks/1
```

## Тестирование

Для запуска юнит-тестов используется PHPUnit. Запуск:
```bash
./vendor/bin/phpunit --configuration tests/phpunit.xml
```

## Документация Swagger

Документация API генерируется автоматически на основе аннотаций, прописанных в коде контроллеров.
