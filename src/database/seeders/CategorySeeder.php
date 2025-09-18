<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $parents = Category::factory()->count(10)->create();

        foreach ($parents as $parent) {
            Category::factory()->count(rand(2, 4))->create([
                'parent_id' => $parent->id,
            ]);
        }

        Category::factory()->count(5)->create([
            'parent_id' => null,
        ]);
    }
}
