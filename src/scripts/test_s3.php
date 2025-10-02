<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    echo "FILESYSTEM_DISK=" . env('FILESYSTEM_DISK') . PHP_EOL;
    echo "AWS_BUCKET=" . env('AWS_BUCKET') . PHP_EOL;
    echo "S3 config: " . var_export(config('filesystems.disks.s3'), true) . PHP_EOL;

    $path = Storage::disk('s3')->putFile('products', new \Illuminate\Http\File(__DIR__ . '/../tests/Fixtures/example.png'));
    echo "PUT PATH: " . var_export($path, true) . PHP_EOL;
    try {
        $url = Storage::disk('s3')->url($path);
        echo "URL: " . var_export($url, true) . PHP_EOL;
    } catch (\Exception $e) {
        echo "URL ERROR: " . $e->getMessage() . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
