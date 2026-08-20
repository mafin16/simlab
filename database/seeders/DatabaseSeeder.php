<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLabsAndAssets();
        $this->call(ScheduleSeeder::class);
        $this->seedUsers();
    }

    private function seedLabsAndAssets(): void
    {
        $labSpecs = [
            [
                'lab_code' => 'LAB-1',
                'name' => 'Laboratorium Komputer 1',
                'capacity' => 20,
                'location' => 'Ruang Lab 1',
                'pc_prefix' => 'LAB1-PC-',
                'pc_count' => 20,
            ],
            [
                'lab_code' => 'LAB-2',
                'name' => 'Laboratorium Komputer 2',
                'capacity' => 15,
                'location' => 'Ruang Lab 2',
                'pc_prefix' => 'LAB2-PC-',
                'pc_count' => 15,
            ],
        ];

        foreach ($labSpecs as $spec) {
            $lab = Lab::create([
                'lab_code' => $spec['lab_code'],
                'name' => $spec['name'],
                'capacity' => $spec['capacity'],
                'location' => $spec['location'],
            ]);

            for ($i = 1; $i <= $spec['pc_count']; $i++) {
                $seq = Str::padLeft((string) $i, 2, '0');
                $assetCode = $spec['pc_prefix'].$seq;

                Asset::create([
                    'asset_code' => $assetCode,
                    'name' => 'PC Client '.$seq,
                    'lab_id' => $lab->id,
                    'seat_label' => 'Meja '.$seq,
                    'category' => 'PC Desktop',
                    'cpu_spec' => 'Intel Core i3-10100',
                    'ram_gb' => 8,
                    'ram_type' => 'DDR4',
                    'storage_primary' => 'SSD 256GB',
                    'storage_secondary' => 'HDD 500GB',
                    'gpu_spec' => 'Intel UHD Graphics 630',
                    'ip_address' => "192.168.{$lab->id}.{$i}",
                    'mac_address' => '00:1A:2B:3C:'.Str::padLeft(dechex($lab->id), 2, '0').':'.Str::padLeft(dechex($i), 2, '0'),
                    'serial_number' => 'SN-'.$assetCode,
                    'procurement_source' => 'Dana BOS',
                    'purchase_date' => now()->subYears(2),
                    'warranty_expiry' => now()->addYear(),
                    'status' => 'Ready',
                ]);
            }
        }
    }

    private function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@simlab.test',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Teknisi Lab',
            'email' => 'teknisi@simlab.test',
            'password' => 'password',
            'role' => User::ROLE_TEKNISI,
        ]);

        User::factory()->create([
            'name' => 'Instruktur',
            'email' => 'instruktur@simlab.test',
            'password' => 'password',
            'role' => User::ROLE_INSTRUKTUR,
        ]);
    }
}
