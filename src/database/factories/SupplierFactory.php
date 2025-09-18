<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Supplier;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'contact_name' => $this->faker->name(),
            'website' => $this->faker->url(),
            'description' => $this->faker->catchPhrase(),
            'created_by' => (string) Str::uuid(),
            'updated_by' => (string) Str::uuid(),
        ];
    }
}
