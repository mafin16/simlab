<?php

namespace Database\Factories;

use App\Models\Lab;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $start = fake()->randomElement(['07:00', '09:30', '13:00']);
        $end = match ($start) {
            '07:00' => '09:15',
            '09:30' => '11:45',
            default => '15:15',
        };

        return [
            'lab_id' => Lab::factory(),
            'day_name' => fake()->randomElement($days),
            'start_time' => $start,
            'end_time' => $end,
            'subject_name' => fake()->randomElement(['Pemrograman Web', 'Basis Data', 'Pemrograman Mobile', 'Desain UI/UX', 'Jaringan Dasar', 'IoT & Embedded']),
            'class_group' => fake()->randomElement(['XII RPL 1', 'XII RPL 2', 'XI RPL 1', 'XI RPL 2']),
            'instructor_name' => fake()->name(),
        ];
    }
}
