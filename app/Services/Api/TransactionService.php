<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
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

        $query = Transaction::query();

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
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
}
