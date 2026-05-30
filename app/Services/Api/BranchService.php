<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BranchStatus;
use App\Models\Branch;
use Illuminate\Http\Request;

/**
 * Class BranchService
 * @package App\Services\Api
 */
final class BranchService
{
    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return Branch
     */
    public function create(array $data): Branch
    {
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = BranchStatus::ACTIVE;
        }

        // Set default cash balance if not provided
        if (!isset($data['cash_balance'])) {
            $data['cash_balance'] = 0;
        }

        return Branch::create($data);
    }

    /**
     * Update the specified resource in storage.
     * @param Branch $branch
     * @param array $data
     * @return Branch
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh();
    }

    /**
     * Remove the specified resource from storage.
     * @param Branch $branch
     */
    public function destroy(Branch $branch): void
    {
        $branch->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getBranches(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 200);
        $search = $request->input('search');
        $status = $request->input('status');
        $countryId = $request->input('country_id');

        $query = Branch::query()
            ->with(['country', 'balances.currency'])
            ->select(['id', 'uuid', 'code', 'name', 'country_id', 'address', 'cash_balance', 'status', 'created_at', 'updated_at']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('code', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('address', 'like', '%' . addcslashes($search, '%_\\') . '%');
            });
        }

        // Apply status filter
        if ($status && in_array($status, BranchStatus::values())) {
            $query->where('status', $status);
        }

        // Apply country filter
        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Toggle branch status between active and inactive
     * @param Branch $branch
     * @return Branch
     */
    public function toggleStatus(Branch $branch): Branch
    {
        $newStatus = $branch->status === BranchStatus::ACTIVE 
            ? BranchStatus::INACTIVE 
            : BranchStatus::ACTIVE;

        $branch->update(['status' => $newStatus]);
        
        return $branch->fresh();
    }

    /**
     * Update branch cash balance
     * @param Branch $branch
     * @param float $amount
     * @param string $operation (add|subtract|set)
     * @return Branch
     */
    public function updateCashBalance(Branch $branch, float $amount, string $operation = 'set'): Branch
    {
        $newBalance = match($operation) {
            'add' => $branch->cash_balance + $amount,
            'subtract' => $branch->cash_balance - $amount,
            'set' => $amount,
            default => $branch->cash_balance,
        };

        // Ensure balance doesn't go negative
        $newBalance = max(0, $newBalance);

        $branch->update(['cash_balance' => $newBalance]);
        
        return $branch->fresh();
    }

    /**
     * Export branches to PDF
     * @param Request $request
     * @return mixed
     */
    public function exportToPDF(Request $request)
    {
        $query = Branch::query()->with('country');

        // Apply same filters as getBranches method
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if (in_array($status, BranchStatus::values())) {
                $query->where('status', $status);
            }
        }

        if ($request->has('country_id')) {
            $countryId = $request->input('country_id');
            if ($countryId) {
                $query->where('country_id', $countryId);
            }
        }

        // Get all matching branches (no pagination for export)
        $branches = $query->orderBy('name')->get();

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.branches', [
            'branches' => $branches,
            'date' => now()->format('d/m/Y H:i'),
            'total' => $branches->count(),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Return PDF download
        return $pdf->download('branches_' . now()->format('Y-m-d') . '.pdf');
    }
}
