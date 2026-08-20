<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_code' => 'LAB1-PC-'.fake()->unique()->numberBetween(1, 999),
            'name' => fake()->words(3, true),
            'lab_id' => Lab::factory(),
            'seat_label' => 'Meja '.fake()->randomLetter().'-'.fake()->numberBetween(1, 30),
            'category' => fake()->randomElement(['PC Desktop', 'Server', 'Workstation']),
            'cpu_spec' => 'Intel i5-'.fake()->numberBetween(8, 14).'00',
            'ram_gb' => fake()->randomElement([8, 16, 32]),
            'ram_type' => fake()->randomElement(['DDR3', 'DDR4']),
            'storage_primary' => fake()->randomElement(['256GB NVMe SSD', '512GB NVMe SSD', '1TB HDD']),
            'storage_secondary' => fake()->optional()->randomElement(['1TB HDD', '512GB SSD', '']) ?? '',
            'gpu_spec' => fake()->optional()->randomElement(['Intel UHD Graphics', 'NVIDIA GT 1030', '']) ?? '',
            'ip_address' => fake()->unique()->ipv4(),
            'mac_address' => fake()->unique()->macAddress(),
            'serial_number' => 'SN-'.fake()->unique()->bothify('###-??-####'),
            'procurement_source' => fake()->randomElement(['Dana BOS', 'APBD', 'Hibah']),
            'purchase_date' => fake()->date(),
            'warranty_expiry' => fake()->date(),
            'status' => fake()->randomElement(['Ready', 'Ready', 'Ready', 'Degraded', 'Maintenance']),
            'qr_code_url' => null,
        ];
    }
}
