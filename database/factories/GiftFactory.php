<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'target_amount' => fake()->numberBetween(5, 100) * 1000,
        ];
    }
}
