<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContributionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'event_id' => fn (array $attrs) => Guest::find($attrs['guest_id'])->event_id,
            'gift_id' => null,
            'amount' => fake()->numberBetween(1, 50) * 1000,
            'message' => fake()->sentence(),
            'status' => 'pending',
            'payment_token' => Str::random(20),
            'payment_url' => 'https://payin.moneyfusion.net/payment/'.Str::random(10).'/Marchand',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed', 'confirmed_at' => now()]);
    }
}
