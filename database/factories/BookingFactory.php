<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->randomElement(['07:00', '09:30', '13:00', '15:30']);
        $duration = fake()->randomElement([2, 3, 4]);
        $startCarbon = Carbon::createFromFormat('H:i', $start);

        return [
            'lab_id' => Lab::factory(),
            'day_name' => fake()->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
            'applicant_name' => fake()->name(),
            'event_name' => fake()->randomElement(['Workshop Sertifikasi', 'Tryout Online', 'Seminar Digital', 'Ujian Praktik', 'Ekstrakurikuler Coding']),
            'start_time' => $start,
            'end_time' => $startCarbon->copy()->addHours($duration)->format('H:i'),
            'status' => 'APPROVED',
        ];
    }
}
