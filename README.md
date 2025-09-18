# Nomenclature Microservice

## Описание
Микросервис для управления номенклатурой: товары, категории, поставщики и история изменений.  
Реализовано: полный CRUD, загрузка файлов (MinIO), импорт CSV через RabbitMQ, авторизация через Laravel Passport, единый JSON-формат ответов.

## Технологический стек
- Laravel LTS (PHP 8.2+)
- PostgreSQL
- RabbitMQ (очереди)
- MinIO (S3-совместимое хранилище)
- Laravel Passport (OAuth2)
- Laravel Excel (импорт CSV)
- Docker + Docker Compose
- pgAdmin
- Postman (коллекция запросов и тестов)

## Установка и запуск
```bash
git clone <repo_url>
cd nomenclature-microservice
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan passport:install
```
