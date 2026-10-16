<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'event_id' => fn (array $attrs) => Guest::find($attrs['guest_id'])->event_id,
            'path' => 'photos/test/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(3),
            'status' => 'pending',
        ];
    }
}
