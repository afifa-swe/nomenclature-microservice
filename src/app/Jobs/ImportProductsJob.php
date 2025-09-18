<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle()
    {
        try {
            $fullPath = storage_path('app/' . $this->path);

            $import = new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            };

            $sheets = Excel::toArray($import, $fullPath);
            $rows = $sheets[0] ?? [];

            if (count($rows) === 0) {
                Log::info('ImportProductsJob: CSV is empty - ' . $this->path);
                return;
            }

            // Assume first row is header
            $headerRow = array_map(function ($h) {
                return mb_strtolower(trim($h));
            }, $rows[0]);

            $dataRows = array_slice($rows, 1);

            $chunks = array_chunk($dataRows, 100);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    try {
                        // Map row values to header keys
                        if (count($headerRow) !== count($row)) {
                            // Try to pad row to header length
                            $row = array_pad($row, count($headerRow), null);
                        }

                        $assoc = array_combine($headerRow, $row);
                        if ($assoc === false) {
                            continue;
                        }

                        $name = $assoc['name'] ?? null;
                        if (empty($name)) {
                            // skip rows without name
                            continue;
                        }

                        Product::create([
                            'name' => $name,
                            'description' => $assoc['description'] ?? null,
                            'category_id' => $assoc['category_id'] ?? null,
                            'supplier_id' => $assoc['supplier_id'] ?? null,
                            'price' => isset($assoc['price']) ? (float)$assoc['price'] : 0,
                            'file_url' => null,
                            'is_active' => true,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('ImportProductsJob: row import error - ' . $e->getMessage(), ['exception' => $e]);
                        continue;
                    }
                }
            }

            Log::info('ImportProductsJob: completed import - ' . $this->path);
        } catch (\Throwable $e) {
            Log::error('ImportProductsJob: failed - ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
