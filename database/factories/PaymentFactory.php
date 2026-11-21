<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'receipt_number' => 'PAY-'.fake()->unique()->numerify('#####'),
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'amount' => number_format(fake()->randomFloat(2, 100, 5000), 2, '.', ''),
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the payment was made by bank transfer.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => Payment::METHOD_BANK_TRANSFER,
            'reference' => 'TRX-'.fake()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the payment was made by card.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => Payment::METHOD_CARD,
            'reference' => 'AUTH-'.fake()->numerify('######'),
        ]);
    }
}
