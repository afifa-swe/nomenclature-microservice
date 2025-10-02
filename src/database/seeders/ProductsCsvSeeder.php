<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;

class ProductsCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Try to find a current user (seeded by UserSeeder). Fallback to first user.
        $user = User::where('email', 'admin@example.com')->first() ?? User::first();

        // Get up to 2 categories and 2 suppliers owned by this user.
        $categories = Category::where('created_by', $user?->id)->take(2)->get();
        $suppliers = Supplier::where('created_by', $user?->id)->take(2)->get();

        // If not enough owned records, fall back to any available ones.
        if ($categories->count() < 2) {
            $categories = Category::take(2)->get();
        }
        if ($suppliers->count() < 2) {
            $suppliers = Supplier::take(2)->get();
        }

        // Ensure we have at least one category and supplier to use.
        if ($categories->isEmpty() || $suppliers->isEmpty()) {
            $this->command->warn('Not enough categories or suppliers found to generate CSV.');
            return;
        }

        $dir = storage_path('app/tests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'products_test.csv';

        $handle = fopen($path, 'w');
        if ($handle === false) {
            $this->command->error('Failed to open file for writing: ' . $path);
            return;
        }

        // Header
        fputcsv($handle, ['name', 'description', 'category_id', 'supplier_id', 'price', 'file_url']);

        // Build 100 rows
        for ($i = 1; $i <= 100; $i++) {
            $name = 'Product ' . $i;
            $description = 'Fake description ' . $i;

            // Choose category/supplier by round-robin
            $category = $categories->get(($i - 1) % $categories->count());
            $supplier = $suppliers->get(($i - 1) % $suppliers->count());

            $price = number_format(mt_rand(1000, 10000) / 100, 2, '.', ''); // 10.00 - 100.00
            $fileUrl = 'https://example.com/file' . $i . '.jpg';

            fputcsv($handle, [
                $name,
                $description,
                $category->id,
                $supplier->id,
                $price,
                $fileUrl,
            ]);
        }

        fclose($handle);

        $this->command->info('CSV file generated at: ' . $path);
    }
}
