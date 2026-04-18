<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Console\Command;

class InitializeBranchBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'branches:init-balances 
                            {--branch= : Specific branch ID or code}
                            {--currency= : Specific currency code}
                            {--amount=0 : Default amount to set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize or reset branch cash balances for currencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchFilter = $this->option('branch');
        $currencyFilter = $this->option('currency');
        $defaultAmount = (float) $this->option('amount');

        // Get branches
        if ($branchFilter) {
            $branches = Branch::where('id', $branchFilter)
                ->orWhere('code', $branchFilter)
                ->get();
                
            if ($branches->isEmpty()) {
                $this->error("Branch not found: {$branchFilter}");
                return 1;
            }
        } else {
            $branches = Branch::where('status', 'active')->get();
        }

        // Get currencies
        if ($currencyFilter) {
            $currencies = Currency::where('code', $currencyFilter)
                ->where('is_active', true)
                ->get();
                
            if ($currencies->isEmpty()) {
                $this->error("Currency not found or inactive: {$currencyFilter}");
                return 1;
            }
        } else {
            $currencies = Currency::where('is_active', true)->get();
        }

        // Confirm action
        $branchesCount = $branches->count();
        $currenciesCount = $currencies->count();
        
        $this->info("About to initialize balances:");
        $this->info("  - Branches: {$branchesCount}");
        $this->info("  - Currencies: {$currenciesCount}");
        $this->info("  - Default amount: {$defaultAmount}");
        
        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Initialize balances
        $this->info("\nInitializing balances...\n");
        
        $progressBar = $this->output->createProgressBar($branchesCount * $currenciesCount);
        $progressBar->start();

        foreach ($branches as $branch) {
            foreach ($currencies as $currency) {
                $branchBalance = $branch->getOrCreateBalance($currency->code);
                
                // Only update if balance is 0 (don't overwrite existing balances)
                if ($branchBalance->cash_balance == 0) {
                    $branchBalance->update(['cash_balance' => $defaultAmount]);
                }
                
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        
        $this->newLine(2);
        $this->info('✓ Branch balances initialized successfully!');
        
        return 0;
    }
}
