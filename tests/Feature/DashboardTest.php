<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Total Unit PC')
            ->assertSee('Tiket Aktif');
    }

    public function test_role_middleware_blocks_unauthorized_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_seed_data_creates_labs_and_assets(): void
    {
        $this->seed();

        $this->assertDatabaseCount('labs', 2);
        $this->assertDatabaseCount('assets', 35);
        $this->assertDatabaseCount('schedules', 20);
    }

    public function test_dashboard_shows_today_schedule(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => now()->format('l'),
            'subject_name' => 'Pemrograman Web',
            'start_time' => '07:00',
            'end_time' => '09:15',
        ]);

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get('/dashboard?lab_id='.$lab->id)
            ->assertOk()
            ->assertSee('Pemrograman Web');
    }

    public function test_dashboard_filters_assets_by_lab(): void
    {
        $lab1 = Lab::create([
            'lab_code' => 'LAB-1',
            'name' => 'Laboratorium Komputer 1',
            'capacity' => 20,
            'location' => 'Ruang Lab 1',
        ]);
        $lab2 = Lab::create([
            'lab_code' => 'LAB-2',
            'name' => 'Laboratorium Komputer 2',
            'capacity' => 15,
            'location' => 'Ruang Lab 2',
        ]);

        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)
            ->get('/dashboard?lab_id='.$lab1->id)
            ->assertOk()
            ->assertSee('Laboratorium Komputer 1');

        $this->actingAs($user)
            ->get('/dashboard?lab_id='.$lab2->id)
            ->assertOk()
            ->assertSee('Laboratorium Komputer 2');
    }
}
