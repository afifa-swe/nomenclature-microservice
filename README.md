# Nomenclature microservice (Номенклатура)

Оригинал: Laravel 12 (LTS-like) микросервис для работы с номенклатурой (товары, категории, поставщики, история изменений). Документ описывает, как поднять проект в контейнерах, получить токен и пройти CRUD-эндпоинты и импорт CSV.

## Содержание

- [Краткое описание](#краткое-описание)
- [Что делает сервис](#что-делает-сервис)
- [Ключевые технологии](#ключевые-технологии)
- [Структура проекта](#структура-проекта)
- [Быстрый старт (TL;DR)](#быстрый-старт-tldr)
- [Переменные окружения (.env)](#переменные-окружения-env)
- [Запуск и команды](#запуск-и-команды)
- [Доступные сервисы и порты](#доступные-сервисы-и-порты)
- [Аутентификация (Laravel Passport)](#аутентификация-laravel-passport)
- [MinIO (S3) настройка](#minio-s3-настройка)
- [Загрузка изображений](#загрузка-изображений)
- [Импорт CSV и очереди](#импорт-csv-и-очереди)
- [Миграции и сидеры](#миграции-и-сидеры)
- [Формат JSON-ответов](#формат-json-ответов)
- [API эндпоинты (кратко)](#api-эндпоинты-кратко)
- [Postman коллекция](#postman-коллекция)
- [Траблшутинг (частые проблемы)](#траблшутинг-частые-проблемы)
- [Полезные команды / скрипты](#полезные-команды--скрипты)
- [Лицензия](#лицензия)

## Краткое описание

Микросервис предоставляет REST API для управления товарами (products), категориями (categories), поставщиками (suppliers) и логами изменений (change_logs). Все ответы возвращаются в едином JSON-формате с полями: message, data, timestamp, success.

Проект содержит Docker Compose для локальной разработки: PostgreSQL, pgAdmin, RabbitMQ (management), MinIO (S3), Nginx + PHP-FPM (контейнеры `web` и `app`).

## Что делает сервис

- CRUD для товаров, категорий и поставщиков.
- Soft-delete для товаров (is_active=false).
- Загрузка изображений товаров в S3 (MinIO) и возвращение публичного file_url.
- Импорт товаров из CSV — загрузка файла через /api/products/import, задача ставится в очередь, обработка батчами по 100 записей.
- Запись событий (create/update/delete) в таблицу change_logs.

## Ключевые технологии

- PHP 8.2, Laravel 12
- PostgreSQL 15
- Laravel Passport (OAuth2) — для доступа к API
- Очереди: database / RabbitMQ via vladimir-yuldashev/laravel-queue-rabbitmq
- Файловое хранилище: MinIO (S3), league/flysystem-aws-s3-v3
- Импорт CSV: maatwebsite/excel
- Docker Compose, Nginx, PHP-FPM, pgAdmin, RabbitMQ Management
- Postman коллекция для ручного тестирования

## Структура проекта (основные каталоги)

Корень проекта:

- src/ — Laravel приложение
  - app/ — контроллеры, модели, Jobs
  - routes/ — api.php, web.php
  - database/ — migrations, seeders, factories
  - public/ — public entrypoint
  - config/ — конфигурации (filesystems, queue и т.д.)
- postman/ — Postman коллекция
- docker-compose.yml, Dockerfile, nginx/nginx.conf

(Полная структура — в корне рабочей директории, откройте `src/` для кода приложения.)

## Быстрый старт (TL;DR)

1. Скопировать .env:

```bash
cp src/.env.example src/.env
# или если вы работаете с уже добавленным .env в репо — убедитесь что значения верны
```

2. Отредактировать ключевые переменные (см. раздел ниже).

3. Поднять контейнеры:

```bash
docker compose up -d --build
```

4. Генерировать ключ приложения и выполнить миграции/сиды и ссылку на storage:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
```

5. Запустить воркер очередей (если используете RabbitMQ/очереди):

```bash
docker compose exec app php artisan queue:work
```

Теперь API доступно по адресу, указанному в Postman коллекции ({{base_url}}).

## Переменные окружения (.env) — ключевые

Из примера `src/.env.example` и текущего `src/.env` проект реально использует следующие переменные (не полный список):

- APP_URL — базовый URL приложения (например http://localhost)
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- QUEUE_CONNECTION (в `src/.env` по умолчанию `rabbitmq`)
- RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASSWORD, RABBITMQ_VHOST
- FILESYSTEM_DISK (обычно s3)
- AWS_ENDPOINT, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET, AWS_DEFAULT_REGION, AWS_USE_PATH_STYLE_ENDPOINT=true
- PASSPORT_CLIENT_ID, PASSPORT_CLIENT_SECRET (client для password grant — уже добавлены в `src/.env`)

Обратите внимание: в `src/.env` уже прописаны значения для PostgreSQL/MinIO/RabbitMQ, подходящие для docker-compose в этом проекте.

## Запуск и команды

Основные команды уже указаны в Быстром старте. Дополнительно:

- Остановить:

```bash
docker compose down
```

- Очистить кеши Laravel:

```bash
docker compose exec app php artisan optimize:clear
```

- Пересоздать БД и заполнить снова:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

- Просмотреть миграции/маршруты:

```bash
docker compose exec app php artisan route:list
```

- Открыть tinker и проверить количество записей (пример):

```bash
docker compose exec app php artisan tinker --execute="echo 'products='.\DB::table('products')->count().PHP_EOL; echo 'suppliers='.\DB::table('suppliers')->count().PHP_EOL; echo 'categories='.\DB::table('categories')->count().PHP_EOL; echo 'change_logs='.\DB::table('change_logs')->count().PHP_EOL;"
```

## Доступные сервисы и порты (из docker-compose)

- API (Nginx) — http://localhost:8080 (контейнер `web` проброшен порт 8080 -> 80)
- PostgreSQL — 5425 (хост:localhost порт:5425 -> контейнер db:5432)
- pgAdmin — http://localhost:5050 (логин admin@example.com / admin)
- RabbitMQ Management — http://localhost:15672 (guest/guest)
- RabbitMQ AMQP — 5672 (guest/guest)
- MinIO Console — http://localhost:9001 (minioadmin/minioadmin)
- MinIO S3 API — http://localhost:9000

## Аутентификация (Laravel Passport)

Проект использует Laravel Passport для oauth2 password grant. В `src/.env` уже присутствуют значения:

- PASSPORT_CLIENT_ID
- PASSPORT_CLIENT_SECRET

Если нужно создать client вручную, используйте:

```bash
# внутри контейнера app
docker compose exec app php artisan passport:client --password
# сохраните client id и secret
```

Пример запроса токена (curl):

```bash
curl -X POST http://localhost:8080/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "<PASSPORT_CLIENT_ID>",
    "client_secret": "<PASSPORT_CLIENT_SECRET>",
    "username": "admin@example.com",
    "password": "password",
    "scope": "*"
  }'
```

В Postman в `postman/Nomenclature.postman_collection.json` есть запрос `token`, который делает тот же POST и сохраняет полученный access_token в переменную `{{token}}`. Все остальные запросы используют Bearer-авторизацию с этой переменной.

## MinIO (S3) настройка

В `src/.env` настроено использование MinIO через S3:

- AWS_ENDPOINT=http://minio:9000
- AWS_ACCESS_KEY_ID=minioadmin
- AWS_SECRET_ACCESS_KEY=minioadmin
- AWS_BUCKET=local
- AWS_DEFAULT_REGION=us-east-1
- AWS_USE_PATH_STYLE_ENDPOINT=true

Обратите внимание: важно установить AWS_USE_PATH_STYLE_ENDPOINT=true при работе с MinIO (путь-style).

## Загрузка изображений

- Endpoint: POST /api/products/upload
- Ожидается form-data с ключом `image` (file).
- Ограничения: mimes: jpg,jpeg,png, max: 5MB (5120 KB) — валидация в контроллере.
- Файлы сохраняются через Storage::disk('s3')->putFile('products', $file) и возвращается публичный `file_url`.

Проблемы с 413 Request Entity Too Large решаются правкой `nginx/nginx.conf` (client_max_body_size) и php.ini (upload_max_filesize/post_max_size) в контейнере `app`.

## Импорт CSV и очередь

- Endpoint: POST /api/products/import — form-data (file)
- Контроллер сохраняет файл в storage (store('imports')) и ставит в очередь ImportProductsJob::dispatch($path)
- ImportProductsJob использует Maatwebsite/Excel::toArray и обрабатывает строки, батчами по 100.
- Пример ограничений: max: 5MB

Как запустить обработку:

```bash
docker compose exec app php artisan queue:work
# или если используете supervisord/другой подход — запустите соответствующий воркер
```

Логи очереди и общие ошибки смотрите в `src/storage/logs/laravel.log`.

## Миграции и сидеры

- Миграции создают таблицы: users, categories, suppliers, products, change_logs, jobs, failed_jobs и т.д. (см. `src/database/migrations`)
- Seeder (DatabaseSeeder) вызывает:
  - UserSeeder (создаёт admin@example.com / password)
  - CategorySeeder (~30–50 категорий: 10 parents + children + 5)
  - SupplierSeeder (100)
  - ProductSeeder (5000)
- Команда для наполнения: `docker compose exec app php artisan db:seed --force`

## Формат JSON-ответов

Все успешные ответы имеют вид:

```json
{
  "message": "...",
  "data": { /* данные или коллекция */ },
  "timestamp": "2025-...T...Z",
  "success": true
}
```

Ошибки валидации возвращают 422 и структуру:

```json
{
  "message": "Ошибка валидации",
  "data": { "field": ["error1", "error2"] },
  "timestamp": "...",
  "success": false
}
```

Это реализовано в FormRequest->failedValidation и в контроллерах через единые helper-методы `ok(...)`.

## API эндпоинты (кратко)

Все эндпоинты защищены Passport (auth:api). Базовый префикс: /api

Products
- GET /api/products — список (query: per_page, search, category_id, supplier_id)
- GET /api/products/{id} — получить товар
- POST /api/products — создать
  - Обязательные поля (StoreProductRequest): name (string), category_id (uuid), supplier_id (uuid), price (numeric)
  - Пример:

```json
{
  "name": "Example product",
  "sku": "EX-001",
  "price": 100.5,
  "category_id": "d52e26a5-ffa7-48d0-bd86-a43e506e14a3",
  "supplier_id": "7f98b2f5-5901-4176-ad88-dedf913d43ff",
  "is_active": true
}
```

- PUT /api/products/{id} — обновить (UpdateProductRequest: все поля optional)
- DELETE /api/products/{id} — soft delete (sets is_active=false)
- POST /api/products/upload — загрузка изображения (form-data key `image` file)
  - Ограничение размера 5MB; mimes: jpg,jpeg,png
  - Настройка nginx/php для 413 см. Траблшутинг
- POST /api/products/import — импорт CSV (file) → кладёт в очередь ImportProductsJob

Categories
- GET /api/categories — список (per_page, parent_id, search)
- GET /api/categories/{id}
- POST /api/categories — name (required), parent_id (nullable uuid)
- PUT /api/categories/{id}
- DELETE /api/categories/{id}

Suppliers
- GET /api/suppliers — список (per_page, search)
- GET /api/suppliers/{id}
- POST /api/suppliers — name (required), email (nullable)
- PUT /api/suppliers/{id}
- DELETE /api/suppliers/{id}

ChangeLogs
- GET /api/changes — список
  - Фильтры: entity_type, action, user_id, per_page, sort (asc/desc)

Примеры успешного ответа уже были выше (см. Формат JSON-ответов).

## Postman коллекция

Коллекция лежит в `postman/Nomenclature.postman_collection.json`.

Переменные окружения для Postman:
- base_url (например http://localhost:8080)
- client_id
- client_secret
- token (останавливается автоматом запросом `token` в коллекции)

Инструкция: импортируйте коллекцию в Postman, создайте окружение с `base_url` и `client_id`/`client_secret`, запустите запрос `token` (он сохранит {{token}}), далее запускайте запросы из коллекции. В Runner можно прогнать тесты.

## Траблшутинг (частые проблемы и решения)

- Auth guard [api] is not defined — в `src/config/auth.php` проверьте, что guard `api` использует driver `passport`.

- Редирект на /login вместо JSON (страницы) — проект добавляет named route 'login' в `routes/web.php`, и глобальный middleware `JsonResponseMiddleware` в `bootstrap/app.php`.

- 413 Request Entity Too Large — увеличьте в `nginx/nginx.conf` client_max_body_size (по умолчанию в проекте 20M) и в php.ini увеличьте upload_max_filesize/post_max_size; затем пересоберите контейнеры.

- Flysystem S3: PortableVisibilityConverter not found — установите совместимую версию `league/flysystem-aws-s3-v3` (в composer.json уже указан), выполните `composer install`.

- MinIO: GetObject Key length 0 — проверьте, что `AWS_BUCKET`, `AWS_ENDPOINT` и `AWS_USE_PATH_STYLE_ENDPOINT=true` заданы и bucket создан в MinIO.

- Импорт CSV не обрабатывается — убедитесь, что работает воркер очереди (`php artisan queue:work`), и что `QUEUE_CONNECTION` настроен (rabbitmq или database). Посмотрите логи в `storage/logs/laravel.log`.

- Предупреждение колляции PostgreSQL — если встречается ошибка с collation, можно выполнить REFRESH COLLATION в БД или изменить локаль БД; это специфично для окружения.

## Полезные команды / скрипты

- docker compose up -d --build
- docker compose exec app php artisan key:generate
- docker compose exec app php artisan migrate --force
- docker compose exec app php artisan db:seed --force
- docker compose exec app php artisan storage:link
- docker compose exec app php artisan queue:work
- docker compose exec app php artisan tinker
- docker compose exec app php artisan route:list
- docker compose exec app php artisan optimize:clear
- docker compose exec app php artisan migrate:fresh --seed

## Лицензия

MIT

---

Файл README.md создан автоматически на основании структуры репозитория. Добавил подробную инструкцию по запуску, переменные окружения, описание API, очередей, MinIO, Postman и траблшутинг.
