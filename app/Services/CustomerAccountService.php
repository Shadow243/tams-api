<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountTransactionType;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CustomerAccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerAccountService
{
    /**
     * Deposit money into a customer account.
     *
     * @param CustomerAccount $account
     * @param float $amount
     * @param User $user
     * @param string|null $description
     * @param Transaction|null $transaction
     * @return AccountTransaction
     * @throws \Exception
     */
    public function deposit(
        CustomerAccount $account,
        float $amount,
        User $user,
        ?string $description = null,
        ?Transaction $transaction = null,
        ?int $branchId = null
    ): AccountTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du dépôt doit être positif.');
        }

        if (!$account->isActive()) {
            throw new \Exception('Le compte n\'est pas actif.');
        }

        $branchId = $branchId ?? $user->branch_id;

        return DB::transaction(function () use ($account, $amount, $user, $description, $transaction, $branchId) {
            $account = CustomerAccount::where('id', $account->id)->lockForUpdate()->first();
            $account->loadMissing('currency');

            $balanceBefore = $account->balance;
            $balanceAfter  = $balanceBefore + $amount;

            $account->update(['balance' => $balanceAfter]);

            // Branch receives cash (credit) when client deposits into TAMS account
            if ($branchId) {
                $this->updateBranchCash($branchId, $amount, $account->currency?->code ?? 'CDF');
            }

            $accountTransaction = AccountTransaction::create([
                'customer_account_id' => $account->id,
                'transaction_id' => $transaction?->id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'type' => AccountTransactionType::DEPOSIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'Dépôt sur le compte',
            ]);

            $accountTransaction->load(['customerAccount.customer', 'customerAccount.currency', 'branch']);
            $this->notifyAccountOperation($accountTransaction, $user, $branchId);

            Log::info('Deposit made to customer account', [
                'account_id'     => $account->id,
                'account_number' => $account->account_number,
                'amount'         => $amount,
                'balance_after'  => $balanceAfter,
                'branch_id'      => $branchId,
                'user_id'        => $user->id,
            ]);

            return $accountTransaction;
        });
    }

    /**
     * Withdraw money from a customer account.
     *
     * @param CustomerAccount $account
     * @param float $amount
     * @param User $user
     * @param string|null $description
     * @param Transaction|null $transaction
     * @return AccountTransaction
     * @throws \Exception
     */
    public function withdraw(
        CustomerAccount $account,
        float $amount,
        User $user,
        ?string $description = null,
        ?Transaction $transaction = null,
        ?int $branchId = null
    ): AccountTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du retrait doit être positif.');
        }

        if (!$account->isActive()) {
            throw new \Exception('Le compte n\'est pas actif.');
        }

        if (!$account->hasSufficientBalance($amount)) {
            throw new \Exception(
                sprintf(
                    'Solde insuffisant. Solde disponible: %.2f (solde: %.2f + crédit: %.2f)',
                    $account->available_balance,
                    $account->balance,
                    $account->credit_limit
                )
            );
        }

        $branchId = $branchId ?? $user->branch_id;

        return DB::transaction(function () use ($account, $amount, $user, $description, $transaction, $branchId) {
            $account = CustomerAccount::where('id', $account->id)->lockForUpdate()->first();
            $account->loadMissing('currency');

            $balanceBefore = $account->balance;
            $balanceAfter  = $balanceBefore - $amount;

            $account->update(['balance' => $balanceAfter]);

            // Branch gives out cash (debit) when client withdraws from TAMS account
            if ($branchId) {
                $this->updateBranchCash($branchId, -$amount, $account->currency?->code ?? 'CDF');
            }

            $accountTransaction = AccountTransaction::create([
                'customer_account_id' => $account->id,
                'transaction_id' => $transaction?->id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'type' => AccountTransactionType::WITHDRAWAL,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'Retrait du compte',
            ]);

            $accountTransaction->load(['customerAccount.customer', 'customerAccount.currency', 'branch']);
            $this->notifyAccountOperation($accountTransaction, $user, $branchId);

            Log::info('Withdrawal made from customer account', [
                'account_id'     => $account->id,
                'account_number' => $account->account_number,
                'amount'         => $amount,
                'balance_after'  => $balanceAfter,
                'is_in_debt'     => $balanceAfter < 0,
                'branch_id'      => $branchId,
                'user_id'        => $user->id,
            ]);

            return $accountTransaction;
        });
    }

    /**
     * Send notification to the cashier and all users of the branch.
     */
    private function notifyAccountOperation(
        AccountTransaction $accountTransaction,
        User $operator,
        ?int $branchId
    ): void {
        $notification = new CustomerAccountNotification($accountTransaction);

        // Notify the cashier who performed the operation
        $operator->notify($notification);

        // Notify all users of the branch (excluding the cashier to avoid double notification)
        if ($branchId) {
            User::where('branch_id', $branchId)
                ->where('id', '!=', $operator->id)
                ->get()
                ->each(fn(User $u) => $u->notify($notification));
        }
    }

    /**
     * Credit (+) or debit (–) a branch's cash balance for a given currency.
     * Throws if the result would be negative (insufficient cash for a withdrawal).
     */
    private function updateBranchCash(int $branchId, float $delta, string $currencyCode): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;

        $balance     = $branch->getOrCreateBalance($currencyCode);
        $current     = (float) $balance->cash_balance;
        $newBalance  = $current + $delta;

        if ($newBalance < 0) {
            throw new \Exception(sprintf(
                'Solde de caisse insuffisant en %s pour l\'agence %s (disponible: %s, requis: %s)',
                $currencyCode,
                $branch->name,
                number_format($current, 2),
                number_format(abs($delta), 2)
            ));
        }

        $balance->update(['cash_balance' => $newBalance]);
    }

    /**
     * Apply interest to a customer account.
     *
     * @param CustomerAccount $account
     * @param User $user
     * @return AccountTransaction|null
     * @throws \Exception
     */
    public function applyInterest(CustomerAccount $account, User $user): ?AccountTransaction
    {
        if (!$account->isActive()) {
            throw new \Exception('Le compte n\'est pas actif.');
        }

        $interestSettings = $account->interestSettings;

        if (!$interestSettings || !$interestSettings->is_active) {
            return null;
        }

        if (!$interestSettings->shouldApplyInterest($account->balance)) {
            return null;
        }

        return DB::transaction(function () use ($account, $user, $interestSettings) {
            // Lock the account row for update
            $account = CustomerAccount::where('id', $account->id)->lockForUpdate()->first();

            $balanceBefore = $account->balance;
            
            // Calculate interest on the absolute value of the balance
            // If balance = -500$ (debt), interest is calculated on 500$ only
            // Not on the total amount withdrawn
            $interestAmount = $interestSettings->calculateInterestAmount($balanceBefore);

            // Determine if interest is credit or debit
            // Negative balance (debt) -> charge interest (debit), which increases the debt
            // Positive balance (savings) -> pay interest (credit), which increases the balance
            $isDebt = $balanceBefore < 0;
            $transactionType = $isDebt
                ? AccountTransactionType::INTEREST_DEBIT
                : AccountTransactionType::INTEREST_CREDIT;

            $balanceAfter = $isDebt
                ? $balanceBefore - $interestAmount
                : $balanceBefore + $interestAmount;

            // Update account balance
            $account->update(['balance' => $balanceAfter]);

            // Create transaction record
            $accountTransaction = AccountTransaction::create([
                'customer_account_id' => $account->id,
                'user_id' => $user->id,
                'type' => $transactionType,
                'amount' => $interestAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => sprintf(
                    'Intérêt %s pour la période %s',
                    $isDebt ? 'débiteur' : 'créditeur',
                    $interestSettings->application_period->label()
                ),
            ]);

            // Mark interest as applied
            $interestSettings->markAsApplied();

            Log::info('Interest applied to customer account', [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'interest_amount' => $interestAmount,
                'transaction_type' => $transactionType->value,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return $accountTransaction;
        });
    }

    /**
     * Make a balance adjustment.
     *
     * @param CustomerAccount $account
     * @param float $amount (positive for increase, negative for decrease)
     * @param User $user
     * @param string $description
     * @return AccountTransaction
     * @throws \Exception
     */
    public function adjust(
        CustomerAccount $account,
        float $amount,
        User $user,
        string $description
    ): AccountTransaction {
        if ($amount == 0) {
            throw new \InvalidArgumentException('Le montant de l\'ajustement ne peut pas être zéro.');
        }

        if (!$account->isActive()) {
            throw new \Exception('Le compte n\'est pas actif.');
        }

        return DB::transaction(function () use ($account, $amount, $user, $description) {
            // Lock the account row for update
            $account = CustomerAccount::where('id', $account->id)->lockForUpdate()->first();

            $balanceBefore = $account->balance;
            $balanceAfter = $balanceBefore + $amount;

            // Update account balance
            $account->update(['balance' => $balanceAfter]);

            // Create transaction record
            $accountTransaction = AccountTransaction::create([
                'customer_account_id' => $account->id,
                'user_id' => $user->id,
                'type' => AccountTransactionType::ADJUSTMENT,
                'amount' => abs($amount),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
            ]);

            Log::info('Adjustment made to customer account', [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'user_id' => $user->id,
                'description' => $description,
            ]);

            return $accountTransaction;
        });
    }

    /**
     * Get account statement for a period.
     *
     * @param CustomerAccount $account
     * @param \Carbon\Carbon|null $from
     * @param \Carbon\Carbon|null $to
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStatement(
        CustomerAccount $account,
        ?\Carbon\Carbon $from = null,
        ?\Carbon\Carbon $to = null
    ) {
        $query = $account->transactions()->with('user');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
