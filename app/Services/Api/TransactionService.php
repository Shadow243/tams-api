<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
use App\Http\Resources\Api\TransactionResource;
use App\Models\FeeRule;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BalanceUpdateNotification;
use App\Services\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class TransactionService
 * @package App\Services\Api
 */
final class TransactionService
{
    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Generate reference if not provided
            if (!isset($data['reference'])) {
                $data['reference'] = Transaction::generateReference();
            }

            // Set user_id from authenticated user if not provided
            if (!isset($data['user_id'])) {
                $data['user_id'] = auth()->id();
            }

            // Set default status
            if (!isset($data['status'])) {
                $data['status'] = TransactionStatus::PENDING;
            }

            // Calculate fee if not provided, or get fee rule info if fee_amount is provided
            if (!isset($data['fee_amount'])) {
                // No fee amount provided, calculate it automatically
                $feeCalculation = $this->calculateFee($data);
                $data['fee_amount'] = $feeCalculation['fee_amount'];
                $data['fee_mode_applied'] = $feeCalculation['fee_mode_applied'];
                $data['fee_rule_id'] = $feeCalculation['fee_rule_id'];
                $data['fee_snapshot'] = $feeCalculation['fee_snapshot'];
            } else {
                // Fee amount provided (e.g., negotiable mode), but we need to get the rule info
                if (!isset($data['fee_mode_applied']) || !isset($data['fee_rule_id'])) {
                    $feeInfo = $this->getFeeRuleInfo($data);
                    $data['fee_mode_applied'] = $data['fee_mode_applied'] ?? $feeInfo['fee_mode_applied'];
                    $data['fee_rule_id'] = $data['fee_rule_id'] ?? $feeInfo['fee_rule_id'];
                    $data['fee_snapshot'] = $data['fee_snapshot'] ?? $feeInfo['fee_snapshot'];
                }
            }

            $data['id'] = (new Transaction)->newUniqueId();

            // Calculate net amount
            $data['net_amount'] = (float) $data['gross_amount'] - (float) $data['fee_amount'];

            // Validate balances before creating transaction
            $this->validateBalancesBeforeCreate($data);

            return Transaction::create($data);
        });
    }

    /**
     * Update the specified resource in storage.
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Recalculate net amount if amounts changed
            if (isset($data['gross_amount']) || isset($data['fee_amount'])) {
                $gross = (float) ($data['gross_amount'] ?? $transaction->gross_amount);
                $fee = (float) ($data['fee_amount'] ?? $transaction->fee_amount);
                $data['net_amount'] = $gross - $fee;
            }

            // Re-validate balances if any balance-affecting field changes
            $balanceFields = ['gross_amount', 'branch_id', 'wallet_id', 'destination_branch_id', 'transaction_type_id'];
            if (array_intersect_key($data, array_flip($balanceFields))) {
                $merged = array_merge($transaction->toArray(), $data);
                $this->validateBalancesBeforeCreate($merged);
            }

            $transaction->update($data);
            return $transaction->fresh();
        });
    }

    /**
     * Remove the specified resource from storage.
     * @param Transaction $transaction
     */
    public function destroy(Transaction $transaction): void
    {
        $transaction->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTransactions(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        return $this->buildTransactionQuery($request)->latest()->paginate($perPage);
    }

    /**
     * Get all transactions matching the request filters (no pagination).
     * Use for exports where the full result set is needed.
     *
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    public function getAllTransactions(Request $request): \Illuminate\Support\Collection
    {
        return $this->buildTransactionQuery($request)->latest()->get();
    }

    /**
     * Build a filtered Transaction query from the request without applying pagination.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTransactionQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $search            = $request->input('search');
        $transactionTypeId = $request->input('transaction_type_id');
        $branchId          = $request->input('branch_id');
        $userId            = $request->input('user_id');
        $completedBy       = $request->input('completed_by');
        $servedByBranchId  = $request->input('served_by_branch_id');
        $customerId        = $request->input('customer_id');
        $customerPhone     = $request->input('customer_phone');
        $status            = $request->input('status');
        $startDate         = $request->input('start_date');
        $endDate           = $request->input('end_date');

        $query = Transaction::query()
            ->with([
                'transactionType',
                'branch',
                'destinationBranch',
                'user',
                'completedBy',
                'servedByBranch',
                'customer',
                'destCustomer',
                'wallet',
                'destWallet',
                'feeRule',
                'currency',
            ])
            ->select([
                'id', 'uuid', 'reference', 'transaction_type_id', 'branch_id',
                'destination_branch_id', 'user_id', 'completed_by', 'served_by_branch_id',
                'customer_id', 'dest_customer_id', 'wallet_id', 'dest_wallet_id', 'customer_phone',
                'gross_amount', 'fee_amount', 'net_amount',
                'currency_id', 'currency_code',
                'fee_rule_id', 'fee_mode_applied', 'parent_transaction_id',
                'withdrawal_code', 'expires_at', 'completed_at', 'status', 'description', 'created_at', 'updated_at'
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('customer_phone', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('withdrawal_code', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhereHas('transactionType', function ($query) use ($search) {
                      $query->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                            ->orWhere('code', 'like', '%' . addcslashes($search, '%_\\') . '%');
                  })
                  ->orWhereHas('customer', function ($query) use ($search) {
                      $query->where('full_name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                            ->orWhere('phone', 'like', '%' . addcslashes($search, '%_\\') . '%');
                  });
            });
        }

        if ($transactionTypeId) {
            $query->where('transaction_type_id', $transactionTypeId);
        }

        // Agents are automatically scoped to their branch:
        // transactions they created OR transfers sent to their branch (to validate & serve)
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->branch_id && $currentUser->hasRole('agent')) {
            $agentBranchId = $currentUser->branch_id;
            $query->where(function ($q) use ($agentBranchId) {
                $q->where('branch_id', $agentBranchId)
                  ->orWhere('destination_branch_id', $agentBranchId);
            });
        } elseif ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('destination_branch_id', $branchId);
            });
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($completedBy) {
            $query->where('completed_by', $completedBy);
        }

        if ($servedByBranchId) {
            $query->where('served_by_branch_id', $servedByBranchId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($customerPhone) {
            $query->where('customer_phone', $customerPhone);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query;
    }

    /**
     * Change transaction status
     * @param Transaction $transaction
     * @param TransactionStatus $status
     * @return Transaction
     */
    public function changeStatus(Transaction $transaction, TransactionStatus $status): Transaction
    {
        return DB::transaction(function () use ($transaction, $status) {
            $transaction->update(['status' => $status]);
            return $transaction->fresh();
        });
    }

    /**
     * Cancel a transaction
     * @param Transaction $transaction
     * @return Transaction
     */
    public function cancel(Transaction $transaction): Transaction
    {
        if (!$transaction->canBeCancelled()) {
            throw new \Exception(__('This transaction cannot be cancelled'));
        }

        return $this->changeStatus($transaction, TransactionStatus::CANCELLED);
    }

    /**
     * Complete a transaction
     * @param Transaction $transaction
     * @return Transaction
     */
    public function complete(Transaction $transaction): Transaction
    {
        if ($transaction->isCompleted()) {
            throw new \Exception(__('messages.transaction_already_completed'));
        }

        return DB::transaction(function () use ($transaction) {
            // Load necessary relationships
            $transaction->load(['transactionType', 'branch', 'destinationBranch', 'wallet', 'destWallet', 'currency', 'customerAccount']);
            
            // Update balances based on transaction type
            $this->updateBalances($transaction);
            
            // Get current authenticated user's branch
            $currentUser = auth()->user();
            $currentBranchId = $currentUser->branch_id ?? null;
            
            // Change status to completed with tracking info
            $transaction->update([
                'status' => TransactionStatus::COMPLETED,
                'completed_by' => $currentUser->id,
                'served_by_branch_id' => $currentBranchId,
                'completed_at' => now(),
            ]);
            
            return $transaction->fresh();
        });
    }

    /**
     * Update balances based on transaction type
     * @param Transaction $transaction
     * @return void
     */
    private function updateBalances(Transaction $transaction): void
    {
        $type        = $transaction->transactionType;
        $gross       = (float) $transaction->gross_amount;
        $fee         = (float) $transaction->fee_amount;
        $net         = $gross - $fee;  // Recalculate to ensure consistency
        $currency    = $transaction->currency->code;

        // Helper: resolve amount by config key
        $resolve = fn(string $amountKey): float => match($amountKey) {
            'fee'  => $fee,
            'net'  => $net,
            default => $gross,   // 'gross'
        };

        // ── Branch (source) ──────────────────────────────────────────────
        // Branch cash: what the branch physically receives (+) or gives out (-)
        $branchEffect = $type->branch_effect ?? 'none';
        if ($branchEffect !== 'none' && $transaction->branch) {
            $delta = $resolve($type->branch_amount ?? 'gross');
            $this->updateBranchCash(
                $transaction->branch,
                $branchEffect === 'debit' ? -$delta : $delta,
                $currency,
                $transaction
            );
        }

        // ── Wallet ───────────────────────────────────────────────────────
        // Wallet virtual: what the wallet balance changes by (includes fees)
        $walletEffect = $type->wallet_effect ?? 'none';
        if ($walletEffect !== 'none' && $transaction->wallet) {
            $delta = $resolve($type->wallet_amount ?? 'gross');
            $this->updateWalletVirtual(
                $transaction->wallet,
                $walletEffect === 'debit' ? -$delta : $delta,
                $transaction
            );
        }

        // ── Destination branch ───────────────────────────────────────────
        $destEffect = $type->dest_branch_effect ?? 'none';
        if ($destEffect !== 'none' && $transaction->destinationBranch) {
            $delta = $resolve($type->dest_branch_amount ?? 'gross');
            $this->updateBranchCash(
                $transaction->destinationBranch,
                $destEffect === 'debit' ? -$delta : $delta,
                $currency,
                $transaction
            );
        }

        // ── Destination wallet ───────────────────────────────────────────
        $destWalletEffect = $type->dest_wallet_effect ?? 'none';
        if ($destWalletEffect !== 'none' && $transaction->destWallet) {
            $delta = $resolve($type->dest_wallet_amount ?? 'gross');
            $this->updateWalletVirtual(
                $transaction->destWallet,
                $destWalletEffect === 'debit' ? -$delta : $delta,
                $transaction
            );
        }

        // ── Customer TAMS account ────────────────────────────────────────
        $accountEffect = $type->customer_account_effect ?? 'none';
        if ($accountEffect !== 'none' && $transaction->customerAccount) {
            $delta  = $resolve($type->customer_account_amount ?? 'gross');
            $user   = auth()->user();
            $desc   = "Transaction {$transaction->reference}";
            $svc    = app(CustomerAccountService::class);

            if ($accountEffect === 'credit') {
                $svc->deposit($transaction->customerAccount, $delta, $user, $desc, $transaction);
            } else {
                $svc->withdraw($transaction->customerAccount, $delta, $user, $desc, $transaction);
            }
        }
    }

    /**
     * Update branch cash balance for a specific currency
     * @param \App\Models\Branch $branch
     * @param float $amount
     * @param string $currencyCode
     * @param Transaction $transaction
     * @return void
     */
    private function updateBranchCash($branch, float $amount, string $currencyCode, Transaction $transaction): void
    {
        // Get or create balance record for this currency
        $branchBalance = $branch->getOrCreateBalance($currencyCode);
        
        // Ensure cash_balance is a float
        $currentBalance = (float) $branchBalance->cash_balance;
        
        // Calculate new balance
        $newBalance = $currentBalance + $amount;
        
        // Check for negative balance
        if ($newBalance < 0) {
            throw new \Exception(sprintf(
                'Insufficient %s cash balance in branch %s. Required: %s, Available: %s',
                $currencyCode,
                $branch->name,
                number_format(abs($amount), 2),
                number_format($currentBalance, 2)
            ));
        }
        
        // Update balance
        $branchBalance->update(['cash_balance' => $newBalance]);

        // Send notification to users of this branch and the transaction creator
        $this->notifyBalanceUpdate(
            'branch',
            $branch->id,
            $branch->name,
            $currentBalance,
            $newBalance,
            $amount,
            $currencyCode,
            $transaction,
            $branch->id
        );
    }

    /**
     * Update wallet virtual balance
     * @param \App\Models\Wallet $wallet
     * @param float $amount
     * @param Transaction $transaction
     * @return void
     */
    private function updateWalletVirtual($wallet, float $amount, Transaction $transaction): void
    {
        $currentBalance = (float) $wallet->virtual_balance;
        $newBalance = $currentBalance + $amount;
        
        // Check for negative balance
        if ($newBalance < 0) {
            throw new \Exception(__('Insufficient virtual balance in wallet :number. Available: :balance', [
                'number' => $wallet->wallet_number,
                'balance' => number_format($currentBalance, 2)
            ]));
        }
        
        $wallet->update(['virtual_balance' => $newBalance]);

        // Send notification to transaction creator (wallet operations are typically self-service)
        $this->notifyBalanceUpdate(
            'wallet',
            $wallet->id,
            $wallet->wallet_number ?? "Wallet #{$wallet->id}",
            $currentBalance,
            $newBalance,
            $amount,
            $transaction->currency->code,
            $transaction,
            null  // No branch association for wallet notifications
        );
    }

    /**
     * Send balance update notification to relevant users
     * @param string $entityType
     * @param int|string $entityId
     * @param string $entityName
     * @param float $oldBalance
     * @param float $newBalance
     * @param float $amount
     * @param string $currencyCode
     * @param Transaction $transaction
     * @param int|null $branchId
     * @return void
     */
    private function notifyBalanceUpdate(
        string $entityType,
        int|string $entityId,
        string $entityName,
        float $oldBalance,
        float $newBalance,
        float $amount,
        string $currencyCode,
        Transaction $transaction,
        ?int $branchId
    ): void {
        $notification = new BalanceUpdateNotification(
            $entityType,
            $entityId,
            $entityName,
            $oldBalance,
            $newBalance,
            $amount,
            $currencyCode,
            $transaction
        );

        // Always notify the transaction creator
        $creator = User::find($transaction->user_id);
        if ($creator) {
            $creator->notify($notification);
        }

        // If there's a branch associated, notify branch users with appropriate permissions
        if ($branchId) {
            $branchUsers = User::where('branch_id', $branchId)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'branch_manager', 'cashier']);
                })
                ->where('id', '!=', $transaction->user_id) // Don't notify creator twice
                ->get();

            foreach ($branchUsers as $user) {
                $user->notify($notification);
            }
        }
    }

    /**
     * Validate balances before creating a transaction
     * This prevents creating transactions that will fail at completion
     * 
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function validateBalancesBeforeCreate(array $data): void
    {
        // Load necessary models
        $transactionType = \App\Models\TransactionType::find($data['transaction_type_id']);
        if (!$transactionType) {
            return;
        }

        $gross    = (float) $data['gross_amount'];
        $fee      = (float) ($data['fee_amount'] ?? 0);
        $net      = (float) ($data['net_amount'] ?? $gross - $fee);
        
        // Get currency code from relation
        $currencyModel = \App\Models\Currency::find($data['currency_id'] ?? null);
        $currency = $currencyModel ? $currencyModel->code : 'CDF';

        $branch            = isset($data['branch_id']) ? \App\Models\Branch::find($data['branch_id']) : null;
        $destinationBranch = isset($data['destination_branch_id']) ? \App\Models\Branch::find($data['destination_branch_id']) : null;
        $wallet            = isset($data['wallet_id']) ? \App\Models\Wallet::find($data['wallet_id']) : null;
        $destWallet        = isset($data['dest_wallet_id']) ? \App\Models\Wallet::find($data['dest_wallet_id']) : null;

        $resolve = fn(string $key) => match($key) {
            'fee'  => $fee,
            'net'  => $net,
            default => $gross,
        };

        // Validate branch (source) debit
        if (($transactionType->branch_effect ?? 'none') === 'debit' && $branch) {
            $required  = $resolve($transactionType->branch_amount ?? 'gross');
            $available = $branch->getBalance($currency);
            if ($available < $required) {
                throw new \Exception(__('Insufficient :currency cash balance in branch :name. Required: :required, Available: :balance', [
                    'currency' => $currency,
                    'name'     => $branch->name,
                    'required' => number_format($required, 2),
                    'balance'  => number_format($available, 2),
                ]));
            }
        }

        // Validate destination branch debit
        if (($transactionType->dest_branch_effect ?? 'none') === 'debit' && $destinationBranch) {
            $required  = $resolve($transactionType->dest_branch_amount ?? 'gross');
            $available = $destinationBranch->getBalance($currency);
            if ($available < $required) {
                throw new \Exception(__('Insufficient :currency cash balance in destination branch :name. Required: :required, Available: :balance', [
                    'currency' => $currency,
                    'name'     => $destinationBranch->name,
                    'required' => number_format($required, 2),
                    'balance'  => number_format($available, 2),
                ]));
            }
        }

        // Validate wallet debit
        if (($transactionType->wallet_effect ?? 'none') === 'debit' && $wallet) {
            $required      = $resolve($transactionType->wallet_amount ?? 'gross');
            $walletBalance = (float) $wallet->virtual_balance;
            if ($walletBalance < $required) {
                throw new \Exception(__('Insufficient virtual balance in wallet :number. Required: :required, Available: :balance', [
                    'number'   => $wallet->wallet_number ?? $wallet->id,
                    'required' => number_format($required, 2),
                    'balance'  => number_format($walletBalance, 2),
                ]));
            }
        }

        // Validate destination wallet debit
        if (($transactionType->dest_wallet_effect ?? 'none') === 'debit' && $destWallet) {
            $required          = $resolve($transactionType->dest_wallet_amount ?? 'gross');
            $destWalletBalance = (float) $destWallet->virtual_balance;
            if ($destWalletBalance < $required) {
                throw new \Exception(__('Insufficient virtual balance in destination wallet :number. Required: :required, Available: :balance', [
                    'number'   => $destWallet->wallet_number ?? $destWallet->id,
                    'required' => number_format($required, 2),
                    'balance'  => number_format($destWalletBalance, 2),
                ]));
            }
        }
    }

    /**
     * Calculate fee for a transaction
     * @param array $data
     * @return array
     */
    private function calculateFee(array $data): array
    {
        $feeRuleService = new FeeRuleService();
        
        $feeRule = $feeRuleService->getApplicableFeeRule(
            $data['transaction_type_id'],
            $data['operator_id'] ?? null,
            $data['branch_id']
        );

        if (!$feeRule) {
            return [
                'fee_amount' => 0,
                'fee_mode_applied' => FeeModeApplied::FIXED,
                'fee_rule_id' => null,
                'fee_snapshot' => null,
            ];
        }

        $grossAmount = (float) $data['gross_amount'];
        $feeAmount = $feeRule->calculateFee($grossAmount);

        // Map FeeMode to FeeModeApplied
        $feeModeApplied = match($feeRule->fee_mode) {
            \App\Enums\FeeMode::FIXED => FeeModeApplied::FIXED,
            \App\Enums\FeeMode::PERCENTAGE => FeeModeApplied::PERCENTAGE,
            \App\Enums\FeeMode::NEGOTIABLE => FeeModeApplied::NEGOTIATED,
        };

        return [
            'fee_amount' => $feeAmount,
            'fee_mode_applied' => $feeModeApplied,
            'fee_rule_id' => $feeRule->id,
            'fee_snapshot' => [
                'fee_mode' => $feeRule->fee_mode->value,
                'value' => $feeRule->value,
                'min_fee' => $feeRule->min_fee,
                'max_fee' => $feeRule->max_fee,
            ],
        ];
    }

    /**
     * Get fee rule info without calculating fee amount
     * Used when fee_amount is provided by user (e.g., negotiable mode)
     * @param array $data
     * @return array
     */
    private function getFeeRuleInfo(array $data): array
    {
        $feeRuleService = new FeeRuleService();
        
        $feeRule = $feeRuleService->getApplicableFeeRule(
            $data['transaction_type_id'],
            $data['operator_id'] ?? null,
            $data['branch_id']
        );

        if (!$feeRule) {
            return [
                'fee_mode_applied' => FeeModeApplied::MANUAL_OVERRIDE,
                'fee_rule_id' => null,
                'fee_snapshot' => null,
            ];
        }

        // Map FeeMode to FeeModeApplied
        $feeModeApplied = match($feeRule->fee_mode) {
            \App\Enums\FeeMode::FIXED => FeeModeApplied::FIXED,
            \App\Enums\FeeMode::PERCENTAGE => FeeModeApplied::PERCENTAGE,
            \App\Enums\FeeMode::NEGOTIABLE => FeeModeApplied::NEGOTIATED,
        };

        return [
            'fee_mode_applied' => $feeModeApplied,
            'fee_rule_id' => $feeRule->id,
            'fee_snapshot' => [
                'fee_mode' => $feeRule->fee_mode->value,
                'value' => $feeRule->value,
                'min_fee' => $feeRule->min_fee,
                'max_fee' => $feeRule->max_fee,
            ],
        ];
    }

    /**
     * Verify withdrawal code
     * @param string $code
     * @return Transaction|null
     */
    public function verifyWithdrawalCode(string $code): ?Transaction
    {
        return Transaction::where('withdrawal_code', $code)
            ->where('status', TransactionStatus::AVAILABLE->value)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Get transaction statistics
     * @param Request $request
     * @return array
     */
    public function getStatistics(Request $request): array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        $currencyId = $request->input('currency_id');

        // Single aggregate query: counts, sums, and status breakdown in one pass
        $stats = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->selectRaw("
                COUNT(*) as total_transactions,
                COALESCE(SUM(gross_amount), 0) as total_amount,
                COALESCE(SUM(fee_amount), 0) as total_fees,
                COALESCE(SUM(net_amount), 0) as total_net,
                SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as cnt_pending,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as cnt_available,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as cnt_completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cnt_cancelled,
                SUM(CASE WHEN status = 'failed'    THEN 1 ELSE 0 END) as cnt_failed,
                SUM(CASE WHEN status = 'expired'   THEN 1 ELSE 0 END) as cnt_expired
            ")
            ->first();

        return [
            'total_transactions' => (int) $stats->total_transactions,
            'total_amount'       => (float) $stats->total_amount,
            'total_fees'         => (float) $stats->total_fees,
            'total_net'          => (float) $stats->total_net,
            'by_status' => [
                'pending'   => (int) $stats->cnt_pending,
                'available' => (int) $stats->cnt_available,
                'completed' => (int) $stats->cnt_completed,
                'cancelled' => (int) $stats->cnt_cancelled,
                'failed'    => (int) $stats->cnt_failed,
                'expired'   => (int) $stats->cnt_expired,
            ],
        ];
    }

    /**
     * Get dashboard statistics with extended metrics
     * @param Request $request
     * @return array
     */
    public function getDashboardStatistics(Request $request): array
    {
        $startDate         = $request->input('start_date');
        $endDate           = $request->input('end_date');
        $branchId          = $request->input('branch_id');
        $currencyId        = $request->input('currency_id');
        $transactionTypeId = $request->input('transaction_type_id');
        $userId            = $request->input('user_id');   // filtre agent

        $cacheKey = 'dashboard:stats:' . md5(json_encode([
            $startDate, $endDate, $branchId, $currencyId, $transactionTypeId, $userId,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $startDate, $endDate, $branchId, $currencyId, $transactionTypeId, $userId
        ) {
            // ── Query 1: overview KPIs + status breakdown in a single pass ───
            $stats = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->selectRaw("
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(gross_amount), 0) as total_amount,
                    COALESCE(SUM(fee_amount), 0)   as total_fees,
                    COALESCE(SUM(net_amount), 0)   as total_net,
                    SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as cnt_pending,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as cnt_available,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as cnt_completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cnt_cancelled,
                    SUM(CASE WHEN status = 'failed'    THEN 1 ELSE 0 END) as cnt_failed,
                    SUM(CASE WHEN status = 'expired'   THEN 1 ELSE 0 END) as cnt_expired
                ")
                ->first();

            $totalTransactions    = (int)   $stats->total_transactions;
            $totalAmount          = (float) $stats->total_amount;
            $totalFees            = (float) $stats->total_fees;
            $totalNet             = (float) $stats->total_net;
            $byStatus = [
                'pending'   => (int) $stats->cnt_pending,
                'available' => (int) $stats->cnt_available,
                'completed' => (int) $stats->cnt_completed,
                'cancelled' => (int) $stats->cnt_cancelled,
                'failed'    => (int) $stats->cnt_failed,
                'expired'   => (int) $stats->cnt_expired,
            ];

            $averageAmount      = $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0;
            $successfulCount    = $byStatus['completed'] + $byStatus['available'];
            $successRate        = $totalTransactions > 0
                ? ($successfulCount / $totalTransactions) * 100
                : 0;

            // ── Query 2: transactions by type ────────────────────────────────
            $byType = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->selectRaw('transaction_type_id, COUNT(*) as count, SUM(gross_amount) as total_amount')
                ->groupBy('transaction_type_id')
                ->with('transactionType:id,name,code')
                ->get()
                ->map(fn($item) => [
                    'type_id'      => $item->transaction_type_id,
                    'type_name'    => $item->transactionType->name ?? 'N/A',
                    'type_code'    => $item->transactionType->code ?? 'N/A',
                    'count'        => (int) $item->count,
                    'total_amount' => (float) $item->total_amount,
                ]);

            // ── Query 3: transactions by branch ──────────────────────────────
            $byBranch = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->selectRaw('branch_id, COUNT(*) as count, SUM(gross_amount) as total_amount')
                ->groupBy('branch_id')
                ->with('branch:id,name,code')
                ->get()
                ->map(fn($item) => [
                    'branch_id'    => $item->branch_id,
                    'branch_name'  => $item->branch->name ?? 'N/A',
                    'branch_code'  => $item->branch->code ?? 'N/A',
                    'count'        => (int) $item->count,
                    'total_amount' => (float) $item->total_amount,
                ]);

            // ── Query 4: daily trend grouped by date + currency ───────────────
            // MySQL does not support LIMIT inside an IN() subquery directly.
            // Workaround: fetch the 30 most-recent distinct dates as a plain
            // array first, then use whereIn() with that array.
            $trendDates = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->selectRaw('DATE(created_at) as d')
                ->groupBy('d')
                ->orderBy('d', 'desc')
                ->limit(30)
                ->pluck('d')
                ->all();

            $trendData = $trendDates
                ? Transaction::query()
                    ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                    ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                    ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                    ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                    ->when($userId,            fn($q) => $q->where('user_id', $userId))
                    ->whereIn(DB::raw('DATE(created_at)'), $trendDates)
                    ->selectRaw('DATE(created_at) as date, currency_id, currency_code, COUNT(*) as count, SUM(gross_amount) as total_amount, SUM(fee_amount) as total_fees')
                    ->groupBy('date', 'currency_id', 'currency_code')
                    ->orderBy('date', 'asc')
                    ->get()
                    ->map(fn($item) => [
                        'date'          => $item->date,
                        'currency_id'   => $item->currency_id,
                        'currency_code' => $item->currency_code,
                        'count'         => (int)   $item->count,
                        'total_amount'  => (float) $item->total_amount,
                        'total_fees'    => (float) $item->total_fees,
                    ])
                : collect();

            // ── Query 5: recent transactions ─────────────────────────────────
            $recentTransactions = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->with([
                    'branch:id,name,code,status',
                    'transactionType:id,name,code',
                    'wallet:id,wallet_number,currency_id,status',
                ])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // ── Query 6: stats by wallet ──────────────────────────────────────
            $byWallet = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
                ->when($userId,            fn($q) => $q->where('user_id', $userId))
                ->whereNotNull('wallet_id')
                ->selectRaw('wallet_id, COUNT(*) as count, SUM(gross_amount) as total_amount, SUM(fee_amount) as total_fees')
                ->groupBy('wallet_id')
                ->with([
                    'wallet:id,wallet_number,operator_id,branch_id,currency_id',
                    'wallet.operator:id,name',
                    'wallet.branch:id,name',
                    'wallet.currency:id,code,symbol',
                ])
                ->orderByDesc('total_amount')
                ->get()
                ->map(fn($item) => [
                    'wallet_id'      => $item->wallet_id,
                    'wallet_number'  => $item->wallet?->wallet_number  ?? 'N/A',
                    'operator_name'  => $item->wallet?->operator?->name ?? 'N/A',
                    'branch_name'    => $item->wallet?->branch?->name   ?? 'N/A',
                    'currency_code'  => $item->wallet?->currency?->code ?? 'N/A',
                    'currency_symbol'=> $item->wallet?->currency?->symbol,
                    'count'          => (int)   $item->count,
                    'total_amount'   => (float) $item->total_amount,
                    'total_fees'     => (float) $item->total_fees,
                ]);

            return [
                'overview' => [
                    'total_transactions' => $totalTransactions,
                    'total_amount'       => $totalAmount,
                    'total_fees'         => $totalFees,
                    'total_net'          => $totalNet,
                    'average_amount'     => round((float) $averageAmount, 2),
                    'success_rate'       => round((float) $successRate, 2),
                ],
                'by_status' => $byStatus,
                'by_type'   => $byType,
                'by_branch' => $byBranch,
                'by_wallet' => $byWallet,
                'trend'     => $trendData,
                'recent_transactions' => TransactionResource::collection($recentTransactions),
            ];
        });
    }
}
