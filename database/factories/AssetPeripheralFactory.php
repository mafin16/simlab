<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetPeripheral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetPeripheral>
 */
class AssetPeripheralFactory extends Factory
{
    protected $model = AssetPeripheral::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peripheral_code' => 'PER-'.fake()->unique()->numberBetween(100, 9999),
            'asset_id' => Asset::factory(),
            'type' => fake()->randomElement(['Monitor', 'Keyboard', 'Mouse', 'Headset', 'UPS']),
            'brand' => fake()->randomElement(['LG', 'Logitech', 'Acer', 'Samsung', 'APC']),
            'model_name' => fake()->bothify('####-??'),
            'serial_number' => 'SN-'.fake()->unique()->bothify('##-??-####'),
            'condition' => fake()->randomElement(['Baik / Normal', 'Baik / Normal', 'Perlu Penggantian', 'Rusak']),
            'location_note' => fake()->optional()->sentence(3),
        ];
    }
}
