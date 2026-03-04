<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionType>
 */
class TransactionTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            ['code' => 'cash_deposit_transfer', 'name' => 'Cash Deposit Transfer', 'description' => 'Transfer cash deposit to wallet'],
            ['code' => 'cash_withdraw_transfer', 'name' => 'Cash Withdraw Transfer', 'description' => 'Transfer cash withdrawal from wallet'],
            ['code' => 'wallet_cash_in', 'name' => 'Wallet Cash In', 'description' => 'Cash in to wallet'],
            ['code' => 'wallet_cash_out', 'name' => 'Wallet Cash Out', 'description' => 'Cash out from wallet'],
            ['code' => 'tams_deposit', 'name' => 'TAMS Deposit', 'description' => 'Deposit to TAMS system'],
            ['code' => 'tams_withdraw', 'name' => 'TAMS Withdraw', 'description' => 'Withdraw from TAMS system'],
        ];

        $type = fake()->randomElement($types);

        return [
            'code' => $type['code'] . '_' . fake()->unique()->randomNumber(5),
            'name' => $type['name'],
            'description' => $type['description'],
        ];
    }

    /**
     * State for cash deposit transfer type
     */
    public function cashDepositTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'cash_deposit_transfer',
            'name' => 'Cash Deposit Transfer',
            'description' => 'Transfer cash deposit to wallet',
        ]);
    }

    /**
     * State for cash withdraw transfer type
     */
    public function cashWithdrawTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'cash_withdraw_transfer',
            'name' => 'Cash Withdraw Transfer',
            'description' => 'Transfer cash withdrawal from wallet',
        ]);
    }

    /**
     * State for wallet cash in type
     */
    public function walletCashIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'wallet_cash_in',
            'name' => 'Wallet Cash In',
            'description' => 'Cash in to wallet',
        ]);
    }

    /**
     * State for wallet cash out type
     */
    public function walletCashOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'wallet_cash_out',
            'name' => 'Wallet Cash Out',
            'description' => 'Cash out from wallet',
        ]);
    }

    /**
     * State for TAMS deposit type
     */
    public function tamsDeposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'tams_deposit',
            'name' => 'TAMS Deposit',
            'description' => 'Deposit to TAMS system',
        ]);
    }

    /**
     * State for TAMS withdraw type
     */
    public function tamsWithdraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'tams_withdraw',
            'name' => 'TAMS Withdraw',
            'description' => 'Withdraw from TAMS system',
        ]);
    }
}
