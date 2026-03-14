<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WalletStatus;
use App\Models\Wallet;
use App\Models\Branch;
use App\Models\Operator;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random active currency, or create one if none exists
        $currency = Currency::where('is_active', true)->inRandomOrder()->first();
        
        return [
            'branch_id' => Branch::factory(),
            'operator_id' => Operator::factory(),
            'wallet_number' => fake()->unique()->numerify('##########'),
            'balance' => fake()->randomFloat(2, 0, 50000),
            'currency_id' => $currency?->id ?? Currency::first()?->id ?? 1,
            'status' => fake()->randomElement(WalletStatus::cases()),
        ];
    }

    /**
     * Indicate that the wallet is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WalletStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the wallet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WalletStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the wallet has zero balance.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
        ]);
    }

    /**
     * Indicate that the wallet has a specific currency.
     */
    public function currency(int $currencyId): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_id' => $currencyId,
        ]);
    }
}
