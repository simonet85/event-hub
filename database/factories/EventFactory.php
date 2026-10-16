<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(EventType::cases());
        $title = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'type' => $type,
            'event_date' => fake()->dateTimeBetween('+1 week', '+1 year'),
            'location_name' => fake()->company(),
            'location_address' => fake()->address(),
            'welcome_message' => fake()->sentence(),
            'theme_config' => $type->defaultTheme(),
        ];
    }
}
