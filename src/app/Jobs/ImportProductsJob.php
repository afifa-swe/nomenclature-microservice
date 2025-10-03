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

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected array $chunk;
    protected $userId;

    public function __construct(array $chunk, $userId = null)
    {
        $this->chunk = $chunk;
        $this->userId = $userId;
        $this->connection = 'rabbitmq';
        $this->queue = 'imports';
    }

    public function handle()
    {
        Log::info('Start processing chunk', ['rows_in_chunk' => count($this->chunk)]);

        foreach ($this->chunk as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $product = Product::create([
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'category_id' => $row['category_id'] ?? null,
                'supplier_id' => $row['supplier_id'] ?? null,
                'price' => $row['price'] ?? 0,
                'file_url' => $row['file_url'] ?? null,
                'is_active' => true,
                'created_by' => $this->userId,
            ]);

            Log::info('Product created from chunk', ['id' => $product->id]);
        }

        Log::info('Finished processing chunk');
    }
}
