<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

class EnsureS3Bucket extends Command
{
    protected $signature = 's3:ensure-bucket {bucket?}';
    protected $description = 'Ensure the given S3 bucket exists (creates it when missing)';

    public function handle()
    {
        $bucket = $this->argument('bucket') ?? config('filesystems.disks.s3.bucket') ?? env('AWS_BUCKET');
        if (!$bucket) {
            $this->error('Bucket name not configured');
            return 1;
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
            // ensure path style if configured
            $s3Config['use_path_style_endpoint'] = $config['use_path_style_endpoint'] ?? env('AWS_USE_PATH_STYLE_ENDPOINT', true);
        }

        $client = new S3Client($s3Config);

        try {
            $exists = false;
            try {
                $client->headBucket(['Bucket' => $bucket]);
                $exists = true;
            } catch (\Aws\S3\Exception\S3Exception $e) {
                // not exists
                $exists = false;
            }

            if ($exists) {
                $this->info("Bucket '{$bucket}' already exists");
                return 0;
            }

            $client->createBucket(['Bucket' => $bucket]);
            // wait until created
            $client->waitUntil('BucketExists', ['Bucket' => $bucket]);
            $this->info("Bucket '{$bucket}' created");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error ensuring bucket: ' . $e->getMessage());
            return 2;
        }
    }
}
