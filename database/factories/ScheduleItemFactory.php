<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+1 year'),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ];
    }
}
