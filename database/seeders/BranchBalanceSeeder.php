<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class BranchBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder initializes branch balances for all active branches
     * with default amounts for each currency.
     */
    public function run(): void
    {
        // Get all active branches
        $branches = Branch::where('status', 'active')->get();
        
        // Get all active currencies
        $currencies = Currency::where('is_active', true)->get();

        // Define default balances by currency (in units)
        $defaultBalances = [
            'USD' => 10000.00,    // 10,000 USD
            'EUR' => 5000.00,     // 5,000 EUR
            'CDF' => 2000000.00,  // 2,000,000 CDF (Franc Congolais)
            'GBP' => 3000.00,     // 3,000 GBP
            'ZAR' => 50000.00,    // 50,000 ZAR (Rand Sud-Africain)
        ];

        foreach ($branches as $branch) {
            $this->command->info("Setting up balances for branch: {$branch->name}");
            
            foreach ($currencies as $currency) {
                // Get default balance for this currency, or 0 if not defined
                $amount = $defaultBalances[$currency->code] ?? 0;
                
                // Create or update balance
                $branch->getOrCreateBalance($currency->code)->update([
                    'cash_balance' => $amount
                ]);
                
                $this->command->info("  - {$currency->code}: {$amount}");
            }
        }

        $this->command->info("\n✓ Branch balances initialized successfully!");
        $this->command->info("Total branches: " . $branches->count());
        $this->command->info("Total currencies: " . $currencies->count());
    }
}
