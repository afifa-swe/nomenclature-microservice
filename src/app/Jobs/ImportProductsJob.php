<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
// Removed maatwebsite/excel usage: we'll use native fgetcsv to read all rows reliably

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;
    protected $userId;

    public function __construct(string $path, $userId = null)
    {
        $this->path = $path;
        $this->userId = $userId;
    }

    public function handle()
    {
        $fullPath = storage_path('app/' . $this->path);
        Log::info('ImportProductsJob: start', ['path' => $this->path, 'fullPath' => $fullPath, 'exists' => file_exists($fullPath)]);

        // Read entire CSV via native fgetcsv to avoid Excel::toArray inconsistencies
        $rows = [];
        if (! file_exists($fullPath) || ! is_readable($fullPath)) {
            Log::error('ImportProductsJob: file missing or not readable', ['fullPath' => $fullPath]);
            return;
        }

        if (($handle = fopen($fullPath, 'r')) === false) {
            Log::error('ImportProductsJob: cannot open file for reading', ['fullPath' => $fullPath]);
            return;
        }

        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        Log::info('ImportProductsJob: read (fgetcsv)', ['rows' => count($rows)]);

        if (empty($rows)) {
            Log::info('ImportProductsJob: no rows found', ['path' => $this->path]);
            return;
        }

        // header and normalization
        $rawHeader = $rows[0];
        $headerRow = array_map(fn($h) => mb_strtolower(trim((string)$h)), $rawHeader);
        Log::info('ImportProductsJob: header', ['header' => $headerRow]);

    $dataRows = array_slice($rows, 1);
    $total = count($dataRows);
    $created = 0;
        foreach ($dataRows as $i => $row) {
            try {
                if (count($headerRow) !== count($row)) $row = array_pad($row, count($headerRow), null);
                $assoc = @array_combine($headerRow, $row);
                if ($assoc === false || $assoc === null) {
                    Log::warning('ImportProductsJob: array_combine failed', ['index' => $i, 'row' => $row]);
                    continue;
                }

                $name = $assoc['name'] ?? null;
                if (empty($name)) { Log::warning('ImportProductsJob: skipping empty name', ['index'=>$i]); continue; }

                $categoryId = $assoc['category_id'] ?? null;
                $supplierId = $assoc['supplier_id'] ?? null;

                $isUuid = fn($v) => !empty($v) && preg_match('/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/', $v);

                if (! $isUuid($categoryId)) {
                    $existing = Category::first();
                    $categoryId = $existing ? $existing->id : Category::create(['name'=>'Imported default'])->id;
                }
                if (! $isUuid($supplierId)) {
                    $existing = Supplier::first();
                    $supplierId = $existing ? $existing->id : Supplier::create(['name'=>'Imported default'])->id;
                }

                // create product record (one product per CSV row)
                Product::create([
                    'name' => $name,
                    'description' => $assoc['description'] ?? null,
                    'category_id' => $categoryId,
                    'supplier_id' => $supplierId,
                    'price' => isset($assoc['price']) ? (float)$assoc['price'] : 0,
                    'file_url' => $assoc['file_url'] ?? null,
                    'is_active' => true,
                    'created_by' => $this->userId !== null ? (string)$this->userId : null,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('ImportProductsJob: failed row', ['index'=>$i,'error'=>$e->getMessage()]);
                continue;
            }
        }

        Log::info('ImportProductsJob: done', ['path'=>$this->path, 'total'=>$total, 'created'=>$created]);
    }
}
