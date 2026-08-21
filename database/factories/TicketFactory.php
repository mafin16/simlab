<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_code' => 'TKT-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'asset_id' => Asset::factory(),
            'component_issue' => fake()->randomElement(Ticket::COMPONENTS),
            'description' => fake()->sentence(),
            'priority' => fake()->randomElement(Ticket::PRIORITIES),
            'status' => 'Open',
            'reporter_name' => fake()->name(),
            'reported_at' => now(),
        ];
    }

    public function high(): static
    {
        return $this->state(fn () => ['priority' => 'High']);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'In Progress', 'technician_name' => 'Teknisi Lab']);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'Resolved',
            'technician_name' => 'Teknisi Lab',
            'resolution_notes' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }
}
