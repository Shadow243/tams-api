<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountInterestSetting;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InterestCalculationService
{
    public function __construct(
        private CustomerAccountService $accountService
    ) {}

    /**
     * Process all due interests for all accounts.
     *
     * @param User|null $user System user for automated processes
     * @return array Summary of processed interests
     */
    public function processAllDueInterests(?User $user = null): array
    {
        // Get system user if not provided
        $user = $user ?? $this->getSystemUser();

        $summary = [
            'total_processed' => 0,
            'total_amount' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get all active interest settings that are due
        $dueSettings = AccountInterestSetting::with(['customerAccount.customer'])
            ->active()
            ->due()
            ->get();

        Log::info('Starting interest calculation process', [
            'total_due' => $dueSettings->count(),
        ]);

        foreach ($dueSettings as $setting) {
            $account = $setting->customerAccount;

            if (!$account->isActive()) {
                continue;
            }

            try {
                $transaction = $this->accountService->applyInterest($account, $user);

                if ($transaction) {
                    $summary['total_processed']++;
                    $summary['success']++;
                    $summary['total_amount'] += (float) $transaction->amount;

                    Log::info('Interest applied successfully', [
                        'account_number' => $account->account_number,
                        'customer' => $account->customer->full_name,
                        'amount' => $transaction->amount,
                        'type' => $transaction->type->value,
                    ]);
                }
            } catch (\Exception $e) {
                $summary['failed']++;
                $summary['errors'][] = [
                    'account_number' => $account->account_number,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to apply interest', [
                    'account_number' => $account->account_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Interest calculation process completed', $summary);

        return $summary;
    }

    /**
     * Process interest for a specific account.
     *
     * @param CustomerAccount $account
     * @param User $user
     * @return bool
     */
    public function processAccountInterest(CustomerAccount $account, User $user): bool
    {
        try {
            $transaction = $this->accountService->applyInterest($account, $user);
            return $transaction !== null;
        } catch (\Exception $e) {
            Log::error('Failed to process account interest', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get system user for automated processes.
     *
     * @return User
     */
    private function getSystemUser(): User
    {
        // Try to find a system user or use the first admin
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'super-admin');
        })->first() ?? User::first();
    }

    /**
     * Simulate interest calculation without applying it.
     *
     * @param CustomerAccount $account
     * @return array
     */
    public function simulateInterest(CustomerAccount $account): array
    {
        $interestSettings = $account->interestSettings;

        if (!$interestSettings || !$interestSettings->is_active) {
            return [
                'applicable' => false,
                'reason' => 'Aucun paramètre d\'intérêt actif',
            ];
        }

        $balance = (float) $account->balance;

        if (!$interestSettings->shouldApplyInterest($balance)) {
            $reasons = [];

            if ($balance < 0 && !$interestSettings->apply_on_negative_balance) {
                $reasons[] = 'Les intérêts ne sont pas applicables sur les soldes négatifs';
            }

            if ($balance > 0 && !$interestSettings->apply_on_positive_balance) {
                $reasons[] = 'Les intérêts ne sont pas applicables sur les soldes positifs';
            }

            if ($balance == 0) {
                $reasons[] = 'Le solde est nul';
            }

            return [
                'applicable' => false,
                'reason' => implode('. ', $reasons),
                'next_application_date' => $interestSettings->next_application_date,
            ];
        }

        $interestAmount = $interestSettings->calculateInterestAmount($balance);
        $isDebt = $balance < 0;
        $balanceAfter = $isDebt ? $balance - $interestAmount : $balance + $interestAmount;

        return [
            'applicable' => true,
            'current_balance' => $balance,
            'interest_amount' => $interestAmount,
            'balance_after' => $balanceAfter,
            'interest_type' => $interestSettings->interest_type->label(),
            'interest_rate' => $interestSettings->interest_rate,
            'fixed_amount' => $interestSettings->fixed_amount,
            'application_period' => $interestSettings->application_period->label(),
            'is_debt_interest' => $isDebt,
            'next_application_date' => $interestSettings->next_application_date,
        ];
    }
}
