<?php

namespace Database\Factories;

use App\Models\Event;
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
            'event_id' => fn (array $attrs) => Guest::find($attrs['guest_id'])?->event_id ?? Event::factory(),
            'path' => 'photos/test/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(3),
            'status' => 'pending',
        ];
    }

    /** Photo de la galerie : publiée par l'hôte, sans invité auteur. */
    public function gallery(): static
    {
        return $this->state(fn () => [
            'guest_id' => null,
            'thumb_path' => 'photos/test/'.fake()->uuid().'-mini.jpg',
            'status' => 'approved',
        ]);
    }
}
