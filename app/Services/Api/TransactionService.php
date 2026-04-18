<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
use App\Http\Resources\Api\TransactionResource;
use App\Models\FeeRule;
use App\Models\Transaction;
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

            // Calculate fee if not provided
            if (!isset($data['fee_amount']) || !isset($data['fee_mode_applied'])) {
                $feeCalculation = $this->calculateFee($data);
                $data['fee_amount'] = $feeCalculation['fee_amount'];
                $data['fee_mode_applied'] = $feeCalculation['fee_mode_applied'];
                $data['fee_rule_id'] = $feeCalculation['fee_rule_id'];
                $data['fee_snapshot'] = $feeCalculation['fee_snapshot'];
            }

            $data['id'] = (new Transaction)->newUniqueId();

            // Calculate net amount
            $data['net_amount'] = $data['gross_amount'] - $data['fee_amount'];

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
                $gross = $data['gross_amount'] ?? $transaction->gross_amount;
                $fee = $data['fee_amount'] ?? $transaction->fee_amount;
                $data['net_amount'] = $gross - $fee;
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
                'wallet',
                'feeRule',
                'currency',
            ])
            ->select([
                'id', 'uuid', 'reference', 'transaction_type_id', 'branch_id',
                'destination_branch_id', 'user_id', 'completed_by', 'served_by_branch_id',
                'customer_id', 'wallet_id', 'customer_phone', 'gross_amount', 'fee_amount', 'net_amount',
                'currency_id', 'currency_code',
                'fee_rule_id', 'fee_mode_applied', 'parent_transaction_id',
                'withdrawal_code', 'expires_at', 'completed_at', 'status', 'created_at', 'updated_at'
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

        if ($branchId) {
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
        return DB::transaction(function () use ($transaction) {
            // Load necessary relationships
            $transaction->load(['transactionType', 'branch', 'destinationBranch', 'wallet']);
            
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
        $transactionCode = $transaction->transactionType->code;
        $amount = $transaction->gross_amount;
        $feeAmount = $transaction->fee_amount;
        
        switch ($transactionCode) {
            case 'cash_deposit_transfer':
                // Dépôt cash pour retrait ultérieur
                // Branch source perd du cash
                if ($transaction->branch) {
                    $this->updateBranchCash($transaction->branch, -$amount);
                }
                break;
                
            case 'cash_withdraw_transfer':
                // Retrait d'un transfert
                // Branch destination gagne du cash (mais perd le montant donné au client)
                if ($transaction->destinationBranch) {
                    $this->updateBranchCash($transaction->destinationBranch, -$amount);
                }
                break;
                
            case 'wallet_cash_in':
                // Client envoie wallet → reçoit cash
                // Wallet perd du virtuel, Branch perd du cash
                if ($transaction->wallet) {
                    $this->updateWalletVirtual($transaction->wallet, -$amount);
                }
                if ($transaction->branch) {
                    $this->updateBranchCash($transaction->branch, -($amount - $feeAmount));
                }
                break;
                
            case 'wallet_cash_out':
                // Client donne cash → reçoit wallet
                // Wallet gagne du virtuel, Branch gagne du cash
                if ($transaction->wallet) {
                    $this->updateWalletVirtual($transaction->wallet, $amount - $feeAmount);
                }
                if ($transaction->branch) {
                    $this->updateBranchCash($transaction->branch, $amount);
                }
                break;
                
            case 'tams_deposit':
                // Dépôt Compte TAMS
                // Branch gagne du cash
                if ($transaction->branch) {
                    $this->updateBranchCash($transaction->branch, $amount);
                }
                break;
                
            case 'tams_withdraw':
            case 'tams_debt_withdraw':
                // Retrait Compte TAMS
                // Branch perd du cash
                if ($transaction->branch) {
                    $this->updateBranchCash($transaction->branch, -$amount);
                }
                break;
                
            case 'wallet_to_wallet_transfer':
                // Transfert wallet vers numéro
                // Wallet source perd du virtuel
                if ($transaction->wallet) {
                    $this->updateWalletVirtual($transaction->wallet, -$amount);
                }
                break;
                
            case 'wallet_receive_only':
                // Réception wallet simple
                // Wallet gagne du virtuel
                if ($transaction->wallet) {
                    $this->updateWalletVirtual($transaction->wallet, $amount);
                }
                break;
        }
    }

    /**
     * Update branch cash balance
     * @param \App\Models\Branch $branch
     * @param float $amount
     * @return void
     */
    private function updateBranchCash($branch, float $amount): void
    {
        $newBalance = $branch->cash_balance + $amount;
        
        // Check for negative balance
        if ($newBalance < 0) {
            throw new \Exception(__('Insufficient cash balance in branch. Available: :balance', [
                'balance' => number_format($branch->cash_balance, 2)
            ]));
        }
        
        $branch->update(['cash_balance' => $newBalance]);
    }

    /**
     * Update wallet virtual balance
     * @param \App\Models\Wallet $wallet
     * @param float $amount
     * @return void
     */
    private function updateWalletVirtual($wallet, float $amount): void
    {
        $newBalance = $wallet->virtual_balance + $amount;
        
        // Check for negative balance
        if ($newBalance < 0) {
            throw new \Exception(__('Insufficient virtual balance in wallet :number. Available: :balance', [
                'number' => $wallet->wallet_number,
                'balance' => number_format($wallet->virtual_balance, 2)
            ]));
        }
        
        $wallet->update(['virtual_balance' => $newBalance]);
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

        $grossAmount = $data['gross_amount'];
        $feeAmount = $feeRule->calculateFee($grossAmount);

        return [
            'fee_amount' => $feeAmount,
            'fee_mode_applied' => FeeModeApplied::from($feeRule->fee_mode->value),
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
        $startDate        = $request->input('start_date');
        $endDate          = $request->input('end_date');
        $branchId         = $request->input('branch_id');
        $currencyId       = $request->input('currency_id');
        $transactionTypeId = $request->input('transaction_type_id');

        $cacheKey = 'dashboard:stats:' . md5(json_encode([
            $startDate, $endDate, $branchId, $currencyId, $transactionTypeId,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $startDate, $endDate, $branchId, $currencyId, $transactionTypeId
        ) {
            // ── Query 1: overview KPIs + status breakdown in a single pass ───
            $stats = Transaction::query()
                ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
                ->when($branchId,          fn($q) => $q->where('branch_id', $branchId))
                ->when($currencyId,        fn($q) => $q->where('currency_id', $currencyId))
                ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
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
                ->with([
                    'branch:id,name,code,status',
                    'transactionType:id,name,code',
                    'wallet:id,wallet_number,currency_id,status',
                ])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return [
                'overview' => [
                    'total_transactions' => $totalTransactions,
                    'total_amount'       => $totalAmount,
                    'total_fees'         => $totalFees,
                    'total_net'          => $totalNet,
                    'average_amount'     => round($averageAmount, 2),
                    'success_rate'       => round($successRate, 2),
                ],
                'by_status' => $byStatus,
                'by_type'   => $byType,
                'by_branch' => $byBranch,
                'trend'     => $trendData,
                'recent_transactions' => TransactionResource::collection($recentTransactions),
            ];
        });
    }
}
