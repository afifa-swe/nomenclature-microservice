<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create {email} {--name=} {--password=}', function (string $email, ?string $name, ?string $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Invalid email format.');
        return self::FAILURE;
    }

    if (User::where('email', $email)->exists()) {
        $this->error("User with email {$email} already exists.");
        return self::FAILURE;
    }

    $name = $name ?: explode('@', $email)[0];
    $generated = false;
    if (!$password) {
        $password = Str::random(12);
        $generated = true;
    }

    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);

    $this->info('User created successfully:');
    $this->line("  ID: {$user->id}");
    $this->line("  Name: {$user->name}");
    $this->line("  Email: {$user->email}");
    $this->line('  Password: ' . ($generated ? "{$password} (generated)" : '****** (provided)'));

    return self::SUCCESS;
})->purpose('Create a user without using Tinker');

Artisan::command('s3:ensure-bucket {bucket?}', function ($bucket = null) {
    $bucket = $bucket ?? config('filesystems.disks.s3.bucket') ?? env('AWS_BUCKET');
    if (!$bucket) {
        $this->error('Bucket name not configured');
        return self::FAILURE;
    }

    $config = config('filesystems.disks.s3');
    $endpoint = $config['endpoint'] ?? env('AWS_ENDPOINT');

    $s3Config = [
        'version' => 'latest',
        'region' => $config['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
        'credentials' => [
            'key' => $config['key'] ?? env('AWS_ACCESS_KEY_ID'),
            'secret' => $config['secret'] ?? env('AWS_SECRET_ACCESS_KEY'),
        ],
    ];

    if ($endpoint) {
        $s3Config['endpoint'] = $endpoint;
        $s3Config['use_path_style_endpoint'] = $config['use_path_style_endpoint'] ?? env('AWS_USE_PATH_STYLE_ENDPOINT', true);
    }

    $client = new \Aws\S3\S3Client($s3Config);

    try {
        $exists = false;
        try {
            $client->headBucket(['Bucket' => $bucket]);
            $exists = true;
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $exists = false;
        }

        if ($exists) {
            $this->info("Bucket '{$bucket}' already exists");
            return self::SUCCESS;
        }

        $client->createBucket(['Bucket' => $bucket]);
        $client->waitUntil('BucketExists', ['Bucket' => $bucket]);
        $this->info("Bucket '{$bucket}' created");
        return self::SUCCESS;
    } catch (\Exception $e) {
        $this->error('Error ensuring bucket: ' . $e->getMessage());
        return self::FAILURE;
    }
});
