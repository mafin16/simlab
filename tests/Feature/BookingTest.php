<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Lab;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
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
            'day_name' => 'Friday',
            'applicant_name' => 'Pak Hendra',
            'event_name' => 'Workshop Sertifikasi',
            'start_time' => '08:00',
            'end_time' => '11:30',
        ], $overrides);
    }

    public function test_instruktur_can_create_booking(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->post(route('bookings.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'event_name' => 'Workshop Sertifikasi',
            'status' => 'APPROVED',
        ]);
    }

    public function test_siswa_cannot_create_booking(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SISWA]);

        $this->actingAs($user)
            ->post(route('bookings.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejected_when_colliding_with_schedule(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Friday',
            'start_time' => '08:00',
            'end_time' => '11:30',
            'subject_name' => 'Pemrograman Web',
        ]);

        $this->actingAs($this->admin())
            ->post(route('bookings.store'), $this->validPayload([
                'lab_id' => $lab->id,
                'day_name' => 'Friday',
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]))
            ->assertSessionHas('error')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejected_when_colliding_with_existing_booking(): void
    {
        $lab = Lab::factory()->create();
        Booking::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Friday',
            'start_time' => '08:00',
            'end_time' => '11:30',
            'status' => 'APPROVED',
        ]);

        $this->actingAs($this->admin())
            ->post(route('bookings.store'), $this->validPayload([
                'lab_id' => $lab->id,
                'day_name' => 'Friday',
                'start_time' => '10:00',
                'end_time' => '12:00',
            ]))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_succeeds_when_no_collision(): void
    {
        $lab = Lab::factory()->create();
        Schedule::factory()->create([
            'lab_id' => $lab->id,
            'day_name' => 'Monday',
            'start_time' => '07:00',
            'end_time' => '09:15',
        ]);

        $this->actingAs($this->admin())
            ->post(route('bookings.store'), $this->validPayload([
                'lab_id' => $lab->id,
                'day_name' => 'Monday',
                'start_time' => '13:00',
                'end_time' => '15:00',
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_super_admin_can_update_booking_status(): void
    {
        $booking = Booking::factory()->create(['status' => 'APPROVED']);

        $this->actingAs($this->admin())
            ->patch(route('bookings.status', $booking), ['status' => 'REJECTED'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'REJECTED']);
    }

    public function test_instruktur_cannot_update_booking_status(): void
    {
        $booking = Booking::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_INSTRUKTUR]);

        $this->actingAs($user)
            ->patch(route('bookings.status', $booking), ['status' => 'REJECTED'])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'APPROVED']);
    }

    public function test_super_admin_can_destroy_booking(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('bookings.destroy', $booking))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('bookings', 0);
    }
}
