<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestbookEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $guest = Guest::factory();

        return [
            'guest_id' => $guest,
            'event_id' => fn (array $attrs) => Guest::find($attrs['guest_id'])->event_id,
            'message' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
