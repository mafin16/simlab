<?php

namespace Database\Factories;

use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lab>
 */
class LabFactory extends Factory
{
    protected $model = Lab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_code' => 'LAB-'.fake()->unique()->numberBetween(1, 999),
            'name' => 'Laboratorium Komputer '.fake()->unique()->numberBetween(1, 99),
            'capacity' => fake()->numberBetween(10, 30),
            'location' => 'Ruang '.fake()->word(),
        ];
    }
}
