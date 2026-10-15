<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => fn (array $attributes) => \App\Models\Client::find($attributes['client_id'])->user_id,
            'number' => 'INV-'.fake()->unique()->numberBetween(10000, 99999),
            'amount' => fake()->randomFloat(2, 200, 5000),
            'currency' => 'USD',
            'due_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => Invoice::STATUS_DRAFT,
            'late_fee' => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Invoice::STATUS_PAID,
            'sent_at' => now()->subDays(10),
            'paid_at' => now()->subDays(5),
        ]);
    }
}
