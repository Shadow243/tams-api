<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InterestCalculationService;
use Illuminate\Console\Command;

class ProcessAccountInterests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:process-interests
                            {--dry-run : Simulate without actually applying interests}
                            {--account= : Process interest for a specific account number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and apply due interests to customer accounts';

    /**
     * Execute the console command.
     */
    public function handle(InterestCalculationService $interestService): int
    {
        $this->info('Starting interest calculation process...');

        if ($this->option('dry-run')) {
            $this->warn('Running in DRY-RUN mode - no changes will be made');
            return self::SUCCESS;
        }

        $accountNumber = $this->option('account');

        if ($accountNumber) {
            return $this->processSpecificAccount($accountNumber, $interestService);
        }

        return $this->processAllAccounts($interestService);
    }

    /**
     * Process interests for all accounts.
     */
    private function processAllAccounts(InterestCalculationService $interestService): int
    {
        $summary = $interestService->processAllDueInterests();

        $this->newLine();
        $this->info('Interest calculation completed!');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $summary['total_processed']],
                ['Successful', $summary['success']],
                ['Failed', $summary['failed']],
                ['Total Amount', number_format($summary['total_amount'], 2)],
            ]
        );

        if (!empty($summary['errors'])) {
            $this->newLine();
            $this->error('Errors occurred:');
            foreach ($summary['errors'] as $error) {
                $this->line("  - Account {$error['account_number']}: {$error['error']}");
            }
        }

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process interest for a specific account.
     */
    private function processSpecificAccount(string $accountNumber, InterestCalculationService $interestService): int
    {
        $account = \App\Models\CustomerAccount::where('account_number', $accountNumber)->first();

        if (!$account) {
            $this->error("Account {$accountNumber} not found");
            return self::FAILURE;
        }

        $this->info("Processing interest for account: {$accountNumber}");
        $this->line("Customer: {$account->customer->full_name}");
        $this->line("Current balance: {$account->balance}");

        $user = \App\Models\User::first();
        $success = $interestService->processAccountInterest($account, $user);

        if ($success) {
            $account->refresh();
            $this->info("Interest applied successfully!");
            $this->line("New balance: {$account->balance}");
            return self::SUCCESS;
        }

        $this->error("Failed to apply interest");
        return self::FAILURE;
    }
}
