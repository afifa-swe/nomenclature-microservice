# MinIO bucket setup and testing

If you run the project with Docker Compose (recommended), MinIO runs in the `minio` service and listens on port 9000.

Create the `products` bucket (once) from within the `app` container:

    docker compose up -d
    docker compose exec app php artisan s3:ensure-bucket products

Notes:
- The command `php artisan s3:ensure-bucket` uses the S3 configuration from `config/filesystems.php` / `.env` and will create the bucket when missing.
- If you run artisan from your host (not inside containers), make sure `AWS_ENDPOINT` points to `http://localhost:9000` and MinIO is reachable from the host.

Quick Tinker test (inside the `app` container):

    docker compose exec app php artisan tinker --execute "Storage::disk('s3')->putFile('products', new \\Illuminate\\Http\\File('tests/Fixtures/example.png'))"

This should print a path (e.g. `products/abc123.png`). Then you can get URL with:

    docker compose exec app php artisan tinker --execute "echo Storage::disk('s3')->url('products/abc123.png')"
