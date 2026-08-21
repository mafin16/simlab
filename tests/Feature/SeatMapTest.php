<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Presence;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatMapTest extends TestCase
{
    use RefreshDatabase;

    private function makeAsset(Lab $lab, string $code, string $status = 'Ready'): Asset
    {
        return Asset::factory()->create([
            'lab_id' => $lab->id,
            'asset_code' => $code,
            'category' => 'PC Desktop',
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_from_seatmap(): void
    {
        $this->get(route('seatmap.index'))->assertRedirect('/login');
        $this->get(route('seatmap.status'))->assertRedirect('/login');
    }

    public function test_all_roles_can_view_seatmap(): void
    {
        $roles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_TEKNISI,
            User::ROLE_INSTRUKTUR,
            User::ROLE_SISWA,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('seatmap.index'))
                ->assertOk()
                ->assertSee('Denah Lab', false);
        }
    }

    public function test_siswa_does_not_see_report_button_on_seatmap(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->get(route('seatmap.index'))
            ->assertOk()
            ->assertDontSee('Lapor Kendala PC Ini');
    }

    public function test_admin_sees_report_button_on_seatmap(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get(route('seatmap.index'))
            ->assertOk()
            ->assertSee('Lapor Kendala PC Ini');
    }

    public function test_seatmap_shows_assets_of_selected_lab_only(): void
    {
        $user = User::factory()->create();
        $labA = Lab::factory()->create();
        $labB = Lab::factory()->create();
        $this->makeAsset($labA, 'LABA-PC-01');
        $this->makeAsset($labA, 'LABA-PC-02');
        $this->makeAsset($labB, 'LABB-PC-01');

        $this->actingAs($user)
            ->get(route('seatmap.index', ['lab_id' => $labA->id]))
            ->assertOk()
            ->assertSee('LABA-PC-01')
            ->assertSee('LABA-PC-02')
            ->assertDontSee('LABB-PC-01');
    }

    public function test_seatmap_defaults_to_first_lab(): void
    {
        $user = User::factory()->create();
        $firstLab = Lab::factory()->create(['name' => 'Laboratorium Komputer 1']);
        Lab::factory()->create(['name' => 'Laboratorium Komputer 2']);
        $this->makeAsset($firstLab, 'LAB1-PC-01');

        $this->actingAs($user)
            ->get(route('seatmap.index'))
            ->assertOk()
            ->assertSee('LAB1-PC-01');
    }

    public function test_status_endpoint_returns_seats_json(): void
    {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $this->makeAsset($lab, 'LAB1-PC-01', 'Degraded');

        $this->actingAs($user)
            ->getJson(route('seatmap.status', ['lab_id' => $lab->id]))
            ->assertOk()
            ->assertJsonStructure(['server_time', 'lab_id', 'seats' => [['id', 'code', 'seat_label', 'status', 'presence']]])
            ->assertJsonPath('seats.0.code', 'LAB1-PC-01')
            ->assertJsonPath('seats.0.status', 'Degraded')
            ->assertJsonPath('seats.0.presence', null);
    }

    public function test_status_endpoint_marks_occupied_seat(): void
    {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $asset = $this->makeAsset($lab, 'LAB1-PC-05');

        Presence::factory()->create([
            'asset_id' => $asset->id,
            'user_identifier' => '20260012',
            'user_fullname' => 'Rina Amelia Putri',
        ]);

        $this->actingAs($user)
            ->getJson(route('seatmap.status', ['lab_id' => $lab->id]))
            ->assertOk()
            ->assertJsonPath('seats.0.presence.identifier', '20260012')
            ->assertJsonPath('seats.0.presence.fullname', 'Rina Amelia Putri');
    }

    public function test_stale_presence_from_previous_day_is_swept_closed(): void
    {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $asset = $this->makeAsset($lab, 'LAB1-PC-01');

        Presence::factory()->yesterday()->create(['asset_id' => $asset->id]);

        $this->actingAs($user)->get(route('seatmap.index'))->assertOk();

        $this->assertNotNull($asset->presences()->first()->check_out_time);
    }

    public function test_presence_auto_closes_after_session_ends_but_stays_open_during_session(): void
    {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();

        Schedule::create([
            'lab_id' => $lab->id,
            'day_name' => now()->format('l'),
            'start_time' => '00:01',
            'end_time' => '00:02',
            'subject_name' => 'Praktikum Selesai',
            'class_group' => 'XII RPL 1',
            'instructor_name' => 'Pak Hendra',
        ]);

        $expired = $this->makeAsset($lab, 'LAB1-PC-01');
        Presence::factory()->create([
            'asset_id' => $expired->id,
            'check_in_time' => today()->setTime(0, 1),
        ]);

        $ongoing = $this->makeAsset($lab, 'LAB1-PC-02');
        Presence::factory()->create([
            'asset_id' => $ongoing->id,
            'check_in_time' => now(),
        ]);

        $this->actingAs($user)->get(route('seatmap.index'))->assertOk();

        // Sesi yang sudah lewat ditutup tepat di jam akhir sesi.
        $this->assertTrue(
            $expired->presences()->first()->check_out_time->equalTo(today()->setTime(0, 2))
        );

        // Sesi yang masih berjalan tetap aktif.
        $this->assertNull($ongoing->presences()->first()->check_out_time);
    }
}
