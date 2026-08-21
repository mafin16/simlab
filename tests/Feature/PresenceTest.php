<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Presence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAsset(string $code = 'LAB1-PC-01', string $status = 'Ready'): Asset
    {
        return Asset::factory()->create([
            'asset_code' => $code,
            'category' => 'PC Desktop',
            'status' => $status,
        ]);
    }

    public function test_checkin_page_accessible_without_login(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');

        $this->get(route('checkin.show', $asset->asset_code))
            ->assertOk()
            ->assertSee('LAB1-PC-05')
            ->assertSee('Check-in Sekarang');
    }

    public function test_checkin_page_returns_404_for_unknown_asset(): void
    {
        $this->get(route('checkin.show', 'LAB9-PC-99'))->assertNotFound();
    }

    public function test_checkin_page_blocked_for_maintenance_and_scrapped_asset(): void
    {
        $maintenance = $this->makeAsset('LAB1-PC-01', 'Maintenance');
        $scrapped = $this->makeAsset('LAB1-PC-02', 'Scrapped');

        $this->get(route('checkin.show', $maintenance->asset_code))
            ->assertOk()
            ->assertSee('tidak dapat digunakan');

        $this->get(route('checkin.show', $scrapped->asset_code))
            ->assertOk()
            ->assertSee('tidak dapat digunakan');
    }

    public function test_checkin_requires_identifier_and_fullname(): void
    {
        $asset = $this->makeAsset();

        $this->post(route('checkin.store', $asset->asset_code), [
            'user_identifier' => '',
            'user_fullname' => '',
        ])->assertSessionHasErrors(['user_identifier', 'user_fullname']);

        $this->assertDatabaseCount('presences', 0);
    }

    public function test_checkin_success_creates_active_presence(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');

        $this->followRedirects($this->post(route('checkin.store', $asset->asset_code), [
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]))
            ->assertOk()
            ->assertSee('Selamat datang');

        $this->assertDatabaseHas('presences', [
            'asset_id' => $asset->id,
            'user_identifier' => '20260012',
            'check_out_time' => null,
        ]);
    }

    public function test_degraded_asset_allows_checkin_with_warning(): void
    {
        $asset = $this->makeAsset('LAB1-PC-07', 'Degraded');

        $this->get(route('checkin.show', $asset->asset_code))
            ->assertOk()
            ->assertSee('Degraded');

        $this->post(route('checkin.store', $asset->asset_code), [
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ])->assertRedirect(route('checkin.show', $asset->asset_code));

        $this->assertDatabaseCount('presences', 1);
    }

    public function test_checkin_rejected_when_seat_taken_by_other_user(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');
        Presence::factory()->create([
            'asset_id' => $asset->id,
            'user_identifier' => '20260001',
            'user_fullname' => 'Andi Saputra',
        ]);

        $this->post(route('checkin.store', $asset->asset_code), [
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('presences', 1);
        $this->assertDatabaseHas('presences', ['user_identifier' => '20260001', 'check_out_time' => null]);
    }

    public function test_checkin_same_identifier_on_same_pc_is_idempotent(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');
        Presence::factory()->create([
            'asset_id' => $asset->id,
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]);

        $this->followRedirects($this->post(route('checkin.store', $asset->asset_code), [
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]))->assertSee('Selamat datang');

        $this->assertDatabaseCount('presences', 1);
    }

    public function test_checkin_auto_moves_user_from_other_pc(): void
    {
        $oldSeat = $this->makeAsset('LAB1-PC-02');
        $newSeat = $this->makeAsset('LAB1-PC-03');

        Presence::factory()->create([
            'asset_id' => $oldSeat->id,
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]);

        $this->post(route('checkin.store', $newSeat->asset_code), [
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ])->assertRedirect(route('checkin.show', $newSeat->asset_code));

        // Sesi di PC lama otomatis ditutup.
        $this->assertNotNull($oldSeat->presences()->first()->check_out_time);

        // Sesi baru aktif di PC tujuan.
        $this->assertDatabaseHas('presences', [
            'asset_id' => $newSeat->id,
            'user_identifier' => '20260012',
            'check_out_time' => null,
        ]);
    }

    public function test_checkout_closes_active_presence(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');
        Presence::factory()->create([
            'asset_id' => $asset->id,
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]);

        $this->post(route('checkin.checkout', $asset->asset_code), [
            'user_identifier' => '20260012',
        ])
            ->assertRedirect(route('checkin.show', $asset->asset_code))
            ->assertSessionHas('checkout_success');

        $this->assertNotNull($asset->presences()->first()->check_out_time);
    }

    public function test_checkout_with_wrong_identifier_fails(): void
    {
        $asset = $this->makeAsset('LAB1-PC-05');
        Presence::factory()->create([
            'asset_id' => $asset->id,
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]);

        $this->post(route('checkin.checkout', $asset->asset_code), [
            'user_identifier' => '99999999',
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($asset->presences()->first()->check_out_time);
    }
}
