<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\FeeRule;
use Illuminate\Http\Request;

/**
 * Class FeeRuleService
 * @package App\Services\Api
 */
final class FeeRuleService
{
    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return FeeRule
     */
    public function create(array $data): FeeRule
    {
        // Set default is_active if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return FeeRule::create($data);
    }

    /**
     * Update the specified resource in storage.
     * @param FeeRule $feeRule
     * @param array $data
     * @return FeeRule
     */
    public function update(FeeRule $feeRule, array $data): FeeRule
    {
        $feeRule->update($data);
        return $feeRule->fresh();
    }

    /**
     * Remove the specified resource from storage.
     * @param FeeRule $feeRule
     */
    public function destroy(FeeRule $feeRule): void
    {
        $feeRule->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFeeRules(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = $request->input('search');
        $transactionTypeId = $request->input('transaction_type_id');
        $operatorId = $request->input('operator_id');
        $branchId = $request->input('branch_id');
        $feeMode = $request->input('fee_mode');
        $isActive = $request->input('is_active');

        $query = FeeRule::query()
            ->with(['transactionType', 'operator', 'branch'])
            ->select([
                'id', 'uuid', 'transaction_type_id', 'operator_id', 'branch_id',
                'fee_mode', 'value', 'min_fee', 'max_fee', 'is_active',
                'created_at', 'updated_at'
            ]);

        // Apply search filter
        if ($search) {
            $query->whereHas('transactionType', function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('code', 'like', '%' . addcslashes($search, '%_\\') . '%');
            })
            ->orWhereHas('operator', function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
            })
            ->orWhereHas('branch', function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('code', 'like', '%' . addcslashes($search, '%_\\') . '%');
            });
        }

        // Apply transaction type filter
        if ($transactionTypeId) {
            $query->where('transaction_type_id', $transactionTypeId);
        }

        // Apply operator filter
        if ($operatorId !== null) {
            if ($operatorId === 'null' || $operatorId === '') {
                $query->whereNull('operator_id');
            } else {
                $query->where('operator_id', $operatorId);
            }
        }

        // Apply branch filter
        if ($branchId !== null) {
            if ($branchId === 'null' || $branchId === '') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }
        }

        // Apply fee mode filter
        if ($feeMode) {
            $query->where('fee_mode', $feeMode);
        }

        // Apply is_active filter
        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Toggle fee rule active status
     * @param FeeRule $feeRule
     * @return FeeRule
     */
    public function toggleStatus(FeeRule $feeRule): FeeRule
    {
        $feeRule->update(['is_active' => !$feeRule->is_active]);
        
        return $feeRule->fresh();
    }

    /**
     * Get applicable fee rule for a transaction
     * @param int $transactionTypeId
     * @param int|null $operatorId
     * @param int|null $branchId
     * @return FeeRule|null
     */
    public function getApplicableFeeRule(
        int $transactionTypeId,
        ?int $operatorId = null,
        ?int $branchId = null
    ): ?FeeRule {
        // Priority order:
        // 1. Specific to transaction type, operator, and branch
        // 2. Specific to transaction type and operator
        // 3. Specific to transaction type and branch
        // 4. Specific to transaction type only

        $query = FeeRule::where('transaction_type_id', $transactionTypeId)
            ->where('is_active', true);

        // Try to find the most specific rule first
        if ($operatorId && $branchId) {
            $rule = (clone $query)
                ->where('operator_id', $operatorId)
                ->where('branch_id', $branchId)
                ->first();
            if ($rule) return $rule;
        }

        if ($operatorId) {
            $rule = (clone $query)
                ->where('operator_id', $operatorId)
                ->whereNull('branch_id')
                ->first();
            if ($rule) return $rule;
        }

        if ($branchId) {
            $rule = (clone $query)
                ->where('branch_id', $branchId)
                ->whereNull('operator_id')
                ->first();
            if ($rule) return $rule;
        }

        // Finally, try to find a generic rule for the transaction type
        return $query
            ->whereNull('operator_id')
            ->whereNull('branch_id')
            ->first();
    }

    /**
     * Calculate fee amount based on fee rule and transaction amount
     * @param FeeRule $feeRule
     * @param float $amount
     * @return float
     */
    public function calculateFee(FeeRule $feeRule, float $amount): float
    {
        $calculatedFee = 0;

        switch ($feeRule->fee_mode->value) {
            case 'fixed':
                $calculatedFee = (float) $feeRule->value;
                break;
            
            case 'percentage':
                $calculatedFee = ($amount * (float) $feeRule->value) / 100;
                break;
            
            default:
                $calculatedFee = 0;
                break;
        }

        // Apply min_fee constraint
        if ($feeRule->min_fee && $calculatedFee < (float) $feeRule->min_fee) {
            $calculatedFee = (float) $feeRule->min_fee;
        }

        // Apply max_fee constraint
        if ($feeRule->max_fee && $calculatedFee > (float) $feeRule->max_fee) {
            $calculatedFee = (float) $feeRule->max_fee;
        }

        return round($calculatedFee, 2);
    }

    /**
     * Export fee rules to PDF
     * @param Request $request
     * @return mixed
     */
    public function exportToPDF(Request $request)
    {
        $query = FeeRule::query()->with(['transactionType', 'operator', 'branch']);

        // Apply same filters as getFeeRules method
        if ($request->has('transaction_type_id')) {
            $query->where('transaction_type_id', $request->input('transaction_type_id'));
        }

        if ($request->has('operator_id')) {
            $operatorId = $request->input('operator_id');
            if ($operatorId === 'null' || $operatorId === '') {
                $query->whereNull('operator_id');
            } else {
                $query->where('operator_id', $operatorId);
            }
        }

        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id');
            if ($branchId === 'null' || $branchId === '') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', $branchId);
            }
        }

        if ($request->has('fee_mode')) {
            $query->where('fee_mode', $request->input('fee_mode'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Get all matching fee rules (no pagination for export)
        $feeRules = $query->orderBy('created_at', 'desc')->get();

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fee-rules', [
            'feeRules' => $feeRules,
            'date' => now()->format('d/m/Y H:i'),
            'total' => $feeRules->count(),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Return PDF download
        return $pdf->download('fee_rules_' . now()->format('Y-m-d') . '.pdf');
    }
}
