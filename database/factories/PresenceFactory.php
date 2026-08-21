<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Presence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presence>
 */
class PresenceFactory extends Factory
{
    protected $model = Presence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'user_identifier' => (string) fake()->unique()->numberBetween(10000, 99999),
            'user_fullname' => fake()->name(),
            'session_date' => today(),
            'check_in_time' => now(),
            'check_out_time' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn () => ['check_out_time' => now()]);
    }

    public function yesterday(): static
    {
        return $this->state(fn () => [
            'session_date' => today()->subDay(),
            'check_in_time' => today()->subDay()->setHour(8),
        ]);
    }
}
