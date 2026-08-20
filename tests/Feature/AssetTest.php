<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetPeripheral;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a real .xlsx upload file from an array of rows (first row = header).
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buildImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_guest_is_redirected_from_assets_index(): void
    {
        $this->get(route('assets.index'))->assertRedirect('/login');
    }

    public function test_super_admin_can_view_assets_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSee('Inventaris Aset & Spesifikasi PC')
            ->assertSee('Tambah Aset');
    }

    public function test_instruktur_can_view_assets_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);
        $asset = Asset::factory()->create(['asset_code' => 'LAB1-PC-01']);

        $this->actingAs($user)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSee('LAB1-PC-01')
            ->assertDontSee('Tambah Aset');
    }

    public function test_siswa_cannot_access_create_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->get(route('assets.create'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_create_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $lab = Lab::factory()->create();

        $this->actingAs($user)
            ->get(route('assets.create'))
            ->assertOk()
            ->assertSee('Informasi Identitas')
            ->assertSee('Spesifikasi Hardware')
            ->assertSee('Jaringan & Inventaris', false)
            ->assertSee('Masa Berlaku');
    }

    public function test_teknisi_can_create_asset(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        $lab = Lab::factory()->create();

        $this->actingAs($user)
            ->post(route('assets.store'), [
                'asset_code' => 'LAB1-PC-21',
                'name' => 'PC Client 21',
                'lab_id' => $lab->id,
                'seat_label' => 'Meja A-21',
                'category' => 'PC Desktop',
                'cpu_spec' => 'Intel i5-12400',
                'ram_gb' => 16,
                'ram_type' => 'DDR4',
                'storage_primary' => '512GB NVMe SSD',
                'storage_secondary' => 'HDD 500GB',
                'gpu_spec' => 'Intel UHD Graphics 730',
                'ip_address' => '192.168.10.31',
                'mac_address' => '00:1A:2B:3C:01:21',
                'serial_number' => 'SN-LAB1-PC-21',
                'procurement_source' => 'Dana BOS',
                'purchase_date' => '2024-08-01',
                'warranty_expiry' => '2027-08-01',
                'status' => 'Ready',
            ])
            ->assertRedirect(route('assets.index'));

        $this->assertDatabaseHas('assets', [
            'asset_code' => 'LAB1-PC-21',
            'lab_id' => $lab->id,
            'status' => 'Ready',
        ]);
    }

    public function test_asset_code_is_required_on_create(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        $lab = Lab::factory()->create();

        $this->actingAs($user)
            ->post(route('assets.store'), [
                'asset_code' => '',
                'name' => 'PC Tanpa Kode',
                'lab_id' => $lab->id,
                'seat_label' => 'Meja A-1',
                'cpu_spec' => 'Intel i5',
                'ram_gb' => 8,
                'ram_type' => 'DDR4',
                'storage_primary' => '256GB SSD',
                'status' => 'Ready',
            ])
            ->assertSessionHasErrors('asset_code');

        $this->assertDatabaseCount('assets', 0);
    }

    public function test_super_admin_can_update_asset(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $asset = Asset::factory()->create();
        $newLab = Lab::factory()->create();

        $this->actingAs($user)
            ->put(route('assets.update', $asset), [
                'asset_code' => $asset->asset_code,
                'name' => 'PC Client Revisi',
                'lab_id' => $newLab->id,
                'seat_label' => 'Meja B-02',
                'category' => 'PC Desktop',
                'cpu_spec' => 'Intel i7-12700',
                'ram_gb' => 32,
                'ram_type' => 'DDR4',
                'storage_primary' => '1TB NVMe SSD',
                'status' => 'Ready',
            ])
            ->assertRedirect(route('assets.show', $asset));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'PC Client Revisi',
            'lab_id' => $newLab->id,
            'cpu_spec' => 'Intel i7-12700',
        ]);
    }

    public function test_super_admin_can_delete_asset(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $asset = Asset::factory()->create();

        $this->actingAs($user)
            ->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('assets.index'));

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_assets_index_filters_by_lab_and_search(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $lab1 = Lab::factory()->create();
        $lab2 = Lab::factory()->create();

        Asset::factory()->create(['lab_id' => $lab1->id, 'asset_code' => 'LAB1-PC-01']);
        Asset::factory()->create(['lab_id' => $lab2->id, 'asset_code' => 'LAB2-PC-02']);

        $this->actingAs($user)
            ->get(route('assets.index', ['lab_id' => $lab1->id]))
            ->assertOk()
            ->assertSee('LAB1-PC-01')
            ->assertDontSee('LAB2-PC-02');

        $this->actingAs($user)
            ->get(route('assets.index', ['search' => 'LAB2-PC']))
            ->assertOk()
            ->assertSee('LAB2-PC-02')
            ->assertDontSee('LAB1-PC-01');
    }

    public function test_assets_index_respects_per_page_option(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Asset::factory()->count(25)->create();

        $this->actingAs($user)
            ->get(route('assets.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('per_page=10')
            ->assertSee('100')
            ->assertSee('per halaman');
    }

    public function test_assets_index_falls_back_to_default_per_page_for_invalid_value(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Asset::factory()->count(25)->create();

        $this->actingAs($user)
            ->get(route('assets.index', ['per_page' => 999]))
            ->assertOk()
            ->assertSee('per_page=10')
            ->assertDontSee('per_page=999');
    }

    public function test_super_admin_can_bulk_delete_assets(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $assets = Asset::factory()->count(5)->create();
        $ids = $assets->take(3)->pluck('id')->all();

        $this->actingAs($user)
            ->post(route('assets.bulk-destroy'), ['ids' => $ids])
            ->assertRedirect(route('assets.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('assets', 2);
    }

    public function test_bulk_delete_without_selection_shows_error(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Asset::factory()->count(3)->create();

        $this->actingAs($user)
            ->post(route('assets.bulk-destroy'), ['ids' => []])
            ->assertRedirect(route('assets.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('assets', 3);
    }

    public function test_siswa_cannot_bulk_delete_assets(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->post(route('assets.bulk-destroy'), ['ids' => [1]])
            ->assertForbidden();
    }

    public function test_asset_show_page_displays_detail(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);
        $asset = Asset::factory()->create(['asset_code' => 'LAB1-PC-07']);

        $this->actingAs($user)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('LAB1-PC-07')
            ->assertSee('Spesifikasi Hardware')
            ->assertSee('QR Code Aset');
    }

    public function test_asset_export_downloads_xlsx(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Asset::factory()->create();

        $this->actingAs($user)
            ->get(route('assets.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_asset_import_template_downloads_xlsx(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get(route('assets.import.template'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_teknisi_can_import_assets_from_xlsx(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        $lab = Lab::factory()->create(['lab_code' => 'LAB-1']);

        $rows = [
            ['asset_code', 'name', 'lab_code', 'seat_label', 'category', 'cpu_spec', 'ram_gb', 'ram_type', 'storage_primary', 'storage_secondary', 'gpu_spec', 'ip_address', 'mac_address', 'serial_number', 'procurement_source', 'purchase_date', 'warranty_expiry', 'status'],
            ['LAB1-PC-21', 'PC Client 21', 'LAB-1', 'Meja A-21', 'PC Desktop', 'Intel i5-12400', 16, 'DDR4', '512GB NVMe SSD', '', 'Intel UHD 730', '192.168.10.31', '00:1A:2B:3C:01:21', 'SN-21', 'Dana BOS', '2024-08-01', '2027-08-01', 'Ready'],
            ['LAB1-PC-22', 'PC Client 22', 'LAB-1', 'Meja A-22', 'PC Desktop', 'Intel i5-12400', 16, 'DDR4', '512GB NVMe SSD', '', 'Intel UHD 730', '192.168.10.32', '00:1A:2B:3C:01:22', 'SN-22', 'Dana BOS', '2024-08-01', '2027-08-01', 'Ready'],
        ];

        $binary = $this->buildImportFile($rows);

        $this->actingAs($user)
            ->post(route('assets.import'), ['file' => $binary])
            ->assertRedirect();

        $this->assertDatabaseHas('assets', ['asset_code' => 'LAB1-PC-21', 'lab_id' => $lab->id]);
        $this->assertDatabaseHas('assets', ['asset_code' => 'LAB1-PC-22', 'lab_id' => $lab->id]);
    }

    public function test_import_skips_duplicate_asset_codes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $lab = Lab::factory()->create(['lab_code' => 'LAB-1']);
        Asset::factory()->create(['asset_code' => 'LAB1-PC-99', 'lab_id' => $lab->id]);

        $rows = [
            ['asset_code', 'name', 'lab_code', 'seat_label', 'category', 'cpu_spec', 'ram_gb', 'ram_type', 'storage_primary', 'status'],
            ['LAB1-PC-99', 'Duplikat', 'LAB-1', 'Meja A-99', 'PC Desktop', 'Intel i5', 8, 'DDR4', '256GB SSD', 'Ready'],
        ];

        $file = $this->buildImportFile($rows);

        $this->actingAs($user)
            ->post(route('assets.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('assets', 1);
    }

    public function test_teknisi_can_add_peripheral_with_auto_code(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        $asset = Asset::factory()->create(['asset_code' => 'LAB1-PC-05']);

        $this->actingAs($user)
            ->post(route('assets.peripherals.store', $asset), [
                'type' => 'Monitor',
                'brand' => 'LG',
                'model_name' => '24MP400',
                'serial_number' => 'SN-MON-001',
                'condition' => 'Baik / Normal',
                'location_note' => 'Port HDMI',
            ])
            ->assertRedirect(route('assets.show', $asset));

        $this->assertDatabaseHas('asset_peripherals', [
            'asset_id' => $asset->id,
            'type' => 'Monitor',
            'brand' => 'LG',
        ]);

        $peripheral = AssetPeripheral::where('asset_id', $asset->id)->first();
        $this->assertSame('LAB1-PC-05-PER-1', $peripheral->peripheral_code);
    }

    public function test_teknisi_can_delete_peripheral(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);
        $asset = Asset::factory()->create();
        $peripheral = AssetPeripheral::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs($user)
            ->delete(route('assets.peripherals.destroy', $peripheral))
            ->assertRedirect(route('assets.show', $asset));

        $this->assertDatabaseMissing('asset_peripherals', ['id' => $peripheral->id]);
    }
}
