<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
use App\Http\Resources\Api\TransactionResource;
use App\Models\FeeRule;
use App\Models\Transaction;
use Illuminate\Http\Request;
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
        $search = $request->input('search');
        $transactionTypeId = $request->input('transaction_type_id');
        $branchId = $request->input('branch_id');
        $userId = $request->input('user_id');
        $customerId = $request->input('customer_id');
        $customerPhone = $request->input('customer_phone');
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Transaction::query()
            ->with([
                'transactionType',
                'branch',
                'destinationBranch',
                'user',
                'customer',
                'wallet',
                'feeRule',
                'currency',
            ])
            ->select([
                'id', 'uuid', 'reference', 'transaction_type_id', 'branch_id',
                'destination_branch_id', 'user_id', 'customer_id', 'wallet_id',
                'customer_phone', 'gross_amount', 'fee_amount', 'net_amount',
                'currency_id', 'currency_code',
                'fee_rule_id', 'fee_mode_applied', 'parent_transaction_id',
                'withdrawal_code', 'expires_at', 'status', 'created_at', 'updated_at'
            ]);

        // Apply search filter
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

        // Apply transaction type filter
        if ($transactionTypeId) {
            $query->where('transaction_type_id', $transactionTypeId);
        }

        // Apply branch filter
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('destination_branch_id', $branchId);
            });
        }

        // Apply user filter
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Apply customer filter
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        // Apply customer phone filter
        if ($customerPhone) {
            $query->where('customer_phone', $customerPhone);
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->latest()->paginate($perPage);
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
        return $this->changeStatus($transaction, TransactionStatus::COMPLETED);
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

        $query = Transaction::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        return [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('gross_amount'),
            'total_fees' => $query->sum('fee_amount'),
            'total_net' => $query->sum('net_amount'),
            'by_status' => [
                'pending' => $query->clone()->pending()->count(),
                'available' => $query->clone()->available()->count(),
                'completed' => $query->clone()->completed()->count(),
                'cancelled' => $query->clone()->where('status', TransactionStatus::CANCELLED->value)->count(),
                'failed' => $query->clone()->where('status', TransactionStatus::FAILED->value)->count(),
                'expired' => $query->clone()->where('status', TransactionStatus::EXPIRED->value)->count(),
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
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branchId = $request->input('branch_id');
        $currencyId = $request->input('currency_id');
        $transactionTypeId = $request->input('transaction_type_id');

        $query = Transaction::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        if ($transactionTypeId) {
            $query->where('transaction_type_id', $transactionTypeId);
        }

        // Basic stats
        $totalTransactions = $query->count();
        $totalAmount = $query->sum('gross_amount');
        $totalFees = $query->sum('fee_amount');
        $totalNet = $query->sum('net_amount');

        // Average transaction value
        $averageAmount = $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0;

        // Status breakdown
        $byStatus = [
            'pending' => $query->clone()->pending()->count(),
            'available' => $query->clone()->available()->count(),
            'completed' => $query->clone()->completed()->count(),
            'cancelled' => $query->clone()->where('status', TransactionStatus::CANCELLED->value)->count(),
            'failed' => $query->clone()->where('status', TransactionStatus::FAILED->value)->count(),
            'expired' => $query->clone()->where('status', TransactionStatus::EXPIRED->value)->count(),
        ];

        // Success rate
        $successfulTransactions = $byStatus['completed'] + $byStatus['available'];
        $successRate = $totalTransactions > 0 ? ($successfulTransactions / $totalTransactions) * 100 : 0;

        // Transactions by type
        $byType = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->selectRaw('transaction_type_id, COUNT(*) as count, SUM(gross_amount) as total_amount')
            ->groupBy('transaction_type_id')
            ->with('transactionType:id,name,code')
            ->get()
            ->map(function ($item) {
                return [
                    'type_id' => $item->transaction_type_id,
                    'type_name' => $item->transactionType->name ?? 'N/A',
                    'type_code' => $item->transactionType->code ?? 'N/A',
                    'count' => $item->count,
                    'total_amount' => $item->total_amount,
                ];
            });

        // Transactions by branch
        $byBranch = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
            ->selectRaw('branch_id, COUNT(*) as count, SUM(gross_amount) as total_amount')
            ->groupBy('branch_id')
            ->with('branch:id,name,code')
            ->get()
            ->map(function ($item) {
                return [
                    'branch_id' => $item->branch_id,
                    'branch_name' => $item->branch->name ?? 'N/A',
                    'branch_code' => $item->branch->code ?? 'N/A',
                    'count' => $item->count,
                    'total_amount' => $item->total_amount,
                ];
            });

        // Daily trend – grouped by date + currency (max 30 distinct dates)
        $trendDates = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
            ->selectRaw('DATE(created_at) as d')
            ->groupBy('d')
            ->orderBy('d', 'desc')
            ->limit(30)
            ->pluck('d');

        $trendQuery = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
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
                'count'         => (int) $item->count,
                'total_amount'  => (float) $item->total_amount,
                'total_fees'    => (float) $item->total_fees,
            ]);

        // Recent transactions
        $recentTransactions = Transaction::query()
            ->when($startDate && $endDate, fn($q) => $q->dateRange($startDate, $endDate))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currencyId, fn($q) => $q->where('currency_id', $currencyId))
            ->when($transactionTypeId, fn($q) => $q->where('transaction_type_id', $transactionTypeId))
            ->with(['branch:id,name,code,status', 'transactionType:id,name,code', 'wallet:id,wallet_number,currency_id,status'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'overview' => [
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount,
                'total_fees' => $totalFees,
                'total_net' => $totalNet,
                'average_amount' => round($averageAmount, 2),
                'success_rate' => round($successRate, 2),
            ],
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_branch' => $byBranch,
            'trend' => $trendQuery,
            'recent_transactions' => TransactionResource::collection($recentTransactions),
        ];
    }
}
