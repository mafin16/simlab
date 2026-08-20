<?php

namespace Tests\Feature;

use App\Models\Lab;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'lab_id' => Lab::factory()->create()->id,
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
            'subject_name' => 'Pemrograman Web',
            'class_group' => 'XII RPL 1',
            'instructor_name' => 'Pak Hendra, S.Kom',
        ], $overrides);
    }

    public function test_guest_is_redirected_from_schedules_index(): void
    {
        $this->get(route('schedules.index'))->assertRedirect('/login');
    }

    public function test_super_admin_can_view_schedules_index(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'subject_name' => 'Pemrograman Web',
            'day_name' => 'Monday',
            'start_time' => '07:00',
        ]);

        $this->actingAs($this->admin())
            ->get(route('schedules.index').'?lab_id='.$lab->id)
            ->assertOk()
            ->assertSee('Jadwal & Booking Lab')
            ->assertSee('Pemrograman Web')
            ->assertSee('Daftar Booking Mingguan');
    }

    public function test_instruktur_can_view_schedules_index(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create(['lab_id' => $lab->id]);

        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->get(route('schedules.index').'?lab_id='.$lab->id)
            ->assertOk();
    }

    public function test_teknisi_can_store_schedule(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_TEKNISI]);

        $this->actingAs($user)
            ->post(route('schedules.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', ['subject_name' => 'Pemrograman Web']);
    }

    public function test_siswa_cannot_store_schedule(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->post(route('schedules.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_instruktur_cannot_store_schedule(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->post(route('schedules.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_store_schedule_rejected_when_collides_with_existing_schedule(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
            'subject_name' => 'Basis Data',
        ]);

        $this->actingAs($this->admin())
            ->post(route('schedules.store'), $this->validPayload([
                'lab_id' => $lab->id,
                'day_name' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'subject_name' => 'Pemrograman Web',
            ]))
            ->assertSessionHas('error')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_store_schedule_succeeds_when_no_collision(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
        ]);

        $this->actingAs($this->admin())
            ->post(route('schedules.store'), $this->validPayload([
                'lab_id' => $lab->id,
                'day_name' => 'Monday',
                'start_time' => '10:00',
                'end_time' => '12:00',
                'subject_name' => 'Pemrograman Mobile',
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_store_schedule_validation_fails_on_invalid_time(): void
    {
        $this->actingAs($this->admin())
            ->post(route('schedules.store'), $this->validPayload([
                'start_time' => '09:15',
                'end_time' => '07:00',
            ]))
            ->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_update_schedule_same_slot_allowed(): void
    {
        $schedule = Schedule::factory()->create([
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
            'subject_name' => 'Basis Data',
        ]);

        $this->actingAs($this->admin())
            ->put(route('schedules.update', $schedule), [
                'lab_id' => $schedule->lab_id,
                'day_name' => 'Monday',
                'start_time' => '07:00',
                'end_time' => '09:15',
                'subject_name' => 'Basis Data Lanjutan',
                'class_group' => 'XII RPL 1',
                'instructor_name' => 'Pak Budi',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', ['subject_name' => 'Basis Data Lanjutan']);
    }

    public function test_update_schedule_rejected_when_colliding_with_other_schedule(): void
    {
        $lab = Lab::factory()->create();
        $own = Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
            'subject_name' => 'Jaringan',
        ]);

        $this->actingAs($this->admin())
            ->put(route('schedules.update', $own), [
                'lab_id' => $lab->id,
                'day_name' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'subject_name' => 'Basis Data',
                'class_group' => 'XII RPL 1',
                'instructor_name' => 'Pak Budi',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('schedules', ['id' => $own->id, 'start_time' => '10:00:00']);
    }

    public function test_super_admin_can_destroy_schedule(): void
    {
        $schedule = Schedule::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('schedules.destroy', $schedule))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_siswa_cannot_destroy_schedule(): void
    {
        $schedule = Schedule::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->delete(route('schedules.destroy', $schedule))
            ->assertForbidden();

        $this->assertDatabaseCount('schedules', 1);
    }
}
